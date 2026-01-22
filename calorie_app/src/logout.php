<?php
// セッションが開始されていない場合のみ開始
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Content-Type: text/html; charset=UTF-8");

// ログアウト前のユーザー情報を保存（ログ用）
$user_id = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? 'unknown';

// セッション変数を全て削除
$_SESSION = [];

// セッションクッキーも削除
if (isset($_COOKIE[session_name()])) {
    setcookie(
        session_name(),
        '',
        time() - 3600,
        '/',
        '',
        isset($_SERVER['HTTPS']),  // Secure flag
        true  // HttpOnly flag
    );
}

// セッション破棄
session_destroy();

// ログ記録
if ($user_id) {
    error_log("User logged out: ID=$user_id ($username) from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
}

// ログイン画面へリダイレクト（ログアウトメッセージ付き）
header("Location: login.php?logout=1");
exit();