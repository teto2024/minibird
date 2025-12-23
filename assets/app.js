// Mobile breakpoint constant
const MOBILE_BREAKPOINT = 768;
const MAX_MEDIA_FILES = 4; // 最大画像アップロード数

const state = {
    feed: 'null',
    posts: [],
    newestId: 0,
    oldestId: 0,
    isLoading: false,
    hasMore: true
};
console.log('state.feed:', state.feed);
//----------
//いいね更新用関数
//-----------
function updateLikeUI(p) {
    const wrap = qs('#feed');
    const postEl = wrap.querySelector(`[data-post-id="${p.id}"]`);
    if (!postEl) return;

    const likeBtn = postEl.querySelector('.like-btn');
    if (likeBtn) {
        likeBtn.textContent = '❤️' + (p.like_count || 0);
        if (p.liked) {
            likeBtn.classList.add('liked');
        } else {
            likeBtn.classList.remove('liked');
        }
    }
}

//---------------
//parseMessage - YouTube embedding support
//----------------
// YouTube URL patterns constant for ID extraction
const YOUTUBE_URL_PATTERNS = [
    /(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/,
    /youtube\.com\/watch\?.*v=([a-zA-Z0-9_-]{11})/,
    /(?:m\.youtube\.com\/watch\?v=)([a-zA-Z0-9_-]{11})/
];

// Common YouTube URL pattern for regex replacements in HTML
// Note: This pattern is optimized for detecting YouTube URLs in typical social media posts
// It handles the most common YouTube URL formats but may not cover every edge case
const YOUTUBE_URL_PATTERN_STR = 'https?:\\/\\/(?:www\\.|m\\.)?(?:youtube\\.com\\/watch\\?[^"\'\\s]*v=|youtu\\.be\\/|youtube\\.com\\/embed\\/)([a-zA-Z0-9_-]{11})';

function extractYouTubeId(url) {
    // YouTube URL patterns:
    // https://www.youtube.com/watch?v=VIDEO_ID
    // https://youtu.be/VIDEO_ID
    // https://www.youtube.com/embed/VIDEO_ID
    for (const pattern of YOUTUBE_URL_PATTERNS) {
        const match = url.match(pattern);
        if (match && match[1]) {
            return match[1];
        }
    }
    return null;
}

function createYouTubeEmbed(videoId) {
    return `<div class="youtube-embed-wrapper">
        <iframe class="youtube-embed" 
                src="https://www.youtube.com/embed/${videoId}" 
                frameborder="0" 
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                allowfullscreen>
        </iframe>
    </div>`;
}

function embedYouTube(html) {
    // Process YouTube URLs and convert them to embeds
    // This function processes both bare URLs and URLs inside anchor tags
    
    // Pattern 1: YouTube links inside <a> tags (from marked.parse)
    // Matches: <a href="youtube-url">...</a>
    // Using .*? to properly handle nested HTML elements within links
    // Note: The [^"']* pattern handles most YouTube URL query parameters correctly
    // for typical use cases (e.g., ?v=ID&t=30s)
    const anchorPattern = new RegExp(
        `<a[^>]*href=["'](${YOUTUBE_URL_PATTERN_STR})[^"']*["'][^>]*>.*?<\\/a>`,
        'gi'
    );
    html = html.replace(anchorPattern, (match, url, videoId) => {
        return createYouTubeEmbed(videoId);
    });
    
    // Pattern 2: Bare YouTube URLs not yet converted to links
    // Matches: plain text YouTube URLs
    // Negative lookahead stops at common punctuation that typically ends a URL in text
    // This covers the vast majority of real-world use cases in social media posts
    const bareUrlPattern = new RegExp(
        `(^|[^">])(${YOUTUBE_URL_PATTERN_STR})(?=[\\s<.,;!?]|$)`,
        'gi'
    );
    html = html.replace(bareUrlPattern, (match, prefix, url, videoId) => {
        return prefix + createYouTubeEmbed(videoId);
    });
    
    return html;
}

function parseMessage(html) {
    // メンション、URL自動リンク化、ハッシュタグ変換を実行
    // 注意: リンク内のテキストは変換しない
    
    // HTML特殊文字をエスケープするヘルパー関数
    function escapeHtml(str) {
        return str.replace(/[&<>"']/g, function(m) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m];
        });
    }
    
    // URLを自動リンク化（ただし既にリンクになっているものは除外）
    // より単純な方法: <a タグ内のURLは無視
    const parts = html.split(/(<a[^>]*>.*?<\/a>)/gi);
    const result = parts.map((part, i) => {
        // 偶数インデックスはリンク外、奇数はリンク内
        if (i % 2 === 0) {
            // メンションをリンク化（@username）
            // YouTube埋め込みやURL変換の前に処理
            let processed = part.replace(/@([a-zA-Z0-9_]+)/g, (match, handle) => {
                return `<a href="profile.php?handle=${encodeURIComponent(handle)}" class="mention">@${escapeHtml(handle)}</a>`;
            });
            
            // URLを自動リンク化
            processed = processed.replace(/(https?:\/\/[^\s<]+)/g, (url) => {
                // Check if it's a YouTube URL
                const youtubeId = extractYouTubeId(url);
                if (youtubeId) {
                    // Create YouTube embed
                    return createYouTubeEmbed(youtubeId);
                }
                return `<a href="${escapeHtml(url)}" target="_blank" class="link">${escapeHtml(url)}</a>`;
            });
            
            // ハッシュタグをリンク化（日本語、英数字、アンダースコアに対応）
            // 既にリンク化されていない#タグのみ対象
            processed = processed.replace(/#([a-zA-Z0-9_\u3040-\u309F\u30A0-\u30FF\u4E00-\u9FAF]+)/g, (match, tag) => {
                return `<a href="search.php?q=${encodeURIComponent('#' + tag)}" class="hashtag">#${escapeHtml(tag)}</a>`;
            });
            
            return processed;
        }
        return part;
    });

    return result.join('');
}



function qs(sel) { return document.querySelector(sel) }
function ce(tag, cls) { const el = document.createElement(tag); if (cls) el.className = cls; return el }
function timeago(ts) { return new Date(ts).toLocaleString() }
async function api(path, data) {
    try {
        const res = await fetch(path, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data),
            credentials: 'include'
        });
        const text = await res.text(); // まずテキストで取得
        try {
            return JSON.parse(text);   // JSON に変換
        } catch (e) {
            console.error('JSON parse error:', text);
            return { ok: false, error: 'invalid_json', raw: text };
        }
    } catch (e) {
        console.error('Fetch error:', e);
        return { ok: false, error: 'network_error' };
    }
}

