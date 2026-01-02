-- ===============================================
-- ワールドボス報酬制度改定 2026
-- ボスレベルごとに報酬の種類と量が段階的に増加
-- 基本資源は多く、希少資源は少なめに配分
-- JSON内でのサブクエリの問題を修正（JSON_OBJECT使用）
-- 資源名とアイコンは直接挿入（サブクエリなし）
-- ===============================================

USE syugetsu2025_clone;

-- ===============================================
-- 既存の報酬設定を削除（クリーンスタート）
-- ===============================================
DELETE FROM world_boss_rewards;

-- ===============================================
-- 通常ボスと中間レベルボスの報酬
-- ===============================================

-- Lv5 goblin_dagas_lv5
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 1, 1, 5000, 50, 5,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 2500
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 1500
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'stone' LIMIT 1),
    'resource_key', 'stone',
    'name', '石材',
    'icon', '🪨',
    'amount', 1000
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'goblin_dagas_lv5';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 2, 3, 2500, 25, 2,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 1200
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 750
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'goblin_dagas_lv5';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 4, 10, 1000, 10, 1,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 500
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'goblin_dagas_lv5';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 11, 50, 250, 2, 0,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 250
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'goblin_dagas_lv5';

-- Lv10 titan_lv10
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 1, 1, 10000, 100, 10,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 5000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 3000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'stone' LIMIT 1),
    'resource_key', 'stone',
    'name', '石材',
    'icon', '🪨',
    'amount', 2000
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'titan_lv10';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 2, 3, 5000, 50, 5,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 2500
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 1500
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'titan_lv10';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 4, 10, 2000, 20, 2,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 1000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 600
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'titan_lv10';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 11, 50, 500, 5, 1,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 500
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'titan_lv10';

-- Lv15 demon_morgate_lv15
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 1, 1, 17500, 175, 17,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 7500
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 4500
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'stone' LIMIT 1),
    'resource_key', 'stone',
    'name', '石材',
    'icon', '🪨',
    'amount', 3000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'bronze' LIMIT 1),
    'resource_key', 'bronze',
    'name', '青銅',
    'icon', '🔶',
    'amount', 375
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'demon_morgate_lv15';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 2, 3, 8500, 85, 8,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 3750
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 2250
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'demon_morgate_lv15';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 4, 10, 3500, 35, 3,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 1500
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'demon_morgate_lv15';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 11, 50, 750, 7, 1,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 650
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'demon_morgate_lv15';

-- Lv20 hydra_lv20
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 1, 1, 25000, 250, 25,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 10000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 6000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'stone' LIMIT 1),
    'resource_key', 'stone',
    'name', '石材',
    'icon', '🪨',
    'amount', 4000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'bronze' LIMIT 1),
    'resource_key', 'bronze',
    'name', '青銅',
    'icon', '🔶',
    'amount', 500
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'herbs' LIMIT 1),
    'resource_key', 'herbs',
    'name', '薬草',
    'icon', '🌿',
    'amount', 300
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'hydra_lv20';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 2, 3, 12000, 120, 12,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 5000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 3000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'bronze' LIMIT 1),
    'resource_key', 'bronze',
    'name', '青銅',
    'icon', '🔶',
    'amount', 250
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'hydra_lv20';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 4, 10, 5000, 50, 5,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 2000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 1200
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'hydra_lv20';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 11, 50, 1000, 10, 2,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 800
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'hydra_lv20';

-- Lv25 aigaon_lv25
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 1, 1, 37500, 375, 37,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 15000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 9000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'stone' LIMIT 1),
    'resource_key', 'stone',
    'name', '石材',
    'icon', '🪨',
    'amount', 6000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'bronze' LIMIT 1),
    'resource_key', 'bronze',
    'name', '青銅',
    'icon', '🔶',
    'amount', 750
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'herbs' LIMIT 1),
    'resource_key', 'herbs',
    'name', '薬草',
    'icon', '🌿',
    'amount', 450
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'cloth' LIMIT 1),
    'resource_key', 'cloth',
    'name', '布',
    'icon', '🧵',
    'amount', 300
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'aigaon_lv25';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 2, 3, 18500, 185, 18,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 7500
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 4500
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'bronze' LIMIT 1),
    'resource_key', 'bronze',
    'name', '青銅',
    'icon', '🔶',
    'amount', 375
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'aigaon_lv25';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 4, 10, 7500, 75, 7,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 3000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 1800
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'aigaon_lv25';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 11, 50, 1500, 15, 1,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 1100
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'aigaon_lv25';

-- Lv30 phoenix_lv30
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 1, 1, 50000, 500, 50,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 20000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 12000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'stone' LIMIT 1),
    'resource_key', 'stone',
    'name', '石材',
    'icon', '🪨',
    'amount', 8000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'bronze' LIMIT 1),
    'resource_key', 'bronze',
    'name', '青銅',
    'icon', '🔶',
    'amount', 1000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'herbs' LIMIT 1),
    'resource_key', 'herbs',
    'name', '薬草',
    'icon', '🌿',
    'amount', 600
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'cloth' LIMIT 1),
    'resource_key', 'cloth',
    'name', '布',
    'icon', '🧵',
    'amount', 400
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'horses' LIMIT 1),
    'resource_key', 'horses',
    'name', '馬',
    'icon', '🐴',
    'amount', 300
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'bandages' LIMIT 1),
    'resource_key', 'bandages',
    'name', '包帯',
    'icon', '🩹',
    'amount', 500
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 200
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'phoenix_lv30';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 2, 3, 25000, 250, 25,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 10000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 6000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'bronze' LIMIT 1),
    'resource_key', 'bronze',
    'name', '青銅',
    'icon', '🔶',
    'amount', 500
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 100
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'phoenix_lv30';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 4, 10, 10000, 100, 10,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 4000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 2400
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'phoenix_lv30';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 11, 50, 2000, 20, 2,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 1500
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'phoenix_lv30';

-- Lv35 undebab_lv35
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 1, 1, 75000, 750, 75,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 30000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 18000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'stone' LIMIT 1),
    'resource_key', 'stone',
    'name', '石材',
    'icon', '🪨',
    'amount', 12000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'bronze' LIMIT 1),
    'resource_key', 'bronze',
    'name', '青銅',
    'icon', '🔶',
    'amount', 1500
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'herbs' LIMIT 1),
    'resource_key', 'herbs',
    'name', '薬草',
    'icon', '🌿',
    'amount', 900
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'cloth' LIMIT 1),
    'resource_key', 'cloth',
    'name', '布',
    'icon', '🧵',
    'amount', 600
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'horses' LIMIT 1),
    'resource_key', 'horses',
    'name', '馬',
    'icon', '🐴',
    'amount', 450
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'bandages' LIMIT 1),
    'resource_key', 'bandages',
    'name', '包帯',
    'icon', '🩹',
    'amount', 750
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 300
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'glass' LIMIT 1),
    'resource_key', 'glass',
    'name', 'ガラス',
    'icon', '🔮',
    'amount', 225
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'undebab_lv35';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 2, 3, 37500, 375, 37,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 15000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 9000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'bronze' LIMIT 1),
    'resource_key', 'bronze',
    'name', '青銅',
    'icon', '🔶',
    'amount', 750
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 150
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'undebab_lv35';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 4, 10, 15000, 150, 15,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 6000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 3600
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'undebab_lv35';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 11, 50, 3000, 30, 3,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 2250
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'undebab_lv35';

