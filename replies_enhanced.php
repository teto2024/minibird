<?php
// ===============================================
// replies_enhanced.php
// 改良版返信ページ（いいね、リポスト、ネスト対応）
// ===============================================

require_once __DIR__ . '/config.php';
$me = user();
$post_id = (int)($_GET['post_id'] ?? 0);

if ($post_id <= 0) {
    http_response_code(400);
    echo "無効な投稿IDです";
    exit;
}

$pdo = db();

// 元投稿取得（フレーム情報、称号情報も含む）
$stmt = $pdo->prepare("
    SELECT p.*, u.handle, u.display_name, u.icon, u.active_frame_id, u.vip_level,
           f.css_token as frame_class,
           ut.title_id, tp.title_text, tp.title_css,
           (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
           (SELECT COUNT(*) FROM posts WHERE repost_of = p.id) as repost_count,
           (SELECT 1 FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked
    FROM posts p
    JOIN users u ON u.id = p.user_id
    LEFT JOIN frames f ON f.id = u.active_frame_id
    LEFT JOIN user_titles ut ON ut.user_id = u.id AND ut.is_equipped = TRUE
    LEFT JOIN title_packages tp ON tp.id = ut.title_id
    WHERE p.id = ?
");
$stmt->execute([$me ? $me['id'] : 0, $post_id]);
$original_post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$original_post) {
    http_response_code(404);
    echo "投稿が見つかりません";
    exit;
}
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>返信 - MiniBird</title>
<link rel="stylesheet" href="assets/style.css">
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<style>
.replies-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}
.original-post {
    background: white;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.reply-form {
    background: white;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
}
.reply-form h3 {
    margin: 0 0 15px 0;
    color: #2d3748;
}
.reply-form textarea {
    width: 100%;
    min-height: 100px;
    padding: 12px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-family: inherit;
    font-size: 14px;
    resize: vertical;
    margin-bottom: 10px;
}
.reply-form textarea:focus {
    outline: none;
    border-color: #667eea;
}
.reply-form-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.reply-form button {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 10px 24px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: bold;
}
.replies-list {
    background: white;
    border-radius: 12px;
    padding: 20px;
}
.replies-list h3 {
    margin: 0 0 20px 0;
    color: #2d3748;
}
.reply-item {
    border-left: 3px solid #e2e8f0;
    padding-left: 15px;
    margin-bottom: 20px;
    transition: border-color 0.3s;
}
.reply-item:hover {
    border-left-color: #667eea;
}
.reply-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}
.reply-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}
.reply-meta {
    flex: 1;
}
.reply-author {
    font-weight: bold;
    color: #2d3748;
}
.reply-title {
    margin-left: 8px;
    font-size: 12px;
    padding: 2px 8px;
    border-radius: 4px;
}
.reply-time {
    color: #a0aec0;
    font-size: 13px;
}
.reply-content {
    margin: 10px 0;
    line-height: 1.6;
    color: #4a5568;
}
.reply-actions {
    display: flex;
    gap: 15px;
    margin-top: 10px;
}
.reply-action-btn {
    background: none;
    border: none;
    color: #718096;
    cursor: pointer;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 5px 10px;
    border-radius: 6px;
    transition: all 0.3s;
}
.reply-action-btn:hover {
    background: #f7fafc;
    color: #667eea;
}
.reply-action-btn.liked {
    color: #e53e3e;
}
.reply-action-btn.reposted {
    color: #48bb78;
}
.nested-replies {
    margin-top: 15px;
    margin-left: 20px;
    border-left: 2px solid #e2e8f0;
    padding-left: 15px;
}
.empty-state {
    text-align: center;
    padding: 40px;
    color: #a0aec0;
}

