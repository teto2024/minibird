# Darkroom Extended - 拡張機能ドキュメント

## 概要

A Dark Room風のミニゲーム「暗い部屋」に以下の機能を追加しました：

1. **レベリングシステム**
2. **クエストシステム**
3. **アイテムクラフトシステム**
4. **戦闘システム**

すべての機能は拡張性を考慮した設計になっており、簡単に新しいコンテンツを追加できます。

---

## セットアップ手順

### 1. データベーススキーマのインポート

```bash
mysql -u root -p microblog < darkroom_extended_schema.sql
```

このSQLファイルには以下のテーブルが含まれます：
- `darkroom_player_stats` - プレイヤーのレベルとステータス
- `darkroom_items` - アイテムマスターデータ
- `darkroom_inventory` - プレイヤーのインベントリ
- `darkroom_recipes` - クラフトレシピ
- `darkroom_quests` - クエストマスターデータ
- `darkroom_quest_progress` - クエスト進行状況
- `darkroom_enemies` - 敵キャラクターデータ
- `darkroom_battle_logs` - 戦闘履歴
- `darkroom_unlocks` - アンロック情報（拡張用）

初期データ（アイテム、レシピ、敵、クエスト）も自動的に投入されます。

### 2. ファイル構成

- `darkroom.php` - メインゲームファイル（拡張済み）
- `darkroom_engine.php` - ゲームロジックエンジン
- `darkroom_extended_schema.sql` - データベーススキーマ

---

## 機能詳細

### 1. レベリングシステム

#### 特徴
- アクションを実行すると経験値を獲得
- レベルアップすると自動的に最大HPが増加
- レベルアップごとに3ステータスポイント獲得
- ステータスポイントで攻撃力、防御力、敏捷性、最大HPを強化可能

#### 経験値の獲得方法
- 木材採集: 2 EXP
- アイテムクラフト: レシピごとに設定
- 戦闘勝利: 敵ごとに設定
- クエスト完了: クエストごとに設定

#### レベルアップ計算式
```
必要経験値 = 現在レベル × 100
```

#### 拡張方法
`darkroom_engine.php`の`addExperience()`メソッドを使用：

```php
$engine->addExperience(50); // 50経験値を付与
```

---

### 2. アイテム＆クラフトシステム

#### アイテムタイプ
- `weapon` - 武器（攻撃力ボーナス）
- `armor` - 防具（防御力・最大HPボーナス）
- `consumable` - 消耗品（回復・バフ効果）
- `material` - 素材（クラフト用）
- `quest` - クエストアイテム
- `misc` - その他

#### レアリティ
- `common` - コモン（灰色）
- `uncommon` - アンコモン（緑色）
- `rare` - レア（青色）
- `epic` - エピック（紫色）
- `legendary` - レジェンダリー（オレンジ色）

#### 新しいアイテムの追加

```sql
INSERT INTO darkroom_items (item_key, name, description, type, rarity, stats, is_craftable) 
VALUES ('steel_sword', '鋼鉄の剣', '強力な鋼鉄製の剣', 'weapon', 'rare', '{"attack": 25}', TRUE);
```

#### 新しいレシピの追加

```sql
INSERT INTO darkroom_recipes (recipe_key, result_item_id, required_level, materials, crafting_time, experience_reward)
VALUES (
    'craft_steel_sword', 
    (SELECT id FROM darkroom_items WHERE item_key='steel_sword'),
    10,
    '[{"item_key": "iron_ore", "quantity": 15}, {"item_key": "stone", "quantity": 10}]',
    20,
    150
);
```

#### プログラムからの使用

```php
// アイテム追加
$engine->addItem('wood', 10);

// アイテム削除
$engine->removeItem('wood', 5);

// クラフト実行
$result = $engine->craftItem('craft_wooden_sword');
```

---

### 3. クエストシステム

#### クエストタイプ
- `main` - メインクエスト
- `side` - サイドクエスト
- `daily` - デイリークエスト
- `achievement` - アチーブメント

#### 目標タイプ
- `kill` - 敵を倒す
- `gather` - アイテムを集める
- `craft` - アイテムをクラフトする

#### 新しいクエストの追加

