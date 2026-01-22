DROP DATABASE IF EXISTS calorie_db;

CREATE DATABASE calorie_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;  -- より包括的な照合順序

USE calorie_db;

-- ========================================
-- users テーブル
-- ========================================
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(255) DEFAULT NULL,  -- メールアドレス追加
  password VARCHAR(255) NOT NULL,
  height FLOAT DEFAULT NULL,
  weight FLOAT DEFAULT NULL,
  age INT DEFAULT NULL,
  gender ENUM('male','female') DEFAULT NULL,
  activity_level ENUM('low','normal','high') DEFAULT 'normal',
  goal ENUM('bulk','maintain','cut') DEFAULT 'maintain',
  is_active TINYINT(1) DEFAULT 1,  -- アカウント有効/無効
  last_login DATETIME DEFAULT NULL,  -- 最終ログイン日時
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_username (username),
  INDEX idx_email (email),
  INDEX idx_is_active (is_active)
) ENGINE=InnoDB
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci
COMMENT='ユーザー情報テーブル';

-- ========================================
-- foods テーブル
-- ========================================
CREATE TABLE foods (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  calorie FLOAT DEFAULT 0 COMMENT '100gあたりのカロリー(kcal)',
  protein FLOAT DEFAULT 0 COMMENT '100gあたりのタンパク質(g)',
  fat FLOAT DEFAULT 0 COMMENT '100gあたりの脂質(g)',
  carb FLOAT DEFAULT 0 COMMENT '100gあたりの炭水化物(g)',
  fiber FLOAT DEFAULT NULL COMMENT '100gあたりの食物繊維(g)',
  sugar FLOAT DEFAULT NULL COMMENT '100gあたりの糖質(g)',
  sodium FLOAT DEFAULT NULL COMMENT '100gあたりのナトリウム(mg)',
  vitamin TEXT DEFAULT NULL COMMENT 'ビタミン情報（JSON形式推奨）',
  mineral TEXT DEFAULT NULL COMMENT 'ミネラル情報（JSON形式推奨）',
  category VARCHAR(50) DEFAULT NULL COMMENT '食品カテゴリ（肉類、魚類、野菜など）',
  source VARCHAR(100) DEFAULT NULL COMMENT 'データソース（手動入力、USDA、日本食品標準成分表など）',
  barcode VARCHAR(50) DEFAULT NULL COMMENT 'バーコード（将来的な機能拡張用）',
  image_url VARCHAR(255) DEFAULT NULL COMMENT '食品画像URL',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_name (name),
  INDEX idx_category (category),
  FULLTEXT INDEX ft_name (name)  -- 全文検索用インデックス
) ENGINE=InnoDB
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci
COMMENT='食品マスタテーブル';

-- ========================================
-- meals テーブル
-- ========================================
CREATE TABLE meals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  food_id INT NOT NULL,
  amount FLOAT NOT NULL COMMENT '摂取量(g)',
  meal_date DATE NOT NULL,
  meal_type ENUM('breakfast','lunch','dinner','snack') DEFAULT NULL COMMENT '食事の種類',
  note TEXT DEFAULT NULL COMMENT 'メモ',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_meals_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_meals_food
    FOREIGN KEY (food_id) REFERENCES foods(id)
    ON DELETE RESTRICT  -- 食品が使用されている場合は削除不可
    ON UPDATE CASCADE,
  INDEX idx_user_date (user_id, meal_date),
  INDEX idx_meal_date (meal_date),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci
COMMENT='食事記録テーブル';

-- ========================================
-- user_settings テーブル（オプション）
-- ========================================
CREATE TABLE user_settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL UNIQUE,
  notification_enabled TINYINT(1) DEFAULT 1,
  theme VARCHAR(20) DEFAULT 'light' COMMENT 'light/dark',
  language VARCHAR(10) DEFAULT 'ja',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_settings_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci
COMMENT='ユーザー設定テーブル';

-- ========================================
-- 初期データ挿入
-- ========================================

-- 初期食品データ（日本の一般的な食品）
INSERT INTO foods
(name, calorie, protein, fat, carb, fiber, category, source) VALUES
('白米（ごはん）', 168, 2.5, 0.3, 37.1, 0.5, '穀類', '日本食品標準成分表'),
('玄米（ごはん）', 165, 2.8, 1.0, 35.6, 1.4, '穀類', '日本食品標準成分表'),
('食パン', 264, 9.3, 4.4, 46.7, 2.3, '穀類', '日本食品標準成分表'),
('うどん（ゆで）', 105, 2.6, 0.4, 21.6, 0.8, '穀類', '日本食品標準成分表'),
('そば（ゆで）', 132, 4.8, 1.0, 26.0, 2.0, '穀類', '日本食品標準成分表'),

('鶏むね肉（皮なし）', 108, 22.3, 1.5, 0, 0, '肉類', '日本食品標準成分表'),
('鶏もも肉（皮なし）', 116, 18.8, 3.9, 0, 0, '肉類', '日本食品標準成分表'),
('豚ロース', 263, 19.3, 19.2, 0.2, 0, '肉類', '日本食品標準成分表'),
('牛もも肉', 182, 21.2, 9.6, 0.5, 0, '肉類', '日本食品標準成分表'),

('サーモン（生）', 138, 20.1, 4.5, 0.1, 0, '魚類', '日本食品標準成分表'),
('サバ（生）', 202, 20.7, 12.1, 0.3, 0, '魚類', '日本食品標準成分表'),
('マグロ（赤身）', 125, 26.4, 1.4, 0.1, 0, '魚類', '日本食品標準成分表'),

