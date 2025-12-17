<?php
// 管理者用パスワードリセット管理ページ
require_once __DIR__ . '/config.php';

// ログインチェック
$me = user();
if (!$me) {
    header('Location: login.php');
    exit;
}

// 管理者権限チェック (id = 1)
if ((int)$me['id'] !== 1) {
    echo "<!doctype html><html><head><meta charset='utf-8'><title>Access Denied</title></head><body>";
    echo "<h1>Access Denied</h1>";
    echo "<p>このページは管理者専用です。</p>";
    echo "<a href='index.php'>← トップページに戻る</a>";
    echo "</body></html>";
    exit;
}
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>パスワードリセット管理 - MiniBird</title>
<link rel="stylesheet" href="assets/style.css">
<style>
body {
    background: var(--bg);
    color: var(--text);
}

.admin-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.admin-header {
    background: var(--card);
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    border: 1px solid var(--border);
}

.admin-header h1 {
    margin: 0 0 10px 0;
    color: var(--text);
}

.admin-header .back-link {
    color: var(--blue);
    text-decoration: none;
}

.admin-header .back-link:hover {
    text-decoration: underline;
}

.tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    background: var(--card);
    padding: 10px;
    border-radius: 8px;
    border: 1px solid var(--border);
}

.tab {
    padding: 10px 20px;
    border: none;
    background: transparent;
    color: var(--muted);
    cursor: pointer;
    border-radius: 6px;
    font-size: 16px;
    transition: all 0.3s;
}

.tab:hover {
    background: rgba(29, 155, 240, 0.1);
    color: var(--blue);
}

.tab.active {
    background: var(--blue);
    color: white;
    font-weight: bold;
}

.requests-container {
    background: var(--card);
    padding: 20px;
    border-radius: 8px;
    border: 1px solid var(--border);
}

.request-card {
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
}

.request-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.request-id {
    font-weight: bold;
    color: var(--blue);
}

.status-badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: bold;
}

.status-pending {
    background: rgba(255, 165, 0, 0.2);
    color: #ffa500;
}

.status-approved {
    background: rgba(0, 186, 124, 0.2);
    color: var(--green);
}

.status-rejected {
    background: rgba(249, 24, 128, 0.2);
    color: var(--red);
}

.request-info {
    margin-bottom: 10px;
}

.request-info p {
    margin: 5px 0;
    color: var(--text);
}

.request-info strong {
    color: var(--muted);
}

.request-reason {
    background: var(--card);
    padding: 10px;
    border-radius: 6px;
    margin: 10px 0;
    color: var(--text);
    border-left: 3px solid var(--blue);
}

.request-actions {
    display: flex;
    gap: 10px;
    margin-top: 10px;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
    transition: all 0.3s;
}

.btn-approve {
    background: var(--green);
    color: white;
}

.btn-approve:hover {
    opacity: 0.8;
}

.btn-reject {
    background: var(--red);
    color: white;
}

.btn-reject:hover {
    opacity: 0.8;
}

.empty-state {
    text-align: center;
    padding: 40px;
    color: var(--muted);
}

/* モーダル */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.8);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal.show {
    display: flex;
}

.modal-content {
    background: var(--card);
    padding: 30px;
    border-radius: 12px;
    max-width: 500px;
    width: 90%;
    border: 1px solid var(--border);
}

.modal-content h3 {
    margin: 0 0 20px 0;
    color: var(--text);
}

.modal-content textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid var(--border);
    border-radius: 6px;
    background: var(--bg);
    color: var(--text);
    font-family: inherit;
    resize: vertical;
    min-height: 100px;
}

.modal-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.btn-cancel {
    background: var(--muted);
    color: white;
}

.btn-cancel:hover {
    opacity: 0.8;
}

.loading {
    text-align: center;
    padding: 20px;
    color: var(--muted);
}

@media (max-width: 768px) {
    .admin-container {
        padding: 10px;
    }
    
    .request-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .request-actions {
        flex-direction: column;
    }
    
    .tabs {
        overflow-x: auto;
    }
}
</style>
</head>
<body>
<div class="admin-container">
    <div class="admin-header">
        <h1>パスワードリセット管理</h1>
        <a href="index.php" class="back-link">← トップページに戻る</a>
    </div>
    
    <div class="tabs">
        <button class="tab active" data-status="pending">Pending</button>
        <button class="tab" data-status="approved">Approved</button>
        <button class="tab" data-status="rejected">Rejected</button>
        <button class="tab" data-status="all">All</button>
    </div>
    
    <div class="requests-container">
        <div id="requestsList" class="loading">読み込み中...</div>
    </div>
</div>

<!-- モーダル -->
<div id="commentModal" class="modal">
    <div class="modal-content">
        <h3 id="modalTitle">コメント入力</h3>
        <textarea id="adminComment" placeholder="管理者コメント（オプション）"></textarea>
        <div class="modal-actions">
            <button id="confirmBtn" class="btn">確認</button>
            <button id="cancelBtn" class="btn btn-cancel">キャンセル</button>
        </div>
    </div>
