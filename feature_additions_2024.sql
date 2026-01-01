-- ===============================================
-- MiniBird 機能追加・修正 2024
-- ===============================================

USE microblog;

-- ===============================================
-- 13: バフテーブルにactivated_byカラムを追加（存在しない場合）
-- ===============================================

ALTER TABLE buffs ADD COLUMN IF NOT EXISTS activated_by INT UNSIGNED NULL COMMENT 'バフを発動したユーザーID' AFTER level;

-- ===============================================
-- ① 新規資源の追加
-- 包帯、ゴム、チタン、大理石、鋼鉄、香辛料、火薬、火薬資源、マナ
-- ===============================================

INSERT IGNORE INTO civilization_resource_types (resource_key, name, icon, description, unlock_order, color) VALUES
('bandage', '包帯', '🩹', '負傷者の治療に使用', 2, '#FFFACD'),
('rubber', 'ゴム', '⚫', '工業製品の素材', 4, '#2F2F2F'),
('titanium', 'チタン', '🔷', '高強度の金属素材', 5, '#E0E0E0'),
('marble', '大理石', '🏛️', '高級建材', 3, '#FAFAFA'),
('steel', '鋼鉄', '🔩', '近代的な武器と建物に必要', 4, '#708090'),
('spice', '香辛料', '🌶️', '貿易と料理に使用', 3, '#FF6347'),
('gunpowder', '火薬', '💥', '銃火器と爆発物に必要', 4, '#8B4513'),
('saltpeter', '火薬資源', '🧂', '火薬の原料', 3, '#F5F5F5'),
('mana', 'マナ', '✨', '魔法の源、特殊効果に使用', 6, '#9932CC');

-- ===============================================
-- ⑨ シャドウアサシンのスキル弱体化
-- 50%即死 → 20%で半壊（半分即死）
-- ===============================================

UPDATE heroes 
SET battle_skill_desc = '20%の確率で敵を半壊させる（HPを半分にする、ボス無効）',
    battle_skill_effect = '{"half_kill_chance": 20}'
WHERE hero_key = 'shadow_assassin';

-- ===============================================
-- ③ 新ヒーロー5種類追加
-- ===============================================

INSERT IGNORE INTO heroes (hero_key, name, title, description, icon, generation, rarity, unlock_shards, star_up_shards, battle_skill_name, battle_skill_desc, battle_skill_effect, passive_skill_name, passive_skill_desc, passive_skill_effect) VALUES
-- ヒーロー1: 防御特化タンク
('iron_fortress', 'アイアンフォートレス', '鋼鉄の要塞', '鉄壁の防御を誇る重装戦士。味方を守り抜く不動の盾。', '🛡️', 0, 'epic', 40, '[50, 80, 125, 190, 280, 400, 550]', '鋼鉄の守護', '味方全体のアーマーを80%上昇させ、自身が敵の攻撃を2ターン引き付ける', '{"armor_buff": 80, "taunt_duration": 2}', '要塞の加護', '建物の防御力が10%増加', '{"building_defense_bonus": 10}'),

-- ヒーロー2: 高速アタッカー
('wind_dancer', 'ウィンドダンサー', '疾風の踊り子', '風のように素早く敵を翻弄する踊り子。連続攻撃が得意。', '💨', 0, 'rare', 25, '[30, 50, 80, 120, 175, 250, 350]', '疾風連撃', '敵に5連続攻撃を行い、合計200%のダメージを与える', '{"damage_multiplier": 2.0, "hit_count": 5}', '風の祝福', '兵士の移動速度が15%増加', '{"movement_speed_bonus": 15}'),