-- Lv40 kraken_lv40
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 1, 1, 100000, 1000, 100,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 40000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 24000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'stone' LIMIT 1),
    'resource_key', 'stone',
    'name', '石材',
    'icon', '🪨',
    'amount', 16000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'bronze' LIMIT 1),
    'resource_key', 'bronze',
    'name', '青銅',
    'icon', '🔶',
    'amount', 2000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'herbs' LIMIT 1),
    'resource_key', 'herbs',
    'name', '薬草',
    'icon', '🌿',
    'amount', 1200
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'cloth' LIMIT 1),
    'resource_key', 'cloth',
    'name', '布',
    'icon', '🧵',
    'amount', 800
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'horses' LIMIT 1),
    'resource_key', 'horses',
    'name', '馬',
    'icon', '🐴',
    'amount', 600
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'bandages' LIMIT 1),
    'resource_key', 'bandages',
    'name', '包帯',
    'icon', '🩹',
    'amount', 1000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 400
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'glass' LIMIT 1),
    'resource_key', 'glass',
    'name', 'ガラス',
    'icon', '🔮',
    'amount', 300
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'marble' LIMIT 1),
    'resource_key', 'marble',
    'name', '大理石',
    'icon', '🏛️',
    'amount', 250
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'sulfur' LIMIT 1),
    'resource_key', 'sulfur',
    'name', '硫黄',
    'icon', '🔶',
    'amount', 200
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gold' LIMIT 1),
    'resource_key', 'gold',
    'name', '金',
    'icon', '💰',
    'amount', 150
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'kraken_lv40';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 2, 3, 50000, 500, 50,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 20000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 12000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'bronze' LIMIT 1),
    'resource_key', 'bronze',
    'name', '青銅',
    'icon', '🔶',
    'amount', 1000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 200
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gold' LIMIT 1),
    'resource_key', 'gold',
    'name', '金',
    'icon', '💰',
    'amount', 75
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'kraken_lv40';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 4, 10, 20000, 200, 20,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 8000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 4800
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 100
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'kraken_lv40';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 11, 50, 4000, 40, 4,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 3000
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'kraken_lv40';

-- Lv45 bakust_lv45
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 1, 1, 150000, 1500, 150,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 60000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 36000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'stone' LIMIT 1),
    'resource_key', 'stone',
    'name', '石材',
    'icon', '🪨',
    'amount', 24000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 600
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gold' LIMIT 1),
    'resource_key', 'gold',
    'name', '金',
    'icon', '💰',
    'amount', 225
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'medicine' LIMIT 1),
    'resource_key', 'medicine',
    'name', '医薬品',
    'icon', '💊',
    'amount', 375
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gems' LIMIT 1),
    'resource_key', 'gems',
    'name', '宝石',
    'icon', '💎',
    'amount', 75
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'steel' LIMIT 1),
    'resource_key', 'steel',
    'name', '鋼鉄',
    'icon', '⚙️',
    'amount', 150
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'coal' LIMIT 1),
    'resource_key', 'coal',
    'name', '石炭',
    'icon', '⬛',
    'amount', 225
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'bakust_lv45';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 2, 3, 75000, 750, 75,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 30000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 18000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 300
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gold' LIMIT 1),
    'resource_key', 'gold',
    'name', '金',
    'icon', '💰',
    'amount', 112
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'bakust_lv45';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 4, 10, 30000, 300, 30,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 12000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 7200
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 150
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'bakust_lv45';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 11, 50, 6000, 60, 6,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 4500
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'bakust_lv45';

-- Lv50 behemoth_lv50
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 1, 1, 200000, 2000, 200,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 80000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 48000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'stone' LIMIT 1),
    'resource_key', 'stone',
    'name', '石材',
    'icon', '🪨',
    'amount', 32000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'bronze' LIMIT 1),
    'resource_key', 'bronze',
    'name', '青銅',
    'icon', '🔶',
    'amount', 4000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'herbs' LIMIT 1),
    'resource_key', 'herbs',
    'name', '薬草',
    'icon', '🌿',
    'amount', 2400
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'cloth' LIMIT 1),
    'resource_key', 'cloth',
    'name', '布',
    'icon', '🧵',
    'amount', 1600
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'horses' LIMIT 1),
    'resource_key', 'horses',
    'name', '馬',
    'icon', '🐴',
    'amount', 1200
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'bandages' LIMIT 1),
    'resource_key', 'bandages',
    'name', '包帯',
    'icon', '🩹',
    'amount', 2000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 800
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'glass' LIMIT 1),
    'resource_key', 'glass',
    'name', 'ガラス',
    'icon', '🔮',
    'amount', 600
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'marble' LIMIT 1),
    'resource_key', 'marble',
    'name', '大理石',
    'icon', '🏛️',
    'amount', 500
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'sulfur' LIMIT 1),
    'resource_key', 'sulfur',
    'name', '硫黄',
    'icon', '🔶',
    'amount', 400
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gold' LIMIT 1),
    'resource_key', 'gold',
    'name', '金',
    'icon', '💰',
    'amount', 300
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'medicine' LIMIT 1),
    'resource_key', 'medicine',
    'name', '医薬品',
    'icon', '💊',
    'amount', 250
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gems' LIMIT 1),
    'resource_key', 'gems',
    'name', '宝石',
    'icon', '💎',
    'amount', 50
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'steel' LIMIT 1),
    'resource_key', 'steel',
    'name', '鋼鉄',
    'icon', '⚙️',
    'amount', 100
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'coal' LIMIT 1),
    'resource_key', 'coal',
    'name', '石炭',
    'icon', '⬛',
    'amount', 150
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'knowledge' LIMIT 1),
    'resource_key', 'knowledge',
    'name', '知識',
    'icon', '📚',
    'amount', 80
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'behemoth_lv50';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 2, 3, 100000, 1000, 100,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 40000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 24000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 400
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gold' LIMIT 1),
    'resource_key', 'gold',
    'name', '金',
    'icon', '💰',
    'amount', 150
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gems' LIMIT 1),
    'resource_key', 'gems',
    'name', '宝石',
    'icon', '💎',
    'amount', 25
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'steel' LIMIT 1),
    'resource_key', 'steel',
    'name', '鋼鉄',
    'icon', '⚙️',
    'amount', 50
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'behemoth_lv50';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 4, 10, 50000, 500, 50,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 16000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 9600
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 200
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'coal' LIMIT 1),
    'resource_key', 'coal',
    'name', '石炭',
    'icon', '⬛',
    'amount', 75
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'behemoth_lv50';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 11, 50, 10000, 100, 10,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 6000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 3600
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'behemoth_lv50';

-- Lv55 oceano_serpent_lv55
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 1, 1, 300000, 3000, 300,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 120000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 72000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'stone' LIMIT 1),
    'resource_key', 'stone',
    'name', '石材',
    'icon', '🪨',
    'amount', 48000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 1200
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gold' LIMIT 1),
    'resource_key', 'gold',
    'name', '金',
    'icon', '💰',
    'amount', 450
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gems' LIMIT 1),
    'resource_key', 'gems',
    'name', '宝石',
    'icon', '💎',
    'amount', 150
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'steel' LIMIT 1),
    'resource_key', 'steel',
    'name', '鋼鉄',
    'icon', '⚙️',
    'amount', 300
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'coal' LIMIT 1),
    'resource_key', 'coal',
    'name', '石炭',
    'icon', '⬛',
    'amount', 450
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'knowledge' LIMIT 1),
    'resource_key', 'knowledge',
    'name', '知識',
    'icon', '📚',
    'amount', 120
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'spices' LIMIT 1),
    'resource_key', 'spices',
    'name', '香辛料',
    'icon', '🌶️',
    'amount', 90
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gunpowder' LIMIT 1),
    'resource_key', 'gunpowder',
    'name', '火薬',
    'icon', '💥',
    'amount', 60
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'oceano_serpent_lv55';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 2, 3, 150000, 1500, 150,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 60000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 36000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 600
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gold' LIMIT 1),
    'resource_key', 'gold',
    'name', '金',
    'icon', '💰',
    'amount', 225
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gems' LIMIT 1),
    'resource_key', 'gems',
    'name', '宝石',
    'icon', '💎',
    'amount', 75
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'oceano_serpent_lv55';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 4, 10, 60000, 600, 60,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 24000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 14400
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 300
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'oceano_serpent_lv55';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 11, 50, 12000, 120, 12,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 9000
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'oceano_serpent_lv55';

