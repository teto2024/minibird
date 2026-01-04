<?php
// ===============================================
// civilization_mail_api.php
// 文明育成ゲーム メールシステムAPI
// ===============================================

require_once __DIR__ . '/config.php';

// 偵察システム定数
define('RECONNAISSANCE_WAR_LIMIT_PER_HOUR', 5);        // 戦争偵察：1時間に5回まで
define('RECONNAISSANCE_CONQUEST_LIMIT_PER_HOUR', 15);  // 占領戦偵察：1時間に15回まで
define('RECONNAISSANCE_FAILURE_RATE', 30);             // 偵察失敗率：30%
define('RECONNAISSANCE_STEALTH_ERROR_MIN', 25);        // ステルス部隊の誤差：25%〜175%
define('RECONNAISSANCE_STEALTH_ERROR_MAX', 175);

header('Content-Type: application/json');

$me = user();
if (!$me) {
    echo json_encode(['ok' => false, 'error' => 'login_required']);
    exit;
}

$pdo = db();
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $input['action'] ?? '';

// ゲームメンテナンスモードのチェック
check_game_maintenance();

/**
 * ユーザーハンドルを取得するヘルパー関数
 */
function getUserHandle($pdo, $userId) {
    if ($userId === null) {
        return null;
    }
    $stmt = $pdo->prepare("SELECT handle FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? '@' . $result['handle'] : null;
}

/**
 * ユーザーの文明名を取得するヘルパー関数
 */
function getCivilizationName($pdo, $userId) {
    if ($userId === null) {
        return null;
    }
    $stmt = $pdo->prepare("SELECT civilization_name FROM user_civilizations WHERE user_id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['civilization_name'] : null;
}

/**
 * 管理者かどうかをチェック
 */
function isAdminUser($user) {
    return $user && isset($user['role']) && $user['role'] === 'admin';
}

/**
 * メールを作成するヘルパー関数
 */
function createMail($pdo, $mailType, $senderUserId, $recipientUserId, $subject, $body, $extraData = null, $compensation = null) {
    $stmt = $pdo->prepare("
        INSERT INTO civilization_mails (mail_type, sender_user_id, recipient_user_id, subject, body, extra_data, compensation)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $mailType,
        $senderUserId,
        $recipientUserId,
        $subject,
        $body,
        $extraData ? json_encode($extraData) : null,
        $compensation ? json_encode($compensation) : null
    ]);
    return $pdo->lastInsertId();
}

/**
 * 部隊情報を整形するヘルパー関数（ステルス部隊の可視化あり）
 */
function formatTroopInfo($pdo, $troops, $includeHidden = true) {
    $formatted = [];
    foreach ($troops as $troop) {
        $troopTypeId = $troop['troop_type_id'];
        $count = (int)$troop['count'];
        
        $stmt = $pdo->prepare("SELECT name, icon, COALESCE(is_stealth, FALSE) as is_stealth FROM civilization_troop_types WHERE id = ?");
        $stmt->execute([$troopTypeId]);
        $troopType = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($troopType) {
            $formatted[] = [
                'troop_type_id' => $troopTypeId,
                'name' => $troopType['name'],
                'icon' => $troopType['icon'],
                'count' => $count,
                'is_stealth' => !empty($troopType['is_stealth'])
            ];
        }
    }
    return $formatted;
}

/**
 * 偵察結果の部隊数に誤差を加える（ステルス部隊用）
 */
function applyStealthError($count, $isStealth) {
    if (!$isStealth) {
        return $count;
    }
    // 25%〜175%の誤差を適用（random_int使用）
    $errorPercent = random_int(RECONNAISSANCE_STEALTH_ERROR_MIN, RECONNAISSANCE_STEALTH_ERROR_MAX);
    return (int)round($count * $errorPercent / 100);
}

/**
 * 偵察レート制限をチェック
 */
function checkReconnaissanceRateLimit($pdo, $userId, $type) {
    $limit = ($type === 'war') ? RECONNAISSANCE_WAR_LIMIT_PER_HOUR : RECONNAISSANCE_CONQUEST_LIMIT_PER_HOUR;
    $oneHourAgo = date('Y-m-d H:i:s', strtotime('-1 hour'));
    
    // 過去1時間以内の偵察回数と最も古い偵察時刻を取得
    $stmt = $pdo->prepare("
        SELECT created_at
        FROM civilization_reconnaissance_logs
        WHERE user_id = ? AND reconnaissance_type = ? AND created_at >= ?
        ORDER BY created_at ASC
    ");
    $stmt->execute([$userId, $type, $oneHourAgo]);
    $logs = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $currentCount = count($logs);
    $remaining = max(0, $limit - $currentCount);
    $isLimited = $remaining <= 0;
    
    $waitSeconds = 0;
    $nextAvailable = null;
    
    // レート制限中の場合、次の偵察可能時刻を計算
    if ($isLimited && !empty($logs)) {
        $oldestLog = $logs[0];
        $nextAvailable = date('Y-m-d H:i:s', strtotime($oldestLog . ' +1 hour'));
        $waitSeconds = max(0, strtotime($nextAvailable) - time());
    }
    
    return [
        'allowed' => $remaining > 0,
        'limit' => $limit,
        'used' => $currentCount,
        'remaining' => $remaining,
        'is_limited' => $isLimited,
        'wait_seconds' => $waitSeconds,
        'next_available' => $nextAvailable
    ];
}

// ===============================================
// メール一覧取得
// ===============================================
if ($action === 'get_mails') {
    $page = max(1, (int)($input['page'] ?? 1));
    $perPage = min(50, max(10, (int)($input['per_page'] ?? 20)));
    $mailType = $input['mail_type'] ?? null; // null: 全て, info, war, conquest, reconnaissance
    $offset = ($page - 1) * $perPage;
    
    // 整数型にキャスト済みのため、SQLインジェクションの危険性なし
    $limitVal = (int)$perPage;
    $offsetVal = (int)$offset;
    
    try {
        // 条件構築
        $conditions = ["(m.recipient_user_id = ? OR m.recipient_user_id IS NULL)"];
        $params = [$me['id']];
        
        if ($mailType && in_array($mailType, ['info', 'war', 'conquest', 'reconnaissance'])) {
            $conditions[] = "m.mail_type = ?";
            $params[] = $mailType;
        }
        
        $whereClause = implode(' AND ', $conditions);
        
        // メール取得
        // LIMIT/OFFSETは整数値を直接SQLに埋め込む（整数キャスト済みのため安全）
        $sql = "
            SELECT 
                m.*,
                COALESCE(mrs.is_read, FALSE) as is_read,
                mrs.read_at,
                COALESCE(mrs.compensation_claimed, FALSE) as compensation_claimed,
                sender.handle as sender_handle,
                sender.display_name as sender_name
            FROM civilization_mails m
            LEFT JOIN civilization_mail_read_status mrs ON m.id = mrs.mail_id AND mrs.user_id = ?
            LEFT JOIN users sender ON m.sender_user_id = sender.id
            WHERE {$whereClause}
            ORDER BY m.created_at DESC
            LIMIT {$limitVal} OFFSET {$offsetVal}
        ";
        $stmt = $pdo->prepare($sql);
        $allParams = array_merge([$me['id']], $params);
        $stmt->execute($allParams);
        $mails = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 総件数を取得
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM civilization_mails m
            WHERE {$whereClause}
        ");
        $stmt->execute($params);
        $totalCount = (int)$stmt->fetchColumn();
        
        // 未読件数を取得
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as unread_count
            FROM civilization_mails m
            LEFT JOIN civilization_mail_read_status mrs ON m.id = mrs.mail_id AND mrs.user_id = ?
            WHERE (m.recipient_user_id = ? OR m.recipient_user_id IS NULL)
              AND (mrs.is_read IS NULL OR mrs.is_read = FALSE)
        ");
        $stmt->execute([$me['id'], $me['id']]);
        $unreadCount = (int)$stmt->fetchColumn();
        
        // メールを整形
        foreach ($mails as &$mail) {
            // 差出人名を設定
            if ($mail['sender_user_id'] === null) {
                $mail['sender_display'] = 'システム';
            } else {
                $mail['sender_display'] = '@' . $mail['sender_handle'];
            }
            
            // 受取人名を設定
            if ($mail['recipient_user_id'] === null) {
                $mail['recipient_display'] = 'プレイヤー';
            } else {
                $mail['recipient_display'] = '@' . getUserHandle($pdo, $mail['recipient_user_id']);
            }
            
            // JSONデータをデコード
            $mail['extra_data'] = json_decode($mail['extra_data'], true);
            $mail['compensation'] = json_decode($mail['compensation'], true);
        }
        unset($mail);
        
        echo json_encode([
            'ok' => true,
            'mails' => $mails,
            'total_count' => $totalCount,
            'unread_count' => $unreadCount,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($totalCount / $perPage)
        ]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ===============================================
// メール詳細取得
// ===============================================
if ($action === 'get_mail_detail') {
    $mailId = (int)($input['mail_id'] ?? 0);
    
    if ($mailId <= 0) {
        echo json_encode(['ok' => false, 'error' => 'メールIDが指定されていません']);
        exit;
    }
    
    try {
        // メール取得
        $stmt = $pdo->prepare("
            SELECT 
                m.*,
                COALESCE(mrs.is_read, FALSE) as is_read,
                mrs.read_at,
                COALESCE(mrs.compensation_claimed, FALSE) as compensation_claimed,
                sender.handle as sender_handle,
                sender.display_name as sender_name
            FROM civilization_mails m
            LEFT JOIN civilization_mail_read_status mrs ON m.id = mrs.mail_id AND mrs.user_id = ?
            LEFT JOIN users sender ON m.sender_user_id = sender.id
            WHERE m.id = ? AND (m.recipient_user_id = ? OR m.recipient_user_id IS NULL)
        ");
        $stmt->execute([$me['id'], $mailId, $me['id']]);
        $mail = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$mail) {
            echo json_encode(['ok' => false, 'error' => 'メールが見つかりません']);
            exit;
        }
        
        // 既読にする
        $stmt = $pdo->prepare("
            INSERT INTO civilization_mail_read_status (mail_id, user_id, is_read, read_at)
            VALUES (?, ?, TRUE, NOW())
            ON DUPLICATE KEY UPDATE is_read = TRUE, read_at = COALESCE(read_at, NOW())
        ");
        $stmt->execute([$mailId, $me['id']]);
        
        // 差出人・受取人名を設定
        if ($mail['sender_user_id'] === null) {
            $mail['sender_display'] = 'システム';
        } else {
            $mail['sender_display'] = '@' . $mail['sender_handle'];
        }
        
        if ($mail['recipient_user_id'] === null) {
            $mail['recipient_display'] = 'プレイヤー';
        } else {
            $handle = getUserHandle($pdo, $mail['recipient_user_id']);
            $mail['recipient_display'] = $handle ?: 'プレイヤー';
        }
        
        // JSONデータをデコード
        $mail['extra_data'] = json_decode($mail['extra_data'], true);
        $mail['compensation'] = json_decode($mail['compensation'], true);
        $mail['is_read'] = true;
        
        echo json_encode([
            'ok' => true,
            'mail' => $mail
        ]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ===============================================
// 補填を受け取る
// ===============================================
if ($action === 'claim_compensation') {
    $mailId = (int)($input['mail_id'] ?? 0);
    
    if ($mailId <= 0) {
        echo json_encode(['ok' => false, 'error' => 'メールIDが指定されていません']);
        exit;
    }
    
    $pdo->beginTransaction();
    try {
        // メール取得
        $stmt = $pdo->prepare("
            SELECT m.*, mrs.compensation_claimed
            FROM civilization_mails m
            LEFT JOIN civilization_mail_read_status mrs ON m.id = mrs.mail_id AND mrs.user_id = ?
            WHERE m.id = ? AND (m.recipient_user_id = ? OR m.recipient_user_id IS NULL)
        ");
        $stmt->execute([$me['id'], $mailId, $me['id']]);
        $mail = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$mail) {
            throw new Exception('メールが見つかりません');
        }
        
        if ($mail['compensation_claimed']) {
            throw new Exception('補填は既に受け取り済みです');
        }
        
        $compensation = json_decode($mail['compensation'], true);
        if (empty($compensation)) {
            throw new Exception('このメールには補填がありません');
        }
        
        // 補填を付与
        $receivedItems = [];
        
        // コイン
        if (!empty($compensation['coins'])) {
            $stmt = $pdo->prepare("UPDATE users SET coins = coins + ? WHERE id = ?");
            $stmt->execute([$compensation['coins'], $me['id']]);
            $receivedItems['coins'] = $compensation['coins'];
        }
        
        // クリスタル
        if (!empty($compensation['crystals'])) {
            $stmt = $pdo->prepare("UPDATE users SET crystals = crystals + ? WHERE id = ?");
            $stmt->execute([$compensation['crystals'], $me['id']]);
            $receivedItems['crystals'] = $compensation['crystals'];
        }
        
        // ダイヤモンド
        if (!empty($compensation['diamonds'])) {
            $stmt = $pdo->prepare("UPDATE users SET diamonds = diamonds + ? WHERE id = ?");
            $stmt->execute([$compensation['diamonds'], $me['id']]);
            $receivedItems['diamonds'] = $compensation['diamonds'];
        }
        
        // 資源
        if (!empty($compensation['resources']) && is_array($compensation['resources'])) {
            foreach ($compensation['resources'] as $resourceKey => $amount) {
                $stmt = $pdo->prepare("
                    UPDATE user_civilization_resources ucr
                    JOIN civilization_resource_types rt ON ucr.resource_type_id = rt.id
                    SET ucr.amount = ucr.amount + ?
                    WHERE ucr.user_id = ? AND rt.resource_key = ?
                ");
                $stmt->execute([$amount, $me['id'], $resourceKey]);
                $receivedItems['resources'][$resourceKey] = $amount;
            }
        }
        
        // 受け取り済みにマーク
        $stmt = $pdo->prepare("
            INSERT INTO civilization_mail_read_status (mail_id, user_id, is_read, read_at, compensation_claimed, compensation_claimed_at)
            VALUES (?, ?, TRUE, NOW(), TRUE, NOW())
            ON DUPLICATE KEY UPDATE compensation_claimed = TRUE, compensation_claimed_at = NOW()
        ");
        $stmt->execute([$mailId, $me['id']]);
        
        $pdo->commit();
        
        echo json_encode([
            'ok' => true,
            'message' => '補填を受け取りました！',
            'received_items' => $receivedItems
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ===============================================
// 管理者: 全体メールを送信
// ===============================================
if ($action === 'send_broadcast_mail') {
    if (!isAdminUser($me)) {
        echo json_encode(['ok' => false, 'error' => '管理者権限が必要です']);
        exit;
    }
    
    $subject = trim($input['subject'] ?? '');
    $body = trim($input['body'] ?? '');
    $compensation = $input['compensation'] ?? null;
    
    if (empty($subject) || empty($body)) {
        echo json_encode(['ok' => false, 'error' => '件名と本文は必須です']);
        exit;
    }
    
    $pdo->beginTransaction();
    try {
        // 補填データの検証
        $validCompensation = null;
        if (!empty($compensation)) {
            $validCompensation = [];
            if (!empty($compensation['coins']) && is_numeric($compensation['coins'])) {
                $validCompensation['coins'] = (int)$compensation['coins'];
            }
            if (!empty($compensation['crystals']) && is_numeric($compensation['crystals'])) {
                $validCompensation['crystals'] = (int)$compensation['crystals'];
            }
            if (!empty($compensation['diamonds']) && is_numeric($compensation['diamonds'])) {
                $validCompensation['diamonds'] = (int)$compensation['diamonds'];
            }
            if (!empty($compensation['resources']) && is_array($compensation['resources'])) {
                $validCompensation['resources'] = [];
                foreach ($compensation['resources'] as $key => $amount) {
                    if (is_numeric($amount)) {
                        $validCompensation['resources'][$key] = (int)$amount;
                    }
                }
            }
            if (empty($validCompensation)) {
                $validCompensation = null;
            }
        }
        
        // メール作成
        $mailId = createMail(
            $pdo,
            'info',
            $me['id'],  // 管理人のID
            null,       // 全員宛て
            $subject,
            $body,
            null,
            $validCompensation
        );
        
        $pdo->commit();
        
        echo json_encode([
            'ok' => true,
            'message' => '全体メールを送信しました',
            'mail_id' => $mailId
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ===============================================
// 管理者: 個別メールを送信
// ===============================================
if ($action === 'send_individual_mail') {
    if (!isAdminUser($me)) {
        echo json_encode(['ok' => false, 'error' => '管理者権限が必要です']);
        exit;
    }
    
    $recipientUserId = (int)($input['recipient_user_id'] ?? 0);
    $subject = trim($input['subject'] ?? '');
    $body = trim($input['body'] ?? '');
    $compensation = $input['compensation'] ?? null;
    
    if ($recipientUserId <= 0) {
        echo json_encode(['ok' => false, 'error' => '受取人を指定してください']);
        exit;
    }
    
    if (empty($subject) || empty($body)) {
        echo json_encode(['ok' => false, 'error' => '件名と本文は必須です']);
        exit;
    }
    
    $pdo->beginTransaction();
    try {
        // 受取人の存在確認
        $stmt = $pdo->prepare("SELECT 1 FROM users WHERE id = ?");
        $stmt->execute([$recipientUserId]);
        if (!$stmt->fetch()) {
            throw new Exception('受取人が存在しません');
        }
        
        // 補填データの検証
        $validCompensation = null;
        if (!empty($compensation)) {
            $validCompensation = [];
            if (!empty($compensation['coins']) && is_numeric($compensation['coins'])) {
                $validCompensation['coins'] = (int)$compensation['coins'];
            }
            if (!empty($compensation['crystals']) && is_numeric($compensation['crystals'])) {
                $validCompensation['crystals'] = (int)$compensation['crystals'];
            }
            if (!empty($compensation['diamonds']) && is_numeric($compensation['diamonds'])) {
                $validCompensation['diamonds'] = (int)$compensation['diamonds'];
            }
            if (!empty($compensation['resources']) && is_array($compensation['resources'])) {
                $validCompensation['resources'] = [];
                foreach ($compensation['resources'] as $key => $amount) {
                    if (is_numeric($amount)) {
                        $validCompensation['resources'][$key] = (int)$amount;
                    }
                }
            }
            if (empty($validCompensation)) {
                $validCompensation = null;
            }
        }
        
        // メール作成
        $mailId = createMail(
            $pdo,
            'info',
            $me['id'],
            $recipientUserId,
            $subject,
            $body,
            null,
            $validCompensation
        );
        
        $pdo->commit();
        
        echo json_encode([
            'ok' => true,
            'message' => 'メールを送信しました',
            'mail_id' => $mailId
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ===============================================
// 偵察レート制限状態を取得
// ===============================================
if ($action === 'get_reconnaissance_rate_limit') {
    try {
        $warLimit = checkReconnaissanceRateLimit($pdo, $me['id'], 'war');
        $conquestLimit = checkReconnaissanceRateLimit($pdo, $me['id'], 'conquest');
        
        echo json_encode([
            'ok' => true,
            'war' => $warLimit,
            'conquest' => $conquestLimit
        ]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ===============================================
// 戦争偵察を実行
// ===============================================
if ($action === 'reconnaissance_war') {
    $targetUserId = (int)($input['target_user_id'] ?? 0);
    
    if ($targetUserId <= 0) {
        echo json_encode(['ok' => false, 'error' => '偵察対象を指定してください']);
        exit;
    }
    
    if ($targetUserId === $me['id']) {
        echo json_encode(['ok' => false, 'error' => '自分を偵察することはできません']);
        exit;
    }
    
    $pdo->beginTransaction();
    try {
        // レート制限チェック
        $rateLimit = checkReconnaissanceRateLimit($pdo, $me['id'], 'war');
        if (!$rateLimit['allowed']) {
            throw new Exception("偵察回数の上限（1時間に{$rateLimit['limit']}回）に達しました。しばらくお待ちください。");
        }
        
        // 対象ユーザーの存在確認
        $stmt = $pdo->prepare("SELECT u.id, u.handle, uc.civilization_name FROM users u LEFT JOIN user_civilizations uc ON u.id = uc.user_id WHERE u.id = ?");
        $stmt->execute([$targetUserId]);
        $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$targetUser) {
            throw new Exception('偵察対象が存在しません');
        }
        
        // 偵察成功/失敗判定（30%で失敗、random_int使用）
        $isSuccessful = random_int(1, 100) > RECONNAISSANCE_FAILURE_RATE;
        
        if (!$isSuccessful) {
            // 失敗ログを記録
            $stmt = $pdo->prepare("
                INSERT INTO civilization_reconnaissance_logs (user_id, reconnaissance_type, target_user_id, is_successful)
                VALUES (?, 'war', ?, FALSE)
            ");
            $stmt->execute([$me['id'], $targetUserId]);
            
            $pdo->commit();
            
            echo json_encode([
                'ok' => true,
                'success' => false,
                'message' => '偵察に失敗しました。情報を得られませんでした。',
                'rate_limit' => [
                    'used' => $rateLimit['used'] + 1,
                    'remaining' => $rateLimit['remaining'] - 1,
                    'limit' => $rateLimit['limit']
                ]
            ]);
            exit;
        }
        
        // 防御部隊情報を取得
        $stmt = $pdo->prepare("
            SELECT udt.troop_type_id, udt.assigned_count as count
            FROM user_civilization_defense_troops udt
            WHERE udt.user_id = ? AND udt.assigned_count > 0
        ");
        $stmt->execute([$targetUserId]);
        $defenseTroops = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 防御設定がない場合は全兵士を使用
        if (empty($defenseTroops)) {
            $stmt = $pdo->prepare("
                SELECT troop_type_id, count FROM user_civilization_troops
                WHERE user_id = ? AND count > 0
            ");
            $stmt->execute([$targetUserId]);
            $defenseTroops = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        // 部隊情報を整形（ステルス部隊は誤差を加える）
        $formattedTroops = [];
        foreach ($defenseTroops as $troop) {
            $stmt = $pdo->prepare("SELECT name, icon, COALESCE(is_stealth, FALSE) as is_stealth FROM civilization_troop_types WHERE id = ?");
            $stmt->execute([$troop['troop_type_id']]);
            $troopType = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($troopType) {
                $isStealth = !empty($troopType['is_stealth']);
                $displayCount = applyStealthError($troop['count'], $isStealth);
                
                $formattedTroops[] = [
                    'troop_type_id' => $troop['troop_type_id'],
                    'name' => $troopType['name'],
                    'icon' => $troopType['icon'],
                    'count' => $displayCount,
                    'is_stealth' => $isStealth,
                    'is_approximate' => $isStealth
                ];
            }
        }
        
        // 自分の情報
        $myHandle = '@' . $me['handle'];
        $stmt = $pdo->prepare("SELECT civilization_name FROM user_civilizations WHERE user_id = ?");
        $stmt->execute([$me['id']]);
        $myCiv = $stmt->fetch(PDO::FETCH_ASSOC);
        $myCivName = $myCiv ? $myCiv['civilization_name'] : '不明';
        
        // メール本文作成
        $targetHandle = '@' . $targetUser['handle'];
        $targetCivName = $targetUser['civilization_name'] ?? '不明';
        
        $troopListText = '';
        foreach ($formattedTroops as $t) {
            $approx = $t['is_approximate'] ? '約' : '';
            $troopListText .= "・{$t['icon']} {$t['name']}: {$approx}{$t['count']}体\n";
        }
        
        if (empty($troopListText)) {
            $troopListText = "防御部隊は配置されていません。\n";
        }
        
        $mailBody = "【偵察報告】\n\n";
        $mailBody .= "対象: {$targetCivName} ({$targetHandle})\n\n";
        $mailBody .= "■ 防御部隊:\n{$troopListText}\n";
        $mailBody .= "※ステルス部隊の数値には誤差が含まれる場合があります。";
        
        // メールを作成
        $mailId = createMail(
            $pdo,
            'reconnaissance',
            null,  // システムから
            $me['id'],
            "偵察報告: {$targetCivName}",
            $mailBody,
            [
                'reconnaissance_type' => 'war',
                'target_user_id' => $targetUserId,
                'target_handle' => $targetHandle,
                'target_civilization' => $targetCivName,
                'troops' => $formattedTroops
            ]
        );
        
        // 偵察ログを記録
        $stmt = $pdo->prepare("
            INSERT INTO civilization_reconnaissance_logs (user_id, reconnaissance_type, target_user_id, is_successful, result_mail_id)
            VALUES (?, 'war', ?, TRUE, ?)
        ");
        $stmt->execute([$me['id'], $targetUserId, $mailId]);
        
        // 偵察された側にもメールを送信
        $reconMailBody = "【偵察警報】\n\n";
        $reconMailBody .= "{$myCivName} ({$myHandle}) があなたの文明を偵察しました。\n\n";
        $reconMailBody .= "防御部隊の情報が漏洩した可能性があります。";
        
        createMail(
            $pdo,
            'reconnaissance',
            null,
            $targetUserId,
            "偵察警報: {$myCivName}",
            $reconMailBody,
            [
                'reconnaissance_type' => 'war',
                'scout_user_id' => $me['id'],
                'scout_handle' => $myHandle,
                'scout_civilization' => $myCivName
            ]
        );
        
        $pdo->commit();
        
        echo json_encode([
            'ok' => true,
            'success' => true,
            'message' => '偵察に成功しました！結果はメールに送信されました。',
            'mail_id' => $mailId,
            'troops' => $formattedTroops,
            'rate_limit' => [
                'used' => $rateLimit['used'] + 1,
                'remaining' => $rateLimit['remaining'] - 1,
                'limit' => $rateLimit['limit']
            ]
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ===============================================
// 占領戦偵察を実行
// ===============================================
if ($action === 'reconnaissance_conquest') {
    $castleId = (int)($input['castle_id'] ?? 0);
    
    if ($castleId <= 0) {
        echo json_encode(['ok' => false, 'error' => '偵察対象の城を指定してください']);
        exit;
    }
    
    $pdo->beginTransaction();
    try {
        // レート制限チェック
        $rateLimit = checkReconnaissanceRateLimit($pdo, $me['id'], 'conquest');
        if (!$rateLimit['allowed']) {
            throw new Exception("偵察回数の上限（1時間に{$rateLimit['limit']}回）に達しました。しばらくお待ちください。");
        }
        
        // 城の情報取得
        $stmt = $pdo->prepare("
            SELECT cc.*, cs.season_number
            FROM conquest_castles cc
            JOIN conquest_seasons cs ON cc.season_id = cs.id
            WHERE cc.id = ? AND cs.is_active = TRUE
        ");
        $stmt->execute([$castleId]);
        $castle = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$castle) {
            throw new Exception('城が見つかりません');
        }
        
        if ($castle['owner_user_id'] == $me['id']) {
            throw new Exception('自分の城を偵察することはできません');
        }
        
        // 偵察成功/失敗判定（30%で失敗、random_int使用）
        $isSuccessful = random_int(1, 100) > RECONNAISSANCE_FAILURE_RATE;
        
        if (!$isSuccessful) {
            // 失敗ログを記録
            $stmt = $pdo->prepare("
                INSERT INTO civilization_reconnaissance_logs (user_id, reconnaissance_type, target_castle_id, is_successful)
                VALUES (?, 'conquest', ?, FALSE)
            ");
            $stmt->execute([$me['id'], $castleId]);
            
            $pdo->commit();
            
            echo json_encode([
                'ok' => true,
                'success' => false,
                'message' => '偵察に失敗しました。情報を得られませんでした。',
                'rate_limit' => [
                    'used' => $rateLimit['used'] + 1,
                    'remaining' => $rateLimit['remaining'] - 1,
                    'limit' => $rateLimit['limit']
                ]
            ]);
            exit;
        }
        
        // 城の所有者情報
        $ownerHandle = null;
        $ownerCivName = null;
        if ($castle['owner_user_id']) {
            $stmt = $pdo->prepare("SELECT u.handle, uc.civilization_name FROM users u LEFT JOIN user_civilizations uc ON u.id = uc.user_id WHERE u.id = ?");
            $stmt->execute([$castle['owner_user_id']]);
            $owner = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($owner) {
                $ownerHandle = '@' . $owner['handle'];
                $ownerCivName = $owner['civilization_name'] ?? '不明';
            }
        }
        
        // 駐屯部隊情報を取得（複数ユーザーの部隊を集計）
        $stmt = $pdo->prepare("
            SELECT ccd.troop_type_id, SUM(ccd.count) as count
            FROM conquest_castle_defense ccd
            WHERE ccd.castle_id = ? AND ccd.count > 0
            GROUP BY ccd.troop_type_id
        ");
        $stmt->execute([$castleId]);
        $garrisons = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 部隊情報を整形（ステルス部隊は誤差を加える）
        $formattedTroops = [];
        foreach ($garrisons as $garrison) {
            $stmt = $pdo->prepare("SELECT name, icon, COALESCE(is_stealth, FALSE) as is_stealth FROM civilization_troop_types WHERE id = ?");
            $stmt->execute([$garrison['troop_type_id']]);
            $troopType = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($troopType) {
                $isStealth = !empty($troopType['is_stealth']);
                $displayCount = applyStealthError($garrison['count'], $isStealth);
                
                $formattedTroops[] = [
                    'troop_type_id' => $garrison['troop_type_id'],
                    'name' => $troopType['name'],
                    'icon' => $troopType['icon'],
                    'count' => $displayCount,
                    'is_stealth' => $isStealth,
                    'is_approximate' => $isStealth
                ];
            }
        }
        
        // 自分の情報
        $myHandle = '@' . $me['handle'];
        $stmt = $pdo->prepare("SELECT civilization_name FROM user_civilizations WHERE user_id = ?");
        $stmt->execute([$me['id']]);
        $myCiv = $stmt->fetch(PDO::FETCH_ASSOC);
        $myCivName = $myCiv ? $myCiv['civilization_name'] : '不明';
        
        // メール本文作成
        $castleName = $castle['name'] ?? '城';
        $castleCoords = "({$castle['position_x']}, {$castle['position_y']})";
        
        $troopListText = '';
        foreach ($formattedTroops as $t) {
            $approx = $t['is_approximate'] ? '約' : '';
            $troopListText .= "・{$t['icon']} {$t['name']}: {$approx}{$t['count']}体\n";
        }
        
        if (empty($troopListText)) {
            $troopListText = "駐屯部隊はいません。\n";
        }
        
        $mailBody = "【偵察報告】\n\n";
        $mailBody .= "城名: {$castleName}\n";
        $mailBody .= "座標: {$castleCoords}\n";
        if ($ownerCivName) {
            $mailBody .= "所有者: {$ownerCivName} ({$ownerHandle})\n";
        } else {
            $mailBody .= "所有者: NPC\n";
        }
        $mailBody .= "\n■ 駐屯部隊:\n{$troopListText}\n";
        $mailBody .= "※ステルス部隊の数値には誤差が含まれる場合があります。";
        
        // メールを作成
        $mailId = createMail(
            $pdo,
            'reconnaissance',
            null,  // システムから
            $me['id'],
            "偵察報告: {$castleName} {$castleCoords}",
            $mailBody,
            [
                'reconnaissance_type' => 'conquest',
                'castle_id' => $castleId,
                'castle_name' => $castleName,
                'castle_coords' => ['x' => $castle['position_x'], 'y' => $castle['position_y']],
                'owner_user_id' => $castle['owner_user_id'],
                'owner_handle' => $ownerHandle,
                'owner_civilization' => $ownerCivName,
                'troops' => $formattedTroops
            ]
        );
        
        // 偵察ログを記録
        $stmt = $pdo->prepare("
            INSERT INTO civilization_reconnaissance_logs (user_id, reconnaissance_type, target_castle_id, is_successful, result_mail_id)
            VALUES (?, 'conquest', ?, TRUE, ?)
        ");
        $stmt->execute([$me['id'], $castleId, $mailId]);
        
        // 城の所有者にもメールを送信（所有者がいる場合）
        if ($castle['owner_user_id']) {
            $reconMailBody = "【偵察警報】\n\n";
            $reconMailBody .= "{$myCivName} ({$myHandle}) があなたの城を偵察しました。\n\n";
            $reconMailBody .= "城名: {$castleName}\n";
            $reconMailBody .= "座標: {$castleCoords}\n\n";
            $reconMailBody .= "駐屯部隊の情報が漏洩した可能性があります。";
            
            createMail(
                $pdo,
                'reconnaissance',
                null,
                $castle['owner_user_id'],
                "偵察警報: {$castleName}",
                $reconMailBody,
                [
                    'reconnaissance_type' => 'conquest',
                    'castle_id' => $castleId,
                    'castle_name' => $castleName,
                    'castle_coords' => ['x' => $castle['position_x'], 'y' => $castle['position_y']],
                    'scout_user_id' => $me['id'],
                    'scout_handle' => $myHandle,
                    'scout_civilization' => $myCivName
                ]
            );
        }
        
        $pdo->commit();
        
        echo json_encode([
            'ok' => true,
            'success' => true,
            'message' => '偵察に成功しました！結果はメールに送信されました。',
            'mail_id' => $mailId,
            'castle_name' => $castleName,
            'castle_coords' => $castleCoords,
            'owner_civilization' => $ownerCivName,
            'troops' => $formattedTroops,
            'rate_limit' => [
                'used' => $rateLimit['used'] + 1,
                'remaining' => $rateLimit['remaining'] - 1,
                'limit' => $rateLimit['limit']
            ]
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ===============================================
// 未読メール数を取得
// ===============================================
if ($action === 'get_unread_count') {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as unread_count
            FROM civilization_mails m
            LEFT JOIN civilization_mail_read_status mrs ON m.id = mrs.mail_id AND mrs.user_id = ?
            WHERE (m.recipient_user_id = ? OR m.recipient_user_id IS NULL)
              AND (mrs.is_read IS NULL OR mrs.is_read = FALSE)
        ");
        $stmt->execute([$me['id'], $me['id']]);
        $unreadCount = (int)$stmt->fetchColumn();
        
        echo json_encode([
            'ok' => true,
            'unread_count' => $unreadCount
        ]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['ok' => false, 'error' => 'invalid_action']);
