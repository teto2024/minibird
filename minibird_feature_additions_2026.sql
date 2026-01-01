-- ===============================================
-- MiniBird 機能追加 2026
-- ① ウラン(ウラニウム)生産施設を追加
-- ② 建築や兵種の前提研究・前提建築を追加設定
-- ③ ヒーローイベントデータを全ヒーロー分追加
-- ④ スペシャルイベントの限定アイテム交換率向上
-- ===============================================

USE microblog;

-- ===============================================
-- ① ウラン(ウラニウム)生産施設を追加
-- 現状、ウランを生産する建物が存在しないため、「ウラン鉱山」を追加
-- ===============================================

-- ウラン鉱山を追加（現代時代、unlock_era_id=7）
INSERT IGNORE INTO civilization_building_types 
(building_key, name, icon, description, category, produces_resource_id, production_rate, max_level, unlock_era_id, base_build_cost_coins, base_build_cost_resources, base_build_time_seconds, population_capacity, military_power) 
VALUES
('uranium_mine', 'ウラン鉱山', '☢️', '放射性物質ウランを採掘する危険な施設', 'production', 
    (SELECT id FROM civilization_resource_types WHERE resource_key = 'uranium' LIMIT 1), 
    1.5, 10, 7, 80000, '{"iron": 1000, "oil": 300, "stone": 500}', 43200, 0, 0);

-- ウラン鉱山の前提条件を設定（核技術研究が必要）
UPDATE civilization_building_types 
SET prerequisite_research_id = (
    SELECT id FROM civilization_researches WHERE research_key = 'nuclear_power' LIMIT 1
)
WHERE building_key = 'uranium_mine';

-- ===============================================
-- ② 建築や兵種の前提研究・前提建築を追加設定
-- ===============================================

-- 農場は農業の基礎研究が必要
UPDATE civilization_building_types 
SET prerequisite_research_id = (SELECT id FROM civilization_researches WHERE research_key = 'agriculture_basics' LIMIT 1)
WHERE building_key = 'farm' AND prerequisite_research_id IS NULL;

-- 青銅鋳造所は青銅加工研究が必要
UPDATE civilization_building_types 
SET prerequisite_research_id = (SELECT id FROM civilization_researches WHERE research_key = 'bronze_working' LIMIT 1)
WHERE building_key = 'bronze_foundry' AND prerequisite_research_id IS NULL;

-- 鉄鉱山は鉄加工研究が必要
UPDATE civilization_building_types 
SET prerequisite_research_id = (SELECT id FROM civilization_researches WHERE research_key = 'iron_working' LIMIT 1)
WHERE building_key = 'iron_mine' AND prerequisite_research_id IS NULL;

-- 鍛冶場は鉄鉱山が必要
UPDATE civilization_building_types 
SET prerequisite_building_id = (SELECT id FROM civilization_building_types WHERE building_key = 'iron_mine' LIMIT 1)
WHERE building_key = 'blacksmith' AND prerequisite_building_id IS NULL;

-- 金鉱山は銀行業研究が必要
UPDATE civilization_building_types 
SET prerequisite_research_id = (SELECT id FROM civilization_researches WHERE research_key = 'banking' LIMIT 1)
WHERE building_key = 'gold_mine' AND prerequisite_research_id IS NULL;

-- 大学は科学的方法研究が必要
UPDATE civilization_building_types 
SET prerequisite_research_id = (SELECT id FROM civilization_researches WHERE research_key = 'scientific_method' LIMIT 1)
WHERE building_key = 'university' AND prerequisite_research_id IS NULL;

-- 工場は蒸気機関研究が必要
UPDATE civilization_building_types 
SET prerequisite_research_id = (SELECT id FROM civilization_researches WHERE research_key = 'steam_power' LIMIT 1)
WHERE building_key = 'factory' AND prerequisite_research_id IS NULL;

