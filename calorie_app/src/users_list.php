<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Content-Type: text/html; charset=UTF-8");
require "db.php";


if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];


$message = $_GET['message'] ?? '';
$error = $_GET['error'] ?? '';

try {
    $sql = "SELECT id, username, height, weight, age, gender, 
                   activity_level, goal, created_at 
            FROM users WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$current_user_id]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error fetching users: " . $e->getMessage());
    $error = 'system';
    $users = [];
}


function h($str){
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}


function translateGender($gender) {
    return $gender === 'male' ? '男性' : ($gender === 'female' ? '女性' : '-');
}

function translateActivityLevel($level) {
    $map = ['low' => '低い', 'normal' => '普通', 'high' => '高い'];
    return $map[$level] ?? '-';
}

function translateGoal($goal) {
    $map = ['bulk' => '増量', 'maintain' => '維持', 'cut' => '減量'];
    return $map[$goal] ?? '-';
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>プロフィール管理 - カロリー管理アプリ</title>
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
    max-width: 1200px;
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
}

.header h1 {
    font-size: 28px;
    margin-bottom: 10px;
}

.header .user-info {
    font-size: 14px;
    opacity: 0.9;
}

.content {
    padding: 30px;
}

.message {
    padding: 15px;
    margin-bottom: 25px;
    border-radius: 8px;
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

.profile-card {
    background: white;
    border: 2px solid #667eea;
    border-radius: 12px;
    padding: 25px;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e0e0e0;
}

.card-header h3 {
    font-size: 20px;
    color: #333;
}

.profile-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.profile-item {
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

.profile-item .label {
    font-size: 12px;
    color: #666;
    margin-bottom: 5px;
}

.profile-item .value {
    font-size: 18px;
    font-weight: 600;
    color: #333;
}

.card-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e0e0e0;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s;
    font-size: 14px;
}

.btn-primary {
    background: #667eea;
    color: white;
}

.btn-primary:hover {
    background: #5568d3;
    transform: translateY(-2px);
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

@media (max-width: 768px) {
    .profile-grid {
        grid-template-columns: 1fr;
    }
    
    .card-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
        text-align: center;
    }
}
</style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>👤 プロフィール管理</h1>
        <div class="user-info">
            ログイン中：<?= h($_SESSION['username']) ?> さん
        </div>
    </div>

    <div class="content">
        <?php if($message): ?>
            <div class="message success">
                ✅ 
                <?php
                switch($message) {
                    case 'updated':
                        echo 'プロフィールを更新しました。';
                        break;
                    default:
                        echo '操作が完了しました。';
                }
                ?>
            </div>
        <?php endif; ?>

        <?php if($error): ?>
            <div class="message error">
                ❌ 
                <?php
                switch($error) {
                    case 'system':
                        echo 'システムエラーが発生しました。';
                        break;
                    case 'not_found':
                        echo 'ユーザーが見つかりません。';
                        break;
                    case 'permission':
                        echo '権限がありません。';
                        break;
                    default:
                        echo 'エラーが発生しました。';
                }
                ?>
            </div>
        <?php endif; ?>

        <?php foreach($users as $u): ?>
            <div class="profile-card">
                <div class="card-header">
                    <h3>
                        <?= h($u['username']) ?>
                        <span style="color: #667eea; font-size: 14px;">(あなた)</span>
                    </h3>
                </div>

                <div class="profile-grid">
                    <div class="profile-item">
                        <div class="label">身長</div>
                        <div class="value"><?= h($u['height'] ?: '-') ?> cm</div>
                    </div>
                    
                    <div class="profile-item">
                        <div class="label">体重</div>
                        <div class="value"><?= h($u['weight'] ?: '-') ?> kg</div>
                    </div>
                    
                    <div class="profile-item">
                        <div class="label">年齢</div>
                        <div class="value"><?= h($u['age'] ?: '-') ?> 歳</div>
                    </div>
                    
                    <div class="profile-item">
                        <div class="label">性別</div>
                        <div class="value"><?= translateGender($u['gender']) ?></div>
                    </div>
                    
                    <div class="profile-item">
                        <div class="label">活動レベル</div>
                        <div class="value"><?= translateActivityLevel($u['activity_level']) ?></div>
                    </div>
                    
                    <div class="profile-item">
                        <div class="label">目標</div>
                        <div class="value"><?= translateGoal($u['goal']) ?></div>
                    </div>
                    
                    <div class="profile-item">
                        <div class="label">登録日</div>
                        <div class="value"><?= date('Y/m/d', strtotime($u['created_at'])) ?></div>
                    </div>
                </div>

                <div class="card-actions">
                    <a href="user_edit.php?id=<?= h($u['id']) ?>" class="btn btn-primary">
                        ✏️ 編集
                    </a>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if(count($users) === 0): ?>
            <div style="text-align: center; padding: 40px; color: #999;">
                プロフィール情報がありません
            </div>
        <?php endif; ?>

        <a href="home.php" class="back-link">← ホームへ戻る</a>
    </div>
</div>

</body>
</html>