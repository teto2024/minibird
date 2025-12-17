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
<title>新規登録 - MiniBird</title>
<link rel="stylesheet" href="assets/style.css">
<style>
body {
    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
}
.register-container {
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
.form-group small {
    color: #718096;
    font-size: 13px;
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
.btn-register {
    background: #48bb78;
    color: white;
}
.btn-register:hover {
    background: #38a169;
}
.btn-login {
    background: #e2e8f0;
    color: #2d3748;
}
.btn-login:hover {
    background: #cbd5e0;
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
<div class="register-container">
    <div class="logo">🐦</div>
    <h2>MiniBird 新規登録</h2>
    
    <div id="message"></div>
    
    <form id="registerForm">
        <div class="form-group">
            <label for="handle">ハンドル *</label>
            <input type="text" id="handle" name="handle" required pattern="[A-Za-z0-9_]{3,16}" placeholder="your_handle">
            <small>英数字とアンダースコア、3〜16文字</small>
        </div>
        <div class="form-group">
            <label for="password">パスワード *</label>
            <input type="password" id="password" name="password" required minlength="6">
            <small>6文字以上</small>
        </div>
        <div class="form-group">
            <label for="invited_by">招待者のハンドル（任意）</label>
            <input type="text" id="invited_by" name="invited_by" placeholder="inviter_handle">
            <small>招待者がいる場合は入力してください</small>
        </div>
        <button type="submit" class="btn btn-register">登録する</button>
    </form>
    
    <button class="btn btn-login" onclick="location.href='login.php'">すでにアカウントをお持ちの方</button>
</div>

<script>
document.getElementById('registerForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const msgDiv = document.getElementById('message');
    
    try {
        const res = await fetch('auth.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'register',
                handle: form.handle.value,
                password: form.password.value,
                invited_by: form.invited_by.value
            })
        });
        const data = await res.json();
        
        if (data.ok) {
            msgDiv.innerHTML = '<div class="alert alert-success">登録成功！ログインしています...</div>';
            setTimeout(() => {
                location.href = 'index.php';
            }, 1000);
        } else {
            let errorMsg = '登録に失敗しました';
            if (data.error === 'invalid_handle') errorMsg = 'ハンドルの形式が正しくありません';
            if (data.error === 'weak_password') errorMsg = 'パスワードは6文字以上必要です';
            if (data.error === 'handle_taken') errorMsg = 'このハンドルは既に使用されています';
            msgDiv.innerHTML = `<div class="alert alert-error">${errorMsg}</div>`;
        }
    } catch (err) {
        msgDiv.innerHTML = '<div class="alert alert-error">ネットワークエラー</div>';
    }
});
</script>
</body>
</html>
