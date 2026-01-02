-- ===============================================
-- ワールドボス ベテランと中間レベル追加
-- ===============================================

USE microblog;

-- ===============================================
-- 1. world_bossesテーブルにベテランラベルカラムを追加
-- ===============================================
-- 注: labelsカラムはVARCHAR型を使用しています。
-- 現在は単一ラベル「ベテラン」のみを使用しているため、シンプルなVARCHAR実装で十分です。
-- 将来的に複数ラベルが必要になった場合は、ジャンクションテーブルへの移行を検討してください。
ALTER TABLE world_bosses 
ADD COLUMN labels VARCHAR(255) NULL COMMENT 'ボスラベル（カンマ区切り、例: ベテラン）' AFTER description;

-- ===============================================
-- 2. 中間レベルのボス追加 (Lv5, 15, 25, 35, 45, 55, 65, 75, 85, 95)
-- ===============================================

-- Lv5: ゴブリン・ダガス (Lv10タイタンより弱い)
INSERT IGNORE INTO world_bosses (boss_key, name, icon, description, labels, boss_level, min_user_level, summon_cost_diamonds, base_health, base_attack, base_defense, time_limit_hours) VALUES
('goblin_dagas_lv5', 'ゴブリン・ダガス Lv5', '👺', '狡猾なゴブリンの首領', NULL, 5, 5, 5, 500000, 150, 50, 24);

-- Lv15: 悪魔・モルゲート (Lv10とLv20の間)
INSERT IGNORE INTO world_bosses (boss_key, name, icon, description, labels, boss_level, min_user_level, summon_cost_diamonds, base_health, base_attack, base_defense, time_limit_hours) VALUES
('demon_morgate_lv15', '悪魔・モルゲート Lv15', '😈', '地獄から這い出た悪魔', NULL, 15, 15, 17, 3000000, 375, 120, 24);

-- Lv25: アイガオン (Lv20とLv30の間)
INSERT IGNORE INTO world_bosses (boss_key, name, icon, description, labels, boss_level, min_user_level, summon_cost_diamonds, base_health, base_attack, base_defense, time_limit_hours) VALUES
('aigaon_lv25', 'アイガオン Lv25', '🦖', '雷を操る古代の巨獣', NULL, 25, 25, 37, 10000000, 625, 195, 24);

-- Lv35: アンデバブ (Lv30とLv40の間)
INSERT IGNORE INTO world_bosses (boss_key, name, icon, description, labels, boss_level, min_user_level, summon_cost_diamonds, base_health, base_attack, base_defense, time_limit_hours) VALUES
('undebab_lv35', 'アンデバブ Lv35', '👻', '死者の軍団を率いる亡霊王', NULL, 35, 35, 75, 32500000, 875, 270, 24);

-- Lv45: バクスト (Lv40とLv50の間)
INSERT IGNORE INTO world_bosses (boss_key, name, icon, description, labels, boss_level, min_user_level, summon_cost_diamonds, base_health, base_attack, base_defense, time_limit_hours) VALUES
('bakust_lv45', 'バクスト Lv45', '🐗', '荒野を駆ける破壊の獣', NULL, 45, 45, 150, 100000000, 1250, 375, 24);

-- Lv55: オセアノの蛇 (Lv50とLv60の間)
INSERT IGNORE INTO world_bosses (boss_key, name, icon, description, labels, boss_level, min_user_level, summon_cost_diamonds, base_health, base_attack, base_defense, time_limit_hours) VALUES
('oceano_serpent_lv55', 'オセアノの蛇 Lv55', '🐍', '大海を支配する巨大な蛇', NULL, 55, 55, 300, 325000000, 2000, 525, 24);

-- Lv65: トロールの化身 (Lv60とLv70の間)
INSERT IGNORE INTO world_bosses (boss_key, name, icon, description, labels, boss_level, min_user_level, summon_cost_diamonds, base_health, base_attack, base_defense, time_limit_hours) VALUES
('troll_avatar_lv65', 'トロールの化身 Lv65', '🧌', '伝説のトロール王の姿を現した存在', NULL, 65, 65, 500, 750000000, 3000, 750, 24);

