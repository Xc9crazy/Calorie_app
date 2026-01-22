<?php
// セッションが開始されていない場合のみ開始
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Content-Type: text/html; charset=UTF-8");

require "db.php";

// POSTメソッド以外は拒否
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: register.php?error=invalid_request");
    exit();
}

// エラーハンドリング関数
function redirectWithError($error_code, $username = '') {
    $url = "Location: register.php?error=" . urlencode($error_code);
    if ($username) {
        $url .= "&username=" . urlencode($username);
    }
    header($url);
    exit();
}

// 入力値取得とトリム
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';

// ===== 入力値検証 =====

// 必須項目チェック
if (empty($username) || empty($password)) {
    redirectWithError('empty');
}

// ユーザー名検証
if (strlen($username) < 3) {
    redirectWithError('username_short', $username);
}

if (strlen($username) > 50) {
    redirectWithError('username_long', $username);
}

// ユーザー名の文字種チェック（英数字とアンダースコアのみ）
if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    redirectWithError('username_invalid', $username);
}

// パスワード検証
if (strlen($password) < 8) {
    redirectWithError('password_short', $username);
}

// パスワード確認チェック
if (isset($password_confirm) && $password !== $password_confirm) {
    redirectWithError('password_mismatch', $username);
}

try {
    // ===== ユーザー名重複チェック =====
    $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $check->execute([$username]);
    if ($check->fetch()) {
        redirectWithError('username_exists', $username);
    }

    // ===== パスワードハッシュ化 =====
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    if ($hash === false) {
        throw new Exception("パスワードのハッシュ化に失敗しました");
    }

    // ===== 登録処理 =====
    // 最小限のカラムで登録
    $sql = "INSERT INTO users (username, password) VALUES (?, ?)";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$username, $hash]);

    if (!$result) {
        throw new Exception("ユーザー登録に失敗しました");
    }

    // ログ記録
    $user_id = $pdo->lastInsertId();
    error_log("New user registered: ID=$user_id, username=$username from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

    // ===== 登録成功 - ログイン画面へリダイレクト =====
    header("Location: login.php?registered=1");
    exit();

} catch (PDOException $e) {
    // データベースエラー
    error_log("Database error in register_check.php: " . $e->getMessage());
    redirectWithError('system', $username);
} catch (Exception $e) {
    // その他のエラー
    error_log("Error in register_check.php: " . $e->getMessage());
    redirectWithError('system', $username);
}