<?php
// ===============================================
// battle_mail_helpers.php
// 戦争・占領戦のメール送信ヘルパー関数
// ===============================================

/**
 * 戦争の戦闘メールを作成
 * 
 * @param PDO $pdo データベース接続
 * @param int $attackerUserId 攻撃者のユーザーID
 * @param int $defenderUserId 防御者のユーザーID
 * @param array $attackerUnit 攻撃者のユニット情報
 * @param array $defenderUnit 防御者のユニット情報
 * @param array $battleResult バトル結果
 * @param array $attackerLosses 攻撃者の損失
 * @param array $defenderLosses 防御者の損失
 * @param int $lootCoins 略奪したコイン
 * @param array $lootResources 略奪した資源
 * @param int $warLogId 戦争ログID
 * @return array [attacker_mail_id, defender_mail_id]
 */
function createWarBattleMails($pdo, $attackerUserId, $defenderUserId, $attackerUnit, $defenderUnit, $battleResult, $attackerLosses, $defenderLosses, $lootCoins, $lootResources, $warLogId) {
    // ユーザー情報を取得
    $stmt = $pdo->prepare("SELECT u.handle, uc.civilization_name FROM users u LEFT JOIN user_civilizations uc ON u.id = uc.user_id WHERE u.id = ?");
    $stmt->execute([$attackerUserId]);
    $attackerInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt->execute([$defenderUserId]);
    $defenderInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $attackerHandle = '@' . ($attackerInfo['handle'] ?? 'Unknown');
    $attackerCiv = $attackerInfo['civilization_name'] ?? '不明';
    $defenderHandle = '@' . ($defenderInfo['handle'] ?? 'Unknown');
    $defenderCiv = $defenderInfo['civilization_name'] ?? '不明';
    
    $attackerWins = $battleResult['attacker_wins'];
    $resultText = $attackerWins ? '勝利' : '敗北';
    $defenderResultText = $attackerWins ? '敗北' : '勝利';
    
    // 部隊情報を整形（ステルス部隊も可視化）
    $attackerTroopsText = formatTroopListForMail($pdo, $attackerUnit['troops']);
    $defenderTroopsText = formatTroopListForMail($pdo, $defenderUnit['troops']);
    
    // 損失情報を整形
    $attackerLossesText = formatLossesForMail($pdo, $attackerLosses);
    $defenderLossesText = formatLossesForMail($pdo, $defenderLosses);
    
    // 略奪情報
    $lootText = '';
    if ($attackerWins) {
        $lootText = "\n■ 略奪:\n";
        if ($lootCoins > 0) {
            $lootText .= "・🪙 コイン: {$lootCoins}\n";
        }
        foreach ($lootResources as $key => $amount) {
            $resourceName = getResourceName($pdo, $key);
            $lootText .= "・{$resourceName}: {$amount}\n";
        }
    }
    
    // 攻撃者向けメール
    $attackerMailBody = "【戦争報告 - {$resultText}】\n\n";
    $attackerMailBody .= "対戦相手: {$defenderCiv} ({$defenderHandle})\n";
    $attackerMailBody .= "結果: {$resultText}\n";
    $attackerMailBody .= "総ターン数: {$battleResult['total_turns']}\n\n";
    $attackerMailBody .= "■ あなたの攻撃部隊:\n{$attackerTroopsText}\n";
    $attackerMailBody .= "■ 敵の防衛部隊:\n{$defenderTroopsText}\n";
    $attackerMailBody .= "■ 最終HP:\n";
    $attackerMailBody .= "・あなた: {$battleResult['attacker_final_hp']}/{$battleResult['attacker_max_hp']}\n";
    $attackerMailBody .= "・敵: {$battleResult['defender_final_hp']}/{$battleResult['defender_max_hp']}\n";
    if ($attackerLossesText) {
        $attackerMailBody .= "\n■ あなたの損失:\n{$attackerLossesText}";
    }
    $attackerMailBody .= $lootText;
    
    $stmt = $pdo->prepare("
        INSERT INTO civilization_mails (mail_type, sender_user_id, recipient_user_id, subject, body, extra_data)
        VALUES ('war', NULL, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $attackerUserId,
        "戦争報告: vs {$defenderCiv} ({$resultText})",
        $attackerMailBody,
        json_encode([
            'war_log_id' => $warLogId,
            'opponent_user_id' => $defenderUserId,
            'opponent_handle' => $defenderHandle,
            'opponent_civilization' => $defenderCiv,
            'result' => $attackerWins ? 'victory' : 'defeat',
            'attacker_troops' => $attackerUnit['troops'],
            'defender_troops' => $defenderUnit['troops'],
            'battle_result' => $battleResult
        ])
    ]);
    $attackerMailId = $pdo->lastInsertId();
    
    // 防御者向けメール
    $defenderMailBody = "【戦争報告 - {$defenderResultText}】\n\n";
    $defenderMailBody .= "攻撃者: {$attackerCiv} ({$attackerHandle})\n";
    $defenderMailBody .= "結果: {$defenderResultText}\n";
    $defenderMailBody .= "総ターン数: {$battleResult['total_turns']}\n\n";
    $defenderMailBody .= "■ 敵の攻撃部隊:\n{$attackerTroopsText}\n";
    $defenderMailBody .= "■ あなたの防衛部隊:\n{$defenderTroopsText}\n";
    $defenderMailBody .= "■ 最終HP:\n";
    $defenderMailBody .= "・敵: {$battleResult['attacker_final_hp']}/{$battleResult['attacker_max_hp']}\n";
    $defenderMailBody .= "・あなた: {$battleResult['defender_final_hp']}/{$battleResult['defender_max_hp']}\n";
    if ($defenderLossesText) {
        $defenderMailBody .= "\n■ あなたの損失:\n{$defenderLossesText}";
    }
    if ($attackerWins) {
        $defenderMailBody .= "\n※ 資源が略奪されました。";
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO civilization_mails (mail_type, sender_user_id, recipient_user_id, subject, body, extra_data)
        VALUES ('war', NULL, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $defenderUserId,
        "戦争報告: vs {$attackerCiv} ({$defenderResultText})",
        $defenderMailBody,
        json_encode([
            'war_log_id' => $warLogId,
            'opponent_user_id' => $attackerUserId,
            'opponent_handle' => $attackerHandle,
            'opponent_civilization' => $attackerCiv,
            'result' => $attackerWins ? 'defeat' : 'victory',
            'attacker_troops' => $attackerUnit['troops'],
            'defender_troops' => $defenderUnit['troops'],
            'battle_result' => $battleResult
        ])
    ]);
    $defenderMailId = $pdo->lastInsertId();
    
    return [$attackerMailId, $defenderMailId];
}

