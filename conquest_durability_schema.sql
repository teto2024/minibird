-- ===============================================
-- MiniBird 占領戦耐久度システム・兵士バフ/デバフ拡張スキーマ
-- 城の耐久度システム、攻城兵器効率、兵士バフ/デバフ追加
-- ===============================================

USE microblog;

-- ===============================================
-- 城に耐久度カラムを追加
-- ===============================================
ALTER TABLE conquest_castles
ADD COLUMN IF NOT EXISTS durability INT UNSIGNED NOT NULL DEFAULT 100 COMMENT '現在の耐久度' AFTER icon,
ADD COLUMN IF NOT EXISTS max_durability INT UNSIGNED NOT NULL DEFAULT 100 COMMENT '最大耐久度' AFTER durability;

-- 城の種類に応じた耐久度を設定（高めの値）
-- outer: 500, middle: 1000, inner: 2000, sacred: 5000
UPDATE conquest_castles SET durability = 500, max_durability = 500 WHERE castle_type = 'outer';
UPDATE conquest_castles SET durability = 1000, max_durability = 1000 WHERE castle_type = 'middle';
UPDATE conquest_castles SET durability = 2000, max_durability = 2000 WHERE castle_type = 'inner';
UPDATE conquest_castles SET durability = 5000, max_durability = 5000 WHERE castle_type = 'sacred';

-- ===============================================
-- 戦闘ログに耐久度ダメージを追加
-- ===============================================
ALTER TABLE conquest_battle_logs
ADD COLUMN IF NOT EXISTS durability_damage INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '城への耐久度ダメージ' AFTER castle_captured,
ADD COLUMN IF NOT EXISTS is_durability_attack BOOLEAN NOT NULL DEFAULT FALSE COMMENT '耐久度攻撃かどうか' AFTER durability_damage;

-- ===============================================
-- 砲撃ログに耐久度ダメージを追加
-- ===============================================
ALTER TABLE conquest_bombardment_logs
ADD COLUMN IF NOT EXISTS durability_damage INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '城への耐久度ダメージ' AFTER total_wounded;

-- ===============================================
-- 追加の特殊スキル（兵士バフ/デバフ）
-- ===============================================
INSERT IGNORE INTO battle_special_skills (skill_key, name, icon, description, effect_type, effect_target, effect_value, duration_turns, activation_chance) VALUES
-- 攻撃系バフ
('siege_mastery', '攻城術', '🏰', '攻城兵器の城壁への攻撃力が50%上昇', 'buff', 'self', 50, 3, 25),
('war_cry', '雄叫び', '📣', '味方全体の攻撃力を15%上昇', 'buff', 'self', 15, 2, 30),
('bloodlust', '血の渇望', '🩸', '敵を倒すたびに攻撃力が20%上昇', 'buff', 'self', 20, 99, 20),
('precision', '精密射撃', '🎯', 'クリティカル率を30%上昇', 'buff', 'self', 30, 3, 25),

-- 防御系バフ
('fortify', '防御陣形', '🛡️', '防御力を40%上昇', 'buff', 'self', 40, 3, 25),
('iron_will', '鉄の意志', '💪', '状態異常に対する耐性が50%上昇', 'buff', 'self', 50, 3, 20),
('phalanx_formation', 'ファランクス陣形', '⚔️', '前列の防御力を60%上昇', 'buff', 'self', 60, 2, 15),
('shield_wall', '盾の壁', '🔰', '受けるダメージを25%軽減', 'buff', 'self', 25, 3, 25),

-- 攻撃系デバフ
('weakness', '弱体化', '😵', '敵の攻撃力を25%低下', 'debuff', 'enemy', 25, 3, 25),
('disarm', '武装解除', '🚫', '敵の攻撃を1ターン封じる', 'debuff', 'enemy', 0, 1, 15),
('fear', '恐怖', '😱', '敵の攻撃力と防御力を20%低下', 'debuff', 'enemy', 20, 2, 20),
('slow', '鈍化', '🐌', '敵の行動速度を50%低下', 'debuff', 'enemy', 50, 2, 25),

