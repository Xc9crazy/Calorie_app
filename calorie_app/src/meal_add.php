<?php
// セッションが開始されていない場合のみ開始
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Content-Type: text/html; charset=UTF-8");
require "db.php";

/* 未ログインチェック */
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

// エラーとパラメータ取得
$error = $_GET['error'] ?? '';
$selected_food_id = $_GET['food_id'] ?? '';
$amount = $_GET['amount'] ?? '';
$meal_date = $_GET['meal_date'] ?? ($_GET['date'] ?? date('Y-m-d'));
$meal_type = $_GET['meal_type'] ?? '';

// 検索キーワード
$search = $_GET['search'] ?? '';

/* 食品一覧取得（検索対応） */
if ($search !== '') {
    $sql = "SELECT id, name, calorie, protein, fat, carb, category 
            FROM foods 
            WHERE name LIKE ? 
            ORDER BY name ASC 
            LIMIT 100";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['%' . $search . '%']);
} else {
    $sql = "SELECT id, name, calorie, protein, fat, carb, category 
            FROM foods 
            ORDER BY category, name ASC 
            LIMIT 100";
    $stmt = $pdo->query($sql);
}
$foods = $stmt->fetchAll();

// カテゴリでグループ化
$foods_by_category = [];
foreach ($foods as $food) {
    $category = $food['category'] ?? 'その他';
    if (!isset($foods_by_category[$category])) {
        $foods_by_category[$category] = [];
    }
    $foods_by_category[$category][] = $food;
}

function h($str){
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>食事登録 - カロリー管理アプリ</title>
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
    padding: 20px;
}

.container {
    max-width: 800px;
    margin: 0 auto;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    overflow: hidden;
}

.header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    text-align: center;
}

.header h1 {
    font-size: 28px;
    margin-bottom: 10px;
}

.header p {
    font-size: 14px;
    opacity: 0.9;
}

.content {
    padding: 30px;
}

.message {
    padding: 15px;
    margin-bottom: 25px;
    border-radius: 8px;
    font-size: 14px;
    animation: slideDown 0.3s ease;
}

.message.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.search-box {
    margin-bottom: 30px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.search-box h3 {
    font-size: 16px;
    margin-bottom: 15px;
    color: #333;
}

.search-input-wrapper {
    display: flex;
    gap: 10px;
}

.search-box input[type="text"] {
    flex: 1;
    padding: 12px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 15px;
}

.search-box input[type="text"]:focus {
    outline: none;
    border-color: #667eea;
}

.search-box button {
    padding: 12px 24px;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
}

.search-box button:hover {
    background: #5568d3;
}

.form-group {
    margin-bottom: 25px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
    font-size: 14px;
}

.required {
    color: #f5576c;
}

.form-group select,
.form-group input[type="number"],
.form-group input[type="date"],
.form-group textarea {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 15px;
    transition: all 0.3s;
    background: #f8f9fa;
}

.form-group select:focus,
.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #667eea;
    background: white;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-group select {
    cursor: pointer;
}

.form-group select optgroup {
    font-weight: bold;
    font-style: normal;
}

.form-group select option {
    padding: 8px;
}

.food-info {
    display: none;
    margin-top: 15px;
    padding: 15px;
    background: #e8f5e9;
    border-radius: 8px;
    border: 1px solid #c8e6c9;
}

.food-info.show {
    display: block;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.food-info h4 {
    font-size: 14px;
    margin-bottom: 10px;
    color: #2e7d32;
}

.nutrition-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
}

.nutrition-item {
    text-align: center;
    padding: 8px;
    background: white;
    border-radius: 6px;
}

.nutrition-item .label {
    font-size: 11px;
    color: #666;
    margin-bottom: 4px;
}

.nutrition-item .value {
    font-size: 16px;
    font-weight: bold;
    color: #2e7d32;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.quick-amount {
    display: flex;
    gap: 10px;
    margin-top: 10px;
}

.quick-amount button {
    padding: 8px 16px;
    background: #f0f0f0;
    border: 2px solid #e0e0e0;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    transition: all 0.2s;
}

.quick-amount button:hover {
    background: #667eea;
    color: white;
    border-color: #667eea;
}

.submit-btn {
    width: 100%;
    padding: 16px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    margin-top: 20px;
}

.submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
}