-- 油井は石油掘削研究が必要
UPDATE civilization_building_types 
SET prerequisite_research_id = (SELECT id FROM civilization_researches WHERE research_key = 'oil_drilling' LIMIT 1)
WHERE building_key = 'oil_well' AND prerequisite_research_id IS NULL;

-- 造船所は航海術研究が必要
UPDATE civilization_building_types 
SET prerequisite_research_id = (SELECT id FROM civilization_researches WHERE research_key = 'navigation' LIMIT 1)
WHERE building_key = 'naval_dock' AND prerequisite_research_id IS NULL;

-- 弓術場は兵舎が必要
UPDATE civilization_building_types 
SET prerequisite_building_id = (SELECT id FROM civilization_building_types WHERE building_key = 'barracks' LIMIT 1)
WHERE building_key = 'archery_range' AND prerequisite_building_id IS NULL;

-- 厩舎は兵舎が必要
UPDATE civilization_building_types 
SET prerequisite_building_id = (SELECT id FROM civilization_building_types WHERE building_key = 'barracks' LIMIT 1)
WHERE building_key = 'stable' AND prerequisite_building_id IS NULL;

-- 攻城兵器工房は要塞が必要
UPDATE civilization_building_types 
SET prerequisite_building_id = (SELECT id FROM civilization_building_types WHERE building_key = 'fortress' LIMIT 1)
WHERE building_key = 'siege_workshop' AND prerequisite_building_id IS NULL;

-- 兵種の前提条件を追加設定

-- クロスボウ兵は弓術場が必要
UPDATE civilization_troop_types 
SET prerequisite_building_id = (SELECT id FROM civilization_building_types WHERE building_key = 'archery_range' LIMIT 1)
WHERE troop_key = 'crossbowman' AND prerequisite_building_id IS NULL;

-- マスケット銃兵は軍事訓練研究が必要
UPDATE civilization_troop_types 
SET prerequisite_research_id = (SELECT id FROM civilization_researches WHERE research_key = 'military_training' LIMIT 1)
WHERE troop_key = 'musketeer' AND prerequisite_research_id IS NULL;

-- 大砲は攻城兵器工房が必要
UPDATE civilization_troop_types 
SET prerequisite_building_id = (SELECT id FROM civilization_building_types WHERE building_key = 'siege_workshop' LIMIT 1)
WHERE troop_key = 'cannon' AND prerequisite_building_id IS NULL;

-- 歩兵は軍事基地が必要
UPDATE civilization_troop_types 
SET prerequisite_building_id = (SELECT id FROM civilization_building_types WHERE building_key = 'military_base' LIMIT 1)
WHERE troop_key = 'infantry' AND prerequisite_building_id IS NULL;

-- 砲兵は軍事基地が必要
UPDATE civilization_troop_types 
SET prerequisite_building_id = (SELECT id FROM civilization_building_types WHERE building_key = 'military_base' LIMIT 1)
WHERE troop_key = 'artillery' AND prerequisite_building_id IS NULL;

-- ===============================================
-- ③ ヒーローイベントデータを全ヒーロー分追加
-- ===============================================

-- ブレードマスター週間（1/8-1/14）
INSERT IGNORE INTO civilization_events (event_key, event_type, name, description, icon, start_date, end_date, is_active, config) VALUES
('hero_event_blade_master_2026', 'hero', 'ブレードマスター週間', '剣の達人の欠片を集めよう！', '⚔️', '2026-01-08 00:00:00', '2026-01-14 23:59:59', TRUE, '{"featured_hero_key": "blade_master"}');

INSERT IGNORE INTO hero_events (event_id, featured_hero_id, bonus_shard_rate, gacha_discount_percent) 
SELECT ce.id, h.id, 3.0, 15 FROM civilization_events ce, heroes h WHERE ce.event_key = 'hero_event_blade_master_2026' AND h.hero_key = 'blade_master';

-- シールドガーディアン週間（1/15-1/21）
INSERT IGNORE INTO civilization_events (event_key, event_type, name, description, icon, start_date, end_date, is_active, config) VALUES
('hero_event_shield_guardian_2026', 'hero', 'シールドガーディアン週間', '盾の守護者の欠片を集めよう！', '🛡️', '2026-01-15 00:00:00', '2026-01-21 23:59:59', TRUE, '{"featured_hero_key": "shield_guardian"}');

