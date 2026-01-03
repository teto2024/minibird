# 占領戦ランキング表示修正 - 保有城数0の場合の神城累計占領時間表示

## 問題の概要

占領戦で神城累計占領時間順にランキングを変更したが、占領主が変わって全ての城が取り返され保有城数が0になった場合に、神城の累計占領時間表示が消えてしまい、ランキングにカウントされない問題が発生していた。

## 原因

`conquest_api.php` の `get_ranking` アクション内のSQL文が、`conquest_castles` テーブルから城を所有しているプレイヤーのみを取得していたため、城を持っていないプレイヤーは `conquest_sacred_occupation_time` テーブルに累計占領時間が記録されていても、ランキングに表示されなかった。

### 問題のあったクエリ
```sql
SELECT 
    cc.owner_user_id, 
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
WHERE cc.season_id = ? AND cc.owner_user_id IS NOT NULL
GROUP BY cc.owner_user_id
```

このクエリは `FROM conquest_castles cc` で始まり、`WHERE cc.owner_user_id IS NOT NULL` で絞り込んでいるため、城を持っていないプレイヤーは結果に含まれない。

## 修正内容

UNION クエリを使用して、以下の2つのグループのプレイヤーを結合するように変更した：

1. **現在城を持っているプレイヤー**（元のクエリと同じ）
2. **城を持っていないが、神城累計占領時間が記録されているプレイヤー**

### 修正後のクエリ
```sql
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
    WHERE cc.season_id = ? AND cc.owner_user_id IS NOT NULL
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
    WHERE csot.season_id = ?
        AND csot.total_occupation_seconds > 0
        AND csot.user_id NOT IN (
            SELECT DISTINCT owner_user_id 
            FROM conquest_castles 
            WHERE season_id = ? AND owner_user_id IS NOT NULL
        )
) combined
ORDER BY 
    sacred_occupation_seconds DESC,
    castle_count DESC
LIMIT 20
```

## 修正のポイント

1. **UNION を使用**: 2つの異なるデータソースを結合
   - 第1のクエリ: 城を持っているプレイヤー（既存のロジック）
   - 第2のクエリ: 城を持っていないが神城占領時間があるプレイヤー

2. **重複排除**: `NOT IN` 句で、第2のクエリに城を持っているプレイヤーが含まれないようにする

3. **適切なデフォルト値**: 城を持っていないプレイヤーは `castle_count = 0`, `sacred_count = 0` を設定

4. **ソート順の維持**: 
   - 第1優先: `sacred_occupation_seconds DESC` （神城累計占領時間が長い順）
   - 第2優先: `castle_count DESC` （城数が多い順）

## 期待される動作

修正後、以下のような動作になる：

### シナリオ1: プレイヤーAが神城を5時間占領後、全城を失う
- **修正前**: ランキングに表示されない
- **修正後**: ランキングに表示される（神城占領時間: 5時間、城数: 0）

### シナリオ2: プレイヤーBが神城を3時間占領中で、城を2つ所有
- **修正前/後**: ランキングに表示される（神城占領時間: 3時間+現在時刻、城数: 2、⛩️アイコン表示）

### シナリオ3: プレイヤーCが城を5つ所有するが、神城は未占領
- **修正前/後**: ランキングに表示される（神城占領時間: -、城数: 5）

## フロントエンドでの表示

`conquest.php` のランキング表示部分では、以下のように表示される：

```javascript
${data.rankings.map((r, i) => {
    const hours = Math.floor(r.sacred_occupation_seconds / 3600);
    const minutes = Math.floor((r.sacred_occupation_seconds % 3600) / 60);
    const timeStr = r.sacred_occupation_seconds > 0 
        ? (hours > 0 ? `${hours}時間${minutes}分` : `${minutes}分`)
        : '-';
    
    return `
        <tr class="${i < 3 ? 'rank-' + (i + 1) : ''}">
            <td style="font-weight: bold;">${i + 1}</td>
            <td>${escapeHtml(r.civilization_name)}</td>
            <td>@${escapeHtml(r.handle)}</td>
            <td style="color: ${r.sacred_occupation_seconds > 0 ? '#ffd700' : '#888'}; font-weight: ${r.sacred_occupation_seconds > 0 ? 'bold' : 'normal'};">
                ${r.sacred_count > 0 ? '⛩️ ' : ''}${timeStr}
            </td>
            <td>${r.castle_count}</td>
        </tr>
    `;
}).join('')}
```

### 表示の特徴
- `sacred_occupation_seconds > 0`: 金色で太字表示
- `sacred_count > 0`: ⛩️アイコンを表示（現在神城を占領中）
- `castle_count = 0`: 城数は「0」と表示

## テスト項目

- [ ] 神城を占領したプレイヤーが全ての城を失った後もランキングに表示される
- [ ] 累計占領時間が正しく表示される
- [ ] 城数が0と正しく表示される
- [ ] ⛩️アイコンが表示されない（現在占領していないため）
- [ ] ソート順が正しい（累計占領時間降順 → 城数降順）
- [ ] 重複したプレイヤーがランキングに表示されない

## 影響範囲

- ファイル: `conquest_api.php`
- 関数: `get_ranking` アクション
- テーブル: `conquest_castles`, `conquest_sacred_occupation_time`, `users`, `user_civilizations`

## 備考

この修正により、プレイヤーが全ての城を失っても、神城の累計占領時間がランキングに正しく反映されるようになった。これにより、ゲームのルール「シーズン終了時、神城の累計占領時間が最も長いプレイヤーが優勝」が正しく機能するようになった。
