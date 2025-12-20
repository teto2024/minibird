<?php
// パスワードリセット申請ページ
require_once __DIR__ . '/config.php';
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>パスワードリセット申請 - MiniBird</title>
<link rel="stylesheet" href="assets/style.css?v=<?= ASSETS_VERSION ?>">
<style>
.reset-container {
    max-width: 500px;
    margin: 50px auto;
    padding: 30px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}
.form-group {
    margin-bottom: 20px;
}
.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
    color: #2d3748;
}
.form-group input,
.form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #cbd5e0;
    border-radius: 6px;
    font-family: inherit;
}
.btn-submit {
    width: 100%;
    padding: 12px;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 16px;
    font-weight: bold;
}
.btn-submit:hover {
    background: #5568d3;
}
.alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 6px;
}
.alert-success {
    background: #c6f6d5;
    color: #2f855a;
}
.alert-error {
    background: #fed7d7;
    color: #c53030;
}
</style>
</head>
<body>
<div class="reset-container">
    <h2>パスワードリセット申請</h2>
    <p>アカウントにアクセスできなくなった場合、こちらからパスワードリセットを申請できます。管理者が審査後、新しいパスワードが設定されます。</p>
    
    <div id="message"></div>
    
    <form id="resetForm">
        <div class="form-group">
            <label for="handle">ユーザーハンドル *</label>
            <input type="text" id="handle" name="handle" required placeholder="your_handle">
        </div>
        <div class="form-group">
            <label for="new_password">新しいパスワード *</label>
            <input type="password" id="new_password" name="new_password" required minlength="6" placeholder="6文字以上">
        </div>
        <div class="form-group">
            <label for="reason">申請理由 *</label>
            <textarea id="reason" name="reason" rows="4" required placeholder="パスワードを忘れた理由や本人確認情報を記載してください"></textarea>
        </div>
        <button type="submit" class="btn-submit">申請を送信</button>
    </form>
    
    <p style="margin-top: 20px; text-align: center;">
        <a href="index.php">← トップページに戻る</a>
    </p>
</div>

<script>
document.getElementById('resetForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const msgDiv = document.getElementById('message');
    
    const data = {
        action: 'request_password_reset',
        handle: form.handle.value,
        new_password: form.new_password.value,
        reason: form.reason.value
    };
    
    try {
        const res = await fetch('password_reset_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        const result = await res.json();
        
        if (result.ok) {
            msgDiv.innerHTML = '<div class="alert alert-success">申請を受け付けました。管理者の審査をお待ちください。</div>';
            form.reset();
        } else {
            msgDiv.innerHTML = `<div class="alert alert-error">エラー: ${result.error}</div>`;
        }
    } catch (err) {
        msgDiv.innerHTML = '<div class="alert alert-error">ネットワークエラーが発生しました</div>';
    }
});
</script>
</body>
</html>