// ---------------------
// DOMContentLoaded (整理版)
// ---------------------
document.addEventListener('DOMContentLoaded', () => {

    // marked.jsの設定：単一の改行も<br>として扱う
    if (typeof marked !== 'undefined') {
        marked.setOptions({
            breaks: true,  // 単一の改行を<br>に変換
            gfm: true      // GitHub Flavored Markdown を有効化
        });
    }

    // Feed 初期ロード
    const feedEl = document.getElementById('feed');
    if (feedEl && feedEl.dataset.feed) {
        state.feed = feedEl.dataset.feed; // PHP からセットされた feed を優先
    } else {
        state.feed = 'global';
    }
    console.log('refreshFeed feed:', state.feed);
    refreshFeed(true);

    // スクロールで loadMore（統合版）
    window.addEventListener('scroll', () => {
        if (state.isLoading || !state.hasMore) return;
        if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 200) loadMore();
    });

    // Feed 切り替え
    document.querySelectorAll('.tabBtn, .feedTab').forEach(btn => {
        btn.addEventListener('click', async () => {
            const feed = btn.dataset.tab || btn.dataset.feed;
            if (!feed) return;
            state.feed = feed;
            qs('#feed').dataset.feed = feed;
            document.querySelectorAll('.tabBtn, .feedTab').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            console.log('refreshFeed feed:', state.feed);
            await refreshFeed(true);
        });
    });

    // フォロー周り
    document.querySelectorAll('.followBtn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const targetId = btn.dataset.userid;
            const action = btn.classList.contains('following') ? 'unfollow' : 'follow';

            const r = await fetch('follow.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action, target_id: targetId }),
                credentials: 'include'
            }).then(r => r.json());

            if (r.ok) {
                btn.classList.toggle('following', action === 'follow');
                btn.textContent = action === 'follow' ? 'フォロー中' : 'フォロー';
            } else {
                alert('失敗: ' + r.error);
            }
        });
    });

    // Enterで投稿のチェックボックス状態をlocalStorageに保存
    qs('#enterToPost')?.addEventListener('change', (e) => {
        localStorage.setItem('enterToPost', e.target.checked ? 'true' : 'false');
    });

    // ページ読み込み時にlocalStorageから状態を復元
    const savedEnterToPost = localStorage.getItem('enterToPost');
    if (savedEnterToPost && qs('#enterToPost')) {
        qs('#enterToPost').checked = savedEnterToPost === 'true';
    }

    // Quote modal event listeners
    qs('#closeQuoteModal')?.addEventListener('click', hideQuoteModal);
    qs('#cancelQuote')?.addEventListener('click', hideQuoteModal);
    qs('#submitQuote')?.addEventListener('click', submitQuotePost);
    
    // Quote media preview
    qs('#quoteMedia')?.addEventListener('change', (e) => {
        const preview = qs('#quoteMediaPreview');
        preview.innerHTML = '';
        const files = Array.from(e.target.files).slice(0, MAX_MEDIA_FILES);
        files.forEach(file => {
            const reader = new FileReader();
            reader.onload = (e) => {
                if (file.type.startsWith('image/')) {
                    const img = ce('img');
                    img.src = e.target.result;
                    preview.append(img);
                } else if (file.type.startsWith('video/')) {
                    const video = ce('video');
                    video.src = e.target.result;
                    video.controls = true;
                    preview.append(video);
                }
            };
            reader.readAsDataURL(file);
        });
    });
    
    // Quote Enter to post checkbox
    qs('#quoteEnterToPost')?.addEventListener('change', (e) => {
        localStorage.setItem('quoteEnterToPost', e.target.checked ? 'true' : 'false');
    });
    
    // Quote text keyboard shortcuts
    const quoteTextArea = qs('#quoteText');
    if (quoteTextArea) {
        quoteTextArea.addEventListener('keydown', (e) => {
            const quoteEnterToPost = qs('#quoteEnterToPost')?.checked;
            
            // Shift+Enter: allow line break (default behavior)
            if (e.key === 'Enter' && e.shiftKey) {
                return;
            }
            
            // Ctrl+Enter or Cmd+Enter: submit quote (PC only)
            if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
                e.preventDefault();
                if (window.innerWidth > MOBILE_BREAKPOINT) {
                    submitQuotePost();
                }
                return;
            }
            
            // Enter only: submit on mobile if checkbox is ON
            if (e.key === 'Enter' && !e.shiftKey && !e.ctrlKey && !e.metaKey) {
                if (window.innerWidth <= MOBILE_BREAKPOINT && quoteEnterToPost) {
                    e.preventDefault();
                    submitQuotePost();
                    return;
                }
                // On mobile without checkbox, allow line break
                if (window.innerWidth <= MOBILE_BREAKPOINT) {
                    return;
                }
                // On PC, prevent default (no submission)
                e.preventDefault();
            }
        });
    }

});



// ---------------------
// Auth
// ---------------------
const authModal = qs('#authModal');
qs('#showAuth')?.addEventListener('click', () => authModal.classList.remove('hidden'));
qs('#closeAuth')?.addEventListener('click', () => authModal.classList.add('hidden'));
qs('#loginBtn')?.addEventListener('click', async () => {
    const r = await api('auth.php', { action: 'login', handle: qs('#handle').value, password: qs('#password').value });
    if (r.ok) location.reload(); else alert('ログイン失敗: ' + r.error);
});
qs('#registerBtn')?.addEventListener('click', async () => {
    const r = await api('auth.php', { action: 'register', handle: qs('#handle').value, password: qs('#password').value, invited_by: qs('#invited_by').value });
    if (r.ok) location.reload(); else alert('登録失敗: ' + r.error);
});
qs('#logoutBtn')?.addEventListener('click', async () => {
    await api('auth.php', { action: 'logout' }); location.reload();
});
qs('#changePass')?.addEventListener('click', async () => {
    const r = await api('auth.php', { action: 'change_password', new_password: qs('#newPass').value });
    if (r.ok) alert('変更しました'); else alert('失敗: ' + r.error);
});
qs('#revealHash')?.addEventListener('click', async () => {
    const r = await api('auth.php', { action: 'get_user_hash' });
    if (r.ok) { qs('#userHash').textContent = r.user_hash; qs('#userHash').classList.remove('hidden'); }
});

// ---------------------
// Composer
// ---------------------
qs('#submitPost')?.addEventListener('click', async () => {
    const fd = new FormData();
    fd.append('action', 'create_post');
    fd.append('content', qs('#postText').value);
    fd.append('nsfw', qs('#nsfw').checked ? '1' : '0');
    
    // 複数画像対応（最大MAX_MEDIA_FILES枚）
    const mediaInput = qs('#media');
    if (mediaInput && mediaInput.files.length > 0) {
        const files = Array.from(mediaInput.files).slice(0, MAX_MEDIA_FILES);
        if (files.length === 1) {
            // 単一画像の場合は従来通り
            fd.append('media', files[0]);
        } else {
            // 複数画像の場合
            files.forEach((file, index) => {
                fd.append(`media_${index}`, file);
            });
        }
    }
    
    const r = await fetch('post.php', { method: 'POST', body: fd, credentials: 'include' }).then(r => r.json());
    if (r.ok) { 
        qs('#postText').value = ''; 
        qs('#media').value = null; 
        refreshFeed(true); 
    } else {
        if (r.error === 'muted') {
            const remainingTime = r.remaining_time || '不明';
            const mutedUntil = r.muted_until || '不明';
            showMutePopup(remainingTime, mutedUntil);
        } else {
            alert('投稿失敗: ' + r.error);
        }
    }
});

// キーボードショートカット: Shift+Enter で改行、Ctrl+Enter でポスト（PC のみ）
// enterToPostCheckbox要素をキャッシュしてパフォーマンス向上
const postTextArea = qs('#postText');
if (postTextArea) {
    const enterToPostCheckbox = qs('#enterToPost');
    postTextArea.addEventListener('keydown', (e) => {
        const enterToPostEnabled = enterToPostCheckbox && enterToPostCheckbox.checked;
        
        // Shift+Enter: 改行を許可（デフォルト動作）
        if (e.key === 'Enter' && e.shiftKey) {
            // デフォルトの改行動作を許可（何もしない）
            return;
        }
        
        // Ctrl+Enter または Cmd+Enter: ポスト送信（PC のみ）
        if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
            e.preventDefault();
            // モバイルデバイスでは無効化
            if (window.innerWidth > MOBILE_BREAKPOINT) {
                qs('#submitPost')?.click();
            }
            return;
        }
        
        // Enter のみ: PC では何もしない、モバイルでは改行を許可（チェックボックスがONなら投稿）
        if (e.key === 'Enter' && !e.shiftKey && !e.ctrlKey && !e.metaKey) {
            // モバイルでEnterで投稿がONの場合は投稿
            if (window.innerWidth <= MOBILE_BREAKPOINT && enterToPostEnabled) {
                e.preventDefault();
                qs('#submitPost')?.click();
                return;
            }
            // モバイルでチェックボックスがOFFの場合は改行を許可
            if (window.innerWidth <= MOBILE_BREAKPOINT) {
                return;
            }
            // PCでは Enter のみの場合は何もしない（投稿しない）
            e.preventDefault();
        }
    });
}

// ---------------------
// Feed switching
// ---------------------
//ここだけ残して削除済み
const feedEl = qs('#feed');