</div>

<script>
let currentStatus = 'pending';
let currentAction = null;
let currentRequestId = null;

// タブ切り替え
document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        currentStatus = tab.dataset.status;
        loadRequests();
    });
});

// リクエスト一覧取得
async function loadRequests() {
    const listDiv = document.getElementById('requestsList');
    listDiv.innerHTML = '<div class="loading">読み込み中...</div>';
    
    try {
        const res = await fetch('admin_password_reset_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'list_requests',
                status: currentStatus
            })
        });
        
        const data = await res.json();
        
        if (!data.ok) {
            throw new Error(data.error || 'Failed to load requests');
        }
        
        displayRequests(data.requests);
    } catch (err) {
        listDiv.innerHTML = `<div class="empty-state">エラー: ${err.message}</div>`;
    }
}

// リクエスト表示
function displayRequests(requests) {
    const listDiv = document.getElementById('requestsList');
    
    if (!requests || requests.length === 0) {
        listDiv.innerHTML = '<div class="empty-state">申請がありません</div>';
        return;
    }
    
    listDiv.innerHTML = requests.map(req => {
        const statusClass = `status-${req.status}`;
        const statusText = req.status === 'pending' ? '保留中' : 
                          req.status === 'approved' ? '承認済み' : '却下済み';
        
        let actionsHTML = '';
        if (req.status === 'pending') {
            actionsHTML = `
                <div class="request-actions">
                    <button class="btn btn-approve" onclick="showModal('approve', ${req.id})">承認</button>
                    <button class="btn btn-reject" onclick="showModal('reject', ${req.id})">却下</button>
                </div>
            `;
        }
        
        let reviewInfo = '';
        if (req.reviewed_at) {
            const reviewedAt = new Date(req.reviewed_at).toLocaleString('ja-JP');
            reviewInfo = `
                <p><strong>審査日時:</strong> ${reviewedAt}</p>
                ${req.reviewer_handle ? `<p><strong>審査者:</strong> @${req.reviewer_handle}</p>` : ''}
                ${req.admin_comment ? `<p><strong>管理者コメント:</strong> ${req.admin_comment}</p>` : ''}
            `;
        }
        
        const requestedAt = new Date(req.requested_at).toLocaleString('ja-JP');
        
        return `
            <div class="request-card">
                <div class="request-header">
                    <span class="request-id">Request #${req.id}</span>
                    <span class="status-badge ${statusClass}">${statusText}</span>
                </div>
                <div class="request-info">
                    <p><strong>ユーザー:</strong> @${req.handle} (ID: ${req.user_id})</p>
                    <p><strong>申請日時:</strong> ${requestedAt}</p>
                </div>
                <div class="request-reason">
                    <strong>申請理由:</strong><br>
                    ${req.reason.replace(/\n/g, '<br>')}
                </div>
                ${reviewInfo}
                ${actionsHTML}
            </div>
        `;
    }).join('');
}

// モーダル表示
function showModal(action, requestId) {
    currentAction = action;
    currentRequestId = requestId;
    
    const modal = document.getElementById('commentModal');
    const title = document.getElementById('modalTitle');
    const confirmBtn = document.getElementById('confirmBtn');
    
    if (action === 'approve') {
        title.textContent = '申請を承認';
        confirmBtn.textContent = '承認する';
        confirmBtn.className = 'btn btn-approve';
    } else {
        title.textContent = '申請を却下';
        confirmBtn.textContent = '却下する';
        confirmBtn.className = 'btn btn-reject';
    }
    
    document.getElementById('adminComment').value = '';
    modal.classList.add('show');
}

// モーダルを閉じる
function closeModal() {
    document.getElementById('commentModal').classList.remove('show');
    currentAction = null;
    currentRequestId = null;
}

document.getElementById('cancelBtn').addEventListener('click', closeModal);

// 確認ボタン
document.getElementById('confirmBtn').addEventListener('click', async () => {
    const comment = document.getElementById('adminComment').value.trim();
    
    try {
        const action = currentAction === 'approve' ? 'approve_request' : 'reject_request';
        
        const res = await fetch('admin_password_reset_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: action,
                request_id: currentRequestId,
                admin_comment: comment
            })
        });
        
        const data = await res.json();
        
        if (!data.ok) {
            throw new Error(data.error || 'Failed to process request');
        }
        
        closeModal();
        loadRequests(); // リストを再読み込み
        
        alert(data.message || '処理が完了しました');
    } catch (err) {
        alert(`エラー: ${err.message}`);
    }
});

// モーダル外クリックで閉じる
document.getElementById('commentModal').addEventListener('click', (e) => {
    if (e.target.id === 'commentModal') {
        closeModal();
    }
});

// 初回読み込み
loadRequests();
</script>
</body>
</html>
