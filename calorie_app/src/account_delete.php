<?php
session_start();
header("Content-Type: text/html; charset=UTF-8");
require "db.php";

// ログインチェック
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// 確認メッセージ
$step = $_GET['step'] ?? 'confirm';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>アカウント削除 - カロリー管理アプリ</title>
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

.container {
    background: white;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    width: 100%;
    max-width: 500px;
    overflow: hidden;
}

.header {
    background: linear-gradient(135deg, #f5576c 0%, #e04455 100%);
    color: white;
    padding: 40px 30px;
    text-align: center;
}

.header h1 {
    font-size: 28px;
    margin-bottom: 10px;
}

.header .icon {
    font-size: 48px;
    margin-bottom: 15px;
}

.content {
    padding: 40px 30px;
}

.warning-box {
    background: #fff3cd;
    border: 2px solid #ffc107;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 25px;
}

.warning-box h3 {
    color: #856404;
    margin-bottom: 15px;
    font-size: 18px;
}

.warning-box ul {
    margin-left: 20px;
    color: #856404;
}

.warning-box li {
    margin-bottom: 10px;
}

.confirm-input {
    margin: 25px 0;
}

.confirm-input label {
    display: block;
    margin-bottom: 10px;
    font-weight: 600;
    color: #333;
}

.confirm-input input {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 15px;
}

.confirm-input input:focus {
    outline: none;
    border-color: #f5576c;
}

.button-group {
    display: flex;
    gap: 15px;
    margin-top: 30px;
}

.btn {
    padding: 14px 28px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 15px;
    transition: all 0.3s;
    flex: 1;
    text-align: center;
    text-decoration: none;
    display: inline-block;
}

.btn-danger {
    background: #f5576c;
    color: white;
}

.btn-danger:hover {
    background: #e04455;
    transform: translateY(-2px);
}

.btn-danger:disabled {
    background: #ccc;
    cursor: not-allowed;
    transform: none;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
}
</style>
</head>
<body>

<div class="container">
    <div class="header">
        <div class="icon">⚠️</div>
        <h1>アカウント削除</h1>
    </div>

    <div class="content">
        <?php if($step === 'confirm'): ?>
            <div class="warning-box">
                <h3>⚠️ 重要な警告</h3>
                <ul>
                    <li>アカウントを削除すると、<strong>すべてのデータが永久に削除</strong>されます</li>
                    <li>削除されるデータ：プロフィール情報、食事記録、すべての履歴</li>
                    <li><strong>この操作は取り消せません</strong></li>
                </ul>
            </div>

            <p style="margin-bottom: 20px; color: #666;">
                本当にアカウント「<strong><?= htmlspecialchars($username) ?></strong>」を削除しますか？
            </p>

            <form method="POST" action="account_delete_process.php" id="deleteForm">
                <div class="confirm-input">
                    <label for="confirm_username">
                        確認のため、ユーザー名を入力してください：
                    </label>
                    <input type="text" 
                           id="confirm_username" 
                           name="confirm_username" 
                           required
                           placeholder="<?= htmlspecialchars($username) ?>"
                           autocomplete="off">
                </div>

                <div class="button-group">
                    <button type="submit" class="btn btn-danger" id="deleteBtn" disabled>
                        削除する
                    </button>
                    <a href="users_list.php" class="btn btn-secondary">
                        キャンセル
                    </a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
const confirmInput = document.getElementById('confirm_username');
const deleteBtn = document.getElementById('deleteBtn');
const expectedUsername = <?= json_encode($username) ?>;

confirmInput.addEventListener('input', function() {
    if (this.value === expectedUsername) {
        deleteBtn.disabled = false;
    } else {
        deleteBtn.disabled = true;
    }
});

document.getElementById('deleteForm').addEventListener('submit', function(e) {
    if (confirmInput.value !== expectedUsername) {
        e.preventDefault();
        alert('ユーザー名が一致しません。');
        return false;
    }
    
    if (!confirm('本当に削除しますか？この操作は取り消せません。')) {
        e.preventDefault();
        return false;
    }
});
</script>

</body>
</html>