// ---------------------
// Feed Handling
// ---------------------
async function refreshFeed(reset = false) {
    console.log('refreshFeed feed:', state.feed);
    if (state.isLoading) return;
    state.isLoading = true;

    const feedEl = qs('#feed');
    const loadingEl = qs('#loading');
    loadingEl.style.display = 'block';

    try {
        const r = await api('feed.php', {
            action: 'fetch',
            feed: state.feed,
            since_id: reset ? 0 : state.newestId,
            limit: 50
        });

        if (r.ok) {
            if (reset) {
                state.posts = r.items.map(p => ({ ...p }));
            } else {
                r.items.forEach(p => {
                    if (!state.posts.some(existing => existing.id === p.id)) {
                        state.posts.unshift(p);
                    }
                });
            }

            // 投稿を描画（投稿要素だけ更新）
            renderFeed();

            if (state.posts.length) {
                state.newestId = Math.max(...state.posts.map(p => p.id));
                state.oldestId = Math.min(...state.posts.map(p => p.id));
            }

        } else {
            console.error('読み込みエラー', r);
        }
    } catch (e) {
        console.error('通信エラー', e);
    }

    loadingEl.style.display = 'none';
    state.isLoading = false;
}



async function loadMore() {
    if (state.isLoading || !state.hasMore) return;
    state.isLoading = true; qs('#loading').style.display = 'block';
    const r = await api('feed.php', { action: 'fetch_more', feed: state.feed, max_id: state.oldestId - 1, limit: 50 });
    if (r.ok && r.items.length) {
        r.items.forEach(p => { if (!state.posts.some(existing => existing.id === p.id)) state.posts.push(p); });
        state.oldestId = Math.min(...state.posts.map(p => p.id));
    } else state.hasMore = false;
    renderFeed();
    qs('#loading').style.display = 'none';
    state.isLoading = false;
}

window.addEventListener('scroll', () => {
    if (state.isLoading || !state.hasMore) return;
    if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 200) loadMore();
});
//----------------------
//render
//----------------------
/*function renderPost(p, wrap) {
    console.log('renderPost data:', p); // ←ここ
    const post = ce('div', 'post ' + (p.frame_class || ''));
    post.dataset.postId = p.id;

    // アイコン
    const av = ce('img');
    // PHP側で正しいパスが返るのでそのまま使用。なければデフォルトアイコン
    av.src = p.icon || '/uploads/icons/default_icon.png';
    // 表示名があれば alt に設定
    av.alt = p.display_name || p.handle || 'unknown';
    av.classList.add('avatar');

    // コンテンツ
    const cnt = ce('div', 'content');

    // meta
    const meta = ce('div', 'meta');
    // 表示名優先、なければハンドルネーム
    const displayName = p.display_name || p.handle || 'unknown';
    // ユーザープロフィールリンク
    const userLink = p.user_id ? `profile.php?id=${p.user_id}` : `profile.php?handle=${encodeURIComponent(p.handle)}`;
    meta.innerHTML = `<a href="${userLink}" class="mention">${displayName}</a> @${p.handle} ・ ${timeago(p.created_at)}`;
    
// ユーザー名表示部分の直後にVIPラベルを追加
if (p.vip_level && p.vip_level > 0) {
    meta.innerHTML += ` ・ <span class="vip-label">👑VIP${p.vip_level}</span>`;
}

    // リポスト情報
    if (p.is_repost_of) {
        const repLink = p.reposter_id
            ? `profile.php?id=${p.reposter_id}`
            : `profile.php?handle=${encodeURIComponent(p.reposter)}`;
        const repName = p.reposter || 'unknown';
        meta.innerHTML += `
        ・ <span class="repost-label">♲リポスト</span>
        <a href="${repLink}" class="mention"><strong>${repName}</strong></a>さんの投稿をリポストしました
    `;
    }


    if (p.deleted) meta.textContent += ' ・ 削除済み';

    //------------ 
    // 本文 (Markdown + メンション変換)
    //------------
    const body = ce('div', 'body');

    if (p.deleted) {
        body.textContent = '削除済み';
    } else {
        // 引用元があれば別要素で表示
        if (p.quoted_post) {
            const quoteDiv = ce('div', 'quote');
            // クリックして引用元投稿へ遷移
            quoteDiv.style.cursor = 'pointer';
            quoteDiv.onclick = (e) => {
                e.stopPropagation();
                const quotedPostId = p.quoted_post.id || p.quote_post_id;
                if (quotedPostId) {
                    window.location.href = `replies_enhanced.php?post_id=${quotedPostId}`;
                }
            };

            const quoteMeta = ce('div', 'meta');
            const qDisplayName = p.quoted_post.display_name || p.quoted_post.handle || 'unknown';
            const qLink = p.quoted_post.user_id ? `profile.php?id=${p.quoted_post.user_id}` : `profile.php?handle=${encodeURIComponent(p.quoted_post.handle)}`;
            quoteMeta.innerHTML = `<a href="${qLink}" class="mention">${qDisplayName}</a>`;
            quoteDiv.append(quoteMeta);

            const quoteBody = ce('div', 'quote-body');
            const quotedMd = p.quoted_post.content_md || p.quoted_post.content_html || '';
            quoteBody.innerHTML = parseMessage(marked.parse(quotedMd));
            quoteDiv.append(quoteBody);

            body.append(quoteDiv);
        }

        // 自分の本文
        const rawContent = p.content_md || p.content_html || '';
        const myBody = ce('div', 'my-body');
        myBody.innerHTML = parseMessage(marked.parse(rawContent));
        body.append(myBody);
    }

    // -------------------------
    // メディア表示（複数画像対応）
    // -------------------------
    if (!p.deleted && (p.media_paths || p.media_path)) {
        const mediaWrapper = ce('div', 'media-wrapper');
        
        // 複数画像がある場合
        if (p.media_paths && Array.isArray(p.media_paths) && p.media_paths.length > 0) {
            const mediaGrid = ce('div', 'media-grid');
            mediaGrid.classList.add(`media-count-${Math.min(p.media_paths.length, MAX_MEDIA_FILES)}`);
            
            p.media_paths.forEach((mediaPath, index) => {
                const mediaContainer = ce('div', 'media-item');
                const ext = mediaPath.split('.').pop().toLowerCase();
                const mediaSrc = window.location.origin + '/' + mediaPath;
                
                let mediaEl;
                if (['png', 'jpg', 'jpeg', 'gif', 'webp'].includes(ext)) {
                    mediaEl = ce('img');
                    mediaEl.loading = 'lazy';
                } else if (['mp4', 'webm', 'ogg'].includes(ext)) {
                    mediaEl = ce('video');
                    mediaEl.controls = true;
                }
                
                if (mediaEl) {
                    mediaEl.src = mediaSrc;
                    mediaContainer.append(mediaEl);
                    mediaGrid.append(mediaContainer);
                }
            });
            
            mediaWrapper.append(mediaGrid);
            body.append(mediaWrapper);
        } 
        // 単一画像の場合（後方互換性）
        else if (p.media_path) {
            const mediaContainer = ce('div', 'media');
            let mediaEl;
            const ext = p.media_path.split('.').pop().toLowerCase();
            const mediaSrc = window.location.origin + '/' + p.media_path;

            if (['png', 'jpg', 'jpeg', 'gif', 'webp'].includes(ext)) {
                mediaEl = ce('img');
            } else if (['mp4', 'webm', 'ogg'].includes(ext)) {
                mediaEl = ce('video');
                mediaEl.controls = true;
            }
            
            if (mediaEl) {
                mediaEl.src = mediaSrc;
                mediaContainer.append(mediaEl);
            }

            mediaWrapper.append(mediaContainer);
            body.append(mediaWrapper);
        }
    }

    // -------------------------
    // NSFW テキストぼかし
    // -------------------------
    if (!p.deleted && p.nsfw) {
        body.style.filter = 'blur(var(--nsfw-blur))';
        body.style.cursor = 'pointer';
        body.title = 'NSFW: クリックで表示';
        body.addEventListener('click', () => { body.style.filter = ''; });
    }

    // -------------------------
    // NSFW メディアぼかし
    // -------------------------
    if (!p.deleted && p.nsfw) {
        const mediaWrapper = body.querySelector('.media-wrapper');
        if (mediaWrapper) {
            mediaWrapper.style.filter = 'blur(var(--nsfw-blur))';
            mediaWrapper.style.cursor = 'pointer';
            mediaWrapper.title = 'NSFW: クリックで表示';
            mediaWrapper.addEventListener('click', () => { mediaWrapper.style.filter = ''; });
        }
    }

    // -------------------------
    // ボタン類
    // -------------------------
    const buttons = ce('div', 'buttons');
    // いいねボタン
    const like = ce('button', 'like-btn');
    like.textContent = '❤️' + (p.like_count || 0);
    if (p.liked) like.classList.add('liked');

    like.onclick = async () => {
        const r = await api('actions.php', { action: 'toggle_like', post_id: p.id });
        if (r.ok) {
            p.liked = r.liked;
            p.like_count = r.count;
            updateLikeUI(p);  // ← 投稿全体ではなくUIだけ更新
        }
    };

    const repost = ce('button'); repost.textContent = '♻️' + (p.repost_count || 0); if (p.reposted) repost.classList.add('reposted');
    repost.onclick = async () => { const r = await api('actions.php', { action: 'toggle_repost', post_id: p.id }); if (r.ok) { p.reposted = r.reposted; p.repost_count = r.count; refreshFeed(true); } };

    const bm = ce('button'); bm.textContent = '📑'; bm.onclick = async () => { const r = await api('actions.php', { action: 'toggle_bookmark', post_id: p.id }); if (!r.ok) alert('ブックマーク失敗'); };

    const rep = ce('button'); rep.textContent = '💬' + (p.reply_count || 0); rep.onclick = () => { window.location = 'replies.php?post_id=' + p.id; };
    const qt = ce('button'); qt.textContent = '❝ 引用'; qt.onclick = () => { const t = prompt('引用コメント'); if (t) quotePost(p.id, t); };

    let delBtn = null;
    if (p._can_delete && !p.deleted) {
        delBtn = ce('button'); delBtn.textContent = '削除';
        delBtn.onclick = async () => { if (!confirm('この投稿を削除しますか？')) return; const r = await api('actions.php', { action: 'delete_post', post_id: p.id }); if (r.ok) { p.deleted = true; updatePost(p); } else alert('削除失敗'); };
    }

    buttons.append(like, repost, bm, rep, qt); if (delBtn) buttons.append(delBtn);
    cnt.append(meta, body, buttons);
    post.append(av, cnt);
    wrap.append(post);
}
*/


