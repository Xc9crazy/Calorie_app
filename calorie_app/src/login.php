<?php
// セッションが開始されていない場合のみ開始
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Content-Type: text/html; charset=UTF-8");

// 既にログイン済みの場合はホームへリダイレクト
if(isset($_SESSION['user_id'])){
    header("Location: home.php");
    exit();
}

// CSRFトークン生成
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// メッセージ取得
$registered = isset($_GET['registered']);
$error = isset($_GET['error']) ? $_GET['error'] : '';
$logout = isset($_GET['logout']);

// エスケープ関数
function h($str){
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ログイン - カロリー管理アプリ</title>
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.login-container {
    background: white;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    width: 100%;
    max-width: 420px;
    overflow: hidden;
}

.login-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px 30px;
    text-align: center;
}

.login-header h1 {
    font-size: 32px;
    margin-bottom: 10px;
}

.login-header .emoji {
    font-size: 48px;
    margin-bottom: 15px;
}

.login-header p {
    font-size: 14px;
    opacity: 0.9;
}

.login-body {
    padding: 40px 30px;
}

.message {
    padding: 15px;
    margin-bottom: 25px;
    border-radius: 8px;
    font-size: 14px;
    animation: slideDown 0.3s ease;
}

.message.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.message.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.message.info {
    background: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.form-group {
    margin-bottom: 25px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
    font-size: 14px;
}

.form-group .required {
    color: #f5576c;
}

.input-wrapper {
    position: relative;
}

.form-group input[type="text"],
.form-group input[type="password"] {
    width: 100%;
    padding: 14px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 15px;
    transition: all 0.3s;
    background: #f8f9fa;
}

.form-group input[type="text"]:focus,
.form-group input[type="password"]:focus {
    outline: none;
    border-color: #667eea;
    background: white;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.password-toggle {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    font-size: 18px;
    color: #999;
    padding: 5px;
    transition: color 0.3s;
}

.password-toggle:hover {
    color: #667eea;
}

.login-btn {
    width: 100%;
    padding: 16px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.login-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
}

.login-btn:active {
    transform: translateY(0);
}

.divider {
    text-align: center;
    margin: 30px 0;
    position: relative;
}

.divider::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    width: 100%;
    height: 1px;
    background: #e0e0e0;
}

.divider span {
    background: white;
    padding: 0 15px;
    color: #999;
    font-size: 14px;
    position: relative;
}

.register-link {
    text-align: center;
}

.register-link a {
    color: #667eea;
    text-decoration: none;
    font-weight: 600;
    font-size: 15px;
    transition: all 0.3s;
}

.register-link a:hover {
    color: #764ba2;
    text-decoration: underline;
}

.features {
    margin-top: 30px;
    padding-top: 25px;
    border-top: 1px solid #e0e0e0;
}

.features h3 {
    font-size: 14px;
    color: #666;
    margin-bottom: 15px;
}

.features ul {
    list-style: none;
}

.features li {
    padding: 8px 0;
    color: #666;
    font-size: 13px;
}

.features li::before {
    content: "✓ ";
    color: #4CAF50;
    font-weight: bold;
    margin-right: 8px;
}

@media (max-width: 480px) {
    .login-header {
        padding: 30px 20px;
    }

    .login-header h1 {
        font-size: 26px;
    }

    .login-body {
        padding: 30px 20px;
    }
}
</style>
</head>
<body>

<div class="login-container">
    <div class="login-header">
        <div class="emoji">🍽️</div>
        <h1>ログイン</h1>
        <p>カロリー管理アプリ</p>
    </div>

    <div class="login-body">
        <?php if($registered): ?>
            <div class="message success">
                ✅ 登録が完了しました！<br>
                ログインして始めましょう。
            </div>
        <?php endif; ?>

        <?php if($logout): ?>
            <div class="message info">
                ℹ️ ログアウトしました。
            </div>
        <?php endif; ?>

        <?php if($error): ?>
            <div class="message error">
                ❌ 
                <?php
                switch($error) {
                    case 'invalid':
                        echo 'ユーザー名またはパスワードが正しくありません。';
                        break;
                    case 'empty':
                        echo 'ユーザー名とパスワードを入力してください。';
                        break;
                    case 'session':
                        echo 'セッションエラーが発生しました。もう一度お試しください。';
                        break;
                    case 'locked':
                        echo 'ログイン試行回数が上限に達しました。5分後に再度お試しください。';
                        break;
                    case 'inactive':
                        echo 'このアカウントは無効化されています。管理者にお問い合わせください。';
                        break;
                    case 'system':
                        echo 'システムエラーが発生しました。しばらくしてから再度お試しください。';
                        break;
                    case 'invalid_request':
                        echo '不正なリクエストです。';
                        break;
                    default:
                        echo 'ログインに失敗しました。';
                }
                ?>
            </div>
        <?php endif; ?>

        <form action="login_check.php" method="POST">
            <!-- CSRF トークン -->
            <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
            
            <div class="form-group">
                <label for="username">
                    ユーザー名 <span class="required">*</span>
                </label>
                <input type="text" 
                       id="username" 
                       name="username" 
                       required 
                       autofocus
                       placeholder="ユーザー名を入力"
                       autocomplete="username">
            </div>

            <div class="form-group">
                <label for="password">
                    パスワード <span class="required">*</span>
                </label>
                <div class="input-wrapper">
                    <input type="password" 
                           id="password" 
                           name="password" 
                           required
                           placeholder="パスワードを入力"
                           autocomplete="current-password">
                    <button type="button" 
                            class="password-toggle" 
                            onclick="togglePassword()"
                            aria-label="パスワードを表示">
                        👁️
                    </button>
                </div>
            </div>

            <button type="submit" class="login-btn">
                ログイン
            </button>
        </form>

        <div class="divider">
            <span>または</span>
        </div>

        <div class="register-link">
            <a href="register.php">
                📝 新規アカウント登録
            </a>
        </div>

        <div class="features">
            <h3>アプリの主な機能</h3>
            <ul>
                <li>毎日のカロリー・栄養管理</li>
                <li>PFCバランスの可視化</li>
                <li>目標に合わせた摂取カロリー計算</li>
                <li>食品データベース管理</li>
            </ul>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleBtn = document.querySelector('.password-toggle');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleBtn.textContent = '🙈';
    } else {
        passwordInput.type = 'password';
        toggleBtn.textContent = '👁️';
    }
}

// エンターキーでフォーム送信
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const inputs = form.querySelectorAll('input');
    
    inputs.forEach(input => {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                form.submit();
            }
        });
    });
});
</script>

</body>
</html>