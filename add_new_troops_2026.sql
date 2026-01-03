-- ===============================================
-- 新規兵種追加 2026 - 文明育成ゲーム拡張
-- ルネサンス、産業革命、現代、原子力時代、現代Ⅱ～Ⅴ、宇宙時代の新兵種
-- ===============================================

USE microblog;

-- ===============================================
-- ① 新スキルの追加
-- ===============================================

INSERT IGNORE INTO battle_special_skills (skill_key, name, icon, description, effect_type, effect_target, effect_value, duration_turns, activation_chance) VALUES
-- 対空掃射：相手に空カテゴリがいる場合自身の攻撃力40%アップ（発動率100%）
('anti_air_barrage', '対空掃射', '🎯', '相手に空カテゴリがいる場合自身の攻撃力40%アップ', 'buff', 'self', 40, 99, 100),

-- 戦車駆逐：相手に陸カテゴリかつ騎兵カテゴリがいる場合、自身の攻撃力40%アップ（発動率100%）
('tank_destroyer', '戦車駆逐', '🎖️', '相手に陸カテゴリかつ騎兵カテゴリがいる場合、自身の攻撃力40%アップ', 'buff', 'self', 40, 99, 100),

-- 精密射撃：クリティカル率上昇（既存のcriticalと類似だが別バージョン）
('precision_shot', '精密射撃', '🔭', 'クリティカル率が大幅上昇', 'buff', 'self', 60, 2, 25),

-- 血の渇望：攻撃力上昇（攻撃的）
('bloodlust', '血の渇望', '🩸', '攻撃力を40%上昇させる', 'buff', 'self', 40, 3, 30),

-- 恐怖：敵の攻撃力と防御力を低下
('fear', '恐怖', '😱', '敵に恐怖を与え、攻撃力と防御力を30%低下させる', 'debuff', 'enemy', 30, 3, 25),

-- 鎧砕き：敵の防御力を大幅に低下
('armor_crush', '鎧砕き', '🔨', '敵の防御力を60%低下させる', 'debuff', 'enemy', 60, 2, 30),

-- 武装解除：敵の攻撃力を大幅に低下
('disarm', '武装解除', '🚫', '敵の武装を解除し、攻撃力を50%低下させる', 'debuff', 'enemy', 50, 2, 25),

-- 弱体化：敵の全ステータスを低下
('weaken', '弱体化', '💀', '敵の攻撃力と防御力を25%低下させる', 'debuff', 'enemy', 25, 2, 30),

-- 反撃：ダメージを受けた時に反撃
('counter', '反撃', '⚔️', 'ダメージを受けた時に30%の確率で反撃', 'special', 'self', 30, 99, 30),

-- 回避：攻撃を回避
('evasion', '回避', '💨', '35%の確率で敵の攻撃を回避', 'buff', 'self', 35, 99, 35),

-- 鼓舞：味方全体の攻撃力上昇
('inspire', '鼓舞', '📣', '味方全体の攻撃力を30%上昇させる', 'buff', 'self', 30, 3, 25),

-- 防御陣形：防御力大幅上昇
('defense_formation', '防御陣形', '🛡️', '防御陣形を取り、防御力を40%上昇させる', 'buff', 'self', 40, 3, 30);

-- ===============================================
-- ② ルネサンス時代（era_order = 5）の新兵種
-- ===============================================

INSERT IGNORE INTO civilization_troop_types (
    troop_key, name, icon, description, unlock_era_id, 
    attack_power, defense_power, health_points, troop_category, domain_category,
    train_cost_coins, train_cost_resources, train_time_seconds,
    heal_time_seconds, heal_cost_coins, special_skill_id
) VALUES
-- 装甲車：陸・騎兵・アーマー硬化
('armored_car', '装甲車', '🚙', '初期の装甲車両。アーマー硬化スキルで防御力を高める【陸・騎兵】', 
    5, 80, 90, 180, 'cavalry', 'land',
    4000, '{"iron": 150, "wood": 50}', 1200,
    60, 40, (SELECT id FROM battle_special_skills WHERE skill_key = 'armor_harden' LIMIT 1)),

