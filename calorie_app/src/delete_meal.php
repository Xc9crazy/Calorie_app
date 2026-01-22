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

// POSTメソッド以外は拒否
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: home.php?error=invalid_request");
    exit();
}

$user_id = $_SESSION['user_id'];
$meal_id = $_POST['id'] ?? null;
$return_date = $_POST['return_date'] ?? date('Y-m-d');

// meal_id チェック
if(!$meal_id || !is_numeric($meal_id)){
    header("Location: home.php?date=" . urlencode($return_date) . "&error=invalid_id");
    exit();
}

try {
    // トランザクション開始
    $pdo->beginTransaction();

    // 削除前に食事情報を取得（ログ用）
    $check_sql = "SELECT m.id, f.name, m.amount, m.meal_date 
                  FROM meals m 
                  JOIN foods f ON m.food_id = f.id 
                  WHERE m.id = ? AND m.user_id = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$meal_id, $user_id]);
    $meal = $check_stmt->fetch();

    if (!$meal) {
        $pdo->rollBack();
        header("Location: home.php?date=" . urlencode($return_date) . "&error=not_found");
        exit();
    }

    // 自分の食事のみ削除
    $sql = "DELETE FROM meals WHERE id = ? AND user_id = ?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$meal_id, $user_id]);

    if ($result && $stmt->rowCount() > 0) {
        // コミット
        $pdo->commit();

        // ログ記録
        error_log("Meal deleted: user_id={$user_id}, meal_id={$meal_id}, food={$meal['name']}, amount={$meal['amount']}g");

        // 成功メッセージ付きでリダイレクト
        header("Location: home.php?date=" . urlencode($return_date) . "&message=deleted");
        exit();
    } else {
        $pdo->rollBack();
        header("Location: home.php?date=" . urlencode($return_date) . "&error=delete_failed");
        exit();
    }

} catch (PDOException $e) {
    // ロールバック
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // エラーログ
    error_log("Error deleting meal: " . $e->getMessage());
    
    // エラーメッセージ付きでリダイレクト
    header("Location: home.php?date=" . urlencode($return_date) . "&error=system");
    exit();
}