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
    max-width: 100%;
    overflow: hidden;
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
    overflow: hidden;
    max-width: 100%;
}
.reply-content .youtube-embed-wrapper {
    max-width: 100%;
    margin: 12px 0;
    /* Constrain YouTube embeds in reply content to prevent overflow */
    position: relative;
    padding-bottom: 56.25%;
    height: 0;
    overflow: hidden;
    border-radius: 8px;
}
.reply-content .youtube-embed {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border-radius: 8px;
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

// YouTube helper functions
function extractYouTubeId(url) {
    const patterns = [
        /(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/,
        /youtube\.com\/watch\?.*v=([a-zA-Z0-9_-]{11})/,
        /(?:m\.youtube\.com\/watch\?v=)([a-zA-Z0-9_-]{11})/
    ];
    
    for (const pattern of patterns) {
        const match = url.match(pattern);
        if (match && match[1]) {
            return match[1];
        }
    }
    return null;
}

function embedYouTube(html) {
    // Replace YouTube URLs with embeds (supports www, m.youtube.com, and short URLs)
    return html.replace(/(https?:\/\/(?:www\.|m\.)?(?:youtube\.com\/watch\?[^\s<]*v=|youtu\.be\/)([a-zA-Z0-9_-]{11})(?:[^\s<]*))/g, function(match, fullUrl, videoId) {
        // Skip if videoId is null or undefined
        if (!videoId) return match;
        
        return `<div class="youtube-embed-wrapper">
            <iframe class="youtube-embed" 
                    src="https://www.youtube.com/embed/${videoId}" 
                    frameborder="0" 
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                    allowfullscreen>
            </iframe>
        </div>`;
    });
}

// ハッシュタグをリンク化
function parseHashtags(html) {
    // 既にリンク化されている部分を分離
    const parts = html.split(/(<a[^>]*>.*?<\/a>)/gi);
    const result = parts.map((part, i) => {
        // 偶数インデックスはリンク外、奇数はリンク内
        if (i % 2 === 0) {
            // ハッシュタグをリンク化（日本語、英数字、アンダースコアに対応）
            return part.replace(/#([a-zA-Z0-9_\u3040-\u309F\u30A0-\u30FF\u4E00-\u9FAF]+)/g, (match, tag) => {
                return `<a href="search.php?q=${encodeURIComponent('#' + tag)}" class="hashtag">#${tag}</a>`;
            });
        }
        return part;
    });
    return result.join('');
}

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
    
    // 既存のYouTube iframeを保存（URL別に保存して再利用）- 実際のDOMノードを保存
    const existingYouTubeIframes = {};
    container.querySelectorAll('.reply-item').forEach(replyEl => {
        const replyId = replyEl.dataset.replyId;
        const youtubeIframes = replyEl.querySelectorAll('.youtube-embed');
        youtubeIframes.forEach(iframe => {
            const src = iframe.getAttribute('src');
            if (src && replyId) {
                const key = `${replyId}-${src}`;
                // Save the actual DOM node instead of cloning to preserve playback state
                existingYouTubeIframes[key] = iframe;
            }
        });
    });
    
    // 親投稿のみをレンダリング（ネスト構造を後で実装）
    const topLevelReplies = replies.filter(r => !r.parent_id || r.parent_id === POST_ID);
    container.innerHTML = topLevelReplies.map(reply => renderReply(reply, false)).join('');
    
    // YouTube埋め込みを処理（既存のiframeを再利用）
    container.querySelectorAll('.reply-item').forEach(replyEl => {
        const replyId = replyEl.dataset.replyId;
        const replyContent = replyEl.querySelector('.reply-content');
        if (replyContent && !replyContent.dataset.youtubeProcessed) {
            processYouTubeEmbeds(replyContent, replyId, existingYouTubeIframes);
            replyContent.dataset.youtubeProcessed = 'true';
        }
    });
}