-- 非常召集兵：陸・歩兵・回復
('emergency_conscript', '非常召集兵', '👥', '緊急時に召集される兵士。回復スキルで生存率を高める【陸・歩兵】',
    5, 60, 50, 120, 'infantry', 'land',
    2500, '{"food": 100, "wood": 30}', 800,
    40, 25, (SELECT id FROM battle_special_skills WHERE skill_key = 'heal' LIMIT 1)),

-- レンジャー歩兵：陸・攻城・加速
('ranger_infantry', 'レンジャー歩兵', '🎯', '機動力に優れた攻城部隊。加速スキルで連続攻撃【陸・攻城】',
    5, 70, 45, 110, 'siege', 'land',
    3500, '{"food": 80, "iron": 50, "wood": 40}', 1000,
    50, 30, (SELECT id FROM battle_special_skills WHERE skill_key = 'acceleration' LIMIT 1));

-- ===============================================
-- ③ 産業革命時代（era_order = 6）の新兵種
-- ===============================================

INSERT IGNORE INTO civilization_troop_types (
    troop_key, name, icon, description, unlock_era_id, 
    attack_power, defense_power, health_points, troop_category, domain_category,
    train_cost_coins, train_cost_resources, train_time_seconds,
    heal_time_seconds, heal_cost_coins, special_skill_id
) VALUES
-- 機械化歩兵：陸・歩兵・攻撃力上昇
('mechanized_infantry', '機械化歩兵', '🦾', '機械装備で強化された歩兵。攻撃力上昇スキル【陸・歩兵】',
    6, 110, 80, 200, 'infantry', 'land',
    8000, '{"food": 120, "iron": 100, "oil": 30}', 1800,
    80, 60, (SELECT id FROM battle_special_skills WHERE skill_key = 'attack_up' LIMIT 1)),

-- 奇襲隊：陸・歩兵・無防備
('raid_squad', '奇襲隊', '💥', '敵の防御を崩す奇襲部隊。無防備スキルで敵の防御力を低下【陸・歩兵】',
    6, 100, 60, 160, 'infantry', 'land',
    7000, '{"food": 100, "iron": 80, "gunpowder": 20}', 1600,
    70, 50, (SELECT id FROM battle_special_skills WHERE skill_key = 'vulnerable' LIMIT 1)),

-- 自走砲車：陸・騎兵・攻撃低下
('self_propelled_artillery', '自走砲車', '🚚', '移動可能な砲台。攻撃低下スキルで敵を弱体化【陸・騎兵】',
    6, 130, 70, 180, 'cavalry', 'land',
    9000, '{"iron": 200, "oil": 50}', 2100,
    90, 65, (SELECT id FROM battle_special_skills WHERE skill_key = 'attack_down' LIMIT 1)),

-- 重戦車：陸・騎兵・スタン
('heavy_tank', '重戦車', '🛡️', '重装甲の戦車。スタンスキルで敵を行動不能に【陸・騎兵】',
    6, 150, 120, 350, 'cavalry', 'land',
    12000, '{"iron": 300, "oil": 80, "steel": 50}', 2700,
    110, 80, (SELECT id FROM battle_special_skills WHERE skill_key = 'stun' LIMIT 1)),

-- 駆逐戦車：陸・騎兵・加速
('tank_destroyer_unit', '駆逐戦車', '⚡', '機動力に優れた対戦車車両。加速スキルで連続攻撃【陸・騎兵】',
    6, 140, 90, 280, 'cavalry', 'land',
    10000, '{"iron": 250, "oil": 60}', 2400,
    100, 70, (SELECT id FROM battle_special_skills WHERE skill_key = 'acceleration' LIMIT 1)),

-- 駆逐艦：海・騎兵・クリティカル
('destroyer_ship', '駆逐艦', '🚢', '高速艦船。クリティカルスキルで致命打【海・騎兵】',
    6, 120, 80, 250, 'cavalry', 'sea',
    11000, '{"iron": 280, "oil": 70, "steel": 40}', 2500,
    105, 75, (SELECT id FROM battle_special_skills WHERE skill_key = 'critical' LIMIT 1)),

-- 輸送機：超低コスト・空・遠距離・防御陣形・ステータス低め
('transport_plane', '輸送機', '✈️', '【超低コスト】物資輸送用航空機。防御陣形で生存率向上【空・遠距離】',
    6, 30, 20, 80, 'ranged', 'air',
    1500, '{"iron": 40, "oil": 20}', 600,
    30, 15, (SELECT id FROM battle_special_skills WHERE skill_key = 'defense_formation' LIMIT 1)),

