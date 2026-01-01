-- ===============================================
-- MiniBird ヒーローイベント タスク・報酬追加 2026
-- 各ヒーローイベントにタスクとポイント報酬を追加
-- ===============================================

USE microblog;

-- ===============================================
-- 各イベントにタスクを追加（テンプレート形式）
-- ===============================================

-- ブレードマスターイベント用タスク
INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward)
SELECT he.id, 'login_1', 'イベント中ログイン1回', '毎日ログインしよう', '🏠', 'login', 1, 10
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_blade_master_2026';

INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward)
SELECT he.id, 'login_5', 'イベント中ログイン5回', '5日間ログインしよう', '🏠', 'login', 5, 50
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_blade_master_2026';

INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward)
SELECT he.id, 'battle_3', '戦闘に3回参加', '戦闘に3回参加しよう', '⚔️', 'battle', 3, 30
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_blade_master_2026';

INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward)
SELECT he.id, 'battle_10', '戦闘に10回参加', '戦闘に10回参加しよう', '⚔️', 'battle', 10, 100
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_blade_master_2026';

INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward)
SELECT he.id, 'gacha_3', 'ガチャを3回回す', 'ヒーローガチャを3回回そう', '🎰', 'gacha', 3, 45
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_blade_master_2026';

INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward)
SELECT he.id, 'gacha_10', 'ガチャを10回回す', 'ヒーローガチャを10回回そう', '🎰', 'gacha', 10, 150
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_blade_master_2026';

INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward)
SELECT he.id, 'train_20', '兵士を20体訓練', '兵士を20体訓練しよう', '🎖️', 'train', 20, 40
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_blade_master_2026';

INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward)
SELECT he.id, 'collect_10', '資源を10回収集', '資源を10回収集しよう', '📦', 'collect', 10, 60
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_blade_master_2026';

-- ブレードマスターイベント用ポイント報酬
INSERT IGNORE INTO hero_event_point_rewards (hero_event_id, required_points, reward_type, reward_amount)
SELECT he.id, 50, 'coins', 3000
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_blade_master_2026';

INSERT IGNORE INTO hero_event_point_rewards (hero_event_id, required_points, reward_type, reward_amount)
SELECT he.id, 100, 'crystals', 20
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_blade_master_2026';

INSERT IGNORE INTO hero_event_point_rewards (hero_event_id, required_points, reward_type, reward_amount)
SELECT he.id, 200, 'hero_shards', 5
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_blade_master_2026';

INSERT IGNORE INTO hero_event_point_rewards (hero_event_id, required_points, reward_type, reward_amount)
SELECT he.id, 350, 'diamonds', 5
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_blade_master_2026';

INSERT IGNORE INTO hero_event_point_rewards (hero_event_id, required_points, reward_type, reward_amount)
SELECT he.id, 500, 'hero_shards', 15
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_blade_master_2026';

-- シールドガーディアンイベント用タスク・報酬（同様のパターン）
INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward)
SELECT he.id, 'login_1', 'イベント中ログイン1回', '毎日ログインしよう', '🏠', 'login', 1, 10
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_shield_guardian_2026';

INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward)
SELECT he.id, 'login_5', 'イベント中ログイン5回', '5日間ログインしよう', '🏠', 'login', 5, 50
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_shield_guardian_2026';

INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward)
SELECT he.id, 'battle_5', '戦闘に5回参加', '戦闘に5回参加しよう', '⚔️', 'battle', 5, 50
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_shield_guardian_2026';

INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward)
SELECT he.id, 'gacha_5', 'ガチャを5回回す', 'ヒーローガチャを5回回そう', '🎰', 'gacha', 5, 75
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_shield_guardian_2026';

INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward)
SELECT he.id, 'build_3', '建物を3回建設・アップグレード', '建物を3回建設またはアップグレードしよう', '🏗️', 'build', 3, 60
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_shield_guardian_2026';

INSERT IGNORE INTO hero_event_point_rewards (hero_event_id, required_points, reward_type, reward_amount)
SELECT he.id, 50, 'coins', 3000
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_shield_guardian_2026';

INSERT IGNORE INTO hero_event_point_rewards (hero_event_id, required_points, reward_type, reward_amount)
SELECT he.id, 100, 'crystals', 20
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_shield_guardian_2026';

INSERT IGNORE INTO hero_event_point_rewards (hero_event_id, required_points, reward_type, reward_amount)
SELECT he.id, 200, 'hero_shards', 5
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_shield_guardian_2026';