-- Lv75: コンデット (Lv70とLv80の間)
INSERT IGNORE INTO world_bosses (boss_key, name, icon, description, labels, boss_level, min_user_level, summon_cost_diamonds, base_health, base_attack, base_defense, time_limit_hours) VALUES
('condett_lv75', 'コンデット Lv75', '⚡', '雷神の化身、天空の支配者', NULL, 75, 75, 800, 2000000000, 4250, 1200, 24);

-- Lv85: ビースト軍団 (Lv80とLv90の間)
INSERT IGNORE INTO world_bosses (boss_key, name, icon, description, labels, boss_level, min_user_level, summon_cost_diamonds, base_health, base_attack, base_defense, time_limit_hours) VALUES
('beast_legion_lv85', 'ビースト軍団 Lv85', '🦁', '無数の魔獣が集結した軍団', NULL, 85, 85, 1500, 6500000000, 6250, 1950, 24);

-- Lv95: エピックゴーレム (Lv90とLv100の間)
INSERT IGNORE INTO world_bosses (boss_key, name, icon, description, labels, boss_level, min_user_level, summon_cost_diamonds, base_health, base_attack, base_defense, time_limit_hours) VALUES
('epic_golem_lv95', 'エピックゴーレム Lv95', '🗿', '古代文明の最終兵器', NULL, 95, 95, 3500, 30000000000, 11250, 3450, 24);

-- ===============================================
-- 3. ベテランボス追加 (Lv10, 20, 30, 40, 50, 60, 70, 80, 90, 100)
-- 召喚コスト2倍、体力5倍、名前に(ベテラン)を追加
-- ===============================================

-- Lv10 ベテラン
INSERT IGNORE INTO world_bosses (boss_key, name, icon, description, labels, boss_level, min_user_level, summon_cost_diamonds, base_health, base_attack, base_defense, time_limit_hours) VALUES
('titan_lv10_veteran', '巨神タイタン Lv10 (ベテラン)', '🦾', '大地を揺るがす巨大な巨人（ベテラン）', 'ベテラン', 10, 10, 20, 5000000, 250, 90, 24);

-- Lv20 ベテラン
INSERT IGNORE INTO world_bosses (boss_key, name, icon, description, labels, boss_level, min_user_level, summon_cost_diamonds, base_health, base_attack, base_defense, time_limit_hours) VALUES
('hydra_lv20_veteran', '九頭竜ヒュドラ Lv20 (ベテラン)', '🐍', '無数の首を持つ恐るべき蛇竜（ベテラン）', 'ベテラン', 20, 20, 50, 25000000, 500, 150, 24);

-- Lv30 ベテラン
INSERT IGNORE INTO world_bosses (boss_key, name, icon, description, labels, boss_level, min_user_level, summon_cost_diamonds, base_health, base_attack, base_defense, time_limit_hours) VALUES
('phoenix_lv30_veteran', '不死鳥フェニックス Lv30 (ベテラン)', '🔥', '炎の中から蘇る不死の鳥（ベテラン）', 'ベテラン', 30, 30, 100, 75000000, 750, 240, 24);

-- Lv40 ベテラン
INSERT IGNORE INTO world_bosses (boss_key, name, icon, description, labels, boss_level, min_user_level, summon_cost_diamonds, base_health, base_attack, base_defense, time_limit_hours) VALUES
('kraken_lv40_veteran', '海魔クラーケン Lv40 (ベテラン)', '🦑', '深海から現れる巨大なイカ（ベテラン）', 'ベテラン', 40, 40, 200, 250000000, 1000, 300, 24);

-- Lv50 ベテラン
INSERT IGNORE INTO world_bosses (boss_key, name, icon, description, labels, boss_level, min_user_level, summon_cost_diamonds, base_health, base_attack, base_defense, time_limit_hours) VALUES
('behemoth_lv50_veteran', '魔獣ベヒモス Lv50 (ベテラン)', '🦏', '世界を破壊する伝説の獣（ベテラン）', 'ベテラン', 50, 50, 400, 750000000, 1500, 450, 24);

