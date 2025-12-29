-- ===============================================
-- 出撃兵士数上限システム スキーマ
-- 司令部、軍事センターなど出撃上限をアップする建物を追加
-- ===============================================

USE microblog;

-- ===============================================
-- 建物タイプマスターにtroop_deployment_bonus列を追加
-- ===============================================
ALTER TABLE civilization_building_types 
ADD COLUMN IF NOT EXISTS troop_deployment_bonus INT UNSIGNED NOT NULL DEFAULT 0 
COMMENT '出撃兵士数上限ボーナス（レベルごとに加算）';

-- ===============================================
-- 司令部・軍事センターなど出撃上限アップ建物を追加
-- ===============================================
INSERT INTO civilization_building_types 
(building_key, name, icon, description, category, produces_resource_id, production_rate, max_level, unlock_era_id, base_build_cost_coins, base_build_cost_resources, base_build_time_seconds, population_capacity, military_power, troop_deployment_bonus) 
VALUES
-- 青銅器時代: 司令部 (基本の出撃上限アップ)
('command_post', '司令部', '🏛️', '兵士の指揮統制を行う施設。出撃上限+50/レベル', 'military', NULL, 0, 10, 2, 800, '{"wood": 100, "stone": 80}', 600, 0, 15, 50),

-- 鉄器時代: 軍事センター (中程度の出撃上限アップ)
('military_center', '軍事センター', '🎪', '大規模な軍事訓練・指揮施設。出撃上限+100/レベル', 'military', NULL, 0, 10, 3, 2500, '{"stone": 200, "iron": 50}', 1800, 0, 40, 100),

-- 中世: 戦略本部 (上級の出撃上限アップ)  
('strategic_hq', '戦略本部', '🗺️', '軍事戦略を立案・指揮する最高司令部。出撃上限+200/レベル', 'military', NULL, 0, 5, 4, 8000, '{"stone": 400, "iron": 150, "gold": 30}', 5400, 0, 100, 200),

-- 産業革命: 総司令部 (最上級の出撃上限アップ)
('supreme_command', '総司令部', '⭐', '軍全体を統括する最高司令機関。出撃上限+500/レベル', 'military', NULL, 0, 3, 6, 50000, '{"iron": 500, "oil": 150, "gold": 100}', 18000, 0, 300, 500)

ON DUPLICATE KEY UPDATE 
    troop_deployment_bonus = VALUES(troop_deployment_bonus);

-- ===============================================
-- 既存の軍事建物にもtroop_deployment_bonusを設定
-- ===============================================
-- 兵舎: 少量のボーナス
UPDATE civilization_building_types SET troop_deployment_bonus = 10 
WHERE building_key = 'barracks';

-- 要塞: 中程度のボーナス
UPDATE civilization_building_types SET troop_deployment_bonus = 30
WHERE building_key = 'fortress';

-- 城: ボーナス
UPDATE civilization_building_types SET troop_deployment_bonus = 50
WHERE building_key = 'castle';

-- 軍事基地: 大量のボーナス
UPDATE civilization_building_types SET troop_deployment_bonus = 200
WHERE building_key = 'military_base';

-- 空軍基地: 大量のボーナス
UPDATE civilization_building_types SET troop_deployment_bonus = 150
WHERE building_key = 'air_base';

-- 完了メッセージ
SELECT 'Troop deployment limit schema applied successfully' AS status;
