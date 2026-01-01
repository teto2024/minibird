-- ===============================================
-- MiniBird 機能追加・修正スキーマ 2026
-- 1. ヒーローイベントタスクの追加
-- 2. ヒーローイベントポイント報酬の追加
-- 3. ポータルボスのloot_tableの修正
-- ===============================================

USE microblog;

-- ===============================================
-- ⑦⑧ ヒーローイベントタスクとポイント報酬のデータ復元・増加
-- ===============================================

-- 既存のヒーローイベントIDを取得してタスクを追加
-- hero_event_jan_2026 イベント用のヒーローイベントIDを取得

-- まず既存タスクを削除しないで、新しいタスクを追加（INSERT IGNORE使用）
-- ヒーローイベントタスクを増加（種類も数も量も）
INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward) 
SELECT he.id, 'hero_login_1', 'イベント中ログイン1回', '毎日ログインしよう', '🏠', 'login', 1, 10
FROM hero_events he 
JOIN civilization_events ce ON he.event_id = ce.id 
WHERE ce.event_key = 'hero_event_jan_2026';

INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward) 
SELECT he.id, 'hero_login_3', 'イベント中ログイン3回', '3日間ログインしよう', '🏠', 'login', 3, 30
FROM hero_events he 
JOIN civilization_events ce ON he.event_id = ce.id 
WHERE ce.event_key = 'hero_event_jan_2026';

INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward) 
SELECT he.id, 'hero_login_7', 'イベント中ログイン7回', '7日間連続ログインしよう', '🏠', 'login', 7, 70
FROM hero_events he 
JOIN civilization_events ce ON he.event_id = ce.id 
WHERE ce.event_key = 'hero_event_jan_2026';

INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward) 
SELECT he.id, 'hero_battle_1', '戦闘に1回参加', '戦闘に1回参加しよう', '⚔️', 'battle', 1, 15
FROM hero_events he 
JOIN civilization_events ce ON he.event_id = ce.id 
WHERE ce.event_key = 'hero_event_jan_2026';

INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward) 
SELECT he.id, 'hero_battle_5', '戦闘に5回参加', '戦闘に5回参加しよう', '⚔️', 'battle', 5, 50
FROM hero_events he 
JOIN civilization_events ce ON he.event_id = ce.id 
WHERE ce.event_key = 'hero_event_jan_2026';

INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward) 
SELECT he.id, 'hero_battle_10', '戦闘に10回参加', '戦闘に10回参加しよう', '⚔️', 'battle', 10, 100
FROM hero_events he 
JOIN civilization_events ce ON he.event_id = ce.id 
WHERE ce.event_key = 'hero_event_jan_2026';

INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward) 
SELECT he.id, 'hero_gacha_1', 'ガチャを1回回す', 'ヒーローガチャを1回回そう', '🎰', 'gacha', 1, 20
FROM hero_events he 
JOIN civilization_events ce ON he.event_id = ce.id 
WHERE ce.event_key = 'hero_event_jan_2026';

INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward) 
SELECT he.id, 'hero_gacha_5', 'ガチャを5回回す', 'ヒーローガチャを5回回そう', '🎰', 'gacha', 5, 75
FROM hero_events he 
JOIN civilization_events ce ON he.event_id = ce.id 
WHERE ce.event_key = 'hero_event_jan_2026';

INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward) 
SELECT he.id, 'hero_gacha_10', 'ガチャを10回回す', 'ヒーローガチャを10回回そう', '🎰', 'gacha', 10, 150
FROM hero_events he 
JOIN civilization_events ce ON he.event_id = ce.id 
WHERE ce.event_key = 'hero_event_jan_2026';

INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward) 
SELECT he.id, 'hero_build_1', '建物を建設・アップグレード', '建物を1回建設またはアップグレードしよう', '🏗️', 'build', 1, 20
FROM hero_events he 
JOIN civilization_events ce ON he.event_id = ce.id 
WHERE ce.event_key = 'hero_event_jan_2026';

INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward) 
SELECT he.id, 'hero_train_10', '兵士を10体訓練', '兵士を10体訓練しよう', '🎖️', 'train', 10, 30
FROM hero_events he 
JOIN civilization_events ce ON he.event_id = ce.id 
WHERE ce.event_key = 'hero_event_jan_2026';

INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward) 
SELECT he.id, 'hero_train_50', '兵士を50体訓練', '兵士を50体訓練しよう', '🎖️', 'train', 50, 100
FROM hero_events he 
JOIN civilization_events ce ON he.event_id = ce.id 
WHERE ce.event_key = 'hero_event_jan_2026';

INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward) 
SELECT he.id, 'hero_collect_5', '資源を5回収集', '資源を5回収集しよう', '📦', 'collect', 5, 40
FROM hero_events he 
JOIN civilization_events ce ON he.event_id = ce.id 
WHERE ce.event_key = 'hero_event_jan_2026';

INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward) 
SELECT he.id, 'hero_invest_5', 'コイン投資を5回', 'コイン投資を5回行おう', '💰', 'invest', 5, 50
FROM hero_events he 
JOIN civilization_events ce ON he.event_id = ce.id 
WHERE ce.event_key = 'hero_event_jan_2026';

-- ⑧ ヒーローイベントポイント報酬を増加（種類も数も量も）
INSERT IGNORE INTO hero_event_point_rewards (hero_event_id, required_points, reward_type, reward_amount) 
SELECT he.id, 30, 'coins', 2000
FROM hero_events he 
JOIN civilization_events ce ON he.event_id = ce.id 
WHERE ce.event_key = 'hero_event_jan_2026';

INSERT IGNORE INTO hero_event_point_rewards (hero_event_id, required_points, reward_type, reward_amount) 
SELECT he.id, 60, 'crystals', 15
FROM hero_events he 
JOIN civilization_events ce ON he.event_id = ce.id 
WHERE ce.event_key = 'hero_event_jan_2026';

INSERT IGNORE INTO hero_event_point_rewards (hero_event_id, required_points, reward_type, reward_amount) 
SELECT he.id, 100, 'hero_shards', 5
FROM hero_events he 
JOIN civilization_events ce ON he.event_id = ce.id 
WHERE ce.event_key = 'hero_event_jan_2026';

INSERT IGNORE INTO hero_event_point_rewards (hero_event_id, required_points, reward_type, reward_amount) 
SELECT he.id, 150, 'diamonds', 3
FROM hero_events he 
JOIN civilization_events ce ON he.event_id = ce.id 
WHERE ce.event_key = 'hero_event_jan_2026';

INSERT IGNORE INTO hero_event_point_rewards (hero_event_id, required_points, reward_type, reward_amount) 
SELECT he.id, 200, 'coins', 5000
FROM hero_events he 
JOIN civilization_events ce ON he.event_id = ce.id 
WHERE ce.event_key = 'hero_event_jan_2026';

INSERT IGNORE INTO hero_event_point_rewards (hero_event_id, required_points, reward_type, reward_amount) 
SELECT he.id, 300, 'crystals', 30
FROM hero_events he 
JOIN civilization_events ce ON he.event_id = ce.id 
WHERE ce.event_key = 'hero_event_jan_2026';

INSERT IGNORE INTO hero_event_point_rewards (hero_event_id, required_points, reward_type, reward_amount) 
SELECT he.id, 400, 'hero_shards', 10
FROM hero_events he 
JOIN civilization_events ce ON he.event_id = ce.id 
WHERE ce.event_key = 'hero_event_jan_2026';

INSERT IGNORE INTO hero_event_point_rewards (hero_event_id, required_points, reward_type, reward_amount) 
SELECT he.id, 500, 'diamonds', 10
FROM hero_events he 
JOIN civilization_events ce ON he.event_id = ce.id 
WHERE ce.event_key = 'hero_event_jan_2026';

INSERT IGNORE INTO hero_event_point_rewards (hero_event_id, required_points, reward_type, reward_amount) 
SELECT he.id, 750, 'hero_shards', 20
FROM hero_events he 
JOIN civilization_events ce ON he.event_id = ce.id 
WHERE ce.event_key = 'hero_event_jan_2026';

INSERT IGNORE INTO hero_event_point_rewards (hero_event_id, required_points, reward_type, reward_amount) 
SELECT he.id, 1000, 'diamonds', 25
FROM hero_events he 
JOIN civilization_events ce ON he.event_id = ce.id 
WHERE ce.event_key = 'hero_event_jan_2026';

-- ===============================================
-- ⑨ ポータルボスのloot_tableの修正
-- item_idを実際のspecial_event_itemsのIDに合わせる
-- ===============================================

-- loot_tableを更新（item_idをsubqueryで正しく設定、NULL対策にCOALESCE使用）
UPDATE special_event_portal_bosses sepb
JOIN civilization_events ce ON sepb.event_id = ce.id
SET sepb.loot_table = (
    SELECT CONCAT(
        '[',
        '{"item_id":', COALESCE((SELECT id FROM special_event_items WHERE item_key = 'new_year_coin' AND event_id = ce.id LIMIT 1), 0), ',"chance":50,"min_count":1,"max_count":5},',
        '{"item_id":', COALESCE((SELECT id FROM special_event_items WHERE item_key = 'lucky_charm' AND event_id = ce.id LIMIT 1), 0), ',"chance":35,"min_count":1,"max_count":3},',
        '{"item_id":', COALESCE((SELECT id FROM special_event_items WHERE item_key = 'golden_dragon' AND event_id = ce.id LIMIT 1), 0), ',"chance":20,"min_count":1,"max_count":2},',
        '{"item_id":', COALESCE((SELECT id FROM special_event_items WHERE item_key = 'phoenix_feather' AND event_id = ce.id LIMIT 1), 0), ',"chance":10,"min_count":1,"max_count":1}',
        ']'
    )
)
WHERE ce.event_key = 'new_year_2026';

-- ===============================================
-- 完了メッセージ
-- ===============================================
SELECT 'MiniBird fixes 2026 schema applied successfully' AS status;
