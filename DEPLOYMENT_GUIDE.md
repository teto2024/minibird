# デプロイメントガイド

## 前提条件

- PHP 7.4以上
- MySQL/MariaDB 5.7以上
- Webサーバー（Apache/Nginx）
- 既存のMiniBirdインストール

## デプロイ手順

### 1. コードの更新

```bash
# リポジトリから最新のコードをプル
git pull origin copilot/add-community-post-delete-feature
```

### 2. データベーススキーマの適用

```bash
# データベースにスキーマを適用
mysql -u [username] -p [database_name] < add_delete_flags_schema.sql
```

**重要**: データベース接続情報は `config.php` から確認してください。

### 3. データベーススキーマの確認

```bash
# テストスクリプトを実行（オプション）
./test_schema.sh
```

または、手動で確認：

```sql
-- postsテーブルの確認
SHOW COLUMNS FROM posts LIKE 'is_deleted';
SHOW COLUMNS FROM posts LIKE 'deleted_at';

-- community_postsテーブルの確認
SHOW COLUMNS FROM community_posts LIKE 'is_deleted';
SHOW COLUMNS FROM community_posts LIKE 'deleted_at';

-- usersテーブルにdiamondsカラムがあるか確認
SHOW COLUMNS FROM users LIKE 'diamonds';
```

### 4. ファイルパーミッションの確認

```bash
# アップロードディレクトリの権限確認
chmod 755 uploads/
chown www-data:www-data uploads/  # Apacheの場合
```

### 5. キャッシュのクリア

```bash
# PHPのOPcacheをクリア（必要に応じて）
service php-fpm restart  # または service apache2 restart
```

ブラウザのキャッシュもクリアしてください（Ctrl+Shift+Delete）。

## 動作確認

### 基本機能テスト

1. **ログインテスト**
   - ユーザーでログインできることを確認

2. **投稿削除テスト**
   - 通常投稿を作成し、削除して「削除済み」と表示されることを確認
   - コミュニティ投稿を作成し、削除して「削除済み」と表示されることを確認

3. **メンション通知テスト**
   - コミュニティで他のメンバーを @mention して通知が届くことを確認

4. **検索テスト**
   - handleとdisplay_nameで検索できることを確認

5. **プロフィールテスト**
   - プロフィールページでコイン、クリスタル、ダイヤモンドが表示されることを確認
   - モバイルデバイスでレスポンシブに表示されることを確認

6. **引用ポストテスト**
   - 引用ポストをクリックして引用元に遷移することを確認

### レスポンシブデザインテスト

ブラウザのデベロッパーツール（F12）でデバイスモードに切り替え、以下の画面サイズで確認：

- モバイル（320px - 480px）
- タブレット（481px - 768px）
- デスクトップ（769px以上）

## トラブルシューティング

### 問題: CSSやJSの変更が反映されない

**解決方法:**
1. ブラウザのキャッシュをクリア（Ctrl+Shift+Delete）
2. ブラウザのスーパーリロード（Ctrl+Shift+R または Cmd+Shift+R）
3. サーバー側のキャッシュをクリア

### 問題: データベースエラーが発生する

**解決方法:**
1. `add_delete_flags_schema.sql` が正しく適用されているか確認
2. データベース接続情報（config.php）が正しいか確認
3. データベースユーザーに適切な権限があるか確認

```sql
-- 権限の確認
SHOW GRANTS FOR 'username'@'localhost';
```

### 問題: 削除済み投稿が正しく表示されない

**解決方法:**
1. `is_deleted` カラムが存在するか確認
2. `deleted_at` カラムが存在するか確認
3. 既存の削除済みデータがある場合、手動で `is_deleted` を更新

```sql
-- 既存の削除済みデータを更新（必要に応じて）
UPDATE posts SET is_deleted = TRUE WHERE deleted_at IS NOT NULL;
UPDATE community_posts SET is_deleted = TRUE WHERE deleted_at IS NOT NULL;
```

### 問題: 通知が届かない

**解決方法:**
1. notifications テーブルが存在するか確認
2. cron ジョブが正しく設定されているか確認（あれば）
3. ブラウザのコンソールでエラーを確認

## ロールバック手順

問題が発生した場合、以下の手順でロールバックできます：

### 1. コードのロールバック

```bash
git checkout [previous-commit-hash]
```

### 2. データベースのロールバック

```sql
-- is_deletedとdeleted_atカラムを削除
ALTER TABLE posts DROP COLUMN is_deleted;
ALTER TABLE posts DROP COLUMN deleted_at;
ALTER TABLE community_posts DROP COLUMN is_deleted;
ALTER TABLE community_posts DROP COLUMN deleted_at;
```

**注意**: データベースのロールバックは慎重に行ってください。本番環境では必ずバックアップを取ってから実行してください。

## サポート

問題が解決しない場合は、以下の情報を添えてサポートに連絡してください：

1. エラーメッセージの全文
2. ブラウザのコンソールログ
3. サーバーのエラーログ（`/var/log/apache2/error.log` など）
4. 実行したSQL文と結果
5. PHPとMySQLのバージョン

## 参考資料

- `IMPLEMENTATION_SUMMARY.md` - 実装の詳細
- `add_delete_flags_schema.sql` - データベーススキーマ
- GitHub Issues - バグ報告や機能リクエスト