```sql
INSERT INTO darkroom_quests (quest_key, title, description, type, required_level, objectives, rewards)
VALUES (
    'collect_iron',
    '鉄鉱石収集',
    '鉄鉱石を20個集めよう',
    'side',
    5,
    '[{"type": "gather", "item_key": "iron_ore", "count": 20}]',
    '{"experience": 150, "coins": 100, "items": [{"item_key": "health_potion", "quantity": 5}]}'
);
```

#### 前提クエストの設定

```sql
UPDATE darkroom_quests 
SET prerequisite_quest_id = (SELECT id FROM darkroom_quests WHERE quest_key='tutorial_gather')
WHERE quest_key='tutorial_craft';
```

#### プログラムからの使用

```php
// クエスト開始
$engine->startQuest('hunt_rats');

// 進捗更新（自動的に行われる）
$engine->updateQuestProgress('kill', 'rat', 1);

// クエスト完了
$engine->completeQuest('hunt_rats');
```

---

### 4. 戦闘システム

#### 特徴
- ターン制バトル（最大20ターン）
- ダメージ計算: `攻撃力 - 防御力 + ランダム(-2〜+2)`
- 装備品のボーナスが自動適用
- 勝利時にドロップアイテム＆経験値獲得
- 戦闘ログをDBに保存

#### 新しい敵の追加

```sql
INSERT INTO darkroom_enemies (
    enemy_key, name, description, level, health, attack, defense, agility,
    experience_reward, loot_table, is_boss
)
VALUES (
    'dragon',
    'ドラゴン',
    '伝説の古龍',
    20,
    1000,
    60,
    40,
    20,
    2000,
    '[{"item_key": "iron_ore", "drop_rate": 1.0, "quantity_min": 20, "quantity_max": 30}]',
    TRUE
);
```

#### ドロップテーブルの設定

```json
[
  {
    "item_key": "iron_ore",
    "drop_rate": 0.7,
    "quantity_min": 3,
    "quantity_max": 5
  },
  {
    "item_key": "leather",
    "drop_rate": 0.5,
    "quantity_min": 2,
    "quantity_max": 4
  }
]
```

- `drop_rate`: ドロップ確率（0.0〜1.0）
- `quantity_min/max`: ドロップ数の範囲

#### プログラムからの使用

```php
// 戦闘実行
$result = $engine->battle('wolf');

if ($result['result'] === 'victory') {
    echo "勝利！経験値: {$result['experience_gained']}";
    foreach ($result['loot'] as $item) {
        echo "獲得: {$item['item_key']} x{$item['quantity']}";
    }
}
```

---

## アーキテクチャ

### DarkroomEngineクラス

すべてのゲームロジックを管理するメインクラスです。

#### 主要メソッド

```php
// レベリング
$engine->getPlayerStats();           // プレイヤーステータス取得
$engine->addExperience($exp);        // 経験値追加
$engine->allocateStatPoint($stat);   // ステータスポイント割り振り
$engine->getRequiredExp($level);     // 必要経験値取得

// アイテム
$engine->addItem($itemKey, $quantity);       // アイテム追加
$engine->removeItem($itemKey, $quantity);    // アイテム削除
$engine->getInventory();                     // インベントリ取得

// クラフト
$engine->craftItem($recipeKey);              // クラフト実行
$engine->getAvailableRecipes();              // 利用可能なレシピ一覧

// クエスト
$engine->startQuest($questKey);              // クエスト開始
$engine->updateQuestProgress($type, $key, $count);  // 進捗更新
$engine->completeQuest($questKey);           // クエスト完了
$engine->getQuests();                        // クエスト一覧

// 戦闘
$engine->battle($enemyKey);                  // 戦闘実行
$engine->getAvailableEnemies();              // 利用可能な敵一覧
```

### データベース設計

#### 正規化とJSON活用
- マスターデータ（アイテム、敵、クエスト）は個別テーブル
- 可変長データ（レシピ素材、クエスト目標、戦利品）はJSON型
- プレイヤーデータは`user_id`を外部キー参照

#### インデックス
- 頻繁に検索される列（`level`, `type`, `rarity`等）にインデックス
- 複合インデックス（`user_id + status`）でクエリ最適化

---

## UI拡張

### タブシステム

