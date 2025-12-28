-- ===============================================
-- MiniBird 文明育成システム拡張スキーマ
-- 追加資源、建物、兵種、ブースト機能
-- ===============================================

USE microblog;

-- ===============================================
-- 兵種タイプマスター
-- ===============================================
CREATE TABLE IF NOT EXISTS civilization_troop_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    troop_key VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(50) NOT NULL,
    description TEXT,
    unlock_era_id INT UNSIGNED NULL COMMENT 'アンロックに必要な時代',
    attack_power INT UNSIGNED NOT NULL DEFAULT 10,
    defense_power INT UNSIGNED NOT NULL DEFAULT 5,
    train_cost_coins INT UNSIGNED NOT NULL DEFAULT 100,
    train_cost_resources JSON COMMENT '訓練に必要な資源 {"food": 10, "iron": 5}',
    train_time_seconds INT UNSIGNED NOT NULL DEFAULT 60,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_unlock_era (unlock_era_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='兵種タイプマスター';

-- ===============================================
-- ユーザー兵士
-- ===============================================
CREATE TABLE IF NOT EXISTS user_civilization_troops (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    troop_type_id INT UNSIGNED NOT NULL,
    count INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (troop_type_id) REFERENCES civilization_troop_types(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_troop (user_id, troop_type_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='ユーザー兵士';

-- ===============================================
-- ブーストテーブル
-- ===============================================
CREATE TABLE IF NOT EXISTS civilization_boosts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    boost_type VARCHAR(50) NOT NULL COMMENT 'production_2x, research_speed, build_speed など',
    multiplier DECIMAL(5,2) NOT NULL DEFAULT 2.00,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_boost (user_id, boost_type),
    INDEX idx_user (user_id),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='ユーザーブースト';

-- ===============================================
-- 追加資源タイプ
-- ===============================================
INSERT IGNORE INTO civilization_resource_types (resource_key, name, icon, description, unlock_order, color) VALUES
('crystal', 'クリスタル', '💎', '高度な技術に必要', 6, '#9932CC'),
('mana', 'マナ', '✨', '魔法の力の源', 7, '#4B0082'),
('uranium', 'ウラン', '☢️', '核技術に必要な資源', 8, '#32CD32'),
('diamond', 'ダイヤモンド', '💠', '最高級の資源', 9, '#00CED1');

-- ===============================================
-- 追加建物タイプ
-- ===============================================
INSERT IGNORE INTO civilization_building_types (building_key, name, icon, description, category, produces_resource_id, production_rate, max_level, unlock_era_id, base_build_cost_coins, base_build_cost_resources, base_build_time_seconds, population_capacity, military_power) VALUES
-- 追加の住居系
('apartment', 'アパートメント', '🏢', '現代的な集合住宅', 'housing', NULL, 0, 10, 7, 30000, '{"iron": 300, "oil": 100}', 18000, 100, 0),
('manor', '荘園', '🏰', '広大な領地を持つ邸宅', 'housing', NULL, 0, 5, 4, 8000, '{"stone": 200, "wood": 150, "gold": 30}', 7200, 40, 5),

-- 追加の生産系
('oil_well', '油井', '🛢️', '石油を採掘する', 'production', 8, 3, 15, 6, 20000, '{"iron": 400, "stone": 200}', 10800, 0, 0),
('crystal_mine', 'クリスタル鉱山', '💎', 'クリスタルを採掘する', 'production', NULL, 0, 10, 5, 30000, '{"stone": 500, "gold": 100}', 14400, 0, 0),
('windmill', '風車', '🌬️', '効率的に穀物を生産', 'production', 1, 35, 10, 4, 3000, '{"wood": 150, "stone": 50}', 1800, 0, 0),
('sawmill', '製材所', '🪚', '木材を効率的に生産', 'production', 2, 20, 10, 3, 2000, '{"iron": 40, "stone": 60}', 1200, 0, 0),

-- 追加の軍事系
('archery_range', '弓術場', '🏹', '弓兵を訓練する', 'military', NULL, 0, 10, 2, 800, '{"wood": 80, "stone": 40}', 480, 0, 15),
('stable', '厩舎', '🐴', '騎兵を訓練する', 'military', NULL, 0, 10, 3, 2000, '{"wood": 100, "iron": 50, "food": 100}', 900, 0, 30),
('siege_workshop', '攻城兵器工房', '⚙️', '攻城兵器を製造', 'military', NULL, 0, 5, 4, 5000, '{"iron": 200, "wood": 150}', 3600, 0, 80),
('naval_dock', '造船所', '⚓', '海軍を建造する', 'military', NULL, 0, 10, 5, 10000, '{"wood": 300, "iron": 150, "gold": 50}', 7200, 0, 100),
('air_base', '空軍基地', '✈️', '航空機を配備', 'military', NULL, 0, 5, 7, 80000, '{"iron": 1000, "oil": 500}', 28800, 0, 1000),

-- 追加の研究系
('academy', 'アカデミー', '🎓', '高度な研究を行う', 'research', 7, 10, 10, 5, 12000, '{"stone": 400, "gold": 80, "knowledge": 30}', 5400, 0, 0),
('observatory', '天文台', '🔭', '天体観測と科学研究', 'research', 7, 8, 10, 4, 6000, '{"stone": 300, "gold": 50}', 3600, 0, 0),

-- 追加の特殊建物
('wonder_pyramid', 'ピラミッド', '🔺', '古代の驚異', 'special', NULL, 0, 1, 2, 50000, '{"stone": 1000, "gold": 200}', 86400, 0, 0),
('wonder_colosseum', 'コロセウム', '🏟️', '娯楽の殿堂', 'special', NULL, 0, 1, 3, 40000, '{"stone": 800, "iron": 200}', 72000, 50, 0),
('wonder_great_wall', '万里の長城', '🧱', '究極の防御施設', 'special', NULL, 0, 1, 4, 100000, '{"stone": 2000, "iron": 500}', 172800, 0, 500),
('market', '市場', '🏪', '資源の交換ができる', 'special', NULL, 0, 10, 2, 1000, '{"wood": 50, "stone": 30}', 600, 0, 0),
('bank', '銀行', '🏦', 'コインを増やす', 'special', 6, 1, 10, 4, 5000, '{"stone": 200, "gold": 50}', 3600, 0, 0),
('temple', '神殿', '🛕', '文化と信仰の中心', 'special', NULL, 0, 5, 3, 4000, '{"stone": 200, "gold": 30}', 2400, 10, 0);

-- ===============================================
-- 兵種初期データ
-- ===============================================
INSERT IGNORE INTO civilization_troop_types (troop_key, name, icon, description, unlock_era_id, attack_power, defense_power, train_cost_coins, train_cost_resources, train_time_seconds) VALUES
-- 石器時代
('hunter', '狩人', '🏹', '基本的な遠距離兵', 1, 5, 3, 50, '{"food": 10}', 30),
('warrior', '戦士', '⚔️', '基本的な近接兵', 1, 8, 5, 80, '{"food": 15}', 45),

-- 青銅器時代
('spearman', '槍兵', '🗡️', '騎兵に強い歩兵', 2, 10, 8, 120, '{"food": 20, "bronze": 5}', 60),
('chariot', '戦車', '🛞', '高速突撃ユニット', 2, 15, 6, 200, '{"food": 30, "wood": 20, "bronze": 10}', 120),

-- 鉄器時代
('swordsman', '剣士', '⚔️', '強力な近接兵', 3, 20, 15, 250, '{"food": 30, "iron": 10}', 90),
('cavalry', '騎兵', '🐎', '機動力の高い騎馬兵', 3, 25, 12, 350, '{"food": 50, "iron": 15}', 150),
('archer', '弓兵', '🏹', '強化された遠距離兵', 3, 18, 8, 180, '{"food": 25, "wood": 10}', 75),

-- 中世
('knight', '騎士', '🛡️', '重装騎兵', 4, 40, 30, 600, '{"food": 80, "iron": 30, "gold": 5}', 300),
('crossbowman', 'クロスボウ兵', '🎯', '強力な遠距離兵', 4, 30, 15, 400, '{"food": 40, "iron": 20, "wood": 15}', 180),
('catapult', 'カタパルト', '🪨', '攻城兵器', 4, 50, 10, 800, '{"wood": 100, "iron": 50}', 600),

-- ルネサンス
('musketeer', 'マスケット銃兵', '🔫', '火器を使う歩兵', 5, 45, 20, 500, '{"food": 50, "iron": 25}', 240),
('cannon', '大砲', '💣', '強力な攻城兵器', 5, 80, 15, 1200, '{"iron": 80, "gold": 20}', 900),
('galleon', 'ガレオン船', '⛵', '海上戦闘ユニット', 5, 60, 40, 1500, '{"wood": 200, "iron": 80, "gold": 30}', 1800),

-- 産業革命
('infantry', '歩兵', '🎖️', '近代歩兵', 6, 60, 40, 800, '{"food": 60, "iron": 40}', 300),
('artillery', '砲兵', '💥', '長距離砲撃ユニット', 6, 100, 25, 2000, '{"iron": 150, "oil": 30}', 1200),
('ironclad', '装甲艦', '🚢', '強力な海上ユニット', 6, 120, 80, 5000, '{"iron": 300, "oil": 100}', 3600),

-- 現代
('tank', '戦車', '🚜', '強力な陸上ユニット', 7, 150, 100, 8000, '{"iron": 200, "oil": 150}', 1800),
('fighter', '戦闘機', '✈️', '航空戦闘ユニット', 7, 180, 50, 12000, '{"iron": 300, "oil": 200}', 2400),
('bomber', '爆撃機', '💣', '対地攻撃航空機', 7, 250, 30, 15000, '{"iron": 400, "oil": 300}', 3600),
('submarine', '潜水艦', '🚤', 'ステルス海上ユニット', 7, 200, 60, 10000, '{"iron": 250, "oil": 200}', 2700);

-- ===============================================
-- 追加研究データ
-- ===============================================
INSERT IGNORE INTO civilization_researches (research_key, name, icon, description, era_id, unlock_building_id, unlock_resource_id, research_cost_points, research_time_seconds, prerequisite_research_id) VALUES
-- 産業革命の追加研究
('oil_drilling', '石油掘削', '🛢️', '石油を採掘する技術', 6, NULL, 8, 1000, 6000, 15),
('mass_production', '大量生産', '🏭', '製造効率を上げる', 6, NULL, NULL, 1100, 6600, 15),
('aviation', '航空技術', '✈️', '空を飛ぶ技術', 6, NULL, NULL, 1300, 7200, 16),

-- 現代の研究
('nuclear_power', '原子力', '☢️', '核の力を利用', 7, NULL, NULL, 2000, 10800, 16),
('computers', 'コンピュータ', '💻', 'デジタル技術', 7, NULL, NULL, 1800, 9000, 13),
('internet', 'インターネット', '🌐', '世界をつなぐ', 7, NULL, NULL, 2500, 14400, NULL);

-- テーブル作成完了メッセージ
SELECT 'Civilization extended schema created successfully' AS status;