-- Lv60 ベテラン
INSERT IGNORE INTO world_bosses (boss_key, name, icon, description, labels, boss_level, min_user_level, summon_cost_diamonds, base_health, base_attack, base_defense, time_limit_hours) VALUES
('chaos_dragon_lv60_veteran', '混沌龍カオス Lv60 (ベテラン)', '🐉', '混沌をもたらす最強のドラゴン（ベテラン）', 'ベテラン', 60, 60, 800, 2500000000, 2500, 600, 24);

-- Lv70 ベテラン
INSERT IGNORE INTO world_bosses (boss_key, name, icon, description, labels, boss_level, min_user_level, summon_cost_diamonds, base_health, base_attack, base_defense, time_limit_hours) VALUES
('god_of_war_lv70_veteran', '戦神マルス Lv70 (ベテラン)', '⚔️', '戦争を司る神（ベテラン）', 'ベテラン', 70, 70, 1200, 5000000000, 3500, 900, 24);

-- Lv80 ベテラン
INSERT IGNORE INTO world_bosses (boss_key, name, icon, description, labels, boss_level, min_user_level, summon_cost_diamonds, base_health, base_attack, base_defense, time_limit_hours) VALUES
('world_eater_lv80_veteran', '世界喰いジョルムンガンド Lv80 (ベテラン)', '🌍', '世界を飲み込む巨大な蛇（ベテラン）', 'ベテラン', 80, 80, 2000, 15000000000, 5000, 1500, 24);

-- Lv90 ベテラン
INSERT IGNORE INTO world_bosses (boss_key, name, icon, description, labels, boss_level, min_user_level, summon_cost_diamonds, base_health, base_attack, base_defense, time_limit_hours) VALUES
('void_lord_lv90_veteran', '虚無王 Lv90 (ベテラン)', '🌑', '虚無の次元から来た支配者（ベテラン）', 'ベテラン', 90, 90, 4000, 50000000000, 7500, 2400, 24);

-- Lv100 ベテラン
INSERT IGNORE INTO world_bosses (boss_key, name, icon, description, labels, boss_level, min_user_level, summon_cost_diamonds, base_health, base_attack, base_defense, time_limit_hours) VALUES
('cosmic_entity_lv100_veteran', '宇宙創造神 Lv100 (ベテラン)', '🌌', '宇宙を創造した超越的存在（ベテラン）', 'ベテラン', 100, 100, 10000, 250000000000, 15000, 4500, 24);

-- ===============================================
-- 4. 中間レベルボスの報酬設定
-- ===============================================

-- Lv5 ゴブリン・ダガスの報酬
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 5000, 50, 5, '[{"resource_type_id":1,"amount":100},{"resource_type_id":2,"amount":100}]', NULL FROM world_bosses WHERE boss_key = 'goblin_dagas_lv5';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 2500, 25, 2, '[{"resource_type_id":1,"amount":50},{"resource_type_id":2,"amount":50}]', NULL FROM world_bosses WHERE boss_key = 'goblin_dagas_lv5';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 1000, 10, 1, '[{"resource_type_id":1,"amount":20},{"resource_type_id":2,"amount":20}]', NULL FROM world_bosses WHERE boss_key = 'goblin_dagas_lv5';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 250, 2, 0, '[{"resource_type_id":1,"amount":10}]', NULL FROM world_bosses WHERE boss_key = 'goblin_dagas_lv5';

-- Lv15 悪魔・モルゲートの報酬
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 17500, 175, 17, '[{"resource_type_id":1,"amount":200},{"resource_type_id":2,"amount":200},{"resource_type_id":3,"amount":100}]', NULL FROM world_bosses WHERE boss_key = 'demon_morgate_lv15';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 8500, 85, 8, '[{"resource_type_id":1,"amount":100},{"resource_type_id":2,"amount":100},{"resource_type_id":3,"amount":50}]', NULL FROM world_bosses WHERE boss_key = 'demon_morgate_lv15';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 3500, 35, 3, '[{"resource_type_id":1,"amount":50},{"resource_type_id":2,"amount":50}]', NULL FROM world_bosses WHERE boss_key = 'demon_morgate_lv15';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 750, 7, 1, '[{"resource_type_id":1,"amount":20}]', NULL FROM world_bosses WHERE boss_key = 'demon_morgate_lv15';

