-- ===============================================
-- ワールドボス報酬制度改定 2026
-- ボスレベルごとに報酬の種類と量が段階的に増加
-- 基本資源は多く、希少資源は少なめに配分
-- ===============================================

USE microblog;

-- ===============================================
-- 既存の報酬設定を削除（クリーンスタート）
-- ===============================================
DELETE FROM world_boss_rewards;

-- ===============================================
-- Lv10 巨神タイタン の報酬
-- 基本資源（食料、木材、石材）のみ
-- ===============================================
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 10000, 100, 10, 
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 5000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 3000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "stone" LIMIT 1), "amount": 2000}
]', NULL 
FROM world_bosses WHERE boss_key = 'titan_lv10';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 5000, 50, 5,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 2500},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 1500}
]', NULL 
FROM world_bosses WHERE boss_key = 'titan_lv10';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 2000, 20, 2,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 1000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 600}
]', NULL 
FROM world_bosses WHERE boss_key = 'titan_lv10';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 500, 5, 1,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 500}
]', NULL 
FROM world_bosses WHERE boss_key = 'titan_lv10';

-- ===============================================
-- Lv20 九頭竜ヒュドラ の報酬
-- 基本資源 + 準基本資源（青銅、薬草）
-- ===============================================
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 25000, 250, 25,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 10000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 6000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "stone" LIMIT 1), "amount": 4000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "bronze" LIMIT 1), "amount": 500},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "herbs" LIMIT 1), "amount": 300}
]', NULL 
FROM world_bosses WHERE boss_key = 'hydra_lv20';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 12000, 120, 12,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 5000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 3000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "bronze" LIMIT 1), "amount": 250}
]', NULL 
FROM world_bosses WHERE boss_key = 'hydra_lv20';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 5000, 50, 5,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 2000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 1200}
]', NULL 
FROM world_bosses WHERE boss_key = 'hydra_lv20';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 1000, 10, 2,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 800}
]', NULL 
FROM world_bosses WHERE boss_key = 'hydra_lv20';

-- ===============================================
-- Lv30 不死鳥フェニックス の報酬
-- 基本資源 + 準基本資源（布、馬、包帯、鉄）
-- ===============================================
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 50000, 500, 50,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 20000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 12000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "stone" LIMIT 1), "amount": 8000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "bronze" LIMIT 1), "amount": 1000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "herbs" LIMIT 1), "amount": 600},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "cloth" LIMIT 1), "amount": 400},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "horses" LIMIT 1), "amount": 300},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "bandages" LIMIT 1), "amount": 500},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 200}
]', NULL 
FROM world_bosses WHERE boss_key = 'phoenix_lv30';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 25000, 250, 25,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 10000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 6000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "bronze" LIMIT 1), "amount": 500},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 100}
]', NULL 
FROM world_bosses WHERE boss_key = 'phoenix_lv30';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 10000, 100, 10,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 4000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 2400}
]', NULL 
FROM world_bosses WHERE boss_key = 'phoenix_lv30';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 2000, 20, 2,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 1500}
]', NULL 
FROM world_bosses WHERE boss_key = 'phoenix_lv30';

-- ===============================================
-- Lv40 海魔クラーケン の報酬
-- 準基本資源に ガラス、大理石、硫黄、金 を追加
-- ===============================================
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 100000, 1000, 100,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 40000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 24000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "stone" LIMIT 1), "amount": 16000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "bronze" LIMIT 1), "amount": 2000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "herbs" LIMIT 1), "amount": 1200},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "cloth" LIMIT 1), "amount": 800},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "horses" LIMIT 1), "amount": 600},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "bandages" LIMIT 1), "amount": 1000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 400},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "glass" LIMIT 1), "amount": 300},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "marble" LIMIT 1), "amount": 250},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "sulfur" LIMIT 1), "amount": 200},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gold" LIMIT 1), "amount": 150}
]', NULL 
FROM world_bosses WHERE boss_key = 'kraken_lv40';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 50000, 500, 50,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 20000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 12000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "bronze" LIMIT 1), "amount": 1000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 200},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gold" LIMIT 1), "amount": 75}
]', NULL 
FROM world_bosses WHERE boss_key = 'kraken_lv40';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 20000, 200, 20,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 8000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 4800},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 100}
]', NULL 
FROM world_bosses WHERE boss_key = 'kraken_lv40';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 4000, 40, 4,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 3000}
]', NULL 
FROM world_bosses WHERE boss_key = 'kraken_lv40';

