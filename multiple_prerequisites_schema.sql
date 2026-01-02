-- ===============================================
-- MiniBird 複数前提条件対応スキーマ
-- 建物、兵士、研究に複数の前提条件を設定可能にする
-- ===============================================

USE microblog;

-- ===============================================
-- ① 建物の複数前提条件テーブル
-- ===============================================
CREATE TABLE IF NOT EXISTS civilization_building_prerequisites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    building_type_id INT UNSIGNED NOT NULL COMMENT '対象建物ID',
    prerequisite_building_id INT UNSIGNED NULL COMMENT '前提建物ID',
    prerequisite_research_id INT UNSIGNED NULL COMMENT '前提研究ID',
    is_required BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'TRUE=必須、FALSE=いずれか1つ',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (building_type_id) REFERENCES civilization_building_types(id) ON DELETE CASCADE,
    FOREIGN KEY (prerequisite_building_id) REFERENCES civilization_building_types(id) ON DELETE CASCADE,
    FOREIGN KEY (prerequisite_research_id) REFERENCES civilization_researches(id) ON DELETE CASCADE,
    INDEX idx_building (building_type_id),
    INDEX idx_prereq_building (prerequisite_building_id),
    INDEX idx_prereq_research (prerequisite_research_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='建物の複数前提条件';

-- ===============================================
-- ② 兵種の複数前提条件テーブル
-- ===============================================
CREATE TABLE IF NOT EXISTS civilization_troop_prerequisites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    troop_type_id INT UNSIGNED NOT NULL COMMENT '対象兵種ID',
    prerequisite_building_id INT UNSIGNED NULL COMMENT '前提建物ID',
    prerequisite_research_id INT UNSIGNED NULL COMMENT '前提研究ID',
    is_required BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'TRUE=必須、FALSE=いずれか1つ',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (troop_type_id) REFERENCES civilization_troop_types(id) ON DELETE CASCADE,
    FOREIGN KEY (prerequisite_building_id) REFERENCES civilization_building_types(id) ON DELETE CASCADE,
    FOREIGN KEY (prerequisite_research_id) REFERENCES civilization_researches(id) ON DELETE CASCADE,
    INDEX idx_troop (troop_type_id),
    INDEX idx_prereq_building (prerequisite_building_id),
    INDEX idx_prereq_research (prerequisite_research_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='兵種の複数前提条件';

-- ===============================================
-- ③ 研究の複数前提条件テーブル
-- ===============================================
CREATE TABLE IF NOT EXISTS civilization_research_prerequisites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    research_id INT UNSIGNED NOT NULL COMMENT '対象研究ID',
    prerequisite_research_id INT UNSIGNED NOT NULL COMMENT '前提研究ID',
    is_required BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'TRUE=必須、FALSE=いずれか1つ',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (research_id) REFERENCES civilization_researches(id) ON DELETE CASCADE,
    FOREIGN KEY (prerequisite_research_id) REFERENCES civilization_researches(id) ON DELETE CASCADE,
    INDEX idx_research (research_id),
    INDEX idx_prereq_research (prerequisite_research_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='研究の複数前提条件';

-- ===============================================
-- ④ 研究の複数アンロック対象テーブル
-- ===============================================
CREATE TABLE IF NOT EXISTS civilization_research_unlocks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    research_id INT UNSIGNED NOT NULL COMMENT '研究ID',
    unlock_building_id INT UNSIGNED NULL COMMENT 'アンロックする建物ID',
    unlock_resource_id INT UNSIGNED NULL COMMENT 'アンロックする資源ID',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (research_id) REFERENCES civilization_researches(id) ON DELETE CASCADE,
    FOREIGN KEY (unlock_building_id) REFERENCES civilization_building_types(id) ON DELETE CASCADE,
    FOREIGN KEY (unlock_resource_id) REFERENCES civilization_resource_types(id) ON DELETE CASCADE,
    INDEX idx_research (research_id),
    INDEX idx_building (unlock_building_id),
    INDEX idx_resource (unlock_resource_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='研究の複数アンロック対象';

-- ===============================================
-- ⑤ 既存の単一前提条件を複数前提条件テーブルに移行
-- ===============================================

-- 建物の既存前提条件を移行
INSERT INTO civilization_building_prerequisites (building_type_id, prerequisite_building_id, prerequisite_research_id, is_required)
SELECT 
    bt.id,
    bt.prerequisite_building_id,
    bt.prerequisite_research_id,
    TRUE
FROM civilization_building_types bt
WHERE bt.prerequisite_building_id IS NOT NULL OR bt.prerequisite_research_id IS NOT NULL;

-- 兵種の既存前提条件を移行
INSERT INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, prerequisite_research_id, is_required)
SELECT 
    tt.id,
    tt.prerequisite_building_id,
    tt.prerequisite_research_id,
    TRUE
FROM civilization_troop_types tt
WHERE tt.prerequisite_building_id IS NOT NULL OR tt.prerequisite_research_id IS NOT NULL;

-- 研究の既存前提条件を移行
INSERT INTO civilization_research_prerequisites (research_id, prerequisite_research_id, is_required)
SELECT 
    r.id,
    r.prerequisite_research_id,
    TRUE
FROM civilization_researches r
WHERE r.prerequisite_research_id IS NOT NULL;

-- 研究の既存アンロック対象を移行
INSERT INTO civilization_research_unlocks (research_id, unlock_building_id, unlock_resource_id)
SELECT 
    r.id,
    r.unlock_building_id,
    r.unlock_resource_id
FROM civilization_researches r
WHERE r.unlock_building_id IS NOT NULL OR r.unlock_resource_id IS NOT NULL;

-- ===============================================
-- ⑥ 使用例：複数前提条件の設定
-- ===============================================

-- 例1: 核サイロは「軍事基地」と「核技術研究」の両方が必要
-- INSERT INTO civilization_building_prerequisites (building_type_id, prerequisite_building_id, is_required)
-- SELECT id, (SELECT id FROM civilization_building_types WHERE building_key = 'military_base'), TRUE
-- FROM civilization_building_types WHERE building_key = 'nuclear_silo';
--
-- INSERT INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
-- SELECT id, (SELECT id FROM civilization_researches WHERE research_key = 'nuclear_power'), TRUE
-- FROM civilization_building_types WHERE building_key = 'nuclear_silo';

-- 例2: 戦車は「兵舎」または「工場」のいずれかが必要
-- INSERT INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
-- SELECT id, (SELECT id FROM civilization_building_types WHERE building_key = 'barracks'), FALSE
-- FROM civilization_troop_types WHERE troop_key = 'tank';
--
-- INSERT INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
-- SELECT id, (SELECT id FROM civilization_building_types WHERE building_key = 'factory'), FALSE
-- FROM civilization_troop_types WHERE troop_key = 'tank';

-- 例3: 高度な研究は複数の前提研究が必要
-- INSERT INTO civilization_research_prerequisites (research_id, prerequisite_research_id, is_required)
-- SELECT id, (SELECT id FROM civilization_researches WHERE research_key = 'physics'), TRUE
-- FROM civilization_researches WHERE research_key = 'nuclear_physics';
--
-- INSERT INTO civilization_research_prerequisites (research_id, prerequisite_research_id, is_required)
-- SELECT id, (SELECT id FROM civilization_researches WHERE research_key = 'chemistry'), TRUE
-- FROM civilization_researches WHERE research_key = 'nuclear_physics';

-- 例4: 研究が複数の建物をアンロック
-- INSERT INTO civilization_research_unlocks (research_id, unlock_building_id)
-- SELECT id, (SELECT id FROM civilization_building_types WHERE building_key = 'factory')
-- FROM civilization_researches WHERE research_key = 'industrialization';
--
-- INSERT INTO civilization_research_unlocks (research_id, unlock_building_id)
-- SELECT id, (SELECT id FROM civilization_building_types WHERE building_key = 'power_plant')
-- FROM civilization_researches WHERE research_key = 'industrialization';

-- ===============================================
-- ⑦ 注意事項
-- ===============================================
-- 
-- このスキーマを使用する際は、civilization_api.phpの以下の関数を更新する必要があります:
-- 
-- 1. 建物建設チェック関数
--    - 単一の prerequisite_building_id チェックから
--    - civilization_building_prerequisites テーブルをチェックに変更
-- 
-- 2. 兵種訓練チェック関数
--    - 単一の prerequisite_building_id/prerequisite_research_id チェックから
--    - civilization_troop_prerequisites テーブルをチェックに変更
-- 
-- 3. 研究開始チェック関数
--    - 単一の prerequisite_research_id チェックから
--    - civilization_research_prerequisites テーブルをチェックに変更
-- 
-- 4. 研究完了時のアンロック処理
--    - 単一の unlock_building_id/unlock_resource_id から
--    - civilization_research_unlocks テーブルをチェックに変更
-- 
-- ===============================================

-- ===============================================
-- 完了メッセージ
-- ===============================================
SELECT 'Multiple prerequisites schema created successfully' AS status;
SELECT 'Building prerequisites migrated' AS status, COUNT(*) as count FROM civilization_building_prerequisites;
SELECT 'Troop prerequisites migrated' AS status, COUNT(*) as count FROM civilization_troop_prerequisites;
SELECT 'Research prerequisites migrated' AS status, COUNT(*) as count FROM civilization_research_prerequisites;
SELECT 'Research unlocks migrated' AS status, COUNT(*) as count FROM civilization_research_unlocks;