/**
 * 占領戦の戦闘メールを作成
 * 
 * @param PDO $pdo データベース接続
 * @param int $attackerUserId 攻撃者のユーザーID
 * @param int|null $defenderUserId 防御者のユーザーID（NPCの場合はnull）
 * @param array $castle 城の情報
 * @param array $attackerUnit 攻撃者のユニット情報
 * @param array $defenderUnit 防御者のユニット情報
 * @param array $battleResult バトル結果
 * @param array $attackerLosses 攻撃者の損失
 * @param array $defenderLosses 防御者の損失
 * @param bool $castleCaptured 城を占領したかどうか
 * @param int $durabilityDamage 耐久度ダメージ
 * @param int $battleLogId 戦闘ログID
 * @return array [attacker_mail_id, defender_mail_id]
 */
function createConquestBattleMails($pdo, $attackerUserId, $defenderUserId, $castle, $attackerUnit, $defenderUnit, $battleResult, $attackerLosses, $defenderLosses, $castleCaptured, $durabilityDamage, $battleLogId) {
    // 城情報
    $castleName = $castle['name'] ?? '城';
    $castleCoords = "({$castle['x']}, {$castle['y']})";
    
    // ユーザー情報を取得
    $stmt = $pdo->prepare("SELECT u.handle, uc.civilization_name FROM users u LEFT JOIN user_civilizations uc ON u.id = uc.user_id WHERE u.id = ?");
    $stmt->execute([$attackerUserId]);
    $attackerInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $attackerHandle = '@' . ($attackerInfo['handle'] ?? 'Unknown');
    $attackerCiv = $attackerInfo['civilization_name'] ?? '不明';
    
    $defenderHandle = null;
    $defenderCiv = null;
    $isNpcDefender = ($defenderUserId === null);
    
    if (!$isNpcDefender) {
        $stmt->execute([$defenderUserId]);
        $defenderInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        $defenderHandle = '@' . ($defenderInfo['handle'] ?? 'Unknown');
        $defenderCiv = $defenderInfo['civilization_name'] ?? '不明';
    } else {
        $defenderHandle = 'NPC';
        $defenderCiv = 'NPC守備隊';
    }
    
    $attackerWins = $battleResult['attacker_wins'];
    $resultText = $attackerWins ? '勝利' : '敗北';
    $defenderResultText = $attackerWins ? '敗北' : '勝利';
    
    // 部隊情報を整形（ステルス部隊も可視化）
    $attackerTroopsText = formatTroopListForMail($pdo, $attackerUnit['troops']);
    $defenderTroopsText = formatTroopListForMail($pdo, $defenderUnit['troops']);
    
    // 損失情報を整形
    $attackerLossesText = formatLossesForMail($pdo, $attackerLosses);
    $defenderLossesText = formatLossesForMail($pdo, $defenderLosses);
    
    // 占領結果テキスト
    $captureText = '';
    if ($castleCaptured) {
        $captureText = "\n🏰 城を占領しました！";
    } else if ($durabilityDamage > 0) {
        $captureText = "\n🔨 耐久度に{$durabilityDamage}ダメージを与えました。";
    }
    
    // 攻撃者向けメール
    $attackerMailBody = "【占領戦報告 - {$resultText}】\n\n";
    $attackerMailBody .= "城名: {$castleName}\n";
    $attackerMailBody .= "座標: {$castleCoords}\n";
    $attackerMailBody .= "防衛者: {$defenderCiv}" . ($defenderHandle !== 'NPC' ? " ({$defenderHandle})" : "") . "\n";
    $attackerMailBody .= "結果: {$resultText}{$captureText}\n";
    $attackerMailBody .= "総ターン数: {$battleResult['total_turns']}\n\n";
    $attackerMailBody .= "■ あなたの攻撃部隊:\n{$attackerTroopsText}\n";
    $attackerMailBody .= "■ 敵の防衛部隊:\n{$defenderTroopsText}\n";
    $attackerMailBody .= "■ 最終HP:\n";
    $attackerMailBody .= "・あなた: {$battleResult['attacker_final_hp']}/{$battleResult['attacker_max_hp']}\n";
    $attackerMailBody .= "・敵: {$battleResult['defender_final_hp']}/{$battleResult['defender_max_hp']}\n";
    if ($attackerLossesText) {
        $attackerMailBody .= "\n■ あなたの損失:\n{$attackerLossesText}";
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO civilization_mails (mail_type, sender_user_id, recipient_user_id, subject, body, extra_data)
        VALUES ('conquest', NULL, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $attackerUserId,
        "占領戦報告: {$castleName} {$castleCoords} ({$resultText})",
        $attackerMailBody,
        json_encode([
            'battle_log_id' => $battleLogId,
            'castle_id' => $castle['id'],
            'castle_name' => $castleName,
            'castle_coords' => ['x' => $castle['x'], 'y' => $castle['y']],
            'defender_user_id' => $defenderUserId,
            'defender_handle' => $defenderHandle,
            'defender_civilization' => $defenderCiv,
            'result' => $attackerWins ? 'victory' : 'defeat',
            'castle_captured' => $castleCaptured,
            'durability_damage' => $durabilityDamage,
            'attacker_troops' => $attackerUnit['troops'],
            'defender_troops' => $defenderUnit['troops'],
            'battle_result' => $battleResult
        ])
    ]);
    $attackerMailId = $pdo->lastInsertId();
    
    $defenderMailId = null;
    
    // 防御者向けメール（プレイヤーの場合のみ）
    if (!$isNpcDefender) {
        $defenderMailBody = "【占領戦報告 - {$defenderResultText}】\n\n";
        $defenderMailBody .= "城名: {$castleName}\n";
        $defenderMailBody .= "座標: {$castleCoords}\n";
        $defenderMailBody .= "攻撃者: {$attackerCiv} ({$attackerHandle})\n";
        $defenderMailBody .= "結果: {$defenderResultText}";
        if ($castleCaptured) {
            $defenderMailBody .= "\n⚠️ 城を失いました！";
        }
        $defenderMailBody .= "\n総ターン数: {$battleResult['total_turns']}\n\n";
        $defenderMailBody .= "■ 敵の攻撃部隊:\n{$attackerTroopsText}\n";
        $defenderMailBody .= "■ あなたの防衛部隊:\n{$defenderTroopsText}\n";
        $defenderMailBody .= "■ 最終HP:\n";
        $defenderMailBody .= "・敵: {$battleResult['attacker_final_hp']}/{$battleResult['attacker_max_hp']}\n";
        $defenderMailBody .= "・あなた: {$battleResult['defender_final_hp']}/{$battleResult['defender_max_hp']}\n";
        if ($defenderLossesText) {
            $defenderMailBody .= "\n■ あなたの損失:\n{$defenderLossesText}";
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO civilization_mails (mail_type, sender_user_id, recipient_user_id, subject, body, extra_data)
            VALUES ('conquest', NULL, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $defenderUserId,
            "占領戦報告: {$castleName} {$castleCoords} ({$defenderResultText})",
            $defenderMailBody,
            json_encode([
                'battle_log_id' => $battleLogId,
                'castle_id' => $castle['id'],
                'castle_name' => $castleName,
                'castle_coords' => ['x' => $castle['x'], 'y' => $castle['y']],
                'attacker_user_id' => $attackerUserId,
                'attacker_handle' => $attackerHandle,
                'attacker_civilization' => $attackerCiv,
                'result' => $attackerWins ? 'defeat' : 'victory',
                'castle_captured' => $castleCaptured,
                'attacker_troops' => $attackerUnit['troops'],
                'defender_troops' => $defenderUnit['troops'],
                'battle_result' => $battleResult
            ])
        ]);
        $defenderMailId = $pdo->lastInsertId();
    }
    
    return [$attackerMailId, $defenderMailId];
}

