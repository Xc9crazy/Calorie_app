<?php

if (getenv('APP_ENV') !== 'production') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

mb_internal_encoding('UTF-8');

try {
    $db_host = getenv('DB_HOST') ?: 'mysql';
    $db_name = getenv('DB_NAME') ?: 'calorie_db';
    $db_user = getenv('DB_USER') ?: 'calorie_user';
    $db_pass = getenv('DB_PASS') ?: 'calorie_pass';

    $dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";
    
    $pdo = new PDO(
        $dsn,
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false,
            PDO::ATTR_TIMEOUT => 5,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    );
    
    // タイムゾーン設定
    $pdo->exec("SET time_zone = '+09:00'");

} catch (PDOException $e) {
    if (getenv('APP_ENV') === 'production') {
        error_log("Database connection error: " . $e->getMessage());
        http_response_code(503);
        exit('サービスが一時的に利用できません。しばらくしてから再度お試しください。');
    } else {
        http_response_code(503);
        echo "<h1>データベース接続エラー</h1>";
        echo "<p><strong>エラーメッセージ:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>対処方法:</strong></p>";
        echo "<ul>";
        echo "<li>Dockerコンテナが起動しているか確認してください</li>";
        echo "<li>init.sqlでデータベースが初期化されているか確認してください</li>";
        echo "<li>db.phpの接続情報が正しいか確認してください</li>";
        echo "</ul>";
        exit();
    }
}