-- ===============================================
-- ワールドボス ドロップ報酬改定
-- クリスタルとダイヤモンドの報酬をレベルに関わらず一定の値に更新
-- 資源とコインの量は変更しない
-- ===============================================

USE microblog;

-- ===============================================
-- 報酬の新しい基準:
-- 1位: クリスタル 1000, ダイヤモンド 500
-- 2位〜3位: 1位の半分 (クリスタル 500, ダイヤモンド 250)
-- 4位〜5位: 1位の1/5 (クリスタル 200, ダイヤモンド 100)
-- 6位〜10位: 1位の1/10 (クリスタル 100, ダイヤモンド 50)
-- 11位〜50位: 1位の1/100 (クリスタル 10, ダイヤモンド 5)
-- ベテランの場合はクリスタルとダイヤモンドが2倍
-- ===============================================

-- 一時テーブルがすでに存在する場合は削除
DROP TEMPORARY TABLE IF EXISTS temp_4_10_rewards;
DROP TEMPORARY TABLE IF EXISTS temp_4_10_rewards_veteran;

-- トランザクション開始
START TRANSACTION;

-- ===============================================
-- 通常ボスの報酬更新 (ベテラン以外)
-- ===============================================

-- 1位の報酬更新
UPDATE world_boss_rewards wbr
JOIN world_bosses wb ON wbr.boss_id = wb.id
SET wbr.reward_crystals = 1000,
    wbr.reward_diamonds = 500
WHERE wbr.rank_start = 1 
  AND wbr.rank_end = 1
  AND (wb.labels IS NULL OR wb.labels NOT LIKE '%ベテラン%');

-- 2位〜3位の報酬更新
UPDATE world_boss_rewards wbr
JOIN world_bosses wb ON wbr.boss_id = wb.id
SET wbr.reward_crystals = 500,
    wbr.reward_diamonds = 250
WHERE wbr.rank_start = 2 
  AND wbr.rank_end = 3
  AND (wb.labels IS NULL OR wb.labels NOT LIKE '%ベテラン%');

-- 4位〜10位の範囲を4位〜5位と6位〜10位に分割するため、一時テーブルに保存
CREATE TEMPORARY TABLE IF NOT EXISTS temp_4_10_rewards AS
SELECT wbr.boss_id, wbr.reward_coins, wbr.reward_resources, wbr.reward_troops
FROM world_boss_rewards wbr
JOIN world_bosses wb ON wbr.boss_id = wb.id
WHERE wbr.rank_start = 4 
  AND wbr.rank_end = 10
  AND (wb.labels IS NULL OR wb.labels NOT LIKE '%ベテラン%');

-- 4位〜5位の新しいレコードを挿入 (1位の1/5: クリスタル200, ダイヤモンド100)
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT boss_id, 4, 5, reward_coins, 200, 100, reward_resources, reward_troops
FROM temp_4_10_rewards;

-- 6位〜10位の新しいレコードを挿入 (1位の1/10: クリスタル100, ダイヤモンド50)
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT boss_id, 6, 10, reward_coins, 100, 50, reward_resources, reward_troops
FROM temp_4_10_rewards;

-- INSERTが成功した後に既存の4位〜10位のレコードを削除
DELETE wbr FROM world_boss_rewards wbr
JOIN world_bosses wb ON wbr.boss_id = wb.id
WHERE wbr.rank_start = 4 
  AND wbr.rank_end = 10
  AND (wb.labels IS NULL OR wb.labels NOT LIKE '%ベテラン%');

-- 一時テーブルを削除
DROP TEMPORARY TABLE IF EXISTS temp_4_10_rewards;

-- 11位〜50位の報酬更新
UPDATE world_boss_rewards wbr
JOIN world_bosses wb ON wbr.boss_id = wb.id
SET wbr.reward_crystals = 10,
    wbr.reward_diamonds = 5
WHERE wbr.rank_start = 11 
  AND wbr.rank_end = 50
  AND (wb.labels IS NULL OR wb.labels NOT LIKE '%ベテラン%');

-- ===============================================
-- ベテランボスの報酬更新 (2倍)
-- ===============================================

-- 1位の報酬更新 (クリスタル2000, ダイヤモンド1000)
UPDATE world_boss_rewards wbr
JOIN world_bosses wb ON wbr.boss_id = wb.id
SET wbr.reward_crystals = 2000,
    wbr.reward_diamonds = 1000
WHERE wbr.rank_start = 1 
  AND wbr.rank_end = 1
  AND wb.labels LIKE '%ベテラン%';

-- 2位〜3位の報酬更新 (クリスタル1000, ダイヤモンド500)
UPDATE world_boss_rewards wbr
JOIN world_bosses wb ON wbr.boss_id = wb.id
SET wbr.reward_crystals = 1000,
    wbr.reward_diamonds = 500
WHERE wbr.rank_start = 2 
  AND wbr.rank_end = 3
  AND wb.labels LIKE '%ベテラン%';

-- 4位〜10位の範囲を4位〜5位と6位〜10位に分割するため、一時テーブルに保存
CREATE TEMPORARY TABLE IF NOT EXISTS temp_4_10_rewards_veteran AS
SELECT wbr.boss_id, wbr.reward_coins, wbr.reward_resources, wbr.reward_troops
FROM world_boss_rewards wbr
JOIN world_bosses wb ON wbr.boss_id = wb.id
WHERE wbr.rank_start = 4 
  AND wbr.rank_end = 10
  AND wb.labels LIKE '%ベテラン%';

-- 4位〜5位の新しいレコードを挿入 (クリスタル400, ダイヤモンド200)
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT boss_id, 4, 5, reward_coins, 400, 200, reward_resources, reward_troops
FROM temp_4_10_rewards_veteran;

-- 6位〜10位の新しいレコードを挿入 (クリスタル200, ダイヤモンド100)
INSERT INTO world_boss_rewards (boss_id, rank_start, rank_end, reward_coins, reward_crystals, reward_diamonds, reward_resources, reward_troops)
SELECT boss_id, 6, 10, reward_coins, 200, 100, reward_resources, reward_troops
FROM temp_4_10_rewards_veteran;

-- INSERTが成功した後に既存の4位〜10位のレコードを削除
DELETE wbr FROM world_boss_rewards wbr
JOIN world_bosses wb ON wbr.boss_id = wb.id
WHERE wbr.rank_start = 4 
  AND wbr.rank_end = 10
  AND wb.labels LIKE '%ベテラン%';

-- 一時テーブルを削除
DROP TEMPORARY TABLE IF EXISTS temp_4_10_rewards_veteran;

-- 11位〜50位の報酬更新 (クリスタル20, ダイヤモンド10)
UPDATE world_boss_rewards wbr
JOIN world_bosses wb ON wbr.boss_id = wb.id
SET wbr.reward_crystals = 20,
    wbr.reward_diamonds = 10
WHERE wbr.rank_start = 11 
  AND wbr.rank_end = 50
  AND wb.labels LIKE '%ベテラン%';

-- ===============================================
-- 完了メッセージ
-- ===============================================

-- トランザクションをコミット
COMMIT;

SELECT 'World boss drop rewards updated successfully' AS status;