6つのタブで機能を分離：
1. **村** - 既存の基本機能
2. **キャラクター** - ステータス表示・強化
3. **インベントリ** - 所持アイテム一覧
4. **クラフト** - アイテム作成
5. **クエスト** - クエスト管理
6. **戦闘** - 敵との戦闘

### JavaScript API

```javascript
// タブ切り替え
switchTab('tab-battle');

// データ読み込み
loadPlayerStats();
loadInventory();
loadRecipes();
loadQuests();
loadEnemies();

// アクション実行
allocateStat('attack');
craftItem('craft_wooden_sword');
startQuest('hunt_rats');
completeQuest('hunt_rats');
startBattle('wolf');
```

---

## カスタマイズガイド

### 難易度調整

#### レベルアップ速度
`darkroom_engine.php`の`getRequiredExp()`を変更：

```php
public function getRequiredExp($level) {
    return $level * 150; // 100から150に変更で難易度UP
}
```

#### 戦闘バランス
`darkroom_engine.php`の`battle()`メソッド内のダメージ計算を変更：

```php
$damage = max(1, $playerAttack - $enemyDefense + rand(-5, 5)); // ランダム幅を広げる
```

### 新コンテンツ追加

#### 1. 新しいアイテムタイプを追加

```sql
ALTER TABLE darkroom_items 
MODIFY COLUMN type ENUM('weapon', 'armor', 'consumable', 'material', 'quest', 'misc', 'accessory') NOT NULL;
```

#### 2. 新しいクエストタイプを追加

```sql
ALTER TABLE darkroom_quests 
MODIFY COLUMN type ENUM('main', 'side', 'daily', 'achievement', 'event') NOT NULL;
```

#### 3. スキルシステムの追加（例）

```sql
CREATE TABLE darkroom_skills (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    skill_key VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    type ENUM('passive', 'active') NOT NULL,
    required_level INT UNSIGNED NOT NULL DEFAULT 1,
    effects JSON
);

CREATE TABLE darkroom_player_skills (
    user_id INT UNSIGNED NOT NULL,
    skill_id INT UNSIGNED NOT NULL,
    level INT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (user_id, skill_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES darkroom_skills(id) ON DELETE CASCADE
);
```

---

## パフォーマンス最適化

### 推奨事項

1. **頻繁にアクセスするデータのキャッシュ**
```php
// セッションにプレイヤーステータスをキャッシュ
$_SESSION['player_stats_cache'] = $engine->getPlayerStats();
```

2. **バッチ処理**
```php
// 複数アイテムを一度に追加
$items = ['wood' => 10, 'stone' => 5];
foreach ($items as $key => $qty) {
    $engine->addItem($key, $qty);
}
```

3. **インデックスの活用**
すでに主要カラムにインデックスが設定されていますが、クエリパフォーマンスをモニタリングして追加を検討してください。

---

## トラブルシューティング

### よくある問題

#### 1. テーブルが作成されない
```bash
# MySQLのエラーログを確認
tail -f /var/log/mysql/error.log

# 手動でテーブル存在確認
mysql -u root -p microblog -e "SHOW TABLES LIKE 'darkroom_%';"
```

#### 2. JSON解析エラー
```php
// JSONデータの検証
$objectives = json_decode($quest['objectives'], true);
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("JSON decode error: " . json_last_error_msg());
}
```

#### 3. アイテムが追加されない
```php
// デバッグログを追加
error_log("Adding item: $itemKey x$quantity");
$result = $engine->addItem($itemKey, $quantity);
error_log("Result: " . json_encode($result));
```

---

## 今後の拡張アイデア

1. **マルチプレイヤー要素**
   - ギルドシステム
   - PvP戦闘
   - 協力クエスト

2. **エリア探索**
   - 複数のマップエリア
   - ダンジョンシステム
   - ボス戦

3. **経済システム**
   - プレイヤー間取引
   - オークションハウス
   - NPC商店

4. **スキルツリー**
   - 職業システム
   - パッシブ/アクティブスキル
   - 特殊能力

5. **イベントシステム**
   - 期間限定イベント
   - シーズンクエスト
   - ランキング報酬

---

## ライセンス

このコードはMIT Licenseの下で提供されています。

## サポート

問題や質問がある場合は、プロジェクトのIssueトラッカーに報告してください。
