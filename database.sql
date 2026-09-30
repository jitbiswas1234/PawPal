-- ============================================
-- PawPal - Virtual Pet Adoption & Care Simulator
-- Database Setup Script
-- ============================================

CREATE DATABASE IF NOT EXISTS pawpal_db;
USE pawpal_db;

-- Drop tables if exist (for clean install)
DROP TABLE IF EXISTS pet_notifications;
DROP TABLE IF EXISTS user_pet_items;
DROP TABLE IF EXISTS pet_care_logs;
DROP TABLE IF EXISTS daily_rewards;
DROP TABLE IF EXISTS adoptions;
DROP TABLE IF EXISTS pet_items;
DROP TABLE IF EXISTS pets;
DROP TABLE IF EXISTS users;

-- ============================================
-- 1. Users Table
-- ============================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(120) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user','admin') DEFAULT 'user',
    coins INT DEFAULT 100,
    points INT DEFAULT 0,
    avatar VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 2. Pets Table
-- ============================================
CREATE TABLE pets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pet_name VARCHAR(100) NOT NULL,
    pet_type VARCHAR(50) NOT NULL,
    breed VARCHAR(100),
    age INT DEFAULT 1,
    gender ENUM('Male','Female') DEFAULT 'Male',
    image VARCHAR(255) DEFAULT NULL,
    description TEXT,
    health INT DEFAULT 100,
    hunger INT DEFAULT 30,
    happiness INT DEFAULT 70,
    cleanliness INT DEFAULT 80,
    energy INT DEFAULT 80,
    xp INT DEFAULT 0,
    level INT DEFAULT 1,
    mood VARCHAR(50) DEFAULT 'Happy',
    status ENUM('available','adopted') DEFAULT 'available',
    last_interaction TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 3. Adoptions Table
-- ============================================
CREATE TABLE adoptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    pet_id INT NOT NULL,
    message TEXT,
    adoption_status ENUM('pending','approved','rejected') DEFAULT 'pending',
    adopted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 4. Pet Care Logs Table
-- ============================================
CREATE TABLE pet_care_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    pet_id INT NOT NULL,
    action_type ENUM('feed','groom','play','sleep','water','medicine','training') NOT NULL,
    action_value VARCHAR(255),
    coins_earned INT DEFAULT 0,
    xp_earned INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 5. Pet Items Table (Shop)
-- ============================================
CREATE TABLE pet_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(100) NOT NULL,
    item_type ENUM('food','toy','medicine','accessory') NOT NULL,
    description TEXT,
    price INT NOT NULL DEFAULT 10,
    effect_stat VARCHAR(50) DEFAULT 'happiness',
    effect_value INT DEFAULT 10,
    image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 6. User Pet Items (Inventory)
-- ============================================
CREATE TABLE user_pet_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity INT DEFAULT 1,
    purchased_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES pet_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 7. Pet Notifications Table
-- ============================================
CREATE TABLE pet_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    pet_id INT,
    message TEXT NOT NULL,
    type ENUM('warning','info','success','danger') DEFAULT 'info',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 8. Daily Rewards Table
