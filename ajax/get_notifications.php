<?php
/**
 * PawPal - AJAX Get Notifications
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit;
}

$userId = $_SESSION['user_id'];
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
if ($limit <= 0 || $limit > 50) {
    $limit = 5;
}

$unreadCount = getUnreadNotificationCount($pdo, $userId);
$notifications = getNotifications($pdo, $userId, $limit);

// Format times for display in javascript
foreach ($notifications as &$n) {
    $n['time_ago'] = timeAgo($n['created_at']);
}

echo json_encode([
    'success' => true,
    'unread_count' => $unreadCount,
    'notifications' => $notifications
]);
