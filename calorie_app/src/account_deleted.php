<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>アカウント削除完了</title>
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
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.container {
    background: white;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    padding: 60px 40px;
    text-align: center;
    max-width: 500px;
}

.icon {
    font-size: 64px;
    margin-bottom: 20px;
}

h1 {
    font-size: 28px;
    color: #333;
    margin-bottom: 20px;
}

p {
    color: #666;
    line-height: 1.6;
    margin-bottom: 30px;
}

.btn {
    display: inline-block;
    padding: 14px 28px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}
</style>
</head>
<body>

<div class="container">
    <div class="icon">✅</div>
    <h1>アカウントを削除しました</h1>
    <p>
        ご利用ありがとうございました。<br>
        すべてのデータが削除されました。
    </p>
    <a href="login.php" class="btn">トップページへ</a>
</div>

</body>
</html>