-- ヒーロー3: 回復特化サポート
('life_weaver', 'ライフウィーバー', '命の紡ぎ手', '生命力を操り味方を癒す聖職者。回復と蘇生を得意とする。', '💚', 0, 'epic', 40, '[50, 80, 125, 190, 280, 400, 550]', '生命の奔流', '味方全体のHPを50%回復し、2ターンの間毎ターン10%の継続回復を付与', '{"heal_percent": 50, "hot_percent": 10, "hot_duration": 2}', '生命の恵み', '負傷兵の治療コストが20%減少', '{"heal_cost_reduction": 20}'),

-- ヒーロー4: 範囲デバッファー
('plague_doctor', 'プレイグドクター', '疫病の医師', '毒と疫病を操る異端の医師。敵全体を弱体化させる。', '☠️', 0, 'rare', 25, '[30, 50, 80, 120, 175, 250, 350]', '疫病の霧', '敵全体に毒を付与し、3ターンの間毎ターン15%のダメージを与え、攻撃力を30%減少させる', '{"poison_percent": 15, "poison_duration": 3, "attack_debuff": 30}', '免疫強化', '味方の状態異常耐性が25%増加', '{"debuff_resistance": 25}'),

-- ヒーロー5: 資源収集特化
('treasure_hunter', 'トレジャーハンター', '財宝の狩人', '世界中の財宝を探し求める冒険家。戦闘後の報酬を大幅に増加させる。', '💰', 0, 'legendary', 60, '[80, 130, 200, 300, 440, 620, 850]', '黄金の嗅覚', '戦闘勝利時、獲得資源を50%増加させ、レアアイテムドロップ率を25%上昇', '{"loot_bonus": 50, "rare_drop_bonus": 25}', '財宝の加護', '全資源の生産量が15%増加', '{"resource_production_bonus": 15}');

-- ===============================================
-- 11: 核汚染スキルの追加
-- ===============================================

INSERT IGNORE INTO battle_special_skills (skill_key, name, icon, description, effect_type, effect_target, effect_value, duration_turns, activation_chance) VALUES
('nuclear_contamination', '核汚染', '☢️', '放射能で敵に継続ダメージを与える（毎ターン固定ダメージ、上限付き）', 'nuclear_dot', 'enemy', 50, 99, 20);

-- ===============================================
-- 15: ユニットスキルの振り分け変更（1スキル1兵種）
-- 追加のユニークスキルを作成
-- ===============================================

INSERT IGNORE INTO battle_special_skills (skill_key, name, icon, description, effect_type, effect_target, effect_value, duration_turns, activation_chance) VALUES
-- 歩兵系ユニークスキル
('warrior_fury', '戦士の怒り', '😤', '戦士が怒りで攻撃力を30%上昇', 'buff', 'self', 30, 2, 25),
('spear_thrust', '槍突撃', '🗡️', '槍による貫通攻撃でアーマーを無視', 'debuff', 'enemy', 100, 1, 20),
('sword_dance', '剣舞', '💃', '剣士の華麗な連撃で追加ダメージ', 'special', 'self', 25, 1, 20),
('phalanx_wall', 'ファランクス陣', '🧱', '密集陣形でアーマー100%上昇', 'buff', 'self', 100, 2, 30),
('pike_formation', '槍衾', '🔱', '槍の壁で騎兵に大ダメージ', 'special', 'enemy', 50, 1, 25),
('marine_assault', '海兵突撃', '🌊', '海兵隊の急襲で先制攻撃', 'special', 'self', 30, 1, 20),
('elite_tactics', '精鋭戦術', '🎖️', '特殊部隊の戦術でクリティカル率2倍', 'buff', 'self', 100, 2, 15),
('berserk_rage', '狂戦士の激怒', '🔴', '攻撃力2倍だがアーマー半減', 'special', 'self', 100, 2, 25),
('royal_command', '王室の命令', '👑', '近衛兵の士気向上でステータス上昇', 'buff', 'self', 40, 3, 20),
('militia_resolve', '民兵の決意', '✊', '民兵の意志で体力を回復', 'buff', 'self', 20, 1, 30),
('medic_care', '軍医の治療', '💉', '軍医による高効率治療', 'buff', 'self', 25, 1, 35),
('surgeon_skill', '外科医の技術', '🏥', '野戦外科医の緊急治療', 'buff', 'self', 30, 1, 30),