-- Lv25 アイガオンの報酬
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 37500, 375, 37, '[{"resource_type_id":1,"amount":400},{"resource_type_id":2,"amount":400},{"resource_type_id":3,"amount":200},{"resource_type_id":4,"amount":100}]', NULL FROM world_bosses WHERE boss_key = 'aigaon_lv25';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 18500, 185, 18, '[{"resource_type_id":1,"amount":200},{"resource_type_id":2,"amount":200},{"resource_type_id":3,"amount":100}]', NULL FROM world_bosses WHERE boss_key = 'aigaon_lv25';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 7500, 75, 7, '[{"resource_type_id":1,"amount":100},{"resource_type_id":2,"amount":100}]', NULL FROM world_bosses WHERE boss_key = 'aigaon_lv25';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 1500, 15, 1, '[{"resource_type_id":1,"amount":40}]', NULL FROM world_bosses WHERE boss_key = 'aigaon_lv25';

-- Lv35 アンデバブの報酬
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 62500, 625, 62, '[{"resource_type_id":1,"amount":600},{"resource_type_id":2,"amount":600},{"resource_type_id":3,"amount":400},{"resource_type_id":4,"amount":200},{"resource_type_id":5,"amount":100}]', NULL FROM world_bosses WHERE boss_key = 'undebab_lv35';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 31000, 310, 31, '[{"resource_type_id":1,"amount":300},{"resource_type_id":2,"amount":300},{"resource_type_id":3,"amount":200}]', NULL FROM world_bosses WHERE boss_key = 'undebab_lv35';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 12500, 125, 12, '[{"resource_type_id":1,"amount":150},{"resource_type_id":2,"amount":150}]', NULL FROM world_bosses WHERE boss_key = 'undebab_lv35';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 2500, 25, 2, '[{"resource_type_id":1,"amount":60}]', NULL FROM world_bosses WHERE boss_key = 'undebab_lv35';

-- Lv45 バクストの報酬
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 125000, 1250, 125, '[{"resource_type_id":1,"amount":1000},{"resource_type_id":2,"amount":1000},{"resource_type_id":3,"amount":600},{"resource_type_id":4,"amount":400},{"resource_type_id":5,"amount":200}]', NULL FROM world_bosses WHERE boss_key = 'bakust_lv45';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 62500, 625, 62, '[{"resource_type_id":1,"amount":500},{"resource_type_id":2,"amount":500},{"resource_type_id":3,"amount":300}]', NULL FROM world_bosses WHERE boss_key = 'bakust_lv45';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 25000, 250, 25, '[{"resource_type_id":1,"amount":250},{"resource_type_id":2,"amount":250}]', NULL FROM world_bosses WHERE boss_key = 'bakust_lv45';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 5000, 50, 5, '[{"resource_type_id":1,"amount":100}]', NULL FROM world_bosses WHERE boss_key = 'bakust_lv45';

-- Lv55 オセアノの蛇の報酬
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 300000, 3000, 300, '[{"resource_type_id":1,"amount":1500},{"resource_type_id":2,"amount":1500},{"resource_type_id":3,"amount":1000},{"resource_type_id":4,"amount":700},{"resource_type_id":5,"amount":500},{"resource_type_id":6,"amount":300}]', NULL FROM world_bosses WHERE boss_key = 'oceano_serpent_lv55';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 150000, 1500, 150, '[{"resource_type_id":1,"amount":750},{"resource_type_id":2,"amount":750},{"resource_type_id":3,"amount":500}]', NULL FROM world_bosses WHERE boss_key = 'oceano_serpent_lv55';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 60000, 600, 60, '[{"resource_type_id":1,"amount":400},{"resource_type_id":2,"amount":400}]', NULL FROM world_bosses WHERE boss_key = 'oceano_serpent_lv55';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 12000, 120, 12, '[{"resource_type_id":1,"amount":150}]', NULL FROM world_bosses WHERE boss_key = 'oceano_serpent_lv55';

