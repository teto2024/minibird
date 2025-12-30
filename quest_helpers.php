<?php
// ===============================================
// quest_helpers.php
// 文明クエストの進捗更新ヘルパー関数
// ===============================================

/**
 * 文明クエストの進捗を更新するヘルパー関数
 * モンスター討伐、ワールドボスダメージ、占領戦などでクエスト進捗を増加させる
 * 
 * @param PDO $pdo データベース接続
 * @param int $userId ユーザーID
 * @param string $questType クエストタイプ (defeat_monster, damage_boss, conquest, etc.)
 * @param string|null $targetKey ターゲットキー（モンスターキーなど）
 * @param int $incrementCount 進捗増加量
 */
function updateCivilizationQuestProgressHelper($pdo, $userId, $questType, $targetKey, $incrementCount = 1) {
    try {
        // ユーザーの現在の時代を取得
        $stmt = $pdo->prepare("SELECT current_era_id FROM user_civilizations WHERE user_id = ?");
        $stmt->execute([$userId]);
        $currentEraId = (int)$stmt->fetchColumn();
        
        if ($currentEraId <= 0) return;
        
        // 該当するアクティブなクエストを取得
        $stmt = $pdo->prepare("
            SELECT cq.* FROM civilization_quests cq
            WHERE cq.quest_type = ? 
              AND (cq.target_key = ? OR cq.target_key IS NULL)
              AND cq.era_id <= ?
              AND cq.is_active = TRUE
        ");
        $stmt->execute([$questType, $targetKey, $currentEraId]);
        $quests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($quests as $quest) {
            // ユーザーのクエスト進捗を取得または作成
            $stmt = $pdo->prepare("
                SELECT * FROM user_civilization_quest_progress 
                WHERE user_id = ? AND quest_id = ?
            ");
            $stmt->execute([$userId, $quest['id']]);
            $progress = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // 繰り返し可能でリセット期間を過ぎている場合はリセット
            if ($progress && $quest['is_repeatable'] && $progress['is_claimed']) {
                $cooldownExpired = false;
                if ($quest['cooldown_hours'] && $progress['claimed_at']) {
                    $claimedTime = strtotime($progress['claimed_at']);
                    $cooldownEnd = $claimedTime + ($quest['cooldown_hours'] * 3600);
                    if (time() >= $cooldownEnd) {
                        $cooldownExpired = true;
                    }
                }
                
                if ($cooldownExpired) {
                    $stmt = $pdo->prepare("
                        UPDATE user_civilization_quest_progress 
                        SET current_progress = 0, is_completed = FALSE, is_claimed = FALSE, 
                            completed_at = NULL, claimed_at = NULL, last_reset_at = NOW()
                        WHERE user_id = ? AND quest_id = ?
                    ");
                    $stmt->execute([$userId, $quest['id']]);
                    $progress['current_progress'] = 0;
                    $progress['is_completed'] = false;
                    $progress['is_claimed'] = false;
                }
            }
            
            // 既に報酬を受け取っている場合はスキップ
            if ($progress && $progress['is_claimed'] && !$quest['is_repeatable']) {
                continue;
            }
            
            if (!$progress) {
                $initialProgress = min($incrementCount, $quest['target_count']);
                $isCompleted = $initialProgress >= $quest['target_count'];
                $stmt = $pdo->prepare("
                    INSERT INTO user_civilization_quest_progress (user_id, quest_id, current_progress, is_completed, completed_at)
                    VALUES (?, ?, ?, ?, IF(?, NOW(), NULL))
                ");
                $stmt->execute([$userId, $quest['id'], $initialProgress, $isCompleted, $isCompleted]);
            } else if (!$progress['is_claimed']) {
                $newProgress = min($progress['current_progress'] + $incrementCount, $quest['target_count']);
                $isCompleted = $newProgress >= $quest['target_count'];
                
                $stmt = $pdo->prepare("
                    UPDATE user_civilization_quest_progress 
                    SET current_progress = ?, is_completed = ?, completed_at = IF(? AND completed_at IS NULL, NOW(), completed_at)
                    WHERE user_id = ? AND quest_id = ?
                ");
                $stmt->execute([$newProgress, $isCompleted, $isCompleted, $userId, $quest['id']]);
            }
        }
    } catch (Exception $e) {
        error_log("Quest progress update error for user {$userId}, type {$questType}, target {$targetKey}: " . $e->getMessage());
    }
}
