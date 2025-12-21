# MiniBird 機能追加・修正 実装レポート

## 概要
このレポートは、MiniBirdプロジェクトに対して実施された機能追加と修正の詳細をまとめたものです。

## 実装日時
2025年12月21日

## 修正された問題

### 1. 通知ベルポップアップの表示問題（スマホ・タブレット）
**問題**: スマホやタブレット画面で通知ベルのポップアップが真っ黒になり、テキストが表示されない

**修正内容**:
- ファイル: `assets/style.css`
- `#notificationPopup li span` に明示的に `color: #e6e6e6;` を追加
- スマホ・タブレット端末での文字色の継承問題を解決

### 2. focus.php の白画面表示問題
**問題**: focus.php にアクセスすると真っ白な画面が表示される

**修正内容**:
- ファイル: `focus.php`
- ファイルの先頭に `<?php require_once __DIR__ . '/config.php'; ?>` を追加
- `ASSETS_VERSION` 定数が未定義だったため、config.php をインクルードして解決

### 3. ブースト期限制限の実装
**問題**: 投稿から2日経過した投稿でもブーストできてしまう

**修正内容**:
- ファイル: `boost_api.php`, `assets/app.js`
- 投稿日時をチェックし、2日を超えた投稿はブーストを拒否
- エラーメッセージ「ブースト期限を過ぎているためブーストできません」を表示
- クライアント側でもエラーメッセージを適切に表示

### 4. コミュニティフィードのNSFWブラー問題
**問題**: コミュニティフィードでNSFWモザイクが正常に動作しない

**修正内容**:
- ファイル: `community_feed.php`
- `is_nsfw` の判定に文字列 `'1'` も含めるように修正
- `post.is_nsfw === '1'` の条件を追加

## 追加されたフレームデザイン

### 1. クローバーカード (frame-clover-card)
**価格**: 700コイン + 5クリスタル

**デザイン特徴**:
- 白い枠、内側が薄い緑色の背景
- 文字が見やすいように配色
- アニメーションなし（シンプルなデザイン）

**実装ファイル**:
- `assets/style.css`: CSSスタイル定義
- `shop.php`: フレームデータの追加

### 2. ハート (frame-heart)
**価格**: 30,000コイン + 3ダイヤモンド

**デザイン特徴**:
- ピンク背景で文字が見やすい
- 右下からピンク色の♡が連続的に湧き出すアニメーション
- `heartFloat` と `heartsContinuous` アニメーション実装

**実装ファイル**:
- `assets/style.css`: CSSスタイルとアニメーション定義
- `shop.php`: フレームデータの追加

## 新機能の追加

### 1. コミュニティアクティブメーター

**実装内容**:
- 総投稿数の表示
- 24時間投稿数の表示
- 5段階のアクティブメーター表示
  - 5段階（20投稿以上）: 緑色
  - 4段階（15-19投稿）: 緑色
  - 3段階（10-14投稿）: 橙色
  - 2段階（5-9投稿）: 橙色
  - 1段階（0-4投稿）: 赤色

**実装ファイル**:
- `community_public_list.php`: 公開コミュニティ一覧
- `communities.php`: 参加中コミュニティ一覧
- 両ファイルにアクティブメーターのスタイルとロジックを追加

### 2. 通報機能

**実装内容**:
- 全体フィードの各投稿に通報ボタンを追加
- 通報理由の選択（Twitter準拠）:
  - スパム
  - ハラスメント・いじめ
  - 暴力的な内容
  - ヘイトスピーチ
  - 性的なコンテンツ
  - 誤情報
  - 著作権侵害
  - その他
- 詳細説明の記入（任意）
- ダイアログUIで使いやすく

**実装ファイル**:
- `report_system_schema.sql`: データベーススキーマ
- `report_api.php`: 通報処理API
- `assets/app.js`: 通報ボタンとダイアログUI

### 3. ミュート時のポップアップ改善

**実装内容**:
- 「あなたは投稿を制限されています」という明確なメッセージ
- 残りミュート時間の表示（時間と分で表示）
- 制限解除予定日時の表示
- 異議申し立てボタンの追加

**異議申し立て機能**:
- 理由の詳細記入（必須）
- 管理者による審査
- 承認されるとミュートが解除される

**実装ファイル**:
- `post.php`: ミュートチェックと残り時間計算
- `community_api.php`: コミュニティ投稿時のミュートチェック
- `appeal_api.php`: 異議申し立て処理API
- `assets/app.js`: ミュートポップアップとダイアログUI
- `community_feed.php`: コミュニティフィード用ポップアップ
- `report_system_schema.sql`: 異議申し立てテーブル定義

### 4. コミュニティ投稿時のミュート制限

