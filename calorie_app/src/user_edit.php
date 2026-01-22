<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Content-Type: text/html; charset=UTF-8");
require "db.php";

// ログインチェック
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$edit_user_id = $_GET['id'] ?? '';

// IDチェック
if(empty($edit_user_id) || !is_numeric($edit_user_id)){
    header("Location: users_list.php?error=invalid_id");
    exit();
}

if($edit_user_id != $current_user_id){
    header("Location: users_list.php?error=permission");
    exit();
}

// エラーメッセージ
$error = $_GET['error'] ?? '';

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$edit_user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$user){
        header("Location: users_list.php?error=not_found");
        exit();
    }

} catch (PDOException $e) {
    error_log("Error fetching user: " . $e->getMessage());
    header("Location: users_list.php?error=system");
    exit();
}

function h($s){
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>プロフィール編集 - カロリー管理アプリ</title>
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
    padding: 20px;
}

.container {
    max-width: 800px;
    margin: 0 auto;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    overflow: hidden;
}

.header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    text-align: center;
}

.header h1 {
    font-size: 28px;
    margin-bottom: 10px;
}

.header p {
    font-size: 14px;
    opacity: 0.9;
}

.content {
    padding: 40px 30px;
}

.message {
    padding: 15px;
    margin-bottom: 25px;
    border-radius: 8px;
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

.form-section {
    margin-bottom: 30px;
}

.form-section h3 {
    font-size: 18px;
    color: #667eea;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e0e0e0;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 15px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group.full-width {
    grid-column: 1 / -1;
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

.form-group input[type="text"],
.form-group input[type="number"],
.form-group select {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 15px;
    transition: all 0.3s;
    background: #f8f9fa;
}

.form-group input:focus,
.form-group select:focus {
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
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    flex: 1;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
}

.back-link {
    display: inline-block;
    margin-top: 20px;
    color: #667eea;
    text-decoration: none;
    font-weight: 600;
}

.back-link:hover {
    text-decoration: underline;
}

@media (max-width: 600px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .button-group {
        flex-direction: column;
    }
}
</style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>✏️ プロフィール編集</h1>
        <p><?= h($user['username']) ?> さんのプロフィール</p>
    </div>

    <div class="content">
        <?php if($error): ?>
            <div class="message error">
                ❌ 
                <?php
                switch($error) {
                    case 'empty':
                        echo 'ユーザー名は必須です。';
                        break;
                    case 'username_exists':
                        echo 'このユーザー名は既に使用されています。';
                        break;
                    case 'username_invalid':
                        echo 'ユーザー名は英数字とアンダースコアのみ使用できます。';
                        break;
                    case 'height_invalid':
                        echo '身長は100〜250cmの範囲で入力してください。';
                        break;
                    case 'weight_invalid':
                        echo '体重は30〜300kgの範囲で入力してください。';
                        break;
                    case 'age_invalid':
                        echo '年齢は10〜120歳の範囲で入力してください。';
                        break;
                    case 'system':
                        echo 'システムエラーが発生しました。';
                        break;
                    default:
                        echo '更新に失敗しました。';
                }
                ?>
            </div>
        <?php endif; ?>

        <form action="user_update.php" method="POST">
            <input type="hidden" name="id" value="<?= h($user['id']) ?>">

            <div class="form-section">
                <h3>🔐 アカウント情報</h3>
                
                <div class="form-group">
                    <label for="username">
                        ユーザー名 <span class="required">*</span>
                    </label>
                    <input type="text" 
                           id="username"
                           name="username" 
                           value="<?= h($user['username']) ?>"
                           required
                           pattern="[a-zA-Z0-9_]{3,50}">
                    <div class="form-help">英数字とアンダースコアのみ（3〜50文字）</div>
                </div>
            </div>

            <div class="form-section">
                <h3>👤 身体情報</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="height">身長 (cm)</label>
                        <input type="number" 
                               id="height"
                               name="height" 
                               value="<?= h($user['height'] ?? '') ?>"
                               min="100"
                               max="250"
                               step="0.1"
                               placeholder="170.0">
                    </div>

                    <div class="form-group">
                        <label for="weight">体重 (kg)</label>
                        <input type="number" 
                               id="weight"
                               name="weight" 
                               value="<?= h($user['weight'] ?? '') ?>"
                               min="30"
                               max="300"
                               step="0.1"
                               placeholder="65.0">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="age">年齢</label>
                        <input type="number" 
                               id="age"
                               name="age" 
                               value="<?= h($user['age'] ?? '') ?>"
                               min="10"
                               max="120"
                               placeholder="25">
                    </div>

                    <div class="form-group">
                        <label for="gender">性別</label>
                        <select id="gender" name="gender">
                            <option value="">選択してください</option>
                            <option value="male" <?= $user['gender'] === 'male' ? 'selected' : '' ?>>男性</option>
                            <option value="female" <?= $user['gender'] === 'female' ? 'selected' : '' ?>>女性</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>🎯 目標設定</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="activity_level">活動レベル</label>
                        <select id="activity_level" name="activity_level">
                            <option value="low" <?= $user['activity_level'] === 'low' ? 'selected' : '' ?>>
                                低い（デスクワーク中心）
                            </option>
                            <option value="normal" <?= $user['activity_level'] === 'normal' ? 'selected' : '' ?>>
                                普通（軽い運動あり）
                            </option>
                            <option value="high" <?= $user['activity_level'] === 'high' ? 'selected' : '' ?>>
                                高い（激しい運動あり）
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="goal">目標</label>
                        <select id="goal" name="goal">
                            <option value="cut" <?= $user['goal'] === 'cut' ? 'selected' : '' ?>>
                                減量（カット）
                            </option>
                            <option value="maintain" <?= $user['goal'] === 'maintain' ? 'selected' : '' ?>>
                                維持
                            </option>
                            <option value="bulk" <?= $user['goal'] === 'bulk' ? 'selected' : '' ?>>
                                増量（バルク）
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="button-group">
                <button type="submit" class="btn btn-primary">
                    ✅ 更新する
                </button>
                <button type="button" class="btn btn-secondary" onclick="location.href='users_list.php'">
                    キャンセル
                </button>
            </div>
        </form>

        <a href="users_list.php" class="back-link">← 一覧に戻る</a>
    </div>
</div>

</body>
</html>