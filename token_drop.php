<?php
/**
 * トークンドロップシステム
 * 各種アクションでトークンをドロップする
 */

require_once __DIR__ . '/config.php';

/**
 * トークンをドロップする
 * 
 * @param int $user_id ユーザーID
 * @param string $action アクション種類（focus_success, focus_fail, post, quote, reply, like, repost, boost）
 * @param int $minutes 集中タイマーの場合の実施時間（分）
 * @return array ドロップしたトークンの配列
 */
function drop_tokens($user_id, $action, $minutes = 0) {
    $pdo = db();
    $drops = [];
    
    // アクションごとのドロップ設定
    switch ($action) {
        case 'focus_success':
            // 成功時：確定でノーマル・レア、確率でユニーク・レジェンド、低確率でエピック
            // 個数は時間依存
            $base_count = max(1, floor($minutes / 10));
            
            // ノーマルトークン（確定）
            $normal_count = $base_count + mt_rand(1, 3);
            $drops['normal_tokens'] = $normal_count;
            
            // レアトークン（確定）
            $rare_count = max(1, floor($base_count / 2)) + mt_rand(0, 2);
            $drops['rare_tokens'] = $rare_count;
            
            // ユニークトークン（50%の確率）
            if (mt_rand(1, 100) <= 50) {
                $drops['unique_tokens'] = max(1, floor($base_count / 3)) + mt_rand(0, 1);
            }
            
            // レジェンドトークン（25%の確率）
            if (mt_rand(1, 100) <= 25) {
                $drops['legend_tokens'] = 1;
            }
            
            // エピックトークン（5%の確率）
            if (mt_rand(1, 100) <= 5) {
                $drops['epic_tokens'] = 1;
            }
            break;
            
        case 'focus_fail':
            // 失敗時：確率でノーマル・レア、低確率でユニーク
            $base_count = max(1, floor($minutes / 15));
            
            // ノーマルトークン（70%の確率）
            if (mt_rand(1, 100) <= 70) {
                $drops['normal_tokens'] = $base_count + mt_rand(0, 2);
            }
            
            // レアトークン（40%の確率）
            if (mt_rand(1, 100) <= 40) {
                $drops['rare_tokens'] = mt_rand(1, 2);
            }
            
            // ユニークトークン（10%の確率）
            if (mt_rand(1, 100) <= 10) {
                $drops['unique_tokens'] = 1;
            }
            break;
            
        case 'post':
        case 'quote':
        case 'reply':
            // 投稿、引用投稿、返信時：確定でノーマル、確率でレア・ユニーク
            // ノーマルトークン（確定）
            $drops['normal_tokens'] = mt_rand(1, 3);
            
            // レアトークン（30%の確率）
            if (mt_rand(1, 100) <= 30) {
                $drops['rare_tokens'] = 1;
            }
            
            // ユニークトークン（5%の確率）
            if (mt_rand(1, 100) <= 5) {
                $drops['unique_tokens'] = 1;
            }
            break;
            
        case 'like':
        case 'repost':
        case 'boost':
            // いいね、リポスト、ブーストされたとき（受け取る側）
            // 確定でノーマル・レア、確率でユニーク、低確率でレジェンド
            $drops['normal_tokens'] = mt_rand(1, 2);
            $drops['rare_tokens'] = 1;
            
            // ユニークトークン（20%の確率）
            if (mt_rand(1, 100) <= 20) {
                $drops['unique_tokens'] = 1;
            }
            
            // レジェンドトークン（3%の確率）
            if (mt_rand(1, 100) <= 3) {
                $drops['legend_tokens'] = 1;
            }
            break;
    }
    
    if (empty($drops)) {
        return [];
    }
    
    // 許可されたカラム名のホワイトリスト
    $allowed_columns = ['normal_tokens', 'rare_tokens', 'unique_tokens', 'legend_tokens', 'epic_tokens', 'hero_tokens', 'mythic_tokens'];
    
    // トークンを付与
    $update_parts = [];
    $params = [];
    foreach ($drops as $token_col => $amount) {
        // ホワイトリスト検証
        if (!in_array($token_col, $allowed_columns)) {
            continue; // 不正なカラム名はスキップ
        }
        $update_parts[] = "{$token_col} = {$token_col} + ?";
        $params[] = $amount;
    }
    
    if (empty($update_parts)) {
        return [];
    }
    
    $params[] = $user_id;
    
    $sql = "UPDATE users SET " . implode(', ', $update_parts) . " WHERE id = ?";
    $st = $pdo->prepare($sql);
    $st->execute($params);
    
    // 履歴を記録（テーブルが存在しない場合はスキップ）
    try {
        foreach ($drops as $token_col => $amount) {
            if (!in_array($token_col, $allowed_columns)) {
                continue;
            }
            $token_type = str_replace('_tokens', '', $token_col);
            $st = $pdo->prepare("INSERT INTO token_history (user_id, token_type, amount, reason) VALUES (?, ?, ?, ?)");
            $st->execute([$user_id, $token_type, $amount, $action]);
        }
    } catch (PDOException $e) {
        // token_historyテーブルがまだ存在しない場合は無視
    }
    
    return $drops;
}

/**
 * ドロップ結果をフォーマットして返す
 * 
 * @param array $drops ドロップ配列
 * @return string フォーマットされた文字列
 */
function format_token_drops($drops) {
    if (empty($drops)) {
        return '';
    }
    
    $icons = [
        'normal_tokens' => '⚪',
        'rare_tokens' => '🟢',
        'unique_tokens' => '🔵',
        'legend_tokens' => '🟡',
        'epic_tokens' => '🟣',
        'hero_tokens' => '🔴',
        'mythic_tokens' => '🌈'
    ];
    
    $parts = [];
    foreach ($drops as $token_col => $amount) {
        $icon = $icons[$token_col] ?? '🎫';
        $parts[] = "{$icon}×{$amount}";
    }
    
    return implode(' ', $parts);
}