-- Lv60 chaos_dragon_lv60
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 1, 1, 400000, 4000, 400,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 160000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 96000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'stone' LIMIT 1),
    'resource_key', 'stone',
    'name', '石材',
    'icon', '🪨',
    'amount', 64000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 1600
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gold' LIMIT 1),
    'resource_key', 'gold',
    'name', '金',
    'icon', '💰',
    'amount', 600
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'medicine' LIMIT 1),
    'resource_key', 'medicine',
    'name', '医薬品',
    'icon', '💊',
    'amount', 500
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gems' LIMIT 1),
    'resource_key', 'gems',
    'name', '宝石',
    'icon', '💎',
    'amount', 100
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'steel' LIMIT 1),
    'resource_key', 'steel',
    'name', '鋼鉄',
    'icon', '⚙️',
    'amount', 200
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'coal' LIMIT 1),
    'resource_key', 'coal',
    'name', '石炭',
    'icon', '⬛',
    'amount', 300
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'knowledge' LIMIT 1),
    'resource_key', 'knowledge',
    'name', '知識',
    'icon', '📚',
    'amount', 160
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'spices' LIMIT 1),
    'resource_key', 'spices',
    'name', '香辛料',
    'icon', '🌶️',
    'amount', 120
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gunpowder' LIMIT 1),
    'resource_key', 'gunpowder',
    'name', '火薬',
    'icon', '💥',
    'amount', 80
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'oil' LIMIT 1),
    'resource_key', 'oil',
    'name', '石油',
    'icon', '🛢️',
    'amount', 100
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'chaos_dragon_lv60';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 2, 3, 200000, 2000, 200,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 80000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 48000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 800
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gold' LIMIT 1),
    'resource_key', 'gold',
    'name', '金',
    'icon', '💰',
    'amount', 300
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gems' LIMIT 1),
    'resource_key', 'gems',
    'name', '宝石',
    'icon', '💎',
    'amount', 50
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'steel' LIMIT 1),
    'resource_key', 'steel',
    'name', '鋼鉄',
    'icon', '⚙️',
    'amount', 100
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gunpowder' LIMIT 1),
    'resource_key', 'gunpowder',
    'name', '火薬',
    'icon', '💥',
    'amount', 40
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'chaos_dragon_lv60';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 4, 10, 100000, 1000, 100,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 32000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 19200
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 400
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'coal' LIMIT 1),
    'resource_key', 'coal',
    'name', '石炭',
    'icon', '⬛',
    'amount', 150
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'chaos_dragon_lv60';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 11, 50, 20000, 200, 20,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 12000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 7200
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'chaos_dragon_lv60';

-- Lv65 troll_avatar_lv65
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 1, 1, 600000, 6000, 600,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 240000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 144000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'stone' LIMIT 1),
    'resource_key', 'stone',
    'name', '石材',
    'icon', '🪨',
    'amount', 96000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 2400
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gold' LIMIT 1),
    'resource_key', 'gold',
    'name', '金',
    'icon', '💰',
    'amount', 900
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gems' LIMIT 1),
    'resource_key', 'gems',
    'name', '宝石',
    'icon', '💎',
    'amount', 300
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'steel' LIMIT 1),
    'resource_key', 'steel',
    'name', '鋼鉄',
    'icon', '⚙️',
    'amount', 600
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'coal' LIMIT 1),
    'resource_key', 'coal',
    'name', '石炭',
    'icon', '⬛',
    'amount', 900
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'knowledge' LIMIT 1),
    'resource_key', 'knowledge',
    'name', '知識',
    'icon', '📚',
    'amount', 240
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'spices' LIMIT 1),
    'resource_key', 'spices',
    'name', '香辛料',
    'icon', '🌶️',
    'amount', 180
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gunpowder' LIMIT 1),
    'resource_key', 'gunpowder',
    'name', '火薬',
    'icon', '💥',
    'amount', 120
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'oil' LIMIT 1),
    'resource_key', 'oil',
    'name', '石油',
    'icon', '🛢️',
    'amount', 150
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'crystal' LIMIT 1),
    'resource_key', 'crystal',
    'name', '文明クリスタル',
    'icon', '💎',
    'amount', 75
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'troll_avatar_lv65';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 2, 3, 300000, 3000, 300,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 120000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 72000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 1200
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gold' LIMIT 1),
    'resource_key', 'gold',
    'name', '金',
    'icon', '💰',
    'amount', 450
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gems' LIMIT 1),
    'resource_key', 'gems',
    'name', '宝石',
    'icon', '💎',
    'amount', 150
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'steel' LIMIT 1),
    'resource_key', 'steel',
    'name', '鋼鉄',
    'icon', '⚙️',
    'amount', 300
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'troll_avatar_lv65';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 4, 10, 120000, 1200, 120,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 48000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 28800
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 600
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'troll_avatar_lv65';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 11, 50, 24000, 240, 24,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 18000
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'troll_avatar_lv65';

-- Lv70 god_of_war_lv70
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 1, 1, 800000, 8000, 800,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 320000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 192000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'stone' LIMIT 1),
    'resource_key', 'stone',
    'name', '石材',
    'icon', '🪨',
    'amount', 128000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 3200
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gold' LIMIT 1),
    'resource_key', 'gold',
    'name', '金',
    'icon', '💰',
    'amount', 1200
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gems' LIMIT 1),
    'resource_key', 'gems',
    'name', '宝石',
    'icon', '💎',
    'amount', 200
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'steel' LIMIT 1),
    'resource_key', 'steel',
    'name', '鋼鉄',
    'icon', '⚙️',
    'amount', 400
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'coal' LIMIT 1),
    'resource_key', 'coal',
    'name', '石炭',
    'icon', '⬛',
    'amount', 600
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'knowledge' LIMIT 1),
    'resource_key', 'knowledge',
    'name', '知識',
    'icon', '📚',
    'amount', 320
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'spices' LIMIT 1),
    'resource_key', 'spices',
    'name', '香辛料',
    'icon', '🌶️',
    'amount', 240
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gunpowder' LIMIT 1),
    'resource_key', 'gunpowder',
    'name', '火薬',
    'icon', '💥',
    'amount', 160
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'oil' LIMIT 1),
    'resource_key', 'oil',
    'name', '石油',
    'icon', '🛢️',
    'amount', 200
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'crystal' LIMIT 1),
    'resource_key', 'crystal',
    'name', '文明クリスタル',
    'icon', '💎',
    'amount', 50
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'rubber' LIMIT 1),
    'resource_key', 'rubber',
    'name', 'ゴム',
    'icon', '⚫',
    'amount', 80
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'mana' LIMIT 1),
    'resource_key', 'mana',
    'name', 'マナ',
    'icon', '✨',
    'amount', 40
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'god_of_war_lv70';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 2, 3, 400000, 4000, 400,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 160000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 96000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 1600
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gold' LIMIT 1),
    'resource_key', 'gold',
    'name', '金',
    'icon', '💰',
    'amount', 600
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gems' LIMIT 1),
    'resource_key', 'gems',
    'name', '宝石',
    'icon', '💎',
    'amount', 100
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'steel' LIMIT 1),
    'resource_key', 'steel',
    'name', '鋼鉄',
    'icon', '⚙️',
    'amount', 200
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gunpowder' LIMIT 1),
    'resource_key', 'gunpowder',
    'name', '火薬',
    'icon', '💥',
    'amount', 80
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'god_of_war_lv70';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 4, 10, 200000, 2000, 200,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 64000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 38400
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 800
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'coal' LIMIT 1),
    'resource_key', 'coal',
    'name', '石炭',
    'icon', '⬛',
    'amount', 300
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'god_of_war_lv70';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 11, 50, 40000, 400, 40,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 24000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 14400
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'god_of_war_lv70';

