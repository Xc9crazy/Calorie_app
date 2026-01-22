<?php
// セッションが開始されていない場合のみ開始
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Content-Type: text/html; charset=UTF-8");
require "db.php";

/* 未ログインならログイン画面へ */
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$today = $_GET['date'] ?? date('Y-m-d');

/* メッセージ表示 */
$message = $_GET['message'] ?? '';

/* XSS対策 */
function h($str){
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

try {
    /* ユーザー情報取得 */
    $sql = "SELECT username, height, weight, age, gender, activity_level, goal
            FROM users WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception("ユーザー情報が見つかりません");
    }

    /* 今日の食事一覧 */
    $sql = "
    SELECT
        meals.id,
        foods.name,
        meals.amount,
        foods.calorie * meals.amount / 100 AS calorie,
        foods.protein * meals.amount / 100 AS protein,
        foods.fat * meals.amount / 100 AS fat,
        foods.carb * meals.amount / 100 AS carb,
        meals.created_at
    FROM meals
    JOIN foods ON meals.food_id = foods.id
    WHERE meals.user_id = ?
    AND meals.meal_date = ?
    ORDER BY meals.created_at DESC, meals.id DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $today]);
    $meals = $stmt->fetchAll();

    /* 合計計算 */
    $total_calorie = 0;
    $total_protein = 0;
    $total_fat = 0;
    $total_carb = 0;

    foreach($meals as $m){
        $total_calorie += $m['calorie'];
        $total_protein += $m['protein'];
        $total_fat += $m['fat'];
        $total_carb += $m['carb'];
    }

    /* 目標カロリー計算 (Mifflin-St Jeor式) */
    $target_calorie = 2000; // デフォルト値
    $profile_incomplete = false;
    
    // 身体情報が全て入力されているかチェック
    if (empty($user['height']) || empty($user['weight']) || empty($user['age']) || empty($user['gender'])) {
        $profile_incomplete = true;
        $target_calorie = 2000; // デフォルト値
    } else {
        // BMR計算
        if($user['gender'] === 'male'){
            $bmr = 10*$user['weight'] + 6.25*$user['height'] - 5*$user['age'] + 5;
        } else {
            $bmr = 10*$user['weight'] + 6.25*$user['height'] - 5*$user['age'] - 161;
        }

        $activity_map = [
            'low' => 1.2,
            'normal' => 1.55,
            'high' => 1.75
        ];

        $tdee = $bmr * ($activity_map[$user['activity_level']] ?? 1.55);

        if($user['goal'] === 'bulk') $tdee += 300;
        if($user['goal'] === 'cut')  $tdee -= 300;

        $target_calorie = round($tdee);
    }
    
    $remain_calorie = $target_calorie - $total_calorie;

    /* 前日・翌日の日付計算 */
    $prev_date = date('Y-m-d', strtotime($today . ' -1 day'));
    $next_date = date('Y-m-d', strtotime($today . ' +1 day'));
    $is_today = ($today === date('Y-m-d'));

} catch (Exception $e) {
    $error_message = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ホーム - カロリー管理アプリ</title>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
* { 
    box-sizing: border-box; 
    margin: 0;
    padding: 0;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 20px;
}

.container {
    max-width: 1200px;
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
}

.header h1 {
    font-size: 28px;
    margin-bottom: 10px;
}

.header .user-info {
    font-size: 14px;
    opacity: 0.9;
}

.content {
    padding: 30px;
}

.message {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 8px;
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.error {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 8px;
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.warning {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 8px;
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffc107;
}

.warning a {
    color: #667eea;
    font-weight: bold;
    text-decoration: underline;
}

.date-selector {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
    margin-bottom: 30px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.date-selector button {
    padding: 10px 20px;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s;
}

.date-selector button:hover {
    background: #5568d3;
    transform: translateY(-2px);
}

.date-selector button:disabled {
    background: #ccc;
    cursor: not-allowed;
    transform: none;
}

.date-selector input[type="date"] {
    padding: 10px 15px;
    border: 2px solid #ddd;
    border-radius: 6px;
    font-size: 16px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.stat-card.success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}

.stat-card.warning {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.stat-card h3 {
    font-size: 14px;
    margin-bottom: 10px;
    opacity: 0.9;
}

.stat-card .value {
    font-size: 32px;
    font-weight: bold;
}

.stat-card .unit {
    font-size: 14px;
    opacity: 0.8;
}

.section {
    margin-bottom: 40px;
}

.section h2 {
    font-size: 24px;
    margin-bottom: 20px;
    color: #333;
    border-bottom: 3px solid #667eea;
    padding-bottom: 10px;
}

.meals-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.meals-table thead {
    background: #667eea;
    color: white;
}

.meals-table th,
.meals-table td {
    padding: 15px;
    text-align: left;
}

.meals-table th {
    font-weight: 600;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 0.5px;
}

.meals-table tbody tr {
    border-bottom: 1px solid #eee;
    transition: background 0.2s;
}

.meals-table tbody tr:hover {
    background: #f8f9fa;
}

.meals-table tbody tr:last-child {
    border-bottom: none;
}

.meals-table .number {
    text-align: right;
}

.delete-btn {
    padding: 6px 12px;
    background: #f5576c;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    transition: all 0.3s;
}

.delete-btn:hover {
    background: #e04455;
}

.empty-message {
    text-align: center;
    padding: 40px;
    color: #999;
    font-size: 16px;
}

.chart-container {
    max-width: 400px;
    margin: 0 auto;
}

.action-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-top: 30px;
    padding-top: 30px;
    border-top: 2px solid #eee;
}

.btn {
    padding: 12px 24px;
    background: #667eea;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.3s;
    display: inline-block;
}

.btn:hover {
    background: #5568d3;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.btn.secondary {
    background: #6c757d;
}

.btn.secondary:hover {
    background: #5a6268;
}

.btn.danger {
    background: #f5576c;
}

.btn.danger:hover {
    background: #e04455;
}

.progress-bar {
    width: 100%;
    height: 30px;
    background: #e9ecef;
    border-radius: 15px;
    overflow: hidden;
    margin: 10px 0;
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #11998e 0%, #38ef7d 100%);
    transition: width 0.5s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 12px;
}

.progress-fill.over {
    background: linear-gradient(90deg, #f093fb 0%, #f5576c 100%);
}

@media (max-width: 768px) {
    .date-selector {
        flex-direction: column;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .meals-table {
        font-size: 14px;
    }
    
    .meals-table th,
    .meals-table td {
        padding: 10px;
    }
}
</style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>🍽️ カロリー管理アプリ</h1>
        <div class="user-info">
            ようこそ、<?= h($user['username'] ?? 'ゲスト') ?> さん
        </div>
    </div>

    <div class="content">
        <?php if (isset($error_message)): ?>
            <div class="error">❌ <?= h($error_message) ?></div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="message">
                ✅ 
                <?php
                switch($message) {
                    case 'added':
                        echo '食事を追加しました。';
                        break;
                    case 'deleted':
                        echo '食事を削除しました。';
                        break;
                    default:
                        echo '操作が完了しました。';
                }
                ?>
            </div>
        <?php endif; ?>

        <?php if ($profile_incomplete): ?>
            <div class="warning">
                ⚠️ <strong>プロフィール情報が未設定です</strong><br>
                正確な目標カロリーを計算するために、<a href="user_edit.php?id=<?= $user_id ?>">プロフィール</a>で身長・体重・年齢・性別を登録してください。<br>
                現在はデフォルト値（2000kcal）を使用しています。
            </div>
        <?php endif; ?>

        <!-- 日付選択 -->
        <div class="date-selector">
            <form method="get" style="display: contents;">
                <button type="submit" name="date" value="<?= h($prev_date) ?>">
                    ← 前日
                </button>
                
                <input type="date" name="date" value="<?= h($today) ?>" 
                       onchange="this.form.submit()">
                
                <button type="submit" name="date" value="<?= h($next_date) ?>"
                        <?= $is_today ? 'disabled' : '' ?>>
                    翌日 →
                </button>
                
                <?php if (!$is_today): ?>
                    <button type="submit" name="date" value="<?= date('Y-m-d') ?>">
                        今日に戻る
                    </button>
                <?php endif; ?>
            </form>
        </div>

        <!-- 統計カード -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>摂取カロリー</h3>
                <div class="value"><?= round($total_calorie) ?></div>
                <div class="unit">kcal</div>
            </div>
            
            <div class="stat-card success">
                <h3>目標カロリー</h3>
                <div class="value"><?= $target_calorie ?></div>
                <div class="unit">kcal</div>
            </div>
            
            <div class="stat-card <?= $remain_calorie < 0 ? 'warning' : '' ?>">
                <h3>残り</h3>
                <div class="value"><?= round($remain_calorie) ?></div>
                <div class="unit">kcal</div>
            </div>
        </div>

        <!-- 進捗バー -->
        <?php 
        $progress = ($target_calorie > 0) ? ($total_calorie / $target_calorie * 100) : 0;
        $progress_display = min($progress, 100);
        ?>
        <div class="progress-bar">
            <div class="progress-fill <?= $total_calorie > $target_calorie ? 'over' : '' ?>" 
                 style="width: <?= $progress_display ?>%">
                <?= round($progress) ?>%
            </div>
        </div>

        <!-- 食事一覧 -->
        <div class="section">
            <h2>📋 今日の食事記録</h2>
            
            <?php if (count($meals) > 0): ?>
                <table class="meals-table">
                    <thead>
                        <tr>
                            <th>食品名</th>
                            <th class="number">量 (g)</th>
                            <th class="number">カロリー</th>
                            <th class="number">P (g)</th>
                            <th class="number">F (g)</th>
                            <th class="number">C (g)</th>
                            <th style="text-align: center;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($meals as $m): ?>
                        <tr>
                            <td><?= h($m['name']) ?></td>
                            <td class="number"><?= h($m['amount']) ?></td>
                            <td class="number"><?= round($m['calorie'], 1) ?></td>
                            <td class="number"><?= round($m['protein'], 1) ?></td>
                            <td class="number"><?= round($m['fat'], 1) ?></td>
                            <td class="number"><?= round($m['carb'], 1) ?></td>
                            <td style="text-align: center;">
                                <form method="POST" action="delete_meal.php" style="display: inline;"
                                      onsubmit="return confirm('この食事を削除しますか？');">
                                    <input type="hidden" name="id" value="<?= h($m['id']) ?>">
                                    <input type="hidden" name="return_date" value="<?= h($today) ?>">
                                    <button type="submit" class="delete-btn">🗑️ 削除</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <!-- 合計行 -->
                        <tr style="background: #f8f9fa; font-weight: bold;">
                            <td>合計</td>
                            <td class="number">-</td>
                            <td class="number"><?= round($total_calorie, 1) ?></td>
                            <td class="number"><?= round($total_protein, 1) ?></td>
                            <td class="number"><?= round($total_fat, 1) ?></td>
                            <td class="number"><?= round($total_carb, 1) ?></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-message">
                    まだ食事が登録されていません<br>
                    「食事を追加」ボタンから記録を始めましょう！
                </div>
            <?php endif; ?>
        </div>

        <!-- PFCバランスグラフ -->
        <?php if ($total_protein + $total_fat + $total_carb > 0): ?>
        <div class="section">
            <h2>📊 PFCバランス</h2>
            <div class="chart-container">
                <canvas id="pfcChart"></canvas>
            </div>
        </div>
        <?php endif; ?>

        <!-- アクションボタン -->
        <div class="action-buttons">
            <a href="meal_add.php?date=<?= h($today) ?>" class="btn">
                ➕ 食事を追加
            </a>
            <a href="foods.php" class="btn secondary">
                🍱 食品マスタ管理
            </a>
            <a href="users_list.php" class="btn secondary">
                👥 プロフィール
            </a>
            <a href="logout.php" class="btn danger">
                🚪 ログアウト
            </a>
        </div>
    </div>
</div>

<?php if ($total_protein + $total_fat + $total_carb > 0): ?>
<script>
const protein = <?= json_encode(round($total_protein, 1)) ?>;
const fat     = <?= json_encode(round($total_fat, 1)) ?>;
const carb    = <?= json_encode(round($total_carb, 1)) ?>;

const ctx = document.getElementById('pfcChart');

new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['たんぱく質 (P)', '脂質 (F)', '炭水化物 (C)'],
        datasets: [{
            data: [protein, fat, carb],
            backgroundColor: [
                '#4CAF50',
                '#FFC107',
                '#2196F3'
            ],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 15,
                    font: {
                        size: 14
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.label || '';
                        let value = context.parsed || 0;
                        let total = context.dataset.data.reduce((a, b) => a + b, 0);
                        let percentage = ((value / total) * 100).toFixed(1);
                        return label + ': ' + value + 'g (' + percentage + '%)';
                    }
                }
            }
        }
    }
});
</script>
<?php endif; ?>

</body>
</html>