-- 騎兵系ユニークスキル
('chariot_rush', '戦車突進', '🛞', '戦車の突進で敵を轢く', 'special', 'enemy', 35, 1, 25),
('cavalry_charge', '騎兵突撃', '🐎', '騎馬隊の突撃で大ダメージ', 'special', 'enemy', 40, 1, 20),
('knight_honor', '騎士の誇り', '⚜️', '騎士道精神でアーマー強化', 'buff', 'self', 60, 2, 25),
('scout_evasion', '斥候の回避', '👁️', '斥候の回避術で攻撃を躱す', 'buff', 'self', 30, 2, 30),
('dragoon_fire', '竜騎兵の射撃', '🔫', '馬上射撃で追加ダメージ', 'special', 'enemy', 25, 1, 25),
('elephant_stomp', '象の踏みつけ', '🐘', '戦象の踏み潰しで敵を粉砕', 'special', 'enemy', 60, 1, 15),
('tank_armor', '戦車装甲', '🛡️', '重装甲でダメージを大幅軽減', 'buff', 'self', 80, 2, 20),
('airborne_drop', '空挺降下', '🪂', '空挺部隊の奇襲攻撃', 'special', 'self', 35, 1, 25),

-- 遠距離系ユニークスキル
('hunter_trap', '狩人の罠', '🪤', '罠で敵を足止め', 'debuff', 'enemy', 30, 2, 20),
('archer_volley', '弓兵の一斉射撃', '🏹', '矢の雨で敵全体にダメージ', 'special', 'enemy', 20, 1, 25),
('crossbow_pierce', 'クロスボウ貫通', '🎯', 'クロスボウの貫通射撃', 'debuff', 'enemy', 70, 1, 20),
('longbow_range', 'ロングボウの射程', '🏹', '長弓の遠距離攻撃', 'special', 'self', 25, 2, 25),
('musket_smoke', 'マスケットの煙幕', '💨', '煙幕で敵の命中率を下げる', 'debuff', 'enemy', 25, 2, 25),
('rifleman_aim', 'ライフルの精密射撃', '🔭', '精密射撃でクリティカル率上昇', 'buff', 'self', 60, 2, 20),
('fighter_dogfight', '戦闘機の空戦', '✈️', '空中戦で優位を取る', 'special', 'self', 30, 1, 20),
('stealth_ambush', 'ステルスの奇襲', '🥷', 'ステルス機の奇襲攻撃', 'special', 'enemy', 45, 1, 15),
('submarine_torpedo', '潜水艦の魚雷', '💣', '魚雷攻撃で大ダメージ', 'special', 'enemy', 50, 1, 15),
('nuclear_sub_launch', '核潜水艦のミサイル発射', '🚀', '核ミサイルで壊滅的ダメージ', 'special', 'enemy', 80, 1, 10),

-- 攻城系ユニークスキル
('catapult_siege', 'カタパルト攻城', '🏰', '城壁を破壊する攻城攻撃', 'debuff', 'enemy', 60, 1, 20),
('cannon_blast', '大砲の砲撃', '💥', '砲撃で敵陣を吹き飛ばす', 'special', 'enemy', 45, 1, 25),
('trebuchet_launch', 'トレビュシェット投擲', '🪨', '巨石投擲で城壁破壊', 'debuff', 'enemy', 80, 1, 15),
('artillery_barrage', '砲兵の弾幕', '🎆', '弾幕射撃で敵全体にダメージ', 'special', 'enemy', 30, 1, 20),
('missile_strike', 'ミサイル攻撃', '🎯', 'ミサイル攻撃で精密打撃', 'special', 'enemy', 55, 1, 20),
('siege_tower_climb', '攻城塔登攀', '🗼', '攻城塔で城壁を越える', 'special', 'self', 40, 1, 25),
('battering_ram_smash', '破城槌の粉砕', '🔨', '城門を破壊する衝撃', 'debuff', 'enemy', 90, 1, 15),
('carrier_launch', '空母の艦載機発進', '🛫', '艦載機で広範囲攻撃', 'special', 'enemy', 40, 1, 20),
('bomber_payload', '爆撃機の爆撃', '💣', '大型爆弾で敵を焼き払う', 'special', 'enemy', 50, 1, 20),

