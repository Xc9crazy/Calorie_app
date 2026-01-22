# Calorie_app
最終定義書

やり残していること一覧

フレームワークに移行

Laravel（PHP）
Next.js + Prisma（モダンスタック）


テスト導入

PHPUnit
Selenium（E2Eテスト）


CI/CD構築

GitHub Actions
自動デプロイ


本番環境デプロイ

AWS / Heroku / Vercel
HTTPS設定
CDN設定


**定義書**


目次
システム概要
機能要件定義
データベース設計
画面定義
API定義
セキュリティ仕様
非機能要件
開発環境
1. システム概要
1.1 システム名
カロリー管理アプリ (Calorie Management System)
1.2 目的
日々の食事記録を通じてカロリー・栄養素（PFC）を管理し、ユーザーの健康的な食生活をサポートする。
1.3 対象ユーザー
ダイエット・減量を目指す人
筋トレ・増量を目指す人
栄養バランスを意識したい人
健康管理に興味がある一般ユーザー
1.4 システム構成

┌─────────────────────────────────────┐
│         ユーザー（ブラウザ）           │
└──────────────┬──────────────────────┘
               │ HTTP/HTTPS
┌──────────────▼──────────────────────┐
│         Webサーバー（nginx）          │
└──────────────┬──────────────────────┘
               │
┌──────────────▼──────────────────────┐
│      アプリケーション（PHP 8.x）       │
│   - セッション管理                    │
│   - ビジネスロジック                  │
│   - バリデーション                    │
└──────────────┬──────────────────────┘
               │ PDO
┌──────────────▼──────────────────────┐
│      データベース（MySQL 8.0）         │
│   - users, foods, meals              │
└─────────────────────────────────────┘

1.5 技術スタックレイヤー技術
フロントエンドHTML5, CSS3, JavaScript (ES6+), Chart.js
バックエンドPHP 8.x
データベースMySQL 8.0
Webサーバーnginx
コンテナDocker, Docker Compose
文字コードUTF-8 (utf8mb4)

3. 機能要件定義
1 機能一覧ID機能名概要優先度
F001ユーザー登録新規ユーザーアカウント作成高
F002ログイン認証によるシステムアクセス高
F003ログアウトセッション終了高
F004プロフィール管理身体情報・目標設定高
F005食事記録追加日々の食事を記録高
F006食事記録削除登録した食事の削除高
F007食事記録閲覧日別の食事履歴表示高
F008カロリー計算摂取・目標・残りカロリー表示高
F009PFCバランス表示タンパク質・脂質・炭水化物のグラフ中
F010食品マスタ管理食品データのCRUD操作高
F011食品検索食品名での検索中
F012日付移動前日・翌日への移動中
2 詳細機能定義F001: ユーザー登録概要
新規ユーザーがアカウントを作成する。入力項目
項目型必須制約ユーザー名VARCHAR(50)○3-50文字、英数字_のみ、重複不可パスワードVARCHAR(255)○8文字以上、ハッシュ化保存パスワード確認VARCHAR(255)○パスワードと一致処理フロー

入力値バリデーション
ユーザー名重複チェック
パスワードハッシュ化（bcrypt, cost=12）
データベース登録
ログイン画面へリダイレクト
エラーパターン

E001: 必須項目未入力
E002: ユーザー名が短い（3文字未満）
E003: ユーザー名が長い（50文字超）
E004: ユーザー名に使用不可文字
E005: ユーザー名重複
E006: パスワードが短い（8文字未満）
E007: パスワード不一致
E008: システムエラー
F002: ログイン概要
登録済みユーザーがシステムにログインする。入力項目
項目型必須ユーザー名VARCHAR(50)○パスワードVARCHAR(255)○処理フロー

入力値バリデーション
ユーザー存在確認
パスワード検証（password_verify）
セッション再生成（CSRF対策）
user_id, usernameをセッションに保存
ホーム画面へリダイレクト
セキュリティ対策

セッション固定攻撃対策（session_regenerate_id）
ログイン試行回数制限（オプション）
エラーメッセージの曖昧化
F004: プロフィール管理概要
身体情報と目標を設定し、目標カロリーを計算する。入力項目
項目型必須制約ユーザー名VARCHAR(50)○3-50文字身長FLOAT-100-250cm体重FLOAT-30-300kg年齢INT-10-120歳性別ENUM-male/female活動レベルENUM-low/normal/high目標ENUM-bulk/maintain/cut目標カロリー計算式（Mifflin-St Jeor式）【基礎代謝量（BMR）】
男性: BMR = 10 × 体重(kg) + 6.25 × 身長(cm) - 5 × 年齢 + 5
女性: BMR = 10 × 体重(kg) + 6.25 × 身長(cm) - 5 × 年齢 - 161

