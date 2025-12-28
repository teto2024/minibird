-- ===============================================
-- MiniBird 文明育成システム 戦争システム拡張スキーマ
-- 兵種に体力と相性カテゴリを追加
-- ===============================================

USE microblog;

-- ===============================================
-- 兵種タイプに体力とカテゴリカラムを追加
-- ===============================================
ALTER TABLE civilization_troop_types
ADD COLUMN IF NOT EXISTS health_points INT UNSIGNED NOT NULL DEFAULT 100 COMMENT '体力ポイント' AFTER defense_power,
ADD COLUMN IF NOT EXISTS troop_category ENUM('infantry', 'cavalry', 'ranged', 'siege') NOT NULL DEFAULT 'infantry' COMMENT '兵種カテゴリ（相性判定用）' AFTER health_points;

-- ===============================================
-- 兵種の体力とカテゴリを設定
-- 相性ルール:
--   - infantry（歩兵）は ranged（遠距離）に強い
--   - ranged（遠距離）は cavalry（騎兵）に強い
--   - cavalry（騎兵）は infantry（歩兵）に強い
--   - siege（攻城）は infantry（歩兵）に強いが、cavalry（騎兵）に弱い
-- ===============================================

-- 石器時代
UPDATE civilization_troop_types SET health_points = 50, troop_category = 'ranged' WHERE troop_key = 'hunter';
UPDATE civilization_troop_types SET health_points = 80, troop_category = 'infantry' WHERE troop_key = 'warrior';
UPDATE civilization_troop_types SET health_points = 30, troop_category = 'infantry' WHERE troop_key = 'scout';

-- 青銅器時代
UPDATE civilization_troop_types SET health_points = 100, troop_category = 'infantry' WHERE troop_key = 'spearman';
UPDATE civilization_troop_types SET health_points = 90, troop_category = 'cavalry' WHERE troop_key = 'chariot';
UPDATE civilization_troop_types SET health_points = 60, troop_category = 'infantry' WHERE troop_key = 'militia';
UPDATE civilization_troop_types SET health_points = 120, troop_category = 'infantry' WHERE troop_key = 'phalanx';

-- 鉄器時代
UPDATE civilization_troop_types SET health_points = 120, troop_category = 'infantry' WHERE troop_key = 'swordsman';
UPDATE civilization_troop_types SET health_points = 100, troop_category = 'cavalry' WHERE troop_key = 'cavalry';
UPDATE civilization_troop_types SET health_points = 70, troop_category = 'ranged' WHERE troop_key = 'archer';
UPDATE civilization_troop_types SET health_points = 130, troop_category = 'infantry' WHERE troop_key = 'pikeman';
UPDATE civilization_troop_types SET health_points = 200, troop_category = 'infantry' WHERE troop_key = 'war_elephant';

-- 中世
UPDATE civilization_troop_types SET health_points = 180, troop_category = 'cavalry' WHERE troop_key = 'knight';
UPDATE civilization_troop_types SET health_points = 80, troop_category = 'ranged' WHERE troop_key = 'crossbowman';
UPDATE civilization_troop_types SET health_points = 60, troop_category = 'siege' WHERE troop_key = 'catapult';
UPDATE civilization_troop_types SET health_points = 90, troop_category = 'ranged' WHERE troop_key = 'longbowman';
UPDATE civilization_troop_types SET health_points = 50, troop_category = 'siege' WHERE troop_key = 'trebuchet';

-- ルネサンス
UPDATE civilization_troop_types SET health_points = 100, troop_category = 'ranged' WHERE troop_key = 'musketeer';
UPDATE civilization_troop_types SET health_points = 80, troop_category = 'siege' WHERE troop_key = 'cannon';
UPDATE civilization_troop_types SET health_points = 250, troop_category = 'siege' WHERE troop_key = 'galleon';
UPDATE civilization_troop_types SET health_points = 110, troop_category = 'ranged' WHERE troop_key = 'rifleman';
UPDATE civilization_troop_types SET health_points = 120, troop_category = 'cavalry' WHERE troop_key = 'dragoon';
UPDATE civilization_troop_types SET health_points = 200, troop_category = 'siege' WHERE troop_key = 'frigate';

-- 産業革命
UPDATE civilization_troop_types SET health_points = 130, troop_category = 'infantry' WHERE troop_key = 'infantry';
UPDATE civilization_troop_types SET health_points = 100, troop_category = 'siege' WHERE troop_key = 'artillery';
UPDATE civilization_troop_types SET health_points = 350, troop_category = 'siege' WHERE troop_key = 'ironclad';
UPDATE civilization_troop_types SET health_points = 150, troop_category = 'infantry' WHERE troop_key = 'marine';

-- 現代
UPDATE civilization_troop_types SET health_points = 400, troop_category = 'cavalry' WHERE troop_key = 'tank';
UPDATE civilization_troop_types SET health_points = 200, troop_category = 'ranged' WHERE troop_key = 'fighter';
UPDATE civilization_troop_types SET health_points = 180, troop_category = 'siege' WHERE troop_key = 'bomber';
UPDATE civilization_troop_types SET health_points = 300, troop_category = 'siege' WHERE troop_key = 'submarine';
UPDATE civilization_troop_types SET health_points = 150, troop_category = 'infantry' WHERE troop_key = 'paratroopers';
UPDATE civilization_troop_types SET health_points = 200, troop_category = 'infantry' WHERE troop_key = 'special_forces';
UPDATE civilization_troop_types SET health_points = 80, troop_category = 'siege' WHERE troop_key = 'missile_launcher';
UPDATE civilization_troop_types SET health_points = 250, troop_category = 'ranged' WHERE troop_key = 'stealth_fighter';
UPDATE civilization_troop_types SET health_points = 600, troop_category = 'siege' WHERE troop_key = 'aircraft_carrier';
UPDATE civilization_troop_types SET health_points = 450, troop_category = 'siege' WHERE troop_key = 'nuclear_submarine';

-- ===============================================
-- 戦争ログに詳細な戦闘情報を追加
-- ===============================================
ALTER TABLE civilization_war_logs
ADD COLUMN IF NOT EXISTS attacker_troop_power INT UNSIGNED DEFAULT 0 COMMENT '攻撃側の兵士パワー' AFTER attacker_power,
ADD COLUMN IF NOT EXISTS attacker_equipment_power INT UNSIGNED DEFAULT 0 COMMENT '攻撃側の装備パワー' AFTER attacker_troop_power,
ADD COLUMN IF NOT EXISTS defender_troop_power INT UNSIGNED DEFAULT 0 COMMENT '防御側の兵士パワー' AFTER defender_power,
ADD COLUMN IF NOT EXISTS defender_equipment_power INT UNSIGNED DEFAULT 0 COMMENT '防御側の装備パワー' AFTER defender_troop_power,
ADD COLUMN IF NOT EXISTS troop_advantage_bonus DECIMAL(5,2) DEFAULT 0 COMMENT '相性ボーナス' AFTER defender_equipment_power;

-- テーブル更新完了メッセージ
SELECT 'War system enhancements schema applied successfully' AS status;
