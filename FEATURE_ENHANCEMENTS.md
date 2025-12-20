# MiniBird 機能拡張 - 実装ドキュメント

このドキュメントでは、MiniBirdに追加された新機能について説明します。

## 実装された機能

### 1. 複数画像アップロード（最大4枚）

#### 機能概要
- ユーザーは1つの投稿に最大4枚の画像を添付できます
- 画像は自動的にグリッドレイアウトで表示されます
- 動画と画像の混在もサポートしています

#### 技術実装
- **データベース**: `posts`テーブルに`media_paths` (JSON)カラムを追加
- **バックエンド**: `post.php`で複数ファイルアップロード処理（`media_0`, `media_1`, `media_2`, `media_3`）
- **フロントエンド**: 
  - `index.php`: `<input type="file" multiple>`対応
  - `assets/app.js`: FormDataで複数ファイル送信
  - `assets/style.css`: レスポンシブグリッドレイアウト

#### グリッドレイアウト
- 1枚: 1列
- 2枚: 2列（横並び）
- 3枚: 1枚目が上段全幅、2-3枚目が下段2列
- 4枚: 2×2グリッド

### 2. 画像サイズ自動調整

#### 機能概要
- 画像は`aspect-ratio: 16/9`で統一表示
- `object-fit: cover`で画像の切り抜き表示
- レスポンシブ対応

#### 技術実装
- `assets/style.css`で`.media-item`にCSSグリッド適用
- 画像は`img { object-fit: cover; }`で自動調整

### 3. リポスト時の画像・NSFWコピー修正

#### 問題点（修正前）
- リポスト時に画像がコピーされない
- NSFWフラグがコピーされず、ブラーが外れる

#### 修正内容
- `actions.php`の`toggle_repost`アクションを修正
- リポスト作成時に`nsfw`, `media_path`, `media_type`, `media_paths`を元の投稿からコピー
```php
INSERT INTO posts(user_id, content_md, content_html, nsfw, media_path, media_type, media_paths, is_repost_of, created_at)
SELECT ?, content_md, content_html, nsfw, media_path, media_type, media_paths, id, NOW()
FROM posts WHERE id=?
```

### 4. 期間限定フレーム機能

#### 機能概要
- フレームに販売期間を設定可能
- 期間外は購入不可
- 残り日数を表示

#### 技術実装
- **データベース**: `frames`テーブルに以下を追加
  - `sale_start_date` (DATETIME): 販売開始日
  - `sale_end_date` (DATETIME): 販売終了日
  - `is_limited` (BOOLEAN): 期間限定フラグ
- **バックエンド**: `shop.php`で期間チェック
- **フロントエンド**: 残り日数表示、期間外は購入ボタン無効化

#### 表示例
```
⏰ 期間限定（あと5日）
```

### 5. ユーザー考案フレーム機能

#### 機能概要
- ユーザーがオリジナルフレームデザインを提出
- 管理者が審査・承認
- 承認されたフレームはショップに掲載

#### 技術実装

##### データベーステーブル
1. `user_designed_frames`: ユーザー提出フレーム管理
   - `user_id`: 提案者
   - `name`, `css_token`, `preview_css`: フレーム情報
   - `status`: pending/approved/rejected
   - `admin_comment`: 管理者コメント
   - `approved_frame_id`: 承認後のframe_id

2. `frames`テーブルに追加
   - `designed_by_user_id`: 考案者のユーザーID
   - `is_user_designed`: ユーザー考案フラグ

##### ユーザー側機能（frame_submit.php）
- フレーム名、CSSトークン、プレビューCSS、説明を入力
- 価格提案（管理者が最終決定）
- 提出履歴の確認
- 審査状況の確認

##### 管理者側機能（frame_admin.php）
- 審査待ちフレームの一覧表示
- フレーム詳細確認
- 承認/却下の判断
- 価格調整
- 期間限定設定
- 承認時に自動でショップに追加

##### 表示
- ショップでは「👤 ユーザー考案 by @username」と表示

## データベース移行

### 実行手順
1. `posts_enhancement_schema.sql`を実行
```bash
mysql -u user -p database < posts_enhancement_schema.sql
```

2. `frames_enhancement_schema.sql`を実行
```bash
mysql -u user -p database < frames_enhancement_schema.sql
```

### 既存データの移行（任意）
既存の単一画像を`media_paths`に移行する場合:
```sql
UPDATE posts 
SET media_paths = JSON_ARRAY(media_path) 
WHERE media_path IS NOT NULL AND media_paths IS NULL;
```

## セキュリティ

### 実装されたセキュリティ対策

1. **ファイルアップロード**
   - ファイルサイズ制限: 10MB（`$MAX_UPLOAD_BYTES`）
   - 拡張子検証
   - ランダムファイル名生成（`bin2hex(random_bytes(12))`）

2. **入力検証**
   - CSSトークンのサーバーサイド検証（`preg_match('/^frame-[a-z0-9-]+$/')`）
   - 重複チェック
   - XSS対策: `htmlspecialchars()`使用

3. **認証・認可**
   - ログイン必須機能に`require_login()`
   - 管理者機能はロールチェック
   - CSRF対策は既存システムに準拠

### CodeQL分析結果
- **JavaScript**: 0件の脆弱性

## パフォーマンス最適化

1. **画像読み込み**
   - `loading="lazy"`属性で遅延読み込み
   - `aspect-ratio`でレイアウトシフト防止

2. **データベース**
   - `media_paths`をJSONで保存（正規化不要）
   - インデックス追加済み

3. **フロントエンド**
   - 定数化（`MAX_MEDIA_FILES = 4`）
   - 重複コード削減

## 後方互換性

- 既存の単一画像投稿は引き続き動作
- `media_path`カラムは保持（既存コードとの互換性）
- 複数画像がある場合は`media_paths`を優先

## 今後の拡張案

1. 画像編集機能（トリミング、フィルター）
2. アニメーションGIF対応
3. フレームプレビュー機能の強化
4. ユーザー考案フレームの投票システム
5. 画像の圧縮・最適化

## 問い合わせ

不具合報告や機能要望は、GitHubのIssueにてお願いします。