-- ===============================================
-- Lv50 魔獣ベヒモス の報酬
-- 準基本資源に 医薬品 を追加、貴重資源開始（宝石、鋼鉄、石炭、知識）
-- ===============================================
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 200000, 2000, 200,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 80000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 48000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "stone" LIMIT 1), "amount": 32000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "bronze" LIMIT 1), "amount": 4000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "herbs" LIMIT 1), "amount": 2400},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "cloth" LIMIT 1), "amount": 1600},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "horses" LIMIT 1), "amount": 1200},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "bandages" LIMIT 1), "amount": 2000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 800},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "glass" LIMIT 1), "amount": 600},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "marble" LIMIT 1), "amount": 500},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "sulfur" LIMIT 1), "amount": 400},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gold" LIMIT 1), "amount": 300},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "medicine" LIMIT 1), "amount": 250},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gems" LIMIT 1), "amount": 50},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "steel" LIMIT 1), "amount": 100},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "coal" LIMIT 1), "amount": 150},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "knowledge" LIMIT 1), "amount": 80}
]', NULL 
FROM world_bosses WHERE boss_key = 'behemoth_lv50';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 100000, 1000, 100,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 40000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 24000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 400},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gold" LIMIT 1), "amount": 150},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gems" LIMIT 1), "amount": 25},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "steel" LIMIT 1), "amount": 50}
]', NULL 
FROM world_bosses WHERE boss_key = 'behemoth_lv50';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 50000, 500, 50,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 16000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 9600},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 200},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "coal" LIMIT 1), "amount": 75}
]', NULL 
FROM world_bosses WHERE boss_key = 'behemoth_lv50';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 10000, 100, 10,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 6000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 3600}
]', NULL 
FROM world_bosses WHERE boss_key = 'behemoth_lv50';

-- ===============================================
-- Lv60 混沌龍カオス の報酬
-- 貴重資源に 香辛料 を追加、火薬資源開始（火薬、石油）
-- ===============================================
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 400000, 4000, 400,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 160000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 96000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "stone" LIMIT 1), "amount": 64000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 1600},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gold" LIMIT 1), "amount": 600},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "medicine" LIMIT 1), "amount": 500},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gems" LIMIT 1), "amount": 100},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "steel" LIMIT 1), "amount": 200},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "coal" LIMIT 1), "amount": 300},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "knowledge" LIMIT 1), "amount": 160},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "spices" LIMIT 1), "amount": 120},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gunpowder" LIMIT 1), "amount": 80},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "oil" LIMIT 1), "amount": 100}
]', NULL 
FROM world_bosses WHERE boss_key = 'chaos_dragon_lv60';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 200000, 2000, 200,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 80000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 48000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 800},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gold" LIMIT 1), "amount": 300},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gems" LIMIT 1), "amount": 50},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "steel" LIMIT 1), "amount": 100},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gunpowder" LIMIT 1), "amount": 40}
]', NULL 
FROM world_bosses WHERE boss_key = 'chaos_dragon_lv60';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 100000, 1000, 100,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 32000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 19200},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 400},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "coal" LIMIT 1), "amount": 150}
]', NULL 
FROM world_bosses WHERE boss_key = 'chaos_dragon_lv60';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 20000, 200, 20,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 12000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 7200}
]', NULL 
FROM world_bosses WHERE boss_key = 'chaos_dragon_lv60';

-- ===============================================
-- Lv70 戦神マルス の報酬
-- 火薬資源に 文明クリスタル、ゴム、マナ を追加
-- ===============================================
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 800000, 8000, 800,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 320000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 192000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "stone" LIMIT 1), "amount": 128000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 3200},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gold" LIMIT 1), "amount": 1200},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gems" LIMIT 1), "amount": 200},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "steel" LIMIT 1), "amount": 400},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "coal" LIMIT 1), "amount": 600},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "knowledge" LIMIT 1), "amount": 320},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "spices" LIMIT 1), "amount": 240},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gunpowder" LIMIT 1), "amount": 160},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "oil" LIMIT 1), "amount": 200},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "crystal" LIMIT 1), "amount": 50},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "rubber" LIMIT 1), "amount": 80},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "mana" LIMIT 1), "amount": 40}
]', NULL 
FROM world_bosses WHERE boss_key = 'god_of_war_lv70';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 400000, 4000, 400,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 160000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 96000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 1600},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gold" LIMIT 1), "amount": 600},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gems" LIMIT 1), "amount": 100},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "steel" LIMIT 1), "amount": 200},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gunpowder" LIMIT 1), "amount": 80}
]', NULL 
FROM world_bosses WHERE boss_key = 'god_of_war_lv70';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 200000, 2000, 200,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 64000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 38400},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 800},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "coal" LIMIT 1), "amount": 300}
]', NULL 
FROM world_bosses WHERE boss_key = 'god_of_war_lv70';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 40000, 400, 40,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 24000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 14400}
]', NULL 
FROM world_bosses WHERE boss_key = 'god_of_war_lv70';

