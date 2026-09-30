<?php
/**
 * PawPal - AJAX Medicine Pet
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit;
}

$userId = $_SESSION['user_id'];
$petId = isset($_POST['pet_id']) ? (int)$_POST['pet_id'] : 0;
$itemId = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
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

$coinsEarned = 0;
$xpEarned = 0;
$description = '';
$changes = [];

if ($itemId > 0) {
    // Treat using inventory item (e.g. Healing Potion or Vitamins)
    $stmt = $pdo->prepare("
        SELECT upi.*, pi.item_name, pi.item_type, pi.effect_stat, pi.effect_value 
        FROM user_pet_items upi
        INNER JOIN pet_items pi ON upi.item_id = pi.id
        WHERE upi.user_id = ? AND upi.item_id = ? AND upi.quantity > 0
    ");
    $stmt->execute([$userId, $itemId]);
    $userItem = $stmt->fetch();
    
    if (!$userItem) {
        echo json_encode(['success' => false, 'message' => 'You do not own this medicine or it is out of stock.']);
        exit;
    }
    
    if ($userItem['item_type'] !== 'medicine') {
        echo json_encode(['success' => false, 'message' => 'This item is not a medicine.']);
        exit;
    }
    
    // Decrement item quantity
    $stmt = $pdo->prepare("UPDATE user_pet_items SET quantity = quantity - 1 WHERE id = ?");
    $stmt->execute([$userItem['id']]);
    
    // Clean up if quantity is 0
    $stmt = $pdo->prepare("DELETE FROM user_pet_items WHERE id = ? AND quantity <= 0");
    $stmt->execute([$userItem['id']]);
    
    // Calculate stat changes
    $statName = $userItem['effect_stat'];
    $changes[$statName] = (int)$userItem['effect_value'];
    
    // Additional boosts
    $changes['happiness'] = 5;
    
    $xpEarned = 10;
    $description = "Treated " . sanitize($pet['pet_name']) . " with " . sanitize($userItem['item_name']);
    
} else {
    // Clinic visit (costs 20 coins)
    if ($pet['health'] >= 100) {
        echo json_encode(['success' => false, 'message' => sanitize($pet['pet_name']) . " is already in perfect health!"]);
        exit;
    }
    
    $clinicCost = 20;
    $user = getCurrentUser($pdo);
    
    if ($user['coins'] < $clinicCost) {
        echo json_encode(['success' => false, 'message' => 'Not enough coins! A vet clinic visit costs ' . $clinicCost . ' coins.']);
        exit;
    }
    
    // Deduct coins
    deductCoins($pdo, $userId, $clinicCost);
    
    $changes['health'] = 30;
    $changes['happiness'] = -10; // Pets hate the vet!
    
    $xpEarned = 10;
    $description = "Took " . sanitize($pet['pet_name']) . " to the Vet Clinic (cost " . $clinicCost . " coins)";
}

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

// Award user points
awardPoints($pdo, $userId, $xpEarned);

// Log activity
logCareAction($pdo, $userId, $petId, 'medicine', $description, 0, $xpEarned);

// Get updated user coins
$currentUser = getCurrentUser($pdo);
$newCoins = $currentUser['coins'];

// Get item quantity remaining
$itemQuantityRemaining = 0;
if ($itemId > 0) {
    $stmt = $pdo->prepare("SELECT quantity FROM user_pet_items WHERE user_id = ? AND item_id = ?");
    $stmt->execute([$userId, $itemId]);
    $itemQuantityRemaining = (int)($stmt->fetch()['quantity'] ?? 0);
}

echo json_encode([
    'success' => true,
    'message' => $description . "!",
    'stats' => $updatedStats,
    'xp' => $xpResult['xp'],
    'level' => $newLevel,
    'xp_needed' => getXPForNextLevel($newLevel),
    'leveled_up' => $leveledUp,
    'coins_earned' => 0,
    'xp_earned' => $xpEarned,
    'user_coins' => $newCoins,
    'item_id' => $itemId,
    'item_qty' => $itemQuantityRemaining
]);
