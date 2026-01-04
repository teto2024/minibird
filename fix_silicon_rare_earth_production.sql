-- ===============================================
-- 資源生産の修正スクリプト
-- シリコン精錬所とレアアース鉱山が資源を生産しない問題を修正
-- ===============================================

USE syugetsu2025_clone;

-- ===============================================
-- 問題: シリコン精錬所がシリコンを生産しない
-- ===============================================
-- シリコンのresource_idを取得して、シリコン精錬所の produces_resource_id を設定

-- シリコン精錬所の建物設定を更新（生産率も設定）
UPDATE civilization_building_types 
SET produces_resource_id = (
    SELECT id FROM civilization_resource_types WHERE resource_key = 'silicon'
),
production_rate = 3.0  -- 1時間あたり3個のシリコンを生産
WHERE building_key = 'silicon_foundry';

-- 確認
SELECT building_key, name, produces_resource_id, production_rate 
FROM civilization_building_types 
WHERE building_key = 'silicon_foundry';

-- ===============================================
-- 問題: レアアース鉱山がレアアースを生産しない
-- ===============================================
-- レアアースのresource_idを取得して、レアアース鉱山の produces_resource_id を設定

-- レアアース鉱山の建物設定を更新（生産率も設定）
UPDATE civilization_building_types 
SET produces_resource_id = (
    SELECT id FROM civilization_resource_types WHERE resource_key = 'rare_earth'
),
production_rate = 2.0  -- 1時間あたり2個のレアアースを生産（希少資源なので少なめ）
WHERE building_key = 'rare_earth_mine';

-- 確認
SELECT building_key, name, produces_resource_id, production_rate 
FROM civilization_building_types 
WHERE building_key = 'rare_earth_mine';

-- ===============================================
-- 追加: 他の新時代の生産施設も確認・修正
-- ===============================================

-- プルトニウム精製所（プルトニウムを生産するはず）
UPDATE civilization_building_types 
SET produces_resource_id = (
    SELECT id FROM civilization_resource_types WHERE resource_key = 'plutonium'
),
production_rate = 1.0  -- 1時間あたり1個のプルトニウムを生産（希少資源）
WHERE building_key = 'plutonium_refinery';

-- 量子結晶鉱山（量子結晶を生産するはず）
UPDATE civilization_building_types 
SET produces_resource_id = (
    SELECT id FROM civilization_resource_types WHERE resource_key = 'quantum_crystal'
),
production_rate = 0.5  -- 1時間あたり0.5個の量子結晶を生産（超希少資源）
WHERE building_key = 'quantum_crystal_mine';

-- AIコア製造工場（AIコアを生産するはず）
UPDATE civilization_building_types 
SET produces_resource_id = (
    SELECT id FROM civilization_resource_types WHERE resource_key = 'ai_core'
),
production_rate = 1.0  -- 1時間あたり1個のAIコアを生産
WHERE building_key = 'ai_core_factory';

-- 遺伝子バンク（遺伝子サンプルを生産するはず）
UPDATE civilization_building_types 
SET produces_resource_id = (
    SELECT id FROM civilization_resource_types WHERE resource_key = 'gene_sample'
),
production_rate = 1.5  -- 1時間あたり1.5個の遺伝子サンプルを生産
WHERE building_key = 'gene_vault';

-- ダークマター収集装置（ダークマターを生産するはず）
UPDATE civilization_building_types 
SET produces_resource_id = (
    SELECT id FROM civilization_resource_types WHERE resource_key = 'dark_matter'
),
production_rate = 0.3  -- 1時間あたり0.3個のダークマターを生産（超希少資源）
WHERE building_key = 'dark_matter_harvester';

-- 反物質リアクター（反物質を生産するはず）
UPDATE civilization_building_types 
SET produces_resource_id = (
    SELECT id FROM civilization_resource_types WHERE resource_key = 'antimatter'
),
production_rate = 0.2  -- 1時間あたり0.2個の反物質を生産（最高希少資源）
WHERE building_key = 'antimatter_reactor';

-- ===============================================
-- 修正内容の確認
-- ===============================================
SELECT 
    bt.building_key,
    bt.name,
    bt.icon,
    bt.category,
    rt.name as produces_resource,
    bt.production_rate,
    bt.unlock_era_id
FROM civilization_building_types bt
LEFT JOIN civilization_resource_types rt ON bt.produces_resource_id = rt.id
WHERE bt.building_key IN (
    'silicon_foundry', 
    'rare_earth_mine', 
    'plutonium_refinery',
    'quantum_crystal_mine',
    'ai_core_factory',
    'gene_vault',
    'dark_matter_harvester',
    'antimatter_reactor'
)
ORDER BY bt.unlock_era_id, bt.building_key;

-- 修正完了メッセージ
SELECT '資源生産の修正が完了しました。シリコン、レアアース、その他の新時代資源が正常に生産されるようになります。' AS status;