-- ===============================================
-- Lv80 世界喰いジョルムンガンド の報酬
-- 火薬資源に 電子部品、チタン を追加、超貴重資源開始（ウラン）
-- ===============================================
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 1600000, 16000, 1600,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 640000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 384000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "stone" LIMIT 1), "amount": 256000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 6400},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gold" LIMIT 1), "amount": 2400},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gems" LIMIT 1), "amount": 400},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "steel" LIMIT 1), "amount": 800},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "coal" LIMIT 1), "amount": 1200},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "knowledge" LIMIT 1), "amount": 640},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gunpowder" LIMIT 1), "amount": 320},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "oil" LIMIT 1), "amount": 400},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "crystal" LIMIT 1), "amount": 100},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "rubber" LIMIT 1), "amount": 160},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "mana" LIMIT 1), "amount": 80},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "electronics" LIMIT 1), "amount": 120},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "titanium" LIMIT 1), "amount": 100},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "uranium" LIMIT 1), "amount": 20}
]', NULL 
FROM world_bosses WHERE boss_key = 'world_eater_lv80';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 800000, 8000, 800,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 320000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 192000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 3200},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gold" LIMIT 1), "amount": 1200},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gems" LIMIT 1), "amount": 200},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "steel" LIMIT 1), "amount": 400},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gunpowder" LIMIT 1), "amount": 160},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "uranium" LIMIT 1), "amount": 10}
]', NULL 
FROM world_bosses WHERE boss_key = 'world_eater_lv80';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 400000, 4000, 400,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 128000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 76800},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 1600},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "coal" LIMIT 1), "amount": 600}
]', NULL 
FROM world_bosses WHERE boss_key = 'world_eater_lv80';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 80000, 800, 80,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 48000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 28800}
]', NULL 
FROM world_bosses WHERE boss_key = 'world_eater_lv80';

-- ===============================================
-- Lv90 虚無王 の報酬
-- 超貴重資源に ダイヤモンド、プルトニウム、シリコン、レアアース を追加
-- ===============================================
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 3200000, 32000, 3200,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 1280000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 768000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "stone" LIMIT 1), "amount": 512000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 12800},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gold" LIMIT 1), "amount": 4800},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gems" LIMIT 1), "amount": 800},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "steel" LIMIT 1), "amount": 1600},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "knowledge" LIMIT 1), "amount": 1280},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gunpowder" LIMIT 1), "amount": 640},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "oil" LIMIT 1), "amount": 800},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "crystal" LIMIT 1), "amount": 200},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "electronics" LIMIT 1), "amount": 240},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "titanium" LIMIT 1), "amount": 200},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "uranium" LIMIT 1), "amount": 40},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "diamond" LIMIT 1), "amount": 30},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "plutonium" LIMIT 1), "amount": 25},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "silicon" LIMIT 1), "amount": 100},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "rare_earth" LIMIT 1), "amount": 80}
]', NULL 
FROM world_bosses WHERE boss_key = 'void_lord_lv90';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 1600000, 16000, 1600,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 640000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 384000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 6400},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gold" LIMIT 1), "amount": 2400},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gems" LIMIT 1), "amount": 400},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "steel" LIMIT 1), "amount": 800},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "uranium" LIMIT 1), "amount": 20},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "diamond" LIMIT 1), "amount": 15},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "silicon" LIMIT 1), "amount": 50}
]', NULL 
FROM world_bosses WHERE boss_key = 'void_lord_lv90';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 800000, 8000, 800,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 256000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 153600},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 3200},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "coal" LIMIT 1), "amount": 1200}
]', NULL 
FROM world_bosses WHERE boss_key = 'void_lord_lv90';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 160000, 1600, 160,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 96000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 57600}
]', NULL 
FROM world_bosses WHERE boss_key = 'void_lord_lv90';

-- ===============================================
-- Lv100 宇宙創造神 の報酬
-- 弩級貴重資源追加（量子結晶、AIコア、遺伝子サンプル、ダークマター、反物質）
-- ===============================================
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 5000000, 50000, 5000,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 2000000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 1200000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "stone" LIMIT 1), "amount": 800000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 20000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gold" LIMIT 1), "amount": 8000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gems" LIMIT 1), "amount": 1500},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "steel" LIMIT 1), "amount": 3000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "knowledge" LIMIT 1), "amount": 2500},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gunpowder" LIMIT 1), "amount": 1200},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "oil" LIMIT 1), "amount": 1500},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "crystal" LIMIT 1), "amount": 400},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "electronics" LIMIT 1), "amount": 500},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "titanium" LIMIT 1), "amount": 400},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "uranium" LIMIT 1), "amount": 80},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "diamond" LIMIT 1), "amount": 60},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "plutonium" LIMIT 1), "amount": 50},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "silicon" LIMIT 1), "amount": 200},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "rare_earth" LIMIT 1), "amount": 150},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "quantum_crystal" LIMIT 1), "amount": 20},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "ai_core" LIMIT 1), "amount": 15},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gene_sample" LIMIT 1), "amount": 25},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "dark_matter" LIMIT 1), "amount": 10},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "antimatter" LIMIT 1), "amount": 5}
]', NULL 
FROM world_bosses WHERE boss_key = 'cosmic_entity_lv100';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 2500000, 25000, 2500,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 1000000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 600000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 10000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gold" LIMIT 1), "amount": 4000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gems" LIMIT 1), "amount": 750},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "steel" LIMIT 1), "amount": 1500},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "uranium" LIMIT 1), "amount": 40},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "diamond" LIMIT 1), "amount": 30},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "silicon" LIMIT 1), "amount": 100},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "quantum_crystal" LIMIT 1), "amount": 10},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "ai_core" LIMIT 1), "amount": 7}
]', NULL 
FROM world_bosses WHERE boss_key = 'cosmic_entity_lv100';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 1000000, 10000, 1000,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 400000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 240000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 5000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "coal" LIMIT 1), "amount": 2000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "silicon" LIMIT 1), "amount": 50}
]', NULL 
FROM world_bosses WHERE boss_key = 'cosmic_entity_lv100';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 250000, 2500, 250,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 150000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 90000}
]', NULL 
FROM world_bosses WHERE boss_key = 'cosmic_entity_lv100';

