-- ===============================================
-- fix_heal_cost_resources_2026.sql
-- 治療資源が設定されていない兵種の修正
-- (薬草: herbs, 医薬品: medicine, 包帯: bandages)
-- ===============================================

-- Era 7 兵種（現代・近代戦争）: medicine のみ
UPDATE civilization_troop_types 
SET heal_cost_resources = '{"medicine": 3}'
WHERE id = 66 AND heal_cost_resources IS NULL;

UPDATE civilization_troop_types 
SET heal_cost_resources = '{"medicine": 3}'
WHERE id = 67 AND heal_cost_resources IS NULL;

UPDATE civilization_troop_types 
SET heal_cost_resources = '{"medicine": 3}'
WHERE id = 68 AND heal_cost_resources IS NULL;

UPDATE civilization_troop_types 
SET heal_cost_resources = '{"medicine": 3}'
WHERE id = 69 AND heal_cost_resources IS NULL;

-- 使い捨てユニット (ミサイル、核ミサイル、量子戦闘機) は治療不要
UPDATE civilization_troop_types 
SET heal_cost_resources = NULL
WHERE id IN (70, 71, 72) AND is_disposable = TRUE;

UPDATE civilization_troop_types 
SET heal_cost_resources = '{"medicine": 3}'
WHERE id = 73 AND heal_cost_resources IS NULL;

-- ID 74: 現代スパイ (Era 7) - infantry
UPDATE civilization_troop_types 
SET heal_cost_resources = '{"medicine": 3}'
WHERE id = 74 AND heal_cost_resources IS NULL;

-- ID 75: ヨット隊 (Era 5) - light unit, uses herbs + medicine like other era 5 units
UPDATE civilization_troop_types 
SET heal_cost_resources = '{"herbs": 2, "medicine": 1}'
WHERE id = 75 AND heal_cost_resources IS NULL;

-- Era 8 兵種（核対応歩兵、ステルス爆撃機）: medicine 3
UPDATE civilization_troop_types 
SET heal_cost_resources = '{"medicine": 3}'
WHERE id = 44 AND heal_cost_resources IS NULL;

UPDATE civilization_troop_types 
SET heal_cost_resources = '{"medicine": 3}'
WHERE id = 45 AND heal_cost_resources IS NULL;

-- Era 9 兵種（サイバー系）: medicine 3
UPDATE civilization_troop_types 
SET heal_cost_resources = '{"medicine": 3}'
WHERE id = 46 AND heal_cost_resources IS NULL;

UPDATE civilization_troop_types 
SET heal_cost_resources = '{"medicine": 3}'
WHERE id = 47 AND heal_cost_resources IS NULL;

UPDATE civilization_troop_types 
SET heal_cost_resources = '{"medicine": 3}'
WHERE id = 48 AND heal_cost_resources IS NULL;

-- Era 10 兵種（スマート系）: medicine 4
UPDATE civilization_troop_types 
SET heal_cost_resources = '{"medicine": 4}'
WHERE id = 49 AND heal_cost_resources IS NULL;

UPDATE civilization_troop_types 
SET heal_cost_resources = '{"medicine": 4}'
WHERE id = 50 AND heal_cost_resources IS NULL;

UPDATE civilization_troop_types 
SET heal_cost_resources = '{"medicine": 4}'
WHERE id = 51 AND heal_cost_resources IS NULL;

-- Era 11 兵種（量子系）: medicine 5
UPDATE civilization_troop_types 
SET heal_cost_resources = '{"medicine": 5}'
WHERE id = 52 AND heal_cost_resources IS NULL;

UPDATE civilization_troop_types 
SET heal_cost_resources = '{"medicine": 5}'
WHERE id = 53 AND heal_cost_resources IS NULL;

UPDATE civilization_troop_types 
SET heal_cost_resources = '{"medicine": 5}'
WHERE id = 54 AND heal_cost_resources IS NULL;

-- Era 12 兵種（AI系）: medicine 5 - 実際はロボットなので修理リソースが適切かも
UPDATE civilization_troop_types 
SET heal_cost_resources = '{"medicine": 5}'
WHERE id = 55 AND heal_cost_resources IS NULL;

UPDATE civilization_troop_types 
SET heal_cost_resources = '{"medicine": 5}'
WHERE id = 56 AND heal_cost_resources IS NULL;

UPDATE civilization_troop_types 
SET heal_cost_resources = '{"medicine": 5}'
WHERE id = 57 AND heal_cost_resources IS NULL;

-- Era 13 兵種（バイオ系）: medicine 6
UPDATE civilization_troop_types 
SET heal_cost_resources = '{"medicine": 6}'
WHERE id = 58 AND heal_cost_resources IS NULL;

UPDATE civilization_troop_types 
SET heal_cost_resources = '{"medicine": 6}'
WHERE id = 59 AND heal_cost_resources IS NULL;

UPDATE civilization_troop_types 
SET heal_cost_resources = '{"medicine": 6}'
WHERE id = 60 AND heal_cost_resources IS NULL;

-- Era 14 兵種（宇宙系）: medicine 7
UPDATE civilization_troop_types 
SET heal_cost_resources = '{"medicine": 7}'
WHERE id = 61 AND heal_cost_resources IS NULL;

UPDATE civilization_troop_types 
SET heal_cost_resources = '{"medicine": 7}'
WHERE id = 62 AND heal_cost_resources IS NULL;

UPDATE civilization_troop_types 
SET heal_cost_resources = '{"medicine": 7}'
WHERE id = 63 AND heal_cost_resources IS NULL;

UPDATE civilization_troop_types 
SET heal_cost_resources = '{"medicine": 7}'
WHERE id = 64 AND heal_cost_resources IS NULL;
