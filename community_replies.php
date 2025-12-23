<?php
// ===============================================
// community_replies.php
// コミュニティ投稿の返信ページ
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
$post_id = intval($_GET['post_id'] ?? 0);

if (!$post_id) {
    echo "投稿IDが指定されていません";
    exit;
}

// 投稿情報とコミュニティ情報を取得
$stmt = $pdo->prepare("
    SELECT 
        cp.*,
        u.handle,
        u.display_name,
        u.icon,
        u.vip_level,
        u.role,
        f.css_token AS frame_class,
        tp.title_text,
        tp.title_css,
        c.id as community_id,
        c.name as community_name
    FROM community_posts cp
    JOIN users u ON u.id = cp.user_id
    JOIN communities c ON c.id = cp.community_id
    LEFT JOIN frames f ON f.id = u.active_frame_id
    LEFT JOIN user_titles ut ON ut.user_id = u.id AND ut.is_equipped = TRUE
    LEFT JOIN title_packages tp ON tp.id = ut.title_id
    WHERE cp.id = ?
");
$stmt->execute([$post_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    echo "投稿が見つかりません";
    exit;
}

// コミュニティメンバーチェック
$stmt = $pdo->prepare("SELECT role FROM community_members WHERE community_id=? AND user_id=?");
$stmt->execute([$post['community_id'], $me['id']]);
$member = $stmt->fetch();

if (!$member) {
    echo "このコミュニティのメンバーではありません";
    exit;
}
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>返信 - <?= htmlspecialchars($post['community_name']) ?> - MiniBird</title>
<link rel="stylesheet" href="assets/style.css?v=<?= ASSETS_VERSION ?>">
<style>
.community-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
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
.btn-secondary {
    background: #cbd5e0;
    color: #2d3748;
}
.community-post {
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
    text-decoration: none;
}
.post-author:hover {
    text-decoration: underline;
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
.reply-form {
    margin: 20px 0;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 15px;
}
.reply-form textarea {
    width: 100%;
    min-height: 80px;
    padding: 10px;
    border: 1px solid #cbd5e0;
    border-radius: 6px;
    resize: vertical;
    font-family: inherit;
}
.reply-form button {
    margin-top: 10px;
    padding: 8px 20px;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
}
.vip-label {
    background: linear-gradient(135deg, #ffd700 0%, #ff8c00 100%);
    color: white;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: bold;
}
.user-title {
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 11px;
    margin-left: 5px;
}
.avatar {
    border-radius: 50%;
}
</style>
</head>
<body>
<div class="container" style="max-width: 800px; margin: 0 auto; padding: 20px;">
    <div class="community-header">
        <h2><?= htmlspecialchars($post['community_name']) ?></h2>
    </div>
    
    <div class="community-actions">
        <button class="btn-secondary" onclick="location.href='community_feed.php?id=<?= $post['community_id'] ?>'">← フィードに戻る</button>
    </div>
    
    <!-- 元の投稿 -->
    <div class="community-post <?= htmlspecialchars($post['frame_class'] ?? '') ?>" id="original-post">
        <div class="post-header">
            <img src="<?= htmlspecialchars($post['icon'] ?: '/uploads/icons/default_icon.png') ?>" 
                 alt="<?= htmlspecialchars($post['display_name'] ?: $post['handle']) ?>" 
                 class="avatar" style="width: 40px; height: 40px;">
            <div>
                <div>
                    <a href="profile.php?id=<?= $post['user_id'] ?>" class="post-author">
                        <?= htmlspecialchars($post['display_name'] ?: $post['handle']) ?>
                    </a>
                    @<?= htmlspecialchars($post['handle']) ?>
                    <?php if ($post['role'] === 'admin'): ?>
                        <span class="role-badge admin-badge">ADMIN</span>
                    <?php elseif ($post['role'] === 'mod'): ?>
                        <span class="role-badge mod-badge">MOD</span>
                    <?php endif; ?>
                    <?php if ($post['title_text'] && $post['title_css']): ?>
                        <span class="user-title <?= htmlspecialchars($post['title_css']) ?>">
                            <?= htmlspecialchars($post['title_text']) ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($post['vip_level'] && $post['vip_level'] > 0): ?>
                        <span class="vip-label">👑VIP<?= $post['vip_level'] ?></span>
                    <?php endif; ?>
                </div>
                <span class="post-time"><?= htmlspecialchars($post['created_at']) ?></span>
            </div>
        </div>
        <div class="post-content" id="original-post-content" data-raw-content="<?= htmlspecialchars($post['content'], ENT_QUOTES) ?>">
            <?php if ($post['is_deleted'] || $post['deleted_at']): ?>
                <p style="color: #999; font-style: italic;">この投稿は削除されました</p>
            <?php else: ?>
                <?= nl2br(htmlspecialchars($post['content'])) ?>
            <?php endif; ?>
        </div>
        <?php if ($post['media_path']): ?>
            <img src="<?= htmlspecialchars($post['media_path']) ?>" style="max-width: 100%; border-radius: 6px; margin-top: 10px;">
        <?php endif; ?>
    </div>
    
    <!-- 返信フォーム -->
    <div class="reply-form">
        <h3>返信を投稿</h3>
        <form id="replyForm">
            <textarea name="content" placeholder="返信を入力..." required></textarea>
            <button type="submit">返信する</button>
        </form>
    </div>
    
    <!-- 返信一覧 -->
    <h3>返信 (<span id="reply-count">0</span>)</h3>
    <div id="replies"></div>
</div>

<script>
const POST_ID = <?= $post_id ?>;
const COMMUNITY_ID = <?= $post['community_id'] ?>;
const USER_ID = <?= $me['id'] ?>;

// 返信フォーム送信
document.getElementById('replyForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData();
    formData.append('action', 'create_post');
    formData.append('community_id', COMMUNITY_ID);
    formData.append('content', form.content.value);
    formData.append('parent_id', POST_ID);
    formData.append('is_nsfw', '0');
    
    try {
        const res = await fetch('community_api.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.ok) {
            form.content.value = '';
            loadReplies();
        } else {
            alert('返信エラー: ' + data.error);
        }
    } catch (err) {
        alert('ネットワークエラー');
    }
});

// 返信読み込み
async function loadReplies() {
    try {
        const res = await fetch(`community_api.php?action=get_replies&post_id=${POST_ID}`);
        const data = await res.json();
        
        if (data.ok) {
            document.getElementById('reply-count').textContent = data.replies.length;
            const container = document.getElementById('replies');
            
            // 既存のYouTube iframeを保存（URL別に保存して再利用）
            const existingYouTubeIframes = {};
            container.querySelectorAll('.community-post').forEach(replyEl => {
                const replyId = replyEl.dataset.replyId;
                const youtubeIframes = replyEl.querySelectorAll('.youtube-embed');
                youtubeIframes.forEach(iframe => {
                    const src = iframe.getAttribute('src');
                    if (src && replyId) {
                        const key = `${replyId}-${src}`;
                        existingYouTubeIframes[key] = iframe.cloneNode(true);
                    }
                });
            });
            
            container.innerHTML = data.replies.map(reply => {
                const displayName = reply.display_name || reply.handle || 'unknown';
                const icon = reply.icon || '/uploads/icons/default_icon.png';
                const frameClass = reply.frame_class || '';
                const titleHtml = reply.title_text && reply.title_css ? 
                    `<span class="user-title ${reply.title_css}">${reply.title_text}</span>` : '';
                const vipHtml = reply.vip_level && reply.vip_level > 0 ? 
                    `<span class="vip-label">👑VIP${reply.vip_level}</span>` : '';
                
                // Role badge display
                let roleBadgeHtml = '';
                if (reply.role === 'admin') {
                    roleBadgeHtml = '<span class="role-badge admin-badge">ADMIN</span>';
                } else if (reply.role === 'mod') {
                    roleBadgeHtml = '<span class="role-badge mod-badge">MOD</span>';
                }
                
                // 削除済み投稿の処理
                const contentHtml = (reply.is_deleted || reply.deleted_at) ?
                    '<p style="color: #999; font-style: italic;">この投稿は削除されました</p>' :
                    escapeHtml(reply.content, false);
                
                // 削除ボタン（自分の投稿のみ表示）
                const deleteBtn = reply.user_id === USER_ID ?
                    `<button class="post-action-btn" onclick="deletePost(${reply.id})" style="color: #e53e3e;">
                        🗑️ 削除
                    </button>` : '';
                
                return `
                <div class="community-post ${frameClass}" data-reply-id="${reply.id}">
                    <div class="post-header">
                        <img src="${icon}" alt="${displayName}" class="avatar" style="width: 32px; height: 32px;">
                        <div>
                            <div>
                                <a href="profile.php?id=${reply.user_id}" class="post-author">${displayName}</a>
                                @${reply.handle}
                                ${roleBadgeHtml}
                                ${titleHtml}
                                ${vipHtml}
                            </div>
                            <span class="post-time">${formatTime(reply.created_at)}</span>
                        </div>
                    </div>
                    <div class="post-content">${contentHtml}</div>
                    <div class="post-actions">
                        <button class="post-action-btn ${reply.user_liked ? 'liked' : ''}" onclick="toggleLike(${reply.id})">
                            ❤️ <span class="like-count">${reply.like_count || 0}</span>
                        </button>
                        ${deleteBtn}
                    </div>
                </div>
            `;
            }).join('');
            
            // YouTube埋め込みを処理（既存のiframeを再利用）
            container.querySelectorAll('.community-post').forEach(replyEl => {
                const replyId = replyEl.dataset.replyId;
                const replyContent = replyEl.querySelector('.post-content');
                if (replyContent && !replyContent.dataset.youtubeProcessed) {
                    processYouTubeEmbeds(replyContent, replyId, existingYouTubeIframes);
                    replyContent.dataset.youtubeProcessed = 'true';
                }
            });
        }
    } catch (err) {
        console.error('返信読み込みエラー', err);
    }
}

// YouTube埋め込み処理（既存のiframeを再利用）
function processYouTubeEmbeds(contentElement, itemId, existingIframes) {
    const html = contentElement.innerHTML;
    const youtubePattern = /(https?:\/\/(?:www\.|m\.)?(?:youtube\.com\/watch\?[^\s<]*v=|youtube\.com\/embed\/|youtu\.be\/)([a-zA-Z0-9_-]{11})(?:[^\s<]*))/g;
    
    let match;
    const replacements = [];
    
    while ((match = youtubePattern.exec(html)) !== null) {
        const fullUrl = match[0];
        const videoId = match[2];
        const embedSrc = `https://www.youtube.com/embed/${videoId}`;
        const key = `${itemId}-${embedSrc}`;
        
        replacements.push({
            fullUrl: fullUrl,
            videoId: videoId,
            embedSrc: embedSrc,
            hasExisting: existingIframes && existingIframes[key]
        });
    }
    
    if (replacements.length > 0) {
        let newHtml = html;
        replacements.forEach(rep => {
            newHtml = newHtml.replace(rep.fullUrl, `<div class="youtube-placeholder-${rep.videoId}"></div>`);
        });
        
        contentElement.innerHTML = newHtml;
        
        // プレースホルダーを実際のiframeで置き換え
        replacements.forEach(rep => {
            const placeholder = contentElement.querySelector(`.youtube-placeholder-${rep.videoId}`);
            if (placeholder) {
                const wrapper = document.createElement('div');
                wrapper.className = 'youtube-embed-wrapper';
                
                const key = `${itemId}-${rep.embedSrc}`;
                const iframe = createYouTubeIframe(rep.embedSrc, existingIframes ? existingIframes[key] : null);
                wrapper.appendChild(iframe);
                
                placeholder.parentNode.replaceChild(wrapper, placeholder);
            }
        });
    }
}

// YouTube iframeを作成または既存のものを返す
function createYouTubeIframe(embedSrc, existingIframe) {
    if (existingIframe) {
        return existingIframe;
    }
    
    const iframe = document.createElement('iframe');
    iframe.className = 'youtube-embed';
    iframe.src = embedSrc;
    iframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture');
    iframe.setAttribute('allowfullscreen', 'true');
    return iframe;
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
            loadReplies();
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

function escapeHtml(text, embedYouTube = true) {
    const div = document.createElement('div');
    div.textContent = text;
    let html = div.innerHTML.replace(/\n/g, '<br>');
    
    // YouTube URL embedding は別途 processYouTubeEmbeds で処理するため、ここでは行わない
    // embedYouTubeパラメータは下位互換性のため残す
    
    return html;
}

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
            loadReplies();
        } else {
            alert('削除エラー: ' + data.error);
        }
    } catch (err) {
        alert('ネットワークエラー');
    }
}

// 初回読み込み
loadReplies();

// YouTube埋め込みを元の投稿に適用（初回のみ）
document.addEventListener('DOMContentLoaded', function() {
    const originalPostContent = document.getElementById('original-post-content');
    if (originalPostContent && !originalPostContent.dataset.youtubeProcessed) {
        const rawContent = originalPostContent.dataset.rawContent;
        if (rawContent) {
            originalPostContent.innerHTML = escapeHtml(rawContent);
            originalPostContent.dataset.youtubeProcessed = 'true';
        }
    }
});

// 3秒ごとに自動更新（返信のみ更新し、元の投稿は更新しない。変更がある場合のみレンダリング）
let lastRepliesData = '';
setInterval(async () => {
    try {
        const res = await fetch(`community_api.php?action=get_replies&post_id=${POST_ID}`);
        const data = await res.json();
        
        if (data.ok) {
            // より包括的なハッシュ値を使用（ID、いいね数、コンテンツ、削除状態を検知）
            const currentData = JSON.stringify(data.replies.map(r => ({
                id: r.id,
                like_count: r.like_count,
                is_deleted: r.is_deleted || r.deleted_at,
                content: r.content
            })));
            
            // 変更があった場合のみloadRepliesを実行
            if (currentData !== lastRepliesData) {
                await loadReplies();
                lastRepliesData = currentData;
            }
        }
    } catch (err) {
        console.error('自動更新エラー', err);
    }
}, 3000);
</script>
</body>
</html>