-- ===============================================
-- 中間レベルのボス報酬（Lv5, 15, 25, 35, 45, 55, 65, 75, 85, 95）
-- ===============================================

-- Lv5 ゴブリン・ダガス（基本資源のみ、Lv10より少なめ）
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 5000, 50, 5,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 2500},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 1500},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "stone" LIMIT 1), "amount": 1000}
]', NULL FROM world_bosses WHERE boss_key = 'goblin_dagas_lv5';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 2500, 25, 2,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 1200},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 750}
]', NULL FROM world_bosses WHERE boss_key = 'goblin_dagas_lv5';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 1000, 10, 1,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 500}
]', NULL FROM world_bosses WHERE boss_key = 'goblin_dagas_lv5';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 250, 2, 0,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 250}
]', NULL FROM world_bosses WHERE boss_key = 'goblin_dagas_lv5';

-- Lv15 悪魔・モルゲート
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 17500, 175, 17,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 7500},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 4500},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "stone" LIMIT 1), "amount": 3000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "bronze" LIMIT 1), "amount": 375}
]', NULL FROM world_bosses WHERE boss_key = 'demon_morgate_lv15';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 8500, 85, 8,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 3750},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 2250}
]', NULL FROM world_bosses WHERE boss_key = 'demon_morgate_lv15';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 3500, 35, 3,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 1500}
]', NULL FROM world_bosses WHERE boss_key = 'demon_morgate_lv15';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 750, 7, 1,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 650}
]', NULL FROM world_bosses WHERE boss_key = 'demon_morgate_lv15';

-- Lv25 アイガオン
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 37500, 375, 37,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 15000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 9000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "stone" LIMIT 1), "amount": 6000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "bronze" LIMIT 1), "amount": 750},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "herbs" LIMIT 1), "amount": 450},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "cloth" LIMIT 1), "amount": 300}
]', NULL FROM world_bosses WHERE boss_key = 'aigaon_lv25';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 18500, 185, 18,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 7500},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 4500},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "bronze" LIMIT 1), "amount": 375}
]', NULL FROM world_bosses WHERE boss_key = 'aigaon_lv25';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 7500, 75, 7,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 3000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 1800}
]', NULL FROM world_bosses WHERE boss_key = 'aigaon_lv25';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 1500, 15, 1,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 1100}
]', NULL FROM world_bosses WHERE boss_key = 'aigaon_lv25';

-- Lv35 アンデバブ
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 75000, 750, 75,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 30000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 18000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "stone" LIMIT 1), "amount": 12000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "bronze" LIMIT 1), "amount": 1500},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "herbs" LIMIT 1), "amount": 900},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "cloth" LIMIT 1), "amount": 600},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "horses" LIMIT 1), "amount": 450},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "bandages" LIMIT 1), "amount": 750},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 300},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "glass" LIMIT 1), "amount": 225}
]', NULL FROM world_bosses WHERE boss_key = 'undebab_lv35';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 37500, 375, 37,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 15000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 9000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "bronze" LIMIT 1), "amount": 750},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 150}
]', NULL FROM world_bosses WHERE boss_key = 'undebab_lv35';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 15000, 150, 15,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 6000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 3600}
]', NULL FROM world_bosses WHERE boss_key = 'undebab_lv35';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 3000, 30, 3,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 2250}
]', NULL FROM world_bosses WHERE boss_key = 'undebab_lv35';