-- 艦船系ユニークスキル
('galleon_broadside', 'ガレオン船の舷側砲', '⛵', '舷側砲の一斉射撃', 'special', 'enemy', 35, 1, 25),
('frigate_maneuver', 'フリゲート艦の機動', '⚓', '素早い機動で回避', 'buff', 'self', 35, 2, 25),
('ironclad_ram', '装甲艦の体当たり', '🚢', '装甲艦の体当たり攻撃', 'special', 'enemy', 45, 1, 20);

-- 兵種にユニークスキルを割り当てる
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'hunter_trap' LIMIT 1) WHERE troop_key = 'hunter';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'warrior_fury' LIMIT 1) WHERE troop_key = 'warrior';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'spear_thrust' LIMIT 1) WHERE troop_key = 'spearman';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'chariot_rush' LIMIT 1) WHERE troop_key = 'chariot';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'sword_dance' LIMIT 1) WHERE troop_key = 'swordsman';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'cavalry_charge' LIMIT 1) WHERE troop_key = 'cavalry';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'archer_volley' LIMIT 1) WHERE troop_key = 'archer';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'knight_honor' LIMIT 1) WHERE troop_key = 'knight';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'crossbow_pierce' LIMIT 1) WHERE troop_key = 'crossbowman';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'catapult_siege' LIMIT 1) WHERE troop_key = 'catapult';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'musket_smoke' LIMIT 1) WHERE troop_key = 'musketeer';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'cannon_blast' LIMIT 1) WHERE troop_key = 'cannon';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'galleon_broadside' LIMIT 1) WHERE troop_key = 'galleon';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'militia_resolve' LIMIT 1) WHERE troop_key = 'infantry';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'artillery_barrage' LIMIT 1) WHERE troop_key = 'artillery';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'ironclad_ram' LIMIT 1) WHERE troop_key = 'ironclad';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'tank_armor' LIMIT 1) WHERE troop_key = 'tank';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'fighter_dogfight' LIMIT 1) WHERE troop_key = 'fighter';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'bomber_payload' LIMIT 1) WHERE troop_key = 'bomber';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'submarine_torpedo' LIMIT 1) WHERE troop_key = 'submarine';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'scout_evasion' LIMIT 1) WHERE troop_key = 'scout';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'phalanx_wall' LIMIT 1) WHERE troop_key = 'phalanx';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'pike_formation' LIMIT 1) WHERE troop_key = 'pikeman';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'longbow_range' LIMIT 1) WHERE troop_key = 'longbowman';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'trebuchet_launch' LIMIT 1) WHERE troop_key = 'trebuchet';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'elephant_stomp' LIMIT 1) WHERE troop_key = 'war_elephant';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'rifleman_aim' LIMIT 1) WHERE troop_key = 'rifleman';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'dragoon_fire' LIMIT 1) WHERE troop_key = 'dragoon';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'frigate_maneuver' LIMIT 1) WHERE troop_key = 'frigate';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'marine_assault' LIMIT 1) WHERE troop_key = 'marine';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'airborne_drop' LIMIT 1) WHERE troop_key = 'paratroopers';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'elite_tactics' LIMIT 1) WHERE troop_key = 'special_forces';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'missile_strike' LIMIT 1) WHERE troop_key = 'missile_launcher';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'stealth_ambush' LIMIT 1) WHERE troop_key = 'stealth_fighter';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'carrier_launch' LIMIT 1) WHERE troop_key = 'aircraft_carrier';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'nuclear_sub_launch' LIMIT 1) WHERE troop_key = 'nuclear_submarine';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'medic_care' LIMIT 1) WHERE troop_key = 'medic';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'surgeon_skill' LIMIT 1) WHERE troop_key = 'field_surgeon';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'siege_tower_climb' LIMIT 1) WHERE troop_key = 'siege_tower';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'battering_ram_smash' LIMIT 1) WHERE troop_key = 'battering_ram';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'royal_command' LIMIT 1) WHERE troop_key = 'royal_guard';
UPDATE civilization_troop_types SET special_skill_id = (SELECT id FROM battle_special_skills WHERE skill_key = 'berserk_rage' LIMIT 1) WHERE troop_key = 'berserker';

