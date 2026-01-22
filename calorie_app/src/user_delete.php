<?php
session_start();
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
$delete_user_id = $_POST['id'] ?? '';

// IDチェック
if(empty($delete_user_id) || !is_numeric($delete_user_id)){
    header("Location: users_list.php?error=invalid_id");
    exit();
}

// 自分自身の削除を防止
if($delete_user_id == $current_user_id){
    header("Location: users_list.php?error=cannot_delete_self");
    exit();
}

// 権限チェック：管理者のみ他のユーザーを削除可能
// 現在の実装では、一般ユーザーは他人を削除できないようにする
header("Location: users_list.php?error=permission");
exit();

/*
// 将来的に管理者機能を実装する場合は以下のコードを使用

try {
    // トランザクション開始
    $pdo->beginTransaction();

    // 削除対象のユーザー情報取得
    $check_sql = "SELECT id, username FROM users WHERE id = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$delete_user_id]);
    $user = $check_stmt->fetch();

    if (!$user) {
        $pdo->rollBack();
        header("Location: users_list.php?error=not_found");
        exit();
    }

    // ユーザー削除（CASCADE設定により関連する meals も自動削除）
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $result = $stmt->execute([$delete_user_id]);

    if ($result && $stmt->rowCount() > 0) {
        // コミット
        $pdo->commit();

        // ログ記録
        error_log("User deleted: user_id={$delete_user_id}, username={$user['username']} by admin_id={$current_user_id}");

        // 成功メッセージ付きでリダイレクト
        header("Location: users_list.php?message=deleted");
        exit();
    } else {
        $pdo->rollBack();
        header("Location: users_list.php?error=delete_failed");
        exit();
    }

} catch (PDOException $e) {
    // ロールバック
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // エラーログ
    error_log("Error deleting user: " . $e->getMessage());
    
    header("Location: users_list.php?error=system");
    exit();
}
*/