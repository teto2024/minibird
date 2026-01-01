-- ===============================================
-- MiniBird 新規ユニット追加 2026
-- 新しい兵種10種類と「陸」「海」「空」カテゴリの追加
-- 重複資源の解消
-- ===============================================

USE microblog;

-- ===============================================
-- ① 重複資源の解消
-- spice/spicesを統合（spicesを使用、spiceを削除）
-- saltpeter/gunpowder_resを統合（gunpowder_resを使用、saltpeterを削除）
-- ===============================================

-- まず重複リソースを参照している箇所がないか確認してから削除
-- spiceをspicesに統合（spiceへの参照をspicesに更新）
UPDATE user_civilization_resources 
SET resource_type_id = (SELECT id FROM civilization_resource_types WHERE resource_key = 'spices' LIMIT 1)
WHERE resource_type_id = (SELECT id FROM civilization_resource_types WHERE resource_key = 'spice' LIMIT 1)
  AND EXISTS (SELECT 1 FROM civilization_resource_types WHERE resource_key = 'spice');

-- saltpeterをgunpowder_resに統合
UPDATE user_civilization_resources 
SET resource_type_id = (SELECT id FROM civilization_resource_types WHERE resource_key = 'gunpowder_res' LIMIT 1)
WHERE resource_type_id = (SELECT id FROM civilization_resource_types WHERE resource_key = 'saltpeter' LIMIT 1)
  AND EXISTS (SELECT 1 FROM civilization_resource_types WHERE resource_key = 'saltpeter');

-- 重複資源を削除（存在する場合）
DELETE FROM civilization_resource_types WHERE resource_key = 'spice' 
  AND EXISTS (SELECT 1 FROM (SELECT 1 FROM civilization_resource_types WHERE resource_key = 'spices') AS t);
DELETE FROM civilization_resource_types WHERE resource_key = 'saltpeter' 
  AND EXISTS (SELECT 1 FROM (SELECT 1 FROM civilization_resource_types WHERE resource_key = 'gunpowder_res') AS t);

-- ===============================================
-- ② 兵種テーブルに「陸」「海」「空」カテゴリを追加
-- ===============================================

-- domain_category カラムを追加（陸・海・空カテゴリ）
ALTER TABLE civilization_troop_types 
ADD COLUMN IF NOT EXISTS domain_category ENUM('land', 'sea', 'air') NOT NULL DEFAULT 'land' 
    COMMENT '領域カテゴリ（陸・海・空）' AFTER troop_category;

-- is_disposable カラムを追加（使い捨てユニット用）
ALTER TABLE civilization_troop_types 
ADD COLUMN IF NOT EXISTS is_disposable BOOLEAN NOT NULL DEFAULT FALSE 
    COMMENT '使い捨てユニット（出撃後死亡扱い）' AFTER domain_category;

-- ===============================================
-- ③ 既存兵種にdomain_categoryを設定
-- ===============================================

-- 海軍ユニット
UPDATE civilization_troop_types SET domain_category = 'sea' WHERE troop_key IN (
    'galleon', 'ironclad', 'submarine', 'frigate', 'aircraft_carrier', 'nuclear_submarine'
);

-- 航空ユニット
UPDATE civilization_troop_types SET domain_category = 'air' WHERE troop_key IN (
    'fighter', 'bomber', 'stealth_fighter', 'paratroopers'
);

-- その他は陸軍（デフォルト値のまま）

-- 既存の使い捨てユニットを設定
UPDATE civilization_troop_types SET is_disposable = TRUE WHERE troop_key IN (
    'nuclear_submarine'  -- 核潜水艦は既存の使い捨てユニット
);

-- ===============================================
-- ④ 新スキルを追加
-- ===============================================

INSERT IGNORE INTO battle_special_skills (skill_key, name, icon, description, effect_type, effect_target, effect_value, duration_turns, activation_chance) VALUES
-- 巡洋艦：潜水艦シナジー（ステータス2倍）
('submarine_synergy', '対潜連携', '🔱', '潜水艦と同時出撃で自身のステータス2倍', 'buff', 'self', 100, 99, 100),

-- 強襲揚陸艦：海兵隊シナジー（ステータス3倍）
('marine_synergy', '上陸支援', '⚓', '海兵隊と同時出撃で自身のステータス3倍', 'buff', 'self', 200, 99, 100),

-- 戦術爆撃機：対歩兵/遠距離（攻撃力50%UP）
('anti_infantry_bomb', '対地爆撃', '💣', '歩兵・遠距離兵種がいる場合攻撃力50%アップ', 'buff', 'self', 50, 99, 100),

-- 戦略爆撃機：対城壁（攻撃力50%UP）
('strategic_bombing', '戦略爆撃', '🎯', 'ワールドボス、放浪モンスター、対城壁戦で攻撃力50%アップ', 'buff', 'self', 50, 99, 100),

-- ミサイル：爆風（歩兵へ3ターン継続ダメージ）
('blast_wave', '爆風', '💥', '25%で「歩兵」兵種に3ターン継続ダメージ', 'damage_over_time', 'enemy', 20, 3, 25),

