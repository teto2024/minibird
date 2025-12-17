<?php
require_once __DIR__ . '/config.php';

// 既にログイン済みならトップページへ
if (user()) {
    header('Location: index.php');
    exit;
}
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ログイン - MiniBird</title>
<link rel="stylesheet" href="assets/style.css">
<style>
body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
}
.login-container {
    max-width: 400px;
    width: 90%;
    background: white;
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
}
.logo {
    text-align: center;
    font-size: 48px;
    margin-bottom: 20px;
}
h2 {
    text-align: center;
    color: #2d3748;
    margin-bottom: 30px;
}
.form-group {
    margin-bottom: 20px;
}
.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
    color: #4a5568;
}
.form-group input {
    width: 100%;
    padding: 12px;
    border: 1px solid #cbd5e0;
    border-radius: 6px;
    font-size: 16px;
}
.btn {
    width: 100%;
    padding: 14px;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    margin-bottom: 10px;
}
.btn-login {
    background: #667eea;
    color: white;
}
.btn-login:hover {
    background: #5568d3;
}
.btn-register {
    background: #48bb78;
    color: white;
}
.btn-register:hover {
    background: #38a169;
}
.links {
    text-align: center;
    margin-top: 20px;
    font-size: 14px;
}
.links a {
    color: #667eea;
    text-decoration: none;
}
.links a:hover {
    text-decoration: underline;
}
.alert {
    padding: 12px;
    margin-bottom: 20px;
    border-radius: 6px;
    text-align: center;
}
.alert-error {
    background: #fed7d7;
    color: #c53030;
}
.alert-success {
    background: #c6f6d5;
    color: #2f855a;
}
</style>
</head>
<body>
<div class="login-container">
    <div class="logo">🐦</div>
    <h2>MiniBird</h2>
    
    <div id="message"></div>
    
    <form id="loginForm">
        <div class="form-group">
            <label for="handle">ハンドル</label>
            <input type="text" id="handle" name="handle" required placeholder="your_handle">
        </div>
        <div class="form-group">
            <label for="password">パスワード</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-login">ログイン</button>
    </form>
    
    <button class="btn btn-register" onclick="location.href='register.php'">新規登録</button>
    
    <div class="links">
        <a href="password_reset_request.php">パスワードを忘れた方</a>
    </div>
</div>

<script>
document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const msgDiv = document.getElementById('message');
    
    try {
        const res = await fetch('auth.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'login',
                handle: form.handle.value,
                password: form.password.value
            })
        });
        const data = await res.json();
        
        if (data.ok) {
            msgDiv.innerHTML = '<div class="alert alert-success">ログイン成功！</div>';
            setTimeout(() => {
                location.href = 'index.php';
            }, 500);
        } else {
            let errorMsg = 'ログインに失敗しました';
            if (data.error === 'invalid_credentials') errorMsg = 'ハンドルまたはパスワードが正しくありません';
            if (data.error === 'account_frozen') errorMsg = 'アカウントが凍結されています';
            msgDiv.innerHTML = `<div class="alert alert-error">${errorMsg}</div>`;
        }
    } catch (err) {
        msgDiv.innerHTML = '<div class="alert alert-error">ネットワークエラー</div>';
    }
});
</script>
</body>
</html>