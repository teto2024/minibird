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

// 元投稿取得（フレーム情報、称号情報、引用投稿も含む）
// リポストの場合は、元投稿の作者情報を表示する
$stmt = $pdo->prepare("
    SELECT p.*, 
           CASE WHEN p.is_repost_of IS NOT NULL THEN op_user.handle ELSE u.handle END as handle,
           CASE WHEN p.is_repost_of IS NOT NULL THEN op_user.display_name ELSE u.display_name END as display_name,
           CASE WHEN p.is_repost_of IS NOT NULL THEN op_user.icon ELSE u.icon END as icon,
           CASE WHEN p.is_repost_of IS NOT NULL THEN op_user.active_frame_id ELSE u.active_frame_id END as active_frame_id,
           CASE WHEN p.is_repost_of IS NOT NULL THEN op_user.vip_level ELSE u.vip_level END as vip_level,
           CASE WHEN p.is_repost_of IS NOT NULL THEN op_user.role ELSE u.role END as role,
           CASE WHEN p.is_repost_of IS NOT NULL THEN f_op.css_token ELSE f.css_token END as frame_class,
           CASE WHEN p.is_repost_of IS NOT NULL THEN ut_op.title_id ELSE ut.title_id END as title_id,
           CASE WHEN p.is_repost_of IS NOT NULL THEN tp_op.title_text ELSE tp.title_text END as title_text,
           CASE WHEN p.is_repost_of IS NOT NULL THEN tp_op.title_css ELSE tp.title_css END as title_css,
           (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
           (SELECT COUNT(*) FROM posts WHERE is_repost_of = p.id) as repost_count,
           (SELECT 1 FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked,
           p.media_path, p.media_type, p.media_paths, p.quote_post_id,
           qp.id as quoted_id, qp.user_id as quoted_user_id, qp.content_md as quoted_content_md,
           qp.content_html as quoted_content_html, qu.handle as quoted_handle, 
           qu.display_name as quoted_display_name, qu.icon as quoted_icon,
           u.handle as reposter_handle, u.display_name as reposter_display_name
    FROM posts p
    JOIN users u ON u.id = p.user_id
    LEFT JOIN posts op ON op.id = p.is_repost_of
    LEFT JOIN users op_user ON op_user.id = op.user_id
    LEFT JOIN frames f ON f.id = u.active_frame_id
    LEFT JOIN frames f_op ON f_op.id = op_user.active_frame_id
    LEFT JOIN user_titles ut ON ut.user_id = u.id AND ut.is_equipped = TRUE
    LEFT JOIN title_packages tp ON tp.id = ut.title_id
    LEFT JOIN user_titles ut_op ON ut_op.user_id = op_user.id AND ut_op.is_equipped = TRUE
    LEFT JOIN title_packages tp_op ON tp_op.id = ut_op.title_id
    LEFT JOIN posts qp ON qp.id = p.quote_post_id
    LEFT JOIN users qu ON qu.id = qp.user_id
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
<link rel="stylesheet" href="assets/style.css?v=<?= ASSETS_VERSION ?>">
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<style>
/* グローバルstyle.cssからフレームスタイルを適用するため、追加のカスタマイズのみ記述 */
.replies-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}
.original-post {
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

/* フレームスタイルはassets/style.cssから継承されます */
</style>
</head>
<body>
<header class="topbar">
    <div class="logo"><a href="index.php">← フィードに戻る</a></div>
</header>

<div class="replies-container">
    <!-- リポスト表示 -->
    <?php if (!empty($original_post['is_repost_of']) && !empty($original_post['reposter_handle'])): ?>
    <div style="padding: 10px 20px; background: #f7fafc; border-left: 3px solid #667eea; margin-bottom: 10px; border-radius: 8px;">
        <span style="color: #667eea; font-size: 14px;">
            🔁 <strong><?= htmlspecialchars($original_post['reposter_display_name'] ?? $original_post['reposter_handle']) ?></strong> がリポストしました
        </span>
    </div>
    <?php endif; ?>
    
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
                    <?php if (isset($original_post['role']) && $original_post['role'] === 'admin'): ?>
                        <span class="role-badge admin-badge">ADMIN</span>
                    <?php elseif (isset($original_post['role']) && $original_post['role'] === 'mod'): ?>
                        <span class="role-badge mod-badge">MOD</span>
                    <?php endif; ?>
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
            <?php if ($original_post['is_deleted'] || $original_post['deleted_at']): ?>
                <p style="color: #999; font-style: italic;">この投稿は削除されました</p>
            <?php else: ?>
                <?php if (!empty($original_post['quote_post_id']) && !empty($original_post['quoted_id'])): ?>
                    <!-- 引用投稿表示 -->
                    <div style="border: 2px solid #e2e8f0; border-radius: 8px; padding: 12px; margin-bottom: 15px; background: #f7fafc; cursor: pointer;" 
                         onclick="location.href='replies_enhanced.php?post_id=<?= (int)$original_post['quoted_id'] ?>'">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                            <img src="<?= htmlspecialchars($original_post['quoted_icon'] ?? '/uploads/icons/default_icon.png') ?>" 
                                 style="width: 30px; height: 30px; border-radius: 50%;">
                            <strong><?= htmlspecialchars($original_post['quoted_display_name'] ?? $original_post['quoted_handle'] ?? 'unknown') ?></strong>
                            <span style="color: #a0aec0;">@<?= htmlspecialchars($original_post['quoted_handle'] ?? 'unknown') ?></span>
                        </div>
                        <div style="color: #4a5568;">
                            <?= nl2br(htmlspecialchars(mb_substr($original_post['quoted_content_md'] ?? '', 0, 200))) ?>
                            <?php if (mb_strlen($original_post['quoted_content_md'] ?? '') > 200): ?>...<?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?= $original_post['content_html'] ?>
                
                <?php 
                // Display media (images, videos, audio)
                $media_paths = [];
                if (!empty($original_post['media_paths'])) {
                    $decoded = json_decode($original_post['media_paths'], true);
                    if (is_array($decoded)) {
                        $media_paths = $decoded;
                    }
                } elseif (!empty($original_post['media_path'])) {
                    $media_paths = [$original_post['media_path']];
                }
                
                if (!empty($media_paths)):
                ?>
                <div class="media-wrapper" style="margin-top: 15px;">
                    <?php if (count($media_paths) > 1): ?>
                    <div class="media-grid" style="display: grid; gap: 10px; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                        <?php foreach ($media_paths as $index => $mediaPath): 
                            if ($index >= 4) break; // 最大4枚まで
                            $ext = strtolower(pathinfo($mediaPath, PATHINFO_EXTENSION));
                            $mediaSrc = '/' . ltrim($mediaPath, '/');
                            $imageExts = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp', 'svg', 'ico', 'avif', 'heic', 'heif'];
                            $videoExts = ['mp4', 'webm', 'mov', 'avi', 'mkv', 'm4v', 'flv', 'wmv', 'ogv', 'ogg'];
                            $audioExts = ['mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac', 'wma', 'opus'];
                            $documentExts = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip', 'rar', '7z', 'tar', 'gz'];
                        ?>
                        <div class="media-item">
                            <?php if (in_array($ext, $imageExts)): ?>
                                <img src="<?= htmlspecialchars($mediaSrc) ?>" style="max-width: 100%; border-radius: 8px; cursor: pointer;" onclick="openMediaExpand('<?= htmlspecialchars($mediaSrc) ?>', 'image')">
                            <?php elseif (in_array($ext, $videoExts)): ?>
                                <video src="<?= htmlspecialchars($mediaSrc) ?>" controls style="max-width: 100%; border-radius: 8px; cursor: pointer;" onclick="openMediaExpand('<?= htmlspecialchars($mediaSrc) ?>', 'video')"></video>
                            <?php elseif (in_array($ext, $audioExts)): ?>
                                <audio src="<?= htmlspecialchars($mediaSrc) ?>" controls style="width: 100%;"></audio>
                            <?php elseif (in_array($ext, $documentExts)): ?>
                                <?php $fileName = basename($mediaPath); ?>
                                <a href="<?= htmlspecialchars($mediaSrc) ?>" download="<?= htmlspecialchars($fileName) ?>" target="_blank" class="document-link">📄 <?= htmlspecialchars($fileName) ?></a>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <?php 
                        $mediaPath = $media_paths[0];
                        $ext = strtolower(pathinfo($mediaPath, PATHINFO_EXTENSION));
                        $mediaSrc = '/' . ltrim($mediaPath, '/');
                        $imageExts = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp', 'svg', 'ico', 'avif', 'heic', 'heif'];
                        $videoExts = ['mp4', 'webm', 'mov', 'avi', 'mkv', 'm4v', 'flv', 'wmv', 'ogv', 'ogg'];
                        $audioExts = ['mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac', 'wma', 'opus'];
                        $documentExts = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip', 'rar', '7z', 'tar', 'gz'];
                    ?>
                    <div class="media-single">
                        <?php if (in_array($ext, $imageExts)): ?>
                            <img src="<?= htmlspecialchars($mediaSrc) ?>" style="max-width: 100%; border-radius: 8px; cursor: pointer;" onclick="openMediaExpand('<?= htmlspecialchars($mediaSrc) ?>', 'image')">
                        <?php elseif (in_array($ext, $videoExts)): ?>
                            <video src="<?= htmlspecialchars($mediaSrc) ?>" controls style="max-width: 100%; border-radius: 8px; cursor: pointer;" onclick="openMediaExpand('<?= htmlspecialchars($mediaSrc) ?>', 'video')"></video>
                        <?php elseif (in_array($ext, $audioExts)): ?>
                            <audio src="<?= htmlspecialchars($mediaSrc) ?>" controls style="width: 100%;"></audio>
                        <?php elseif (in_array($ext, $documentExts)): ?>
                            <?php $fileName = basename($mediaPath); ?>
                            <a href="<?= htmlspecialchars($mediaSrc) ?>" download="<?= htmlspecialchars($fileName) ?>" target="_blank" class="document-link">📄 <?= htmlspecialchars($fileName) ?></a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
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
    
    const deleteBtn = reply._can_delete ? 
        `<button class="reply-action-btn" onclick="deleteReply(${reply.id})" style="color: #e53e3e;">
            🗑️ 削除
        </button>` : '';
    
    // Media handling
    let mediaHtml = '';
    const media_paths = reply.media_paths || (reply.media_path ? [reply.media_path] : []);
    
    if (media_paths.length > 0) {
        mediaHtml = '<div class="media-wrapper" style="margin-top: 10px;">';
        
        if (media_paths.length > 1) {
            mediaHtml += '<div class="media-grid" style="display: grid; gap: 10px; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));">';
            media_paths.slice(0, 4).forEach(mediaPath => {
                const ext = mediaPath.split('.').pop().toLowerCase();
                const mediaSrc = '/' + mediaPath.replace(/^\//, '');
                const imageExts = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp', 'svg', 'ico', 'avif', 'heic', 'heif'];
                const videoExts = ['mp4', 'webm', 'mov', 'avi', 'mkv', 'm4v', 'flv', 'wmv', 'ogv', 'ogg'];
                const audioExts = ['mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac', 'wma', 'opus'];
                const documentExts = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip', 'rar', '7z', 'tar', 'gz'];
                
                if (imageExts.includes(ext)) {
                    mediaHtml += `<img src="${mediaSrc}" style="max-width: 100%; border-radius: 6px; cursor: pointer;" onclick="openMediaExpand('${mediaSrc}', 'image')">`;
                } else if (videoExts.includes(ext)) {
                    mediaHtml += `<video src="${mediaSrc}" controls style="max-width: 100%; border-radius: 6px; cursor: pointer;" onclick="openMediaExpand('${mediaSrc}', 'video')"></video>`;
                } else if (audioExts.includes(ext)) {
                    mediaHtml += `<audio src="${mediaSrc}" controls style="width: 100%;"></audio>`;
                } else if (documentExts.includes(ext)) {
                    const fileName = mediaPath.split('/').pop();
                    mediaHtml += `<a href="${mediaSrc}" download="${fileName}" target="_blank" class="document-link">📄 ${fileName}</a>`;
                }
            });
            mediaHtml += '</div>';
        } else {
            const mediaPath = media_paths[0];
            const ext = mediaPath.split('.').pop().toLowerCase();
            const mediaSrc = '/' + mediaPath.replace(/^\//, '');
            const imageExts = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp', 'svg', 'ico', 'avif', 'heic', 'heif'];
            const videoExts = ['mp4', 'webm', 'mov', 'avi', 'mkv', 'm4v', 'flv', 'wmv', 'ogv', 'ogg'];
            const audioExts = ['mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac', 'wma', 'opus'];
            const documentExts = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip', 'rar', '7z', 'tar', 'gz'];
            
            if (imageExts.includes(ext)) {
                mediaHtml += `<img src="${mediaSrc}" style="max-width: 100%; border-radius: 6px; cursor: pointer;" onclick="openMediaExpand('${mediaSrc}', 'image')">`;
            } else if (videoExts.includes(ext)) {
                mediaHtml += `<video src="${mediaSrc}" controls style="max-width: 100%; border-radius: 6px; cursor: pointer;" onclick="openMediaExpand('${mediaSrc}', 'video')"></video>`;
            } else if (audioExts.includes(ext)) {
                mediaHtml += `<audio src="${mediaSrc}" controls style="width: 100%;"></audio>`;
            } else if (documentExts.includes(ext)) {
                const fileName = mediaPath.split('/').pop();
                mediaHtml += `<a href="${mediaSrc}" download="${fileName}" target="_blank" class="document-link">📄 ${fileName}</a>`;
            }
        }
        
        mediaHtml += '</div>';
    }
    
    return `
        <div class="reply-item ${frameClass}" data-reply-id="${reply.id}">
            <div class="reply-header">
                <img src="${reply.icon || '/uploads/icons/default_icon.png'}" 
                     alt="${reply.display_name || reply.handle}" 
                     class="reply-avatar">
                <div class="reply-meta">
                    <div>
                        <span class="reply-author">${reply.display_name || reply.handle}</span>
                        ${reply.role === 'admin' ? '<span class="role-badge admin-badge">ADMIN</span>' : ''}
                        ${reply.role === 'mod' ? '<span class="role-badge mod-badge">MOD</span>' : ''}
                        ${titleHtml}
                    </div>
                    <div class="reply-time">
                        @${reply.handle} · ${formatTime(reply.created_at)}
                    </div>
                </div>
            </div>
            <div class="reply-content">
                ${marked.parse(reply.content_md || reply.content_html)}
                ${mediaHtml}
            </div>
            <div class="reply-actions">
                <button class="reply-action-btn ${reply.user_liked ? 'liked' : ''}" 
                        onclick="toggleLike(${reply.id}, this)">
                    ❤️ <span class="like-count">${reply.like_count || 0}</span>
                </button>
                <button class="reply-action-btn" onclick="replyTo(${reply.id})">
                    💬 返信
                </button>
                ${deleteBtn}
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

// 返信削除
async function deleteReply(replyId) {
    if (!confirm('この返信を削除しますか？')) return;
    
    try {
        const res = await fetch('replies_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'delete', reply_id: replyId})
        });
        const data = await res.json();
        
        if (data.ok) {
            loadReplies();
        } else {
            alert('削除失敗: ' + (data.error || '不明なエラー'));
        }
    } catch (err) {
        console.error('削除エラー', err);
        alert('ネットワークエラー');
    }
}

// 初回読み込み
loadReplies();

// 3秒ごとに自動更新
setInterval(loadReplies, 3000);

// Media Expand Modal Functions
function openMediaExpand(mediaSrc, mediaType) {
    const modal = document.getElementById('mediaExpandModal');
    const content = document.getElementById('mediaExpandContent');
    
    content.innerHTML = '';
    
    let mediaEl;
    if (mediaType === 'image') {
        mediaEl = document.createElement('img');
    } else if (mediaType === 'video') {
        mediaEl = document.createElement('video');
        mediaEl.controls = true;
        mediaEl.autoplay = true;
    } else if (mediaType === 'audio') {
        mediaEl = document.createElement('audio');
        mediaEl.controls = true;
        mediaEl.autoplay = true;
    }
    
    if (mediaEl) {
        mediaEl.src = mediaSrc;
        mediaEl.onclick = (e) => e.stopPropagation();
        content.appendChild(mediaEl);
        modal.classList.add('active');
    }
}

function closeMediaExpand() {
    const modal = document.getElementById('mediaExpandModal');
    modal.classList.remove('active');
    document.getElementById('mediaExpandContent').innerHTML = '';
}

// Close on ESC key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        const modal = document.getElementById('mediaExpandModal');
        if (modal.classList.contains('active')) {
            closeMediaExpand();
        }
    }
});
</script>

<!-- Media Expand Modal -->
<div id="mediaExpandModal" class="media-expand-modal" onclick="closeMediaExpand()">
  <span class="media-expand-close" onclick="closeMediaExpand()">&times;</span>
  <div id="mediaExpandContent"></div>
</div>

</body>
</html>
