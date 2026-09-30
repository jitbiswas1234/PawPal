<?php
/**
 * PawPal - Core Helper Functions
 */

require_once __DIR__ . '/../config/db.php';

// ============================================
// Authentication Helpers
// ============================================

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect(BASE_URL . '/login.php');
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        redirect(BASE_URL . '/login.php');
    }
}

function getCurrentUser($pdo) {
    if (!isLoggedIn()) return null;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// ============================================
// Redirect & Sanitize
// ============================================

function redirect($url) {
    header("Location: $url");
    exit;
}

function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ============================================
// Pet Mood Calculation
// ============================================

function getPetMood($health, $hunger, $happiness, $energy, $cleanliness) {
    if ($health < 30) return ['mood' => 'Sick', 'emoji' => '🤒', 'color' => '#ef4444'];
    if ($hunger > 70) return ['mood' => 'Hungry', 'emoji' => '😫', 'color' => '#f97316'];
    if ($energy < 20) return ['mood' => 'Sleepy', 'emoji' => '😴', 'color' => '#8b5cf6'];
    if ($happiness < 30) return ['mood' => 'Sad', 'emoji' => '😢', 'color' => '#3b82f6'];
    if ($cleanliness < 30) return ['mood' => 'Dirty', 'emoji' => '🤢', 'color' => '#84cc16'];
    if ($happiness > 80 && $health > 80) return ['mood' => 'Ecstatic', 'emoji' => '🥰', 'color' => '#ec4899'];
    return ['mood' => 'Happy', 'emoji' => '😊', 'color' => '#22c55e'];
}

// ============================================
// Pet Stat Update with Clamping
// ============================================

function clampStat($value) {
    return max(0, min(100, $value));
}

function updatePetStats($pdo, $petId, $changes) {
    $pet = getPetById($pdo, $petId);
    if (!$pet) return false;

    $newHealth = clampStat($pet['health'] + ($changes['health'] ?? 0));
    $newHunger = clampStat($pet['hunger'] + ($changes['hunger'] ?? 0));
    $newHappiness = clampStat($pet['happiness'] + ($changes['happiness'] ?? 0));
    $newCleanliness = clampStat($pet['cleanliness'] + ($changes['cleanliness'] ?? 0));
    $newEnergy = clampStat($pet['energy'] + ($changes['energy'] ?? 0));

    $moodInfo = getPetMood($newHealth, $newHunger, $newHappiness, $newEnergy, $newCleanliness);

    $stmt = $pdo->prepare("UPDATE pets SET 
        health = ?, hunger = ?, happiness = ?, cleanliness = ?, energy = ?, 
        mood = ?, last_interaction = NOW(), updated_at = NOW() 
        WHERE id = ?");
    $stmt->execute([$newHealth, $newHunger, $newHappiness, $newCleanliness, $newEnergy, $moodInfo['mood'], $petId]);

    return [
        'health' => $newHealth,
        'hunger' => $newHunger,
        'happiness' => $newHappiness,
        'cleanliness' => $newCleanliness,
        'energy' => $newEnergy,
        'mood' => $moodInfo['mood'],
        'emoji' => $moodInfo['emoji'],
        'mood_color' => $moodInfo['color']
    ];
}

// ============================================
// Pet Decay System
// ============================================

function applyDecay($pdo, $petId) {
    $pet = getPetById($pdo, $petId);
    if (!$pet || $pet['status'] !== 'adopted') return;

    $lastInteraction = strtotime($pet['last_interaction']);
    $now = time();
    $hoursPassed = ($now - $lastInteraction) / 3600;

    if ($hoursPassed < 1) return; // No decay within first hour

    $changes = [
        'hunger' => (int)($hoursPassed * 3),      // +3 hunger per hour
        'energy' => (int)(-$hoursPassed * 2),      // -2 energy per hour
        'happiness' => (int)(-$hoursPassed * 1),   // -1 happiness per hour
        'cleanliness' => (int)(-$hoursPassed * 1), // -1 cleanliness per hour
        'health' => 0
    ];

    // Health decay if hunger is critical
    if ($pet['hunger'] > 80) {
        $changes['health'] = (int)(-$hoursPassed * 2);
    }

    updatePetStats($pdo, $petId, $changes);

    // Generate notifications for critical stats
    $updatedPet = getPetById($pdo, $petId);
    $adoption = getAdoptionByPet($pdo, $petId);
    if ($adoption) {
        checkAndNotify($pdo, $adoption['user_id'], $updatedPet);
    }
}

function checkAndNotify($pdo, $userId, $pet) {
    if ($pet['hunger'] > 80) {
        createNotification($pdo, $userId, $pet['id'], "🍖 {$pet['pet_name']} is very hungry! Feed them now!", 'danger');
    }
    if ($pet['health'] < 30) {
        createNotification($pdo, $userId, $pet['id'], "💊 {$pet['pet_name']} is feeling sick! Give medicine!", 'danger');
    }
    if ($pet['happiness'] < 30) {
        createNotification($pdo, $userId, $pet['id'], "😢 {$pet['pet_name']} is sad. Play with them!", 'warning');
    }
    if ($pet['energy'] < 20) {
        createNotification($pdo, $userId, $pet['id'], "😴 {$pet['pet_name']} is very tired. Let them sleep!", 'warning');
    }
}

// ============================================
// XP & Level System
// ============================================

function addXP($pdo, $petId, $xp) {
    $pet = getPetById($pdo, $petId);
    $newXP = $pet['xp'] + $xp;
    $newLevel = calculateLevel($newXP);

    $stmt = $pdo->prepare("UPDATE pets SET xp = ?, level = ? WHERE id = ?");
    $stmt->execute([$newXP, $newLevel, $petId]);

    return ['xp' => $newXP, 'level' => $newLevel];
}

function calculateLevel($xp) {
    if ($xp >= 2000) return 10;
    if ($xp >= 1500) return 9;
    if ($xp >= 1100) return 8;
    if ($xp >= 800) return 7;
    if ($xp >= 600) return 6;
    if ($xp >= 400) return 5;
    if ($xp >= 250) return 4;
    if ($xp >= 150) return 3;
    if ($xp >= 50) return 2;
    return 1;
}

function getXPForNextLevel($level) {
    $thresholds = [1=>50, 2=>150, 3=>250, 4=>400, 5=>600, 6=>800, 7=>1100, 8=>1500, 9=>2000, 10=>999999];
    return $thresholds[$level] ?? 999999;
}

// ============================================
// Coin / Points System
// ============================================

function awardCoins($pdo, $userId, $amount) {
    $stmt = $pdo->prepare("UPDATE users SET coins = coins + ? WHERE id = ?");
    $stmt->execute([$amount, $userId]);
}

function awardPoints($pdo, $userId, $amount) {
    $stmt = $pdo->prepare("UPDATE users SET points = points + ? WHERE id = ?");
    $stmt->execute([$amount, $userId]);
}

function deductCoins($pdo, $userId, $amount) {
    $user = getCurrentUser($pdo);
    if ($user['coins'] < $amount) return false;
    $stmt = $pdo->prepare("UPDATE users SET coins = coins - ? WHERE id = ?");
    $stmt->execute([$amount, $userId]);
    return true;
}

// ============================================
// Log Care Action
// ============================================

function logCareAction($pdo, $userId, $petId, $actionType, $description, $coinsEarned = 0, $xpEarned = 0) {
    $stmt = $pdo->prepare("INSERT INTO pet_care_logs (user_id, pet_id, action_type, action_value, coins_earned, xp_earned) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $petId, $actionType, $description, $coinsEarned, $xpEarned]);
}

// ============================================
// Notification System
// ============================================

function createNotification($pdo, $userId, $petId, $message, $type = 'info') {
    // Don't duplicate recent notifications
    $stmt = $pdo->prepare("SELECT id FROM pet_notifications WHERE user_id = ? AND pet_id = ? AND message = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $stmt->execute([$userId, $petId, $message]);
    if ($stmt->fetch()) return;

    $stmt = $pdo->prepare("INSERT INTO pet_notifications (user_id, pet_id, message, type) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $petId, $message, $type]);
}

function getUnreadNotificationCount($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM pet_notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return $stmt->fetch()['count'];
}

function getNotifications($pdo, $userId, $limit = 20) {
    $stmt = $pdo->prepare("SELECT n.*, p.pet_name FROM pet_notifications n LEFT JOIN pets p ON n.pet_id = p.id WHERE n.user_id = ? ORDER BY n.created_at DESC LIMIT ?");
    $stmt->execute([$userId, $limit]);
    return $stmt->fetchAll();
}

// ============================================
// Data Retrieval Helpers
// ============================================

function getPetById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM pets WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getAdoptionByPet($pdo, $petId) {
    $stmt = $pdo->prepare("SELECT * FROM adoptions WHERE pet_id = ? AND adoption_status = 'approved'");
    $stmt->execute([$petId]);
    return $stmt->fetch();
}

function getUserPets($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT p.* FROM pets p INNER JOIN adoptions a ON p.id = a.pet_id WHERE a.user_id = ? AND a.adoption_status = 'approved'");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function getAvailablePets($pdo, $type = null) {
    if ($type) {
        $stmt = $pdo->prepare("SELECT * FROM pets WHERE status = 'available' AND pet_type = ? ORDER BY created_at DESC");
        $stmt->execute([$type]);
    } else {
        $stmt = $pdo->query("SELECT * FROM pets WHERE status = 'available' ORDER BY created_at DESC");
    }
    return $stmt->fetchAll();
}

function getPetTypes($pdo) {
    $stmt = $pdo->query("SELECT DISTINCT pet_type FROM pets ORDER BY pet_type");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getUserPetCount($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM adoptions WHERE user_id = ? AND adoption_status = 'approved'");
    $stmt->execute([$userId]);
    return $stmt->fetch()['count'];
}

function isOwnPet($pdo, $userId, $petId) {
    $stmt = $pdo->prepare("SELECT id FROM adoptions WHERE user_id = ? AND pet_id = ? AND adoption_status = 'approved'");
    $stmt->execute([$userId, $petId]);
    return $stmt->fetch() ? true : false;
}

// ============================================
// Daily Reward System
// ============================================

function canClaimDailyReward($pdo, $userId) {
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT id FROM daily_rewards WHERE user_id = ? AND reward_date = ?");
    $stmt->execute([$userId, $today]);
    return !$stmt->fetch();
}

function getStreakDay($pdo, $userId) {
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $stmt = $pdo->prepare("SELECT streak_day FROM daily_rewards WHERE user_id = ? AND reward_date = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$userId, $yesterday]);
    $result = $stmt->fetch();
    return $result ? $result['streak_day'] + 1 : 1;
}

function claimDailyReward($pdo, $userId) {
    if (!canClaimDailyReward($pdo, $userId)) return false;

    $streakDay = getStreakDay($pdo, $userId);
    if ($streakDay > 7) $streakDay = 1; // Reset after 7 days

    $rewards = [1=>10, 2=>20, 3=>30, 4=>40, 5=>50, 6=>75, 7=>100];
    $coinsEarned = $rewards[$streakDay] ?? 10;

    $today = date('Y-m-d');
    $stmt = $pdo->prepare("INSERT INTO daily_rewards (user_id, reward_date, coins_earned, streak_day) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $today, $coinsEarned, $streakDay]);

    awardCoins($pdo, $userId, $coinsEarned);

    return ['coins' => $coinsEarned, 'streak' => $streakDay];
}

// ============================================
// Time Formatting
// ============================================

function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}

// ============================================
// Pet Image Helper
// ============================================

function getPetImage($image) {
    if ($image) {
        $pngImage = str_replace('.jpg', '.png', $image);
        if (file_exists(__DIR__ . '/../assets/images/pets/' . $pngImage)) {
            return BASE_URL . '/assets/images/pets/' . $pngImage;
        }
        if (file_exists(__DIR__ . '/../assets/images/pets/' . $image)) {
            return BASE_URL . '/assets/images/pets/' . $image;
        }
    }
    return BASE_URL . '/assets/images/pets/default.svg';
}

function getItemImage($image) {
    $svgImage = str_replace('.png', '.svg', $image);
    if ($svgImage && file_exists(__DIR__ . '/../assets/images/items/' . $svgImage)) {
        return BASE_URL . '/assets/images/items/' . $svgImage;
    }
    if ($image && file_exists(__DIR__ . '/../assets/images/items/' . $image)) {
        return BASE_URL . '/assets/images/items/' . $image;
    }
    return BASE_URL . '/assets/images/items/default.svg';
}

// ============================================
// CSRF Token
// ============================================

function generateCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ============================================
// Stat bar color helper
// ============================================

function getStatColor($value, $inverse = false) {
    if ($inverse) $value = 100 - $value; // For hunger: higher = worse
    if ($value >= 70) return '#22c55e';
    if ($value >= 40) return '#f59e0b';
    return '#ef4444';
}