-- Lv65 トロールの化身の報酬
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 600000, 6000, 600, '[{"resource_type_id":1,"amount":2000},{"resource_type_id":2,"amount":2000},{"resource_type_id":3,"amount":1500},{"resource_type_id":4,"amount":1000},{"resource_type_id":5,"amount":700},{"resource_type_id":6,"amount":500}]', NULL FROM world_bosses WHERE boss_key = 'troll_avatar_lv65';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 300000, 3000, 300, '[{"resource_type_id":1,"amount":1000},{"resource_type_id":2,"amount":1000},{"resource_type_id":3,"amount":750}]', NULL FROM world_bosses WHERE boss_key = 'troll_avatar_lv65';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 120000, 1200, 120, '[{"resource_type_id":1,"amount":500},{"resource_type_id":2,"amount":500}]', NULL FROM world_bosses WHERE boss_key = 'troll_avatar_lv65';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 25000, 250, 25, '[{"resource_type_id":1,"amount":200}]', NULL FROM world_bosses WHERE boss_key = 'troll_avatar_lv65';

-- Lv75 コンデットの報酬
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 1200000, 12000, 1200, '[{"resource_type_id":1,"amount":3000},{"resource_type_id":2,"amount":3000},{"resource_type_id":3,"amount":2000},{"resource_type_id":4,"amount":1500},{"resource_type_id":5,"amount":1000},{"resource_type_id":6,"amount":700},{"resource_type_id":7,"amount":500}]', NULL FROM world_bosses WHERE boss_key = 'condett_lv75';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 600000, 6000, 600, '[{"resource_type_id":1,"amount":1500},{"resource_type_id":2,"amount":1500},{"resource_type_id":3,"amount":1000}]', NULL FROM world_bosses WHERE boss_key = 'condett_lv75';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 240000, 2400, 240, '[{"resource_type_id":1,"amount":750},{"resource_type_id":2,"amount":750}]', NULL FROM world_bosses WHERE boss_key = 'condett_lv75';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 50000, 500, 50, '[{"resource_type_id":1,"amount":300}]', NULL FROM world_bosses WHERE boss_key = 'condett_lv75';

-- Lv85 ビースト軍団の報酬
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 2750000, 27500, 2750, '[{"resource_type_id":1,"amount":4500},{"resource_type_id":2,"amount":4500},{"resource_type_id":3,"amount":3000},{"resource_type_id":4,"amount":2000},{"resource_type_id":5,"amount":1500},{"resource_type_id":6,"amount":1000},{"resource_type_id":7,"amount":700}]', NULL FROM world_bosses WHERE boss_key = 'beast_legion_lv85';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 1375000, 13750, 1375, '[{"resource_type_id":1,"amount":2250},{"resource_type_id":2,"amount":2250},{"resource_type_id":3,"amount":1500}]', NULL FROM world_bosses WHERE boss_key = 'beast_legion_lv85';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 550000, 5500, 550, '[{"resource_type_id":1,"amount":1125},{"resource_type_id":2,"amount":1125}]', NULL FROM world_bosses WHERE boss_key = 'beast_legion_lv85';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 112500, 1125, 112, '[{"resource_type_id":1,"amount":450}]', NULL FROM world_bosses WHERE boss_key = 'beast_legion_lv85';

