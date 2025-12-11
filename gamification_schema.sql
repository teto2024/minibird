-- ===============================================
-- MiniBird 拡張機能用テーブル定義
-- コミュニティ、ゲーミフィケーション、ショップ拡張、トレンド機能
-- ===============================================

USE microblog;

-- ===============================================
-- コミュニティ関連テーブル
-- ===============================================

-- コミュニティテーブル（既存の場合はスキップ）
CREATE TABLE IF NOT EXISTS communities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    owner_id INT UNSIGNED NOT NULL,
    is_private BOOLEAN NOT NULL DEFAULT TRUE COMMENT '非公開コミュニティフィード',
    allow_repost BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'リポスト許可（基本的にfalse）',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_owner (owner_id),
    INDEX idx_private (is_private)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='コミュニティ情報';

-- コミュニティメンバーテーブル（既存の場合はスキップ）
CREATE TABLE IF NOT EXISTS community_members (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    community_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    added_by INT UNSIGNED NOT NULL COMMENT '招待したユーザー',
    role ENUM('owner', 'admin', 'member') NOT NULL DEFAULT 'member',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (community_id) REFERENCES communities(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (added_by) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_community_user (community_id, user_id),
    INDEX idx_community (community_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='コミュニティメンバー';

-- コミュニティ投稿テーブル
CREATE TABLE IF NOT EXISTS community_posts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    community_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    content TEXT NOT NULL,
    media_path VARCHAR(255),
    is_nsfw BOOLEAN NOT NULL DEFAULT FALSE,
    parent_id BIGINT UNSIGNED NULL COMMENT '返信の場合、親投稿ID',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (community_id) REFERENCES communities(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES community_posts(id) ON DELETE CASCADE,
    INDEX idx_community_created (community_id, created_at),
    INDEX idx_parent (parent_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='コミュニティ投稿';

-- コミュニティ投稿へのいいね
CREATE TABLE IF NOT EXISTS community_post_likes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id BIGINT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES community_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_post_user (post_id, user_id),
    INDEX idx_post (post_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='コミュニティ投稿いいね';

-- ===============================================
-- ゲーミフィケーション関連テーブル
-- ===============================================

-- クエストマスターテーブル
CREATE TABLE IF NOT EXISTS quests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quest_key VARCHAR(100) NOT NULL UNIQUE,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    type ENUM('daily', 'weekly', 'relay') NOT NULL DEFAULT 'daily',
    conditions JSON NOT NULL COMMENT '{"action": "post", "count": 5} など',
    reward_coins INT UNSIGNED NOT NULL DEFAULT 0,
    reward_crystals INT UNSIGNED NOT NULL DEFAULT 0,
    reward_diamonds INT UNSIGNED NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    relay_order INT UNSIGNED NULL COMMENT 'リレークエストの順番',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_active (is_active),
    INDEX idx_relay_order (relay_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='クエストマスターデータ';

-- ユーザークエスト進行状況
CREATE TABLE IF NOT EXISTS user_quest_progress (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    quest_id INT UNSIGNED NOT NULL,
    progress INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('active', 'completed', 'expired') NOT NULL DEFAULT 'active',
    started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    expired_at DATETIME NULL COMMENT 'デイリー・ウィークリーの有効期限',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (quest_id) REFERENCES quests(id) ON DELETE CASCADE,
    INDEX idx_user_status (user_id, status),
    INDEX idx_expired (expired_at),
    UNIQUE KEY unique_user_quest_period (user_id, quest_id, started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='ユーザークエスト進行状況';

-- リレークエスト進行状況
CREATE TABLE IF NOT EXISTS relay_quest_progress (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    current_order INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '現在のリレー順番',
    last_completed_quest_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (last_completed_quest_id) REFERENCES quests(id) ON DELETE SET NULL,
    UNIQUE KEY unique_user (user_id),
    INDEX idx_order (current_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='リレークエスト進行状況';

-- 通貨テーブル（users テーブルに既に coins, crystals があると仮定し、diamonds を追加）
-- ALTER TABLE users ADD COLUMN IF NOT EXISTS diamonds INT UNSIGNED NOT NULL DEFAULT 0;

-- ===============================================
-- ショップ拡張関連テーブル
-- ===============================================

-- 絵文字パッケージ
CREATE TABLE IF NOT EXISTS emoji_packages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    preview_emoji TEXT COMMENT 'プレビュー用絵文字例',
    price_coins INT UNSIGNED NOT NULL DEFAULT 0,
    price_crystals INT UNSIGNED NOT NULL DEFAULT 0,
    price_diamonds INT UNSIGNED NOT NULL DEFAULT 0,
    emoji_data JSON NOT NULL COMMENT '[{"code": ":custom1:", "image_url": "/uploads/emoji1.png"}]',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_price (price_coins, price_crystals, price_diamonds)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='絵文字パッケージマスター';

-- ユーザー所有絵文字パッケージ
CREATE TABLE IF NOT EXISTS user_emoji_packages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    package_id INT UNSIGNED NOT NULL,
    purchased_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES emoji_packages(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_package (user_id, package_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='ユーザー所有絵文字パッケージ';

-- 称号パッケージ
CREATE TABLE IF NOT EXISTS title_packages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    title_text VARCHAR(50) NOT NULL COMMENT '称号テキスト',
    title_css VARCHAR(255) COMMENT '称号用CSSクラス',
    preview_html TEXT COMMENT 'プレビュー用HTML',
    price_coins INT UNSIGNED NOT NULL DEFAULT 0,
    price_crystals INT UNSIGNED NOT NULL DEFAULT 0,
    price_diamonds INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_price (price_coins, price_crystals, price_diamonds)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='称号パッケージマスター';

-- ユーザー所有称号
CREATE TABLE IF NOT EXISTS user_titles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title_id INT UNSIGNED NOT NULL,
    is_equipped BOOLEAN NOT NULL DEFAULT FALSE,
    purchased_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (title_id) REFERENCES title_packages(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_title (user_id, title_id),
    INDEX idx_user (user_id),
    INDEX idx_equipped (user_id, is_equipped)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='ユーザー所有称号';

-- ===============================================
-- トレンド機能関連テーブル
-- ===============================================

-- トレンドワードテーブル
CREATE TABLE IF NOT EXISTS trend_words (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    word VARCHAR(100) NOT NULL,
    post_count INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '登場投稿数',
    occurrence_count INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '純粋な登場回数',
    total_likes INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '登場投稿の総いいね数',
    total_reposts INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '登場投稿の総リポスト数',
    trend_score FLOAT NOT NULL DEFAULT 0 COMMENT '総合トレンドスコア',
    calculated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    period_start DATETIME NOT NULL COMMENT '集計期間開始',
    period_end DATETIME NOT NULL COMMENT '集計期間終了',
    INDEX idx_score (trend_score),
    INDEX idx_calculated (calculated_at),
    INDEX idx_word (word),
    INDEX idx_period (period_start, period_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='トレンドワード集計';

-- ストップワード（除外単語）
CREATE TABLE IF NOT EXISTS stopwords (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    word VARCHAR(100) NOT NULL UNIQUE,
    category ENUM('particle', 'auxiliary', 'common', 'custom') NOT NULL DEFAULT 'common',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_word (word),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='トレンド検出から除外する単語';

-- ===============================================
-- 初期データ投入
-- ===============================================

-- デイリークエスト初期データ
INSERT IGNORE INTO quests (quest_key, title, description, type, conditions, reward_coins, reward_crystals, reward_diamonds) VALUES
('daily_post_5', '5回投稿する', '今日中に5回投稿しよう', 'daily', '{"action": "post", "count": 5}', 100, 1, 0),
('daily_like_10', '10回いいねする', '今日中に10回いいねしよう', 'daily', '{"action": "like", "count": 10}', 50, 0, 0),
('daily_repost_3', '3回リポストする', '今日中に3回リポストしよう', 'daily', '{"action": "repost", "count": 3}', 80, 1, 0),
('daily_say_hello', '「こんにちは」と発言する', '「こんにちは」を含む投稿をしよう', 'daily', '{"action": "post_contains", "text": "こんにちは"}', 150, 2, 0),
('daily_say_thanks', '「ありがとう」と発言する', '「ありがとう」を含む投稿をしよう', 'daily', '{"action": "post_contains", "text": "ありがとう"}', 150, 2, 0);

-- ウィークリークエスト初期データ
INSERT IGNORE INTO quests (quest_key, title, description, type, conditions, reward_coins, reward_crystals, reward_diamonds) VALUES
('weekly_post_30', '30回投稿する', '今週中に30回投稿しよう', 'weekly', '{"action": "post", "count": 30}', 500, 10, 1),
('weekly_like_50', '50回いいねする', '今週中に50回いいねしよう', 'weekly', '{"action": "like", "count": 50}', 300, 5, 0),
('weekly_repost_20', '20回リポストする', '今週中に20回リポストしよう', 'weekly', '{"action": "repost", "count": 20}', 400, 8, 1);

-- リレークエスト初期データ
INSERT IGNORE INTO quests (quest_key, title, description, type, conditions, reward_coins, reward_crystals, reward_diamonds, relay_order) VALUES
('relay_1_post', 'リレー1: 投稿する', 'まずは1回投稿しよう', 'relay', '{"action": "post", "count": 1}', 50, 1, 0, 1),
('relay_2_like', 'リレー2: いいねする', '誰かの投稿にいいねしよう', 'relay', '{"action": "like", "count": 1}', 50, 1, 0, 2),
('relay_3_post_3', 'リレー3: 3回投稿', '3回投稿しよう', 'relay', '{"action": "post", "count": 3}', 100, 2, 0, 3),
('relay_4_repost', 'リレー4: リポスト', 'リポストしよう', 'relay', '{"action": "repost", "count": 1}', 100, 2, 0, 4),
('relay_5_post_5', 'リレー5: 5回投稿', '5回投稿しよう', 'relay', '{"action": "post", "count": 5}', 200, 5, 1, 5);

-- 絵文字パッケージ初期データ
INSERT IGNORE INTO emoji_packages (name, description, preview_emoji, price_coins, price_crystals, price_diamonds, emoji_data) VALUES
('ベーシックパック', '基本的なカスタム絵文字セット', '😀😎🎉', 1000, 5, 0, '[{"code":":happy_bird:", "char":"🐦"}, {"code":":cool_cat:", "char":"😎"}, {"code":":party:", "char":"🎉"}]'),
('アニマルパック', '動物系の絵文字セット', '🐶🐱🐭', 1500, 8, 0, '[{"code":":dog:", "char":"🐶"}, {"code":":cat:", "char":"🐱"}, {"code":":mouse:", "char":"🐭"}, {"code":":fox:", "char":"🦊"}]'),
('フードパック', '食べ物系の絵文字セット', '🍕🍣🍰', 1200, 6, 0, '[{"code":":pizza:", "char":"🍕"}, {"code":":sushi:", "char":"🍣"}, {"code":":cake:", "char":"🍰"}, {"code":":ramen:", "char":"🍜"}]'),
('プレミアムGIFパック', 'アニメーションGIF絵文字', '✨💫⭐', 5000, 50, 5, '[{"code":":sparkle_anim:", "char":"✨"}, {"code":":star_anim:", "char":"⭐"}, {"code":":fire_anim:", "char":"🔥"}]');

-- 称号パッケージ初期データ
INSERT IGNORE INTO title_packages (name, description, title_text, title_css, preview_html, price_coins, price_crystals, price_diamonds) VALUES
('初心者', '初心者の証', '🔰初心者', 'title-beginner', '<span class="title-beginner">🔰初心者</span>', 100, 0, 0),
('ベテラン', 'ベテランの証', '⭐ベテラン', 'title-veteran', '<span class="title-veteran">⭐ベテラン</span>', 5000, 10, 0),
('マスター', 'マスターの証', '👑マスター', 'title-master', '<span class="title-master">👑マスター</span>', 20000, 50, 5),
('伝説', '伝説の称号', '✨伝説✨', 'title-legend', '<span class="title-legend">✨伝説✨</span>', 100000, 200, 20),
('コミュニティリーダー', 'コミュニティの主催者', '📢リーダー', 'title-leader', '<span class="title-leader">📢リーダー</span>', 10000, 30, 3),
('トレンドセッター', 'トレンドを作る者', '🔥トレンドセッター', 'title-trendsetter', '<span class="title-trendsetter">🔥トレンドセッター</span>', 15000, 40, 5);

-- ストップワード初期データ（日本語助詞など）
INSERT IGNORE INTO stopwords (word, category) VALUES
('の', 'particle'), ('に', 'particle'), ('は', 'particle'), ('を', 'particle'), ('が', 'particle'),
('と', 'particle'), ('で', 'particle'), ('から', 'particle'), ('まで', 'particle'), ('より', 'particle'),
('へ', 'particle'), ('や', 'particle'), ('か', 'particle'), ('も', 'particle'), ('ね', 'particle'),
('よ', 'particle'), ('な', 'particle'), ('ば', 'particle'), ('て', 'particle'), ('だ', 'particle'),
('です', 'auxiliary'), ('ます', 'auxiliary'), ('した', 'auxiliary'), ('する', 'auxiliary'), ('ある', 'auxiliary'),
('いる', 'auxiliary'), ('なる', 'auxiliary'), ('れる', 'auxiliary'), ('られる', 'auxiliary'), ('せる', 'auxiliary'),
('これ', 'common'), ('それ', 'common'), ('あれ', 'common'), ('この', 'common'), ('その', 'common'),
('あの', 'common'), ('ここ', 'common'), ('そこ', 'common'), ('あそこ', 'common'), ('どこ', 'common'),
('今日', 'common'), ('昨日', 'common'), ('明日', 'common'), ('今', 'common'), ('さっき', 'common'),
('でも', 'common'), ('しかし', 'common'), ('けど', 'common'), ('ので', 'common'), ('から', 'common');

-- テーブル作成完了メッセージ
SELECT 'Gamification and extended feature tables created successfully' AS status;
