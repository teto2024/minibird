<?php
// ===============================================
// community_feed.php
// コミュニティフィード表示ページ
// ===============================================

require_once __DIR__ . '/config.php';
$me = user();
if (!$me) {
    header('Location: ./');
    exit;
}

$pdo = db();
$community_id = intval($_GET['id'] ?? 0);

if (!$community_id) {
    echo "<!DOCTYPE html><html><head><title>エラー</title></head><body>";
    echo "<h1>コミュニティIDが指定されていません</h1>";
    echo "<a href='index.php'>トップに戻る</a>";
    echo "</body></html>";
    exit;
}

// コミュニティ情報取得
$stmt = $pdo->prepare("
    SELECT c.*, u.handle as owner_handle
    FROM communities c
    JOIN users u ON u.id = c.owner_id
    WHERE c.id = ?
");
$stmt->execute([$community_id]);
$community = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$community) {
    echo "コミュニティが見つかりません";
    exit;
}

// メンバーチェック
$stmt = $pdo->prepare("SELECT role FROM community_members WHERE community_id=? AND user_id=?");
$stmt->execute([$community_id, $me['id']]);
$member = $stmt->fetch();

if (!$member) {
    echo "このコミュニティのメンバーではありません";
    exit;
}

$is_owner = ($community['owner_id'] == $me['id']);
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($community['name']) ?> - MiniBird</title>
<link rel="stylesheet" href="assets/style.css">
<style>
.community-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}
.community-header h1 {
    margin: 0 0 10px 0;
    font-size: 24px;
}
.community-header p {
    margin: 5px 0;
    opacity: 0.9;
}
.community-actions {
    margin: 20px 0;
    display: flex;
    gap: 10px;
}
.community-actions button {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
}
.btn-primary {
    background: #667eea;
    color: white;
}
.btn-secondary {
    background: #cbd5e0;
    color: #2d3748;
}
.post-form {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
}
.post-form textarea {
    width: 100%;
    min-height: 80px;
    padding: 10px;
    border: 1px solid #cbd5e0;
    border-radius: 6px;
    resize: vertical;
    font-family: inherit;
}
.post-form button {
    margin-top: 10px;
    padding: 8px 20px;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
}
.community-post {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
}
.post-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}
.post-author {
    font-weight: bold;
    color: #2d3748;
}
.post-time {
    color: #718096;
    font-size: 13px;
}
.post-content {
    margin: 10px 0;
    line-height: 1.5;
}
.post-actions {
    display: flex;
    gap: 15px;
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #e2e8f0;
}
.post-action-btn {
    background: none;
    border: none;
    color: #718096;
    cursor: pointer;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 5px;
}
.post-action-btn:hover {
    color: #667eea;
}
.post-action-btn.liked {
    color: #e53e3e;
}
.replies-section {
    margin-top: 15px;
    padding-left: 20px;
    border-left: 3px solid #e2e8f0;
}
.reply-form {
    margin: 10px 0;
    display: none;
}
.reply-form textarea {
    width: 100%;
    min-height: 60px;
    padding: 8px;
    border: 1px solid #cbd5e0;
    border-radius: 6px;
    font-family: inherit;
}
.reply-form button {
    margin-top: 5px;
    padding: 6px 12px;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
}
</style>
</head>
<body>
<div class="container" style="max-width: 800px; margin: 0 auto; padding: 20px;">
    <div class="community-header">
        <h1><?= htmlspecialchars($community['name']) ?></h1>
        <p><?= htmlspecialchars($community['description']) ?></p>
        <p>オーナー: @<?= htmlspecialchars($community['owner_handle']) ?> | <?= $is_owner ? '👑 あなたは管理者です' : 'メンバー' ?></p>
    </div>
    
    <div class="community-actions">
        <button class="btn-secondary" onclick="location.href='index.php'">← フィードに戻る</button>
        <?php if ($is_owner): ?>
        <button class="btn-primary" onclick="manageMembers()">メンバー管理</button>
        <?php endif; ?>
    </div>
    
    <div class="post-form">
        <h3>新規投稿</h3>
        <form id="postForm">
            <textarea name="content" placeholder="コミュニティに投稿..." required></textarea>
            <label>
                <input type="checkbox" name="is_nsfw"> NSFW（成人向けコンテンツ）
            </label>
            <br>
            <button type="submit">投稿する</button>
        </form>
    </div>
    
    <div id="posts"></div>
</div>

<script>
const COMMUNITY_ID = <?= $community_id ?>;
const USER_ID = <?= $me['id'] ?>;
let lastLoadTime = 0;

// 投稿フォーム送信
document.getElementById('postForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData();
    formData.append('action', 'create_post');
    formData.append('community_id', COMMUNITY_ID);
    formData.append('content', form.content.value);
    formData.append('is_nsfw', form.is_nsfw.checked ? '1' : '0');
    
    try {
        const res = await fetch('community_api.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.ok) {
            form.content.value = '';
            form.is_nsfw.checked = false;
            loadPosts();
        } else {
            alert('投稿エラー: ' + data.error);
        }
    } catch (err) {
        alert('ネットワークエラー');
    }
});