// YouTube埋め込み処理（既存のiframeを再利用）
function processYouTubeEmbeds(contentElement, itemId, existingIframes) {
    const html = contentElement.innerHTML;
    
    // Pattern 1: YouTube links inside <a> tags (from marked.parse)
    // Matches: <a href="youtube-url">...</a>
    const anchorPattern = /<a[^>]*href=["'](https?:\/\/(?:www\.|m\.)?(?:youtube\.com\/watch\?[^\s"'<]*v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})[^\s"'<]*)["'][^>]*>.*?<\/a>/gi;
    
    // Pattern 2: Bare YouTube URLs not in anchor tags
    const bareUrlPattern = /(^|[^"'>])(https?:\/\/(?:www\.|m\.)?(?:youtube\.com\/watch\?[^\s<"']*v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})(?:[^\s<"']*))/gi;
    
    let match;
    const replacements = [];
    
    // Find YouTube links in anchor tags
    while ((match = anchorPattern.exec(html)) !== null) {
        const fullMatch = match[0];
        const url = match[1];
        const videoId = match[2];
        
        // Skip if videoId is null or undefined
        if (!videoId) continue;
        
        const embedSrc = `https://www.youtube.com/embed/${videoId}`;
        const key = `${itemId}-${embedSrc}`;
        
        replacements.push({
            fullMatch: fullMatch,
            videoId: videoId,
            embedSrc: embedSrc,
            hasExisting: existingIframes && existingIframes[key],
            index: match.index
        });
    }
    
    // Reset lastIndex for the bare URL pattern
    bareUrlPattern.lastIndex = 0;
    
    // Find bare YouTube URLs
    let tempHtml = html;
    replacements.forEach(rep => {
        // Remove already-found anchor tag URLs to avoid duplicate matching
        tempHtml = tempHtml.replace(rep.fullMatch, '');
    });
    
    while ((match = bareUrlPattern.exec(tempHtml)) !== null) {
        const prefix = match[1];
        const url = match[2];
        const videoId = match[3];
        
        // Skip if videoId is null or undefined
        if (!videoId) continue;
        
        const embedSrc = `https://www.youtube.com/embed/${videoId}`;
        const key = `${itemId}-${embedSrc}`;
        
        replacements.push({
            fullMatch: match[0],
            videoId: videoId,
            embedSrc: embedSrc,
            hasExisting: existingIframes && existingIframes[key],
            prefix: prefix,
            index: match.index
        });
    }
    
    if (replacements.length > 0) {
        // Sort replacements by index in reverse order to replace from end to start
        // This prevents index shifts when replacing
        replacements.sort((a, b) => (b.index || 0) - (a.index || 0));
        
        let newHtml = html;
        replacements.forEach(rep => {
            const placeholderClass = `youtube-placeholder-${rep.videoId}-${Math.random().toString(36).slice(2, 11)}`;
            const placeholder = `<div class="${placeholderClass}"></div>`;
            // Use a more specific replacement - only replace the first occurrence
            newHtml = newHtml.replace(rep.fullMatch, rep.prefix ? rep.prefix + placeholder : placeholder);
            // Store the placeholder class for later retrieval
            rep.placeholderClass = placeholderClass;
        });
        
        contentElement.innerHTML = newHtml;
        
        // プレースホルダーを実際のiframeで置き換え
        replacements.forEach(rep => {
            const placeholder = contentElement.querySelector(`.${rep.placeholderClass}`);
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

// 単一返信のHTML生成（embedYouTubeはfalseの場合は埋め込まない）
function renderReply(reply, embedYouTubeNow = true) {
    const frameClass = reply.frame_class || '';
    const titleHtml = reply.title_text ? 
        `<span class="reply-title ${reply.title_css}">${reply.title_text}</span>` : '';
    
    const deleteBtn = reply._can_delete ? 
        `<button class="reply-action-btn" onclick="deleteReply(${reply.id})" style="color: #e53e3e;">
            🗑️ 削除
        </button>` : '';
    
    // NSFW判定
    const isNsfw = reply.nsfw === true || reply.nsfw === 1 || reply.nsfw === '1';
    const nsfwBlurStyle = isNsfw ? 'filter: blur(12px); cursor: pointer;' : '';
    const nsfwOnClick = isNsfw ? `onclick="this.style.filter='none'; this.style.cursor='default';"` : '';
    const nsfwTitle = isNsfw ? 'title="NSFW: クリックで表示"' : '';
    
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
    
    // コンテンツの処理（embedYouTubeNowがfalseの場合はYouTube埋め込みをしない）
    let contentHtml = marked.parse(reply.content_md || reply.content_html);
    contentHtml = parseHashtags(contentHtml);
    if (embedYouTubeNow) {
        contentHtml = embedYouTube(contentHtml);
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
            <div class="reply-content" style="${nsfwBlurStyle}" ${nsfwOnClick} ${nsfwTitle}>
                ${contentHtml}
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
            if (data.error === 'muted') {
                const remainingTime = data.remaining_time || '不明';
                const mutedUntil = data.muted_until || '不明';
                showMutePopup(remainingTime, mutedUntil);
            } else {
                alert('返信失敗: ' + data.error);
            }
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

// 3秒ごとに自動更新（返信のみチェック）
let lastRepliesData = '';
setInterval(async () => {
    try {
        const res = await fetch('replies_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'list', post_id: POST_ID})
        });
        const data = await res.json();
        
        if (data.ok) {
            // より包括的なハッシュ値を使用（ID、いいね数、コンテンツ、削除状態を検知）
            const currentData = JSON.stringify((data.items || []).map(r => ({
                id: r.id,
                like_count: r.like_count,
                content: r.content_md || r.content_html,
                is_deleted: r.is_deleted || r.deleted_at
            })));
            
            // データが変わった場合のみ再レンダリング
            if (currentData !== lastRepliesData) {
                replies = data.items || [];
                renderReplies();
                lastRepliesData = currentData;
            }
        }
    } catch (err) {
        console.error('自動更新エラー', err);
    }
}, 3000);

// Process original post content for YouTube embeds
document.addEventListener('DOMContentLoaded', function() {
    const originalPostContent = document.querySelector('.original-post .reply-content');
    if (originalPostContent && !originalPostContent.dataset.youtubeEmbedded) {
        originalPostContent.innerHTML = embedYouTube(originalPostContent.innerHTML);
        originalPostContent.dataset.youtubeEmbedded = 'true';
    }
});

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
            const data = await res.json();
            
            if (data.ok) {
                alert('異議申し立てを受け付けました。管理者が審査します。');
                document.body.removeChild(dialog);
            } else {
                alert('異議申し立てに失敗しました: ' + (data.message || data.error));
            }
        } catch (err) {
            alert('ネットワークエラー');
        }
    };
}
</script>

<!-- Media Expand Modal -->
<div id="mediaExpandModal" class="media-expand-modal" onclick="closeMediaExpand()">
  <span class="media-expand-close" onclick="closeMediaExpand()">&times;</span>
  <div id="mediaExpandContent"></div>
</div>

</body>
</html>
