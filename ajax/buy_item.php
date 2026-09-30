<?php
/**
 * PawPal - AJAX Buy Item
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit;
}

$userId = $_SESSION['user_id'];
$itemId = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
$token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

if (!verifyCSRF($token)) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
    exit;
}

if (!$itemId) {
    echo json_encode(['success' => false, 'message' => 'Item ID is required.']);
    exit;
}

if ($quantity <= 0) {
    $quantity = 1;
}

// Fetch item details
$stmt = $pdo->prepare("SELECT * FROM pet_items WHERE id = ?");
$stmt->execute([$itemId]);
$item = $stmt->fetch();

if (!$item) {
    echo json_encode(['success' => false, 'message' => 'Item not found.']);
    exit;
}

// Calculate total cost
$totalCost = $item['price'] * $quantity;

// Check user coins
$user = getCurrentUser($pdo);
if ($user['coins'] < $totalCost) {
    echo json_encode(['success' => false, 'message' => 'Not enough coins. You need ' . $totalCost . ' coins.']);
    exit;
}

// Deduct coins
if (!deductCoins($pdo, $userId, $totalCost)) {
    echo json_encode(['success' => false, 'message' => 'Transaction failed. Not enough coins.']);
    exit;
}

// Add to inventory
// Check if user already owns this item
$stmt = $pdo->prepare("SELECT id, quantity FROM user_pet_items WHERE user_id = ? AND item_id = ?");
$stmt->execute([$userId, $itemId]);
$existingInventory = $stmt->fetch();

if ($existingInventory) {
    // Update quantity
    $stmt = $pdo->prepare("UPDATE user_pet_items SET quantity = quantity + ? WHERE id = ?");
    $stmt->execute([$quantity, $existingInventory['id']]);
} else {
    // Insert new row
    $stmt = $pdo->prepare("INSERT INTO user_pet_items (user_id, item_id, quantity) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $itemId, $quantity]);
}

// Get updated coin balance
$updatedUser = getCurrentUser($pdo);
$newCoins = $updatedUser['coins'];

// Create notification
createNotification($pdo, $userId, null, "🛒 Purchased " . $quantity . "x " . sanitize($item['item_name']) . " for " . $totalCost . " coins.", 'info');

echo json_encode([
    'success' => true,
    'message' => 'Purchased ' . $quantity . 'x ' . sanitize($item['item_name']) . ' successfully!',
    'user_coins' => $newCoins,
    'item_name' => $item['item_name'],
    'total_cost' => $totalCost
]);
