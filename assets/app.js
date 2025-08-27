const state = {
    feed: 'global',
    posts: [],
    newestId: 0,
    oldestId: 0,
    isLoading: false,
    hasMore: true
};

// -------------------------
// parseMessage
// MarkdownをHTMLに変換した後、メンションやリンクを処理
// -------------------------
function parseMessage(html) {
    // メンション @username をリンクに変換
    html = html.replace(/@([a-zA-Z0-9_]+)/g, (m, user) => {
        return `<a href="/user/${user}" class="link">@${user}</a>`;
    });

    // URLを自動リンク化
    html = html.replace(/(https?:\/\/[^\s]+)/g, (m, url) => {
        return `<a href="${url}" target="_blank" class="link">${url}</a>`;
    });

    return html;
}


function qs(sel) { return document.querySelector(sel) }
function ce(tag, cls) { const el = document.createElement(tag); if (cls) el.className = cls; return el }
function timeago(ts) { return new Date(ts).toLocaleString() }
async function api(path, data) {
    const res = await fetch(path, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data), credentials: 'include' });
    return res.json();
}

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
    if (qs('#media').files[0]) fd.append('media', qs('#media').files[0]);
    const r = await fetch('post.php', { method: 'POST', body: fd, credentials: 'include' }).then(r => r.json());
    if (r.ok) { qs('#postText').value = ''; qs('#media').value = null; refreshFeed(true); } else alert('投稿失敗: ' + r.error);
});

// ---------------------
// Feed switching
// ---------------------
document.querySelectorAll('.tabBtn, .feedTab').forEach(btn => {
    btn.addEventListener('click', async () => {
        const feed = btn.dataset.tab || btn.dataset.feed;
        if (!feed) return;
        state.feed = feed;
        qs('#feed').dataset.feed = feed;
        document.querySelectorAll('.tabBtn, .feedTab').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        await refreshFeed(true);
    });
});

const feedEl = qs('#feed');

// ---------------------
// Feed Handling
// ---------------------
async function refreshFeed(reset = false) {
    if (state.isLoading) return;
    state.isLoading = true;
    qs('#loading').style.display = 'block';
    try {
        const r = await api('feed.php', { action: 'fetch', feed: state.feed, since_id: reset ? 0 : state.newestId, limit: 50 });
        if (r.ok) {
            if (reset) state.posts = r.items.map(p => ({ ...p }));
            else {
                r.items.forEach(p => {
                    if (!state.posts.some(existing => existing.id === p.id)) {
                        state.posts.unshift(p);
                    }
                });
            }
            if (state.posts.length) {
                state.newestId = Math.max(...state.posts.map(p => p.id));
                state.oldestId = Math.min(...state.posts.map(p => p.id));
            }
            renderFeed();
        } else { feedEl.innerHTML = '<div>読み込みエラー</div>'; }
    } catch (e) { feedEl.innerHTML = '<div>通信エラー</div>'; console.error(e); }
    qs('#loading').style.display = 'none';
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
//-----------
//Render
//-----------

function renderPost(p, wrap) {
    const post = ce('div', 'post ' + (p.frame_class || ''));
    post.dataset.postId = p.id;

    const av = ce('div', 'avatar');
    av.textContent = p.handle[0]?.toUpperCase() || 'U';

    const cnt = ce('div', 'content');
    const meta = ce('div', 'meta');
    meta.textContent = '@' + p.handle + ' ・ ' + timeago(p.created_at);
    if (p.is_repost_of) meta.textContent += ` ・ ${p.reposter}さんがリポストしました`;
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

            const quoteMeta = ce('div', 'meta');
            quoteMeta.textContent = `引用元: @${p.quoted_post.handle}`;
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
    // メディア表示
    // -------------------------
    if (!p.deleted && p.media_path) {
        const mediaWrapper = ce('div', 'media');
        let mediaEl;
        const ext = p.media_path.split('.').pop().toLowerCase();
        const mediaSrc = window.location.origin + '/' + p.media_path;

        if (['png', 'jpg', 'jpeg', 'gif', 'webp'].includes(ext)) mediaEl = ce('img');
        else if (['mp4', 'webm', 'ogg'].includes(ext)) mediaEl = ce('video'), mediaEl.controls = true;
        if (mediaEl) mediaEl.src = mediaSrc, mediaWrapper.append(mediaEl);

        if (p.nsfw) {
            mediaWrapper.style.filter = 'blur(var(--nsfw-blur))';
            mediaWrapper.style.cursor = 'pointer';
            mediaWrapper.title = 'NSFW: クリックで表示';
            mediaWrapper.addEventListener('click', () => { mediaWrapper.style.filter = ''; });
        }

        body.append(mediaWrapper);
    }

    // -------------------------
    // NSFW テキストぼかし
    // -------------------------
    if (p.nsfw && !p.deleted) {
        body.style.filter = 'blur(var(--nsfw-blur))';
        body.style.cursor = 'pointer';
        body.title = 'NSFW: クリックで表示';
        body.addEventListener('click', () => { body.style.filter = ''; });
    }

    // -------------------------
    // ボタン類
    // -------------------------
    const buttons = ce('div', 'buttons');
    const like = ce('button'); like.textContent = '♥ ' + (p.like_count || 0); if (p.liked) like.classList.add('liked');
    like.onclick = async () => { const r = await api('actions.php', { action: 'toggle_like', post_id: p.id }); if (r.ok) { p.liked = r.liked; p.like_count = r.count; updatePost(p); } };

    const repost = ce('button'); repost.textContent = '⤴ ' + (p.repost_count || 0); if (p.reposted) repost.classList.add('reposted');
    repost.onclick = async () => { const r = await api('actions.php', { action: 'toggle_repost', post_id: p.id }); if (r.ok) { p.reposted = r.reposted; p.repost_count = r.count; refreshFeed(true); } };

    const bm = ce('button'); bm.textContent = '🔖'; bm.onclick = async () => { const r = await api('actions.php', { action: 'toggle_bookmark', post_id: p.id }); if (!r.ok) alert('ブックマーク失敗'); };

    const rep = ce('button'); rep.textContent = '💬 ' + (p.reply_count || 0); rep.onclick = () => { window.location = 'replies.php?post_id=' + p.id; };
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

async function quotePost(post_id, text) {
    const r = await api('post.php', { action: 'quote_post', post_id, content: text });
    if (r.ok) refreshFeed(true); else alert('引用失敗: ' + r.error);
}

// ---------------------
// Polling
// ---------------------
setInterval(() => refreshFeed(false), 3000);

// 初回ロード
refreshFeed(true);
