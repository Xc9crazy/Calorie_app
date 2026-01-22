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
    header("Location: meal_add.php?error=invalid_request");
    exit();
}

$user_id = $_SESSION['user_id'];
$food_id = $_POST['food_id'] ?? '';
$amount = $_POST['amount'] ?? '';
$meal_date = $_POST['meal_date'] ?? '';
$meal_type = $_POST['meal_type'] ?? null;
$note = trim($_POST['note'] ?? '');

// エラーハンドリング関数
function redirectWithError($error_code) {
    $params = http_build_query([
        'error' => $error_code,
        'food_id' => $_POST['food_id'] ?? '',
        'amount' => $_POST['amount'] ?? '',
        'meal_date' => $_POST['meal_date'] ?? '',
        'meal_type' => $_POST['meal_type'] ?? '',
    ]);
    header("Location: meal_add.php?" . $params);
    exit();
}

// 入力値検証
if(empty($food_id) || empty($amount) || empty($meal_date)){
    redirectWithError('empty');
}

// 数値検証
if(!is_numeric($food_id) || !is_numeric($amount)){
    redirectWithError('invalid_type');
}

// 量の範囲チェック
if($amount <= 0 || $amount > 10000){
    redirectWithError('invalid_amount');
}

// 日付形式チェック
$date_parts = explode('-', $meal_date);
if(count($date_parts) !== 3 || !checkdate($date_parts[1], $date_parts[2], $date_parts[0])){
    redirectWithError('invalid_date');
}

// 未来の日付チェック
if(strtotime($meal_date) > strtotime('+1 day')){
    redirectWithError('future_date');
}

// meal_type検証
$valid_meal_types = ['breakfast', 'lunch', 'dinner', 'snack', null];
if(!in_array($meal_type, $valid_meal_types)){
    $meal_type = null;
}

try {
    // トランザクション開始
    $pdo->beginTransaction();

    // 食品が存在するかチェック
    $check_sql = "SELECT id, name FROM foods WHERE id = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$food_id]);
    $food = $check_stmt->fetch();

    if(!$food){
        $pdo->rollBack();
        redirectWithError('food_not_found');
    }

    // meals に登録
    $sql = "INSERT INTO meals (user_id, food_id, amount, meal_date, meal_type, note)
            VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $user_id,
        $food_id,
        $amount,
        $meal_date,
        $meal_type,
        !empty($note) ? $note : null
    ]);

    if($result){
        // コミット
        $pdo->commit();

        // ログ記録
        $meal_id = $pdo->lastInsertId();
        error_log("Meal added: user_id={$user_id}, meal_id={$meal_id}, food={$food['name']}, amount={$amount}g, date={$meal_date}");

        // 成功メッセージ付きでリダイレクト
        header("Location: home.php?date=" . urlencode($meal_date) . "&message=added");
        exit();
    } else {
        $pdo->rollBack();
        redirectWithError('insert_failed');
    }

} catch (PDOException $e) {
    // ロールバック
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // エラーログ
    error_log("Error adding meal: " . $e->getMessage());
    
    redirectWithError('system');
}