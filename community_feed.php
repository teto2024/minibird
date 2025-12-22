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
<link rel="stylesheet" href="assets/style.css?v=<?= ASSETS_VERSION ?>">
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
    display: inline-block;
    min-height: 100px;
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
    white-space: nowrap;
    z-index: 10;
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
                <input type="file" name="media" accept="image/*,video/*,audio/*" multiple
                       style="padding: 5px; border: 1px solid #cbd5e0; border-radius: 6px;">
                <small style="color: #999; margin-left: 10px;">最大4ファイルまで（画像・動画・音声対応）</small>
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
    
    // ファイルがある場合は追加（複数ファイル対応）
    if (form.media.files.length > 0) {
        // 最大4ファイルまで
        for (let i = 0; i < Math.min(form.media.files.length, 4); i++) {
            formData.append(`media_${i}`, form.media.files[i]);
        }
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
            if (data.error === 'muted') {
                const remainingTime = data.remaining_time || '不明';
                // Format muted_until to a readable Japanese format
                let mutedUntil = '不明';
                if (data.muted_until) {
                    try {
                        const date = new Date(data.muted_until);
                        mutedUntil = date.toLocaleString('ja-JP', {
                            year: 'numeric',
                            month: '2-digit',
                            day: '2-digit',
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                    } catch (e) {
                        mutedUntil = data.muted_until;
                    }
                }
                showMutePopup(remainingTime, mutedUntil);
            } else {
                alert('投稿エラー: ' + data.error);
            }
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
        // 削除済み投稿の処理
        if (post.is_deleted || post.deleted_at) {
            return `
            <div class="community-post" data-post-id="${post.id}" style="opacity: 0.6;">
                <div class="post-content" style="color: #999; font-style: italic;">この投稿は削除されました</div>
            </div>
            `;
        }
        
        const displayName = post.display_name || post.handle || 'unknown';
        const icon = post.icon || '/uploads/icons/default_icon.png';
        const frameClass = post.frame_class || '';
        const titleHtml = post.title_text && post.title_css ? 
            `<span class="user-title ${post.title_css}">${post.title_text}</span>` : '';
        const vipHtml = post.vip_level && post.vip_level > 0 ? 
            `<span class="vip-label">👑VIP${post.vip_level}</span>` : '';
        
        // NSFW画像処理（複数メディア対応）
        let mediaHtml = '';
        const media_paths = post.media_paths || (post.media_path ? [post.media_path] : []);
        
        if (media_paths.length > 0) {
            const isNsfw = post.is_nsfw == 1 || post.is_nsfw === true || post.is_nsfw === '1';
            const imageExts = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp', 'svg', 'ico', 'avif', 'heic', 'heif'];
            const videoExts = ['mp4', 'webm', 'mov', 'avi', 'mkv', 'm4v', 'flv', 'wmv', 'ogv', 'ogg'];
            const audioExts = ['mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac', 'wma', 'opus'];
            
            if (media_paths.length > 1) {
                // 複数メディア（グリッド表示）
                mediaHtml = '<div style="display: grid; gap: 10px; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); margin-top: 10px;">';
                media_paths.slice(0, 4).forEach(mediaPath => {
                    const ext = mediaPath.split('.').pop().toLowerCase();
                    const mediaSrc = '/' + mediaPath.replace(/^\//, '');
                    
                    if (imageExts.includes(ext)) {
                        if (isNsfw) {
                            mediaHtml += `<div class="nsfw-blur" onclick="this.classList.remove('nsfw-blur'); this.querySelector('img').style.filter='none';"><img src="${mediaSrc}" style="max-width: 100%; border-radius: 6px; filter: blur(20px);"></div>`;
                        } else {
                            mediaHtml += `<img src="${mediaSrc}" style="max-width: 100%; border-radius: 6px;">`;
                        }
                    } else if (videoExts.includes(ext)) {
                        if (isNsfw) {
                            mediaHtml += `<div class="nsfw-blur" onclick="this.classList.remove('nsfw-blur'); this.querySelector('video').style.filter='none';"><video src="${mediaSrc}" controls style="max-width: 100%; border-radius: 6px; filter: blur(20px);"></video></div>`;
                        } else {
                            mediaHtml += `<video src="${mediaSrc}" controls style="max-width: 100%; border-radius: 6px;"></video>`;
                        }
                    } else if (audioExts.includes(ext)) {
                        mediaHtml += `<audio src="${mediaSrc}" controls style="width: 100%;"></audio>`;
                    }
                });
                mediaHtml += '</div>';
            } else {
                // 単一メディア
                const mediaPath = media_paths[0];
                const ext = mediaPath.split('.').pop().toLowerCase();
                const mediaSrc = '/' + mediaPath.replace(/^\//, '');
                
                if (imageExts.includes(ext)) {
                    if (isNsfw) {
                        mediaHtml = `<div class="nsfw-blur" onclick="this.classList.remove('nsfw-blur'); this.querySelector('img').style.filter='none';"><img src="${mediaSrc}" style="max-width: 100%; border-radius: 6px; margin-top: 10px; filter: blur(20px);"></div>`;
                    } else {
                        mediaHtml = `<img src="${mediaSrc}" style="max-width: 100%; border-radius: 6px; margin-top: 10px;">`;
                    }
                } else if (videoExts.includes(ext)) {
                    if (isNsfw) {
                        mediaHtml = `<div class="nsfw-blur" onclick="this.classList.remove('nsfw-blur'); this.querySelector('video').style.filter='none';"><video src="${mediaSrc}" controls style="max-width: 100%; border-radius: 6px; margin-top: 10px; filter: blur(20px);"></video></div>`;
                    } else {
                        mediaHtml = `<video src="${mediaSrc}" controls style="max-width: 100%; border-radius: 6px; margin-top: 10px;"></video>`;
                    }
                } else if (audioExts.includes(ext)) {
                    mediaHtml = `<audio src="${mediaSrc}" controls style="width: 100%; margin-top: 10px;"></audio>`;
                }
            }
        }
        
        // 削除ボタン（自分の投稿のみ表示）
        const deleteBtn = post.user_id === USER_ID ? 
            `<button class="post-action-btn" onclick="deletePost(${post.id})" style="color: #e53e3e;">
                🗑️ 削除
            </button>` : '';
        
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
                ${deleteBtn}
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

// 投稿削除
async function deletePost(postId) {
    if (!confirm('この投稿を削除しますか？\n削除した投稿は「削除されました」と表示されます。')) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'delete_post');
        formData.append('post_id', postId);
        
        const res = await fetch('community_api.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.ok) {
            alert('投稿を削除しました');
            loadPosts();
        } else {
            alert('削除エラー: ' + data.error);
        }
    } catch (err) {
        alert('ネットワークエラー');
    }
}

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

// ミュートポップアップを表示
function showMutePopup(remainingTime, mutedUntil) {
    const dialog = document.createElement('div');
    dialog.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); display: flex; align-items: center; justify-content: center; z-index: 10000;';
    dialog.innerHTML = `
        <div style="background: var(--card); border-radius: 12px; padding: 40px; max-width: 500px; width: 90%; box-shadow: 0 4px 20px rgba(0,0,0,0.5); border: 2px solid #f56565;">
            <div style="text-align: center; margin-bottom: 30px;">
                <div style="font-size: 60px; margin-bottom: 10px;">🚫</div>
                <h2 style="margin: 0 0 10px 0; color: #f56565; font-size: 24px;">あなたは投稿を制限されています</h2>
                <p style="color: var(--muted); margin: 5px 0;">投稿が一時的に制限されています</p>
            </div>
            
            <div style="background: var(--bg); border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                <div style="margin-bottom: 15px;">
                    <strong style="color: var(--text);">残りミュート時間:</strong>
                    <div style="font-size: 28px; font-weight: bold; color: #f56565; margin-top: 5px;">${remainingTime}</div>
                </div>
                <div>
                    <strong style="color: var(--text);">制限解除予定:</strong>
                    <div style="color: var(--muted); margin-top: 5px;">${mutedUntil}</div>
                </div>
            </div>
            
            <div style="text-align: center; margin-bottom: 20px;">
                <p style="color: var(--text); margin: 10px 0;">この制限に異議がある場合は、異議申し立てを行うことができます</p>
            </div>
            
            <div style="display: flex; gap: 10px; justify-content: center;">
                <button id="muteClose" style="padding: 12px 24px; border: 1px solid var(--border); border-radius: 6px; background: var(--bg); color: var(--text); cursor: pointer; font-weight: bold;">閉じる</button>
                <button id="appealBtn" style="padding: 12px 24px; border: none; border-radius: 6px; background: #4299e1; color: white; cursor: pointer; font-weight: bold;">異議申し立て</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(dialog);
    
    document.getElementById('muteClose').onclick = () => {
        document.body.removeChild(dialog);
    };
    
    document.getElementById('appealBtn').onclick = () => {
        document.body.removeChild(dialog);
        showAppealDialog();
    };
}

// 異議申し立てダイアログを表示
function showAppealDialog() {
    const dialog = document.createElement('div');
    dialog.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; z-index: 10000;';
    dialog.innerHTML = `
        <div style="background: var(--card); border-radius: 12px; padding: 30px; max-width: 600px; width: 90%; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
            <h3 style="margin: 0 0 20px 0; color: var(--text);">異議申し立て</h3>
            <p style="color: var(--text); margin-bottom: 20px;">ミュート措置に対する異議申し立ての理由を詳しく記入してください。管理者が審査します。</p>
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: bold; color: var(--text);">申し立て理由（必須）</label>
                <textarea id="appealReason" rows="6" style="width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 6px; background: var(--bg); color: var(--text); resize: vertical; font-family: inherit;" placeholder="なぜミュートが不当だと考えるのか、詳しく説明してください"></textarea>
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button id="appealCancel" style="padding: 10px 20px; border: 1px solid var(--border); border-radius: 6px; background: var(--bg); color: var(--text); cursor: pointer;">キャンセル</button>
                <button id="appealSubmit" style="padding: 10px 20px; border: none; border-radius: 6px; background: #4299e1; color: white; cursor: pointer; font-weight: bold;">申し立てる</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(dialog);
    
    document.getElementById('appealCancel').onclick = () => {
        document.body.removeChild(dialog);
    };
    
    document.getElementById('appealSubmit').onclick = async () => {
        const reason = document.getElementById('appealReason').value.trim();
        
        if (!reason) {
            alert('申し立て理由を入力してください');
            return;
        }
        
        try {
            const res = await fetch('appeal_api.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    action: 'submit_appeal',
                    reason: reason
                })
            });
            const r = await res.json();
            
            if (r.ok) {
                alert('異議申し立てを受け付けました。管理者が審査します。');
                document.body.removeChild(dialog);
            } else {
                alert('異議申し立てに失敗しました: ' + (r.message || r.error));
            }
        } catch (err) {
            alert('ネットワークエラー');
        }
    };
}
</script>
</body>
</html>