-- Lv75 condett_lv75
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 1, 1, 1200000, 12000, 1200,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 480000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 288000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'stone' LIMIT 1),
    'resource_key', 'stone',
    'name', '石材',
    'icon', '🪨',
    'amount', 192000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 4800
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gold' LIMIT 1),
    'resource_key', 'gold',
    'name', '金',
    'icon', '💰',
    'amount', 1800
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gems' LIMIT 1),
    'resource_key', 'gems',
    'name', '宝石',
    'icon', '💎',
    'amount', 600
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'steel' LIMIT 1),
    'resource_key', 'steel',
    'name', '鋼鉄',
    'icon', '⚙️',
    'amount', 1200
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'knowledge' LIMIT 1),
    'resource_key', 'knowledge',
    'name', '知識',
    'icon', '📚',
    'amount', 480
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gunpowder' LIMIT 1),
    'resource_key', 'gunpowder',
    'name', '火薬',
    'icon', '💥',
    'amount', 240
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'oil' LIMIT 1),
    'resource_key', 'oil',
    'name', '石油',
    'icon', '🛢️',
    'amount', 300
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'crystal' LIMIT 1),
    'resource_key', 'crystal',
    'name', '文明クリスタル',
    'icon', '💎',
    'amount', 150
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'rubber' LIMIT 1),
    'resource_key', 'rubber',
    'name', 'ゴム',
    'icon', '⚫',
    'amount', 240
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'mana' LIMIT 1),
    'resource_key', 'mana',
    'name', 'マナ',
    'icon', '✨',
    'amount', 120
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'electronics' LIMIT 1),
    'resource_key', 'electronics',
    'name', '電子部品',
    'icon', '🔌',
    'amount', 180
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'titanium' LIMIT 1),
    'resource_key', 'titanium',
    'name', 'チタン',
    'icon', '🔩',
    'amount', 150
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'condett_lv75';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 2, 3, 600000, 6000, 600,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 240000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 144000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 2400
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gold' LIMIT 1),
    'resource_key', 'gold',
    'name', '金',
    'icon', '💰',
    'amount', 900
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gems' LIMIT 1),
    'resource_key', 'gems',
    'name', '宝石',
    'icon', '💎',
    'amount', 300
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'steel' LIMIT 1),
    'resource_key', 'steel',
    'name', '鋼鉄',
    'icon', '⚙️',
    'amount', 600
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'condett_lv75';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 4, 10, 240000, 2400, 240,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 96000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 57600
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 1200
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'condett_lv75';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 11, 50, 48000, 480, 48,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 36000
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'condett_lv75';

-- Lv80 world_eater_lv80
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 1, 1, 1600000, 16000, 1600,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 640000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 384000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'stone' LIMIT 1),
    'resource_key', 'stone',
    'name', '石材',
    'icon', '🪨',
    'amount', 256000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 6400
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gold' LIMIT 1),
    'resource_key', 'gold',
    'name', '金',
    'icon', '💰',
    'amount', 2400
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gems' LIMIT 1),
    'resource_key', 'gems',
    'name', '宝石',
    'icon', '💎',
    'amount', 400
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'steel' LIMIT 1),
    'resource_key', 'steel',
    'name', '鋼鉄',
    'icon', '⚙️',
    'amount', 800
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'coal' LIMIT 1),
    'resource_key', 'coal',
    'name', '石炭',
    'icon', '⬛',
    'amount', 1200
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'knowledge' LIMIT 1),
    'resource_key', 'knowledge',
    'name', '知識',
    'icon', '📚',
    'amount', 640
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gunpowder' LIMIT 1),
    'resource_key', 'gunpowder',
    'name', '火薬',
    'icon', '💥',
    'amount', 320
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'oil' LIMIT 1),
    'resource_key', 'oil',
    'name', '石油',
    'icon', '🛢️',
    'amount', 400
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'crystal' LIMIT 1),
    'resource_key', 'crystal',
    'name', '文明クリスタル',
    'icon', '💎',
    'amount', 100
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'rubber' LIMIT 1),
    'resource_key', 'rubber',
    'name', 'ゴム',
    'icon', '⚫',
    'amount', 160
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'mana' LIMIT 1),
    'resource_key', 'mana',
    'name', 'マナ',
    'icon', '✨',
    'amount', 80
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'electronics' LIMIT 1),
    'resource_key', 'electronics',
    'name', '電子部品',
    'icon', '🔌',
    'amount', 120
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'titanium' LIMIT 1),
    'resource_key', 'titanium',
    'name', 'チタン',
    'icon', '🔩',
    'amount', 100
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'uranium' LIMIT 1),
    'resource_key', 'uranium',
    'name', 'ウラン',
    'icon', '☢️',
    'amount', 20
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'world_eater_lv80';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 2, 3, 800000, 8000, 800,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 320000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 192000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 3200
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gold' LIMIT 1),
    'resource_key', 'gold',
    'name', '金',
    'icon', '💰',
    'amount', 1200
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gems' LIMIT 1),
    'resource_key', 'gems',
    'name', '宝石',
    'icon', '💎',
    'amount', 200
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'steel' LIMIT 1),
    'resource_key', 'steel',
    'name', '鋼鉄',
    'icon', '⚙️',
    'amount', 400
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gunpowder' LIMIT 1),
    'resource_key', 'gunpowder',
    'name', '火薬',
    'icon', '💥',
    'amount', 160
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'uranium' LIMIT 1),
    'resource_key', 'uranium',
    'name', 'ウラン',
    'icon', '☢️',
    'amount', 10
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'world_eater_lv80';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 4, 10, 400000, 4000, 400,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 128000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 76800
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 1600
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'coal' LIMIT 1),
    'resource_key', 'coal',
    'name', '石炭',
    'icon', '⬛',
    'amount', 600
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'world_eater_lv80';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 11, 50, 80000, 800, 80,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 48000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 28800
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'world_eater_lv80';

-- Lv85 beast_legion_lv85
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 1, 1, 2400000, 24000, 2400,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 960000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 576000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'stone' LIMIT 1),
    'resource_key', 'stone',
    'name', '石材',
    'icon', '🪨',
    'amount', 384000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 9600
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gold' LIMIT 1),
    'resource_key', 'gold',
    'name', '金',
    'icon', '💰',
    'amount', 3600
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gems' LIMIT 1),
    'resource_key', 'gems',
    'name', '宝石',
    'icon', '💎',
    'amount', 1200
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'steel' LIMIT 1),
    'resource_key', 'steel',
    'name', '鋼鉄',
    'icon', '⚙️',
    'amount', 2400
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'knowledge' LIMIT 1),
    'resource_key', 'knowledge',
    'name', '知識',
    'icon', '📚',
    'amount', 960
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gunpowder' LIMIT 1),
    'resource_key', 'gunpowder',
    'name', '火薬',
    'icon', '💥',
    'amount', 480
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'oil' LIMIT 1),
    'resource_key', 'oil',
    'name', '石油',
    'icon', '🛢️',
    'amount', 600
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'crystal' LIMIT 1),
    'resource_key', 'crystal',
    'name', '文明クリスタル',
    'icon', '💎',
    'amount', 300
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'electronics' LIMIT 1),
    'resource_key', 'electronics',
    'name', '電子部品',
    'icon', '🔌',
    'amount', 360
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'titanium' LIMIT 1),
    'resource_key', 'titanium',
    'name', 'チタン',
    'icon', '🔩',
    'amount', 300
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'uranium' LIMIT 1),
    'resource_key', 'uranium',
    'name', 'ウラン',
    'icon', '☢️',
    'amount', 60
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'diamond' LIMIT 1),
    'resource_key', 'diamond',
    'name', '文明ダイヤモンド',
    'icon', '💠',
    'amount', 45
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'plutonium' LIMIT 1),
    'resource_key', 'plutonium',
    'name', 'プルトニウム',
    'icon', '☢️',
    'amount', 37
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'beast_legion_lv85';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 2, 3, 1200000, 12000, 1200,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 480000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 288000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 4800
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gold' LIMIT 1),
    'resource_key', 'gold',
    'name', '金',
    'icon', '💰',
    'amount', 1800
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gems' LIMIT 1),
    'resource_key', 'gems',
    'name', '宝石',
    'icon', '💎',
    'amount', 600
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'uranium' LIMIT 1),
    'resource_key', 'uranium',
    'name', 'ウラン',
    'icon', '☢️',
    'amount', 30
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'beast_legion_lv85';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 4, 10, 480000, 4800, 480,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 192000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 115200
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 2400
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'beast_legion_lv85';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 11, 50, 96000, 960, 96,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 72000
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'beast_legion_lv85';

