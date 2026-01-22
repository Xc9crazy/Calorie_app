<?php
// セッションが開始されていない場合のみ開始
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

// POSTメソッドチェック
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: users_list.php?error=invalid_request");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$id = $_POST['id'] ?? '';

// エラーハンドリング関数
function redirectWithError($error_code, $id) {
    header("Location: user_edit.php?id=" . urlencode($id) . "&error=" . urlencode($error_code));
    exit();
}

// IDチェック
if(empty($id) || !is_numeric($id)){
    header("Location: users_list.php?error=invalid_id");
    exit();
}

// 権限チェック：自分のプロフィールのみ更新可能
if($id != $current_user_id){
    header("Location: users_list.php?error=permission");
    exit();
}

// 入力値取得
$username = trim($_POST['username'] ?? '');
$height = $_POST['height'] ?? null;
$weight = $_POST['weight'] ?? null;
$age = $_POST['age'] ?? null;
$gender = $_POST['gender'] ?? null;
$activity_level = $_POST['activity_level'] ?? 'normal';
$goal = $_POST['goal'] ?? 'maintain';

// === 入力値検証 ===

// 必須項目チェック
if(empty($username)){
    redirectWithError('empty', $id);
}

// ユーザー名検証
if (strlen($username) < 3 || strlen($username) > 50) {
    redirectWithError('username_invalid', $id);
}

if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    redirectWithError('username_invalid', $id);
}

// 数値型の検証
if (!empty($height)) {
    if (!is_numeric($height) || $height < 100 || $height > 250) {
        redirectWithError('height_invalid', $id);
    }
}

if (!empty($weight)) {
    if (!is_numeric($weight) || $weight < 30 || $weight > 300) {
        redirectWithError('weight_invalid', $id);
    }
}

if (!empty($age)) {
    if (!is_numeric($age) || $age < 10 || $age > 120) {
        redirectWithError('age_invalid', $id);
    }
}

// 列挙型の検証
$valid_genders = ['male', 'female', null, ''];
if (!in_array($gender, $valid_genders)) {
    $gender = null;
}

$valid_activity_levels = ['low', 'normal', 'high'];
if (!in_array($activity_level, $valid_activity_levels)) {
    $activity_level = 'normal';
}

$valid_goals = ['bulk', 'maintain', 'cut'];
if (!in_array($goal, $valid_goals)) {
    $goal = 'maintain';
}

try {
    // トランザクション開始
    $pdo->beginTransaction();

    // 現在のユーザー情報取得
    $check_sql = "SELECT username FROM users WHERE id = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$id]);
    $current_user = $check_stmt->fetch();

    if (!$current_user) {
        $pdo->rollBack();
        header("Location: users_list.php?error=not_found");
        exit();
    }

    // ユーザー名が変更されている場合、重複チェック
    if ($username !== $current_user['username']) {
        $dup_check = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $dup_check->execute([$username, $id]);
        if ($dup_check->fetch()) {
            $pdo->rollBack();
            redirectWithError('username_exists', $id);
        }
    }

    // 更新処理
    $sql = "UPDATE users 
            SET username = ?, 
                height = ?, 
                weight = ?, 
                age = ?,
                gender = ?,
                activity_level = ?,
                goal = ?
            WHERE id = ?";

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $username,
        !empty($height) ? $height : null,
        !empty($weight) ? $weight : null,
        !empty($age) ? $age : null,
        !empty($gender) ? $gender : null,
        $activity_level,
        $goal,
        $id
    ]);

    if ($result) {
        // コミット
        $pdo->commit();

        // セッションのユーザー名も更新
        $_SESSION['username'] = $username;

        // ログ記録
        error_log("User profile updated: user_id={$id}, username={$username}");

        // 成功メッセージ付きでリダイレクト
        header("Location: users_list.php?message=updated");
        exit();
    } else {
        $pdo->rollBack();
        redirectWithError('update_failed', $id);
    }

} catch (PDOException $e) {
    // ロールバック
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // エラーログ
    error_log("Error updating user: " . $e->getMessage());
    
    redirectWithError('system', $id);
}