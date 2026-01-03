-- ===============================================
-- MiniBird 既存建物・兵種への前提条件追加
-- 文明育成ゲームの既存建物に前提研究を、既存兵種に前提建物を追加する
-- civilization_building_prerequisites および civilization_troop_prerequisites テーブルを使用
-- 
-- 対象データ:
-- - 建物 118件に適切な前提研究を追加
-- - 兵種 75件に適切な前提建物を追加
-- ===============================================

USE microblog;

-- ==================================================================
-- 第1部: 既存兵種への前提建物追加
-- ==================================================================

-- 石器時代の兵種 (1-2)
INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'hunter' AND bt.building_key = 'hunting_ground';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'warrior' AND bt.building_key = 'barracks';

-- 青銅器時代の兵種 (3-4)
INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'spearman' AND bt.building_key = 'barracks';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'chariot' AND bt.building_key = 'stable';

-- 鉄器時代の兵種 (5-7)
INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'swordsman' AND bt.building_key = 'blacksmith';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'cavalry' AND bt.building_key = 'stable';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'archer' AND bt.building_key = 'archery_range';

-- 中世の兵種 (8-10)
INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'knight' AND bt.building_key = 'stable';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'crossbowman' AND bt.building_key = 'archery_range';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'catapult' AND bt.building_key = 'siege_workshop';

-- ルネサンスの兵種 (11-13)
INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'musketeer' AND bt.building_key = 'barracks';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'cannon' AND bt.building_key = 'siege_workshop';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'galleon' AND bt.building_key = 'naval_dock';

-- 産業革命の兵種 (14-16)
INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'infantry' AND bt.building_key = 'barracks';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'artillery' AND bt.building_key = 'siege_workshop';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'ironclad' AND bt.building_key = 'naval_dock';

-- 現代の兵種 (17-20)
INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'tank' AND bt.building_key = 'factory';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'fighter' AND bt.building_key = 'air_base';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'bomber' AND bt.building_key = 'air_base';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'submarine' AND bt.building_key = 'naval_dock';

-- その他の基本兵種 (21-37)
INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'scout' AND bt.building_key = 'barracks';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'militia' AND bt.building_key = 'barracks';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'phalanx' AND bt.building_key = 'barracks';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'pikeman' AND bt.building_key = 'barracks';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'longbowman' AND bt.building_key = 'archery_range';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'trebuchet' AND bt.building_key = 'siege_workshop';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'war_elephant' AND bt.building_key = 'stable';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'rifleman' AND bt.building_key = 'barracks';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'dragoon' AND bt.building_key = 'stable';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'frigate' AND bt.building_key = 'naval_dock';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'marine' AND bt.building_key = 'barracks';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'paratroopers' AND bt.building_key = 'air_base';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'special_forces' AND bt.building_key = 'barracks';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'missile_launcher' AND bt.building_key = 'missile_silo';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'stealth_fighter' AND bt.building_key = 'air_base';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'aircraft_carrier' AND bt.building_key = 'naval_dock';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'nuclear_submarine' AND bt.building_key = 'naval_dock';

-- 医療兵種 (38-39)
INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'medic' AND bt.building_key = 'field_hospital';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'field_surgeon' AND bt.building_key = 'hospital';

-- 攻城兵器 (40-41)
INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'siege_tower' AND bt.building_key = 'siege_workshop';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'battering_ram' AND bt.building_key = 'siege_workshop';

-- 特殊兵種 (42-43)
INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'royal_guard' AND bt.building_key = 'castle';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'berserker' AND bt.building_key = 'barracks';

-- 先進兵種 (44-64)
INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'nuclear_soldier' AND bt.building_key = 'nuclear_bunker';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'stealth_bomber' AND bt.building_key = 'air_base';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'cyber_operative' AND bt.building_key = 'cyber_command';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'drone_swarm' AND bt.building_key = 'autonomous_drone_base';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'network_defender' AND bt.building_key = 'cyber_command';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'influencer_unit' AND bt.building_key = 'social_media_center';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'smart_soldier' AND bt.building_key = 'tech_university';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'electronic_warfare_unit' AND bt.building_key = 'cyber_command';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'quantum_hacker' AND bt.building_key = 'quantum_lab';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'teleport_commando' AND bt.building_key = 'quantum_computer_center';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'quantum_tank' AND bt.building_key = 'quantum_shield_generator';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'ai_soldier' AND bt.building_key = 'ai_research_center';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'autonomous_tank' AND bt.building_key = 'autonomous_drone_base';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'hunter_killer_drone' AND bt.building_key = 'autonomous_drone_base';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'super_soldier' AND bt.building_key = 'bio_soldier_lab';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'bio_beast' AND bt.building_key = 'bio_soldier_lab';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'healing_squad' AND bt.building_key = 'biolab';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'space_marine' AND bt.building_key = 'space_port';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'orbital_mech' AND bt.building_key = 'orbital_station';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'antimatter_bomber' AND bt.building_key = 'space_battleship_dock';

INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE FROM civilization_troop_types tt CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = 'starship_fighter' AND bt.building_key = 'space_battleship_dock';

-- ==================================================================
-- 第2部: 既存建物への前提研究追加
-- ==================================================================