function renderFeed() {
    const wrap = qs('#feed'); wrap.innerHTML = '';
    state.posts.forEach(p => renderPost(p, wrap));
}

function updatePost(p) {
    const wrap = qs('#feed');
    const old = wrap.querySelector(`[data-post-id="${p.id}"]`);
    if (old) old.remove();
    renderPost(p, wrap);
}

// Quote modal state
let currentQuotePostId = null;

// Show quote modal
function showQuoteModal(post) {
    currentQuotePostId = post.id;
    const modal = qs('#quoteModal');
    const preview = qs('#quotedPostPreview');
    const quoteText = qs('#quoteText');
    const quoteNsfw = qs('#quoteNsfw');
    const quoteMedia = qs('#quoteMedia');
    const quoteMediaPreview = qs('#quoteMediaPreview');
    const quoteEnterToPost = qs('#quoteEnterToPost');
    
    // Reset form
    quoteText.value = '';
    quoteNsfw.checked = false;
    quoteMedia.value = '';
    quoteMediaPreview.innerHTML = '';
    
    // Load saved Enter to post preference
    const savedQuoteEnter = localStorage.getItem('quoteEnterToPost');
    if (savedQuoteEnter) {
        quoteEnterToPost.checked = savedQuoteEnter === 'true';
    }
    
    // Build preview of quoted post
    const displayName = post.display_name || post.handle || 'unknown';
    const userLink = post.user_id ? `profile.php?id=${post.user_id}` : `profile.php?handle=${encodeURIComponent(post.handle)}`;
    const content = post.content_html || marked.parse(post.content_md || '');
    
    preview.innerHTML = `
        <div class="quote-meta">
            <a href="${userLink}" class="mention">${displayName}</a> @${post.handle}
        </div>
        <div class="quote-body">${content}</div>
    `;
    
    modal.classList.remove('hidden');
    quoteText.focus();
}

// Hide quote modal
function hideQuoteModal() {
    const modal = qs('#quoteModal');
    modal.classList.add('hidden');
    currentQuotePostId = null;
    // Clear any error messages
    const errorMsg = modal.querySelector('.quote-error-message');
    if (errorMsg) errorMsg.remove();
}

// Show error in quote modal
function showQuoteError(message) {
    const modal = qs('#quoteModal');
    const modalContent = modal.querySelector('.quote-modal-content');
    
    // Remove existing error message
    const existingError = modal.querySelector('.quote-error-message');
    if (existingError) existingError.remove();
    
    // Add new error message
    const errorDiv = ce('div', 'quote-error-message');
    errorDiv.textContent = message;
    modalContent.insertBefore(errorDiv, modalContent.firstChild);
    
    // Auto-remove after 5 seconds
    setTimeout(() => errorDiv.remove(), 5000);
}

// Submit quote post
async function submitQuotePost() {
    const quoteText = qs('#quoteText').value.trim();
    const quoteNsfw = qs('#quoteNsfw').checked;
    const quoteMedia = qs('#quoteMedia');
    
    if (!quoteText && (!quoteMedia.files || quoteMedia.files.length === 0)) {
        showQuoteError('引用コメントまたは画像を入力してください');
        return;
    }
    
    const fd = new FormData();
    fd.append('action', 'quote_post');
    fd.append('post_id', currentQuotePostId);
    fd.append('content', quoteText);
    fd.append('nsfw', quoteNsfw ? '1' : '0');
    
    // Add media files
    if (quoteMedia.files && quoteMedia.files.length > 0) {
        const files = Array.from(quoteMedia.files).slice(0, MAX_MEDIA_FILES);
        if (files.length === 1) {
            fd.append('media', files[0]);
        } else {
            files.forEach((file, index) => {
                fd.append(`media_${index}`, file);
            });
        }
    }
    
    const r = await fetch('post.php', { 
        method: 'POST', 
        body: fd, 
        credentials: 'include' 
    }).then(r => r.json());
    
    if (r.ok) {
        hideQuoteModal();
        refreshFeed(true);
    } else {
        if (r.error === 'muted') {
            hideQuoteModal();
            const remainingTime = r.remaining_time || '不明';
            const mutedUntil = r.muted_until || '不明';
            showMutePopup(remainingTime, mutedUntil);
        } else {
            showQuoteError('引用失敗: ' + r.error);
        }
    }
}

// Old quotePost function for compatibility
async function quotePost(post_id, text) {
    const r = await api('post.php', { action: 'quote_post', post_id, content: text });
    if (r.ok) refreshFeed(true); else alert('引用失敗: ' + r.error);
}
//----------------------
//ハンガーメニュー
//----------------------
window.addEventListener("load", () => {
    const toggleBtn = document.querySelector(".menu-toggle");
    const leftMenu = document.querySelector(".left");
    const closeBtn = document.querySelector(".close-menu");

    if (toggleBtn && leftMenu) {
        toggleBtn.addEventListener("click", () => {
            leftMenu.classList.add("open");
        });
    }

    if (closeBtn && leftMenu) {
        closeBtn.addEventListener("click", () => {
            leftMenu.classList.remove("open");
        });
    }
});
document.addEventListener("DOMContentLoaded", () => {
    const rightAside = document.querySelector("aside.right");
    const leftNav = document.querySelector("aside.left nav");

    if (rightAside && leftNav) {
        // クローンを作って追加（スマホ用）
        const clone = rightAside.cloneNode(true);
        clone.classList.add("mobile-only"); // スタイル用
        leftNav.appendChild(clone);
    }
});

//----------------------------
// 通知管理スクリプト (安全強化版)
//----------------------------

let lastNotificationId = 0;

const notificationBtn = document.getElementById("notificationBtn");
const notificationPopup = document.getElementById("notificationPopup");
const notificationList = document.getElementById("notificationList");
const badge = document.querySelector("#notification-badge");
const feed = document.getElementById("feed");