-- ===============================================
-- ② イベントシステム用テーブル
-- ===============================================

-- イベントマスターテーブル
CREATE TABLE IF NOT EXISTS civilization_events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_key VARCHAR(50) NOT NULL UNIQUE,
    event_type ENUM('daily', 'special', 'hero') NOT NULL COMMENT 'イベント種類',
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(20) NOT NULL DEFAULT '🎉',
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    config JSON COMMENT 'イベント設定（報酬率、ドロップアイテム等）',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type (event_type),
    INDEX idx_dates (start_date, end_date),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='イベントマスター';

-- デイリータスクテーブル
CREATE TABLE IF NOT EXISTS civilization_daily_tasks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    task_key VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(20) NOT NULL DEFAULT '📋',
    task_type VARCHAR(50) NOT NULL COMMENT 'タスク種類（post, battle, collect等）',
    target_count INT UNSIGNED NOT NULL DEFAULT 1,
    reward_coins INT UNSIGNED NOT NULL DEFAULT 0,
    reward_crystals INT UNSIGNED NOT NULL DEFAULT 0,
    reward_diamonds INT UNSIGNED NOT NULL DEFAULT 0,
    reward_resources JSON COMMENT '資源報酬',
    reward_exp INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '経験値報酬',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='デイリータスクマスター';