-- ============================================
CREATE TABLE daily_rewards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reward_date DATE NOT NULL,
    coins_earned INT DEFAULT 10,
    streak_day INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_daily (user_id, reward_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- SEED DATA
-- ============================================

-- Admin and test user (passwords are hashed versions of 'admin123' and 'user123')
INSERT INTO users (name, email, password, role, coins, points) VALUES
('Admin', 'admin@pawpal.com', '$2y$10$xyGgfyBoYzh81C6v4rtf8ulbH/dZwsb2frkBvqx9KfYBi/RKEAQLC', 'admin', 9999, 0),
('John Doe', 'user@pawpal.com', '$2y$10$Gpsq8FRa1.4x9aGVpt53EeA28CNR6eH3CFDkBVioGju3FNfZXS4Ze', 'user', 200, 50);

-- Pets seed data (12 pets across 6 types)
INSERT INTO pets (pet_name, pet_type, breed, age, gender, image, description, health, hunger, happiness, cleanliness, energy, mood) VALUES
('Buddy', 'Dog', 'Golden Retriever', 2, 'Male', 'dog1.jpg', 'Buddy is a friendly and loyal Golden Retriever who loves to play fetch and cuddle. He''s the perfect family companion!', 95, 25, 85, 90, 80, 'Happy'),
('Luna', 'Dog', 'Siberian Husky', 3, 'Female', 'dog2.jpg', 'Luna is a beautiful Husky with striking blue eyes. She loves running in the snow and howling at the moon.', 90, 30, 75, 85, 90, 'Happy'),
('Whiskers', 'Cat', 'Persian', 1, 'Male', 'cat1.jpg', 'Whiskers is a fluffy Persian cat who enjoys lounging in sunny spots and being pampered with gentle brushing.', 100, 20, 90, 95, 60, 'Happy'),
('Mittens', 'Cat', 'British Shorthair', 2, 'Female', 'cat2.jpg', 'Mittens is an elegant British Shorthair who loves to chase laser pointers and sleep on soft pillows.', 85, 35, 70, 80, 70, 'Happy'),
('Cotton', 'Rabbit', 'Holland Lop', 1, 'Female', 'rabbit1.jpg', 'Cotton is an adorable Holland Lop with floppy ears. She loves munching on carrots and hopping around the garden.', 100, 20, 80, 90, 85, 'Happy'),
('Thumper', 'Rabbit', 'Mini Rex', 1, 'Male', 'rabbit2.jpg', 'Thumper is an energetic Mini Rex who loves to explore and play with tunnels. His soft fur is irresistible!', 95, 25, 85, 85, 90, 'Happy'),
('Sky', 'Bird', 'Parakeet', 1, 'Male', 'bird1.jpg', 'Sky is a cheerful Parakeet with vibrant green and yellow feathers. He loves singing and learning new words!', 100, 15, 90, 95, 80, 'Happy'),
('Kiwi', 'Bird', 'Cockatiel', 2, 'Female', 'bird2.jpg', 'Kiwi is a sweet Cockatiel who loves head scratches and whistling tunes. She''ll brighten up your day!', 90, 20, 85, 90, 75, 'Happy'),
('Nemo', 'Fish', 'Clownfish', 1, 'Male', 'fish1.jpg', 'Nemo is a vibrant Clownfish with beautiful orange and white stripes. He loves exploring his aquarium world.', 100, 10, 80, 95, 90, 'Happy'),
('Bubbles', 'Fish', 'Betta', 1, 'Female', 'fish2.jpg', 'Bubbles is a stunning Betta fish with flowing red and blue fins. She gracefully swims and loves attention.', 95, 15, 85, 90, 85, 'Happy'),
('Peanut', 'Hamster', 'Syrian', 1, 'Male', 'hamster1.jpg', 'Peanut is a curious Syrian Hamster who loves running on his wheel and stuffing his cheeks with sunflower seeds.', 100, 20, 90, 85, 95, 'Happy'),
('Hazel', 'Hamster', 'Dwarf', 1, 'Female', 'hamster2.jpg', 'Hazel is a tiny Dwarf Hamster with a big personality. She enjoys burrowing and being held gently.', 95, 25, 80, 80, 85, 'Happy');

-- Shop items seed data
INSERT INTO pet_items (item_name, item_type, description, price, effect_stat, effect_value, image) VALUES
('Premium Kibble', 'food', 'High-quality pet food that satisfies hunger and boosts health.', 15, 'hunger', -25, 'kibble.png'),
('Organic Treats', 'food', 'Delicious organic treats that make your pet happy and reduce hunger.', 10, 'hunger', -15, 'treats.png'),
('Fresh Water Bowl', 'food', 'Clean, fresh water to keep your pet hydrated.', 5, 'hunger', -10, 'water.png'),
('Gourmet Feast', 'food', 'A luxurious meal that greatly reduces hunger and improves health.', 30, 'hunger', -40, 'feast.png'),
('Squeaky Ball', 'toy', 'A fun squeaky ball that boosts happiness and encourages play.', 20, 'happiness', 20, 'ball.png'),
('Feather Wand', 'toy', 'An interactive feather toy for cats and birds. Increases happiness!', 15, 'happiness', 15, 'feather.png'),
('Puzzle Toy', 'toy', 'A stimulating puzzle toy that increases happiness and trains your pet.', 25, 'happiness', 25, 'puzzle.png'),
('Cozy Bed', 'toy', 'A super comfortable bed that helps your pet rest and recover energy.', 35, 'energy', 30, 'bed.png'),
('Vitamin Supplement', 'medicine', 'Daily vitamins that boost overall health significantly.', 20, 'health', 20, 'vitamins.png'),
('Healing Potion', 'medicine', 'A powerful health potion that restores health quickly.', 40, 'health', 35, 'potion.png'),
('Flea Treatment', 'medicine', 'Keeps your pet clean and parasite-free. Boosts cleanliness.', 15, 'cleanliness', 30, 'flea.png'),
('Energy Booster', 'medicine', 'A special supplement that restores your pet''s energy.', 25, 'energy', 25, 'energy_boost.png'),
('Stylish Collar', 'accessory', 'A fashionable collar that makes your pet look adorable!', 30, 'happiness', 10, 'collar.png'),
('Pet Bandana', 'accessory', 'A cute bandana accessory for your pet.', 20, 'happiness', 8, 'bandana.png'),
('Crown Hat', 'accessory', 'A royal crown that makes your pet feel special!', 50, 'happiness', 15, 'crown.png');