INSERT IGNORE INTO hero_events (event_id, featured_hero_id, bonus_shard_rate, gacha_discount_percent) 
SELECT ce.id, h.id, 3.0, 15 FROM civilization_events ce, heroes h WHERE ce.event_key = 'hero_event_shield_guardian_2026' AND h.hero_key = 'shield_guardian';

-- フレイムメイジ週間（1/22-1/28）
INSERT IGNORE INTO civilization_events (event_key, event_type, name, description, icon, start_date, end_date, is_active, config) VALUES
('hero_event_flame_mage_2026', 'hero', 'フレイムメイジ週間', '炎の魔術師の欠片を集めよう！', '🔥', '2026-01-22 00:00:00', '2026-01-28 23:59:59', TRUE, '{"featured_hero_key": "flame_mage"}');

INSERT IGNORE INTO hero_events (event_id, featured_hero_id, bonus_shard_rate, gacha_discount_percent) 
SELECT ce.id, h.id, 3.5, 15 FROM civilization_events ce, heroes h WHERE ce.event_key = 'hero_event_flame_mage_2026' AND h.hero_key = 'flame_mage';

-- フロストクイーン週間（1/29-2/4）
INSERT IGNORE INTO civilization_events (event_key, event_type, name, description, icon, start_date, end_date, is_active, config) VALUES
('hero_event_frost_queen_2026', 'hero', 'フロストクイーン週間', '氷の女王の欠片を集めよう！', '❄️', '2026-01-29 00:00:00', '2026-02-04 23:59:59', TRUE, '{"featured_hero_key": "frost_queen"}');

INSERT IGNORE INTO hero_events (event_id, featured_hero_id, bonus_shard_rate, gacha_discount_percent) 
SELECT ce.id, h.id, 3.5, 15 FROM civilization_events ce, heroes h WHERE ce.event_key = 'hero_event_frost_queen_2026' AND h.hero_key = 'frost_queen';

-- サンダーゴッド週間（2/5-2/11）
INSERT IGNORE INTO civilization_events (event_key, event_type, name, description, icon, start_date, end_date, is_active, config) VALUES
('hero_event_thunder_god_2026', 'hero', 'サンダーゴッド週間', '雷神の欠片を集めよう！', '⚡', '2026-02-05 00:00:00', '2026-02-11 23:59:59', TRUE, '{"featured_hero_key": "thunder_god"}');

INSERT IGNORE INTO hero_events (event_id, featured_hero_id, bonus_shard_rate, gacha_discount_percent) 
SELECT ce.id, h.id, 4.0, 20 FROM civilization_events ce, heroes h WHERE ce.event_key = 'hero_event_thunder_god_2026' AND h.hero_key = 'thunder_god';

-- ネイチャードルイド週間（2/12-2/18）
INSERT IGNORE INTO civilization_events (event_key, event_type, name, description, icon, start_date, end_date, is_active, config) VALUES
('hero_event_nature_druid_2026', 'hero', 'ネイチャードルイド週間', '森の賢者の欠片を集めよう！', '🌿', '2026-02-12 00:00:00', '2026-02-18 23:59:59', TRUE, '{"featured_hero_key": "nature_druid"}');

INSERT IGNORE INTO hero_events (event_id, featured_hero_id, bonus_shard_rate, gacha_discount_percent) 
SELECT ce.id, h.id, 3.0, 15 FROM civilization_events ce, heroes h WHERE ce.event_key = 'hero_event_nature_druid_2026' AND h.hero_key = 'nature_druid';

-- シャドウアサシン週間（2/19-2/25）
INSERT IGNORE INTO civilization_events (event_key, event_type, name, description, icon, start_date, end_date, is_active, config) VALUES
('hero_event_shadow_assassin_2026', 'hero', 'シャドウアサシン週間', '影の暗殺者の欠片を集めよう！', '🗡️', '2026-02-19 00:00:00', '2026-02-25 23:59:59', TRUE, '{"featured_hero_key": "shadow_assassin"}');

