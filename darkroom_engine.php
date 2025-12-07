<?php
/**
 * Darkroom Game Engine
 * レベリング、クエスト、アイテムクラフト、戦闘システムの中核
 */

class DarkroomEngine {
    private $pdo;
    private $userId;
    
    public function __construct($pdo, $userId) {
        $this->pdo = $pdo;
        $this->userId = $userId;
        $this->initializePlayer();
    }
    
    /**
     * プレイヤーの初期化（初回のみ）
     */
    private function initializePlayer() {
        try {
            $stmt = $this->pdo->prepare("
                INSERT IGNORE INTO darkroom_player_stats (user_id)
                VALUES (?)
            ");
            $stmt->execute([$this->userId]);
        } catch (PDOException $e) {
            error_log("Player initialization error: " . $e->getMessage());
        }
    }
    
    /**
     * プレイヤーステータス取得
     */
    public function getPlayerStats() {
        $stmt = $this->pdo->prepare("SELECT * FROM darkroom_player_stats WHERE user_id = ?");
        $stmt->execute([$this->userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * 経験値を追加してレベルアップ判定
     */
    public function addExperience($exp) {
        if ($exp <= 0) return ['leveled_up' => false, 'new_level' => 0];
        
        $stats = $this->getPlayerStats();
        $currentLevel = $stats['level'];
        $currentExp = $stats['experience'];
        $newExp = $currentExp + $exp;
        
        // レベルアップ判定（必要経験値は level * 100）
        $leveledUp = false;
        $newLevel = $currentLevel;
        $statPointsGained = 0;
        
        while ($newExp >= $this->getRequiredExp($newLevel)) {
            $newExp -= $this->getRequiredExp($newLevel);
            $newLevel++;
            $statPointsGained += 3; // レベルアップごとに3ポイント
            $leveledUp = true;
        }
        
        // ステータス更新
        if ($leveledUp) {
            $stmt = $this->pdo->prepare("
                UPDATE darkroom_player_stats 
                SET level = ?, experience = ?, stat_points = stat_points + ?,
                    max_health = max_health + 10
                WHERE user_id = ?
            ");
            $stmt->execute([$newLevel, $newExp, $statPointsGained, $this->userId]);
        } else {
            $stmt = $this->pdo->prepare("
                UPDATE darkroom_player_stats SET experience = ? WHERE user_id = ?
            ");
            $stmt->execute([$newExp, $this->userId]);
        }
        
        return [
            'leveled_up' => $leveledUp,
            'new_level' => $newLevel,
            'stat_points_gained' => $statPointsGained,
            'experience' => $newExp
        ];
    }
    
    /**
     * 次のレベルに必要な経験値
     */
    public function getRequiredExp($level) {
        return $level * 100;
    }
    
    /**
     * ステータスポイントを割り振る
     */
    public function allocateStatPoint($stat) {
        $validStats = ['max_health', 'attack', 'defense', 'agility'];
        if (!in_array($stat, $validStats)) {
            return ['success' => false, 'error' => '無効なステータス'];
        }
        
        $stats = $this->getPlayerStats();
        if ($stats['stat_points'] <= 0) {
            return ['success' => false, 'error' => 'ステータスポイントが不足'];
        }
        
        $increment = $stat === 'max_health' ? 10 : 1;
        
        $stmt = $this->pdo->prepare("
            UPDATE darkroom_player_stats 
            SET $stat = $stat + ?, stat_points = stat_points - 1,
                health = IF('$stat' = 'max_health', health + ?, health)
            WHERE user_id = ?
        ");
        $stmt->execute([$increment, $increment, $this->userId]);
        
        return ['success' => true, 'stat' => $stat, 'increment' => $increment];
    }
    
    /**
     * インベントリにアイテム追加
     */
    public function addItem($itemKey, $quantity = 1) {
        // アイテムIDを取得
        $stmt = $this->pdo->prepare("SELECT id FROM darkroom_items WHERE item_key = ?");
        $stmt->execute([$itemKey]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$item) {
            return ['success' => false, 'error' => 'アイテムが存在しません'];
        }
        
        $itemId = $item['id'];
        
        // すでに所持しているか確認
        $stmt = $this->pdo->prepare("
            SELECT quantity FROM darkroom_inventory 
            WHERE user_id = ? AND item_id = ?
        ");
        $stmt->execute([$this->userId, $itemId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // 数量を増やす
            $stmt = $this->pdo->prepare("
                UPDATE darkroom_inventory 
                SET quantity = quantity + ? 
                WHERE user_id = ? AND item_id = ?
            ");
            $stmt->execute([$quantity, $this->userId, $itemId]);
        } else {
            // 新規追加
            $stmt = $this->pdo->prepare("
                INSERT INTO darkroom_inventory (user_id, item_id, quantity)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$this->userId, $itemId, $quantity]);
        }
        
        return ['success' => true, 'item_id' => $itemId, 'quantity' => $quantity];
    }
    
    /**
     * インベントリからアイテム削除
     */
    public function removeItem($itemKey, $quantity = 1) {
        $stmt = $this->pdo->prepare("
            SELECT i.id, inv.quantity 
            FROM darkroom_items i
            JOIN darkroom_inventory inv ON i.id = inv.item_id
            WHERE i.item_key = ? AND inv.user_id = ?
        ");
        $stmt->execute([$itemKey, $this->userId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$item || $item['quantity'] < $quantity) {
            return ['success' => false, 'error' => 'アイテムが不足しています'];
        }
        
        $newQuantity = $item['quantity'] - $quantity;
        
        if ($newQuantity <= 0) {
            // 完全に削除
            $stmt = $this->pdo->prepare("
                DELETE FROM darkroom_inventory 
                WHERE user_id = ? AND item_id = ?
            ");
            $stmt->execute([$this->userId, $item['id']]);
        } else {
            // 数量を減らす
            $stmt = $this->pdo->prepare("
                UPDATE darkroom_inventory 
                SET quantity = ? 
                WHERE user_id = ? AND item_id = ?
            ");
            $stmt->execute([$newQuantity, $this->userId, $item['id']]);
        }
        
        return ['success' => true, 'removed' => $quantity];
    }
    
    /**
     * インベントリ取得
     */
    public function getInventory() {
        $stmt = $this->pdo->prepare("
            SELECT i.*, inv.quantity, inv.equipped
            FROM darkroom_inventory inv
            JOIN darkroom_items i ON inv.item_id = i.id
            WHERE inv.user_id = ?
            ORDER BY i.type, i.rarity, i.name
        ");
        $stmt->execute([$this->userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * アイテムクラフト
     */
    public function craftItem($recipeKey) {
        // レシピ情報取得
        $stmt = $this->pdo->prepare("
            SELECT r.*, i.item_key, i.name
            FROM darkroom_recipes r
            JOIN darkroom_items i ON r.result_item_id = i.id
            WHERE r.recipe_key = ?
        ");
        $stmt->execute([$recipeKey]);
        $recipe = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$recipe) {
            return ['success' => false, 'error' => 'レシピが存在しません'];
        }
        
        // レベル確認
        $stats = $this->getPlayerStats();
        if ($stats['level'] < $recipe['required_level']) {
            return ['success' => false, 'error' => 'レベルが足りません'];
        }
        
        // 素材確認
        $materials = json_decode($recipe['materials'], true);
        $inventory = $this->getInventory();
        $inventoryMap = [];
        
        foreach ($inventory as $item) {
            $inventoryMap[$item['item_key']] = $item['quantity'];
        }
        
        foreach ($materials as $material) {
            $itemKey = $material['item_key'];
            $required = $material['quantity'];
            $have = $inventoryMap[$itemKey] ?? 0;
            
            if ($have < $required) {
                return [
                    'success' => false, 
                    'error' => "{$itemKey} が不足しています（必要: {$required}, 所持: {$have}）"
                ];
            }
        }
        
        // 素材を消費
        foreach ($materials as $material) {
            $this->removeItem($material['item_key'], $material['quantity']);
        }
        
        // アイテム作成
        $this->addItem($recipe['item_key'], $recipe['result_quantity']);
        
        // 経験値獲得
        $expResult = $this->addExperience($recipe['experience_reward']);
        
        return [
            'success' => true,
            'item_name' => $recipe['name'],
            'quantity' => $recipe['result_quantity'],
            'experience_gained' => $recipe['experience_reward'],
            'leveled_up' => $expResult['leveled_up'],
            'new_level' => $expResult['new_level']
        ];
    }
    
    /**
     * 利用可能なレシピ一覧
     */
    public function getAvailableRecipes() {
        $stats = $this->getPlayerStats();
        $stmt = $this->pdo->prepare("
            SELECT r.*, i.name as result_name, i.item_key as result_key
            FROM darkroom_recipes r
            JOIN darkroom_items i ON r.result_item_id = i.id
            WHERE r.required_level <= ?
            ORDER BY r.required_level, r.id
        ");
        $stmt->execute([$stats['level']]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * クエスト開始
     */
    public function startQuest($questKey) {
        // クエスト情報取得
        $stmt = $this->pdo->prepare("SELECT * FROM darkroom_quests WHERE quest_key = ?");
        $stmt->execute([$questKey]);
        $quest = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$quest) {
            return ['success' => false, 'error' => 'クエストが存在しません'];
        }
        
        // レベル確認
        $stats = $this->getPlayerStats();
        if ($stats['level'] < $quest['required_level']) {
            return ['success' => false, 'error' => 'レベルが足りません'];
        }
        
        // 既に開始済みか確認
        $stmt = $this->pdo->prepare("
            SELECT status FROM darkroom_quest_progress 
            WHERE user_id = ? AND quest_id = ?
        ");
        $stmt->execute([$this->userId, $quest['id']]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing && $existing['status'] === 'active') {
            return ['success' => false, 'error' => '既にクエストを開始しています'];
        }
        
        if ($existing && $existing['status'] === 'completed' && !$quest['is_repeatable']) {
            return ['success' => false, 'error' => 'このクエストは繰り返せません'];
        }
        
        // 初期進捗を作成
        $objectives = json_decode($quest['objectives'], true);
        $initialProgress = [];
        foreach ($objectives as $idx => $obj) {
            $initialProgress[$idx] = 0;
        }
        
        $stmt = $this->pdo->prepare("
            INSERT INTO darkroom_quest_progress (user_id, quest_id, status, progress, started_at)
            VALUES (?, ?, 'active', ?, NOW())
            ON DUPLICATE KEY UPDATE status = 'active', progress = ?, started_at = NOW()
        ");
        $progressJson = json_encode($initialProgress);
        $stmt->execute([$this->userId, $quest['id'], $progressJson, $progressJson]);
        
        return ['success' => true, 'quest_title' => $quest['title']];
    }
    
    /**
     * クエスト進捗更新
     */
    public function updateQuestProgress($type, $targetKey, $count = 1) {
        // アクティブなクエストを取得
        $stmt = $this->pdo->prepare("
            SELECT qp.*, q.objectives, q.title
            FROM darkroom_quest_progress qp
            JOIN darkroom_quests q ON qp.quest_id = q.id
            WHERE qp.user_id = ? AND qp.status = 'active'
        ");
        $stmt->execute([$this->userId]);
        $activeQuests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $updated = [];
        
        foreach ($activeQuests as $questProgress) {
            $objectives = json_decode($questProgress['objectives'], true);
            $progress = json_decode($questProgress['progress'], true);
            $modified = false;
            
            foreach ($objectives as $idx => $obj) {
                if ($obj['type'] === $type) {
                    $targetField = $type === 'kill' ? 'enemy_key' : ($type === 'craft' ? 'recipe_key' : 'item_key');
                    if ($obj[$targetField] === $targetKey) {
                        $progress[$idx] = min(($progress[$idx] ?? 0) + $count, $obj['count']);
                        $modified = true;
                    }
                }
            }
            
            if ($modified) {
                $stmt = $this->pdo->prepare("
                    UPDATE darkroom_quest_progress 
                    SET progress = ? 
                    WHERE user_id = ? AND quest_id = ?
                ");
                $stmt->execute([json_encode($progress), $this->userId, $questProgress['quest_id']]);
                $updated[] = $questProgress['title'];
            }
        }
        
        return ['updated_quests' => $updated];
    }
    
    /**
     * クエスト完了
     */
    public function completeQuest($questKey) {
        $stmt = $this->pdo->prepare("
            SELECT qp.*, q.objectives, q.rewards, q.title
            FROM darkroom_quest_progress qp
            JOIN darkroom_quests q ON qp.quest_id = q.id
            WHERE qp.user_id = ? AND q.quest_key = ? AND qp.status = 'active'
        ");
        $stmt->execute([$this->userId, $questKey]);
        $questProgress = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$questProgress) {
            return ['success' => false, 'error' => 'クエストが見つかりません'];
        }
        
        // 全ての目標が達成されているか確認
        $objectives = json_decode($questProgress['objectives'], true);
        $progress = json_decode($questProgress['progress'], true);
        
        foreach ($objectives as $idx => $obj) {
            if (($progress[$idx] ?? 0) < $obj['count']) {
                return ['success' => false, 'error' => 'まだ目標を達成していません'];
            }
        }
        
        // 報酬付与
        $rewards = json_decode($questProgress['rewards'], true);
        $rewardText = [];
        
        if (isset($rewards['experience'])) {
            $expResult = $this->addExperience($rewards['experience']);
            $rewardText[] = "経験値 +{$rewards['experience']}";
        }
        
        if (isset($rewards['coins'])) {
            $stmt = $this->pdo->prepare("UPDATE users SET coins = coins + ? WHERE id = ?");
            $stmt->execute([$rewards['coins'], $this->userId]);
            $rewardText[] = "コイン +{$rewards['coins']}";
        }
        
        if (isset($rewards['crystals'])) {
            $stmt = $this->pdo->prepare("UPDATE users SET crystals = crystals + ? WHERE id = ?");
            $stmt->execute([$rewards['crystals'], $this->userId]);
            $rewardText[] = "クリスタル +{$rewards['crystals']}";
        }
        
        if (isset($rewards['items'])) {
            foreach ($rewards['items'] as $item) {
                $this->addItem($item['item_key'], $item['quantity']);
                $rewardText[] = "{$item['item_key']} x{$item['quantity']}";
            }
        }
        
        // クエスト完了状態に更新
        $stmt = $this->pdo->prepare("
            UPDATE darkroom_quest_progress 
            SET status = 'completed', completed_at = NOW() 
            WHERE user_id = ? AND quest_id = ?
        ");
        $stmt->execute([$this->userId, $questProgress['quest_id']]);
        
        return [
            'success' => true,
            'quest_title' => $questProgress['title'],
            'rewards' => $rewardText,
            'leveled_up' => $expResult['leveled_up'] ?? false,
            'new_level' => $expResult['new_level'] ?? 0
        ];
    }
    
    /**
     * クエスト一覧取得
     */
    public function getQuests() {
        $stats = $this->getPlayerStats();
        $stmt = $this->pdo->prepare("
            SELECT q.*, 
                   COALESCE(qp.status, 'available') as player_status,
                   qp.progress
            FROM darkroom_quests q
            LEFT JOIN darkroom_quest_progress qp ON q.id = qp.quest_id AND qp.user_id = ?
            WHERE q.required_level <= ?
            ORDER BY q.type, q.required_level, q.id
        ");
        $stmt->execute([$this->userId, $stats['level']]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * 戦闘実行
     */
    public function battle($enemyKey) {
        // 敵情報取得
        $stmt = $this->pdo->prepare("SELECT * FROM darkroom_enemies WHERE enemy_key = ?");
        $stmt->execute([$enemyKey]);
        $enemy = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$enemy) {
            return ['success' => false, 'error' => '敵が存在しません'];
        }
        
        // プレイヤーステータス取得
        $player = $this->getPlayerStats();
        
        // 装備品のボーナス計算
        $stmt = $this->pdo->prepare("
            SELECT i.stats 
            FROM darkroom_inventory inv
            JOIN darkroom_items i ON inv.item_id = i.id
            WHERE inv.user_id = ? AND inv.equipped = TRUE
        ");
        $stmt->execute([$this->userId]);
        $equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $equipmentBonus = ['attack' => 0, 'defense' => 0, 'max_health' => 0];
        foreach ($equipment as $item) {
            if ($item['stats']) {
                $stats = json_decode($item['stats'], true);
                foreach ($equipmentBonus as $key => $val) {
                    $equipmentBonus[$key] += $stats[$key] ?? 0;
                }
            }
        }
        
        $playerAttack = $player['attack'] + $equipmentBonus['attack'];
        $playerDefense = $player['defense'] + $equipmentBonus['defense'];
        $playerMaxHealth = $player['max_health'] + $equipmentBonus['max_health'];
        $playerHealth = min($player['health'], $playerMaxHealth);
        
        $enemyHealth = $enemy['health'];
        $enemyAttack = $enemy['attack'];
        $enemyDefense = $enemy['defense'];
        
        // 戦闘ログ
        $battleLog = [];
        $turns = 0;
        $damageDealt = 0;
        $damageTaken = 0;
        
        // ターン制バトル（最大20ターン）
        while ($turns < 20 && $playerHealth > 0 && $enemyHealth > 0) {
            $turns++;
            
            // プレイヤーの攻撃
            $damage = max(1, $playerAttack - $enemyDefense + rand(-2, 2));
            $enemyHealth -= $damage;
            $damageDealt += $damage;
            $battleLog[] = "あなたは {$enemy['name']} に {$damage} のダメージを与えた！";
            
            if ($enemyHealth <= 0) {
                break;
            }
            
            // 敵の攻撃
            $damage = max(1, $enemyAttack - $playerDefense + rand(-2, 2));
            $playerHealth -= $damage;
            $damageTaken += $damage;
            $battleLog[] = "{$enemy['name']} の攻撃！ {$damage} のダメージを受けた！";
        }
        
        // 結果判定
        $result = $enemyHealth <= 0 ? 'victory' : ($playerHealth <= 0 ? 'defeat' : 'fled');
        
        // ドロップアイテム
        $loot = [];
        if ($result === 'victory' && $enemy['loot_table']) {
            $lootTable = json_decode($enemy['loot_table'], true);
            foreach ($lootTable as $drop) {
                if (mt_rand() / mt_getrandmax() <= $drop['drop_rate']) {
                    $quantity = rand($drop['quantity_min'], $drop['quantity_max']);
                    $this->addItem($drop['item_key'], $quantity);
                    $loot[] = ['item_key' => $drop['item_key'], 'quantity' => $quantity];
                }
            }
        }
        
        // 経験値獲得
        $expGained = $result === 'victory' ? $enemy['experience_reward'] : 0;
        $expResult = [];
        if ($expGained > 0) {
            $expResult = $this->addExperience($expGained);
        }
        
        // プレイヤーのHP更新
        $newHealth = max(1, $playerHealth);
        $stmt = $this->pdo->prepare("UPDATE darkroom_player_stats SET health = ? WHERE user_id = ?");
        $stmt->execute([$newHealth, $this->userId]);
        
        // 戦闘ログをDBに保存
        $stmt = $this->pdo->prepare("
            INSERT INTO darkroom_battle_logs 
            (user_id, enemy_id, result, turns, damage_dealt, damage_taken, experience_gained, loot, battle_log)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $this->userId,
            $enemy['id'],
            $result,
            $turns,
            $damageDealt,
            $damageTaken,
            $expGained,
            json_encode($loot),
            implode("\n", $battleLog)
        ]);
        
        // クエスト進捗更新
        if ($result === 'victory') {
            $this->updateQuestProgress('kill', $enemyKey, 1);
        }
        
        return [
            'success' => true,
            'result' => $result,
            'turns' => $turns,
            'battle_log' => $battleLog,
            'loot' => $loot,
            'experience_gained' => $expGained,
            'player_health' => $newHealth,
            'leveled_up' => $expResult['leveled_up'] ?? false,
            'new_level' => $expResult['new_level'] ?? 0
        ];
    }
    
    /**
     * 利用可能な敵一覧
     */
    public function getAvailableEnemies() {
        $stats = $this->getPlayerStats();
        $maxLevel = $stats['level'] + 3; // プレイヤーレベル+3まで
        
        $stmt = $this->pdo->prepare("
            SELECT * FROM darkroom_enemies 
            WHERE level <= ?
            ORDER BY level, id
        ");
        $stmt->execute([$maxLevel]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