-- 輸送船：超低コスト・海・歩兵・防御陣形・ステータス低め
('transport_ship', '輸送船', '⛴️', '【超低コスト】物資輸送用船舶。防御陣形で生存率向上【海・歩兵】',
    6, 25, 25, 100, 'infantry', 'sea',
    1200, '{"wood": 50, "iron": 30}', 500,
    25, 12, (SELECT id FROM battle_special_skills WHERE skill_key = 'defense_formation' LIMIT 1)),

-- 輸送車：超低コスト・陸・騎兵・防御陣形・ステータス低め
('transport_vehicle', '輸送車', '🚛', '【超低コスト】物資輸送用車両。防御陣形で生存率向上【陸・騎兵】',
    6, 20, 30, 90, 'cavalry', 'land',
    1000, '{"iron": 25, "oil": 15}', 400,
    20, 10, (SELECT id FROM battle_special_skills WHERE skill_key = 'defense_formation' LIMIT 1));

-- ===============================================
-- ④ 現代時代（era_order = 7）の新兵種
-- ===============================================

INSERT IGNORE INTO civilization_troop_types (
    troop_key, name, icon, description, unlock_era_id, 
    attack_power, defense_power, health_points, troop_category, domain_category,
    train_cost_coins, train_cost_resources, train_time_seconds,
    heal_time_seconds, heal_cost_coins, special_skill_id
) VALUES
-- 対空ミサイル：陸・攻城・対空掃射（発動率100%）
('anti_air_missile', '対空ミサイル', '🚀', '対空専用ミサイル。空カテゴリに対して攻撃力40%アップ【陸・攻城】',
    7, 140, 50, 150, 'siege', 'land',
    15000, '{"iron": 300, "oil": 100, "electronics": 50}', 3000,
    120, 90, (SELECT id FROM battle_special_skills WHERE skill_key = 'anti_air_barrage' LIMIT 1)),

-- 対戦車砲兵：陸・攻城・戦車駆逐（発動率100%）
('anti_tank_artillery', '対戦車砲兵', '💣', '対戦車専用砲。陸騎兵に対して攻撃力40%アップ【陸・攻城】',
    7, 150, 60, 160, 'siege', 'land',
    16000, '{"iron": 320, "gunpowder": 80}', 3200,
    130, 95, (SELECT id FROM battle_special_skills WHERE skill_key = 'tank_destroyer' LIMIT 1)),

-- 火炎放射戦車：陸・騎兵・燃焼
('flamethrower_tank', '火炎放射戦車', '🔥', '火炎放射器を搭載した戦車。燃焼スキルで継続ダメージ【陸・騎兵】',
    7, 160, 100, 300, 'cavalry', 'land',
    18000, '{"iron": 350, "oil": 120}', 3600,
    140, 100, (SELECT id FROM battle_special_skills WHERE skill_key = 'burn' LIMIT 1)),

-- 迎撃機：空・遠距離・精密射撃
('interceptor', '迎撃機', '✈️', '敵機迎撃専用戦闘機。精密射撃スキル【空・遠距離】',
    7, 180, 70, 200, 'ranged', 'air',
    20000, '{"iron": 400, "oil": 150, "electronics": 60}', 4000,
    150, 110, (SELECT id FROM battle_special_skills WHERE skill_key = 'precision_shot' LIMIT 1)),

-- 海軍爆撃機：空・攻城・血の渇望
('naval_bomber', '海軍爆撃機', '🛩️', '海上目標を攻撃する爆撃機。血の渇望スキルで攻撃力上昇【空・攻城】',
    7, 200, 60, 180, 'siege', 'air',
    22000, '{"iron": 450, "oil": 180}', 4400,
    160, 120, (SELECT id FROM battle_special_skills WHERE skill_key = 'bloodlust' LIMIT 1)),

-- 偵察機：空・遠距離・恐怖
('reconnaissance_plane', '偵察機', '🛫', '敵を偵察し恐怖を与える。恐怖スキルで敵を弱体化【空・遠距離】',
    7, 100, 50, 140, 'ranged', 'air',
    12000, '{"iron": 250, "oil": 100}', 2400,
    100, 70, (SELECT id FROM battle_special_skills WHERE skill_key = 'fear' LIMIT 1)),