// JSON 安全取得関数
async function fetchJSON(url) {
    try {
        const res = await fetch(url);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();
        return Array.isArray(data) ? data : [];
    } catch (e) {
        console.error("JSON取得エラー", e);
        return [];
    }
}

// 未読バッジ更新
function updateNotificationBadge(count) {
    if (!badge) return;
    badge.textContent = count > 0 ? count : "";
}

// 通知を既読化
async function markNotificationsRead() {
    try {
        await fetch("/mark_notifications_read.php", { method: "POST" });
        updateNotificationBadge(0);
    } catch (e) {
        console.error("既読化失敗", e);
    }
}

// 通知一覧の表示
async function loadNotifications() {
    if (!feed) return;
    feed.innerHTML = "<p>通知を読み込み中...</p>";

    const data = await fetchJSON("/notifications_api.php");
    if (data.length === 0) {
        feed.innerHTML = "<p>通知はまだありません。</p>";
        return;
    }

    // 最新 ID を更新
    const ids = data.map(n => n.id).filter(id => typeof id === "number");
    if (ids.length > 0) lastNotificationId = Math.max(...ids);

    // 未読件数バッジ更新
    const unreadCount = data.filter(n => n.is_read === 0).length;
    updateNotificationBadge(unreadCount);

    feed.innerHTML = data.map(n => {
        const actorIcon = n.actor?.icon || '/default_icon.png';
        const message = n.message || '';
        // Check if it's a community post
        const isCommunityPost = n.post && n.post.is_community;
        const postLink = n.post && n.post.id 
            ? (isCommunityPost ? `community_replies.php?post_id=${n.post.id}` : `replies.php?post_id=${n.post.id}`)
            : '#';
        const clickable = n.post && n.post.id ? 'style="cursor: pointer;"' : '';
        const onClick = n.post && n.post.id ? `onclick="location.href='${postLink}'"` : '';
        return `
        <div class="notification ${n.highlight ? "highlight" : ""}" ${clickable} ${onClick}>
            <img src="${actorIcon}" alt="アイコン" class="avatar">
            <div>
                <p>${message}</p>
                <small>${n.created_at || ''}</small>
            </div>
        </div>`;
    }).join("");

    await markNotificationsRead();
}

// ポップアップ表示用の最新5件
notificationBtn?.addEventListener("click", async () => {
    notificationPopup?.classList.toggle("hidden");

    if (!notificationPopup?.classList.contains("hidden")) {
        const data = await fetchJSON("/notifications_api.php?limit=5");
        if (!notificationList) return;
        
        if (data.length === 0) {
            notificationList.innerHTML = '<li style="padding: 10px; color: var(--muted);">通知はありません</li>';
            return;
        }
        
        notificationList.innerHTML = data.map(n => {
            const actorIcon = n.actor?.icon || '/default_icon.png';
            const message = n.message || '通知';
            // Check if it's a community post
            const isCommunityPost = n.post && n.post.is_community;
            const postLink = n.post && n.post.id 
                ? (isCommunityPost ? `community_replies.php?post_id=${n.post.id}` : `replies.php?post_id=${n.post.id}`)
                : '#';
            const clickable = n.post && n.post.id ? 'style="cursor: pointer;"' : '';
            const onClick = n.post && n.post.id ? `onclick="location.href='${postLink}'"` : '';
            return `
            <li class="${n.highlight ? "highlight" : ""}" ${clickable} ${onClick}>
                <img src="${actorIcon}" class="avatar" alt="アイコン">
                <span style="color: var(--text);">${message}</span>
            </li>`;
        }).join("");
    }
});

// 定期的に新着通知チェック
async function fetchNewNotifications() {
    const data = await fetchJSON(`/notifications_api.php?since_id=${lastNotificationId}`);
    if (data.length > 0) {
        const ids = data.map(n => n.id).filter(id => typeof id === "number");
        if (ids.length > 0) lastNotificationId = Math.max(...ids);
        const unreadCount = data.filter(n => n.is_read === 0).length;
        updateNotificationBadge(unreadCount);
    }
}

// 初回ロード時に既存通知の件数と最新IDを取得
(async function initNotifications() {
    const data = await fetchJSON("/notifications_api.php");
    if (data.length > 0) {
        const ids = data.map(n => n.id).filter(id => typeof id === "number");
        if (ids.length > 0) lastNotificationId = Math.max(...ids);
        const unreadCount = data.filter(n => n.is_read === 0).length;
        updateNotificationBadge(unreadCount);
    }
})();

// 5秒おきに新着通知チェック
setInterval(fetchNewNotifications, 5000);

// タブ切り替え時に通知タブならロード
document.querySelectorAll(".tabBtn").forEach(btn => {
    btn.addEventListener("click", async () => {
        const tab = btn.dataset.tab;
        if (tab === "notifications") {
            await loadNotifications();
        } else {
            // 他タブの既存処理
            loadFeed(tab);
        }
    });
});

//---------------------
//検索
//----------------------
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('q');
    if (!searchInput) return; // 存在しなければ処理を止める

    searchInput.addEventListener('keypress', async (e) => {
        if (e.key === 'Enter') {
            const q = searchInput.value.trim();
            if (!q) return;

            const res = await fetch(`/search.php?q=${encodeURIComponent(q)}`);
            const data = await res.json();

            const feed = document.getElementById('feed');
            feed.innerHTML = '';

            if (data.users.length > 0) {
                feed.innerHTML += '<h3>ユーザー</h3><ul>' +
                    data.users.map(u => `<li><a href="/profile.php?handle=${u.handle}">@${u.handle}</a></li>`).join('') +
                    '</ul>';
            }

            if (data.posts.length > 0) {
                feed.innerHTML += '<h3>投稿</h3><ul>' +
                    data.posts.map(p => `<li>${p.content}</li>`).join('') +
                    '</ul>';
            }

            if (data.users.length === 0 && data.posts.length === 0) {
                feed.innerHTML = '<p>検索結果はありませんでした。</p>';
            }
        }
    });
});