INSERT IGNORE INTO hero_events (event_id, featured_hero_id, bonus_shard_rate, gacha_discount_percent) 
SELECT ce.id, h.id, 4.0, 20 FROM civilization_events ce, heroes h WHERE ce.event_key = 'hero_event_shadow_assassin_2026' AND h.hero_key = 'shadow_assassin';

-- ホーリーパラディン週間（2/26-3/4）
INSERT IGNORE INTO civilization_events (event_key, event_type, name, description, icon, start_date, end_date, is_active, config) VALUES
('hero_event_holy_paladin_2026', 'hero', 'ホーリーパラディン週間', '聖なる騎士の欠片を集めよう！', '✨', '2026-02-26 00:00:00', '2026-03-04 23:59:59', TRUE, '{"featured_hero_key": "holy_paladin"}');

INSERT IGNORE INTO hero_events (event_id, featured_hero_id, bonus_shard_rate, gacha_discount_percent) 
SELECT ce.id, h.id, 3.5, 15 FROM civilization_events ce, heroes h WHERE ce.event_key = 'hero_event_holy_paladin_2026' AND h.hero_key = 'holy_paladin';

-- タイムセージ週間（3/5-3/11）
INSERT IGNORE INTO civilization_events (event_key, event_type, name, description, icon, start_date, end_date, is_active, config) VALUES
('hero_event_time_sage_2026', 'hero', 'タイムセージ週間', '時の賢者の欠片を集めよう！', '⏰', '2026-03-05 00:00:00', '2026-03-11 23:59:59', TRUE, '{"featured_hero_key": "time_sage"}');

INSERT IGNORE INTO hero_events (event_id, featured_hero_id, bonus_shard_rate, gacha_discount_percent) 
SELECT ce.id, h.id, 5.0, 25 FROM civilization_events ce, heroes h WHERE ce.event_key = 'hero_event_time_sage_2026' AND h.hero_key = 'time_sage';

-- カオスロード週間（3/12-3/18）
INSERT IGNORE INTO civilization_events (event_key, event_type, name, description, icon, start_date, end_date, is_active, config) VALUES
('hero_event_chaos_lord_2026', 'hero', 'カオスロード週間', '混沌の支配者の欠片を集めよう！', '🌀', '2026-03-12 00:00:00', '2026-03-18 23:59:59', TRUE, '{"featured_hero_key": "chaos_lord"}');

INSERT IGNORE INTO hero_events (event_id, featured_hero_id, bonus_shard_rate, gacha_discount_percent) 
SELECT ce.id, h.id, 6.0, 30 FROM civilization_events ce, heroes h WHERE ce.event_key = 'hero_event_chaos_lord_2026' AND h.hero_key = 'chaos_lord';

-- アイアンフォートレス週間（3/19-3/25）
INSERT IGNORE INTO civilization_events (event_key, event_type, name, description, icon, start_date, end_date, is_active, config) VALUES
('hero_event_iron_fortress_2026', 'hero', 'アイアンフォートレス週間', '鋼鉄の要塞の欠片を集めよう！', '🛡️', '2026-03-19 00:00:00', '2026-03-25 23:59:59', TRUE, '{"featured_hero_key": "iron_fortress"}');

INSERT IGNORE INTO hero_events (event_id, featured_hero_id, bonus_shard_rate, gacha_discount_percent) 
SELECT ce.id, h.id, 5.0, 25 FROM civilization_events ce, heroes h WHERE ce.event_key = 'hero_event_iron_fortress_2026' AND h.hero_key = 'iron_fortress';

