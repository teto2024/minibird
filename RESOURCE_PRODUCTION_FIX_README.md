# 資源生産の修正について

## 問題の概要

文明育成ゲームにおいて、以下の資源が建物を建設しても生産されない問題がありました：

### 主な問題
1. **薬草** (🌿) - 薬草園を建設しても生産されない
2. **ガラス** (🔮) - ガラス工房を建設しても生産されない

### その他の問題
同様の問題が他の特殊生産施設にも存在していました：
- 布 (🧵) - 織物工場
- クリスタル (💎) - クリスタル鉱山
- 医薬品 (💊) - 調剤所
- 鋼鉄 (⚙️) - 製鋼所
- 火薬 (💥) - 火薬工場
- 電子部品 (🔌) - 電子部品工場

## 原因

データベースの `civilization_building_types` テーブルにおいて、これらの建物の `produces_resource_id` カラムが `NULL` に設定されていました。そのため、建物を建設しても対応する資源が生産されませんでした。

## 解決方法

### 修正スクリプトの適用

`fix_resource_production.sql` を実行してください：

```bash
mysql -u root -p microblog < fix_resource_production.sql
```

または、MySQLクライアントで：

```sql
USE microblog;
SOURCE fix_resource_production.sql;
```

### 修正内容

このスクリプトは以下を実行します：

1. **薬草園** - 薬草を1時間あたり8個生産するように設定
2. **ガラス工房** - ガラスを1時間あたり4個生産するように設定
3. **織物工場** - 布を1時間あたり6個生産するように設定
4. **クリスタル鉱山** - クリスタルを1時間あたり1個生産するように設定（希少資源）
5. **調剤所** - 医薬品を1時間あたり3個生産するように設定
6. **製鋼所** - 鋼鉄を1時間あたり3個生産するように設定
7. **火薬工場** - 火薬を1時間あたり2個生産するように設定
8. **電子部品工場** - 電子部品を1時間あたり1.5個生産するように設定

また、カテゴリが 'special' になっていた一部の建物を 'production' に変更しました。

## 修正後の確認方法

修正スクリプトは実行後に結果を表示します：

```sql
SELECT 
    building_key,
    name,
    icon,
    category,
    rt.name as produces_resource,
    production_rate,
    unlock_era_id
FROM civilization_building_types bt
LEFT JOIN civilization_resource_types rt ON bt.produces_resource_id = rt.id
WHERE building_key IN (
    'herb_garden', 
    'glassworks', 
    'weaving_mill', 
    'crystal_mine',
    'apothecary',
    'steel_mill',
    'gunpowder_factory',
    'electronics_factory'
)
ORDER BY unlock_era_id, building_key;
```

### 期待される結果

| building_key | name | produces_resource | production_rate | unlock_era_id |
|-------------|------|-------------------|-----------------|---------------|
| herb_garden | 薬草園 | 薬草 | 8.0 | 2 |
| weaving_mill | 織物工場 | 布 | 6.0 | 2 |
| glassworks | ガラス工房 | ガラス | 4.0 | 4 |
| apothecary | 調剤所 | 医薬品 | 3.0 | 4 |
| crystal_mine | クリスタル鉱山 | クリスタル | 1.0 | 5 |
| steel_mill | 製鋼所 | 鋼鉄 | 3.0 | 5 |
| gunpowder_factory | 火薬工場 | 火薬 | 2.0 | 5 |
| electronics_factory | 電子部品工場 | 電子部品 | 1.5 | 7 |

## ゲームへの影響

### プレイヤーへの影響
- 既に建設済みの該当建物は、次回ログイン時から自動的に資源を生産し始めます
- 資源は時間経過で自動的に収集されます
- 建物のレベルに応じて生産量が増加します

### 資源の利用用途

#### 薬草 (🌿)
- 医薬品の製造に必要
- 負傷兵の治療に使用される医薬品の原料

#### ガラス (🔮)
- 研究所の建設に必要（石材300、鉄150、**ガラス50**）
- 科学技術の発展に重要

#### 布 (🧵)
- 衣服や帆の製造に使用
- 海軍関連の建物や兵種に必要

#### クリスタル (💎)
- 建設や研究の即時完了に使用（60秒/個）
- Focus（集中）システムで獲得可能

#### 医薬品 (💊)
- 負傷兵の治療効率を上げる
- 高度な医療施設に必要

#### 鋼鉄 (⚙️)
- 高品質な武器と防具の製造
- 近代的な軍事ユニットに必要

#### 火薬 (💥)
- 火器と爆発物に使用
- ルネサンス以降の軍事ユニットに必要

#### 電子部品 (🔌)
- 現代技術に必要
- 最先端の建物や装備に使用

## 既存プレイヤーへの補償

修正適用前に該当建物を建設していたプレイヤーには、以下の補償を検討することをお勧めします：

```sql
-- 該当建物を所有しているユーザーに資源をボーナスとして付与
-- （例：レベル×100個の資源）

-- 薬草園を持っているユーザーに薬草を付与
INSERT INTO user_civilization_resources (user_id, resource_type_id, amount, unlocked, unlocked_at)
SELECT DISTINCT ucb.user_id, rt.id, 100 * SUM(ucb.level), TRUE, NOW()
FROM user_civilization_buildings ucb
JOIN civilization_building_types bt ON ucb.building_type_id = bt.id
CROSS JOIN civilization_resource_types rt
WHERE bt.building_key = 'herb_garden' 
  AND rt.resource_key = 'herbs'
  AND ucb.is_constructing = FALSE
GROUP BY ucb.user_id, rt.id
ON DUPLICATE KEY UPDATE 
    amount = amount + VALUES(amount),
    unlocked = TRUE;

-- 同様に他の資源についても実施
-- （glassworks → glass, weaving_mill → cloth など）
```

## トラブルシューティング

### 修正後も資源が生産されない場合

1. **データベースを確認**
   ```sql
   SELECT building_key, name, produces_resource_id, production_rate 
   FROM civilization_building_types 
   WHERE building_key IN ('herb_garden', 'glassworks');
   ```

2. **建物の状態を確認**
   ```sql
   SELECT ucb.*, bt.name 
   FROM user_civilization_buildings ucb
   JOIN civilization_building_types bt ON ucb.building_type_id = bt.id
   WHERE ucb.user_id = [YOUR_USER_ID]
     AND bt.building_key IN ('herb_garden', 'glassworks');
   ```

3. **資源収集が正常に動作しているか確認**
   - `civilization_api.php` の `collectResources()` 関数をチェック
   - 最終収集時刻 (`last_resource_collection`) が更新されているか確認

## 参考情報

- [完全な仕様書](./CIVILIZATION_GAME_SPECIFICATION.md)
- データベーススキーマ: `civilization_schema.sql`, `civilization_enhancements_schema.sql`, `war_system_advanced_schema.sql`
- API実装: `civilization_api.php`
- フロントエンド: `civilization.php`

## 更新履歴

- **2024年12月29日**: 初版作成、資源生産の問題を修正