// ---------------------
// 差分取得用 Feed 更新
// ---------------------
async function refreshFeedPartial() {
    if (state.isLoading) return;
    state.isLoading = true;

    try {
        const r = await api('feed.php', {
            action: 'fetch',
            feed: state.feed,
            since_id: state.newestId,
            limit: 50
        });

        if (r.ok && r.items.length) {
            r.items.forEach(p => {
                if (!state.posts.some(existing => existing.id === p.id)) {
                    state.posts.unshift(p);
                    renderPost(p, qs('#feed'), true); // wrap, prepend
                }
            });
            state.newestId = Math.max(...state.posts.map(p => p.id));
        }

    } catch (e) {
        console.error(e);
    }

    state.isLoading = false;
}
/*
function renderPost(p, wrap, prepend = false) {
    console.log('renderPost data:', p);

    // リポスト元があればそちらのフレームを優先
    const frameClass = p.is_repost_of && p.quoted_post ? p.quoted_post.frame_class || '' : p.frame_class || '';
    const post = ce('div', 'post ' + frameClass);
    post.dataset.postId = p.id;

    // ユーザーアイコン
    let displayName = p.display_name || p.handle || 'unknown';
    let userIcon = p.icon || '/uploads/icons/default_icon.png';
    let userLink = p.user_id ? `profile.php?id=${p.user_id}` : `profile.php?handle=${encodeURIComponent(p.handle)}`;

    // リポスト元情報がある場合は上書き
    if (p.is_repost_of && p.quoted_post) {
        displayName = p.quoted_post.display_name || p.quoted_post.handle || 'unknown';
        userIcon = p.quoted_post.icon || '/uploads/icons/default_icon.png';
        userLink = p.quoted_post.user_id ? `profile.php?id=${p.quoted_post.user_id}` : `profile.php?handle=${encodeURIComponent(p.quoted_post.handle)}`;
    }

    const av = ce('img');
    av.src = userIcon;
    av.alt = displayName;
    av.classList.add('avatar');

    const cnt = ce('div', 'content');
    const meta = ce('div', 'meta');
    meta.innerHTML = `<a href="${userLink}" class="mention">${displayName}</a> @${p.handle} ・ ${timeago(p.created_at)}`;

    // VIP表示
    if (p.vip_level && p.vip_level > 0) {
        meta.innerHTML += ` ・ <span class="vip-label">👑VIP${p.vip_level}</span>`;
    }

    // リポスト表示
    if (p.is_repost_of) {
        const repLink = p.reposter_id
            ? `profile.php?id=${p.reposter_id}`
            : (p.reposter ? `profile.php?handle=${encodeURIComponent(p.reposter)}` : '#');
        const repName = p.reposter || 'unknown';
        meta.innerHTML += `
        ・ <span class="repost-label">♲リポスト</span>
        <a href="${repLink}" class="mention"><strong>${repName}</strong></a>
    `;
    }

    if (p.deleted) meta.textContent += ' ・ 削除済み';

    const body = ce('div', 'body');

    if (p.deleted) {
        body.textContent = '削除済み';
    } else {
        if (p.quoted_post) {
            const quoteDiv = ce('div', 'quote');
            // クリックして引用元投稿へ遷移
            quoteDiv.style.cursor = 'pointer';
            quoteDiv.onclick = (e) => {
                e.stopPropagation();
                const quotedPostId = p.quoted_post.id || p.quote_post_id;
                if (quotedPostId) {
                    window.location.href = `replies_enhanced.php?post_id=${quotedPostId}`;
                }
            };
            const quoteMeta = ce('div', 'meta');
            const qDisplayName = p.quoted_post.display_name || p.quoted_post.handle || 'unknown';
            const qLink = p.quoted_post.user_id ? `profile.php?id=${p.quoted_post.user_id}` : `profile.php?handle=${encodeURIComponent(p.quoted_post.handle)}`;
            quoteMeta.innerHTML = `<a href="${qLink}" class="mention">${qDisplayName}</a>`;
            quoteDiv.append(quoteMeta);
            const quoteBody = ce('div', 'quote-body');
            const quotedMd = p.quoted_post.content_md || p.quoted_post.content_html || '';
            quoteBody.innerHTML = parseMessage(marked.parse(quotedMd));
            quoteDiv.append(quoteBody);
            body.append(quoteDiv);
        }

        const rawContent = p.content_md || p.content_html || '';
        const myBody = ce('div', 'my-body');
        myBody.innerHTML = parseMessage(marked.parse(rawContent));
        body.append(myBody);
    }

    if (!p.deleted && p.media_path) {
        const mediaWrapper = ce('div', 'media');
        let mediaEl;
        const ext = p.media_path.split('.').pop().toLowerCase();
        const mediaSrc = window.location.origin + '/' + p.media_path;

        if (['png', 'jpg', 'jpeg', 'gif', 'webp'].includes(ext)) mediaEl = ce('img');
        else if (['mp4', 'webm', 'ogg'].includes(ext)) mediaEl = ce('video'), mediaEl.controls = true;
        if (mediaEl) mediaEl.src = mediaSrc, mediaWrapper.append(mediaEl);
        body.append(mediaWrapper);
    }

    if (!p.deleted && p.nsfw) {
        body.style.filter = 'blur(var(--nsfw-blur))';
        body.style.cursor = 'pointer';
        body.title = 'NSFW: クリックで表示';
        body.addEventListener('click', () => { body.style.filter = ''; });
    }

    const buttons = ce('div', 'buttons');
    const like = ce('button', 'like-btn');
    like.textContent = '❤️' + (p.like_count || 0);
    if (p.liked) like.classList.add('liked');
    like.onclick = async () => { const r = await api('actions.php', { action: 'toggle_like', post_id: p.id }); if (r.ok) { p.liked = r.liked; p.like_count = r.count; updateLikeUI(p); } };

    const repost = ce('button'); repost.textContent = '♻️' + (p.repost_count || 0); if (p.reposted) repost.classList.add('reposted');
    repost.onclick = async () => { const r = await api('actions.php', { action: 'toggle_repost', post_id: p.id }); if (r.ok) { p.reposted = r.reposted; p.repost_count = r.count; refreshFeed(true); } };

    const bm = ce('button'); bm.textContent = '📑'; bm.onclick = async () => { const r = await api('actions.php', { action: 'toggle_bookmark', post_id: p.id }); if (!r.ok) alert('ブックマーク失敗'); };

    const rep = ce('button'); rep.textContent = '💬' + (p.reply_count || 0); rep.onclick = () => { window.location = 'replies.php?post_id=' + p.id; };
    const qt = ce('button'); qt.textContent = '❝ 引用'; qt.onclick = () => { const t = prompt('引用コメント'); if (t) quotePost(p.id, t); };

    let delBtn = null;
    if (p._can_delete && !p.deleted) {
        delBtn = ce('button'); delBtn.textContent = '削除';
        delBtn.onclick = async () => { if (!confirm('この投稿を削除しますか？')) return; const r = await api('actions.php', { action: 'delete_post', post_id: p.id }); if (r.ok) { p.deleted = true; updatePost(p); } else alert('削除失敗'); };
    }

    buttons.append(like, repost, bm, rep, qt); if (delBtn) buttons.append(delBtn);
    cnt.append(meta, body, buttons);
    post.append(av, cnt);

    if (prepend) wrap.prepend(post); else wrap.append(post);
}
*/