-- 戦艦：海・騎兵・防御破壊
('battleship', '戦艦', '⚓', '巨大な主力艦。防御破壊スキルで敵の防御を無視【海・騎兵】',
    7, 220, 150, 500, 'cavalry', 'sea',
    30000, '{"iron": 600, "steel": 150, "oil": 200}', 6000,
    200, 150, (SELECT id FROM battle_special_skills WHERE skill_key = 'defense_break' LIMIT 1)),

-- 列車砲：陸・遠距離・鎧砕き
('railway_gun', '列車砲', '🚂', '列車に搭載された巨大砲。鎧砕きスキルで防御力を大幅低下【陸・遠距離】',
    7, 240, 80, 250, 'ranged', 'land',
    28000, '{"iron": 550, "steel": 120}', 5600,
    180, 140, (SELECT id FROM battle_special_skills WHERE skill_key = 'armor_crush' LIMIT 1));

-- ===============================================
-- ⑤ 原子力時代（era_order = 8）の新兵種
-- ===============================================

INSERT IGNORE INTO civilization_troop_types (
    troop_key, name, icon, description, unlock_era_id, 
    attack_power, defense_power, health_points, troop_category, domain_category,
    train_cost_coins, train_cost_resources, train_time_seconds,
    heal_time_seconds, heal_cost_coins, special_skill_id
) VALUES
-- 核対応戦車：陸・騎兵・武装解除
('nuclear_resistant_tank', '核対応戦車', '☢️', '核環境でも活動可能な戦車。武装解除スキルで敵を弱体化【陸・騎兵】',
    8, 200, 140, 400, 'cavalry', 'land',
    35000, '{"iron": 700, "uranium": 50, "steel": 150}', 7000,
    220, 170, (SELECT id FROM battle_special_skills WHERE skill_key = 'disarm' LIMIT 1));

-- ===============================================
-- ⑥ 現代Ⅱ（era_order = 9）の新兵種
-- ===============================================

INSERT IGNORE INTO civilization_troop_types (
    troop_key, name, icon, description, unlock_era_id, 
    attack_power, defense_power, health_points, troop_category, domain_category,
    train_cost_coins, train_cost_resources, train_time_seconds,
    heal_time_seconds, heal_cost_coins, special_skill_id
) VALUES
-- 無人ドローン：空・遠距離・弱体化
('unmanned_drone', '無人ドローン', '🛸', '自律型無人航空機。弱体化スキルで敵を弱体化【空・遠距離】',
    9, 150, 60, 180, 'ranged', 'air',
    25000, '{"silicon": 150, "ai_core": 10, "iron": 200}', 5000,
    160, 130, (SELECT id FROM battle_special_skills WHERE skill_key = 'weaken' LIMIT 1));

-- ===============================================
-- ⑦ 現代Ⅲ（era_order = 10）の新兵種
-- ===============================================

INSERT IGNORE INTO civilization_troop_types (
    troop_key, name, icon, description, unlock_era_id, 
    attack_power, defense_power, health_points, troop_category, domain_category,
    train_cost_coins, train_cost_resources, train_time_seconds,
    heal_time_seconds, heal_cost_coins, special_skill_id
) VALUES
-- 無人攻撃機：空・遠距離・恐怖
('unmanned_attack_aircraft', '無人攻撃機', '🛩️', '攻撃型無人機。恐怖スキルで敵を恐怖に陥れる【空・遠距離】',
    10, 180, 70, 200, 'ranged', 'air',
    30000, '{"silicon": 200, "rare_earth": 50, "ai_core": 15}', 6000,
    180, 140, (SELECT id FROM battle_special_skills WHERE skill_key = 'fear' LIMIT 1));

-- ===============================================
-- ⑧ 量子革命時代（era_order = 11）の新兵種
-- ===============================================

