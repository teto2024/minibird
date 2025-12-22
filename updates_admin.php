<?php
require_once __DIR__ . '/config.php';
require_login();

$me = user();
$pdo = db();

// 管理者チェック
if ($me['role'] !== 'admin') {
    http_response_code(403);
    echo "管理者権限が必要です";
    exit;
}

// アップデート情報取得（すべて）
$stmt = $pdo->prepare("
    SELECT u.*, us.handle as creator_handle, us.display_name as creator_name
    FROM updates u
    LEFT JOIN users us ON us.id = u.created_by
    ORDER BY u.created_at DESC
");
$stmt->execute();
$updates = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>アップデート管理 - MiniBird</title>
<link rel="stylesheet" href="assets/style.css?v=<?= ASSETS_VERSION ?>">
<style>
body {
  margin: 0;
  min-height: 100vh;
  background: linear-gradient(135deg, #0d0d0d 0%, #1a1a2e 50%, #16213e 100%);
  color: #fff;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.page-header {
  text-align: center;
  margin-bottom: 40px;
}

.page-header h1 {
  font-size: 2.5rem;
  margin: 0 0 10px 0;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  font-weight: bold;
}

.action-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
  gap: 16px;
  flex-wrap: wrap;
}

.btn {
  padding: 12px 24px;
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
}

.btn-secondary {
  background: rgba(160, 160, 192, 0.2);
  color: #a0a0c0;
  border: 1px solid rgba(160, 160, 192, 0.3);
}

.btn-secondary:hover {
  background: rgba(160, 160, 192, 0.3);
}

.btn-danger {
  background: linear-gradient(135deg, #f56565 0%, #c53030 100%);
  color: white;
}

.updates-table {
  background: linear-gradient(135deg, rgba(30, 30, 50, 0.95) 0%, rgba(20, 20, 35, 0.95) 100%);
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 10px 40px rgba(0,0,0,0.6);
  border: 1px solid rgba(102, 126, 234, 0.2);
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th {
  text-align: left;
  padding: 16px 12px;
  border-bottom: 2px solid rgba(102, 126, 234, 0.3);
  color: #a0aeff;
  font-weight: 600;
  font-size: 0.9rem;
  text-transform: uppercase;
}

td {
  padding: 16px 12px;
  border-bottom: 1px solid rgba(102, 126, 234, 0.1);
  color: #e0e0e0;
}

tr:hover {
  background: rgba(102, 126, 234, 0.05);
}

.status-badge {
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 0.8rem;
  font-weight: 600;
}

.status-published {
  background: rgba(72, 187, 120, 0.3);
  color: #68d391;
  border: 1px solid rgba(72, 187, 120, 0.5);
}

.status-draft {
  background: rgba(160, 160, 192, 0.2);
  color: #a0a0c0;
  border: 1px solid rgba(160, 160, 192, 0.3);
}

.category-badge {
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 0.8rem;
  font-weight: 600;
}

.category-feature {
  background: rgba(72, 187, 120, 0.2);
  color: #68d391;
}

.category-bugfix {
  background: rgba(245, 101, 101, 0.2);
  color: #fc8181;
}

.category-improvement {
  background: rgba(102, 126, 234, 0.2);
  color: #a0aeff;
}

.category-announcement {
  background: rgba(237, 137, 54, 0.2);
  color: #f6ad55;
}

.action-buttons {
  display: flex;
  gap: 8px;
}

.btn-small {
  padding: 6px 12px;
  font-size: 0.85rem;
  border-radius: 6px;
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
  justify-content: center;
  align-items: center;
  padding: 20px;
  overflow-y: auto;
}

.modal.active {
  display: flex;
}

.modal-content {
  background: linear-gradient(135deg, rgba(30, 30, 50, 0.98) 0%, rgba(20, 20, 35, 0.98) 100%);
  border-radius: 16px;
  padding: 32px;
  max-width: 700px;
  width: 100%;
  box-shadow: 0 20px 60px rgba(0,0,0,0.8);
  border: 1px solid rgba(102, 126, 234, 0.3);
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  font-size: 1.8rem;
  margin-bottom: 24px;
  color: #fff;
  font-weight: bold;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  color: #a0aeff;
  font-weight: 600;
  font-size: 0.95rem;
}

.form-group input[type="text"],
.form-group textarea,
.form-group select {
  width: 100%;
  padding: 12px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(102, 126, 234, 0.3);
  border-radius: 8px;
  color: #fff;
  font-size: 1rem;
  font-family: inherit;
  transition: all 0.3s;
}

.form-group input[type="text"]:focus,
.form-group textarea:focus,
.form-group select:focus {
  outline: none;
  border-color: #667eea;
  background: rgba(255, 255, 255, 0.08);
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
}

.form-group textarea {
  min-height: 200px;
  resize: vertical;
}

.checkbox-group {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: 16px;
}

.checkbox-group input[type="checkbox"] {
  width: 20px;
  height: 20px;
  cursor: pointer;
}

.checkbox-group label {
  margin: 0;
  cursor: pointer;
}

.modal-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 24px;
}

.empty-state {
  text-align: center;
  padding: 60px 20px;
  color: #a0a0c0;
}
</style>
</head>
<body>
<header class="topbar">
  <div class="logo"><a class="link" href="admin_unified.php">← 管理画面に戻る</a></div>
</header>

<div class="container">
  <div class="page-header">
    <h1>📢 アップデート情報管理</h1>
    <p style="color: #a0a0c0;">ユーザーへのアップデート情報を管理します</p>
  </div>

  <div class="action-bar">
    <button class="btn btn-primary" onclick="showCreateModal()">
      ✨ 新規作成
    </button>
    <a href="updates.php" class="btn btn-secondary" target="_blank">
      👁️ ユーザー画面を見る
    </a>
  </div>

  <?php if ($updates): ?>
  <div class="updates-table">
    <table>
      <thead>
        <tr>
          <th>タイトル</th>
          <th>カテゴリ</th>
          <th>バージョン</th>
          <th>ステータス</th>
          <th>作成日</th>
          <th>操作</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($updates as $update): 
          $category_labels = [
            'feature' => '✨ 新機能',
            'bugfix' => '🐛 バグ修正',
            'improvement' => '🔧 改善',
            'announcement' => '📢 お知らせ'
          ];
        ?>
        <tr>
          <td><strong><?= htmlspecialchars($update['title']) ?></strong></td>
          <td><span class="category-badge category-<?= $update['category'] ?>"><?= $category_labels[$update['category']] ?? $update['category'] ?></span></td>
          <td><?= $update['version'] ? 'v' . htmlspecialchars($update['version']) : '-' ?></td>
          <td>
            <span class="status-badge <?= $update['is_published'] ? 'status-published' : 'status-draft' ?>">
              <?= $update['is_published'] ? '公開中' : '下書き' ?>
            </span>
          </td>
          <td><?= date('Y/m/d', strtotime($update['created_at'])) ?></td>
          <td>
            <div class="action-buttons">
              <button class="btn btn-small btn-secondary" 
                      data-update-id="<?= $update['id'] ?>"
                      data-update-title="<?= htmlspecialchars($update['title']) ?>"
                      data-update-content="<?= htmlspecialchars($update['content']) ?>"
                      data-update-category="<?= htmlspecialchars($update['category']) ?>"
                      data-update-version="<?= htmlspecialchars($update['version'] ?? '') ?>"
                      data-update-published="<?= $update['is_published'] ? '1' : '0' ?>"
                      onclick="editUpdateFromData(this)">
                編集
              </button>
              <button class="btn btn-small btn-danger" onclick="deleteUpdate(<?= $update['id'] ?>)">
                削除
              </button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="empty-state">
    <div style="font-size: 4rem; margin-bottom: 20px;">📭</div>
    <p>まだアップデート情報がありません</p>
    <p style="font-size: 0.9rem; margin-top: 10px;">「新規作成」ボタンから最初のアップデートを作成しましょう</p>
  </div>
  <?php endif; ?>
</div>

<!-- 作成/編集モーダル -->
<div id="updateModal" class="modal">
  <div class="modal-content">
    <h2 class="modal-header" id="modalTitle">アップデート情報を作成</h2>
    <form id="updateForm">
      <input type="hidden" id="updateId" name="id">
      
      <div class="form-group">
        <label for="title">タイトル *</label>
        <input type="text" id="title" name="title" required placeholder="例: 新しい集中タイマー機能を追加">
      </div>
      
      <div class="form-group">
        <label for="content">内容 *</label>
        <textarea id="content" name="content" required placeholder="アップデートの詳細を記入してください..."></textarea>
      </div>
      
      <div class="form-group">
        <label for="category">カテゴリ *</label>
        <select id="category" name="category" required>
          <option value="feature">✨ 新機能</option>
          <option value="bugfix">🐛 バグ修正</option>
          <option value="improvement">🔧 改善</option>
          <option value="announcement">📢 お知らせ</option>
        </select>
      </div>
      
      <div class="form-group">
        <label for="version">バージョン</label>
        <input type="text" id="version" name="version" placeholder="例: 1.2.0">
      </div>
      
      <div class="checkbox-group">
        <input type="checkbox" id="is_published" name="is_published">
        <label for="is_published">公開する（チェックするとユーザーに表示されます）</label>
      </div>
      
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" onclick="closeModal()">キャンセル</button>
        <button type="submit" class="btn btn-primary">保存</button>
      </div>
    </form>
  </div>
</div>

<script>
const modal = document.getElementById('updateModal');
const form = document.getElementById('updateForm');
let isEditMode = false;

function showCreateModal() {
  isEditMode = false;
  document.getElementById('modalTitle').textContent = 'アップデート情報を作成';
  form.reset();
  document.getElementById('updateId').value = '';
  modal.classList.add('active');
}

function editUpdateFromData(button) {
  isEditMode = true;
  document.getElementById('modalTitle').textContent = 'アップデート情報を編集';
  document.getElementById('updateId').value = button.dataset.updateId;
  document.getElementById('title').value = button.dataset.updateTitle;
  document.getElementById('content').value = button.dataset.updateContent;
  document.getElementById('category').value = button.dataset.updateCategory;
  document.getElementById('version').value = button.dataset.updateVersion;
  document.getElementById('is_published').checked = button.dataset.updatePublished === '1';
  modal.classList.add('active');
}

function closeModal() {
  modal.classList.remove('active');
  form.reset();
}

form.addEventListener('submit', async (e) => {
  e.preventDefault();
  
  const formData = new FormData(form);
  formData.append('action', isEditMode ? 'update' : 'create');
  formData.set('is_published', document.getElementById('is_published').checked ? '1' : '0');
  
  try {
    const res = await fetch('updates_api.php', {
      method: 'POST',
      body: formData
    });
    const data = await res.json();
    
    if (data.ok) {
      alert(isEditMode ? '更新しました' : '作成しました');
      location.reload();
    } else {
      alert('エラー: ' + (data.error || '不明なエラー'));
    }
  } catch (err) {
    alert('ネットワークエラー');
    console.error(err);
  }
});

async function deleteUpdate(id) {
  if (!confirm('このアップデート情報を削除しますか？')) return;
  
  const formData = new FormData();
  formData.append('action', 'delete');
  formData.append('id', id);
  
  try {
    const res = await fetch('updates_api.php', {
      method: 'POST',
      body: formData
    });
    const data = await res.json();
    
    if (data.ok) {
      alert('削除しました');
      location.reload();
    } else {
      alert('エラー: ' + (data.error || '不明なエラー'));
    }
  } catch (err) {
    alert('ネットワークエラー');
    console.error(err);
  }
}

// モーダル外クリックで閉じる
modal.addEventListener('click', (e) => {
  if (e.target === modal) {
    closeModal();
  }
});

// ESCキーでモーダルを閉じる
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape' && modal.classList.contains('active')) {
    closeModal();
  }
});
</script>
</body>
</html>