-- Lv95 エピックゴーレムの報酬
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 3875000, 38750, 3875, '[{"resource_type_id":1,"amount":6000},{"resource_type_id":2,"amount":6000},{"resource_type_id":3,"amount":4000},{"resource_type_id":4,"amount":3000},{"resource_type_id":5,"amount":2000},{"resource_type_id":6,"amount":1500},{"resource_type_id":7,"amount":1000},{"resource_type_id":8,"amount":500}]', NULL FROM world_bosses WHERE boss_key = 'epic_golem_lv95';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 1937500, 19375, 1937, '[{"resource_type_id":1,"amount":3000},{"resource_type_id":2,"amount":3000},{"resource_type_id":3,"amount":2000}]', NULL FROM world_bosses WHERE boss_key = 'epic_golem_lv95';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 775000, 7750, 775, '[{"resource_type_id":1,"amount":1500},{"resource_type_id":2,"amount":1500}]', NULL FROM world_bosses WHERE boss_key = 'epic_golem_lv95';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 156250, 1562, 156, '[{"resource_type_id":1,"amount":600}]', NULL FROM world_bosses WHERE boss_key = 'epic_golem_lv95';

-- ===============================================
-- 5. ベテランボスの報酬設定
-- コイン2倍、クリスタルとダイヤモンドはそのまま、資源2倍
-- ===============================================

-- Lv10 ベテランの報酬 (元のLv10の報酬: コイン2倍、資源2倍)
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 20000, 100, 10, '[{"resource_type_id":1,"amount":200},{"resource_type_id":2,"amount":200}]', NULL FROM world_bosses WHERE boss_key = 'titan_lv10_veteran';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 10000, 50, 5, '[{"resource_type_id":1,"amount":100},{"resource_type_id":2,"amount":100}]', NULL FROM world_bosses WHERE boss_key = 'titan_lv10_veteran';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 4000, 20, 2, '[{"resource_type_id":1,"amount":40},{"resource_type_id":2,"amount":40}]', NULL FROM world_bosses WHERE boss_key = 'titan_lv10_veteran';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 1000, 5, 1, '[{"resource_type_id":1,"amount":20}]', NULL FROM world_bosses WHERE boss_key = 'titan_lv10_veteran';

-- Lv20 ベテランの報酬
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 50000, 250, 25, '[{"resource_type_id":1,"amount":500},{"resource_type_id":2,"amount":500},{"resource_type_id":3,"amount":200}]', NULL FROM world_bosses WHERE boss_key = 'hydra_lv20_veteran';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 24000, 120, 12, '[{"resource_type_id":1,"amount":250},{"resource_type_id":2,"amount":250},{"resource_type_id":3,"amount":100}]', NULL FROM world_bosses WHERE boss_key = 'hydra_lv20_veteran';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 10000, 50, 5, '[{"resource_type_id":1,"amount":100},{"resource_type_id":2,"amount":100}]', NULL FROM world_bosses WHERE boss_key = 'hydra_lv20_veteran';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 2000, 10, 2, '[{"resource_type_id":1,"amount":40}]', NULL FROM world_bosses WHERE boss_key = 'hydra_lv20_veteran';

-- Lv30 ベテランの報酬 (元の報酬データがないため推定)
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 100000, 1000, 100, '[{"resource_type_id":1,"amount":800},{"resource_type_id":2,"amount":800},{"resource_type_id":3,"amount":400},{"resource_type_id":4,"amount":200}]', NULL FROM world_bosses WHERE boss_key = 'phoenix_lv30_veteran';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 50000, 500, 50, '[{"resource_type_id":1,"amount":400},{"resource_type_id":2,"amount":400},{"resource_type_id":3,"amount":200}]', NULL FROM world_bosses WHERE boss_key = 'phoenix_lv30_veteran';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 20000, 200, 20, '[{"resource_type_id":1,"amount":200},{"resource_type_id":2,"amount":200}]', NULL FROM world_bosses WHERE boss_key = 'phoenix_lv30_veteran';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 4000, 40, 4, '[{"resource_type_id":1,"amount":80}]', NULL FROM world_bosses WHERE boss_key = 'phoenix_lv30_veteran';

