<?php
// セッションが開始されていない場合のみ開始
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Content-Type: text/html; charset=UTF-8");

// ログインチェック
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require "db.php";

// エスケープ用関数
function h($str){
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

$message = '';
$error = '';

// === 削除処理 ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM foods WHERE id = ?");
        $stmt->execute([$_POST['delete_id']]);
        $message = "食品を削除しました";
    } catch (PDOException $e) {
        $error = "削除に失敗しました: " . $e->getMessage();
    }
}

// === 新規追加処理 ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO foods (name, calorie, protein, fat, carb) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['name'],
            $_POST['calorie'],
            $_POST['protein'],
            $_POST['fat'],
            $_POST['carb']
        ]);
        $message = "食品を追加しました";
    } catch (PDOException $e) {
        $error = "追加に失敗しました: " . $e->getMessage();
    }
}

// === 更新処理 ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    try {
        $stmt = $pdo->prepare("UPDATE foods SET name=?, calorie=?, protein=?, fat=?, carb=? WHERE id=?");
        $stmt->execute([
            $_POST['name'],
            $_POST['calorie'],
            $_POST['protein'],
            $_POST['fat'],
            $_POST['carb'],
            $_POST['id']
        ]);
        $message = "食品を更新しました";
    } catch (PDOException $e) {
        $error = "更新に失敗しました: " . $e->getMessage();
    }
}

// === 検索処理 ===
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sql = "SELECT id, name, calorie, protein, fat, carb FROM foods";
if ($search !== '') {
    $sql .= " WHERE name LIKE ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['%' . $search . '%']);
} else {
    $sql .= " ORDER BY id ASC";
    $stmt = $pdo->query($sql);
}
$foods = $stmt->fetchAll();

// === 編集対象取得 ===
$edit_food = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM foods WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_food = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>食品管理</title>
<style>
* { box-sizing: border-box; }
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    background: #f5f5f5;
}
.container {
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
h2 {
    color: #333;
    border-bottom: 3px solid #4CAF50;
    padding-bottom: 10px;
}
.message {
    padding: 12px;
    margin: 15px 0;
    border-radius: 4px;
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}
.error {
    padding: 12px;
    margin: 15px 0;
    border-radius: 4px;
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
.form-section {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 5px;
    margin-bottom: 30px;
}
.form-group {
    margin-bottom: 15px;
}
label {
    display: inline-block;
    width: 120px;
    font-weight: bold;
    color: #555;
}
input[type="text"],
input[type="number"] {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    width: 200px;
    font-size: 14px;
}
input[type="text"]:focus,
input[type="number"]:focus {
    outline: none;
    border-color: #4CAF50;
}
button {
    padding: 10px 20px;
    background: #4CAF50;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    margin-right: 10px;
}
button:hover {
    background: #45a049;
}
button.cancel {
    background: #999;
}
button.cancel:hover {
    background: #777;
}
.search-box {
    margin-bottom: 20px;
}
.search-box input {
    width: 300px;
    margin-right: 10px;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}
th, td {
    border: 1px solid #ddd;
    padding: 12px;
    text-align: left;
}
th {
    background: #4CAF50;
    color: white;
    font-weight: bold;
}
tr:nth-child(even) {
    background: #f9f9f9;
}
tr:hover {
    background: #f0f0f0;
}
.actions {
    white-space: nowrap;
}
.actions a {
    margin-right: 10px;
    color: #4CAF50;
    text-decoration: none;
}
.actions a:hover {
    text-decoration: underline;
}
.actions form {
    display: inline;
}
.actions button {
    padding: 5px 10px;
    font-size: 12px;
    background: #f44336;
}
.actions button:hover {
    background: #da190b;
}
.back-link {
    display: inline-block;
    margin-top: 20px;
    color: #4CAF50;
    text-decoration: none;
}
.back-link:hover {
    text-decoration: underline;
}
</style>
</head>
<body>

<div class="container">
    <h2>🍽️ 食品管理（CRUD）</h2>

    <?php if ($message): ?>
        <div class="message"><?= h($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error"><?= h($error) ?></div>
    <?php endif; ?>

    <!-- 追加・編集フォーム -->
    <div class="form-section">
        <h3><?= $edit_food ? '食品編集' : '新規食品追加' ?></h3>
        <form method="POST">
            <?php if ($edit_food): ?>
                <input type="hidden" name="id" value="<?= h($edit_food['id']) ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label>食品名：</label>
                <input type="text" name="name" required 
                       value="<?= $edit_food ? h($edit_food['name']) : '' ?>">
            </div>
            
            <div class="form-group">
                <label>カロリー (kcal)：</label>
                <input type="number" step="0.1" name="calorie" required 
                       value="<?= $edit_food ? h($edit_food['calorie']) : '' ?>">
            </div>
            
            <div class="form-group">
                <label>タンパク質 (g)：</label>
                <input type="number" step="0.1" name="protein" required 
                       value="<?= $edit_food ? h($edit_food['protein']) : '' ?>">
            </div>
            
            <div class="form-group">
                <label>脂質 (g)：</label>
                <input type="number" step="0.1" name="fat" required 
                       value="<?= $edit_food ? h($edit_food['fat']) : '' ?>">
            </div>
            
            <div class="form-group">
                <label>炭水化物 (g)：</label>
                <input type="number" step="0.1" name="carb" required 
                       value="<?= $edit_food ? h($edit_food['carb']) : '' ?>">
            </div>
            
            <div class="form-group">
                <?php if ($edit_food): ?>
                    <button type="submit" name="update">更新</button>
                    <button type="button" class="cancel" onclick="location.href='foods.php'">キャンセル</button>
                <?php else: ?>
                    <button type="submit" name="add">追加</button>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- 検索フォーム -->
    <div class="search-box">
        <form method="GET">
            <input type="text" name="search" placeholder="食品名で検索..." 
                   value="<?= h($search) ?>">
            <button type="submit">🔍 検索</button>
            <?php if ($search): ?>
                <button type="button" class="cancel" onclick="location.href='foods.php'">クリア</button>
            <?php endif; ?>
        </form>
    </div>

    <!-- 食品一覧テーブル -->
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>食品名</th>
                <th>カロリー(kcal)</th>
                <th>P(g)</th>
                <th>F(g)</th>
                <th>C(g)</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($foods) > 0): ?>
                <?php foreach($foods as $food): ?>
                <tr>
                    <td><?= h($food['id']) ?></td>
                    <td><?= h($food['name']) ?></td>
                    <td><?= h($food['calorie']) ?></td>
                    <td><?= h($food['protein']) ?></td>
                    <td><?= h($food['fat']) ?></td>
                    <td><?= h($food['carb']) ?></td>
                    <td class="actions">
                        <a href="?edit=<?= h($food['id']) ?>">✏️ 編集</a>
                        <form method="POST" style="display:inline;" 
                              onsubmit="return confirm('本当に削除しますか？');">
                            <input type="hidden" name="delete_id" value="<?= h($food['id']) ?>">
                            <button type="submit">🗑️ 削除</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align:center; color:#999;">
                        <?= $search ? '検索結果が見つかりませんでした' : '食品データがありません' ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <a href="home.php" class="back-link">← ホームへ戻る</a>
</div>

</body>
</html>