// 投稿読み込み
async function loadPosts() {
    try {
        const res = await fetch(`community_api.php?action=get_posts&community_id=${COMMUNITY_ID}&t=${Date.now()}`);
        const data = await res.json();
        
        if (data.ok) {
            renderPosts(data.posts);
            lastLoadTime = Date.now();
        }
    } catch (err) {
        console.error('投稿読み込みエラー', err);
    }
}

// 投稿レンダリング
function renderPosts(posts) {
    const container = document.getElementById('posts');
    container.innerHTML = posts.map(post => `
        <div class="community-post" data-post-id="${post.id}">
            <div class="post-header">
                <span class="post-author">@${post.handle}</span>
                <span class="post-time">${formatTime(post.created_at)}</span>
            </div>
            <div class="post-content">${escapeHtml(post.content)}</div>
            ${post.media_path ? `<img src="${post.media_path}" style="max-width: 100%; border-radius: 6px; margin-top: 10px;">` : ''}
            <div class="post-actions">
                <button class="post-action-btn ${post.user_liked ? 'liked' : ''}" onclick="toggleLike(${post.id})">
                    ❤️ <span class="like-count">${post.like_count || 0}</span>
                </button>
                <button class="post-action-btn" onclick="toggleReplyForm(${post.id})">
                    💬 返信 <span class="reply-count">${post.reply_count || 0}</span>
                </button>
                <button class="post-action-btn" onclick="loadReplies(${post.id})">
                    👁️ 返信を見る
                </button>
            </div>
            <div class="reply-form" id="replyForm-${post.id}">
                <textarea placeholder="返信を入力..." id="replyText-${post.id}"></textarea>
                <button onclick="postReply(${post.id})">返信する</button>
            </div>
            <div class="replies-section" id="replies-${post.id}"></div>
        </div>
    `).join('');
}

// いいね切り替え
async function toggleLike(postId) {
    try {
        const formData = new FormData();
        formData.append('action', 'toggle_like');
        formData.append('post_id', postId);
        
        const res = await fetch('community_api.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.ok) {
            const post = document.querySelector(`[data-post-id="${postId}"]`);
            const btn = post.querySelector('.post-action-btn');
            const likeCount = btn.querySelector('.like-count');
            
            if (data.action === 'liked') {
                btn.classList.add('liked');
            } else {
                btn.classList.remove('liked');
            }
            likeCount.textContent = data.like_count;
        }
    } catch (err) {
        console.error('いいねエラー', err);
    }
}

// 返信フォーム表示切り替え
function toggleReplyForm(postId) {
    const form = document.getElementById(`replyForm-${postId}`);
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}

// 返信投稿
async function postReply(postId) {
    const text = document.getElementById(`replyText-${postId}`).value.trim();
    if (!text) return;
    
    try {
        const formData = new FormData();
        formData.append('action', 'create_post');
        formData.append('community_id', COMMUNITY_ID);
        formData.append('content', text);
        formData.append('parent_id', postId);
        
        const res = await fetch('community_api.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.ok) {
            document.getElementById(`replyText-${postId}`).value = '';
            loadReplies(postId);
        }
    } catch (err) {
        console.error('返信エラー', err);
    }
}

// 返信読み込み
async function loadReplies(postId) {
    try {
        const res = await fetch(`community_api.php?action=get_replies&post_id=${postId}`);
        const data = await res.json();
        
        if (data.ok) {
            const container = document.getElementById(`replies-${postId}`);
            container.innerHTML = data.replies.map(reply => `
                <div class="community-post" style="margin-bottom: 10px;">
                    <div class="post-header">
                        <span class="post-author">@${reply.handle}</span>
                        <span class="post-time">${formatTime(reply.created_at)}</span>
                    </div>
                    <div class="post-content">${escapeHtml(reply.content)}</div>
                    <div class="post-actions">
                        <button class="post-action-btn ${reply.user_liked ? 'liked' : ''}" onclick="toggleLike(${reply.id})">
                            ❤️ <span class="like-count">${reply.like_count || 0}</span>
                        </button>
                    </div>
                </div>
            `).join('');
        }
    } catch (err) {
        console.error('返信読み込みエラー', err);
    }
}

// ユーティリティ関数
function formatTime(datetime) {
    const date = new Date(datetime);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000);
    
    if (diff < 60) return `${diff}秒前`;
    if (diff < 3600) return `${Math.floor(diff / 60)}分前`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}時間前`;
    return `${Math.floor(diff / 86400)}日前`;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML.replace(/\n/g, '<br>');
}

function manageMembers() {
    location.href = `community_members.php?id=${COMMUNITY_ID}`;
}

// 初回読み込み
loadPosts();

// 3秒ごとに自動更新
setInterval(loadPosts, 3000);
</script>
</body>
</html>