-- Lv40 ベテランの報酬 (元の報酬データがないため推定)
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 150000, 1500, 150, '[{"resource_type_id":1,"amount":1200},{"resource_type_id":2,"amount":1200},{"resource_type_id":3,"amount":800},{"resource_type_id":4,"amount":400},{"resource_type_id":5,"amount":200}]', NULL FROM world_bosses WHERE boss_key = 'kraken_lv40_veteran';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 75000, 750, 75, '[{"resource_type_id":1,"amount":600},{"resource_type_id":2,"amount":600},{"resource_type_id":3,"amount":400}]', NULL FROM world_bosses WHERE boss_key = 'kraken_lv40_veteran';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 30000, 300, 30, '[{"resource_type_id":1,"amount":300},{"resource_type_id":2,"amount":300}]', NULL FROM world_bosses WHERE boss_key = 'kraken_lv40_veteran';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 6000, 60, 6, '[{"resource_type_id":1,"amount":120}]', NULL FROM world_bosses WHERE boss_key = 'kraken_lv40_veteran';

-- Lv50 ベテランの報酬
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 400000, 2000, 200, '[{"resource_type_id":1,"amount":2000},{"resource_type_id":2,"amount":2000},{"resource_type_id":3,"amount":1200},{"resource_type_id":4,"amount":800},{"resource_type_id":5,"amount":400},{"resource_type_id":6,"amount":200}]', NULL FROM world_bosses WHERE boss_key = 'behemoth_lv50_veteran';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 200000, 1000, 100, '[{"resource_type_id":1,"amount":1000},{"resource_type_id":2,"amount":1000},{"resource_type_id":3,"amount":600}]', NULL FROM world_bosses WHERE boss_key = 'behemoth_lv50_veteran';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 100000, 500, 50, '[{"resource_type_id":1,"amount":500},{"resource_type_id":2,"amount":500}]', NULL FROM world_bosses WHERE boss_key = 'behemoth_lv50_veteran';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 20000, 100, 10, '[{"resource_type_id":1,"amount":200}]', NULL FROM world_bosses WHERE boss_key = 'behemoth_lv50_veteran';

-- Lv60 ベテランの報酬 (元の報酬データがないため推定)
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 800000, 8000, 800, '[{"resource_type_id":1,"amount":3000},{"resource_type_id":2,"amount":3000},{"resource_type_id":3,"amount":2000},{"resource_type_id":4,"amount":1200},{"resource_type_id":5,"amount":800},{"resource_type_id":6,"amount":400}]', NULL FROM world_bosses WHERE boss_key = 'chaos_dragon_lv60_veteran';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 400000, 4000, 400, '[{"resource_type_id":1,"amount":1500},{"resource_type_id":2,"amount":1500},{"resource_type_id":3,"amount":1000}]', NULL FROM world_bosses WHERE boss_key = 'chaos_dragon_lv60_veteran';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 160000, 1600, 160, '[{"resource_type_id":1,"amount":750},{"resource_type_id":2,"amount":750}]', NULL FROM world_bosses WHERE boss_key = 'chaos_dragon_lv60_veteran';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 32000, 320, 32, '[{"resource_type_id":1,"amount":300}]', NULL FROM world_bosses WHERE boss_key = 'chaos_dragon_lv60_veteran';

-- Lv70 ベテランの報酬 (元の報酬データがないため推定)
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 1600000, 16000, 1600, '[{"resource_type_id":1,"amount":4000},{"resource_type_id":2,"amount":4000},{"resource_type_id":3,"amount":3000},{"resource_type_id":4,"amount":2000},{"resource_type_id":5,"amount":1200},{"resource_type_id":6,"amount":800},{"resource_type_id":7,"amount":400}]', NULL FROM world_bosses WHERE boss_key = 'god_of_war_lv70_veteran';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 800000, 8000, 800, '[{"resource_type_id":1,"amount":2000},{"resource_type_id":2,"amount":2000},{"resource_type_id":3,"amount":1500}]', NULL FROM world_bosses WHERE boss_key = 'god_of_war_lv70_veteran';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 320000, 3200, 320, '[{"resource_type_id":1,"amount":1000},{"resource_type_id":2,"amount":1000}]', NULL FROM world_bosses WHERE boss_key = 'god_of_war_lv70_veteran';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 64000, 640, 64, '[{"resource_type_id":1,"amount":400}]', NULL FROM world_bosses WHERE boss_key = 'god_of_war_lv70_veteran';

