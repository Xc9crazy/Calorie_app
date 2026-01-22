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

// エラーとユーザー名の取得
$error = $_GET['error'] ?? '';
$username = $_GET['username'] ?? '';

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
<title>新規登録 - カロリー管理アプリ</title>
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

.register-container {
    background: white;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    width: 100%;
    max-width: 500px;
    overflow: hidden;
}

.register-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px 30px;
    text-align: center;
}

.register-header h1 {
    font-size: 32px;
    margin-bottom: 10px;
}

.register-header .emoji {
    font-size: 48px;
    margin-bottom: 15px;
}

.register-body {
    padding: 40px 30px;
}

.message {
    padding: 15px;
    margin-bottom: 25px;
    border-radius: 8px;
    font-size: 14px;
    animation: slideDown 0.3s ease;
}

.message.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
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
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
    font-size: 14px;
}

.required {
    color: #f5576c;
}

.form-group input {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 15px;
    transition: all 0.3s;
    background: #f8f9fa;
}

.form-group input:focus {
    outline: none;
    border-color: #667eea;
    background: white;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-help {
    font-size: 12px;
    color: #666;
    margin-top: 5px;
}

.register-btn {
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
    margin-top: 20px;
}

.register-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
}

.divider {
    text-align: center;
    margin: 25px 0;
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

.login-link {
    text-align: center;
}

.login-link a {
    color: #667eea;
    text-decoration: none;
    font-weight: 600;
    font-size: 15px;
}

.login-link a:hover {
    color: #764ba2;
    text-decoration: underline;
}

@media (max-width: 480px) {
    .register-header {
        padding: 30px 20px;
    }
    
    .register-body {
        padding: 30px 20px;
    }
}
</style>
</head>
<body>

<div class="register-container">
    <div class="register-header">
        <div class="emoji">📝</div>
        <h1>新規登録</h1>
        <p>アカウントを作成して始めましょう</p>
    </div>

    <div class="register-body">
        <?php if($error): ?>
            <div class="message error">
                ❌ 
                <?php
                switch($error) {
                    case 'empty':
                        echo 'ユーザー名とパスワードは必須です。';
                        break;
                    case 'username_short':
                        echo 'ユーザー名は3文字以上で入力してください。';
                        break;
                    case 'username_long':
                        echo 'ユーザー名は50文字以内で入力してください。';
                        break;
                    case 'username_invalid':
                        echo 'ユーザー名は英数字とアンダースコアのみ使用できます。';
                        break;
                    case 'username_exists':
                        echo 'このユーザー名は既に使用されています。';
                        break;
                    case 'password_short':
                        echo 'パスワードは8文字以上で入力してください。';
                        break;
                    case 'password_mismatch':
                        echo 'パスワードが一致しません。';
                        break;
                    case 'system':
                        echo 'システムエラーが発生しました。もう一度お試しください。';
                        break;
                    case 'invalid_request':
                        echo '不正なリクエストです。';
                        break;
                    default:
                        echo '登録に失敗しました。';
                }
                ?>
            </div>
        <?php endif; ?>

        <form action="register_check.php" method="POST">
            <div class="form-group">
                <label for="username">
                    ユーザー名 <span class="required">*</span>
                </label>
                <input type="text" 
                       id="username" 
                       name="username" 
                       required
                       autofocus
                       value="<?= h($username) ?>"
                       placeholder="英数字とアンダースコアのみ（3〜50文字）"
                       pattern="[a-zA-Z0-9_]{3,50}">
                <div class="form-help">例: user123, test_user</div>
            </div>

            <div class="form-group">
                <label for="password">
                    パスワード <span class="required">*</span>
                </label>
                <input type="password" 
                       id="password" 
                       name="password" 
                       required
                       minlength="8"
                       placeholder="8文字以上">
                <div class="form-help">8文字以上で入力してください</div>
            </div>

            <div class="form-group">
                <label for="password_confirm">
                    パスワード（確認） <span class="required">*</span>
                </label>
                <input type="password" 
                       id="password_confirm" 
                       name="password_confirm" 
                       required
                       placeholder="もう一度入力してください">
            </div>

            <button type="submit" class="register-btn">
                アカウントを作成
            </button>
        </form>

        <div class="divider">
            <span>既にアカウントをお持ちですか？</span>
        </div>

        <div class="login-link">
            <a href="login.php">
                🔑 ログインはこちら
            </a>
        </div>
    </div>
</div>

<script>
// フォーム送信前のバリデーション
document.querySelector('form').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const passwordConfirm = document.getElementById('password_confirm').value;
    
    if (password !== passwordConfirm) {
        e.preventDefault();
        alert('パスワードが一致しません。');
        return false;
    }
});
</script>

</body>
</html>