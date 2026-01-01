<?php
// ===============================================
// exp_system.php
// ユーザー経験値・レベルシステム
// ===============================================

require_once __DIR__ . '/config.php';

// 経験値システム定数
define('EXP_MIN', 50);       // 最小獲得経験値
define('EXP_MAX', 100);      // 最大獲得経験値
define('EXP_MAX_LEVEL', 200); // 最大レベル（200に緩和）

/**
 * 経験値を付与してレベルアップをチェック
 * @param int $user_id ユーザーID
 * @param string $reason 経験値獲得理由
 * @param float $exp_bonus_percent 経験値ボーナス率（装備やヒーローから）
 * @return array 獲得経験値とレベルアップ情報
 */
function grant_exp($user_id, $reason, $exp_bonus_percent = 0) {
    $pdo = db();
    
    // ランダムな基本経験値
    $base_exp = mt_rand(EXP_MIN, EXP_MAX);
    
    // ボーナス適用
    $bonus_multiplier = 1 + ($exp_bonus_percent / 100);
    $final_exp = (int)floor($base_exp * $bonus_multiplier);
    
    // レベルアップ閾値（レベルnに必要な累積経験値）
    // レベル1: 0, レベル2: 500, レベル3: 1200, ...
    // 公式: レベルnに必要な累積経験値 = (n-1) * 500 + (n-1)*(n-2)*100
    
    try {
        $pdo->beginTransaction();
        
        // ユーザーの現在の経験値とレベルを取得
        $stmt = $pdo->prepare("SELECT user_level, user_exp FROM users WHERE id = ? FOR UPDATE");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            $pdo->rollBack();
            return ['exp_gained' => 0, 'level_up' => false, 'new_level' => 0];
        }
        
        $current_level = (int)($user['user_level'] ?? 1);
        $current_exp = (int)($user['user_exp'] ?? 0);
        $new_exp = $current_exp + $final_exp;
        
        // レベルアップチェック
        $new_level = $current_level;
        while (true) {
            $next_level_exp = get_exp_required_for_level($new_level + 1);
            if ($new_exp >= $next_level_exp) {
                $new_level++;
            } else {
                break;
            }
            // 最大レベル制限
            if ($new_level >= EXP_MAX_LEVEL) {
                $new_level = EXP_MAX_LEVEL;
                break;
            }
        }
        
        $level_up = $new_level > $current_level;
        
        // 経験値とレベルを更新
        $stmt = $pdo->prepare("UPDATE users SET user_exp = ?, user_level = ? WHERE id = ?");
        $stmt->execute([$new_exp, $new_level, $user_id]);
        
        // 経験値履歴を記録
        $stmt = $pdo->prepare("
            INSERT INTO exp_history (user_id, exp_amount, reason, exp_bonus_percent, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$user_id, $final_exp, $reason, $exp_bonus_percent]);
        
        $pdo->commit();
        
        return [
            'exp_gained' => $final_exp,
            'base_exp' => $base_exp,
            'bonus_percent' => $exp_bonus_percent,
            'level_up' => $level_up,
            'old_level' => $current_level,
            'new_level' => $new_level,
            'current_exp' => $new_exp,
            'next_level_exp' => get_exp_required_for_level($new_level + 1)
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("EXP grant error: " . $e->getMessage());
        return ['exp_gained' => 0, 'level_up' => false, 'new_level' => 0];
    }
}

/**
 * 指定レベルに必要な累積経験値を計算
 * @param int $level レベル
 * @return int 必要な累積経験値
 */
function get_exp_required_for_level($level) {
    if ($level <= 1) return 0;
    // レベル2: 500
    // レベル3: 1200 (500 + 700)
    // レベル4: 2100 (1200 + 900)
    // ...
    // 公式: 基本500 + レベルごとに200増加
    $total = 0;
    for ($i = 2; $i <= $level; $i++) {
        $total += 500 + ($i - 2) * 200;
    }
    return $total;
}

/**
 * ユーザーの経験値ボーナス率を取得（装備とヒーローから）
 * @param int $user_id ユーザーID
 * @return float 経験値ボーナス率（%）
 */
function get_user_exp_bonus($user_id) {
    $pdo = db();
    $total_bonus = 0;
    
    // 装備からの経験値ボーナスを取得
    try {
        $stmt = $pdo->prepare("
            SELECT buffs FROM user_equipment 
            WHERE user_id = ? AND is_equipped = 1
        ");
        $stmt->execute([$user_id]);
        $equipments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($equipments as $eq) {
            $buffs = json_decode($eq['buffs'], true) ?: [];
            if (isset($buffs['exp_bonus'])) {
                $total_bonus += $buffs['exp_bonus'];
            }
        }
    } catch (Exception $e) {
        // テーブルが存在しない場合は無視
    }
    
    // ヒーローからの経験値ボーナスを取得
    try {
        $stmt = $pdo->prepare("
            SELECT h.passive_skill_effect 
            FROM user_heroes uh
            JOIN heroes h ON h.id = uh.hero_id
            WHERE uh.user_id = ? AND uh.star_level > 0 AND uh.is_equipped = 1
        ");
        $stmt->execute([$user_id]);
        $heroes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($heroes as $hero) {
            $effect = json_decode($hero['passive_skill_effect'], true) ?: [];
            if (isset($effect['exp_bonus'])) {
                $total_bonus += $effect['exp_bonus'];
            }
        }
    } catch (Exception $e) {
        // テーブルが存在しない場合は無視
    }
    
    return $total_bonus;
}

/**
 * ユーザーのレベル情報を取得
 * @param int $user_id ユーザーID
 * @return array レベル情報
 */
function get_user_level_info($user_id) {
    $pdo = db();
    
    try {
        $stmt = $pdo->prepare("SELECT user_level, user_exp FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return null;
        }
        
        $level = (int)($user['user_level'] ?? 1);
        $current_exp = (int)($user['user_exp'] ?? 0);
        $current_level_exp = get_exp_required_for_level($level);
        $next_level_exp = get_exp_required_for_level($level + 1);
        
        // 現在レベル内での進捗
        $level_exp = $current_exp - $current_level_exp;
        $level_exp_needed = $next_level_exp - $current_level_exp;
        $progress_percent = $level_exp_needed > 0 ? ($level_exp / $level_exp_needed) * 100 : 0;
        
        return [
            'level' => $level,
            'current_exp' => $current_exp,
            'level_exp' => $level_exp,
            'level_exp_needed' => $level_exp_needed,
            'next_level_exp' => $next_level_exp,
            'progress_percent' => round($progress_percent, 1)
        ];
    } catch (Exception $e) {
        return null;
    }
}