-- Lv90 void_lord_lv90
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 1, 1, 3200000, 32000, 3200,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 1280000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 768000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'stone' LIMIT 1),
    'resource_key', 'stone',
    'name', '石材',
    'icon', '🪨',
    'amount', 512000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 12800
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gold' LIMIT 1),
    'resource_key', 'gold',
    'name', '金',
    'icon', '💰',
    'amount', 4800
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gems' LIMIT 1),
    'resource_key', 'gems',
    'name', '宝石',
    'icon', '💎',
    'amount', 800
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'steel' LIMIT 1),
    'resource_key', 'steel',
    'name', '鋼鉄',
    'icon', '⚙️',
    'amount', 1600
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'knowledge' LIMIT 1),
    'resource_key', 'knowledge',
    'name', '知識',
    'icon', '📚',
    'amount', 1280
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gunpowder' LIMIT 1),
    'resource_key', 'gunpowder',
    'name', '火薬',
    'icon', '💥',
    'amount', 640
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'oil' LIMIT 1),
    'resource_key', 'oil',
    'name', '石油',
    'icon', '🛢️',
    'amount', 800
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'crystal' LIMIT 1),
    'resource_key', 'crystal',
    'name', '文明クリスタル',
    'icon', '💎',
    'amount', 200
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'electronics' LIMIT 1),
    'resource_key', 'electronics',
    'name', '電子部品',
    'icon', '🔌',
    'amount', 240
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'titanium' LIMIT 1),
    'resource_key', 'titanium',
    'name', 'チタン',
    'icon', '🔩',
    'amount', 200
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'uranium' LIMIT 1),
    'resource_key', 'uranium',
    'name', 'ウラン',
    'icon', '☢️',
    'amount', 40
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'diamond' LIMIT 1),
    'resource_key', 'diamond',
    'name', '文明ダイヤモンド',
    'icon', '💠',
    'amount', 30
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'plutonium' LIMIT 1),
    'resource_key', 'plutonium',
    'name', 'プルトニウム',
    'icon', '☢️',
    'amount', 25
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'silicon' LIMIT 1),
    'resource_key', 'silicon',
    'name', 'シリコン',
    'icon', '🔲',
    'amount', 100
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'rare_earth' LIMIT 1),
    'resource_key', 'rare_earth',
    'name', 'レアアース',
    'icon', '💫',
    'amount', 80
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'void_lord_lv90';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 2, 3, 1600000, 16000, 1600,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 640000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 384000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 6400
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gold' LIMIT 1),
    'resource_key', 'gold',
    'name', '金',
    'icon', '💰',
    'amount', 2400
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gems' LIMIT 1),
    'resource_key', 'gems',
    'name', '宝石',
    'icon', '💎',
    'amount', 400
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'steel' LIMIT 1),
    'resource_key', 'steel',
    'name', '鋼鉄',
    'icon', '⚙️',
    'amount', 800
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'uranium' LIMIT 1),
    'resource_key', 'uranium',
    'name', 'ウラン',
    'icon', '☢️',
    'amount', 20
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'diamond' LIMIT 1),
    'resource_key', 'diamond',
    'name', '文明ダイヤモンド',
    'icon', '💠',
    'amount', 15
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'silicon' LIMIT 1),
    'resource_key', 'silicon',
    'name', 'シリコン',
    'icon', '🔲',
    'amount', 50
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'void_lord_lv90';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 4, 10, 800000, 8000, 800,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 256000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 153600
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 3200
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'coal' LIMIT 1),
    'resource_key', 'coal',
    'name', '石炭',
    'icon', '⬛',
    'amount', 1200
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'void_lord_lv90';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 11, 50, 160000, 1600, 160,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 96000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 57600
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'void_lord_lv90';

-- Lv95 epic_golem_lv95
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 1, 1, 4000000, 40000, 4000,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 1600000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 960000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'stone' LIMIT 1),
    'resource_key', 'stone',
    'name', '石材',
    'icon', '🪨',
    'amount', 640000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 16000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gold' LIMIT 1),
    'resource_key', 'gold',
    'name', '金',
    'icon', '💰',
    'amount', 6000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gems' LIMIT 1),
    'resource_key', 'gems',
    'name', '宝石',
    'icon', '💎',
    'amount', 2000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'steel' LIMIT 1),
    'resource_key', 'steel',
    'name', '鋼鉄',
    'icon', '⚙️',
    'amount', 4000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'knowledge' LIMIT 1),
    'resource_key', 'knowledge',
    'name', '知識',
    'icon', '📚',
    'amount', 1600
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'oil' LIMIT 1),
    'resource_key', 'oil',
    'name', '石油',
    'icon', '🛢️',
    'amount', 1000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'electronics' LIMIT 1),
    'resource_key', 'electronics',
    'name', '電子部品',
    'icon', '🔌',
    'amount', 600
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'titanium' LIMIT 1),
    'resource_key', 'titanium',
    'name', 'チタン',
    'icon', '🔩',
    'amount', 500
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'uranium' LIMIT 1),
    'resource_key', 'uranium',
    'name', 'ウラン',
    'icon', '☢️',
    'amount', 100
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'diamond' LIMIT 1),
    'resource_key', 'diamond',
    'name', '文明ダイヤモンド',
    'icon', '💠',
    'amount', 75
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'plutonium' LIMIT 1),
    'resource_key', 'plutonium',
    'name', 'プルトニウム',
    'icon', '☢️',
    'amount', 62
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'silicon' LIMIT 1),
    'resource_key', 'silicon',
    'name', 'シリコン',
    'icon', '🔲',
    'amount', 250
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'rare_earth' LIMIT 1),
    'resource_key', 'rare_earth',
    'name', 'レアアース',
    'icon', '💫',
    'amount', 200
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'quantum_crystal' LIMIT 1),
    'resource_key', 'quantum_crystal',
    'name', '量子結晶',
    'icon', '🔮',
    'amount', 30
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'ai_core' LIMIT 1),
    'resource_key', 'ai_core',
    'name', 'AIコア',
    'icon', '🧠',
    'amount', 22
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'epic_golem_lv95';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 2, 3, 2000000, 20000, 2000,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 800000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 480000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 8000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gold' LIMIT 1),
    'resource_key', 'gold',
    'name', '金',
    'icon', '💰',
    'amount', 3000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gems' LIMIT 1),
    'resource_key', 'gems',
    'name', '宝石',
    'icon', '💎',
    'amount', 1000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'uranium' LIMIT 1),
    'resource_key', 'uranium',
    'name', 'ウラン',
    'icon', '☢️',
    'amount', 50
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'silicon' LIMIT 1),
    'resource_key', 'silicon',
    'name', 'シリコン',
    'icon', '🔲',
    'amount', 125
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'epic_golem_lv95';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 4, 10, 800000, 8000, 800,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 320000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 192000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 4000
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'epic_golem_lv95';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 11, 50, 160000, 1600, 160,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 120000
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'epic_golem_lv95';

