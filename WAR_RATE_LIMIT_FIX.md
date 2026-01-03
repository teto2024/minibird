# 戦争レート制限の修正と改善

## 問題の概要

文明育成ゲームで設定されていた戦争レート制限（1時間に3回）が正しく動作していませんでした。

## 根本原因

`user_war_rate_limits` テーブルが存在しない場合、戦争攻撃時にデータベースエラーが発生し、すべての攻撃が失敗していました。

## 実装した改善

### 1. 後方互換性の追加

- テーブルが存在しない場合でも攻撃が可能になりました
- エラーは `error_log` に記録されるため、管理者は問題を把握できます
- レート制限はテーブルが存在する場合のみ適用されます

### 2. UI の改善

戦争タブに以下の情報を表示するセクションを追加：

- **残り攻撃可能回数**: 「2 / 3」のように表示
- **プログレスバー**: 使用した攻撃回数を視覚化
  - 緑: まだ余裕がある
  - 黄色: 残り1回
  - 赤: レート制限に到達
- **次の攻撃まで の時間**: レート制限に到達した場合、待ち時間を表示

### 3. 新しい API エンドポイント

`get_war_rate_limit_status` アクション:
- 現在の攻撃回数
- 残り攻撃可能回数
- 次の攻撃可能時刻
- 待ち時間（秒）

## データベースセットアップ

テーブルを作成するには、以下のコマンドを実行してください：

```bash
mysql -u [username] -p [database_name] < war_rate_limit_schema.sql
```

または SQL を直接実行：

```sql
USE microblog;

CREATE TABLE IF NOT EXISTS user_war_rate_limits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL COMMENT '攻撃者のユーザーID',
    attack_timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '攻撃実行時刻',
    target_user_id INT UNSIGNED NOT NULL COMMENT '攻撃対象のユーザーID',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_timestamp (user_id, attack_timestamp),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='戦争レート制限追跡テーブル（1時間に3回まで）';
```

## 動作確認

### テーブルが存在する場合

1. 戦争タブを開く
2. レート制限セクションが表示される
3. 攻撃を3回実行
4. 4回目の攻撃時にレート制限エラーが表示される
5. プログレスバーが赤色になり、待ち時間が表示される

### テーブルが存在しない場合

1. 戦争タブを開く
2. レート制限セクションに警告が表示される「⚠️ レート制限テーブルが見つかりません」
3. 攻撃は制限なく実行可能（後方互換性）
4. エラーログに警告が記録される

## レート制限のカスタマイズ

`civilization_api.php` の以下の部分を変更することで制限を調整できます：

```php
// 制限回数を変更（現在は3回）
if ($attackCount >= 3) {
    // ...
}

// 時間枠を変更（現在は1時間）
$oneHourAgo = date('Y-m-d H:i:s', strtotime('-1 hour'));
```

## メンテナンス

古い攻撃記録を定期的に削除することを推奨します：

```sql
-- 7日以上前の記録を削除
DELETE FROM user_war_rate_limits 
WHERE attack_timestamp < DATE_SUB(NOW(), INTERVAL 7 DAY);
```

これを cron ジョブとして設定することをお勧めします。

## 技術詳細

### レート制限のロジック

1. **スライディングウィンドウ方式**: 過去1時間の攻撃回数をカウント
2. **トランザクション内で実行**: データの整合性を保証
3. **戦闘成功後に記録**: 失敗した攻撃はカウントされない

### エラーハンドリング

- `PDOException` をキャッチしてテーブル不在を検出
- エラーは `error_log()` に記録
- ユーザーには適切なメッセージを表示

---

**実装日**: 2026年1月3日  
**バージョン**: 1.1  
**対応ファイル**: 
- `civilization_api.php`
- `civilization.php`
- `war_rate_limit_schema.sql`