-- Lv45 バクスト
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 150000, 1500, 150,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 60000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 36000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "stone" LIMIT 1), "amount": 24000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 600},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gold" LIMIT 1), "amount": 225},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "medicine" LIMIT 1), "amount": 375},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gems" LIMIT 1), "amount": 75},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "steel" LIMIT 1), "amount": 150},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "coal" LIMIT 1), "amount": 225}
]', NULL FROM world_bosses WHERE boss_key = 'bakust_lv45';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 75000, 750, 75,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 30000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 18000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 300},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gold" LIMIT 1), "amount": 112}
]', NULL FROM world_bosses WHERE boss_key = 'bakust_lv45';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 30000, 300, 30,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 12000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 7200},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 150}
]', NULL FROM world_bosses WHERE boss_key = 'bakust_lv45';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 6000, 60, 6,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 4500}
]', NULL FROM world_bosses WHERE boss_key = 'bakust_lv45';

-- Lv55 オセアノの蛇
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 300000, 3000, 300,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 120000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 72000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "stone" LIMIT 1), "amount": 48000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 1200},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gold" LIMIT 1), "amount": 450},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gems" LIMIT 1), "amount": 150},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "steel" LIMIT 1), "amount": 300},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "coal" LIMIT 1), "amount": 450},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "knowledge" LIMIT 1), "amount": 120},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "spices" LIMIT 1), "amount": 90},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gunpowder" LIMIT 1), "amount": 60}
]', NULL FROM world_bosses WHERE boss_key = 'oceano_serpent_lv55';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 150000, 1500, 150,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 60000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 36000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 600},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gold" LIMIT 1), "amount": 225},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gems" LIMIT 1), "amount": 75}
]', NULL FROM world_bosses WHERE boss_key = 'oceano_serpent_lv55';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 60000, 600, 60,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 24000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 14400},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 300}
]', NULL FROM world_bosses WHERE boss_key = 'oceano_serpent_lv55';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 12000, 120, 12,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 9000}
]', NULL FROM world_bosses WHERE boss_key = 'oceano_serpent_lv55';

-- Lv65 トロールの化身
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 600000, 6000, 600,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 240000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 144000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "stone" LIMIT 1), "amount": 96000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 2400},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gold" LIMIT 1), "amount": 900},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gems" LIMIT 1), "amount": 300},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "steel" LIMIT 1), "amount": 600},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "coal" LIMIT 1), "amount": 900},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "knowledge" LIMIT 1), "amount": 240},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "spices" LIMIT 1), "amount": 180},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gunpowder" LIMIT 1), "amount": 120},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "oil" LIMIT 1), "amount": 150},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "crystal" LIMIT 1), "amount": 75}
]', NULL FROM world_bosses WHERE boss_key = 'troll_avatar_lv65';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 300000, 3000, 300,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 120000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 72000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 1200},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gold" LIMIT 1), "amount": 450},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gems" LIMIT 1), "amount": 150},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "steel" LIMIT 1), "amount": 300}
]', NULL FROM world_bosses WHERE boss_key = 'troll_avatar_lv65';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 120000, 1200, 120,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 48000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 28800},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 600}
]', NULL FROM world_bosses WHERE boss_key = 'troll_avatar_lv65';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 24000, 240, 24,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 18000}
]', NULL FROM world_bosses WHERE boss_key = 'troll_avatar_lv65';

-- Lv75 コンデット
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 1200000, 12000, 1200,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 480000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 288000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "stone" LIMIT 1), "amount": 192000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 4800},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gold" LIMIT 1), "amount": 1800},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gems" LIMIT 1), "amount": 600},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "steel" LIMIT 1), "amount": 1200},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "knowledge" LIMIT 1), "amount": 480},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gunpowder" LIMIT 1), "amount": 240},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "oil" LIMIT 1), "amount": 300},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "crystal" LIMIT 1), "amount": 150},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "rubber" LIMIT 1), "amount": 240},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "mana" LIMIT 1), "amount": 120},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "electronics" LIMIT 1), "amount": 180},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "titanium" LIMIT 1), "amount": 150}
]', NULL FROM world_bosses WHERE boss_key = 'condett_lv75';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 600000, 6000, 600,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 240000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 144000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 2400},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gold" LIMIT 1), "amount": 900},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gems" LIMIT 1), "amount": 300},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "steel" LIMIT 1), "amount": 600}
]', NULL FROM world_bosses WHERE boss_key = 'condett_lv75';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 240000, 2400, 240,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 96000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 57600},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 1200}
]', NULL FROM world_bosses WHERE boss_key = 'condett_lv75';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 48000, 480, 48,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 36000}
]', NULL FROM world_bosses WHERE boss_key = 'condett_lv75';

