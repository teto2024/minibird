# テスト手順 - 占領戦ランキング表示修正

## 修正内容のサマリー
城を持っていないプレイヤーでも、神城の累計占領時間が記録されている場合、ランキングに表示されるようになりました。

## テストシナリオ

### シナリオ1: 基本的な確認
**目的**: 城を持っていないプレイヤーがランキングに表示されることを確認

**手順**:
1. 占領戦画面を開く
2. ランキングタブを開く
3. 城数が0だが神城占領時間が記録されているプレイヤーがいることを確認

**期待される結果**:
- 城数が「0」と表示される
- 神城占領時間が正しく表示される（例: 「3時間15分」）
- ⛩️アイコンは表示されない（現在占領していないため）

### シナリオ2: ソート順の確認
**目的**: ランキングのソート順が正しいことを確認

**手順**:
1. ランキングタブで上位プレイヤーを確認
2. 神城占領時間の長い順になっていることを確認
3. 神城占領時間が同じ場合、城数の多い順になっていることを確認

**期待される結果**:
- 第1優先: 神城占領時間（降順）
- 第2優先: 城数（降順）
- 城数が0のプレイヤーも正しい位置に表示される

### シナリオ3: リアルタイム更新の確認
**目的**: 現在神城を占領中のプレイヤーの時間が正しく表示されることを確認

**手順**:
1. 現在神城を占領しているプレイヤーを確認
2. ランキングタブを開く
3. そのプレイヤーの神城占領時間に⛩️アイコンが表示されていることを確認
4. 時間が累計時間+現在の占領時間になっていることを確認

**期待される結果**:
- ⛩️アイコンが表示される
- 累計時間+現在の占領時間が表示される
- 他のプレイヤーと正しくソートされている

### シナリオ4: 実戦シミュレーション
**目的**: 実際のゲームプレイで修正が機能することを確認

**手順**:
1. プレイヤーAが神城を占領（1時間程度維持）
2. プレイヤーBがプレイヤーAから全ての城を奪う
3. ランキングを確認

**期待される結果**:
- プレイヤーAは城数0でランキングに表示される
- プレイヤーAの神城占領時間（約1時間）が表示される
- プレイヤーBが新しく神城を占領している場合、⛩️アイコンが表示される

## SQLクエリの動作確認

### データベースで直接確認する場合

```sql
-- 現在のアクティブシーズンIDを取得
SELECT id, season_number FROM conquest_seasons WHERE is_active = TRUE;

-- 城を持っていないが神城占領時間があるプレイヤーを確認
SELECT 
    csot.user_id,
    u.handle,
    uc.civilization_name,
    csot.total_occupation_seconds,
    COUNT(cc.id) as current_castle_count
FROM conquest_sacred_occupation_time csot
JOIN users u ON csot.user_id = u.id
JOIN user_civilizations uc ON csot.user_id = uc.user_id
LEFT JOIN conquest_castles cc 
    ON csot.user_id = cc.owner_user_id AND csot.season_id = cc.season_id
WHERE csot.season_id = <SEASON_ID>
    AND csot.total_occupation_seconds > 0
GROUP BY csot.user_id
HAVING current_castle_count = 0;

-- ランキング全体を確認（実際のAPIと同じクエリ）
SELECT 
    user_id as owner_user_id,
    handle,
    civilization_name,
    castle_count,
    sacred_count,
    sacred_occupation_seconds
FROM (
    -- 現在城を持っているプレイヤー
    SELECT 
        cc.owner_user_id as user_id,
        u.handle,
        uc.civilization_name,
        COUNT(*) as castle_count,
        SUM(CASE WHEN cc.is_sacred THEN 1 ELSE 0 END) as sacred_count,
        COALESCE(csot.total_occupation_seconds, 0) as sacred_occupation_seconds
    FROM conquest_castles cc
    JOIN users u ON cc.owner_user_id = u.id
    JOIN user_civilizations uc ON cc.owner_user_id = uc.user_id
    LEFT JOIN conquest_sacred_occupation_time csot 
        ON cc.owner_user_id = csot.user_id AND cc.season_id = csot.season_id
    WHERE cc.season_id = <SEASON_ID> AND cc.owner_user_id IS NOT NULL
    GROUP BY cc.owner_user_id
    
    UNION
    
    -- 城を持っていないが神城占領時間があるプレイヤー
    SELECT 
        csot.user_id,
        u.handle,
        uc.civilization_name,
        0 as castle_count,
        0 as sacred_count,
        csot.total_occupation_seconds as sacred_occupation_seconds
    FROM conquest_sacred_occupation_time csot
    JOIN users u ON csot.user_id = u.id
    JOIN user_civilizations uc ON csot.user_id = uc.user_id
    LEFT JOIN conquest_castles cc 
        ON csot.user_id = cc.owner_user_id AND csot.season_id = cc.season_id
    WHERE csot.season_id = <SEASON_ID>
        AND csot.total_occupation_seconds > 0
        AND cc.owner_user_id IS NULL
) combined
ORDER BY 
    sacred_occupation_seconds DESC,
    castle_count DESC
LIMIT 20;
```

## 既知の制限事項

なし。この修正は既存の機能を拡張するものであり、既存の動作には影響しません。

## ロールバック方法

万が一問題が発生した場合は、以下の手順でロールバックできます：

1. `conquest_api.php` の `get_ranking` アクションを元のコードに戻す
2. サーバーを再起動（必要な場合）

元のクエリは `CONQUEST_RANKING_ZERO_CASTLES_FIX.md` に記載されています。

## デプロイ後の確認事項

- [ ] ランキングページが正常に表示される
- [ ] 城数0のプレイヤーがランキングに表示される（該当者がいる場合）
- [ ] ソート順が正しい（神城占領時間 > 城数）
- [ ] パフォーマンスに問題がない
- [ ] エラーログに新しいエラーが出ていない

## サポート情報

### 問題が発生した場合の確認ポイント

1. **ランキングが表示されない**
   - ブラウザのコンソールでJavaScriptエラーを確認
   - サーバーのエラーログを確認
   - conquest_api.php の構文エラーを確認: `php -l conquest_api.php`

2. **特定のプレイヤーが表示されない**
   - データベースで該当プレイヤーの `conquest_sacred_occupation_time` レコードを確認
   - `total_occupation_seconds > 0` であることを確認

3. **パフォーマンスが遅い**
   - SQLクエリの実行時間を確認
   - 必要に応じてインデックスを追加（既にスキーマに含まれています）

## 関連ドキュメント

- `CONQUEST_RANKING_ZERO_CASTLES_FIX.md`: 詳細な技術仕様
- `CONQUEST_SACRED_OCCUPATION_TIME_README.md`: 神城占領時間トラッキング機能の概要
- `conquest_sacred_occupation_time_schema.sql`: データベーススキーマ
