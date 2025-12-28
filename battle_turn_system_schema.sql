-- ===============================================
-- MiniBird ターン制バトルシステムスキーマ
-- 攻撃/防御ターン制バトル、特殊スキル、詳細バトルログ
-- ===============================================

USE microblog;

-- ===============================================
-- 特殊スキルマスターテーブル
-- ===============================================
CREATE TABLE IF NOT EXISTS battle_special_skills (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    skill_key VARCHAR(50) NOT NULL UNIQUE COMMENT 'スキル識別子',
    name VARCHAR(100) NOT NULL COMMENT 'スキル名',
    icon VARCHAR(50) NOT NULL COMMENT 'アイコン絵文字',
    description TEXT COMMENT 'スキル説明',
    effect_type ENUM('buff', 'debuff', 'damage_over_time', 'special') NOT NULL DEFAULT 'buff' COMMENT 'エフェクトタイプ',
    effect_target ENUM('self', 'enemy', 'both') NOT NULL DEFAULT 'enemy' COMMENT 'エフェクト対象',
    effect_value DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'エフェクト値',
    duration_turns INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '効果持続ターン',
    activation_chance DECIMAL(5,2) NOT NULL DEFAULT 100 COMMENT '発動確率（%）',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_skill_key (skill_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='特殊スキルマスター';

-- ===============================================
-- 特殊スキル初期データ
-- ===============================================
INSERT IGNORE INTO battle_special_skills (skill_key, name, icon, description, effect_type, effect_target, effect_value, duration_turns, activation_chance) VALUES
('burn', '燃焼', '🔥', '敵に継続ダメージを与える。毎ターン10%の追加ダメージ', 'damage_over_time', 'enemy', 10, 3, 30),
('poison', '毒', '☠️', '敵に毒を付与し、毎ターン固定ダメージを与える', 'damage_over_time', 'enemy', 15, 4, 25),
('freeze', '凍結', '❄️', '敵を凍結させ、1ターン行動不能にする', 'debuff', 'enemy', 0, 1, 20),
('vulnerable', '無防備', '💔', '敵のアーマーを50%低下させる', 'debuff', 'enemy', 50, 2, 25),
('attack_up', '攻撃力上昇', '⚔️', '自分の攻撃力を25%上昇させる', 'buff', 'self', 25, 3, 30),
('armor_harden', 'アーマー硬化', '🛡️', '自分のアーマーを50%上昇させる', 'buff', 'self', 50, 3, 25),
('attack_down', '攻撃低下', '⬇️', '敵の攻撃力を20%低下させる', 'debuff', 'enemy', 20, 2, 30),
('acceleration', '加速', '⚡', '2ターン連続で攻撃できる', 'special', 'self', 2, 1, 15),
('heal', '回復', '💚', '自分の体力を15%回復する', 'buff', 'self', 15, 1, 20),
('stun', 'スタン', '💫', '敵を気絶させ、1ターン行動不能にする', 'debuff', 'enemy', 0, 1, 15),
('critical', 'クリティカル', '💥', 'クリティカルダメージ確率が上昇する', 'buff', 'self', 50, 2, 20),
('defense_break', '防御破壊', '🔨', '敵のアーマーを一時的に無視する', 'debuff', 'enemy', 100, 1, 10);

-- ===============================================
-- 兵種タイプに特殊スキルを追加
-- ===============================================
ALTER TABLE civilization_troop_types
ADD COLUMN IF NOT EXISTS special_skill_id INT UNSIGNED NULL COMMENT '特殊スキルID' AFTER heal_cost_resources,
ADD COLUMN IF NOT EXISTS health_points INT UNSIGNED NOT NULL DEFAULT 100 COMMENT '体力' AFTER defense_power,
ADD COLUMN IF NOT EXISTS troop_category ENUM('infantry', 'cavalry', 'ranged', 'siege') NOT NULL DEFAULT 'infantry' COMMENT '兵種カテゴリ' AFTER health_points;

-- 外部キー追加（存在チェック）
-- SET @constraint_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
--    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'civilization_troop_types' 
--    AND CONSTRAINT_NAME = 'fk_troop_special_skill');
-- ALTER TABLE civilization_troop_types
-- ADD CONSTRAINT fk_troop_special_skill FOREIGN KEY (special_skill_id) REFERENCES battle_special_skills(id) ON DELETE SET NULL;

-- ===============================================
-- 兵種に特殊スキルと体力を設定
-- ===============================================
-- 狩人 - 毒攻撃
UPDATE civilization_troop_types SET special_skill_id = 2, health_points = 60, troop_category = 'ranged' WHERE troop_key = 'hunter';
-- 戦士 - 攻撃力上昇
UPDATE civilization_troop_types SET special_skill_id = 5, health_points = 100, troop_category = 'infantry' WHERE troop_key = 'warrior';
-- 槍兵 - 防御破壊
UPDATE civilization_troop_types SET special_skill_id = 12, health_points = 120, troop_category = 'infantry' WHERE troop_key = 'spearman';
-- 戦車 - 加速
UPDATE civilization_troop_types SET special_skill_id = 8, health_points = 80, troop_category = 'cavalry' WHERE troop_key = 'chariot';
-- 剣士 - クリティカル
UPDATE civilization_troop_types SET special_skill_id = 11, health_points = 150, troop_category = 'infantry' WHERE troop_key = 'swordsman';
-- 騎兵 - 加速
UPDATE civilization_troop_types SET special_skill_id = 8, health_points = 120, troop_category = 'cavalry' WHERE troop_key = 'cavalry';
-- 弓兵 - 燃焼
UPDATE civilization_troop_types SET special_skill_id = 1, health_points = 70, troop_category = 'ranged' WHERE troop_key = 'archer';
-- 騎士 - アーマー硬化
UPDATE civilization_troop_types SET special_skill_id = 6, health_points = 200, troop_category = 'cavalry' WHERE troop_key = 'knight';
-- クロスボウ兵 - 無防備
UPDATE civilization_troop_types SET special_skill_id = 4, health_points = 90, troop_category = 'ranged' WHERE troop_key = 'crossbowman';
-- カタパルト - 防御破壊
UPDATE civilization_troop_types SET special_skill_id = 12, health_points = 50, troop_category = 'siege' WHERE troop_key = 'catapult';
-- マスケット銃兵 - 攻撃低下
UPDATE civilization_troop_types SET special_skill_id = 7, health_points = 100, troop_category = 'ranged' WHERE troop_key = 'musketeer';
-- 大砲 - 燃焼
UPDATE civilization_troop_types SET special_skill_id = 1, health_points = 60, troop_category = 'siege' WHERE troop_key = 'cannon';
-- ガレオン船 - スタン
UPDATE civilization_troop_types SET special_skill_id = 10, health_points = 180, troop_category = 'ranged' WHERE troop_key = 'galleon';
-- 歩兵 - 回復
UPDATE civilization_troop_types SET special_skill_id = 9, health_points = 150, troop_category = 'infantry' WHERE troop_key = 'infantry';
-- 砲兵 - 攻撃低下
UPDATE civilization_troop_types SET special_skill_id = 7, health_points = 80, troop_category = 'siege' WHERE troop_key = 'artillery';
-- 装甲艦 - アーマー硬化
UPDATE civilization_troop_types SET special_skill_id = 6, health_points = 300, troop_category = 'ranged' WHERE troop_key = 'ironclad';
-- 戦車 - クリティカル
UPDATE civilization_troop_types SET special_skill_id = 11, health_points = 400, troop_category = 'cavalry' WHERE troop_key = 'tank';
-- 戦闘機 - 加速
UPDATE civilization_troop_types SET special_skill_id = 8, health_points = 150, troop_category = 'ranged' WHERE troop_key = 'fighter';
-- 爆撃機 - 燃焼
UPDATE civilization_troop_types SET special_skill_id = 1, health_points = 120, troop_category = 'ranged' WHERE troop_key = 'bomber';
-- 潜水艦 - 毒
UPDATE civilization_troop_types SET special_skill_id = 2, health_points = 200, troop_category = 'ranged' WHERE troop_key = 'submarine';

-- 追加兵種への設定
-- 斥候 - 加速
UPDATE civilization_troop_types SET special_skill_id = 8, health_points = 40, troop_category = 'cavalry' WHERE troop_key = 'scout';
-- 民兵 - 回復
UPDATE civilization_troop_types SET special_skill_id = 9, health_points = 80, troop_category = 'infantry' WHERE troop_key = 'militia';
-- ファランクス - アーマー硬化
UPDATE civilization_troop_types SET special_skill_id = 6, health_points = 180, troop_category = 'infantry' WHERE troop_key = 'phalanx';
-- 長槍兵 - 防御破壊
UPDATE civilization_troop_types SET special_skill_id = 12, health_points = 140, troop_category = 'infantry' WHERE troop_key = 'pikeman';
-- 長弓兵 - 凍結
UPDATE civilization_troop_types SET special_skill_id = 3, health_points = 80, troop_category = 'ranged' WHERE troop_key = 'longbowman';
-- トレビュシェット - 無防備
UPDATE civilization_troop_types SET special_skill_id = 4, health_points = 60, troop_category = 'siege' WHERE troop_key = 'trebuchet';
-- 戦象 - スタン
UPDATE civilization_troop_types SET special_skill_id = 10, health_points = 350, troop_category = 'cavalry' WHERE troop_key = 'war_elephant';
-- ライフル兵 - クリティカル
UPDATE civilization_troop_types SET special_skill_id = 11, health_points = 120, troop_category = 'ranged' WHERE troop_key = 'rifleman';
-- 竜騎兵 - 攻撃力上昇
UPDATE civilization_troop_types SET special_skill_id = 5, health_points = 140, troop_category = 'cavalry' WHERE troop_key = 'dragoon';
-- フリゲート艦 - 凍結
UPDATE civilization_troop_types SET special_skill_id = 3, health_points = 200, troop_category = 'ranged' WHERE troop_key = 'frigate';
-- 海兵隊 - 攻撃力上昇
UPDATE civilization_troop_types SET special_skill_id = 5, health_points = 160, troop_category = 'infantry' WHERE troop_key = 'marine';
-- 空挺部隊 - 加速
UPDATE civilization_troop_types SET special_skill_id = 8, health_points = 130, troop_category = 'infantry' WHERE troop_key = 'paratroopers';
-- 特殊部隊 - クリティカル
UPDATE civilization_troop_types SET special_skill_id = 11, health_points = 180, troop_category = 'infantry' WHERE troop_key = 'special_forces';
-- ミサイル発射機 - 燃焼
UPDATE civilization_troop_types SET special_skill_id = 1, health_points = 70, troop_category = 'siege' WHERE troop_key = 'missile_launcher';
-- ステルス戦闘機 - 無防備
UPDATE civilization_troop_types SET special_skill_id = 4, health_points = 180, troop_category = 'ranged' WHERE troop_key = 'stealth_fighter';
-- 航空母艦 - アーマー硬化
UPDATE civilization_troop_types SET special_skill_id = 6, health_points = 500, troop_category = 'siege' WHERE troop_key = 'aircraft_carrier';
-- 原子力潜水艦 - 毒
UPDATE civilization_troop_types SET special_skill_id = 2, health_points = 400, troop_category = 'ranged' WHERE troop_key = 'nuclear_submarine';

-- 医療ユニット
UPDATE civilization_troop_types SET special_skill_id = 9, health_points = 60, troop_category = 'infantry' WHERE troop_key = 'medic';
UPDATE civilization_troop_types SET special_skill_id = 9, health_points = 80, troop_category = 'infantry' WHERE troop_key = 'field_surgeon';
-- 攻城兵器
UPDATE civilization_troop_types SET special_skill_id = 4, health_points = 250, troop_category = 'siege' WHERE troop_key = 'siege_tower';
UPDATE civilization_troop_types SET special_skill_id = 12, health_points = 200, troop_category = 'siege' WHERE troop_key = 'battering_ram';
-- 特殊ユニット
UPDATE civilization_troop_types SET special_skill_id = 6, health_points = 180, troop_category = 'infantry' WHERE troop_key = 'royal_guard';
UPDATE civilization_troop_types SET special_skill_id = 5, health_points = 100, troop_category = 'infantry' WHERE troop_key = 'berserker';

-- ===============================================
-- ターン制バトル詳細ログテーブル（占領戦用）
-- ===============================================
CREATE TABLE IF NOT EXISTS conquest_battle_turn_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    battle_id BIGINT UNSIGNED NOT NULL COMMENT '戦闘ログID（conquest_battle_logs.id）',
    turn_number INT UNSIGNED NOT NULL COMMENT 'ターン番号',
    actor_side ENUM('attacker', 'defender') NOT NULL COMMENT '行動者側',
    actor_troop_type_id INT UNSIGNED NULL COMMENT '行動した兵種ID',
    action_type ENUM('attack', 'skill', 'status_effect', 'defeat') NOT NULL COMMENT 'アクション種類',
    damage_dealt INT NOT NULL DEFAULT 0 COMMENT '与えたダメージ',
    damage_received INT NOT NULL DEFAULT 0 COMMENT '受けたダメージ',
    skill_activated VARCHAR(50) NULL COMMENT '発動したスキル',
    skill_effect TEXT NULL COMMENT 'スキル効果の説明',
    attacker_hp_before INT NOT NULL DEFAULT 0 COMMENT '攻撃側のターン前HP',
    attacker_hp_after INT NOT NULL DEFAULT 0 COMMENT '攻撃側のターン後HP',
    defender_hp_before INT NOT NULL DEFAULT 0 COMMENT '防御側のターン前HP',
    defender_hp_after INT NOT NULL DEFAULT 0 COMMENT '防御側のターン後HP',
    status_effects JSON COMMENT '現在の状態異常一覧',
    log_message TEXT COMMENT 'ログメッセージ',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (battle_id) REFERENCES conquest_battle_logs(id) ON DELETE CASCADE,
    INDEX idx_battle (battle_id),
    INDEX idx_turn (battle_id, turn_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='占領戦ターン制バトルログ';

-- ===============================================
-- ターン制バトル詳細ログテーブル（文明戦争用）
-- ===============================================
CREATE TABLE IF NOT EXISTS civilization_battle_turn_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    war_log_id BIGINT UNSIGNED NOT NULL COMMENT '戦争ログID（civilization_war_logs.id）',
    turn_number INT UNSIGNED NOT NULL COMMENT 'ターン番号',
    actor_side ENUM('attacker', 'defender') NOT NULL COMMENT '行動者側',
    actor_troop_type_id INT UNSIGNED NULL COMMENT '行動した兵種ID',
    action_type ENUM('attack', 'skill', 'status_effect', 'defeat') NOT NULL COMMENT 'アクション種類',
    damage_dealt INT NOT NULL DEFAULT 0 COMMENT '与えたダメージ',
    damage_received INT NOT NULL DEFAULT 0 COMMENT '受けたダメージ',
    skill_activated VARCHAR(50) NULL COMMENT '発動したスキル',
    skill_effect TEXT NULL COMMENT 'スキル効果の説明',
    attacker_hp_before INT NOT NULL DEFAULT 0 COMMENT '攻撃側のターン前HP',
    attacker_hp_after INT NOT NULL DEFAULT 0 COMMENT '攻撃側のターン後HP',
    defender_hp_before INT NOT NULL DEFAULT 0 COMMENT '防御側のターン前HP',
    defender_hp_after INT NOT NULL DEFAULT 0 COMMENT '防御側のターン後HP',
    status_effects JSON COMMENT '現在の状態異常一覧',
    log_message TEXT COMMENT 'ログメッセージ',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (war_log_id) REFERENCES civilization_war_logs(id) ON DELETE CASCADE,
    INDEX idx_war_log (war_log_id),
    INDEX idx_turn (war_log_id, turn_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='文明戦争ターン制バトルログ';

-- ===============================================
-- 戦闘ログテーブルにターン制バトル結果を追加
-- ===============================================
ALTER TABLE conquest_battle_logs
ADD COLUMN IF NOT EXISTS total_turns INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '総ターン数' AFTER castle_captured,
ADD COLUMN IF NOT EXISTS battle_log_summary TEXT COMMENT 'バトルログ概要' AFTER total_turns,
ADD COLUMN IF NOT EXISTS attacker_final_hp INT NOT NULL DEFAULT 0 COMMENT '攻撃側最終HP' AFTER battle_log_summary,
ADD COLUMN IF NOT EXISTS defender_final_hp INT NOT NULL DEFAULT 0 COMMENT '防御側最終HP' AFTER attacker_final_hp;

ALTER TABLE civilization_war_logs
ADD COLUMN IF NOT EXISTS total_turns INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '総ターン数' AFTER defender_wounded,
ADD COLUMN IF NOT EXISTS battle_log_summary TEXT COMMENT 'バトルログ概要' AFTER total_turns,
ADD COLUMN IF NOT EXISTS attacker_final_hp INT NOT NULL DEFAULT 0 COMMENT '攻撃側最終HP' AFTER battle_log_summary,
ADD COLUMN IF NOT EXISTS defender_final_hp INT NOT NULL DEFAULT 0 COMMENT '防御側最終HP' AFTER attacker_final_hp;

-- テーブル作成完了メッセージ
SELECT 'Battle turn system schema created successfully' AS status;
