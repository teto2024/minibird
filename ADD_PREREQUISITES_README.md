# 既存建物・兵種への前提条件追加SQL

## 概要
このSQLファイル (`add_prerequisites_comprehensive.sql`) は、MiniBird文明育成ゲームの既存の建物と兵種に対して、適切な前提条件を追加します。

## 対象データ
- **兵種**: 75種（64件の前提建物を追加）
- **建物**: 118種（77件の前提研究を追加）

## 使用方法

### 1. データベースへの適用
```bash
mysql -u [ユーザー名] -p [データベース名] < add_prerequisites_comprehensive.sql
```

### 2. 前提条件の確認
実行後、以下のクエリで追加された前提条件を確認できます：

```sql
-- 兵種の前提建物を確認
SELECT 
    tt.name AS '兵種名',
    bt.name AS '前提建物',
    tp.is_required AS '必須'
FROM civilization_troop_prerequisites tp
JOIN civilization_troop_types tt ON tp.troop_type_id = tt.id
JOIN civilization_building_types bt ON tp.prerequisite_building_id = bt.id
ORDER BY tt.name;

-- 建物の前提研究を確認
SELECT 
    bt.name AS '建物名',
    r.name AS '前提研究',
    bp.is_required AS '必須'
FROM civilization_building_prerequisites bp
JOIN civilization_building_types bt ON bp.building_type_id = bt.id
JOIN civilization_researches r ON bp.prerequisite_research_id = r.id
ORDER BY bt.name;
```

## 追加される前提条件の例

### 兵種への前提建物
| 兵種 | 前提建物 |
|------|----------|
| 狩人 | 狩場 |
| 戦士 | 兵舎 |
| 弓兵 | 弓術場 |
| 騎兵 | 厩舎 |
| 戦闘機 | 空軍基地 |
| AI兵士 | AI研究センター |

### 建物への前提研究
| 建物 | 前提研究 |
|------|----------|
| 農場 | 農業の基礎 |
| 兵舎 | 軍事訓練 |
| 空軍基地 | 航空技術 |
| 量子研究所 | 量子力学 |
| AI研究センター | 機械学習 |
| 宇宙港 | 宇宙推進技術 |

## 技術的詳細

### 使用テーブル
- `civilization_troop_prerequisites` - 兵種の前提条件
- `civilization_building_prerequisites` - 建物の前提条件
- `civilization_troop_types` - 兵種マスター
- `civilization_building_types` - 建物マスター
- `civilization_researches` - 研究マスター

### SQL構造
```sql
-- 兵種前提建物追加の例
INSERT IGNORE INTO civilization_troop_prerequisites (troop_type_id, prerequisite_building_id, is_required)
SELECT tt.id, bt.id, TRUE 
FROM civilization_troop_types tt 
CROSS JOIN civilization_building_types bt 
WHERE tt.troop_key = '兵種キー' AND bt.building_key = '建物キー';
```

## 注意事項
1. **INSERT IGNORE**: 重複を防止するため、既に同じ前提条件が存在する場合は無視されます
2. **CROSS JOIN**: building_key/research_keyからIDを自動取得します
3. **is_required = TRUE**: すべての条件は必須条件として設定されます

## 前提条件
このSQLを実行する前に、以下のテーブルとデータが存在する必要があります：
- `multiple_prerequisites_schema.sql` - 前提条件テーブルの定義
- `civilization_schema.sql` - 基本的な文明システム
- `civilization_extended_schema.sql` - 拡張された建物と兵種
- `minibird_new_eras_2026.sql` - 新時代の追加

## トラブルシューティング

### エラー: Table doesn't exist
前提条件テーブルが作成されていません。先に `multiple_prerequisites_schema.sql` を実行してください。

### エラー: Foreign key constraint fails
参照先の建物や研究が存在しません。関連するスキーマファイルを先に実行してください。

### 警告: データが追加されない
`INSERT IGNORE` により、既に同じ前提条件が存在する場合は追加されません。これは正常な動作です。

## ファイル情報
- **ファイル名**: add_prerequisites_comprehensive.sql
- **行数**: 612行
- **サイズ**: 約42KB
- **エンコーディング**: UTF-8
- **作成日**: 2026年1月3日

## 関連ファイル
- `multiple_prerequisites_schema.sql` - 前提条件システムの基盤
- `civilization_schema.sql` - 基本的な文明システム
- `civilization_extended_schema.sql` - 拡張機能
- `minibird_new_eras_2026.sql` - 新時代の追加