INSERT IGNORE INTO civilization_troop_types (
    troop_key, name, icon, description, unlock_era_id, 
    attack_power, defense_power, health_points, troop_category, domain_category,
    train_cost_coins, train_cost_resources, train_time_seconds,
    heal_time_seconds, heal_cost_coins, special_skill_id
) VALUES
-- 量子統兵：陸・歩兵・反撃
('quantum_unified_soldier', '量子統兵', '⚛️', '量子技術で強化された兵士。反撃スキルで反撃【陸・歩兵】',
    11, 250, 180, 450, 'infantry', 'land',
    50000, '{"quantum_crystal": 100, "iron": 500, "ai_core": 20}', 10000,
    250, 200, (SELECT id FROM battle_special_skills WHERE skill_key = 'counter' LIMIT 1)),

-- 量子戦艦：海・騎兵・回避
('quantum_battleship', '量子戦艦', '🌊', '量子技術搭載の最新戦艦。回避スキルで攻撃を回避【海・騎兵】',
    11, 280, 200, 600, 'cavalry', 'sea',
    60000, '{"quantum_crystal": 120, "iron": 800, "steel": 200}', 12000,
    280, 220, (SELECT id FROM battle_special_skills WHERE skill_key = 'evasion' LIMIT 1));

-- ===============================================
-- ⑨ 現代Ⅴ（era_order = 13）の新兵種
-- ===============================================

INSERT IGNORE INTO civilization_troop_types (
    troop_key, name, icon, description, unlock_era_id, 
    attack_power, defense_power, health_points, troop_category, domain_category,
    train_cost_coins, train_cost_resources, train_time_seconds,
    heal_time_seconds, heal_cost_coins, special_skill_id
) VALUES
-- 生成軍隊：陸・歩兵・鼓舞
('generated_army', '生成軍隊', '🧬', '生成技術で作られた軍隊。鼓舞スキルで味方を強化【陸・歩兵】',
    13, 300, 220, 500, 'infantry', 'land',
    70000, '{"gene_sample": 150, "ai_core": 30, "iron": 600}', 14000,
    300, 240, (SELECT id FROM battle_special_skills WHERE skill_key = 'inspire' LIMIT 1));

-- ===============================================
-- ⑩ 宇宙時代（era_order = 14）の新兵種
-- ===============================================

INSERT IGNORE INTO civilization_troop_types (
    troop_key, name, icon, description, unlock_era_id, 
    attack_power, defense_power, health_points, troop_category, domain_category,
    train_cost_coins, train_cost_resources, train_time_seconds,
    heal_time_seconds, heal_cost_coins, special_skill_id
) VALUES
-- 宇宙戦車：陸・騎兵・出血
('space_tank', '宇宙戦車', '🚀', '宇宙空間でも活動できる戦車。出血スキルで継続ダメージ【陸・騎兵】',
    14, 350, 250, 700, 'cavalry', 'land',
    100000, '{"dark_matter": 100, "antimatter": 20, "iron": 1000}', 20000,
    350, 280, (SELECT id FROM battle_special_skills WHERE skill_key = 'bleed' LIMIT 1));

-- ===============================================
-- ⑪ 前提条件の設定
-- ===============================================

-- ルネサンス時代の兵種：航海術研究が前提
UPDATE civilization_troop_types 
SET prerequisite_research_id = (SELECT id FROM civilization_researches WHERE research_key = 'navigation' LIMIT 1)
WHERE troop_key IN ('armored_car', 'emergency_conscript', 'ranger_infantry') 
AND prerequisite_research_id IS NULL;

-- 産業革命時代の兵種：蒸気機関研究が前提
UPDATE civilization_troop_types 
SET prerequisite_research_id = (SELECT id FROM civilization_researches WHERE research_key = 'steam_power' LIMIT 1)
WHERE troop_key IN ('mechanized_infantry', 'raid_squad', 'self_propelled_artillery', 
                     'heavy_tank', 'tank_destroyer_unit', 'destroyer_ship',
                     'transport_plane', 'transport_ship', 'transport_vehicle') 
AND prerequisite_research_id IS NULL;

-- 現代時代の兵種：電気研究が前提
UPDATE civilization_troop_types 
SET prerequisite_research_id = (SELECT id FROM civilization_researches WHERE research_key = 'electricity' LIMIT 1)
WHERE troop_key IN ('anti_air_missile', 'anti_tank_artillery', 'flamethrower_tank',
                     'interceptor', 'naval_bomber', 'reconnaissance_plane',
                     'battleship', 'railway_gun') 
AND prerequisite_research_id IS NULL;