-- Lv100 cosmic_entity_lv100
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 1, 1, 5000000, 50000, 5000,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 2000000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 1200000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'stone' LIMIT 1),
    'resource_key', 'stone',
    'name', '石材',
    'icon', '🪨',
    'amount', 800000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 20000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gold' LIMIT 1),
    'resource_key', 'gold',
    'name', '金',
    'icon', '💰',
    'amount', 8000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gems' LIMIT 1),
    'resource_key', 'gems',
    'name', '宝石',
    'icon', '💎',
    'amount', 1500
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'steel' LIMIT 1),
    'resource_key', 'steel',
    'name', '鋼鉄',
    'icon', '⚙️',
    'amount', 3000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'knowledge' LIMIT 1),
    'resource_key', 'knowledge',
    'name', '知識',
    'icon', '📚',
    'amount', 2500
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gunpowder' LIMIT 1),
    'resource_key', 'gunpowder',
    'name', '火薬',
    'icon', '💥',
    'amount', 1200
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'oil' LIMIT 1),
    'resource_key', 'oil',
    'name', '石油',
    'icon', '🛢️',
    'amount', 1500
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'crystal' LIMIT 1),
    'resource_key', 'crystal',
    'name', '文明クリスタル',
    'icon', '💎',
    'amount', 400
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'electronics' LIMIT 1),
    'resource_key', 'electronics',
    'name', '電子部品',
    'icon', '🔌',
    'amount', 500
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'titanium' LIMIT 1),
    'resource_key', 'titanium',
    'name', 'チタン',
    'icon', '🔩',
    'amount', 400
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'uranium' LIMIT 1),
    'resource_key', 'uranium',
    'name', 'ウラン',
    'icon', '☢️',
    'amount', 80
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'diamond' LIMIT 1),
    'resource_key', 'diamond',
    'name', '文明ダイヤモンド',
    'icon', '💠',
    'amount', 60
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'plutonium' LIMIT 1),
    'resource_key', 'plutonium',
    'name', 'プルトニウム',
    'icon', '☢️',
    'amount', 50
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'silicon' LIMIT 1),
    'resource_key', 'silicon',
    'name', 'シリコン',
    'icon', '🔲',
    'amount', 200
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'rare_earth' LIMIT 1),
    'resource_key', 'rare_earth',
    'name', 'レアアース',
    'icon', '💫',
    'amount', 150
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'quantum_crystal' LIMIT 1),
    'resource_key', 'quantum_crystal',
    'name', '量子結晶',
    'icon', '🔮',
    'amount', 20
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'ai_core' LIMIT 1),
    'resource_key', 'ai_core',
    'name', 'AIコア',
    'icon', '🧠',
    'amount', 15
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gene_sample' LIMIT 1),
    'resource_key', 'gene_sample',
    'name', '遺伝子サンプル',
    'icon', '🧬',
    'amount', 25
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'dark_matter' LIMIT 1),
    'resource_key', 'dark_matter',
    'name', 'ダークマター',
    'icon', '🌌',
    'amount', 10
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'antimatter' LIMIT 1),
    'resource_key', 'antimatter',
    'name', '反物質',
    'icon', '💥',
    'amount', 5
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'cosmic_entity_lv100';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 2, 3, 2500000, 25000, 2500,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 1000000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 600000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 10000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gold' LIMIT 1),
    'resource_key', 'gold',
    'name', '金',
    'icon', '💰',
    'amount', 4000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gems' LIMIT 1),
    'resource_key', 'gems',
    'name', '宝石',
    'icon', '💎',
    'amount', 750
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'steel' LIMIT 1),
    'resource_key', 'steel',
    'name', '鋼鉄',
    'icon', '⚙️',
    'amount', 1500
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'uranium' LIMIT 1),
    'resource_key', 'uranium',
    'name', 'ウラン',
    'icon', '☢️',
    'amount', 40
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'diamond' LIMIT 1),
    'resource_key', 'diamond',
    'name', '文明ダイヤモンド',
    'icon', '💠',
    'amount', 30
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'silicon' LIMIT 1),
    'resource_key', 'silicon',
    'name', 'シリコン',
    'icon', '🔲',
    'amount', 100
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'quantum_crystal' LIMIT 1),
    'resource_key', 'quantum_crystal',
    'name', '量子結晶',
    'icon', '🔮',
    'amount', 10
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'ai_core' LIMIT 1),
    'resource_key', 'ai_core',
    'name', 'AIコア',
    'icon', '🧠',
    'amount', 7
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'cosmic_entity_lv100';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 4, 10, 1000000, 10000, 1000,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 400000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 240000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 5000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'coal' LIMIT 1),
    'resource_key', 'coal',
    'name', '石炭',
    'icon', '⬛',
    'amount', 2000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'silicon' LIMIT 1),
    'resource_key', 'silicon',
    'name', 'シリコン',
    'icon', '🔲',
    'amount', 50
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'cosmic_entity_lv100';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 11, 50, 250000, 2500, 250,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 150000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 90000
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'cosmic_entity_lv100';

-- ===============================================
-- ベテランボスの報酬（コイン2倍、資源2倍）
-- ===============================================

-- Lv10 titan_lv10_veteran
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 1, 1, 20000, 100, 10,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 10000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 6000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'stone' LIMIT 1),
    'resource_key', 'stone',
    'name', '石材',
    'icon', '🪨',
    'amount', 4000
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'titan_lv10_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 2, 3, 10000, 50, 5,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 5000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 3000
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'titan_lv10_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 4, 10, 4000, 20, 2,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 2000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 1200
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'titan_lv10_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 11, 50, 1000, 5, 1,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 1000
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'titan_lv10_veteran';

-- Lv20 hydra_lv20_veteran
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 1, 1, 50000, 250, 25,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 20000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 12000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'stone' LIMIT 1),
    'resource_key', 'stone',
    'name', '石材',
    'icon', '🪨',
    'amount', 8000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'bronze' LIMIT 1),
    'resource_key', 'bronze',
    'name', '青銅',
    'icon', '🔶',
    'amount', 1000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'herbs' LIMIT 1),
    'resource_key', 'herbs',
    'name', '薬草',
    'icon', '🌿',
    'amount', 600
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'hydra_lv20_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 2, 3, 24000, 120, 12,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 10000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 6000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'bronze' LIMIT 1),
    'resource_key', 'bronze',
    'name', '青銅',
    'icon', '🔶',
    'amount', 500
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'hydra_lv20_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 4, 10, 10000, 50, 5,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 4000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 2400
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'hydra_lv20_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 11, 50, 2000, 10, 2,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 1600
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'hydra_lv20_veteran';

-- Lv30 phoenix_lv30_veteran
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 1, 1, 100000, 500, 50,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 40000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 24000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'stone' LIMIT 1),
    'resource_key', 'stone',
    'name', '石材',
    'icon', '🪨',
    'amount', 16000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'bronze' LIMIT 1),
    'resource_key', 'bronze',
    'name', '青銅',
    'icon', '🔶',
    'amount', 2000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'herbs' LIMIT 1),
    'resource_key', 'herbs',
    'name', '薬草',
    'icon', '🌿',
    'amount', 1200
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'cloth' LIMIT 1),
    'resource_key', 'cloth',
    'name', '布',
    'icon', '🧵',
    'amount', 800
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'horses' LIMIT 1),
    'resource_key', 'horses',
    'name', '馬',
    'icon', '🐴',
    'amount', 600
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'bandages' LIMIT 1),
    'resource_key', 'bandages',
    'name', '包帯',
    'icon', '🩹',
    'amount', 1000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 400
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'phoenix_lv30_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 2, 3, 50000, 250, 25,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 20000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 12000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'bronze' LIMIT 1),
    'resource_key', 'bronze',
    'name', '青銅',
    'icon', '🔶',
    'amount', 1000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 200
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'phoenix_lv30_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 4, 10, 20000, 100, 10,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 8000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 4800
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'phoenix_lv30_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 11, 50, 4000, 20, 2,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 3000
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'phoenix_lv30_veteran';