-- 核ミサイル：核汚染（戦闘終了まで継続ダメージ）
('nuclear_fallout', '核汚染', '☢️', '30%で核汚染を発生させ戦闘終了まで継続ダメージ', 'nuclear_dot', 'enemy', 50, 99, 30),

-- 量子戦闘機：量子戦（5%で敵HP半減）
('quantum_warfare', '量子戦', '⚛️', '5%の確率で敵の体力を半減', 'special', 'enemy', 50, 1, 5),

-- 強襲型空母：空カテゴリシナジー（味方全体攻撃力40%UP）
('air_superiority', '制空権', '✈️', '「空」カテゴリと同時出撃で味方全体の攻撃力40%アップ', 'buff', 'self', 40, 99, 100),

-- 現代スパイ：寝返り（20%でダメージを与えその分回復）
('defection', '寝返り', '🕵️', '20%で敵にダメージを与えその分だけ自身を回復', 'special', 'enemy', 25, 1, 20),

-- ヨット隊：反射（10%で攻撃を跳ね返す）
('agitation', '扇動', '⛵', '10%で受けた攻撃をそのまま跳ね返す', 'special', 'self', 100, 1, 10);

-- ===============================================
-- ⑤ 新規兵種10種類を追加
-- ===============================================

INSERT IGNORE INTO civilization_troop_types (
    troop_key, name, icon, description, unlock_era_id, 
    attack_power, defense_power, health_points, troop_category, domain_category,
    is_disposable, train_cost_coins, train_cost_resources, train_time_seconds,
    heal_time_seconds, heal_cost_coins, special_skill_id
) VALUES
-- 巡洋艦：攻守バランス、潜水艦シナジー、重コスト
('cruiser', '巡洋艦', '🚢', '攻守バランスの艦船。潜水艦と同時出撃で自身のステータス2倍【海】', 
    7, 180, 150, 400, 'siege', 'sea', FALSE,
    25000, '{"iron": 600, "oil": 400, "steel": 100}', 4800,
    120, 100, (SELECT id FROM battle_special_skills WHERE skill_key = 'submarine_synergy')),

-- 強襲揚陸艦：攻撃特化の上陸船、海兵隊シナジー、超重コスト
('assault_ship', '強襲揚陸艦', '🛳️', '攻撃特化の上陸船。海兵隊と同時出撃で自身のステータス3倍【海】',
    7, 220, 100, 350, 'siege', 'sea', FALSE,
    40000, '{"iron": 800, "oil": 500, "steel": 150, "electronics": 50}', 6000,
    150, 120, (SELECT id FROM battle_special_skills WHERE skill_key = 'marine_synergy')),

-- 戦術爆撃機：対歩兵/遠距離、中コスト
('tactical_bomber', '戦術爆撃機', '✈️', '歩兵・遠距離兵種がいる場合攻撃力50%アップ【空】',
    7, 200, 60, 180, 'ranged', 'air', FALSE,
    18000, '{"iron": 350, "oil": 250}', 2700,
    90, 70, (SELECT id FROM battle_special_skills WHERE skill_key = 'anti_infantry_bomb')),

-- 戦略爆撃機：対城壁、中コスト
('strategic_bomber', '戦略爆撃機', '🛩️', 'ワールドボス、放浪モンスター、対城壁戦で攻撃力50%アップ【空】',
    7, 280, 40, 150, 'siege', 'air', FALSE,
    20000, '{"iron": 400, "oil": 300}', 3000,
    100, 80, (SELECT id FROM battle_special_skills WHERE skill_key = 'strategic_bombing')),

-- ミサイル：使い捨て、爆風スキル、重コスト
('missile', 'ミサイル', '🚀', '【使い捨て】25%で爆風を発生させ「歩兵」に3ターン継続ダメージ',
    7, 300, 10, 50, 'siege', 'air', TRUE,
    30000, '{"iron": 500, "oil": 300, "gunpowder": 100}', 2400,
    0, 0, (SELECT id FROM battle_special_skills WHERE skill_key = 'blast_wave')),

-- 核ミサイル：使い捨て、核汚染スキル、超重コスト
('nuclear_missile', '核ミサイル', '☢️', '【使い捨て】30%で核汚染を発生させ戦闘終了まで継続ダメージ',
    7, 500, 5, 30, 'siege', 'air', TRUE,
    80000, '{"iron": 800, "uranium": 300, "oil": 400}', 7200,
    0, 0, (SELECT id FROM battle_special_skills WHERE skill_key = 'nuclear_fallout')),

-- 量子戦闘機：使い捨て、量子戦スキル、弩級コスト
('quantum_fighter', '量子戦闘機', '⚛️', '【使い捨て】5%の確率で敵の体力を半減',
    7, 350, 80, 100, 'ranged', 'air', TRUE,
    100000, '{"iron": 1000, "uranium": 200, "electronics": 300, "oil": 500}', 10800,
    0, 0, (SELECT id FROM battle_special_skills WHERE skill_key = 'quantum_warfare')),

