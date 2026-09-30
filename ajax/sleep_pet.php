<?php
/**
 * PawPal - AJAX Sleep Pet
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit;
}

$userId = $_SESSION['user_id'];
$petId = isset($_POST['pet_id']) ? (int)$_POST['pet_id'] : 0;
$token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

if (!verifyCSRF($token)) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
    exit;
}

if (!$petId || !isOwnPet($pdo, $userId, $petId)) {
    echo json_encode(['success' => false, 'message' => 'Pet not found or not owned by you.']);
    exit;
}

applyDecay($pdo, $petId);
$pet = getPetById($pdo, $petId);

// Check if pet is already fully rested
if ($pet['energy'] >= 100) {
    echo json_encode(['success' => false, 'message' => sanitize($pet['pet_name']) . " is wide awake and doesn't want to sleep!"]);
    exit;
}

$changes = [
    'energy' => 40,
    'hunger' => 15,
    'cleanliness' => -10,
    'happiness' => 5
];

$xpEarned = 6;
$coinsEarned = 2;
$description = sanitize($pet['pet_name']) . " took a nice, cozy nap";

// Update pet stats
$updatedStats = updatePetStats($pdo, $petId, $changes);
if (!$updatedStats) {
    echo json_encode(['success' => false, 'message' => 'Failed to update pet stats.']);
    exit;
}

// Level & XP update
$oldLevel = $pet['level'];
$xpResult = addXP($pdo, $petId, $xpEarned);
$newLevel = $xpResult['level'];
$leveledUp = ($newLevel > $oldLevel);

if ($leveledUp) {
    awardCoins($pdo, $userId, 50); // Level up bonus!
    createNotification($pdo, $userId, $petId, "🎉 " . sanitize($pet['pet_name']) . " leveled up to Level " . $newLevel . "!", 'success');
}

// Award user coins and points
awardCoins($pdo, $userId, $coinsEarned);
awardPoints($pdo, $userId, $xpEarned);

// Log activity
logCareAction($pdo, $userId, $petId, 'sleep', $description, $coinsEarned, $xpEarned);

// Get updated user coins
$currentUser = getCurrentUser($pdo);
$newCoins = $currentUser['coins'];

echo json_encode([
    'success' => true,
    'message' => $description . "!",
    'stats' => $updatedStats,
    'xp' => $xpResult['xp'],
    'level' => $newLevel,
    'xp_needed' => getXPForNextLevel($newLevel),
    'leveled_up' => $leveledUp,
    'coins_earned' => $coinsEarned,
    'xp_earned' => $xpEarned,
    'user_coins' => $newCoins
]);