-- Lv40 kraken_lv40_veteran
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 1, 1, 200000, 1000, 100,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 80000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 48000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'stone' LIMIT 1),
    'resource_key', 'stone',
    'name', '石材',
    'icon', '🪨',
    'amount', 32000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 800
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gold' LIMIT 1),
    'resource_key', 'gold',
    'name', '金',
    'icon', '💰',
    'amount', 300
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'marble' LIMIT 1),
    'resource_key', 'marble',
    'name', '大理石',
    'icon', '🏛️',
    'amount', 500
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'sulfur' LIMIT 1),
    'resource_key', 'sulfur',
    'name', '硫黄',
    'icon', '🔶',
    'amount', 400
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'kraken_lv40_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 2, 3, 100000, 500, 50,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 40000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 24000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 400
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gold' LIMIT 1),
    'resource_key', 'gold',
    'name', '金',
    'icon', '💰',
    'amount', 150
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'kraken_lv40_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 4, 10, 40000, 200, 20,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 16000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 9600
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'kraken_lv40_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 11, 50, 8000, 40, 4,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 6000
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'kraken_lv40_veteran';

-- Lv50 behemoth_lv50_veteran
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 1, 1, 400000, 2000, 200,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 160000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 96000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'stone' LIMIT 1),
    'resource_key', 'stone',
    'name', '石材',
    'icon', '🪨',
    'amount', 64000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 1600
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gold' LIMIT 1),
    'resource_key', 'gold',
    'name', '金',
    'icon', '💰',
    'amount', 600
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gems' LIMIT 1),
    'resource_key', 'gems',
    'name', '宝石',
    'icon', '💎',
    'amount', 100
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'steel' LIMIT 1),
    'resource_key', 'steel',
    'name', '鋼鉄',
    'icon', '⚙️',
    'amount', 200
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'coal' LIMIT 1),
    'resource_key', 'coal',
    'name', '石炭',
    'icon', '⬛',
    'amount', 300
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'knowledge' LIMIT 1),
    'resource_key', 'knowledge',
    'name', '知識',
    'icon', '📚',
    'amount', 160
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'behemoth_lv50_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 2, 3, 200000, 1000, 100,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 80000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 48000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 800
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gold' LIMIT 1),
    'resource_key', 'gold',
    'name', '金',
    'icon', '💰',
    'amount', 300
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gems' LIMIT 1),
    'resource_key', 'gems',
    'name', '宝石',
    'icon', '💎',
    'amount', 50
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'steel' LIMIT 1),
    'resource_key', 'steel',
    'name', '鋼鉄',
    'icon', '⚙️',
    'amount', 100
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'behemoth_lv50_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 4, 10, 100000, 500, 50,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 32000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 19200
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 400
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'behemoth_lv50_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 11, 50, 20000, 100, 10,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 12000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 7200
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'behemoth_lv50_veteran';

-- Lv60 chaos_dragon_lv60_veteran
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 1, 1, 800000, 4000, 400,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 320000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 192000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'stone' LIMIT 1),
    'resource_key', 'stone',
    'name', '石材',
    'icon', '🪨',
    'amount', 128000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 3200
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gold' LIMIT 1),
    'resource_key', 'gold',
    'name', '金',
    'icon', '💰',
    'amount', 1200
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gems' LIMIT 1),
    'resource_key', 'gems',
    'name', '宝石',
    'icon', '💎',
    'amount', 200
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'steel' LIMIT 1),
    'resource_key', 'steel',
    'name', '鋼鉄',
    'icon', '⚙️',
    'amount', 400
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gunpowder' LIMIT 1),
    'resource_key', 'gunpowder',
    'name', '火薬',
    'icon', '💥',
    'amount', 160
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'oil' LIMIT 1),
    'resource_key', 'oil',
    'name', '石油',
    'icon', '🛢️',
    'amount', 200
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'chaos_dragon_lv60_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 2, 3, 400000, 2000, 200,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 160000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 96000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 1600
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gold' LIMIT 1),
    'resource_key', 'gold',
    'name', '金',
    'icon', '💰',
    'amount', 600
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gems' LIMIT 1),
    'resource_key', 'gems',
    'name', '宝石',
    'icon', '💎',
    'amount', 100
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'chaos_dragon_lv60_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 4, 10, 200000, 1000, 100,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 64000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 38400
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 800
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'chaos_dragon_lv60_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 11, 50, 40000, 200, 20,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 24000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 14400
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'chaos_dragon_lv60_veteran';

-- Lv70 god_of_war_lv70_veteran
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 1, 1, 1600000, 8000, 800,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 640000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 384000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'stone' LIMIT 1),
    'resource_key', 'stone',
    'name', '石材',
    'icon', '🪨',
    'amount', 256000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 6400
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gold' LIMIT 1),
    'resource_key', 'gold',
    'name', '金',
    'icon', '💰',
    'amount', 2400
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gems' LIMIT 1),
    'resource_key', 'gems',
    'name', '宝石',
    'icon', '💎',
    'amount', 400
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'steel' LIMIT 1),
    'resource_key', 'steel',
    'name', '鋼鉄',
    'icon', '⚙️',
    'amount', 800
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gunpowder' LIMIT 1),
    'resource_key', 'gunpowder',
    'name', '火薬',
    'icon', '💥',
    'amount', 320
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'crystal' LIMIT 1),
    'resource_key', 'crystal',
    'name', '文明クリスタル',
    'icon', '💎',
    'amount', 100
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'mana' LIMIT 1),
    'resource_key', 'mana',
    'name', 'マナ',
    'icon', '✨',
    'amount', 80
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'god_of_war_lv70_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 2, 3, 800000, 4000, 400,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 320000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 192000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 3200
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gold' LIMIT 1),
    'resource_key', 'gold',
    'name', '金',
    'icon', '💰',
    'amount', 1200
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gems' LIMIT 1),
    'resource_key', 'gems',
    'name', '宝石',
    'icon', '💎',
    'amount', 200
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'god_of_war_lv70_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 4, 10, 400000, 2000, 200,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 128000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 76800
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 1600
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'god_of_war_lv70_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 11, 50, 80000, 400, 40,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 48000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 28800
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'god_of_war_lv70_veteran';

-- Lv80 world_eater_lv80_veteran
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 1, 1, 3200000, 16000, 1600,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 1280000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 768000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'stone' LIMIT 1),
    'resource_key', 'stone',
    'name', '石材',
    'icon', '🪨',
    'amount', 512000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 12800
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gold' LIMIT 1),
    'resource_key', 'gold',
    'name', '金',
    'icon', '💰',
    'amount', 4800
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gems' LIMIT 1),
    'resource_key', 'gems',
    'name', '宝石',
    'icon', '💎',
    'amount', 800
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'steel' LIMIT 1),
    'resource_key', 'steel',
    'name', '鋼鉄',
    'icon', '⚙️',
    'amount', 1600
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'electronics' LIMIT 1),
    'resource_key', 'electronics',
    'name', '電子部品',
    'icon', '🔌',
    'amount', 240
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'titanium' LIMIT 1),
    'resource_key', 'titanium',
    'name', 'チタン',
    'icon', '🔩',
    'amount', 200
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'uranium' LIMIT 1),
    'resource_key', 'uranium',
    'name', 'ウラン',
    'icon', '☢️',
    'amount', 40
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'world_eater_lv80_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 2, 3, 1600000, 8000, 800,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 640000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 384000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 6400
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gold' LIMIT 1),
    'resource_key', 'gold',
    'name', '金',
    'icon', '💰',
    'amount', 2400
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gems' LIMIT 1),
    'resource_key', 'gems',
    'name', '宝石',
    'icon', '💎',
    'amount', 400
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'uranium' LIMIT 1),
    'resource_key', 'uranium',
    'name', 'ウラン',
    'icon', '☢️',
    'amount', 20
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'world_eater_lv80_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 4, 10, 800000, 4000, 400,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 256000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 153600
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 3200
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'world_eater_lv80_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 11, 50, 160000, 800, 80,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 96000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 57600
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'world_eater_lv80_veteran';