-- 防御系デバフ
('armor_crush', '鎧砕き', '💔', '敵のアーマーを30%低下', 'debuff', 'enemy', 30, 3, 25),
('expose_weakness', '弱点露出', '🔍', '敵へのクリティカルダメージが50%増加', 'debuff', 'enemy', 50, 3, 20),
('curse', '呪い', '👻', '敵の回復効果を50%減少', 'debuff', 'enemy', 50, 4, 15),
('bleed', '出血', '🩸', '敵に継続ダメージを与え、毎ターン5%のダメージ', 'damage_over_time', 'enemy', 5, 4, 30),

-- 特殊効果
('rally', '鼓舞', '🎺', '味方全体のHPを10%回復', 'buff', 'self', 10, 1, 15),
('counter_attack', '反撃', '⚡', '受けたダメージの50%を反射', 'special', 'self', 50, 2, 20),
('evasion', '回避', '💨', '攻撃を30%の確率で回避', 'buff', 'self', 30, 3, 25),
('taunt', '挑発', '😤', '敵の攻撃を自分に集中させる', 'special', 'self', 0, 2, 20);

-- ===============================================
-- 兵種に新しいバフ/デバフスキルを割り当て
-- 各兵種の特徴を活かしたスキル設定
-- 攻撃系バフ: 攻城術(siege_mastery), 雄叫び(war_cry), 血の渇望(bloodlust), 精密射撃(precision)
-- 防御系バフ: 防御陣形(fortify), 鉄の意志(iron_will), ファランクス陣形(phalanx_formation), 盾の壁(shield_wall)
-- 攻撃系デバフ: 弱体化(weakness), 武装解除(disarm), 恐怖(fear), 鈍化(slow)
-- 防御系デバフ: 鎧砕き(armor_crush), 弱点露出(expose_weakness), 呪い(curse), 出血(bleed)
-- 特殊効果: 鼓舞(rally), 反撃(counter_attack), 回避(evasion), 挑発(taunt)
-- ===============================================

-- スキルIDを変数として取得するために、直接IDで指定
-- 既存の12スキル + 新規20スキル = 32スキル
-- 新規スキルは ID 13以降に配置される想定

-- 攻城兵器系 - 攻城術(siege_mastery)スキル
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'siege_mastery' LIMIT 1) WHERE troop_key = 'catapult';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'siege_mastery' LIMIT 1) WHERE troop_key = 'trebuchet';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'siege_mastery' LIMIT 1) WHERE troop_key = 'battering_ram';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'siege_mastery' LIMIT 1) WHERE troop_key = 'siege_tower';

-- 砲撃系 - 攻城術(siege_mastery)スキル
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'siege_mastery' LIMIT 1) WHERE troop_key = 'cannon';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'siege_mastery' LIMIT 1) WHERE troop_key = 'artillery';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'siege_mastery' LIMIT 1) WHERE troop_key = 'bomber';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'siege_mastery' LIMIT 1) WHERE troop_key = 'missile_launcher';

-- 歩兵系 - 雄叫び(war_cry)スキル
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'war_cry' LIMIT 1) WHERE troop_key = 'warrior';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'war_cry' LIMIT 1) WHERE troop_key = 'infantry';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'war_cry' LIMIT 1) WHERE troop_key = 'marine';

-- 狂戦士系 - 血の渇望(bloodlust)スキル
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'bloodlust' LIMIT 1) WHERE troop_key = 'berserker';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'bloodlust' LIMIT 1) WHERE troop_key = 'special_forces';

-- 射撃系 - 精密射撃(precision)スキル
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'precision' LIMIT 1) WHERE troop_key = 'hunter';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'precision' LIMIT 1) WHERE troop_key = 'archer';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'precision' LIMIT 1) WHERE troop_key = 'crossbowman';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'precision' LIMIT 1) WHERE troop_key = 'longbowman';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'precision' LIMIT 1) WHERE troop_key = 'rifleman';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'precision' LIMIT 1) WHERE troop_key = 'musketeer';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'precision' LIMIT 1) WHERE troop_key = 'stealth_fighter';