-- Lv80 ベテランの報酬 (元の報酬データがないため推定)
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 3200000, 32000, 3200, '[{"resource_type_id":1,"amount":6000},{"resource_type_id":2,"amount":6000},{"resource_type_id":3,"amount":4000},{"resource_type_id":4,"amount":3000},{"resource_type_id":5,"amount":2000},{"resource_type_id":6,"amount":1200},{"resource_type_id":7,"amount":800}]', NULL FROM world_bosses WHERE boss_key = 'world_eater_lv80_veteran';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 1600000, 16000, 1600, '[{"resource_type_id":1,"amount":3000},{"resource_type_id":2,"amount":3000},{"resource_type_id":3,"amount":2000}]', NULL FROM world_bosses WHERE boss_key = 'world_eater_lv80_veteran';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 640000, 6400, 640, '[{"resource_type_id":1,"amount":1500},{"resource_type_id":2,"amount":1500}]', NULL FROM world_bosses WHERE boss_key = 'world_eater_lv80_veteran';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 128000, 1280, 128, '[{"resource_type_id":1,"amount":600}]', NULL FROM world_bosses WHERE boss_key = 'world_eater_lv80_veteran';

-- Lv90 ベテランの報酬 (元の報酬データがないため推定)
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 6400000, 64000, 6400, '[{"resource_type_id":1,"amount":8000},{"resource_type_id":2,"amount":8000},{"resource_type_id":3,"amount":6000},{"resource_type_id":4,"amount":4000},{"resource_type_id":5,"amount":3000},{"resource_type_id":6,"amount":2000},{"resource_type_id":7,"amount":1200},{"resource_type_id":8,"amount":800}]', NULL FROM world_bosses WHERE boss_key = 'void_lord_lv90_veteran';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 3200000, 32000, 3200, '[{"resource_type_id":1,"amount":4000},{"resource_type_id":2,"amount":4000},{"resource_type_id":3,"amount":3000}]', NULL FROM world_bosses WHERE boss_key = 'void_lord_lv90_veteran';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 1280000, 12800, 1280, '[{"resource_type_id":1,"amount":2000},{"resource_type_id":2,"amount":2000}]', NULL FROM world_bosses WHERE boss_key = 'void_lord_lv90_veteran';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 256000, 2560, 256, '[{"resource_type_id":1,"amount":800}]', NULL FROM world_bosses WHERE boss_key = 'void_lord_lv90_veteran';

-- Lv100 ベテランの報酬
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 10000000, 50000, 5000, '[{"resource_type_id":1,"amount":10000},{"resource_type_id":2,"amount":10000},{"resource_type_id":3,"amount":8000},{"resource_type_id":4,"amount":6000},{"resource_type_id":5,"amount":4000},{"resource_type_id":6,"amount":3000},{"resource_type_id":7,"amount":2000},{"resource_type_id":8,"amount":1000}]', NULL FROM world_bosses WHERE boss_key = 'cosmic_entity_lv100_veteran';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 5000000, 25000, 2500, '[{"resource_type_id":1,"amount":5000},{"resource_type_id":2,"amount":5000},{"resource_type_id":3,"amount":4000}]', NULL FROM world_bosses WHERE boss_key = 'cosmic_entity_lv100_veteran';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 2000000, 10000, 1000, '[{"resource_type_id":1,"amount":2500},{"resource_type_id":2,"amount":2500}]', NULL FROM world_bosses WHERE boss_key = 'cosmic_entity_lv100_veteran';
INSERT IGNORE INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 500000, 2500, 250, '[{"resource_type_id":1,"amount":1000}]', NULL FROM world_bosses WHERE boss_key = 'cosmic_entity_lv100_veteran';

-- ===============================================
-- 完了メッセージ
-- ===============================================
SELECT 'World boss veteran and intermediate levels schema applied successfully' AS status;