-- 原子力時代の兵種：核技術研究が前提
UPDATE civilization_troop_types 
SET prerequisite_research_id = (SELECT id FROM civilization_researches WHERE research_key = 'nuclear_fission' LIMIT 1)
WHERE troop_key = 'nuclear_resistant_tank' 
AND prerequisite_research_id IS NULL;

-- 現代Ⅱの兵種：半導体技術が前提
UPDATE civilization_troop_types 
SET prerequisite_research_id = (SELECT id FROM civilization_researches WHERE research_key = 'semiconductor_technology' LIMIT 1)
WHERE troop_key = 'unmanned_drone' 
AND prerequisite_research_id IS NULL;

-- 現代Ⅲの兵種：モバイルコンピューティングが前提
UPDATE civilization_troop_types 
SET prerequisite_research_id = (SELECT id FROM civilization_researches WHERE research_key = 'mobile_computing' LIMIT 1)
WHERE troop_key = 'unmanned_attack_aircraft' 
AND prerequisite_research_id IS NULL;

-- 量子革命時代の兵種：量子コンピューティングが前提
UPDATE civilization_troop_types 
SET prerequisite_research_id = (SELECT id FROM civilization_researches WHERE research_key = 'quantum_computing' LIMIT 1)
WHERE troop_key IN ('quantum_unified_soldier', 'quantum_battleship') 
AND prerequisite_research_id IS NULL;

-- 現代Ⅴの兵種：合成生物学が前提
UPDATE civilization_troop_types 
SET prerequisite_research_id = (SELECT id FROM civilization_researches WHERE research_key = 'synthetic_biology' LIMIT 1)
WHERE troop_key = 'generated_army' 
AND prerequisite_research_id IS NULL;

-- 宇宙時代の兵種：宇宙推進技術が前提
UPDATE civilization_troop_types 
SET prerequisite_research_id = (SELECT id FROM civilization_researches WHERE research_key = 'space_propulsion' LIMIT 1)
WHERE troop_key = 'space_tank' 
AND prerequisite_research_id IS NULL;

-- ===============================================
-- ⑫ 建物前提条件の設定
-- ===============================================

-- 空軍兵種は空軍基地が必要
UPDATE civilization_troop_types 
SET prerequisite_building_id = (SELECT id FROM civilization_building_types WHERE building_key = 'air_base' LIMIT 1)
WHERE troop_key IN ('transport_plane', 'interceptor', 'naval_bomber', 'reconnaissance_plane',
                     'unmanned_drone', 'unmanned_attack_aircraft')
AND prerequisite_building_id IS NULL;

-- 海軍兵種は造船所が必要
UPDATE civilization_troop_types 
SET prerequisite_building_id = (SELECT id FROM civilization_building_types WHERE building_key = 'naval_dock' LIMIT 1)
WHERE troop_key IN ('destroyer_ship', 'transport_ship', 'battleship', 'quantum_battleship')
AND prerequisite_building_id IS NULL;

-- 戦車系は軍事基地が必要
UPDATE civilization_troop_types 
SET prerequisite_building_id = (SELECT id FROM civilization_building_types WHERE building_key = 'military_base' LIMIT 1)
WHERE troop_key IN ('heavy_tank', 'tank_destroyer_unit', 'flamethrower_tank', 
                     'nuclear_resistant_tank', 'space_tank')
AND prerequisite_building_id IS NULL;

-- ===============================================
-- 完了メッセージ
-- ===============================================
SELECT 'New troops 2026 schema created successfully' AS status;
SELECT CONCAT('Added ', COUNT(*), ' new troop types') AS troops_added 
FROM civilization_troop_types 
WHERE troop_key IN (
    'armored_car', 'emergency_conscript', 'ranger_infantry',
    'mechanized_infantry', 'raid_squad', 'self_propelled_artillery',
    'heavy_tank', 'tank_destroyer_unit', 'destroyer_ship',
    'transport_plane', 'transport_ship', 'transport_vehicle',
    'anti_air_missile', 'anti_tank_artillery', 'flamethrower_tank',
    'interceptor', 'naval_bomber', 'reconnaissance_plane',
    'battleship', 'railway_gun', 'nuclear_resistant_tank',
    'unmanned_drone', 'unmanned_attack_aircraft',
    'quantum_unified_soldier', 'quantum_battleship',
    'generated_army', 'space_tank'
);