-- ウィンドダンサー週間（3/26-4/1）
INSERT IGNORE INTO civilization_events (event_key, event_type, name, description, icon, start_date, end_date, is_active, config) VALUES
('hero_event_wind_dancer_2026', 'hero', 'ウィンドダンサー週間', '疾風の踊り子の欠片を集めよう！', '💨', '2026-03-26 00:00:00', '2026-04-01 23:59:59', TRUE, '{"featured_hero_key": "wind_dancer"}');

INSERT IGNORE INTO hero_events (event_id, featured_hero_id, bonus_shard_rate, gacha_discount_percent) 
SELECT ce.id, h.id, 4.0, 20 FROM civilization_events ce, heroes h WHERE ce.event_key = 'hero_event_wind_dancer_2026' AND h.hero_key = 'wind_dancer';

-- ライフウィーバー週間（4/2-4/8）
INSERT IGNORE INTO civilization_events (event_key, event_type, name, description, icon, start_date, end_date, is_active, config) VALUES
('hero_event_life_weaver_2026', 'hero', 'ライフウィーバー週間', '命の紡ぎ手の欠片を集めよう！', '💚', '2026-04-02 00:00:00', '2026-04-08 23:59:59', TRUE, '{"featured_hero_key": "life_weaver"}');

INSERT IGNORE INTO hero_events (event_id, featured_hero_id, bonus_shard_rate, gacha_discount_percent) 
SELECT ce.id, h.id, 5.0, 25 FROM civilization_events ce, heroes h WHERE ce.event_key = 'hero_event_life_weaver_2026' AND h.hero_key = 'life_weaver';

-- プレイグドクター週間（4/9-4/15）
INSERT IGNORE INTO civilization_events (event_key, event_type, name, description, icon, start_date, end_date, is_active, config) VALUES
('hero_event_plague_doctor_2026', 'hero', 'プレイグドクター週間', '疫病の医師の欠片を集めよう！', '☠️', '2026-04-09 00:00:00', '2026-04-15 23:59:59', TRUE, '{"featured_hero_key": "plague_doctor"}');

INSERT IGNORE INTO hero_events (event_id, featured_hero_id, bonus_shard_rate, gacha_discount_percent) 
SELECT ce.id, h.id, 4.0, 20 FROM civilization_events ce, heroes h WHERE ce.event_key = 'hero_event_plague_doctor_2026' AND h.hero_key = 'plague_doctor';

-- トレジャーハンター週間（4/16-4/22）
INSERT IGNORE INTO civilization_events (event_key, event_type, name, description, icon, start_date, end_date, is_active, config) VALUES
('hero_event_treasure_hunter_2026', 'hero', 'トレジャーハンター週間', '財宝の狩人の欠片を集めよう！', '💰', '2026-04-16 00:00:00', '2026-04-22 23:59:59', TRUE, '{"featured_hero_key": "treasure_hunter"}');

INSERT IGNORE INTO hero_events (event_id, featured_hero_id, bonus_shard_rate, gacha_discount_percent) 
SELECT ce.id, h.id, 6.0, 30 FROM civilization_events ce, heroes h WHERE ce.event_key = 'hero_event_treasure_hunter_2026' AND h.hero_key = 'treasure_hunter';

-- ===============================================
-- ④ スペシャルイベントの限定アイテム交換率をさらに改善
-- ===============================================

-- 交換レートをさらに改善（交換必要数を減らし、報酬を増加）
UPDATE special_event_exchange see
JOIN civilization_events ce ON see.event_id = ce.id
SET 
    see.required_count = GREATEST(1, FLOOR(see.required_count * 0.5)),
    see.reward_amount = FLOOR(see.reward_amount * 1.5)
WHERE ce.event_type = 'special' AND ce.is_active = TRUE;

-- 交換上限を撤廃または大幅増加
UPDATE special_event_exchange see
JOIN civilization_events ce ON see.event_id = ce.id
SET see.exchange_limit = NULL
WHERE ce.event_type = 'special' AND ce.is_active = TRUE AND see.exchange_limit IS NOT NULL;

-- ===============================================
-- 完了メッセージ
-- ===============================================
SELECT 'MiniBird feature additions 2026 schema applied successfully' AS status;