('卵（全卵）', 151, 12.3, 10.3, 0.3, 0, '卵類', '日本食品標準成分表'),
('卵白', 47, 10.5, 0.2, 0.4, 0, '卵類', '日本食品標準成分表'),
('卵黄', 387, 16.5, 33.5, 0.1, 0, '卵類', '日本食品標準成分表'),

('牛乳', 67, 3.3, 3.8, 4.8, 0, '乳製品', '日本食品標準成分表'),
('ヨーグルト（無糖）', 62, 3.6, 3.0, 4.9, 0, '乳製品', '日本食品標準成分表'),
('チーズ（プロセス）', 339, 22.7, 26.0, 1.3, 0, '乳製品', '日本食品標準成分表'),

('豆腐（木綿）', 72, 6.6, 4.2, 1.6, 0.4, '豆類', '日本食品標準成分表'),
('納豆', 200, 16.5, 10.0, 12.1, 6.7, '豆類', '日本食品標準成分表'),

('ブロッコリー', 33, 4.3, 0.5, 5.2, 4.4, '野菜類', '日本食品標準成分表'),
('ほうれん草', 20, 2.2, 0.4, 3.1, 2.8, '野菜類', '日本食品標準成分表'),
('トマト', 19, 0.7, 0.1, 4.7, 1.0, '野菜類', '日本食品標準成分表'),
('キャベツ', 23, 1.3, 0.2, 5.2, 1.8, '野菜類', '日本食品標準成分表'),

('バナナ', 86, 1.1, 0.2, 22.5, 1.1, '果物類', '日本食品標準成分表'),
('リンゴ', 54, 0.2, 0.1, 14.6, 1.5, '果物類', '日本食品標準成分表'),
('オレンジ', 46, 0.9, 0.1, 11.8, 1.0, '果物類', '日本食品標準成分表'),

('アーモンド', 598, 18.6, 54.2, 19.7, 11.0, 'ナッツ類', '日本食品標準成分表'),
('くるみ', 674, 14.6, 68.8, 11.7, 7.5, 'ナッツ類', '日本食品標準成分表'),

('オリーブオイル', 921, 0, 100.0, 0, 0, '油脂類', '日本食品標準成分表'),
('バター', 745, 0.6, 81.0, 0.2, 0, '油脂類', '日本食品標準成分表');

-- テストユーザー（開発用）
-- パスワード: test123
INSERT INTO users 
(username, email, password, height, weight, age, gender, activity_level, goal, is_active) 
VALUES 
('testuser', 'test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 170, 65, 25, 'male', 'normal', 'maintain', 1);

-- ========================================
-- ビュー作成（オプション）
-- ========================================

-- 日別の栄養摂取量を集計するビュー
CREATE OR REPLACE VIEW daily_nutrition AS
SELECT 
    m.user_id,
    m.meal_date,
    COUNT(m.id) as meal_count,
    SUM(f.calorie * m.amount / 100) as total_calorie,
    SUM(f.protein * m.amount / 100) as total_protein,
    SUM(f.fat * m.amount / 100) as total_fat,
    SUM(f.carb * m.amount / 100) as total_carb
FROM meals m
JOIN foods f ON m.food_id = f.id
GROUP BY m.user_id, m.meal_date;

-- ========================================
-- ストアドプロシージャ（オプション）
-- ========================================

DELIMITER //

-- ユーザーの目標カロリーを計算するストアドプロシージャ
CREATE PROCEDURE calculate_target_calories(
    IN p_user_id INT,
    OUT p_target_calories INT
)
BEGIN
    DECLARE v_height FLOAT;
    DECLARE v_weight FLOAT;
    DECLARE v_age INT;
    DECLARE v_gender ENUM('male','female');
    DECLARE v_activity_level ENUM('low','normal','high');
    DECLARE v_goal ENUM('bulk','maintain','cut');
    DECLARE v_bmr FLOAT;
    DECLARE v_tdee FLOAT;
    DECLARE v_activity_multiplier FLOAT;
    
    -- ユーザー情報取得
    SELECT height, weight, age, gender, activity_level, goal
    INTO v_height, v_weight, v_age, v_gender, v_activity_level, v_goal
    FROM users
    WHERE id = p_user_id;
    
    -- BMR計算（Mifflin-St Jeor式）
    IF v_gender = 'male' THEN
        SET v_bmr = 10 * v_weight + 6.25 * v_height - 5 * v_age + 5;
    ELSE
        SET v_bmr = 10 * v_weight + 6.25 * v_height - 5 * v_age - 161;
    END IF;
    
    -- 活動レベル係数
    SET v_activity_multiplier = CASE v_activity_level
        WHEN 'low' THEN 1.2
        WHEN 'normal' THEN 1.55
        WHEN 'high' THEN 1.75
        ELSE 1.2
    END CASE;
    
    -- TDEE計算
    SET v_tdee = v_bmr * v_activity_multiplier;
    
    -- 目標に応じた調整
    SET v_tdee = CASE v_goal
        WHEN 'bulk' THEN v_tdee + 300
        WHEN 'cut' THEN v_tdee - 300
        ELSE v_tdee
    END CASE;
    
    SET p_target_calories = ROUND(v_tdee);
END //

DELIMITER ;

-- ========================================
-- データベース情報表示
-- ========================================

SELECT 'データベース初期化完了' AS status;
SELECT COUNT(*) as food_count FROM foods;
SELECT COUNT(*) as user_count FROM users;