-- ユーザーデイリータスク進捗
CREATE TABLE IF NOT EXISTS user_daily_task_progress (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    task_id INT UNSIGNED NOT NULL,
    task_date DATE NOT NULL COMMENT 'タスク日付',
    current_progress INT UNSIGNED NOT NULL DEFAULT 0,
    is_completed BOOLEAN NOT NULL DEFAULT FALSE,
    is_claimed BOOLEAN NOT NULL DEFAULT FALSE,
    completed_at DATETIME NULL,
    claimed_at DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES civilization_daily_tasks(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_task_date (user_id, task_id, task_date),
    INDEX idx_user_date (user_id, task_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='ユーザーデイリータスク進捗';

-- スペシャルイベント限定アイテム
CREATE TABLE IF NOT EXISTS special_event_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    item_key VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(20) NOT NULL,
    description TEXT,
    rarity ENUM('common', 'uncommon', 'rare', 'epic', 'legendary') NOT NULL DEFAULT 'common',
    drop_rate DECIMAL(5,2) NOT NULL DEFAULT 10.00 COMMENT 'ドロップ率（%）',
    FOREIGN KEY (event_id) REFERENCES civilization_events(id) ON DELETE CASCADE,
    INDEX idx_event (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='スペシャルイベント限定アイテム';

-- ユーザー限定アイテム所持
CREATE TABLE IF NOT EXISTS user_special_event_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    item_id INT UNSIGNED NOT NULL,
    count INT UNSIGNED NOT NULL DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES special_event_items(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_item (user_id, item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='ユーザー限定アイテム所持';

-- イベント交換所
CREATE TABLE IF NOT EXISTS special_event_exchange (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    item_id INT UNSIGNED NOT NULL COMMENT '必要な限定アイテム',
    required_count INT UNSIGNED NOT NULL DEFAULT 1,
    reward_type ENUM('coins', 'crystals', 'diamonds', 'resource', 'hero_shards', 'equipment') NOT NULL,
    reward_amount INT UNSIGNED NOT NULL DEFAULT 0,
    reward_data JSON COMMENT '追加報酬データ',
    exchange_limit INT UNSIGNED DEFAULT NULL COMMENT '交換上限（NULL=無制限）',
    FOREIGN KEY (event_id) REFERENCES civilization_events(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES special_event_items(id) ON DELETE CASCADE,
    INDEX idx_event (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='イベント交換所';

-- ユーザー交換履歴
CREATE TABLE IF NOT EXISTS user_event_exchange_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    exchange_id INT UNSIGNED NOT NULL,
    exchanged_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (exchange_id) REFERENCES special_event_exchange(id) ON DELETE CASCADE,
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='ユーザー交換履歴';

-- ポータルボステーブル
CREATE TABLE IF NOT EXISTS special_event_portal_bosses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    boss_name VARCHAR(100) NOT NULL,
    boss_icon VARCHAR(20) NOT NULL,
    boss_description TEXT,
    boss_power INT UNSIGNED NOT NULL DEFAULT 1000,
    boss_hp INT UNSIGNED NOT NULL DEFAULT 10000,
    attack_interval_hours INT UNSIGNED NOT NULL DEFAULT 3 COMMENT '攻撃可能間隔（時間）',
    loot_table JSON COMMENT 'ドロップテーブル',
    FOREIGN KEY (event_id) REFERENCES civilization_events(id) ON DELETE CASCADE,
    INDEX idx_event (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='ポータルボス';

-- ユーザーポータルボス攻撃履歴
CREATE TABLE IF NOT EXISTS user_portal_boss_attacks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    boss_id INT UNSIGNED NOT NULL,
    damage_dealt INT UNSIGNED NOT NULL DEFAULT 0,
    loot_received JSON,
    attacked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (boss_id) REFERENCES special_event_portal_bosses(id) ON DELETE CASCADE,
    INDEX idx_user_boss (user_id, boss_id),
    INDEX idx_attacked (attacked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='ユーザーポータルボス攻撃履歴';

-- ヒーローイベントテーブル
CREATE TABLE IF NOT EXISTS hero_events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    featured_hero_id INT UNSIGNED NOT NULL COMMENT 'テーマヒーロー',
    bonus_shard_rate DECIMAL(5,2) NOT NULL DEFAULT 50.00 COMMENT '欠片排出率アップ（%）',
    gacha_discount_percent INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'ガチャ割引率',
    FOREIGN KEY (event_id) REFERENCES civilization_events(id) ON DELETE CASCADE,
    FOREIGN KEY (featured_hero_id) REFERENCES heroes(id) ON DELETE CASCADE,
    INDEX idx_event (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='ヒーローイベント';

-- ヒーローイベントタスク
CREATE TABLE IF NOT EXISTS hero_event_tasks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hero_event_id INT UNSIGNED NOT NULL,
    task_key VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(20) NOT NULL DEFAULT '⭐',
    task_type VARCHAR(50) NOT NULL,
    target_count INT UNSIGNED NOT NULL DEFAULT 1,
    points_reward INT UNSIGNED NOT NULL DEFAULT 10 COMMENT '獲得ポイント',
    FOREIGN KEY (hero_event_id) REFERENCES hero_events(id) ON DELETE CASCADE,
    INDEX idx_event (hero_event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='ヒーローイベントタスク';

-- ヒーローイベントポイント報酬
CREATE TABLE IF NOT EXISTS hero_event_point_rewards (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hero_event_id INT UNSIGNED NOT NULL,
    required_points INT UNSIGNED NOT NULL,
    reward_type ENUM('hero_shards', 'coins', 'crystals', 'diamonds', 'resource', 'equipment') NOT NULL,
    reward_amount INT UNSIGNED NOT NULL DEFAULT 0,
    reward_data JSON COMMENT '追加報酬データ',
    FOREIGN KEY (hero_event_id) REFERENCES hero_events(id) ON DELETE CASCADE,
    INDEX idx_event_points (hero_event_id, required_points)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='ヒーローイベントポイント報酬';

-- ユーザーヒーローイベント進捗
CREATE TABLE IF NOT EXISTS user_hero_event_progress (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    hero_event_id INT UNSIGNED NOT NULL,
    current_points INT UNSIGNED NOT NULL DEFAULT 0,
    claimed_rewards JSON COMMENT '受け取り済み報酬ID一覧',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (hero_event_id) REFERENCES hero_events(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_event (user_id, hero_event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='ユーザーヒーローイベント進捗';

-- ユーザーヒーローイベントタスク進捗
CREATE TABLE IF NOT EXISTS user_hero_event_task_progress (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    task_id INT UNSIGNED NOT NULL,
    current_progress INT UNSIGNED NOT NULL DEFAULT 0,
    is_completed BOOLEAN NOT NULL DEFAULT FALSE,
    is_claimed BOOLEAN NOT NULL DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES hero_event_tasks(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_task (user_id, task_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='ユーザーヒーローイベントタスク進捗';

-- ===============================================
-- ⑤ 市場交換制限テーブル
-- ===============================================

CREATE TABLE IF NOT EXISTS user_market_exchange_limits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    resource_type_id INT UNSIGNED NOT NULL,
    exchanged_amount INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'この時間に交換した量',
    reset_at DATETIME NOT NULL COMMENT '制限リセット時間',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (resource_type_id) REFERENCES civilization_resource_types(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_resource (user_id, resource_type_id),
    INDEX idx_reset (reset_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='ユーザー市場交換制限';

-- ===============================================
-- 初期デイリータスクデータ
-- ===============================================

INSERT IGNORE INTO civilization_daily_tasks (task_key, name, description, icon, task_type, target_count, reward_coins, reward_crystals, reward_diamonds, reward_exp) VALUES
('daily_login', '毎日ログイン', '文明育成ゲームにログインする', '🏠', 'login', 1, 100, 1, 0, 10),
('daily_collect', '資源収集', '資源を3回収集する', '📦', 'collect', 3, 200, 2, 0, 20),
('daily_build', '建設', '建物を1つ建設またはレベルアップする', '🏗️', 'build', 1, 300, 3, 0, 30),
('daily_train', '兵士訓練', '兵士を10体訓練する', '⚔️', 'train', 10, 250, 2, 0, 25),
('daily_battle', '戦闘参加', '戦闘に1回参加する（放浪モンスター/戦争/占領戦）', '🗡️', 'battle', 1, 400, 4, 0, 40),
('daily_invest', 'コイン投資', 'コインを1回投資する', '💰', 'invest', 1, 150, 1, 0, 15),
('daily_research', '研究', '研究を1つ開始または完了する', '📚', 'research', 1, 350, 3, 0, 35);

-- ===============================================
-- サンプルスペシャルイベントデータ（正月イベント）
-- ===============================================

INSERT IGNORE INTO civilization_events (event_key, event_type, name, description, icon, start_date, end_date, is_active, config) VALUES
('new_year_2026', 'special', '新春祭2026', '新年を祝う限定イベント！特別なボスを倒して限定アイテムを集めよう！', '🎍', '2026-01-01 00:00:00', '2026-01-31 23:59:59', TRUE, '{"bonus_drop_rate": 1.5, "special_boss_enabled": true}');

-- 限定アイテム
INSERT IGNORE INTO special_event_items (event_id, item_key, name, icon, description, rarity, drop_rate) VALUES
((SELECT id FROM civilization_events WHERE event_key = 'new_year_2026'), 'new_year_coin', '新春コイン', '🧧', '新年の幸運を象徴するコイン', 'common', 30.00),
((SELECT id FROM civilization_events WHERE event_key = 'new_year_2026'), 'lucky_charm', '幸運のお守り', '🎐', '幸福をもたらすお守り', 'uncommon', 15.00),
((SELECT id FROM civilization_events WHERE event_key = 'new_year_2026'), 'golden_dragon', '金龍の鱗', '🐉', '伝説の龍の鱗', 'rare', 5.00),
((SELECT id FROM civilization_events WHERE event_key = 'new_year_2026'), 'phoenix_feather', '鳳凰の羽', '🔥', '不死鳥の神秘的な羽', 'epic', 2.00);

-- ポータルボスを追加（新春イベント用）
INSERT IGNORE INTO special_event_portal_bosses (event_id, boss_name, boss_icon, boss_power, attack_interval_hours, loot_table) VALUES
((SELECT id FROM civilization_events WHERE event_key = 'new_year_2026'), '黄金龍王', '🐲', 500000, 3, '[{"item_id":1,"chance":50,"min_count":1,"max_count":3},{"item_id":2,"chance":30,"min_count":1,"max_count":2},{"item_id":3,"chance":15,"min_count":1,"max_count":1},{"item_id":4,"chance":5,"min_count":1,"max_count":1}]');

-- ヒーローイベントサンプル
INSERT IGNORE INTO civilization_events (event_key, event_type, name, description, icon, start_date, end_date, is_active, config) VALUES
('hero_event_jan_2026', 'hero', 'アイアンフォートレス週間', '鉄壁の守護者の欠片を集めよう！', '🛡️', '2026-01-01 00:00:00', '2026-01-07 23:59:59', TRUE, '{"featured_hero_id": 1}');

-- ヒーローイベント詳細
INSERT IGNORE INTO hero_events (event_id, featured_hero_id, bonus_shard_rate, gacha_discount_percent) VALUES
((SELECT id FROM civilization_events WHERE event_key = 'hero_event_jan_2026'), 1, 2.0, 20);

-- ヒーローイベントタスク
INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward) VALUES
((SELECT id FROM hero_events WHERE event_id = (SELECT id FROM civilization_events WHERE event_key = 'hero_event_jan_2026')), 'hero_login', 'イベント期間中にログイン', '毎日ログインしよう', '🏠', 'login', 1, 10),
((SELECT id FROM hero_events WHERE event_id = (SELECT id FROM civilization_events WHERE event_key = 'hero_event_jan_2026')), 'hero_battle', '戦闘に参加', '戦闘に3回参加しよう', '⚔️', 'battle', 3, 30),
((SELECT id FROM hero_events WHERE event_id = (SELECT id FROM civilization_events WHERE event_key = 'hero_event_jan_2026')), 'hero_gacha', 'ガチャを回す', 'ヒーローガチャを5回回そう', '🎰', 'gacha', 5, 50);

-- ヒーローイベントポイント報酬
INSERT IGNORE INTO hero_event_point_rewards (hero_event_id, required_points, reward_type, reward_amount) VALUES
((SELECT id FROM hero_events WHERE event_id = (SELECT id FROM civilization_events WHERE event_key = 'hero_event_jan_2026')), 20, 'coins', 1000),
((SELECT id FROM hero_events WHERE event_id = (SELECT id FROM civilization_events WHERE event_key = 'hero_event_jan_2026')), 50, 'crystals', 10),
((SELECT id FROM hero_events WHERE event_id = (SELECT id FROM civilization_events WHERE event_key = 'hero_event_jan_2026')), 100, 'hero_shards', 5);

-- ===============================================
-- 完了メッセージ
-- ===============================================

SELECT 'Feature additions 2024 schema created successfully' AS status;