/**
 * 部隊リストをメール用テキストに整形
 */
function formatTroopListForMail($pdo, $troops) {
    $text = '';
    foreach ($troops as $troop) {
        $name = $troop['name'] ?? '';
        $icon = $troop['icon'] ?? '';
        $count = $troop['count'] ?? 0;
        
        // 名前がない場合はDBから取得
        if (empty($name) && isset($troop['troop_type_id'])) {
            $stmt = $pdo->prepare("SELECT name, icon FROM civilization_troop_types WHERE id = ?");
            $stmt->execute([$troop['troop_type_id']]);
            $troopType = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($troopType) {
                $name = $troopType['name'];
                $icon = $troopType['icon'];
            }
        }
        
        if ($name && $count > 0) {
            $text .= "・{$icon} {$name}: {$count}体\n";
        }
    }
    return $text ?: "（なし）\n";
}

/**
 * 損失情報をメール用テキストに整形
 */
function formatLossesForMail($pdo, $losses) {
    if (empty($losses)) {
        return '';
    }
    
    $text = '';
    foreach ($losses as $troopTypeId => $count) {
        $stmt = $pdo->prepare("SELECT name, icon FROM civilization_troop_types WHERE id = ?");
        $stmt->execute([$troopTypeId]);
        $troopType = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($troopType) {
            $text .= "・{$troopType['icon']} {$troopType['name']}: -{$count}体\n";
        }
    }
    return $text;
}

/**
 * 資源キーから資源名を取得
 */
function getResourceName($pdo, $resourceKey) {
    $stmt = $pdo->prepare("SELECT name, icon FROM civilization_resource_types WHERE resource_key = ?");
    $stmt->execute([$resourceKey]);
    $resource = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($resource) {
        return "{$resource['icon']} {$resource['name']}";
    }
    return $resourceKey;
}
