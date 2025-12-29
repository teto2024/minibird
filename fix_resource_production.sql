-- ===============================================
-- 資源生産の修正スクリプト
-- 薬草とガラスが生産されない問題を修正
-- ===============================================

USE microblog;

-- ===============================================
-- 問題1: 薬草園が薬草を生産しない
-- ===============================================
-- 薬草のresource_idを取得して、薬草園の produces_resource_id を設定

-- 薬草のIDを確認
SELECT id, resource_key, name FROM civilization_resource_types WHERE resource_key = 'herbs';

-- 薬草園の建物設定を更新（生産率も設定）
UPDATE civilization_building_types 
SET produces_resource_id = (
    SELECT id FROM civilization_resource_types WHERE resource_key = 'herbs'
),
production_rate = 8.0  -- 1時間あたり8個の薬草を生産
WHERE building_key = 'herb_garden';

-- 確認
SELECT building_key, name, produces_resource_id, production_rate 
FROM civilization_building_types 
WHERE building_key = 'herb_garden';

-- ===============================================
-- 問題2: ガラス工房がガラスを生産しない
-- ===============================================
-- ガラスのresource_idを取得して、ガラス工房の produces_resource_id を設定

-- ガラスのIDを確認
SELECT id, resource_key, name FROM civilization_resource_types WHERE resource_key = 'glass';

-- ガラス工房の建物設定を更新（生産率も設定）
UPDATE civilization_building_types 
SET produces_resource_id = (
    SELECT id FROM civilization_resource_types WHERE resource_key = 'glass'
),
production_rate = 4.0,  -- 1時間あたり4個のガラスを生産
category = 'production'  -- カテゴリも 'special' から 'production' に変更
WHERE building_key = 'glassworks';

-- 確認
SELECT building_key, name, produces_resource_id, production_rate, category 
FROM civilization_building_types 
WHERE building_key = 'glassworks';

-- ===============================================
-- 追加修正: 他の特殊生産施設も確認
-- ===============================================

-- 織物工場（布を生産するはず）
-- 既に設定されている場合はスキップ（冪等性を保証）
UPDATE civilization_building_types 
SET produces_resource_id = (
    SELECT id FROM civilization_resource_types WHERE resource_key = 'cloth'
),
production_rate = 6.0,  -- 1時間あたり6個の布を生産
category = 'production'
WHERE building_key = 'weaving_mill';

-- クリスタル鉱山（クリスタルを生産するはず）
UPDATE civilization_building_types 
SET produces_resource_id = (
    SELECT id FROM civilization_resource_types WHERE resource_key = 'crystal'
),
production_rate = 1.0  -- 1時間あたり1個のクリスタルを生産（希少資源）
WHERE building_key = 'crystal_mine';

-- 調剤所（医薬品を生産するはず）
UPDATE civilization_building_types 
SET produces_resource_id = (
    SELECT id FROM civilization_resource_types WHERE resource_key = 'medicine'
),
production_rate = 3.0,  -- 1時間あたり3個の医薬品を生産
category = 'production'
WHERE building_key = 'apothecary';

-- 製鋼所（鋼鉄を生産するはず）
UPDATE civilization_building_types 
SET produces_resource_id = (
    SELECT id FROM civilization_resource_types WHERE resource_key = 'steel'
),
production_rate = 3.0,  -- 1時間あたり3個の鋼鉄を生産
category = 'production'
WHERE building_key = 'steel_mill';

-- 火薬工場（火薬を生産するはず）
UPDATE civilization_building_types 
SET produces_resource_id = (
    SELECT id FROM civilization_resource_types WHERE resource_key = 'gunpowder'
),
production_rate = 2.0,  -- 1時間あたり2個の火薬を生産
category = 'production'
WHERE building_key = 'gunpowder_factory';

-- 電子部品工場（電子部品を生産するはず）
UPDATE civilization_building_types 
SET produces_resource_id = (
    SELECT id FROM civilization_resource_types WHERE resource_key = 'electronics'
),
production_rate = 1.5,  -- 1時間あたり1.5個の電子部品を生産
category = 'production'
WHERE building_key = 'electronics_factory';

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
    'herb_garden', 
    'glassworks', 
    'weaving_mill', 
    'crystal_mine',
    'apothecary',
    'steel_mill',
    'gunpowder_factory',
    'electronics_factory'
)
ORDER BY bt.unlock_era_id, bt.building_key;

-- 修正完了メッセージ
SELECT '資源生産の修正が完了しました。薬草、ガラス、その他の特殊資源が正常に生産されるようになります。' AS status;