-- 強襲型空母：空カテゴリシナジー、重コスト
('assault_carrier', '強襲型空母', '🛫', '「空」カテゴリと同時出撃で味方全体の攻撃力40%アップ【海】',
    7, 250, 180, 600, 'siege', 'sea', FALSE,
    60000, '{"iron": 1200, "oil": 800, "steel": 200}', 8000,
    180, 150, (SELECT id FROM battle_special_skills WHERE skill_key = 'air_superiority')),

-- 現代スパイ：寝返りスキル、低コスト
('modern_spy', '現代スパイ', '🕵️', '20%で「寝返り」をさせ敵にダメージを与えその分だけ自身を回復【陸】',
    7, 50, 30, 80, 'infantry', 'land', FALSE,
    5000, '{"food": 50, "knowledge": 20}', 600,
    30, 20, (SELECT id FROM battle_special_skills WHERE skill_key = 'defection')),

-- ヨット隊：反射スキル、軽装備
('yacht_squadron', 'ヨット隊', '⛵', '軽装備。10%で扇動を起こし受けた攻撃をそのまま跳ね返す【海】',
    5, 30, 20, 100, 'ranged', 'sea', FALSE,
    3000, '{"wood": 100, "cloth": 30}', 400,
    20, 15, (SELECT id FROM battle_special_skills WHERE skill_key = 'agitation'));

-- ===============================================
-- ⑥ 前提研究・前提建築の設定
-- ===============================================

-- 巡洋艦は造船所が必要
UPDATE civilization_troop_types 
SET prerequisite_building_id = (SELECT id FROM civilization_building_types WHERE building_key = 'naval_dock' LIMIT 1)
WHERE troop_key = 'cruiser' AND prerequisite_building_id IS NULL;

-- 強襲揚陸艦は造船所が必要
UPDATE civilization_troop_types 
SET prerequisite_building_id = (SELECT id FROM civilization_building_types WHERE building_key = 'naval_dock' LIMIT 1)
WHERE troop_key = 'assault_ship' AND prerequisite_building_id IS NULL;

-- 戦術爆撃機は空軍基地が必要
UPDATE civilization_troop_types 
SET prerequisite_building_id = (SELECT id FROM civilization_building_types WHERE building_key = 'air_base' LIMIT 1)
WHERE troop_key = 'tactical_bomber' AND prerequisite_building_id IS NULL;

-- 戦略爆撃機は空軍基地が必要
UPDATE civilization_troop_types 
SET prerequisite_building_id = (SELECT id FROM civilization_building_types WHERE building_key = 'air_base' LIMIT 1)
WHERE troop_key = 'strategic_bomber' AND prerequisite_building_id IS NULL;

-- ミサイルは軍事基地が必要
UPDATE civilization_troop_types 
SET prerequisite_building_id = (SELECT id FROM civilization_building_types WHERE building_key = 'military_base' LIMIT 1)
WHERE troop_key = 'missile' AND prerequisite_building_id IS NULL;

-- 核ミサイルは核サイロと核技術研究が必要
UPDATE civilization_troop_types 
SET prerequisite_building_id = (SELECT id FROM civilization_building_types WHERE building_key = 'nuclear_silo' LIMIT 1),
    prerequisite_research_id = (SELECT id FROM civilization_researches WHERE research_key = 'nuclear_power' LIMIT 1)
WHERE troop_key = 'nuclear_missile';

-- 量子戦闘機は量子コンピューティング研究が必要
UPDATE civilization_troop_types 
SET prerequisite_research_id = (SELECT id FROM civilization_researches WHERE research_key = 'quantum_computing' LIMIT 1)
WHERE troop_key = 'quantum_fighter' AND prerequisite_research_id IS NULL;

-- 強襲型空母は造船所と航空技術が必要
UPDATE civilization_troop_types 
SET prerequisite_building_id = (SELECT id FROM civilization_building_types WHERE building_key = 'naval_dock' LIMIT 1),
    prerequisite_research_id = (SELECT id FROM civilization_researches WHERE research_key = 'aviation' LIMIT 1)
WHERE troop_key = 'assault_carrier';

-- 現代スパイは人工知能研究が必要
UPDATE civilization_troop_types 
SET prerequisite_research_id = (SELECT id FROM civilization_researches WHERE research_key = 'artificial_intelligence' LIMIT 1)
WHERE troop_key = 'modern_spy' AND prerequisite_research_id IS NULL;

-- ヨット隊は航海術研究が必要（ルネサンス時代解禁）
UPDATE civilization_troop_types 
SET prerequisite_research_id = (SELECT id FROM civilization_researches WHERE research_key = 'sailing' LIMIT 1)
WHERE troop_key = 'yacht_squadron' AND prerequisite_research_id IS NULL;

-- ===============================================
-- 完了メッセージ
-- ===============================================
SELECT 'MiniBird units 2026 schema created successfully' AS status;
