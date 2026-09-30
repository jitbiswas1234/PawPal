<?php
/**
 * PawPal - AJAX Mark Notification Read
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit;
}

$userId = $_SESSION['user_id'];
$id = isset($_POST['id']) ? sanitize($_POST['id']) : '';
$token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

if (!verifyCSRF($token)) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
    exit;
}

if ($id === 'all' || empty($id)) {
    // Mark all notifications for the user as read
    $stmt = $pdo->prepare("UPDATE pet_notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$userId]);
    $message = "All notifications marked as read.";
} else {
    // Mark single notification as read
    $notifId = (int)$id;
    $stmt = $pdo->prepare("UPDATE pet_notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$notifId, $userId]);
    $message = "Notification marked as read.";
}

// Get updated unread count
$unreadCount = getUnreadNotificationCount($pdo, $userId);

echo json_encode([
    'success' => true,
    'message' => $message,
    'unread_count' => $unreadCount
]);