function renderPost(p, wrap, prepend = false) {
    console.log('renderPost data:', p); // ←デバッグ用

    const isRepost = !!p.is_repost_of;
    const orig = isRepost ? p.is_repost_of : null;

    // フレーム（元投稿フレーム優先）
    const frameClass = (orig && orig.frame_class) ? orig.frame_class : (p.frame_class || '');
    const post = ce('div', 'post ' + frameClass);
    post.dataset.postId = p.id;

    // アイコン（通常投稿は自分、リポストは元投稿優先）
    //const av = ce('img');
    //av.src = (isRepost && orig && orig.icon) ? orig.icon : (p.icon || '/uploads/icons/default_icon.png');
    //av.alt = (isRepost && orig && (orig.display_name || orig.handle)) || p.display_name || p.handle || 'unknown';
    //av.classList.add('avatar');
    // アイコン（通常投稿は自分のアイコンを補正、リポストは元投稿優先）
    const av = ce('img');

    // アイコンソース決定
    let iconSrc = (isRepost && orig && orig.icon) ? orig.icon : p.icon;

    // p.icon がデフォルトの場合は reposter.icon を使う
    if (!iconSrc || iconSrc.includes('default_icon.png')) {
        if (p.reposter && p.reposter.icon) iconSrc = p.reposter.icon;
    }

    av.src = iconSrc || '/uploads/icons/default_icon.png';

    // alt 表示名
    let altName = (isRepost && orig && (orig.display_name || orig.handle))
        || p.display_name || p.handle
        || (p.reposter && (p.reposter.display_name || p.reposter.handle))
        || 'unknown';

    av.alt = altName;
    av.classList.add('avatar');

    // コンテンツ
    const cnt = ce('div', 'content');

    // meta
    const meta = ce('div', 'meta');
    const displayName = (isRepost && orig && (orig.display_name || orig.handle)) || p.display_name || p.handle || 'unknown';
    const userId = (isRepost && orig && orig.user_id) || p.user_id;
    const handle = (isRepost && orig && orig.handle) || p.handle;
    const userRole = p.role || null;
    const userLink = userId ? `profile.php?id=${userId}` : `profile.php?handle=${encodeURIComponent(handle)}`;
    meta.innerHTML = `<a href="${userLink}" class="mention">${displayName}</a> @${handle}`;
    
    // Admin/Moderator badge display
    if (userRole === 'admin') {
        meta.innerHTML += ` <span class="role-badge admin-badge">ADMIN</span>`;
    } else if (userRole === 'mod') {
        meta.innerHTML += ` <span class="role-badge mod-badge">MOD</span>`;
    }
    
    // 称号表示
    if (p.title_text && p.title_css) {
        meta.innerHTML += ` <span class="user-title ${p.title_css}">${p.title_text}</span>`;
    }
    
    meta.innerHTML += ` ・ ${timeago(p.created_at)}`;

    if (p.vip_level && p.vip_level > 0) {
        meta.innerHTML += ` ・ <span class="vip-label">👑VIP${p.vip_level}</span>`;
    }

    // リポスト情報（リンク付き）
    if (isRepost && p.reposter) {
        const repName = p.reposter.display_name || p.reposter.handle || 'unknown';
        const repId = p.reposter.id;
        const repLink = repId ? `profile.php?id=${repId}` : `profile.php?handle=${encodeURIComponent(p.reposter.handle)}`;
        meta.innerHTML += `
        ・ <span class="repost-label">♲リポスト</span>
        <a href="${repLink}" class="mention"><strong>${repName}</strong></a>さんがリポストしました
    `;
    }


    if (p.deleted) meta.textContent += ' ・ 削除済み';

    // 本文
    const body = ce('div', 'body');
    if (p.deleted) {
        body.textContent = '削除済み';
    } else {
        if (p.quoted_post) {
            const quoteDiv = ce('div', 'quote');
            // クリックして引用元投稿へ遷移
            quoteDiv.style.cursor = 'pointer';
            quoteDiv.onclick = (e) => {
                e.stopPropagation();
                const quotedPostId = p.quoted_post.id || p.quote_post_id;
                if (quotedPostId) {
                    window.location.href = `replies_enhanced.php?post_id=${quotedPostId}`;
                }
            };
            const quoteMeta = ce('div', 'meta');
            const qDisplayName = p.quoted_post.display_name || p.quoted_post.handle || 'unknown';
            const qLink = p.quoted_post.user_id ? `profile.php?id=${p.quoted_post.user_id}` : `profile.php?handle=${encodeURIComponent(p.quoted_post.handle)}`;
            
            // 引用先がリポストの場合、リポスター情報を併記
            if (p.quoted_post.is_repost && p.quoted_post.reposter_handle) {
                const reposterDisplayName = p.quoted_post.reposter_display_name || p.quoted_post.reposter_handle || 'unknown';
                const reposterLink = p.quoted_post.reposter_user_id 
                    ? `profile.php?id=${p.quoted_post.reposter_user_id}` 
                    : `profile.php?handle=${encodeURIComponent(p.quoted_post.reposter_handle)}`;
                quoteMeta.innerHTML = `<a href="${qLink}" class="mention">${qDisplayName}</a> <span style="color: var(--muted); font-size: 0.85em;">（<a href="${reposterLink}" class="mention">${reposterDisplayName}</a>がリポスト）</span>`;
            } else {
                quoteMeta.innerHTML = `<a href="${qLink}" class="mention">${qDisplayName}</a>`;
            }
            quoteDiv.append(quoteMeta);

            const quoteBody = ce('div', 'quote-body');
            // Use content_md for markdown parsing if available for quoted posts
            if (p.quoted_post.content_md) {
                quoteBody.innerHTML = embedYouTube(parseMessage(marked.parse(p.quoted_post.content_md)));
            } else if (p.quoted_post.content_html) {
                quoteBody.innerHTML = embedYouTube(p.quoted_post.content_html);
            } else {
                quoteBody.innerHTML = '';
            }
            quoteDiv.append(quoteBody);

            body.append(quoteDiv);
        }

        // Use content_md for markdown parsing if available, otherwise fallback to content_html
        const myBody = ce('div', 'my-body');
        if (p.content_md) {
            // Always parse markdown when content_md is available
            // This ensures markdown formatting works in the feed
            myBody.innerHTML = embedYouTube(parseMessage(marked.parse(p.content_md)));
        } else if (p.content_html) {
            // Fallback to content_html if content_md is not available
            myBody.innerHTML = embedYouTube(p.content_html);
        } else {
            myBody.innerHTML = '';
        }
        body.append(myBody);
    }

    // メディア（複数画像対応）
    if (!p.deleted && (p.media_paths || p.media_path)) {
        const mediaWrapper = ce('div', 'media-wrapper');
        
        // 複数画像がある場合
        if (p.media_paths && Array.isArray(p.media_paths) && p.media_paths.length > 0) {
            const mediaGrid = ce('div', 'media-grid');
            mediaGrid.classList.add(`media-count-${Math.min(p.media_paths.length, MAX_MEDIA_FILES)}`);
            
            p.media_paths.forEach((mediaPath, index) => {
                if (index >= MAX_MEDIA_FILES) return; // 最大4枚まで
                const mediaContainer = ce('div', 'media-item');
                const ext = mediaPath.split('.').pop().toLowerCase();
                const mediaSrc = window.location.origin + '/' + mediaPath;
                
                // 画像フォーマット
                const imageExts = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp', 'svg', 'ico', 'avif', 'heic', 'heif'];
                // 動画フォーマット
                const videoExts = ['mp4', 'webm', 'mov', 'avi', 'mkv', 'm4v', 'flv', 'wmv', 'ogv', 'ogg'];
                // 音声フォーマット
                const audioExts = ['mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac', 'wma', 'opus'];
                // ドキュメントフォーマット
                const documentExts = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip', 'rar', '7z', 'tar', 'gz'];
                
                let mediaEl;
                let mediaType;
                if (imageExts.includes(ext)) {
                    mediaEl = ce('img');
                    mediaEl.loading = 'lazy';
                    mediaType = 'image';
                } else if (videoExts.includes(ext)) {
                    mediaEl = ce('video');
                    mediaEl.controls = true;
                    mediaType = 'video';
                } else if (audioExts.includes(ext)) {
                    mediaEl = ce('audio');
                    mediaEl.controls = true;
                    mediaType = 'audio';
                } else if (documentExts.includes(ext)) {
                    // ドキュメントファイル用のリンク
                    mediaEl = ce('a');
                    mediaEl.href = mediaSrc;
                    mediaEl.download = mediaPath.split('/').pop();
                    mediaEl.target = '_blank';
                    mediaEl.className = 'document-link';
                    mediaEl.innerHTML = `📄 ${mediaPath.split('/').pop()}`;
                    mediaType = 'document';
                }
                
                if (mediaEl) {
                    if (mediaType !== 'document') {
                        mediaEl.src = mediaSrc;
                        mediaEl.style.cursor = 'pointer';
                        mediaEl.onclick = (e) => {
                            e.stopPropagation();
                            openMediaExpand(mediaSrc, mediaType);
                        };
                    }
                    mediaContainer.append(mediaEl);
                    mediaGrid.append(mediaContainer);
                }
            });
            
            mediaWrapper.append(mediaGrid);
            body.append(mediaWrapper);
        } 
        // 単一画像の場合（後方互換性）
        else if (p.media_path) {
            const mediaContainer = ce('div', 'media');
            let mediaEl;
            const ext = p.media_path.split('.').pop().toLowerCase();
            const mediaSrc = window.location.origin + '/' + p.media_path;

            // 画像フォーマット
            const imageExts = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp', 'svg', 'ico', 'avif', 'heic', 'heif'];
            // 動画フォーマット
            const videoExts = ['mp4', 'webm', 'mov', 'avi', 'mkv', 'm4v', 'flv', 'wmv', 'ogv', 'ogg'];
            // 音声フォーマット
            const audioExts = ['mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac', 'wma', 'opus'];
            // ドキュメントフォーマット
            const documentExts = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip', 'rar', '7z', 'tar', 'gz'];

            let mediaType;
            if (imageExts.includes(ext)) {
                mediaEl = ce('img');
                mediaType = 'image';
            } else if (videoExts.includes(ext)) {
                mediaEl = ce('video');
                mediaEl.controls = true;
                mediaType = 'video';
            } else if (audioExts.includes(ext)) {
                mediaEl = ce('audio');
                mediaEl.controls = true;
                mediaType = 'audio';
            } else if (documentExts.includes(ext)) {
                // ドキュメントファイル用のリンク
                mediaEl = ce('a');
                mediaEl.href = mediaSrc;
                mediaEl.download = p.media_path.split('/').pop();
                mediaEl.target = '_blank';
                mediaEl.className = 'document-link';
                mediaEl.innerHTML = `📄 ${p.media_path.split('/').pop()}`;
                mediaType = 'document';
            }
            
            if (mediaEl) {
                if (mediaType !== 'document') {
                    mediaEl.src = mediaSrc;
                    mediaEl.style.cursor = 'pointer';
                    mediaEl.onclick = (e) => {
                        e.stopPropagation();
                        openMediaExpand(mediaSrc, mediaType);
                    };
                }
                mediaContainer.append(mediaEl);
            }

            mediaWrapper.append(mediaContainer);
            body.append(mediaWrapper);
        }
    }

    // NSFW 本文・メディアぼかし
    if (!p.deleted && p.nsfw) {
        [body, typeof mediaWrapper !== 'undefined' ? mediaWrapper : null].forEach(el => {
            if (!el) return;
            el.style.filter = 'blur(var(--nsfw-blur))';
            el.style.cursor = 'pointer';
            el.title = 'NSFW: クリックで表示';
            el.addEventListener('click', () => { el.style.filter = ''; });
        });
    }

    // ボタン類
    const buttons = ce('div', 'buttons');

    const like = ce('button', 'like-btn');
    like.textContent = '❤️' + (p.like_count || 0);
    if (p.liked) like.classList.add('liked');
    like.onclick = async () => {
        const r = await api('actions.php', { action: 'toggle_like', post_id: p.id });
        if (r.ok) { p.liked = r.liked; p.like_count = r.count; updateLikeUI(p); }
    };

    // リポストボタン
    const repost = ce('button');
    repost.textContent = '♻️' + (p.repost_count || 0);
    if (p.reposted) repost.classList.add('reposted');

    // ★ここでリポスト不可なら非表示にする
    // p.is_repost_of が存在する場合、再リポスト不可
    if (p.is_repost_of !== null) {
        repost.style.display = 'none';
    } else {
        repost.onclick = async () => {
            const r = await api('actions.php', { action: 'toggle_repost', post_id: p.id });
            if (r.ok) { p.reposted = r.reposted; p.repost_count = r.count; refreshFeed(true); }
        };
    }

    const bm = ce('button');
    bm.textContent = '📑';
    bm.onclick = async () => { const r = await api('actions.php', { action: 'toggle_bookmark', post_id: p.id }); if (!r.ok) alert('ブックマーク失敗'); };

    // ブーストボタン
    const boost = ce('button', 'boost-btn');
    boost.textContent = '🔥' + (p.boost_count || 0);
    boost.onclick = async () => {
        if (!confirm('この投稿をブーストしますか？（コイン200 + クリスタル20）')) return;
        const r = await api('boost_api.php', { action: 'boost', post_id: p.id });
        if (r.ok) { 
            p.boost_count = r.boost_count; 
            boost.textContent = '🔥' + (p.boost_count || 0);
            // 通貨表示を更新
            if (qs('#coins')) qs('#coins').textContent = r.remaining.coins;
            if (qs('#crystals')) qs('#crystals').textContent = r.remaining.crystals;
            alert('ブーストしました！');
        } else {
            if (r.error === 'boost_expired') {
                alert('ブースト期限を過ぎているためブーストできません');
            } else {
                alert('ブースト失敗: ' + (r.message || r.error || 'unknown'));
            }
        }
    };

    const rep = ce('button');
    rep.textContent = '💬' + (p.reply_count || 0);
    rep.onclick = () => { window.location = 'replies.php?post_id=' + p.id; };

    const qt = ce('button');
    qt.textContent = '❝ 引用';
    qt.onclick = () => { showQuoteModal(p); };

    let delBtn = null;
    if (p._can_delete && !p.deleted) {
        delBtn = ce('button');
        delBtn.textContent = '削除';
        delBtn.onclick = async () => {
            if (!confirm('この投稿を削除しますか？')) return;
            const r = await api('actions.php', { action: 'delete_post', post_id: p.id });
            if (r.ok) { p.deleted = true; updatePost(p); }
            else alert('削除失敗');
        };
    }
    
    // 通報ボタン
    const reportBtn = ce('button', 'report-btn');
    reportBtn.textContent = '🚨 通報';
    reportBtn.onclick = async () => {
        await showReportDialog(p.id);
    };

    buttons.append(like, repost, bm, boost, rep, qt, reportBtn);
    if (delBtn) buttons.append(delBtn);
    cnt.append(meta, body, buttons);
    post.append(av, cnt);

    if (prepend) wrap.prepend(post);
    else wrap.append(post);
}

