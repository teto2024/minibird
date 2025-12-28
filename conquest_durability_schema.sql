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
-- 注意: 兵種へのスキル割り当てはbattle_turn_system_schema.sqlで
-- 既に行われているため、ここでは上書きしません。
-- 元のスキル（燃焼、毒、凍結、無防備、攻撃力上昇、アーマー硬化、
-- 攻撃低下、加速など）を保持します。
-- 
-- もしスキルが正しく表示されない場合は、
-- restore_original_skills_schema.sql を実行してください。
-- ===============================================

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
SELECT 'Conquest durability system schema created successfully' AS status;