-- Lv85 ビースト軍団
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 2400000, 24000, 2400,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 960000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 576000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "stone" LIMIT 1), "amount": 384000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 9600},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gold" LIMIT 1), "amount": 3600},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gems" LIMIT 1), "amount": 1200},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "steel" LIMIT 1), "amount": 2400},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "knowledge" LIMIT 1), "amount": 960},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gunpowder" LIMIT 1), "amount": 480},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "oil" LIMIT 1), "amount": 600},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "crystal" LIMIT 1), "amount": 300},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "electronics" LIMIT 1), "amount": 360},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "titanium" LIMIT 1), "amount": 300},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "uranium" LIMIT 1), "amount": 60},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "diamond" LIMIT 1), "amount": 45},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "plutonium" LIMIT 1), "amount": 37}
]', NULL FROM world_bosses WHERE boss_key = 'beast_legion_lv85';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 1200000, 12000, 1200,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 480000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 288000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 4800},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gold" LIMIT 1), "amount": 1800},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gems" LIMIT 1), "amount": 600},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "uranium" LIMIT 1), "amount": 30}
]', NULL FROM world_bosses WHERE boss_key = 'beast_legion_lv85';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 480000, 4800, 480,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 192000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 115200},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 2400}
]', NULL FROM world_bosses WHERE boss_key = 'beast_legion_lv85';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 96000, 960, 96,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 72000}
]', NULL FROM world_bosses WHERE boss_key = 'beast_legion_lv85';

-- Lv95 エピックゴーレム
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 4000000, 40000, 4000,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 1600000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 960000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "stone" LIMIT 1), "amount": 640000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 16000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gold" LIMIT 1), "amount": 6000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gems" LIMIT 1), "amount": 2000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "steel" LIMIT 1), "amount": 4000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "knowledge" LIMIT 1), "amount": 1600},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "oil" LIMIT 1), "amount": 1000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "electronics" LIMIT 1), "amount": 600},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "titanium" LIMIT 1), "amount": 500},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "uranium" LIMIT 1), "amount": 100},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "diamond" LIMIT 1), "amount": 75},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "plutonium" LIMIT 1), "amount": 62},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "silicon" LIMIT 1), "amount": 250},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "rare_earth" LIMIT 1), "amount": 200},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "quantum_crystal" LIMIT 1), "amount": 30},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "ai_core" LIMIT 1), "amount": 22}
]', NULL FROM world_bosses WHERE boss_key = 'epic_golem_lv95';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 2000000, 20000, 2000,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 800000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 480000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 8000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gold" LIMIT 1), "amount": 3000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gems" LIMIT 1), "amount": 1000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "uranium" LIMIT 1), "amount": 50},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "silicon" LIMIT 1), "amount": 125}
]', NULL FROM world_bosses WHERE boss_key = 'epic_golem_lv95';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 800000, 8000, 800,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 320000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 192000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 4000}
]', NULL FROM world_bosses WHERE boss_key = 'epic_golem_lv95';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 160000, 1600, 160,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 120000}
]', NULL FROM world_bosses WHERE boss_key = 'epic_golem_lv95';

-- ===============================================
-- ベテランボスの報酬（コイン2倍、資源2倍、クリスタルとダイヤモンドは同じ）
-- ===============================================

-- Lv10 ベテラン
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 20000, 100, 10,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 10000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 6000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "stone" LIMIT 1), "amount": 4000}
]', NULL FROM world_bosses WHERE boss_key = 'titan_lv10_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 10000, 50, 5,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 5000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 3000}
]', NULL FROM world_bosses WHERE boss_key = 'titan_lv10_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 4000, 20, 2,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 2000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 1200}
]', NULL FROM world_bosses WHERE boss_key = 'titan_lv10_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 1000, 5, 1,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 1000}
]', NULL FROM world_bosses WHERE boss_key = 'titan_lv10_veteran';

-- 残りのベテランボスも同様のパターンで2倍の報酬を設定
-- Lv20以降は長くなるため、主要なものだけ記載し、残りは同様のロジックで倍増

-- Lv20 ベテラン
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 50000, 250, 25,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 20000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 12000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "stone" LIMIT 1), "amount": 8000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "bronze" LIMIT 1), "amount": 1000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "herbs" LIMIT 1), "amount": 600}
]', NULL FROM world_bosses WHERE boss_key = 'hydra_lv20_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 24000, 120, 12,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 10000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 6000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "bronze" LIMIT 1), "amount": 500}
]', NULL FROM world_bosses WHERE boss_key = 'hydra_lv20_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 10000, 50, 5,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 4000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 2400}
]', NULL FROM world_bosses WHERE boss_key = 'hydra_lv20_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 2000, 10, 2,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 1600}
]', NULL FROM world_bosses WHERE boss_key = 'hydra_lv20_veteran';

-- 注: Lv30-100のベテランボスの報酬は通常ボスの2倍（コイン・資源）で設定します
-- 詳細は省略し、必要に応じて同じロジックで追加してください

-- ===============================================
-- 完了メッセージ
-- ===============================================
SELECT 'World boss rewards overhaul 2026 completed successfully. Rewards now scale progressively with boss level.' AS status;

