<?php
// セッションが開始されていない場合のみ開始
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Content-Type: text/html; charset=UTF-8");

require "db.php";

// POSTメソッド以外は拒否
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php?error=invalid_request");
    exit();
}

// 既にログイン済みの場合はホームへ
if (isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit();
}

// 入力値取得
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// エラーハンドリング関数
function redirectWithError($error_code) {
    header("Location: login.php?error=" . urlencode($error_code));
    exit();
}

// 入力値チェック
if (empty($username) || empty($password)) {
    redirectWithError('empty');
}

// ユーザー名の長さチェック（セキュリティ）
if (strlen($username) > 50) {
    redirectWithError('invalid');
}

try {
    // ユーザー情報取得
    $sql = "SELECT id, username, password FROM users WHERE username = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // ユーザーが存在しない、またはパスワードが一致しない
    if (!$user || !password_verify($password, $user['password'])) {
        // ログ記録
        error_log("Failed login attempt for username: " . $username . " from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        
        redirectWithError('invalid');
    }

    // ログイン成功 - セッション固定攻撃対策
    session_regenerate_id(true);

    // セッションに保存
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['login_time'] = time();

    // ログ記録
    error_log("Successful login for user ID: " . $user['id'] . " (" . $username . ") from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

    // ホームへリダイレクト
    header("Location: home.php");
    exit();

} catch (PDOException $e) {
    // データベースエラー
    error_log("Database error in login_check.php: " . $e->getMessage());
    redirectWithError('system');
} catch (Exception $e) {
    // その他のエラー
    error_log("Error in login_check.php: " . $e->getMessage());
    redirectWithError('system');
}