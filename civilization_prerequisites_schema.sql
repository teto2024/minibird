-- ===============================================
-- MiniBird 文明育成システム 前提条件追加スキーマ
-- 建物・兵士雇用に前提条件（建物・研究）を追加
-- ===============================================

USE microblog;

-- ===============================================
-- 建物タイプに前提条件カラムを追加
-- ===============================================
ALTER TABLE civilization_building_types
ADD COLUMN IF NOT EXISTS prerequisite_building_id INT UNSIGNED NULL COMMENT '前提建物ID' AFTER unlock_era_id,
ADD COLUMN IF NOT EXISTS prerequisite_research_id INT UNSIGNED NULL COMMENT '前提研究ID' AFTER prerequisite_building_id;

-- ===============================================
-- 兵種タイプに前提条件カラムを追加
-- ===============================================
ALTER TABLE civilization_troop_types
ADD COLUMN IF NOT EXISTS prerequisite_building_id INT UNSIGNED NULL COMMENT '前提建物ID（例：兵舎が必要）' AFTER unlock_era_id,
ADD COLUMN IF NOT EXISTS prerequisite_research_id INT UNSIGNED NULL COMMENT '前提研究ID' AFTER prerequisite_building_id;

-- ===============================================
-- 建物の前提条件を設定
-- ===============================================

-- 兵舎は伐採場または採石場が必要（基礎資材を確保してから軍事建物）
UPDATE civilization_building_types SET prerequisite_building_id = (SELECT id FROM civilization_building_types WHERE building_key = 'lumber_camp' LIMIT 1) WHERE building_key = 'barracks';

-- 要塞は兵舎が必要
UPDATE civilization_building_types SET prerequisite_building_id = (SELECT id FROM civilization_building_types WHERE building_key = 'barracks' LIMIT 1) WHERE building_key = 'fortress';

-- 城は要塞が必要
UPDATE civilization_building_types SET prerequisite_building_id = (SELECT id FROM civilization_building_types WHERE building_key = 'fortress' LIMIT 1) WHERE building_key = 'castle';

-- 軍事基地は城が必要
UPDATE civilization_building_types SET prerequisite_building_id = (SELECT id FROM civilization_building_types WHERE building_key = 'castle' LIMIT 1) WHERE building_key = 'military_base';

-- 空軍基地は軍事基地が必要
UPDATE civilization_building_types SET prerequisite_building_id = (SELECT id FROM civilization_building_types WHERE building_key = 'military_base' LIMIT 1) WHERE building_key = 'air_base';

-- 大学は図書館が必要
UPDATE civilization_building_types SET prerequisite_building_id = (SELECT id FROM civilization_building_types WHERE building_key = 'library' LIMIT 1) WHERE building_key = 'university';

-- 図書館は家が必要（人口を増やしてから）
UPDATE civilization_building_types SET prerequisite_building_id = (SELECT id FROM civilization_building_types WHERE building_key = 'house' LIMIT 1) WHERE building_key = 'library';

-- ===============================================
-- 兵種の前提条件を設定
-- ===============================================

-- 戦士は兵舎が必要
UPDATE civilization_troop_types SET prerequisite_building_id = (SELECT id FROM civilization_building_types WHERE building_key = 'barracks' LIMIT 1) WHERE troop_key = 'warrior';

-- 槍兵は兵舎が必要
UPDATE civilization_troop_types SET prerequisite_building_id = (SELECT id FROM civilization_building_types WHERE building_key = 'barracks' LIMIT 1) WHERE troop_key = 'spearman';

-- 戦車は兵舎が必要
UPDATE civilization_troop_types SET prerequisite_building_id = (SELECT id FROM civilization_building_types WHERE building_key = 'barracks' LIMIT 1) WHERE troop_key = 'chariot';

-- 剣士は鍛冶場が必要
UPDATE civilization_troop_types SET prerequisite_building_id = (SELECT id FROM civilization_building_types WHERE building_key = 'blacksmith' LIMIT 1) WHERE troop_key = 'swordsman';

-- 弓兵は弓術場が必要（存在する場合）
UPDATE civilization_troop_types SET prerequisite_building_id = (SELECT id FROM civilization_building_types WHERE building_key = 'archery_range' LIMIT 1) WHERE troop_key = 'archer';

-- 騎兵は厩舎が必要（存在する場合）
UPDATE civilization_troop_types SET prerequisite_building_id = (SELECT id FROM civilization_building_types WHERE building_key = 'stable' LIMIT 1) WHERE troop_key = 'cavalry';

-- 騎士は城が必要
UPDATE civilization_troop_types SET prerequisite_building_id = (SELECT id FROM civilization_building_types WHERE building_key = 'castle' LIMIT 1) WHERE troop_key = 'knight';

-- カタパルトは攻城兵器工房が必要（存在する場合）
UPDATE civilization_troop_types SET prerequisite_building_id = (SELECT id FROM civilization_building_types WHERE building_key = 'siege_workshop' LIMIT 1) WHERE troop_key = 'catapult';

-- 戦車（現代）は軍事基地が必要
UPDATE civilization_troop_types SET prerequisite_building_id = (SELECT id FROM civilization_building_types WHERE building_key = 'military_base' LIMIT 1) WHERE troop_key = 'tank';

-- 戦闘機・爆撃機は空軍基地が必要
UPDATE civilization_troop_types SET prerequisite_building_id = (SELECT id FROM civilization_building_types WHERE building_key = 'air_base' LIMIT 1) WHERE troop_key = 'fighter';
UPDATE civilization_troop_types SET prerequisite_building_id = (SELECT id FROM civilization_building_types WHERE building_key = 'air_base' LIMIT 1) WHERE troop_key = 'bomber';

-- 海軍ユニットは造船所が必要
UPDATE civilization_troop_types SET prerequisite_building_id = (SELECT id FROM civilization_building_types WHERE building_key = 'naval_dock' LIMIT 1) WHERE troop_key = 'galleon';
UPDATE civilization_troop_types SET prerequisite_building_id = (SELECT id FROM civilization_building_types WHERE building_key = 'naval_dock' LIMIT 1) WHERE troop_key = 'ironclad';
UPDATE civilization_troop_types SET prerequisite_building_id = (SELECT id FROM civilization_building_types WHERE building_key = 'naval_dock' LIMIT 1) WHERE troop_key = 'submarine';

-- テーブル更新完了メッセージ
SELECT 'Civilization prerequisites schema applied successfully' AS status;