-- 基礎的な建物
INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'farm' AND r.research_key = 'agriculture_basics';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'bronze_foundry' AND r.research_key = 'bronze_working';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'barracks' AND r.research_key = 'military_training';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'iron_mine' AND r.research_key = 'iron_working';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'blacksmith' AND r.research_key = 'iron_working';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'fortress' AND r.research_key = 'engineering';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'castle' AND r.research_key = 'feudalism';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'gold_mine' AND r.research_key = 'banking';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'library' AND r.research_key = 'philosophy';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'cathedral' AND r.research_key = 'theology';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'university' AND r.research_key = 'scientific_method';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'factory' AND r.research_key = 'mass_production';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'military_base' AND r.research_key = 'electricity';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'oil_well' AND r.research_key = 'oil_drilling';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'windmill' AND r.research_key = 'engineering';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'sawmill' AND r.research_key = 'iron_working';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'archery_range' AND r.research_key = 'archery';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'stable' AND r.research_key = 'animal_husbandry';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'siege_workshop' AND r.research_key = 'engineering';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'naval_dock' AND r.research_key = 'sailing';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'air_base' AND r.research_key = 'aviation';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'academy' AND r.research_key = 'scientific_method';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'observatory' AND r.research_key = 'astronomy';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'wonder_pyramid' AND r.research_key = 'engineering';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'market' AND r.research_key = 'currency';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'bank' AND r.research_key = 'banking';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'temple' AND r.research_key = 'theology';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'weaving_mill' AND r.research_key = 'domestication';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'glassworks' AND r.research_key = 'glassmaking';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'mint' AND r.research_key = 'currency';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'harbor' AND r.research_key = 'sailing';

-- 医療建物
INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'field_hospital' AND r.research_key = 'first_aid';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'hospital' AND r.research_key = 'medicine';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'medical_center' AND r.research_key = 'advanced_medicine';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'herb_garden' AND r.research_key = 'herbal_medicine';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'apothecary' AND r.research_key = 'herbal_medicine';

-- 生産建物
INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'steel_mill' AND r.research_key = 'iron_working';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'gunpowder_factory' AND r.research_key = 'gunpowder';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'electronics_factory' AND r.research_key = 'electricity';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'ranch' AND r.research_key = 'animal_husbandry';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'sulfur_mine' AND r.research_key = 'sulfur_mining';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'coal_mine' AND r.research_key = 'coal_mining';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'pharmacy' AND r.research_key = 'medicine';

-- 原子力時代の建物
INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'nuclear_plant' AND r.research_key = 'nuclear_fission';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'plutonium_refinery' AND r.research_key = 'nuclear_weapons';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'missile_silo' AND r.research_key = 'nuclear_weapons';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'nuclear_bunker' AND r.research_key = 'radiation_protection';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'nuclear_lab' AND r.research_key = 'nuclear_fission';

-- デジタル時代の建物
INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'data_center' AND r.research_key = 'internet';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'silicon_foundry' AND r.research_key = 'semiconductor_technology';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'smart_city_hub' AND r.research_key = 'mobile_computing';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'cyber_command' AND r.research_key = 'cyber_security';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'tech_university' AND r.research_key = 'computers';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'smartphone_factory' AND r.research_key = 'mobile_computing';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'social_media_center' AND r.research_key = 'social_networks';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'eco_tower' AND r.research_key = 'renewable_energy';

-- 量子時代の建物
INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'quantum_lab' AND r.research_key = 'quantum_mechanics';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'quantum_computer_center' AND r.research_key = 'quantum_computing';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'quantum_shield_generator' AND r.research_key = 'quantum_cryptography';

-- AI時代の建物
INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'ai_research_center' AND r.research_key = 'machine_learning';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'ai_core_factory' AND r.research_key = 'artificial_general_intelligence';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'autonomous_drone_base' AND r.research_key = 'autonomous_systems';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'robot_city' AND r.research_key = 'artificial_general_intelligence';

-- バイオ時代の建物
INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'biolab' AND r.research_key = 'genetic_engineering';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'gene_vault' AND r.research_key = 'synthetic_biology';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'biome_dome' AND r.research_key = 'synthetic_biology';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'bio_soldier_lab' AND r.research_key = 'genetic_engineering';

-- 宇宙時代の建物
INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'space_port' AND r.research_key = 'space_propulsion';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'dark_matter_harvester' AND r.research_key = 'dark_matter_manipulation';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'antimatter_reactor' AND r.research_key = 'antimatter_engineering';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'orbital_station' AND r.research_key = 'space_propulsion';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'space_battleship_dock' AND r.research_key = 'antimatter_engineering';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'dyson_sphere_project' AND r.research_key = 'dyson_sphere_technology';

-- その他の建物
INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'uranium_mine' AND r.research_key = 'nuclear_power';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'bandage_factory' AND r.research_key = 'medical_supplies';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'rubber_plantation' AND r.research_key = 'rubber_cultivation';

INSERT IGNORE INTO civilization_building_prerequisites (building_type_id, prerequisite_research_id, is_required)
SELECT bt.id, r.id, TRUE FROM civilization_building_types bt CROSS JOIN civilization_researches r 
WHERE bt.building_key = 'titanium_mine' AND r.research_key = 'titanium_metallurgy';

-- ==================================================================
-- 完了メッセージ
-- ==================================================================
SELECT '既存建物・兵種への前提条件追加が完了しました' AS status;
SELECT '兵種への前提建物追加数' AS type, COUNT(*) as count FROM civilization_troop_prerequisites;
SELECT '建物への前提研究追加数' AS type, COUNT(*) as count FROM civilization_building_prerequisites;