-- Lv90 void_lord_lv90_veteran
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 1, 1, 6400000, 32000, 3200,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 2560000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 1536000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'stone' LIMIT 1),
    'resource_key', 'stone',
    'name', '石材',
    'icon', '🪨',
    'amount', 1024000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 25600
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gold' LIMIT 1),
    'resource_key', 'gold',
    'name', '金',
    'icon', '💰',
    'amount', 9600
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gems' LIMIT 1),
    'resource_key', 'gems',
    'name', '宝石',
    'icon', '💎',
    'amount', 1600
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'steel' LIMIT 1),
    'resource_key', 'steel',
    'name', '鋼鉄',
    'icon', '⚙️',
    'amount', 3200
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'uranium' LIMIT 1),
    'resource_key', 'uranium',
    'name', 'ウラン',
    'icon', '☢️',
    'amount', 80
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'diamond' LIMIT 1),
    'resource_key', 'diamond',
    'name', '文明ダイヤモンド',
    'icon', '💠',
    'amount', 60
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'plutonium' LIMIT 1),
    'resource_key', 'plutonium',
    'name', 'プルトニウム',
    'icon', '☢️',
    'amount', 50
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'silicon' LIMIT 1),
    'resource_key', 'silicon',
    'name', 'シリコン',
    'icon', '🔲',
    'amount', 200
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'rare_earth' LIMIT 1),
    'resource_key', 'rare_earth',
    'name', 'レアアース',
    'icon', '💫',
    'amount', 160
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'void_lord_lv90_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 2, 3, 3200000, 16000, 1600,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 1280000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 768000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 12800
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gold' LIMIT 1),
    'resource_key', 'gold',
    'name', '金',
    'icon', '💰',
    'amount', 4800
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gems' LIMIT 1),
    'resource_key', 'gems',
    'name', '宝石',
    'icon', '💎',
    'amount', 800
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'uranium' LIMIT 1),
    'resource_key', 'uranium',
    'name', 'ウラン',
    'icon', '☢️',
    'amount', 40
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'silicon' LIMIT 1),
    'resource_key', 'silicon',
    'name', 'シリコン',
    'icon', '🔲',
    'amount', 100
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'void_lord_lv90_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 4, 10, 1600000, 8000, 800,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 512000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 307200
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 6400
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'void_lord_lv90_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 11, 50, 320000, 1600, 160,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 192000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 115200
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'void_lord_lv90_veteran';

-- Lv100 cosmic_entity_lv100_veteran
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 1, 1, 10000000, 50000, 5000,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 4000000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 2400000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'stone' LIMIT 1),
    'resource_key', 'stone',
    'name', '石材',
    'icon', '🪨',
    'amount', 1600000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 40000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gold' LIMIT 1),
    'resource_key', 'gold',
    'name', '金',
    'icon', '💰',
    'amount', 16000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gems' LIMIT 1),
    'resource_key', 'gems',
    'name', '宝石',
    'icon', '💎',
    'amount', 3000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'steel' LIMIT 1),
    'resource_key', 'steel',
    'name', '鋼鉄',
    'icon', '⚙️',
    'amount', 6000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'knowledge' LIMIT 1),
    'resource_key', 'knowledge',
    'name', '知識',
    'icon', '📚',
    'amount', 5000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'uranium' LIMIT 1),
    'resource_key', 'uranium',
    'name', 'ウラン',
    'icon', '☢️',
    'amount', 160
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'diamond' LIMIT 1),
    'resource_key', 'diamond',
    'name', '文明ダイヤモンド',
    'icon', '💠',
    'amount', 120
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'plutonium' LIMIT 1),
    'resource_key', 'plutonium',
    'name', 'プルトニウム',
    'icon', '☢️',
    'amount', 100
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'silicon' LIMIT 1),
    'resource_key', 'silicon',
    'name', 'シリコン',
    'icon', '🔲',
    'amount', 400
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'rare_earth' LIMIT 1),
    'resource_key', 'rare_earth',
    'name', 'レアアース',
    'icon', '💫',
    'amount', 300
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'quantum_crystal' LIMIT 1),
    'resource_key', 'quantum_crystal',
    'name', '量子結晶',
    'icon', '🔮',
    'amount', 40
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'ai_core' LIMIT 1),
    'resource_key', 'ai_core',
    'name', 'AIコア',
    'icon', '🧠',
    'amount', 30
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gene_sample' LIMIT 1),
    'resource_key', 'gene_sample',
    'name', '遺伝子サンプル',
    'icon', '🧬',
    'amount', 50
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'dark_matter' LIMIT 1),
    'resource_key', 'dark_matter',
    'name', 'ダークマター',
    'icon', '🌌',
    'amount', 20
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'antimatter' LIMIT 1),
    'resource_key', 'antimatter',
    'name', '反物質',
    'icon', '💥',
    'amount', 10
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'cosmic_entity_lv100_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 2, 3, 5000000, 25000, 2500,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 2000000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 1200000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 20000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gold' LIMIT 1),
    'resource_key', 'gold',
    'name', '金',
    'icon', '💰',
    'amount', 8000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'gems' LIMIT 1),
    'resource_key', 'gems',
    'name', '宝石',
    'icon', '💎',
    'amount', 1500
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'uranium' LIMIT 1),
    'resource_key', 'uranium',
    'name', 'ウラン',
    'icon', '☢️',
    'amount', 80
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'silicon' LIMIT 1),
    'resource_key', 'silicon',
    'name', 'シリコン',
    'icon', '🔲',
    'amount', 200
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'quantum_crystal' LIMIT 1),
    'resource_key', 'quantum_crystal',
    'name', '量子結晶',
    'icon', '🔮',
    'amount', 20
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'ai_core' LIMIT 1),
    'resource_key', 'ai_core',
    'name', 'AIコア',
    'icon', '🧠',
    'amount', 14
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'cosmic_entity_lv100_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 4, 10, 2000000, 10000, 1000,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 800000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 480000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'iron' LIMIT 1),
    'resource_key', 'iron',
    'name', '鉄',
    'icon', '⚙️',
    'amount', 10000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'silicon' LIMIT 1),
    'resource_key', 'silicon',
    'name', 'シリコン',
    'icon', '🔲',
    'amount', 100
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'cosmic_entity_lv100_veteran';

INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT wb.id, 11, 50, 500000, 2500, 250,
JSON_ARRAY(
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'food' LIMIT 1),
    'resource_key', 'food',
    'name', '食料',
    'icon', '🍖',
    'amount', 300000
  ),
  JSON_OBJECT(
    'resource_type_id', (SELECT id FROM civilization_resource_types WHERE resource_key = 'wood' LIMIT 1),
    'resource_key', 'wood',
    'name', '木材',
    'icon', '🪵',
    'amount', 180000
  )
), NULL
FROM world_bosses wb WHERE wb.boss_key = 'cosmic_entity_lv100_veteran';

-- ===============================================
-- 完了メッセージ
-- ===============================================
SELECT 'World boss rewards overhaul 2026 completed. JSON with literal resource names and icons.' AS status;