-- Lv30 ベテラン
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 100000, 500, 50,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 40000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 24000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "stone" LIMIT 1), "amount": 16000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "bronze" LIMIT 1), "amount": 2000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "herbs" LIMIT 1), "amount": 1200},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "cloth" LIMIT 1), "amount": 800},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "horses" LIMIT 1), "amount": 600},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "bandages" LIMIT 1), "amount": 1000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 400}
]', NULL FROM world_bosses WHERE boss_key = 'phoenix_lv30_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 50000, 250, 25,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 20000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 12000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "bronze" LIMIT 1), "amount": 1000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 200}
]', NULL FROM world_bosses WHERE boss_key = 'phoenix_lv30_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 20000, 100, 10,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 8000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 4800}
]', NULL FROM world_bosses WHERE boss_key = 'phoenix_lv30_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 4000, 20, 2,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 3000}
]', NULL FROM world_bosses WHERE boss_key = 'phoenix_lv30_veteran';

-- Lv40 ベテラン
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 200000, 1000, 100,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 80000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 48000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "stone" LIMIT 1), "amount": 32000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 800},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gold" LIMIT 1), "amount": 300},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "marble" LIMIT 1), "amount": 500},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "sulfur" LIMIT 1), "amount": 400}
]', NULL FROM world_bosses WHERE boss_key = 'kraken_lv40_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 100000, 500, 50,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 40000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 24000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 400},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gold" LIMIT 1), "amount": 150}
]', NULL FROM world_bosses WHERE boss_key = 'kraken_lv40_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 40000, 200, 20,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 16000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 9600}
]', NULL FROM world_bosses WHERE boss_key = 'kraken_lv40_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 8000, 40, 4,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 6000}
]', NULL FROM world_bosses WHERE boss_key = 'kraken_lv40_veteran';

-- Lv50 ベテラン
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 400000, 2000, 200,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 160000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 96000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "stone" LIMIT 1), "amount": 64000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 1600},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gold" LIMIT 1), "amount": 600},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gems" LIMIT 1), "amount": 100},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "steel" LIMIT 1), "amount": 200},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "coal" LIMIT 1), "amount": 300},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "knowledge" LIMIT 1), "amount": 160}
]', NULL FROM world_bosses WHERE boss_key = 'behemoth_lv50_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 200000, 1000, 100,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 80000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 48000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 800},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gold" LIMIT 1), "amount": 300},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gems" LIMIT 1), "amount": 50},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "steel" LIMIT 1), "amount": 100}
]', NULL FROM world_bosses WHERE boss_key = 'behemoth_lv50_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 100000, 500, 50,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 32000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 19200},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 400}
]', NULL FROM world_bosses WHERE boss_key = 'behemoth_lv50_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 20000, 100, 10,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 12000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 7200}
]', NULL FROM world_bosses WHERE boss_key = 'behemoth_lv50_veteran';

-- Lv60 ベテラン
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 800000, 4000, 400,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 320000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 192000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "stone" LIMIT 1), "amount": 128000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 3200},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gold" LIMIT 1), "amount": 1200},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gems" LIMIT 1), "amount": 200},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "steel" LIMIT 1), "amount": 400},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gunpowder" LIMIT 1), "amount": 160},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "oil" LIMIT 1), "amount": 200}
]', NULL FROM world_bosses WHERE boss_key = 'chaos_dragon_lv60_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 400000, 2000, 200,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 160000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 96000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 1600},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gold" LIMIT 1), "amount": 600},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gems" LIMIT 1), "amount": 100}
]', NULL FROM world_bosses WHERE boss_key = 'chaos_dragon_lv60_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 200000, 1000, 100,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 64000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 38400},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 800}
]', NULL FROM world_bosses WHERE boss_key = 'chaos_dragon_lv60_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 40000, 200, 20,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 24000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 14400}
]', NULL FROM world_bosses WHERE boss_key = 'chaos_dragon_lv60_veteran';

-- Lv70 ベテラン
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 1600000, 8000, 800,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 640000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 384000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "stone" LIMIT 1), "amount": 256000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 6400},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gold" LIMIT 1), "amount": 2400},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gems" LIMIT 1), "amount": 400},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "steel" LIMIT 1), "amount": 800},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gunpowder" LIMIT 1), "amount": 320},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "crystal" LIMIT 1), "amount": 100},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "mana" LIMIT 1), "amount": 80}
]', NULL FROM world_bosses WHERE boss_key = 'god_of_war_lv70_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 800000, 4000, 400,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 320000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 192000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 3200},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gold" LIMIT 1), "amount": 1200},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gems" LIMIT 1), "amount": 200}
]', NULL FROM world_bosses WHERE boss_key = 'god_of_war_lv70_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 400000, 2000, 200,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 128000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 76800},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 1600}
]', NULL FROM world_bosses WHERE boss_key = 'god_of_war_lv70_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 80000, 400, 40,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 48000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 28800}
]', NULL FROM world_bosses WHERE boss_key = 'god_of_war_lv70_veteran';