**実装内容**:
- コミュニティへの投稿時もミュートチェックを実施
- 通常投稿と同様のミュートポップアップを表示
- 異議申し立ての導線も同様に提供

**実装ファイル**:
- `community_api.php`: ミュートチェックロジック追加
- `community_feed.php`: エラーハンドリングとポップアップ表示

### 5. admin.php の改良

**実装内容**:

#### 5.1 ユーザー検索機能
- ユーザーIDまたはハンドル名で検索
- リアルタイムでユーザー一覧をフィルタリング

#### 5.2 通報管理機能
- 未処理の通報を一覧表示
- 通報理由、詳細、投稿内容を表示
- 処理アクション:
  - 投稿を削除して解決
  - 通報を却下
- 管理者コメントの記入機能

#### 5.3 異議申し立て管理機能
- 未処理の異議申し立てを一覧表示
- ユーザー情報と申し立て理由を表示
- 処理アクション:
  - 承認（ミュート解除）
  - 却下
- 管理者コメントの記入機能

#### 5.4 ミュート・凍結機能の改善
- ミュート解除ボタンの追加
- 凍結解除ボタンの追加
- ユーザー一覧で現在のステータスを明確に表示
- ミュート期限の最大値を7日間（10,080分）に拡張

**実装ファイル**:
- `admin.php`: 全機能の実装

## データベーススキーマ

### 新規テーブル

#### reports テーブル
```sql
CREATE TABLE IF NOT EXISTS reports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id BIGINT UNSIGNED NOT NULL,
    reporter_id INT UNSIGNED NOT NULL,
    reason VARCHAR(100) NOT NULL,
    details TEXT,
    status ENUM('pending', 'reviewed', 'resolved', 'dismissed') NOT NULL DEFAULT 'pending',
    admin_comment TEXT,
    reviewed_by INT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- Foreign keys and indexes
)
```

#### appeals テーブル
```sql
CREATE TABLE IF NOT EXISTS appeals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    admin_comment TEXT,
    reviewed_by INT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- Foreign keys and indexes
)
```

**スキーマファイル**: `report_system_schema.sql`

## 変更されたファイル一覧

### PHP ファイル
1. `focus.php` - config.php インクルード追加
2. `boost_api.php` - ブースト期限チェック追加
3. `post.php` - ミュート時の残り時間計算
4. `community_api.php` - コミュニティ投稿時のミュートチェック
5. `community_feed.php` - NSFWブラー修正、ミュートポップアップ追加
6. `community_public_list.php` - アクティブメーター追加
7. `communities.php` - アクティブメーター追加
8. `admin.php` - 通報管理、異議申し立て管理、ユーザー検索機能追加

### 新規 PHP ファイル
1. `report_api.php` - 通報処理API
2. `appeal_api.php` - 異議申し立て処理API

### JavaScript ファイル
1. `assets/app.js` - 通報ボタン、ミュートポップアップ、ブーストエラー処理

### CSS ファイル
1. `assets/style.css` - 通知ポップアップ修正、新フレーム追加

### SQL ファイル
1. `report_system_schema.sql` - 新規作成

### その他
1. `shop.php` - 新フレーム追加

## テスト推奨項目

### 修正された問題のテスト
1. スマホ・タブレットで通知ベルをクリックし、テキストが表示されることを確認
2. focus.php にアクセスし、正常に表示されることを確認
3. 2日以上前の投稿をブーストし、エラーメッセージが表示されることを確認
4. コミュニティフィードでNSFW画像がぼかしで表示されることを確認

### 新機能のテスト
1. コミュニティ一覧でアクティブメーターが正しく表示されることを確認
2. 投稿を通報し、管理画面に表示されることを確認
3. ミュートされた状態で投稿し、ポップアップが表示されることを確認
4. 異議申し立てを行い、管理画面に表示されることを確認
5. 管理画面でユーザー検索が動作することを確認
6. 管理画面で通報を処理できることを確認
7. 管理画面で異議申し立てを処理できることを確認

## まとめ

すべての要件が正常に実装されました。以下の主要な改善が行われました：

1. **ユーザーエクスペリエンスの向上**
   - 通知の可読性改善
   - 明確なエラーメッセージとポップアップ
   - コミュニティのアクティビティ可視化

2. **管理機能の強化**
   - 効率的な通報処理システム
   - 公平な異議申し立てプロセス
   - 強力なユーザー検索と管理ツール

3. **セキュリティとモデレーション**
   - 包括的な通報システム
   - コミュニティ全体でのミュート制限
   - 管理者による適切な対応手段

4. **視覚的要素の追加**
   - 新しいフレームデザイン
   - アニメーション効果
   - 色分けされたアクティブメーター

このアップデートにより、MiniBirdはより使いやすく、管理しやすく、安全なプラットフォームになりました。