【総消費エネルギー（TDEE）】
TDEE = BMR × 活動レベル係数

活動レベル係数:
- low (低い): 1.2
- normal (普通): 1.55
- high (高い): 1.75

【目標カロリー】
- bulk (増量): TDEE + 300kcal
- maintain (維持): TDEE
- cut (減量): TDEE - 300kcalデフォルト値

身体情報未設定時: 2000kcal
F005: 食事記録追加概要
食品と量を選択し、食事記録を追加する。入力項目
項目型必須制約食品IDINT○存在する食品量FLOAT○1-10000g日付DATE○過去〜明日まで食事タイプENUM-breakfast/lunch/dinner/snackメモTEXT--処理フロー

入力値バリデーション
食品存在確認
トランザクション開始
meals テーブルに挿入
コミット
ホーム画面へリダイレクト
栄養計算
カロリー = 食品のカロリー(100gあたり) × 量(g) / 100
タンパク質 = 食品のタンパク質(100gあたり) × 量(g) / 100
脂質 = 食品の脂質(100gあたり) × 量(g) / 100
炭水化物 = 食品の炭水化物(100gあたり) × 量(g) / 100F008: カロリー計算概要
日別の摂取カロリー、目標カロリー、残りカロリーを計算・表示する。計算式
摂取カロリー = Σ(各食事のカロリー)
目標カロリー = TDEEベースの計算（F004参照）
残りカロリー = 目標カロリー - 摂取カロリー
進捗率 = (摂取カロリー / 目標カロリー) × 100表示仕様

摂取カロリー: 紫のグラデーションカード
目標カロリー: 緑のグラデーションカード
残りカロリー:

プラス時: 通常カード
マイナス時（オーバー）: 赤のグラデーションカード


進捗バー:

0-100%: 緑のグラデーション
100%超: 赤のグラデーション


F009: PFCバランス表示概要
タンパク質（P）・脂質（F）・炭水化物（C）の割合をドーナツグラフで表示する。表示条件

P + F + C > 0 の場合のみ表示
グラフ仕様

タイプ: ドーナツグラフ（Chart.js）
色:

タンパク質: 緑 (#4CAF50)
脂質: 黄 (#FFC107)
炭水化物: 青 (#2196F3)


ツールチップ: 値(g) と 割合(%)
F010: 食品マスタ管理概要
食品データの作成・読取・更新・削除を行う。データ項目
項目型必須単位食品名VARCHAR(100)○-カロリーFLOAT○kcal/100gタンパク質FLOAT○g/100g脂質FLOAT○g/100g炭水化物FLOAT○g/100gカテゴリVARCHAR(50)--カテゴリ例

穀類
肉類
魚類
卵類
乳製品
豆類
野菜類
果物類
ナッツ類
CRUD操作C (Create) - 新規追加

画面上部のフォームから入力
全項目入力後、「追加」ボタンをクリック
R (Read) - 一覧表示

テーブル形式で全食品を表示
ID, 食品名, カロリー, P, F, C を表示
U (Update) - 更新

一覧の「編集」リンクをクリック
フォームに既存値が入力された状態で表示
編集後「更新」ボタンをクリック
D (Delete) - 削除

一覧の「削除」ボタンをクリック
確認ダイアログ表示
削除実行
制約: meals テーブルで使用中の食品は削除不可（外部キー制約）
3. データベース設計3.1 ER図┌─────────────────┐
│     users       │
├─────────────────┤
│ id (PK)         │
│ username        │
│ password        │
│ height          │
│ weight          │
│ age             │
│ gender          │
│ activity_level  │
│ goal            │
│ created_at      │
└────────┬────────┘
         │ 1
         │
         │ N
┌────────▼────────┐       N ┌─────────────────┐
│     meals       ├─────────┤     foods       │
├─────────────────┤    1    ├─────────────────┤
│ id (PK)         │         │ id (PK)         │
│ user_id (FK)    │         │ name            │
│ food_id (FK)    │         │ calorie         │
│ amount          │         │ protein         │
│ meal_date       │         │ fat             │
│ meal_type       │         │ carb            │
│ note            │         │ category        │
│ created_at      │         │ created_at      │
└─────────────────┘         └─────────────────┘

3.2 テーブル定義
3.2.1 users テーブル用途: ユーザー情報の管理カラム名型NULLデフォルト説明idINTNOAUTO_INCREMENTユーザーID（主キー）usernameVARCHAR(50)NO-ユーザー名（一意）passwordVARCHAR(255)NO-パスワード（bcryptハッシュ）heightFLOATYESNULL身長（cm）weightFLOATYESNULL体重（kg）ageINTYESNULL年齢genderENUM('male','female')YESNULL性別activity_levelENUM('low','normal','high')NO'normal'活動レベルgoalENUM('bulk','maintain','cut')NO'maintain'目標created_atTIMESTAMPNOCURRENT_TIMESTAMP登録日時インデックス

PRIMARY KEY: id
UNIQUE KEY: username
制約

username: 重複不可
password: NOT NULL（必須）
3.2.2 foods テーブル用途: 食品マスタデータカラム名型NULLデフォルト説明idINTNOAUTO_INCREMENT食品ID（主キー）nameVARCHAR(100)NO-食品名calorieFLOATNO0カロリー（kcal/100g）proteinFLOATNO0タンパク質（g/100g）fatFLOATNO0脂質（g/100g）carbFLOATNO0炭水化物（g/100g）categoryVARCHAR(50)YESNULLカテゴリcreated_atTIMESTAMPNOCURRENT_TIMESTAMP登録日時インデックス

PRIMARY KEY: id
INDEX: name（検索用）
初期データ例
sqlINSERT INTO foods (name, calorie, protein, fat, carb, category) VALUES
('白米（ごはん）', 168, 2.5, 0.3, 37.1, '穀類'),
('鶏むね肉（皮なし）', 108, 22.3, 1.5, 0, '肉類'),
('卵（全卵）', 151, 12.3, 10.3, 0.3, '卵類');

3.2.3 meals テーブル用途: 食事記録データカラム名型NULLデフォルト説明idINTNOAUTO_INCREMENT食事記録ID（主キー）user_idINTNO-ユーザーID（外部キー）food_idINTNO-食品ID（外部キー）amountFLOATNO-量（g）meal_dateDATENO-食事日meal_typeENUM('breakfast','lunch','dinner','snack')YESNULL食事タイプnoteTEXTYESNULLメモcreated_atTIMESTAMPNOCURRENT_TIMESTAMP登録日時インデックス

PRIMARY KEY: id
INDEX: user_id, meal_date（検索用）
INDEX: food_id
外部キー制約
sqlCONSTRAINT fk_meals_user
  FOREIGN KEY (user_id) REFERENCES users(id)
  ON DELETE CASCADE,

CONSTRAINT fk_meals_food
  FOREIGN KEY (food_id) REFERENCES foods(id)
  ON DELETE RESTRICT制約の意味

user削除時: 関連するmealsも削除（CASCADE）
food削除時: 使用中のfoodは削除不可（RESTRICT）
3.3 データベース作成SQLsql-- データベース作成
DROP DATABASE IF EXISTS calorie_db;
CREATE DATABASE calorie_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE calorie_db;

-- usersテーブル
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  height FLOAT,
  weight FLOAT,
  age INT,
  gender ENUM('male','female'),
  activity_level ENUM('low','normal','high') DEFAULT 'normal',
  goal ENUM('bulk','maintain','cut') DEFAULT 'maintain',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- foodsテーブル
CREATE TABLE foods (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  calorie FLOAT DEFAULT 0,
  protein FLOAT DEFAULT 0,
  fat FLOAT DEFAULT 0,
  carb FLOAT DEFAULT 0,
  category VARCHAR(50) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_name (name)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- mealsテーブル
CREATE TABLE meals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  food_id INT NOT NULL,
  amount FLOAT NOT NULL,
  meal_date DATE NOT NULL,
  meal_type ENUM('breakfast','lunch','dinner','snack') DEFAULT NULL,
  note TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user_date (user_id, meal_date),
  CONSTRAINT fk_meals_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_meals_food FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE RESTRICT
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
4. 画面定義
4.1 
画面一覧ID
画面名ファイル名アクセス権
S001ログイン画面login.php全員
S002新規登録画面register.php全員
S003ホーム画面home.phpログインユーザー
S004食事追加画面meal_add.phpログインユーザー
S005食品管理画面foods.phpログインユーザー
S006プロフィール一覧画面users_list.phpログインユーザー
S007プロフィール編集画面user_edit.phpログインユーザー

API定義

### 5.1 処理ファイル一覧

| ファイル名 | メソッド | 説明 | リダイレクト先 |
|-----------|---------|------|---------------|
| login_check.php | POST | ログイン処理 | home.php / login.php |
| register_check.php | POST | 新規登録処理 | login.php?registered=1 |
| logout.php | GET | ログアウト処理 | login.php?logout=1 |
| user_update.php | POST | プロフィール更新 | users_list.php?message=updated |
| meal_add_check.php | POST | 食事追加処理 | home.php?date=X&message=added |
| delete_meal.php | POST | 食事削除処理 | home.php?date=X&message=deleted |

---

### 5.2 処理詳細

#### login_check.php

**リクエスト**
```
POST /login_check.php
Content-Type: application/x-www-form-urlencoded

username=akito
password=test1234
csrf_token=abc123...
```

**処理フロー**
1. POSTメソッドチェック
2. 入力値バリデーション
3. データベース照会
4. パスワード検証
5. セッション再生成
6. user_id, username保存
7. リダイレクト

**レスポンス（成功）**
```
302 Found
Location: home.php
Set-Cookie: PHPSESSID=...
```

**レスポンス（失敗）**
```
302 Found
Location: login.php?error=invalid
```

---

#### meal_add_check.php

**リクエスト**
```
POST /meal_add_check.php
Content-Type: application/x-www-form-urlencoded

food_id=1
amount=150
meal_date=2026-01-22
meal_type=lunch
note=
```

**バリデーション**
- food_id: 数値、存在確認
- amount: 1-10000
- meal_date: 有効な日付、過去〜明日
- meal_type: breakfast/lunch/dinner/snack or NULL

**処理フロー**
1. POSTメソッドチェック
2. ログインチェック
3. 入力値バリデーション
4. トランザクション開始
5. 食品存在確認
6. meals挿入
7. コミット
8. リダイレクト

**レスポンス**
```
302 Found
Location: home.php?date=2026-01-22&message=added
6. セキュリティ仕様
6.1 セキュリティ対策一覧
脅威対策実装箇所SQLインジェクションプリペアドステートメント全SQLクエリ
XSShtmlspecialchars()全出力箇所
CSRFトークン検証login.phpセッション固定攻撃session_regenerate_id()login_check.php
パスワード漏洩bcryptハッシュ化register_check.php
ブルートフォース攻撃試行回数制限(オプション)権限昇格権限チェック全処理ファイル

6.2 パスワードハッシュ化仕様
アルゴリズム: bcrypt
コスト: 12
実装:
php$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
検証:
phpif (password_verify($input_password, $stored_hash)) {
    // 認証成功
}

6.3 セッション管理仕様
セッション開始
phpif (session_status() === PHP_SESSION_NONE) {
    session_start();
}
保存データ

user_id: ユーザーID
username: ユーザー名
login_time: ログイン時刻（オプション）

セッション破棄
php$_SESSION = [];
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}
session_destroy();

6.4 入力値検証仕様
ユーザー名

長さ: 3-50文字
文字種: 英数字とアンダースコア
正規表現: /^[a-zA-Z0-9_]{3,50}$/

パスワード

長さ: 8文字以上
文字種: 制限なし
確認入力: 必須

数値（身長・体重・年齢・量）

型チェック: is_numeric()
範囲チェック: min/max
例: $height >= 100 && $height <= 250

列挙型（性別・活動レベル・目標）

ホワイトリスト検証
例: in_array($gender, ['male', 'female'])

日付

フォーマット: YYYY-MM-DD
検証: checkdate()
範囲: 過去〜明日

7. 非機能要件
7.1 性能要件
項目要件画面表示速度3秒以内
データベースクエリ1秒以内
同時接続ユーザー100ユーザー

7.2 可用性要件
項目要件稼働時間99.0% (開発環境)バックアップ日次（推奨）

7.3 拡張性要件

ユーザー数: 10,000ユーザーまで対応
食品数: 10,000食品まで対応
食事記録: ユーザーあたり無制限


7.4 互換性要件
ブラウザ対応

Google Chrome (最新版)
Firefox (最新版)
Safari (最新版)
Edge (最新版)

レスポンシブ対応

デスクトップ (1200px以上)
タブレット (768px-1199px)
スマートフォン (320px-767px)


8. 開発環境
8.1 Docker構成
yamlversion: '3.8'

services:
  nginx:
    image: nginx:latest
    ports:
      - "80:80"
    volumes:
      - ./src:/var/www/html
      - ./nginx/default.conf:/etc/nginx/conf.d/default.conf

  php:
    build: ./php
    volumes:
      - ./src:/var/www/html

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: root_password
      MYSQL_DATABASE: calorie_db
      MYSQL_USER: calorie_user
      MYSQL_PASSWORD: calorie_pass
    volumes:
      - ./db/init.sql:/docker-entrypoint-initdb.d/init.sql
    ports:
      - "3306:3306"
```

---

### 8.2 ディレクトリ構造
```
project/
├── docker-compose.yml
├── nginx/
│   └── default.conf
├── php/
│   └── Dockerfile
├── db/
│   └── init.sql
└── src/
    ├── db.php
    ├── login.php
    ├── login_check.php
    ├── register.php
    ├── register_check.php
    ├── logout.php
    ├── home.php
    ├── meal_add.php
    ├── meal_add_check.php
    ├── delete_meal.php
    ├── foods.php
    ├── users_list.php
    ├── user_edit.php
    └── user_update.php
```

---

### 8.3 環境変数

| 変数名 | 値 | 説明 |
|--------|-----|------|
| DB_HOST | mysql | MySQLホスト |
| DB_NAME | calorie_db | データベース名 |
| DB_USER | calorie_user | ユーザー名 |
| DB_PASS | calorie_pass | パスワード |
| DB_CHARSET | utf8mb4 | 文字コード |

---

## 9. テスト仕様

### 9.1 テスト観点

| 観点 | 内容 |
|------|------|
| 機能テスト | 全機能が仕様通り動作するか |
| セキュリティテスト | 脆弱性がないか |
| パフォーマンステスト | 性能要件を満たすか |
| ユーザビリティテスト | 使いやすいか |

---

### 9.2 テストケース例

#### ログイン機能

| No | テスト項目 | 入力値 | 期待結果 |
|----|-----------|--------|---------|
| T001 | 正常ログイン | 正しいユーザー名・パスワード | home.phpへ遷移 |
| T002 | ユーザー名誤り | 存在しないユーザー名 | エラーメッセージ表示 |
| T003 | パスワード誤り | 誤ったパスワード | エラーメッセージ表示 |
| T004 | 未入力 | 空欄 | エラーメッセージ表示 |
| T005 | SQLインジェクション | `' OR '1'='1` | エラーメッセージ表示 |

---

## 10. 運用・保守

### 10.1 ログ管理

**ログ出力先**: PHPエラーログ

**記録内容**:
- ログイン成功/失敗
- ユーザー登録
- 食事追加/削除
- エラー発生時のスタックトレース

**例**:
```
[2026-01-22 18:00:00] Successful login for user ID: 1 (akito) from IP: 192.168.1.100
[2026-01-22 18:05:23] Meal added: user_id=1, meal_id=5, food=白米, amount=150g, date=2026-01-22

10.2 バックアップ
対象:

データベース全体（calorie_db）

頻度: 日次（推奨）
コマンド:
bashdocker-compose exec mysql mysqldump -u calorie_user -pcalorie_pass calorie_db > backup_$(date +%Y%m%d).sql

10.3 アップデート手順

バックアップ取得
Dockerコンテナ停止
ソースコード更新
データベースマイグレーション実行
Dockerコンテナ再起動
動作確認


11. 付録
11.1 用語集
用語説明
PFCProtein（タンパク質）, Fat（脂質）, Carbohydrate（炭水化物）の略BMRBasal Metabolic Rate（基礎代謝量）TDEETotal Daily Energy Expenditure（総消費エネルギー）CRUDCreate（作成）, Read（読取）, Update（更新）,
 Delete（削除）

11.2 参考資料

Mifflin-St Jeor式: 基礎代謝量計算の標準的な式
Chart.js公式ドキュメント: https://www.chartjs.org/
PHP公式マニュアル: https://www.php.net/manual/ja/


12. 変更履歴
版数日付変更内容作成者1.02026-01-22初版作成Claude

以上