-- Lv80 ベテラン
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 3200000, 16000, 1600,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 1280000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 768000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "stone" LIMIT 1), "amount": 512000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 12800},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gold" LIMIT 1), "amount": 4800},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gems" LIMIT 1), "amount": 800},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "steel" LIMIT 1), "amount": 1600},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "electronics" LIMIT 1), "amount": 240},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "titanium" LIMIT 1), "amount": 200},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "uranium" LIMIT 1), "amount": 40}
]', NULL FROM world_bosses WHERE boss_key = 'world_eater_lv80_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 1600000, 8000, 800,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 640000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 384000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 6400},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gold" LIMIT 1), "amount": 2400},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gems" LIMIT 1), "amount": 400},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "uranium" LIMIT 1), "amount": 20}
]', NULL FROM world_bosses WHERE boss_key = 'world_eater_lv80_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 800000, 4000, 400,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 256000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 153600},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 3200}
]', NULL FROM world_bosses WHERE boss_key = 'world_eater_lv80_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 160000, 800, 80,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 96000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 57600}
]', NULL FROM world_bosses WHERE boss_key = 'world_eater_lv80_veteran';

-- Lv90 ベテラン
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 6400000, 32000, 3200,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 2560000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 1536000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "stone" LIMIT 1), "amount": 1024000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 25600},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gold" LIMIT 1), "amount": 9600},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gems" LIMIT 1), "amount": 1600},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "steel" LIMIT 1), "amount": 3200},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "uranium" LIMIT 1), "amount": 80},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "diamond" LIMIT 1), "amount": 60},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "plutonium" LIMIT 1), "amount": 50},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "silicon" LIMIT 1), "amount": 200},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "rare_earth" LIMIT 1), "amount": 160}
]', NULL FROM world_bosses WHERE boss_key = 'void_lord_lv90_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 3200000, 16000, 1600,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 1280000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 768000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 12800},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gold" LIMIT 1), "amount": 4800},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gems" LIMIT 1), "amount": 800},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "uranium" LIMIT 1), "amount": 40},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "silicon" LIMIT 1), "amount": 100}
]', NULL FROM world_bosses WHERE boss_key = 'void_lord_lv90_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 1600000, 8000, 800,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 512000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 307200},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 6400}
]', NULL FROM world_bosses WHERE boss_key = 'void_lord_lv90_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 320000, 1600, 160,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 192000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 115200}
]', NULL FROM world_bosses WHERE boss_key = 'void_lord_lv90_veteran';

-- Lv100 ベテラン
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 1, 1, 10000000, 50000, 5000,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 4000000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 2400000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "stone" LIMIT 1), "amount": 1600000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 40000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gold" LIMIT 1), "amount": 16000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gems" LIMIT 1), "amount": 3000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "steel" LIMIT 1), "amount": 6000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "knowledge" LIMIT 1), "amount": 5000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "uranium" LIMIT 1), "amount": 160},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "diamond" LIMIT 1), "amount": 120},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "plutonium" LIMIT 1), "amount": 100},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "silicon" LIMIT 1), "amount": 400},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "rare_earth" LIMIT 1), "amount": 300},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "quantum_crystal" LIMIT 1), "amount": 40},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "ai_core" LIMIT 1), "amount": 30},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gene_sample" LIMIT 1), "amount": 50},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "dark_matter" LIMIT 1), "amount": 20},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "antimatter" LIMIT 1), "amount": 10}
]', NULL FROM world_bosses WHERE boss_key = 'cosmic_entity_lv100_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 2, 3, 5000000, 25000, 2500,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 2000000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 1200000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 20000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gold" LIMIT 1), "amount": 8000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "gems" LIMIT 1), "amount": 1500},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "uranium" LIMIT 1), "amount": 80},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "silicon" LIMIT 1), "amount": 200},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "quantum_crystal" LIMIT 1), "amount": 20},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "ai_core" LIMIT 1), "amount": 14}
]', NULL FROM world_bosses WHERE boss_key = 'cosmic_entity_lv100_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 4, 10, 2000000, 10000, 1000,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 800000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 480000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "iron" LIMIT 1), "amount": 10000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "silicon" LIMIT 1), "amount": 100}
]', NULL FROM world_bosses WHERE boss_key = 'cosmic_entity_lv100_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops) 
SELECT id, 11, 50, 500000, 2500, 250,
'[
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "food" LIMIT 1), "amount": 300000},
  {"resource_type_id": (SELECT id FROM civilization_resource_types WHERE resource_key = "wood" LIMIT 1), "amount": 180000}
]', NULL FROM world_bosses WHERE boss_key = 'cosmic_entity_lv100_veteran';