/* フレームスタイル */
.frame-classic { border: 2px solid #cbd5e0; }
.frame-neon { border: 2px solid #f093fb; box-shadow: 0 0 10px rgba(240, 147, 251, 0.5); }
.frame-sakura { border: 2px solid #fbb6ce; }
.frame-fireworks { border: 2px solid #f59e0b; }
.frame-cyberpunk { border: 2px solid #8b5cf6; }
.frame-vip { border: 3px solid #f59e0b; box-shadow: 0 0 15px rgba(245, 158, 11, 0.6); }
.frame-purple { border: 2px solid #9f7aea; }
.frame-stars { border: 2px solid #ecc94b; }

/* 称号スタイル */
.title-beginner { color: #4299e1; }
.title-veteran { color: #ed8936; text-shadow: 0 0 5px rgba(237, 137, 54, 0.3); }
.title-master { color: #9f7aea; text-shadow: 0 0 10px rgba(159, 122, 234, 0.5); }
.title-legend { 
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.title-leader { color: #48bb78; font-weight: bold; }
.title-trendsetter { color: #f56565; }
</style>
</head>
<body>
<header class="topbar">
    <div class="logo"><a href="index.php">← フィードに戻る</a></div>
</header>

<div class="replies-container">
    <!-- 元投稿表示 -->
    <div class="original-post <?= htmlspecialchars($original_post['frame_class'] ?? '') ?>">
        <div class="reply-header">
            <img src="<?= htmlspecialchars($original_post['icon'] ?? '/uploads/icons/default_icon.png') ?>" 
                 alt="<?= htmlspecialchars($original_post['display_name'] ?? $original_post['handle']) ?>" 
                 class="reply-avatar">
            <div class="reply-meta">
                <div>
                    <span class="reply-author">
                        <?= htmlspecialchars($original_post['display_name'] ?? $original_post['handle']) ?>
                    </span>
                    <?php if ($original_post['title_text']): ?>
                    <span class="reply-title <?= htmlspecialchars($original_post['title_css']) ?>">
                        <?= htmlspecialchars($original_post['title_text']) ?>
                    </span>
                    <?php endif; ?>
                </div>
                <div class="reply-time">
                    @<?= htmlspecialchars($original_post['handle']) ?> · 
                    <?= date('Y/m/d H:i', strtotime($original_post['created_at'])) ?>
                </div>
            </div>
        </div>
        <div class="reply-content">
            <?= $original_post['content_html'] ?>
        </div>
        <div class="reply-actions">
            <button class="reply-action-btn <?= $original_post['user_liked'] ? 'liked' : '' ?>" 
                    onclick="toggleLike(<?= $original_post['id'] ?>, this)">
                ❤️ <span class="like-count"><?= $original_post['like_count'] ?></span>
            </button>
            <button class="reply-action-btn">
                💬 返信する
            </button>
        </div>
    </div>

    <!-- 返信フォーム -->
    <?php if ($me): ?>
    <div class="reply-form">
        <h3>返信を投稿</h3>
        <textarea id="replyText" maxlength="1024" placeholder="返信を入力（Markdown対応）"></textarea>
        <div class="reply-form-actions">
            <label>
                <input type="checkbox" id="nsfw"> NSFW
            </label>
            <button id="sendReply">返信する</button>
        </div>
    </div>
    <?php else: ?>
    <div class="reply-form">
        <p style="text-align: center; color: #a0aec0;">ログインして返信しましょう</p>
    </div>
    <?php endif; ?>

    <!-- 返信一覧 -->
    <div class="replies-list">
        <h3>返信 (<span id="replyCount">0</span>)</h3>
        <div id="repliesList"></div>
    </div>
</div>

<script>
const POST_ID = <?= $post_id ?>;
const USER_ID = <?= $me ? $me['id'] : 0 ?>;
let replies = [];

// 返信読み込み
async function loadReplies() {
    try {
        const res = await fetch('replies_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'list', post_id: POST_ID})
        });
        const data = await res.json();
        
        if (data.ok) {
            replies = data.items || [];
            renderReplies();
        }
    } catch (err) {
        console.error('返信読み込みエラー', err);
    }
}

// 返信レンダリング
function renderReplies() {
    const container = document.getElementById('repliesList');
    document.getElementById('replyCount').textContent = replies.length;
    
    if (replies.length === 0) {
        container.innerHTML = '<div class="empty-state">まだ返信がありません</div>';
        return;
    }
    
    // 親投稿のみをレンダリング（ネスト構造を後で実装）
    const topLevelReplies = replies.filter(r => !r.parent_id || r.parent_id === POST_ID);
    container.innerHTML = topLevelReplies.map(reply => renderReply(reply)).join('');
}

// 単一返信のHTML生成
function renderReply(reply) {
    const frameClass = reply.frame_class || '';
    const titleHtml = reply.title_text ? 
        `<span class="reply-title ${reply.title_css}">${reply.title_text}</span>` : '';
    
    return `
        <div class="reply-item ${frameClass}" data-reply-id="${reply.id}">
            <div class="reply-header">
                <img src="${reply.icon || '/uploads/icons/default_icon.png'}" 
                     alt="${reply.display_name || reply.handle}" 
                     class="reply-avatar">
                <div class="reply-meta">
                    <div>
                        <span class="reply-author">${reply.display_name || reply.handle}</span>
                        ${titleHtml}
                    </div>
                    <div class="reply-time">
                        @${reply.handle} · ${formatTime(reply.created_at)}
                    </div>
                </div>
            </div>
            <div class="reply-content">${marked.parse(reply.content_md || reply.content_html)}</div>
            <div class="reply-actions">
                <button class="reply-action-btn ${reply.user_liked ? 'liked' : ''}" 
                        onclick="toggleLike(${reply.id}, this)">
                    ❤️ <span class="like-count">${reply.like_count || 0}</span>
                </button>
                <button class="reply-action-btn" onclick="replyTo(${reply.id})">
                    💬 返信
                </button>
            </div>
        </div>
    `;
}

// いいね切り替え
async function toggleLike(postId, btn) {
    if (!USER_ID) {
        alert('ログインしてください');
        return;
    }
    
    try {
        const res = await fetch('actions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'toggle_like', post_id: postId})
        });
        const data = await res.json();
        
        if (data.ok) {
            const likeCountSpan = btn.querySelector('.like-count');
            likeCountSpan.textContent = data.count;
            
            if (data.liked) {
                btn.classList.add('liked');
            } else {
                btn.classList.remove('liked');
            }
        }
    } catch (err) {
        console.error('いいねエラー', err);
    }
}

// 返信投稿
document.getElementById('sendReply')?.addEventListener('click', async () => {
    const content = document.getElementById('replyText').value.trim();
    if (!content) {
        alert('返信内容を入力してください');
        return;
    }
    
    try {
        const res = await fetch('replies_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'create',
                post_id: POST_ID,
                content: content,
                nsfw: document.getElementById('nsfw').checked ? 1 : 0
            })
        });
        const data = await res.json();
        
        if (data.ok) {
            document.getElementById('replyText').value = '';
            document.getElementById('nsfw').checked = false;
            loadReplies();
        } else {
            alert('返信失敗: ' + data.error);
        }
    } catch (err) {
        alert('ネットワークエラー');
    }
});

// 時刻フォーマット
function formatTime(datetime) {
    const date = new Date(datetime);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000);
    
    if (diff < 60) return `${diff}秒前`;
    if (diff < 3600) return `${Math.floor(diff / 60)}分前`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}時間前`;
    return `${Math.floor(diff / 86400)}日前`;
}

// 返信への返信
function replyTo(replyId) {
    document.getElementById('replyText').focus();
    // ネスト返信の実装は後ほど
}

// 初回読み込み
loadReplies();

// 3秒ごとに自動更新
setInterval(loadReplies, 3000);
</script>
</body>
</html>