.back-link {
    display: inline-block;
    margin-top: 20px;
    color: #667eea;
    text-decoration: none;
    font-weight: 600;
}

.back-link:hover {
    text-decoration: underline;
}

@media (max-width: 600px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .nutrition-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .quick-amount {
        flex-wrap: wrap;
    }
}
</style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>🍽️ 食事登録</h1>
        <p>食べたものを記録しましょう</p>
    </div>

    <div class="content">
        <?php if($error): ?>
            <div class="message error">
                ❌ 
                <?php
                switch($error) {
                    case 'empty':
                        echo '必須項目を入力してください。';
                        break;
                    case 'invalid_type':
                        echo '入力値が正しくありません。';
                        break;
                    case 'invalid_amount':
                        echo '量は1〜10000gの範囲で入力してください。';
                        break;
                    case 'invalid_date':
                        echo '日付の形式が正しくありません。';
                        break;
                    case 'future_date':
                        echo '未来の日付は登録できません。';
                        break;
                    case 'food_not_found':
                        echo '選択された食品が見つかりません。';
                        break;
                    case 'insert_failed':
                        echo '登録に失敗しました。';
                        break;
                    case 'system':
                        echo 'システムエラーが発生しました。';
                        break;
                    default:
                        echo '登録に失敗しました。';
                }
                ?>
            </div>
        <?php endif; ?>

        <!-- 食品検索 -->
        <div class="search-box">
            <h3>🔍 食品を検索</h3>
            <form method="GET" class="search-input-wrapper">
                <input type="hidden" name="date" value="<?= h($meal_date) ?>">
                <input type="text" 
                       name="search" 
                       placeholder="食品名で検索（例: 鶏肉、ごはん）"
                       value="<?= h($search) ?>">
                <button type="submit">検索</button>
                <?php if ($search): ?>
                    <button type="button" onclick="location.href='meal_add.php?date=<?= h($meal_date) ?>'">
                        クリア
                    </button>
                <?php endif; ?>
            </form>
        </div>

        <!-- 登録フォーム -->
        <form action="meal_add_check.php" method="POST" id="mealForm">
            <div class="form-group">
                <label for="food_id">
                    食品 <span class="required">*</span>
                </label>
                <select name="food_id" id="food_id" required onchange="showFoodInfo()">
                    <option value="">選択してください</option>
                    <?php if ($search): ?>
                        <!-- 検索結果 -->
                        <?php foreach($foods as $f): ?>
                            <option value="<?= h($f['id']) ?>"
                                    data-calorie="<?= h($f['calorie']) ?>"
                                    data-protein="<?= h($f['protein']) ?>"
                                    data-fat="<?= h($f['fat']) ?>"
                                    data-carb="<?= h($f['carb']) ?>"
                                    <?= $selected_food_id == $f['id'] ? 'selected' : '' ?>>
                                <?= h($f['name']) ?> (<?= h($f['calorie']) ?>kcal/100g)
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- カテゴリ別 -->
                        <?php foreach($foods_by_category as $category => $items): ?>
                            <optgroup label="<?= h($category) ?>">
                                <?php foreach($items as $f): ?>
                                    <option value="<?= h($f['id']) ?>"
                                            data-calorie="<?= h($f['calorie']) ?>"
                                            data-protein="<?= h($f['protein']) ?>"
                                            data-fat="<?= h($f['fat']) ?>"
                                            data-carb="<?= h($f['carb']) ?>"
                                            <?= $selected_food_id == $f['id'] ? 'selected' : '' ?>>
                                        <?= h($f['name']) ?> (<?= h($f['calorie']) ?>kcal/100g)
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                
                <!-- 食品情報表示エリア -->
                <div class="food-info" id="foodInfo">
                    <h4>100gあたりの栄養成分</h4>
                    <div class="nutrition-grid">
                        <div class="nutrition-item">
                            <div class="label">カロリー</div>
                            <div class="value" id="infoCalorie">-</div>
                        </div>
                        <div class="nutrition-item">
                            <div class="label">タンパク質</div>
                            <div class="value" id="infoProtein">-</div>
                        </div>
                        <div class="nutrition-item">
                            <div class="label">脂質</div>
                            <div class="value" id="infoFat">-</div>
                        </div>
                        <div class="nutrition-item">
                            <div class="label">炭水化物</div>
                            <div class="value" id="infoCarb">-</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="amount">
                    量 (g) <span class="required">*</span>
                </label>
                <input type="number" 
                       name="amount" 
                       id="amount"
                       step="0.1" 
                       min="1"
                       max="10000"
                       required
                       value="<?= h($amount) ?>"
                       placeholder="100">
                
                <!-- クイック入力ボタン -->
                <div class="quick-amount">
                    <button type="button" onclick="setAmount(50)">50g</button>
                    <button type="button" onclick="setAmount(100)">100g</button>
                    <button type="button" onclick="setAmount(150)">150g</button>
                    <button type="button" onclick="setAmount(200)">200g</button>
                    <button type="button" onclick="setAmount(300)">300g</button>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="meal_date">
                        日付 <span class="required">*</span>
                    </label>
                    <input type="date" 
                           name="meal_date" 
                           id="meal_date"
                           value="<?= h($meal_date) ?>" 
                           max="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                           required>
                </div>

                <div class="form-group">
                    <label for="meal_type">食事タイプ</label>
                    <select name="meal_type" id="meal_type">
                        <option value="">選択なし</option>
                        <option value="breakfast" <?= $meal_type === 'breakfast' ? 'selected' : '' ?>>朝食</option>
                        <option value="lunch" <?= $meal_type === 'lunch' ? 'selected' : '' ?>>昼食</option>
                        <option value="dinner" <?= $meal_type === 'dinner' ? 'selected' : '' ?>>夕食</option>
                        <option value="snack" <?= $meal_type === 'snack' ? 'selected' : '' ?>>間食</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="note">メモ（任意）</label>
                <textarea name="note" 
                          id="note" 
                          rows="3" 
                          placeholder="例: レストランで食べた、自炊、など"></textarea>
            </div>

            <button type="submit" class="submit-btn">
                ✅ 登録する
            </button>
        </form>

        <a href="home.php?date=<?= h($meal_date) ?>" class="back-link">
            ← ホームへ戻る
        </a>
    </div>
</div>

<script>
function showFoodInfo() {
    const select = document.getElementById('food_id');
    const option = select.options[select.selectedIndex];
    const foodInfo = document.getElementById('foodInfo');
    
    if (option.value) {
        const calorie = option.dataset.calorie;
        const protein = option.dataset.protein;
        const fat = option.dataset.fat;
        const carb = option.dataset.carb;
        
        document.getElementById('infoCalorie').textContent = calorie + ' kcal';
        document.getElementById('infoProtein').textContent = protein + ' g';
        document.getElementById('infoFat').textContent = fat + ' g';
        document.getElementById('infoCarb').textContent = carb + ' g';
        
        foodInfo.classList.add('show');
    } else {
        foodInfo.classList.remove('show');
    }
}

function setAmount(value) {
    document.getElementById('amount').value = value;
}

// ページロード時に選択されている食品の情報を表示
window.addEventListener('DOMContentLoaded', function() {
    showFoodInfo();
});
</script>

</body>
</html>