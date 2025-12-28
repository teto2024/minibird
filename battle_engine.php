<?php
// ===============================================
// battle_engine.php
// ターン制バトルシステムエンジン
// ===============================================

// バトルシステム定数
define('BATTLE_MAX_TURNS', 50);                     // 最大ターン数
define('BATTLE_DAMAGE_VARIANCE', 0.2);              // ダメージの乱数幅（±20%）
define('BATTLE_CRITICAL_MULTIPLIER', 1.5);          // クリティカルダメージ倍率
define('BATTLE_BASE_CRITICAL_CHANCE', 5);           // 基本クリティカル率（%）
define('BATTLE_ARMOR_REDUCTION_DIVISOR', 200);      // アーマーからダメージ軽減率への変換（200アーマー=50%軽減）
define('BATTLE_MAX_ARMOR_REDUCTION', 0.75);         // 最大アーマー軽減率（75%）
define('BATTLE_MIN_DAMAGE', 1);                     // 最小ダメージ
define('BATTLE_EQUIPMENT_ATTACK_MULTIPLIER', 0.5);  // 装備攻撃力の適用倍率
define('BATTLE_EQUIPMENT_ARMOR_MULTIPLIER', 1.0);   // 装備アーマーの適用倍率
define('BATTLE_EQUIPMENT_HEALTH_MULTIPLIER', 2.0);  // 装備体力の適用倍率

/**
 * 特殊スキル情報を取得
 * @param PDO $pdo
 * @return array skill_id => skill_data の連想配列
 */
function getSpecialSkills($pdo) {
    static $skills = null;
    if ($skills === null) {
        $stmt = $pdo->query("SELECT * FROM battle_special_skills");
        $skills = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $skills[$row['id']] = $row;
        }
    }
    return $skills;
}

/**
 * 兵種の詳細情報を取得（特殊スキル含む）
 * @param PDO $pdo
 * @param int $troopTypeId
 * @return array|null
 */