INSERT IGNORE INTO hero_event_point_rewards (hero_event_id, required_points, reward_type, reward_amount)
SELECT he.id, 400, 'hero_shards', 15
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_shield_guardian_2026';

-- 他のイベントも同様のパターンで追加
-- フレイムメイジ
INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward)
SELECT he.id, 'login_1', 'イベント中ログイン1回', '毎日ログインしよう', '🏠', 'login', 1, 10
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_flame_mage_2026';

INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward)
SELECT he.id, 'battle_5', '戦闘に5回参加', '戦闘に5回参加しよう', '⚔️', 'battle', 5, 50
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_flame_mage_2026';

INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward)
SELECT he.id, 'gacha_5', 'ガチャを5回回す', 'ヒーローガチャを5回回そう', '🎰', 'gacha', 5, 75
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_flame_mage_2026';

INSERT IGNORE INTO hero_event_point_rewards (hero_event_id, required_points, reward_type, reward_amount)
SELECT he.id, 50, 'coins', 3000
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_flame_mage_2026';

INSERT IGNORE INTO hero_event_point_rewards (hero_event_id, required_points, reward_type, reward_amount)
SELECT he.id, 150, 'hero_shards', 8
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_flame_mage_2026';

-- 残りのイベントにも基本タスクとポイント報酬を追加（フロストクイーン、サンダーゴッド等）
-- フロストクイーン
INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward)
SELECT he.id, 'login_1', 'イベント中ログイン1回', '毎日ログインしよう', '🏠', 'login', 1, 10
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_frost_queen_2026';

INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward)
SELECT he.id, 'gacha_5', 'ガチャを5回回す', 'ヒーローガチャを5回回そう', '🎰', 'gacha', 5, 75
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_frost_queen_2026';

INSERT IGNORE INTO hero_event_point_rewards (hero_event_id, required_points, reward_type, reward_amount)
SELECT he.id, 100, 'hero_shards', 8
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_frost_queen_2026';

-- サンダーゴッド
INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward)
SELECT he.id, 'login_1', 'イベント中ログイン1回', '毎日ログインしよう', '🏠', 'login', 1, 10
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_thunder_god_2026';

INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward)
SELECT he.id, 'gacha_10', 'ガチャを10回回す', 'ヒーローガチャを10回回そう', '🎰', 'gacha', 10, 150
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_thunder_god_2026';

INSERT IGNORE INTO hero_event_point_rewards (hero_event_id, required_points, reward_type, reward_amount)
SELECT he.id, 150, 'hero_shards', 10
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_thunder_god_2026';

-- ネイチャードルイド
INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward)
SELECT he.id, 'login_1', 'イベント中ログイン1回', '毎日ログインしよう', '🏠', 'login', 1, 10
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_nature_druid_2026';

INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward)
SELECT he.id, 'gacha_5', 'ガチャを5回回す', 'ヒーローガチャを5回回そう', '🎰', 'gacha', 5, 75
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_nature_druid_2026';

INSERT IGNORE INTO hero_event_point_rewards (hero_event_id, required_points, reward_type, reward_amount)
SELECT he.id, 100, 'hero_shards', 5
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_nature_druid_2026';

-- シャドウアサシン
INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward)
SELECT he.id, 'login_1', 'イベント中ログイン1回', '毎日ログインしよう', '🏠', 'login', 1, 10
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_shadow_assassin_2026';

INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward)
SELECT he.id, 'gacha_10', 'ガチャを10回回す', 'ヒーローガチャを10回回そう', '🎰', 'gacha', 10, 150
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_shadow_assassin_2026';

INSERT IGNORE INTO hero_event_point_rewards (hero_event_id, required_points, reward_type, reward_amount)
SELECT he.id, 150, 'hero_shards', 10
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_shadow_assassin_2026';

-- ホーリーパラディン
INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward)
SELECT he.id, 'login_1', 'イベント中ログイン1回', '毎日ログインしよう', '🏠', 'login', 1, 10
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_holy_paladin_2026';

INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward)
SELECT he.id, 'gacha_5', 'ガチャを5回回す', 'ヒーローガチャを5回回そう', '🎰', 'gacha', 5, 75
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_holy_paladin_2026';

INSERT IGNORE INTO hero_event_point_rewards (hero_event_id, required_points, reward_type, reward_amount)
SELECT he.id, 100, 'hero_shards', 8
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_holy_paladin_2026';

-- タイムセージ
INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward)
SELECT he.id, 'login_1', 'イベント中ログイン1回', '毎日ログインしよう', '🏠', 'login', 1, 10
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_time_sage_2026';

INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward)
SELECT he.id, 'gacha_10', 'ガチャを10回回す', 'ヒーローガチャを10回回そう', '🎰', 'gacha', 10, 150
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_time_sage_2026';

INSERT IGNORE INTO hero_event_point_rewards (hero_event_id, required_points, reward_type, reward_amount)
SELECT he.id, 150, 'hero_shards', 15
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_time_sage_2026';

-- カオスロード
INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward)
SELECT he.id, 'login_1', 'イベント中ログイン1回', '毎日ログインしよう', '🏠', 'login', 1, 10
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_chaos_lord_2026';

INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward)
SELECT he.id, 'gacha_15', 'ガチャを15回回す', 'ヒーローガチャを15回回そう', '🎰', 'gacha', 15, 225
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_chaos_lord_2026';

INSERT IGNORE INTO hero_event_point_rewards (hero_event_id, required_points, reward_type, reward_amount)
SELECT he.id, 200, 'hero_shards', 20
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_chaos_lord_2026';

-- アイアンフォートレス
INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward)
SELECT he.id, 'login_1', 'イベント中ログイン1回', '毎日ログインしよう', '🏠', 'login', 1, 10
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_iron_fortress_2026';

INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward)
SELECT he.id, 'gacha_10', 'ガチャを10回回す', 'ヒーローガチャを10回回そう', '🎰', 'gacha', 10, 150
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_iron_fortress_2026';

INSERT IGNORE INTO hero_event_point_rewards (hero_event_id, required_points, reward_type, reward_amount)
SELECT he.id, 150, 'hero_shards', 15
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_iron_fortress_2026';

-- ウィンドダンサー
INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward)
SELECT he.id, 'login_1', 'イベント中ログイン1回', '毎日ログインしよう', '🏠', 'login', 1, 10
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_wind_dancer_2026';

INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward)
SELECT he.id, 'gacha_5', 'ガチャを5回回す', 'ヒーローガチャを5回回そう', '🎰', 'gacha', 5, 75
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_wind_dancer_2026';

INSERT IGNORE INTO hero_event_point_rewards (hero_event_id, required_points, reward_type, reward_amount)
SELECT he.id, 100, 'hero_shards', 10
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_wind_dancer_2026';

-- ライフウィーバー
INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward)
SELECT he.id, 'login_1', 'イベント中ログイン1回', '毎日ログインしよう', '🏠', 'login', 1, 10
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_life_weaver_2026';

INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward)
SELECT he.id, 'gacha_10', 'ガチャを10回回す', 'ヒーローガチャを10回回そう', '🎰', 'gacha', 10, 150
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_life_weaver_2026';

INSERT IGNORE INTO hero_event_point_rewards (hero_event_id, required_points, reward_type, reward_amount)
SELECT he.id, 150, 'hero_shards', 15
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_life_weaver_2026';

-- プレイグドクター
INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward)
SELECT he.id, 'login_1', 'イベント中ログイン1回', '毎日ログインしよう', '🏠', 'login', 1, 10
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_plague_doctor_2026';

INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward)
SELECT he.id, 'gacha_5', 'ガチャを5回回す', 'ヒーローガチャを5回回そう', '🎰', 'gacha', 5, 75
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_plague_doctor_2026';

INSERT IGNORE INTO hero_event_point_rewards (hero_event_id, required_points, reward_type, reward_amount)
SELECT he.id, 100, 'hero_shards', 10
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_plague_doctor_2026';

-- トレジャーハンター
INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward)
SELECT he.id, 'login_1', 'イベント中ログイン1回', '毎日ログインしよう', '🏠', 'login', 1, 10
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_treasure_hunter_2026';

INSERT IGNORE INTO hero_event_tasks (hero_event_id, task_key, name, description, icon, task_type, target_count, points_reward)
SELECT he.id, 'gacha_15', 'ガチャを15回回す', 'ヒーローガチャを15回回そう', '🎰', 'gacha', 15, 225
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_treasure_hunter_2026';

INSERT IGNORE INTO hero_event_point_rewards (hero_event_id, required_points, reward_type, reward_amount)
SELECT he.id, 200, 'hero_shards', 20
FROM hero_events he JOIN civilization_events ce ON he.event_id = ce.id WHERE ce.event_key = 'hero_event_treasure_hunter_2026';

-- ===============================================
-- 完了メッセージ
-- ===============================================
SELECT 'MiniBird hero event tasks and rewards 2026 schema applied successfully' AS status;
