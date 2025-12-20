<?php
// ===============================================
// community_feed.php
// コミュニティフィード表示ページ
// ===============================================

require_once __DIR__ . '/config.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$me = user();
if (!$me) {
    header('Location: ./');
    exit;
}

$pdo = db();
$community_id = intval($_GET['id'] ?? 0);

if (!$community_id) {
    header('Location: ./');
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
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
}
.post-form textarea {
    width: 100%;
    min-height: 80px;
    padding: 10px;
    background: var(--bg);
    color: var(--text);
    border: 1px solid var(--border);
    border-radius: 6px;
    resize: vertical;
    font-family: inherit;
}
.post-form button {
    margin-top: 10px;
    padding: 8px 20px;
    background: var(--blue);
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
}
/*
.community-post {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 15px;
    margin-bottom: 15px;
    position: relative;
    transition: transform 0.3s, box-shadow 0.3s;
}
.community-post:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.6);
}
*/
/* フレームスタイルを適用可能にする */
.community-post[class*="frame-"] {
    border: none !important;
}
.post-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
    position: relative;
    z-index: 1;
}
.post-author {
    font-weight: bold;
    color: var(--text);
}
.post-time {
    color: var(--muted);
    font-size: 13px;
}
.post-content {
    margin: 10px 0;
    line-height: 1.5;
    color: var(--text);
    position: relative;
    z-index: 1;
}
.post-actions {
    display: flex;
    gap: 15px;
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid var(--border);
    position: relative;
    z-index: 1;
}
.post-action-btn {
    background: none;
    border: none;
    color: var(--muted);
    cursor: pointer;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 5px;
    transition: color 0.3s;
}
.post-action-btn:hover {
    color: var(--blue);
}
.post-action-btn.liked {
    color: var(--red);
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
/* NSFW モザイク */
.nsfw-blur {
    position: relative;
    cursor: pointer;
}
.nsfw-blur img,
.nsfw-blur video {
    filter: blur(20px);
    transition: filter 0.3s;
}
.nsfw-blur::after {
    content: '🔞 NSFW - クリックして表示';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 15px 25px;
    border-radius: 8px;
    font-weight: bold;
    pointer-events: none;
}
.nsfw-blur.revealed img,
.nsfw-blur.revealed video {
    filter: none;
}
.nsfw-blur.revealed::after {
    display: none;
}
</style>
</head>
<body>
<div class="container" style="max-width: 800px; margin: 0 auto; padding: 20px; background: var(--bg); min-height: 100vh;">
    <div class="community-header">
        <h1><?= htmlspecialchars($community['name']) ?></h1>
        <p><?= htmlspecialchars($community['description']) ?></p>
        <p>オーナー: @<?= htmlspecialchars($community['owner_handle']) ?> | <?= $is_owner ? '👑 あなたは管理者です' : 'メンバー' ?></p>
    </div>
    
    <div class="community-actions">
        <button class="btn-secondary" onclick="location.href='index.php'">← フィードに戻る</button>
        <?php if ($is_owner): ?>
        <button class="btn-primary" onclick="manageMembers()">メンバー管理</button>
        <button class="btn-primary" onclick="showEditCommunity()">コミュニティ編集</button>
        <button class="btn-danger" onclick="deleteCommunity()" style="background: #e53e3e;">コミュニティ削除</button>
        <?php else: ?>
        <button class="btn-danger" onclick="leaveCommunity()" style="background: #f56565;">コミュニティ脱退</button>
        <?php endif; ?>
    </div>
    
    <div class="post-form">
        <h3>新規投稿</h3>
        <form id="postForm" enctype="multipart/form-data">
            <textarea name="content" placeholder="コミュニティに投稿..." required></textarea>
            <div style="margin: 10px 0;">
                <label>
                    <input type="checkbox" name="is_nsfw"> NSFW（成人向けコンテンツ）
                </label>
            </div>
            <div style="margin: 10px 0;">
                <input type="file" name="media" accept="image/*,video/*" 
                       style="padding: 5px; border: 1px solid #cbd5e0; border-radius: 6px;">
            </div>
            <button type="submit" style="padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">投稿する</button>
        </form>
    </div>
    
    <div id="posts"></div>
</div>

<!-- コミュニティ編集モーダル -->
<div id="editCommunityModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background: white; padding: 30px; border-radius: 12px; max-width: 500px; width: 90%;">
        <h3>コミュニティ情報を編集</h3>
        <form id="editCommunityForm">
            <div style="margin-bottom: 15px;">
                <label for="edit_name" style="display: block; margin-bottom: 5px; font-weight: bold;">コミュニティ名</label>
                <input type="text" id="edit_name" name="name" value="<?= htmlspecialchars($community['name']) ?>" 
                       style="width: 100%; padding: 10px; border: 1px solid #cbd5e0; border-radius: 6px;" required>
            </div>
            <div style="margin-bottom: 15px;">
                <label for="edit_description" style="display: block; margin-bottom: 5px; font-weight: bold;">説明</label>
                <textarea id="edit_description" name="description" rows="4" 
                          style="width: 100%; padding: 10px; border: 1px solid #cbd5e0; border-radius: 6px;"><?= htmlspecialchars($community['description']) ?></textarea>
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" onclick="closeEditCommunity()" 
                        style="padding: 10px 20px; background: #cbd5e0; color: #2d3748; border: none; border-radius: 6px; cursor: pointer;">
                    キャンセル
                </button>
                <button type="submit" 
                        style="padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 6px; cursor: pointer;">
                    保存
                </button>
            </div>
        </form>
    </div>
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
    
    // ファイルがある場合は追加
    if (form.media.files.length > 0) {
        formData.append('media', form.media.files[0]);
    }
    
    try {
        const res = await fetch('community_api.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.ok) {
            form.content.value = '';
            form.is_nsfw.checked = false;
            form.media.value = '';
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
    container.innerHTML = posts.map(post => {
        const displayName = post.display_name || post.handle || 'unknown';
        const icon = post.icon || '/uploads/icons/default_icon.png';
        const frameClass = post.frame_class || '';
        const titleHtml = post.title_text && post.title_css ? 
            `<span class="user-title ${post.title_css}">${post.title_text}</span>` : '';
        const vipHtml = post.vip_level && post.vip_level > 0 ? 
            `<span class="vip-label">👑VIP${post.vip_level}</span>` : '';
        
        // NSFW画像処理
        let mediaHtml = '';
        if (post.media_path) {
            const isNsfw = post.is_nsfw == 1 || post.is_nsfw === true;
            const nsfwClass = isNsfw ? 'nsfw-blur' : '';
            const mediaExt = post.media_path.split('.').pop().toLowerCase();
            const isVideo = ['mp4', 'webm'].includes(mediaExt);
            
            if (isVideo) {
                mediaHtml = `<div class="${nsfwClass}" onclick="if(this.classList.contains('nsfw-blur')){this.classList.add('revealed');}"><video src="${post.media_path}" controls style="max-width: 100%; border-radius: 6px; margin-top: 10px;"></video></div>`;
            } else {
                mediaHtml = `<div class="${nsfwClass}" onclick="if(this.classList.contains('nsfw-blur')){this.classList.add('revealed');}"><img src="${post.media_path}" style="max-width: 100%; border-radius: 6px; margin-top: 10px;"></div>`;
            }
        }
        
        return `
        <div class="community-post ${frameClass}" data-post-id="${post.id}">
            <div class="post-header">
                <img src="${icon}" alt="${displayName}" class="avatar" style="width: 40px; height: 40px; border-radius: 50%; margin-right: 10px;">
                <div>
                    <div>
                        <a href="profile.php?id=${post.user_id}" class="post-author">${displayName}</a>
                        @${post.handle}
                        ${titleHtml}
                        ${vipHtml}
                    </div>
                    <span class="post-time">${formatTime(post.created_at)}</span>
                </div>
            </div>
            <div class="post-content">${escapeHtml(post.content)}</div>
            ${mediaHtml}
            <div class="post-actions">
                <button class="post-action-btn ${post.user_liked ? 'liked' : ''}" onclick="toggleLike(${post.id})">
                    ❤️ <span class="like-count">${post.like_count || 0}</span>
                </button>
                <button class="post-action-btn" onclick="location.href='community_replies.php?post_id=${post.id}'">
                    💬 返信 <span class="reply-count">${post.reply_count || 0}</span>
                </button>
            </div>
        </div>
    `;
    }).join('');
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

// コミュニティ編集モーダル表示
function showEditCommunity() {
    document.getElementById('editCommunityModal').style.display = 'flex';
}

// コミュニティ編集モーダル閉じる
function closeEditCommunity() {
    document.getElementById('editCommunityModal').style.display = 'none';
}

// コミュニティ編集フォーム送信
document.getElementById('editCommunityForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData();
    formData.append('action', 'update_community');
    formData.append('community_id', COMMUNITY_ID);
    formData.append('name', form.name.value);
    formData.append('description', form.description.value);
    
    try {
        const res = await fetch('community_api.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.ok) {
            alert('コミュニティ情報を更新しました');
            location.reload();
        } else {
            alert('更新エラー: ' + data.error);
        }
    } catch (err) {
        alert('ネットワークエラー');
    }
});

// 初回読み込み
loadPosts();

// 3秒ごとに自動更新
setInterval(loadPosts, 3000);

// コミュニティ削除
async function deleteCommunity() {
    if (!confirm('本当にこのコミュニティを削除しますか？\nこの操作は取り消せません。')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete_community');
    formData.append('community_id', COMMUNITY_ID);
    
    try {
        const res = await fetch('community_api.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.ok) {
            alert('コミュニティを削除しました');
            location.href = 'communities.php';
        } else {
            alert('削除エラー: ' + data.error);
        }
    } catch (err) {
        alert('ネットワークエラー');
    }
}

// コミュニティ脱退
async function leaveCommunity() {
    if (!confirm('このコミュニティから脱退しますか？')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'leave_community');
    formData.append('community_id', COMMUNITY_ID);
    
    try {
        const res = await fetch('community_api.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.ok) {
            alert('コミュニティから脱退しました');
            location.href = 'communities.php';
        } else {
            alert('脱退エラー: ' + data.error);
        }
    } catch (err) {
        alert('ネットワークエラー');
    }
}
</script>
</body>
</html>