function getTroopTypeWithSkill($pdo, $troopTypeId) {
    $stmt = $pdo->prepare("
        SELECT tt.*, ss.skill_key, ss.name as skill_name, ss.icon as skill_icon, 
               ss.effect_type, ss.effect_target, ss.effect_value, ss.duration_turns, ss.activation_chance
        FROM civilization_troop_types tt
        LEFT JOIN battle_special_skills ss ON tt.special_skill_id = ss.id
        WHERE tt.id = ?
    ");
    $stmt->execute([$troopTypeId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * バトルユニットを準備
 * @param array $troops [{troop_type_id, count, ...}, ...]
 * @param array $equipmentBuffs {attack, armor, health}
 * @param PDO $pdo
 * @return array バトルユニット情報
 */
function prepareBattleUnit($troops, $equipmentBuffs, $pdo) {
    $totalAttack = 0;
    $totalArmor = 0;
    $totalHealth = 0;
    $troopDetails = [];
    $skills = [];
    
    foreach ($troops as $troop) {
        $troopType = getTroopTypeWithSkill($pdo, $troop['troop_type_id']);
        if (!$troopType) continue;
        
        $count = (int)$troop['count'];
        if ($count <= 0) continue;
        
        $attack = (int)$troopType['attack_power'] * $count;
        $defense = (int)$troopType['defense_power'] * $count;
        $health = (int)($troopType['health_points'] ?? 100) * $count;
        
        $totalAttack += $attack;
        $totalArmor += $defense;
        $totalHealth += $health;
        
        // スキル情報を収集
        if (!empty($troopType['skill_key'])) {
            $skills[] = [
                'skill_key' => $troopType['skill_key'],
                'skill_name' => $troopType['skill_name'],
                'skill_icon' => $troopType['skill_icon'],
                'effect_type' => $troopType['effect_type'],
                'effect_target' => $troopType['effect_target'],
                'effect_value' => (float)$troopType['effect_value'],
                'duration_turns' => (int)$troopType['duration_turns'],
                'activation_chance' => (float)$troopType['activation_chance'],
                'troop_type_id' => $troop['troop_type_id'],
                'troop_name' => $troopType['name'],
                'troop_icon' => $troopType['icon'],
                'count' => $count
            ];
        }
        
        $troopDetails[] = [
            'troop_type_id' => $troop['troop_type_id'],
            'name' => $troopType['name'],
            'icon' => $troopType['icon'],
            'count' => $count,
            'attack' => $attack,
            'defense' => $defense,
            'health' => $health,
            'category' => $troopType['troop_category'] ?? 'infantry'
        ];
    }
    
    // 装備バフを追加
    $equipAttackBonus = (int)floor(($equipmentBuffs['attack'] ?? 0) * BATTLE_EQUIPMENT_ATTACK_MULTIPLIER);
    $equipArmorBonus = (int)floor(($equipmentBuffs['armor'] ?? 0) * BATTLE_EQUIPMENT_ARMOR_MULTIPLIER);
    $equipHealthBonus = (int)floor(($equipmentBuffs['health'] ?? 0) * BATTLE_EQUIPMENT_HEALTH_MULTIPLIER);
    
    return [
        'attack' => $totalAttack + $equipAttackBonus,
        'armor' => $totalArmor + $equipArmorBonus,
        'max_health' => $totalHealth + $equipHealthBonus,
        'current_health' => $totalHealth + $equipHealthBonus,
        'troops' => $troopDetails,
        'skills' => $skills,
        'equipment_buffs' => $equipmentBuffs,
        'active_effects' => [], // 現在適用中の状態異常
        'is_frozen' => false,
        'is_stunned' => false,
        'extra_attacks' => 0,   // 加速による追加攻撃回数
    ];
}

/**
 * ダメージを計算（乱数幅あり）
 * @param int $baseAttack 基本攻撃力
 * @param int $targetArmor 対象のアーマー
 * @param array $attackerEffects 攻撃者の状態効果
 * @param array $defenderEffects 防御者の状態効果
 * @return array [damage, isCritical, messages]
 */
function calculateDamage($baseAttack, $targetArmor, $attackerEffects = [], $defenderEffects = []) {
    $messages = [];
    
    // 攻撃力の調整（状態異常による）
    $attackMultiplier = 1.0;
    foreach ($attackerEffects as $effect) {
        if ($effect['skill_key'] === 'attack_up') {
            $attackMultiplier += $effect['effect_value'] / 100;
            $messages[] = "⚔️ 攻撃力上昇中 (+{$effect['effect_value']}%)";
        }
    }
    foreach ($defenderEffects as $effect) {
        if ($effect['skill_key'] === 'attack_down') {
            $attackMultiplier -= $effect['effect_value'] / 100;
            $messages[] = "⬇️ 攻撃低下中 (-{$effect['effect_value']}%)";
        }
    }
    $attackMultiplier = max(0.1, $attackMultiplier);
    
    // アーマーの調整（状態異常による）
    $armorMultiplier = 1.0;
    foreach ($attackerEffects as $effect) {
        if ($effect['skill_key'] === 'armor_harden') {
            // 自分のアーマー硬化は防御時に効果あり
        }
        if ($effect['skill_key'] === 'defense_break') {
            $armorMultiplier = 0; // アーマー無視
            $messages[] = "🔨 防御破壊！アーマー無視";
        }
    }
    foreach ($defenderEffects as $effect) {
        if ($effect['skill_key'] === 'vulnerable') {
            $armorMultiplier -= $effect['effect_value'] / 100;
            $messages[] = "💔 無防備状態 (-{$effect['effect_value']}%アーマー)";
        }
        if ($effect['skill_key'] === 'armor_harden') {
            $armorMultiplier += $effect['effect_value'] / 100;
            $messages[] = "🛡️ アーマー硬化中 (+{$effect['effect_value']}%)";
        }
    }
    $armorMultiplier = max(0, $armorMultiplier);
    
    // 調整後の攻撃力
    $adjustedAttack = $baseAttack * $attackMultiplier;
    
    // 乱数幅を適用（±BATTLE_DAMAGE_VARIANCE）
    $variance = 1 + (mt_rand(-100, 100) / 100) * BATTLE_DAMAGE_VARIANCE;
    $attackWithVariance = $adjustedAttack * $variance;
    
    // クリティカル判定
    $critChance = BATTLE_BASE_CRITICAL_CHANCE;
    foreach ($attackerEffects as $effect) {
        if ($effect['skill_key'] === 'critical') {
            $critChance += $effect['effect_value'];
        }
    }
    $isCritical = mt_rand(1, 100) <= $critChance;
    if ($isCritical) {
        $attackWithVariance *= BATTLE_CRITICAL_MULTIPLIER;
        $messages[] = "💥 クリティカルヒット！";
    }
    
    // アーマーによるダメージ軽減
    $effectiveArmor = $targetArmor * $armorMultiplier;
    $armorReduction = min(BATTLE_MAX_ARMOR_REDUCTION, $effectiveArmor / BATTLE_ARMOR_REDUCTION_DIVISOR);
    $finalDamage = (int)max(BATTLE_MIN_DAMAGE, floor($attackWithVariance * (1 - $armorReduction)));
    
    return [
        'damage' => $finalDamage,
        'is_critical' => $isCritical,
        'messages' => $messages,
        'attack_multiplier' => $attackMultiplier,
        'armor_reduction' => $armorReduction
    ];
}

/**
 * スキル発動判定と効果適用
 * @param array $unit バトルユニット
 * @param array $target ターゲットユニット
 * @param bool $isAttacker 攻撃側かどうか
 * @return array [skill_activated, effect, messages]
 */
function tryActivateSkill($unit, $target, $isAttacker) {
    $messages = [];
    $newEffects = [];
    $extraAttacks = 0;
    
    // 各兵種のスキル発動判定
    foreach ($unit['skills'] as $skill) {
        if (mt_rand(1, 100) <= $skill['activation_chance']) {
            $effect = [
                'skill_key' => $skill['skill_key'],
                'skill_name' => $skill['skill_name'],
                'skill_icon' => $skill['skill_icon'],
                'effect_type' => $skill['effect_type'],
                'effect_target' => $skill['effect_target'],
                'effect_value' => $skill['effect_value'],
                'remaining_turns' => $skill['duration_turns'],
                'troop_name' => $skill['troop_name'],
                'troop_icon' => $skill['troop_icon']
            ];
            
            $messages[] = "{$skill['troop_icon']} {$skill['troop_name']}が「{$skill['skill_icon']} {$skill['skill_name']}」を発動！";
            
            // 加速スキルの特別処理
            if ($skill['skill_key'] === 'acceleration') {
                $extraAttacks = (int)$skill['effect_value'] - 1;
                $messages[] = "⚡ 加速！{$skill['effect_value']}回連続攻撃！";
            } else {
                $newEffects[] = $effect;
            }
            
            // 1つのスキルのみ発動（複数発動を防ぐ）
            break;
        }
    }
    
    return [
        'effects' => $newEffects,
        'messages' => $messages,
        'extra_attacks' => $extraAttacks
    ];
}

/**
 * 継続ダメージを処理（毒、燃焼など）
 * @param array $unit ユニット
 * @return array [damage, messages, updated_effects]
 */
function processDamageOverTime($unit) {
    $totalDamage = 0;
    $messages = [];
    $updatedEffects = [];
    
    foreach ($unit['active_effects'] as $effect) {
        if ($effect['effect_type'] === 'damage_over_time') {
            // 最大HPの割合でダメージ
            $dotDamage = (int)floor($unit['max_health'] * ($effect['effect_value'] / 100));
            $totalDamage += $dotDamage;
            $messages[] = "{$effect['skill_icon']} {$effect['skill_name']}により{$dotDamage}ダメージ！";
        }
        
        // 効果ターン減少
        $effect['remaining_turns']--;
        if ($effect['remaining_turns'] > 0) {
            $updatedEffects[] = $effect;
        } else {
            $messages[] = "{$effect['skill_icon']} {$effect['skill_name']}の効果が切れた";
        }
    }
    
    return [
        'damage' => $totalDamage,
        'messages' => $messages,
        'updated_effects' => $updatedEffects
    ];
}

/**
 * ターン制バトルを実行
 * @param array $attacker 攻撃側ユニット
 * @param array $defender 防御側ユニット
 * @return array バトル結果
 */
function executeTurnBattle($attacker, $defender) {
    $turnLogs = [];
    $currentTurn = 0;
    $battleSummary = [];
    
    // バトルループ
    while ($attacker['current_health'] > 0 && $defender['current_health'] > 0 && $currentTurn < BATTLE_MAX_TURNS) {
        $currentTurn++;
        $turnMessages = [];
        $turnMessages[] = "===== ターン {$currentTurn} =====";
        
        // --- 攻撃側のターン ---
        $attackerFrozen = false;
        $attackerStunned = false;
        
        // 凍結/スタンチェック
        foreach ($attacker['active_effects'] as $effect) {
            if ($effect['skill_key'] === 'freeze' && $effect['remaining_turns'] > 0) {
                $attackerFrozen = true;
                $turnMessages[] = "❄️ 攻撃側は凍結中！行動不能";
            }
            if ($effect['skill_key'] === 'stun' && $effect['remaining_turns'] > 0) {
                $attackerStunned = true;
                $turnMessages[] = "💫 攻撃側はスタン中！行動不能";
            }
        }
        
        if (!$attackerFrozen && !$attackerStunned) {
            // 継続ダメージ処理
            $dotResult = processDamageOverTime($attacker);
            if ($dotResult['damage'] > 0) {
                $attacker['current_health'] -= $dotResult['damage'];
                $turnMessages = array_merge($turnMessages, $dotResult['messages']);
            }
            $attacker['active_effects'] = $dotResult['updated_effects'];
            
            if ($attacker['current_health'] <= 0) {
                $turnMessages[] = "☠️ 攻撃側は継続ダメージで敗北！";
                $turnLogs[] = [
                    'turn' => $currentTurn,
                    'actor' => 'attacker',
                    'action' => 'defeat',
                    'messages' => $turnMessages,
                    'attacker_hp' => 0,
                    'defender_hp' => $defender['current_health']
                ];
                break;
            }
            
            // スキル発動判定
            $skillResult = tryActivateSkill($attacker, $defender, true);
            $turnMessages = array_merge($turnMessages, $skillResult['messages']);
            
            // 新しい効果を適用
            foreach ($skillResult['effects'] as $effect) {
                if ($effect['effect_target'] === 'self') {
                    $attacker['active_effects'][] = $effect;
                } else if ($effect['effect_target'] === 'enemy') {
                    $defender['active_effects'][] = $effect;
                }
            }
            
            // 攻撃回数（通常 + 加速）
            $attackCount = 1 + $skillResult['extra_attacks'];
            
            for ($i = 0; $i < $attackCount; $i++) {
                if ($defender['current_health'] <= 0) break;
                
                // ダメージ計算
                $damageResult = calculateDamage(
                    $attacker['attack'],
                    $defender['armor'],
                    $attacker['active_effects'],
                    $defender['active_effects']
                );
                
                $defender['current_health'] -= $damageResult['damage'];
                $defender['current_health'] = max(0, $defender['current_health']);
                
                $attackLabel = $attackCount > 1 ? "[攻撃{$i}+1] " : "";
                $turnMessages[] = "{$attackLabel}⚔️ 攻撃側が{$damageResult['damage']}ダメージを与えた！";
                $turnMessages = array_merge($turnMessages, $damageResult['messages']);
                $turnMessages[] = "防御側HP: {$defender['current_health']}/{$defender['max_health']}";
            }
            
            // 回復スキルチェック
            foreach ($attacker['active_effects'] as $effect) {
                if ($effect['skill_key'] === 'heal') {
                    $healAmount = (int)floor($attacker['max_health'] * ($effect['effect_value'] / 100));
                    $attacker['current_health'] = min($attacker['max_health'], $attacker['current_health'] + $healAmount);
                    $turnMessages[] = "💚 攻撃側が{$healAmount}回復！";
                }
            }
        }
        
        // 効果ターン減少（凍結/スタン）
        $newAttackerEffects = [];
        foreach ($attacker['active_effects'] as $effect) {
            if (in_array($effect['skill_key'], ['freeze', 'stun'])) {
                $effect['remaining_turns']--;
            }
            if ($effect['remaining_turns'] > 0) {
                $newAttackerEffects[] = $effect;
            }
        }
        $attacker['active_effects'] = $newAttackerEffects;
        
        if ($defender['current_health'] <= 0) {
            $turnMessages[] = "🏆 攻撃側の勝利！";
            $turnLogs[] = [
                'turn' => $currentTurn,
                'actor' => 'attacker',
                'action' => 'attack',
                'messages' => $turnMessages,
                'attacker_hp' => $attacker['current_health'],
                'defender_hp' => 0
            ];
            break;
        }
        
        // --- 防御側のターン ---
        $defenderFrozen = false;
        $defenderStunned = false;
        
        // 凍結/スタンチェック
        foreach ($defender['active_effects'] as $effect) {
            if ($effect['skill_key'] === 'freeze' && $effect['remaining_turns'] > 0) {
                $defenderFrozen = true;
                $turnMessages[] = "❄️ 防御側は凍結中！行動不能";
            }
            if ($effect['skill_key'] === 'stun' && $effect['remaining_turns'] > 0) {
                $defenderStunned = true;
                $turnMessages[] = "💫 防御側はスタン中！行動不能";
            }
        }
        
        if (!$defenderFrozen && !$defenderStunned) {
            // 継続ダメージ処理
            $dotResult = processDamageOverTime($defender);
            if ($dotResult['damage'] > 0) {
                $defender['current_health'] -= $dotResult['damage'];
                $turnMessages = array_merge($turnMessages, $dotResult['messages']);
            }
            $defender['active_effects'] = $dotResult['updated_effects'];
            
            if ($defender['current_health'] <= 0) {
                $turnMessages[] = "☠️ 防御側は継続ダメージで敗北！";
                $turnLogs[] = [
                    'turn' => $currentTurn,
                    'actor' => 'defender',
                    'action' => 'defeat',
                    'messages' => $turnMessages,
                    'attacker_hp' => $attacker['current_health'],
                    'defender_hp' => 0
                ];
                break;
            }
            
            // スキル発動判定
            $skillResult = tryActivateSkill($defender, $attacker, false);
            $turnMessages = array_merge($turnMessages, $skillResult['messages']);
            
            // 新しい効果を適用
            foreach ($skillResult['effects'] as $effect) {
                if ($effect['effect_target'] === 'self') {
                    $defender['active_effects'][] = $effect;
                } else if ($effect['effect_target'] === 'enemy') {
                    $attacker['active_effects'][] = $effect;
                }
            }
            
            // 攻撃回数
            $attackCount = 1 + $skillResult['extra_attacks'];
            
            for ($i = 0; $i < $attackCount; $i++) {
                if ($attacker['current_health'] <= 0) break;
                
                // ダメージ計算
                $damageResult = calculateDamage(
                    $defender['attack'],
                    $attacker['armor'],
                    $defender['active_effects'],
                    $attacker['active_effects']
                );
                
                $attacker['current_health'] -= $damageResult['damage'];
                $attacker['current_health'] = max(0, $attacker['current_health']);
                
                $attackLabel = $attackCount > 1 ? "[攻撃{$i}+1] " : "";
                $turnMessages[] = "{$attackLabel}🛡️ 防御側が{$damageResult['damage']}ダメージを与えた！";
                $turnMessages = array_merge($turnMessages, $damageResult['messages']);
                $turnMessages[] = "攻撃側HP: {$attacker['current_health']}/{$attacker['max_health']}";
            }
            
            // 回復スキルチェック
            foreach ($defender['active_effects'] as $effect) {
                if ($effect['skill_key'] === 'heal') {
                    $healAmount = (int)floor($defender['max_health'] * ($effect['effect_value'] / 100));
                    $defender['current_health'] = min($defender['max_health'], $defender['current_health'] + $healAmount);
                    $turnMessages[] = "💚 防御側が{$healAmount}回復！";
                }
            }
        }
        
        // 効果ターン減少（凍結/スタン）
        $newDefenderEffects = [];
        foreach ($defender['active_effects'] as $effect) {
            if (in_array($effect['skill_key'], ['freeze', 'stun'])) {
                $effect['remaining_turns']--;
            }
            if ($effect['remaining_turns'] > 0) {
                $newDefenderEffects[] = $effect;
            }
        }
        $defender['active_effects'] = $newDefenderEffects;
        
        $turnLogs[] = [
            'turn' => $currentTurn,
            'actor' => 'both',
            'action' => 'attack',
            'messages' => $turnMessages,
            'attacker_hp' => $attacker['current_health'],
            'defender_hp' => $defender['current_health']
        ];
        
        if ($attacker['current_health'] <= 0) {
            $battleSummary[] = "🏆 防御側の勝利！";
            break;
        }
    }
    
    // 最大ターン数に達した場合
    if ($currentTurn >= BATTLE_MAX_TURNS) {
        // HPが多い方が勝ち
        if ($attacker['current_health'] > $defender['current_health']) {
            $battleSummary[] = "⏰ 時間切れ！攻撃側の勝利！（残りHP: {$attacker['current_health']} vs {$defender['current_health']}）";
        } else if ($defender['current_health'] > $attacker['current_health']) {
            $battleSummary[] = "⏰ 時間切れ！防御側の勝利！（残りHP: {$defender['current_health']} vs {$attacker['current_health']}）";
        } else {
            $battleSummary[] = "⏰ 時間切れ！引き分け！";
        }
    }
    
    // 勝者判定
    $attackerWins = $attacker['current_health'] > 0 && 
                   ($defender['current_health'] <= 0 || $attacker['current_health'] > $defender['current_health']);
    
    return [
        'attacker_wins' => $attackerWins,
        'attacker_final_hp' => max(0, $attacker['current_health']),
        'defender_final_hp' => max(0, $defender['current_health']),
        'attacker_max_hp' => $attacker['max_health'],
        'defender_max_hp' => $defender['max_health'],
        'total_turns' => $currentTurn,
        'turn_logs' => $turnLogs,
        'summary' => $battleSummary
    ];
}

/**
 * バトルログをデータベースに保存（占領戦用）
 * @param PDO $pdo
 * @param int $battleId conquest_battle_logs.id
 * @param array $turnLogs ターンログ配列
 */
function saveConquestBattleTurnLogs($pdo, $battleId, $turnLogs) {
    $stmt = $pdo->prepare("
        INSERT INTO conquest_battle_turn_logs 
        (battle_id, turn_number, actor_side, action_type, 
         attacker_hp_before, attacker_hp_after, defender_hp_before, defender_hp_after,
         log_message, status_effects)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $prevAttackerHp = null;
    $prevDefenderHp = null;
    
    foreach ($turnLogs as $log) {
        $attackerHpBefore = $prevAttackerHp ?? $log['attacker_hp'];
        $defenderHpBefore = $prevDefenderHp ?? $log['defender_hp'];
        
        $stmt->execute([
            $battleId,
            $log['turn'],
            $log['actor'] === 'both' ? 'attacker' : $log['actor'],
            $log['action'],
            $attackerHpBefore,
            $log['attacker_hp'],
            $defenderHpBefore,
            $log['defender_hp'],
            implode("\n", $log['messages']),
            json_encode($log['status_effects'] ?? [])
        ]);
        
        $prevAttackerHp = $log['attacker_hp'];
        $prevDefenderHp = $log['defender_hp'];
    }
}

/**
 * バトルログをデータベースに保存（文明戦争用）
 * @param PDO $pdo
 * @param int $warLogId civilization_war_logs.id
 * @param array $turnLogs ターンログ配列
 */
function saveCivilizationBattleTurnLogs($pdo, $warLogId, $turnLogs) {
    $stmt = $pdo->prepare("
        INSERT INTO civilization_battle_turn_logs 
        (war_log_id, turn_number, actor_side, action_type, 
         attacker_hp_before, attacker_hp_after, defender_hp_before, defender_hp_after,
         log_message, status_effects)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $prevAttackerHp = null;
    $prevDefenderHp = null;
    
    foreach ($turnLogs as $log) {
        $attackerHpBefore = $prevAttackerHp ?? $log['attacker_hp'];
        $defenderHpBefore = $prevDefenderHp ?? $log['defender_hp'];
        
        $stmt->execute([
            $warLogId,
            $log['turn'],
            $log['actor'] === 'both' ? 'attacker' : $log['actor'],
            $log['action'],
            $attackerHpBefore,
            $log['attacker_hp'],
            $defenderHpBefore,
            $log['defender_hp'],
            implode("\n", $log['messages']),
            json_encode($log['status_effects'] ?? [])
        ]);
        
        $prevAttackerHp = $log['attacker_hp'];
        $prevDefenderHp = $log['defender_hp'];
    }
}

/**
 * NPC防御ユニットを準備
 * @param int $npcPower NPC防御パワー
 * @return array バトルユニット情報
 */
function prepareNpcDefenseUnit($npcPower) {
    // NPCのステータスはパワーから導出
    $attack = (int)floor($npcPower * 0.4);
    $armor = (int)floor($npcPower * 0.3);
    $health = (int)floor($npcPower * 3);
    
    return [
        'attack' => $attack,
        'armor' => $armor,
        'max_health' => $health,
        'current_health' => $health,
        'troops' => [
            [
                'troop_type_id' => 0,
                'name' => 'NPC守備隊',
                'icon' => '🏰',
                'count' => 1,
                'attack' => $attack,
                'defense' => $armor,
                'health' => $health,
                'category' => 'infantry'
            ]
        ],
        'skills' => [],
        'equipment_buffs' => ['attack' => 0, 'armor' => 0, 'health' => 0],
        'active_effects' => [],
        'is_frozen' => false,
        'is_stunned' => false,
        'extra_attacks' => 0,
    ];
}

/**
 * バトルログ概要を生成
 * @param array $battleResult バトル結果
 * @return string 概要テキスト
 */
function generateBattleSummary($battleResult) {
    $summary = [];
    $summary[] = "総ターン数: {$battleResult['total_turns']}";
    $summary[] = "攻撃側最終HP: {$battleResult['attacker_final_hp']}/{$battleResult['attacker_max_hp']}";
    $summary[] = "防御側最終HP: {$battleResult['defender_final_hp']}/{$battleResult['defender_max_hp']}";
    $summary[] = $battleResult['attacker_wins'] ? "結果: 攻撃側の勝利" : "結果: 防御側の勝利";
    
    return implode("\n", $summary);
}