// 通報ダイアログを表示
async function showReportDialog(postId) {
    const reasons = [
        'スパム',
        'ハラスメント・いじめ',
        '暴力的な内容',
        'ヘイトスピーチ',
        '性的なコンテンツ',
        '誤情報',
        '著作権侵害',
        'その他'
    ];
    
    let reasonHtml = '';
    reasons.forEach((r, i) => {
        reasonHtml += `<option value="${r}">${r}</option>`;
    });
    
    const dialog = document.createElement('div');
    dialog.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; z-index: 10000;';
    dialog.innerHTML = `
        <div style="background: var(--card); border-radius: 12px; padding: 30px; max-width: 500px; width: 90%; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
            <h3 style="margin: 0 0 20px 0; color: var(--text);">投稿を通報</h3>
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 8px; font-weight: bold; color: var(--text);">通報理由（必須）</label>
                <select id="reportReason" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 6px; background: var(--bg); color: var(--text);">
                    ${reasonHtml}
                </select>
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: bold; color: var(--text);">詳細（任意）</label>
                <textarea id="reportDetails" rows="4" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 6px; background: var(--bg); color: var(--text); resize: vertical;" placeholder="詳細な説明を入力してください（任意）"></textarea>
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button id="reportCancel" style="padding: 10px 20px; border: 1px solid var(--border); border-radius: 6px; background: var(--bg); color: var(--text); cursor: pointer;">キャンセル</button>
                <button id="reportSubmit" style="padding: 10px 20px; border: none; border-radius: 6px; background: #f56565; color: white; cursor: pointer; font-weight: bold;">通報する</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(dialog);
    
    document.getElementById('reportCancel').onclick = () => {
        document.body.removeChild(dialog);
    };
    
    document.getElementById('reportSubmit').onclick = async () => {
        const reason = document.getElementById('reportReason').value;
        const details = document.getElementById('reportDetails').value;
        
        if (!reason) {
            alert('通報理由を選択してください');
            return;
        }
        
        const r = await api('report_api.php', {
            action: 'submit_report',
            post_id: postId,
            reason: reason,
            details: details
        });
        
        if (r.ok) {
            alert('通報を受け付けました');
            document.body.removeChild(dialog);
        } else {
            if (r.error === 'already_reported') {
                alert('この投稿は既に通報済みです');
            } else {
                alert('通報に失敗しました: ' + (r.message || r.error));
            }
        }
    };
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
        
        const r = await api('appeal_api.php', {
            action: 'submit_appeal',
            reason: reason
        });
        
        if (r.ok) {
            alert('異議申し立てを受け付けました。管理者が審査します。');
            document.body.removeChild(dialog);
        } else {
            alert('異議申し立てに失敗しました: ' + (r.message || r.error));
        }
    };
}


// 3秒ごとに差分取得
setInterval(() => refreshFeedPartial(), 3000);

// ---------------------
// Polling
// ---------------------
//setInterval(() => refreshFeed(false), 3000);

// ---------------------
// Media Expand Modal
// ---------------------
const mediaExpandModal = document.getElementById('mediaExpandModal');
const mediaExpandContent = document.getElementById('mediaExpandContent');
const mediaExpandClose = document.querySelector('.media-expand-close');

function openMediaExpand(mediaSrc, mediaType) {
    mediaExpandContent.innerHTML = '';
    
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
        mediaEl.onclick = (e) => e.stopPropagation(); // クリックで閉じないようにする
        mediaExpandContent.appendChild(mediaEl);
        mediaExpandModal.classList.add('active');
    }
}

function closeMediaExpand() {
    mediaExpandModal.classList.remove('active');
    mediaExpandContent.innerHTML = '';
}

// Close on click outside
if (mediaExpandModal) {
    mediaExpandModal.addEventListener('click', closeMediaExpand);
}

if (mediaExpandClose) {
    mediaExpandClose.addEventListener('click', closeMediaExpand);
}

// Close on ESC key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && mediaExpandModal.classList.contains('active')) {
        closeMediaExpand();
    }
});

// 初回ロード
//refreshFeed(true);