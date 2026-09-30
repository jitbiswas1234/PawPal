<?php
/**
 * PawPal - AJAX Claim Daily Reward
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit;
}

$userId = $_SESSION['user_id'];
$token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

if (!verifyCSRF($token)) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
    exit;
}

if (!canClaimDailyReward($pdo, $userId)) {
    echo json_encode(['success' => false, 'message' => 'You have already claimed your daily reward today!']);
    exit;
}

$reward = claimDailyReward($pdo, $userId);

if (!$reward) {
    echo json_encode(['success' => false, 'message' => 'Failed to claim daily reward.']);
    exit;
}

// Create notification
createNotification($pdo, $userId, null, "🎁 Claimed Daily Reward! Earned " . $reward['coins'] . " coins. (Day " . $reward['streak'] . " streak!)", 'success');

// Get updated user coins
$currentUser = getCurrentUser($pdo);
$newCoins = $currentUser['coins'];

echo json_encode([
    'success' => true,
    'message' => 'Claimed daily reward successfully!',
    'coins_earned' => $reward['coins'],
    'streak_day' => $reward['streak'],
    'user_coins' => $newCoins
]);