-- 重装系 - 防御陣形(fortify)スキル
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'fortify' LIMIT 1) WHERE troop_key = 'royal_guard';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'fortify' LIMIT 1) WHERE troop_key = 'swordsman';

-- 精鋭系 - 鉄の意志(iron_will)スキル
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'iron_will' LIMIT 1) WHERE troop_key = 'paratroopers';

-- 槍兵系 - ファランクス陣形(phalanx_formation)スキル
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'phalanx_formation' LIMIT 1) WHERE troop_key = 'spearman';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'phalanx_formation' LIMIT 1) WHERE troop_key = 'phalanx';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'phalanx_formation' LIMIT 1) WHERE troop_key = 'pikeman';

-- 騎士系 - 盾の壁(shield_wall)スキル
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'shield_wall' LIMIT 1) WHERE troop_key = 'knight';

-- 騎兵系 - 恐怖(fear)スキル
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'fear' LIMIT 1) WHERE troop_key = 'cavalry';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'fear' LIMIT 1) WHERE troop_key = 'chariot';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'fear' LIMIT 1) WHERE troop_key = 'war_elephant';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'fear' LIMIT 1) WHERE troop_key = 'dragoon';

-- 戦車系 - 鎧砕き(armor_crush)スキル
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'armor_crush' LIMIT 1) WHERE troop_key = 'tank';

-- 戦闘機系 - 回避(evasion)スキル
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'evasion' LIMIT 1) WHERE troop_key = 'fighter';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'evasion' LIMIT 1) WHERE troop_key = 'scout';

-- 潜水艦系 - 弱点露出(expose_weakness)スキル
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'expose_weakness' LIMIT 1) WHERE troop_key = 'submarine';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'expose_weakness' LIMIT 1) WHERE troop_key = 'nuclear_submarine';

-- 海軍系 - 鈍化(slow)スキル
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'slow' LIMIT 1) WHERE troop_key = 'galleon';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'slow' LIMIT 1) WHERE troop_key = 'frigate';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'slow' LIMIT 1) WHERE troop_key = 'ironclad';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'slow' LIMIT 1) WHERE troop_key = 'aircraft_carrier';

-- 民兵系 - 挑発(taunt)スキル
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'taunt' LIMIT 1) WHERE troop_key = 'militia';

-- 医療系 - 鼓舞(rally)スキル
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'rally' LIMIT 1) WHERE troop_key = 'medic';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'rally' LIMIT 1) WHERE troop_key = 'field_surgeon';

-- 攻城兵器に攻城ダメージボーナスフラグを追加
ALTER TABLE civilization_troop_types
ADD COLUMN IF NOT EXISTS siege_damage_multiplier DECIMAL(3,1) NOT NULL DEFAULT 1.0 COMMENT '攻城ダメージ倍率' AFTER troop_category;

-- 攻城兵器の攻城ダメージ倍率を設定
UPDATE civilization_troop_types SET siege_damage_multiplier = 3.0 WHERE troop_key = 'catapult';
UPDATE civilization_troop_types SET siege_damage_multiplier = 4.0 WHERE troop_key = 'cannon';
UPDATE civilization_troop_types SET siege_damage_multiplier = 2.5 WHERE troop_key = 'siege_tower';
UPDATE civilization_troop_types SET siege_damage_multiplier = 3.5 WHERE troop_key = 'battering_ram';
UPDATE civilization_troop_types SET siege_damage_multiplier = 2.0 WHERE troop_key = 'trebuchet';
UPDATE civilization_troop_types SET siege_damage_multiplier = 5.0 WHERE troop_key = 'artillery';
UPDATE civilization_troop_types SET siege_damage_multiplier = 6.0 WHERE troop_key = 'bomber';
UPDATE civilization_troop_types SET siege_damage_multiplier = 4.0 WHERE troop_key = 'missile_launcher';

-- 攻城カテゴリの兵種にはデフォルトで2.0倍を設定
UPDATE civilization_troop_types SET siege_damage_multiplier = 2.0 WHERE troop_category = 'siege' AND siege_damage_multiplier = 1.0;

-- テーブル作成完了メッセージ
SELECT 'Conquest durability system and soldier buff/debuff schema created successfully' AS status;
