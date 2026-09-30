<?php
/**
 * PawPal - User Dashboard
 */
$page_title = 'Dashboard';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/header.php';

$userId = $_SESSION['user_id'];
$currentUser = getCurrentUser($pdo);

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Claim Daily Reward
    if (isset($_POST['claim_daily'])) {
        if (isset($_POST['csrf_token']) && verifyCSRF($_POST['csrf_token'])) {
            $reward = claimDailyReward($pdo, $userId);
            if ($reward) {
                setFlash('success', "🎉 Daily reward claimed! You earned <strong>{$reward['coins']} coins</strong> (Streak: Day {$reward['streak']}).");
            } else {
                setFlash('danger', "You have already claimed your daily reward today.");
            }
        } else {
            setFlash('danger', "Invalid CSRF token. Please try again.");
        }
        redirect(BASE_URL . '/user/dashboard.php');
    }

    // 2. Mark All Notifications as Read
    if (isset($_POST['mark_all_read'])) {
        if (isset($_POST['csrf_token']) && verifyCSRF($_POST['csrf_token'])) {
            $stmt = $pdo->prepare("UPDATE pet_notifications SET is_read = 1 WHERE user_id = ?");
            $stmt->execute([$userId]);
            setFlash('success', "All notifications marked as read.");
        } else {
            setFlash('danger', "Invalid CSRF token.");
        }
        redirect(BASE_URL . '/user/dashboard.php');
    }

    // 3. Mark Single Notification as Read
    if (isset($_POST['mark_read_id'])) {
        if (isset($_POST['csrf_token']) && verifyCSRF($_POST['csrf_token'])) {
            $notifId = (int)$_POST['mark_read_id'];
            $stmt = $pdo->prepare("UPDATE pet_notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
            $stmt->execute([$notifId, $userId]);
            // Return JSON if AJAX, otherwise redirect
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                echo json_encode(['success' => true]);
                exit;
            }
        }
        redirect(BASE_URL . '/user/dashboard.php');
    }
}

// Fetch user data
$myPets = getUserPets($pdo, $userId);
$unreadCount = getUnreadNotificationCount($pdo, $userId);
$allNotifications = getNotifications($pdo, $userId, 15);

// Check reward status
$canClaim = canClaimDailyReward($pdo, $userId);
$streakDay = getStreakDay($pdo, $userId);
// If they claimed today, their current streak is what is in daily_rewards for today.
// If they haven't claimed, getStreakDay tells them what day they WILL claim.
$displayStreak = $canClaim ? ($streakDay - 1) : $streakDay;
if ($displayStreak < 0) $displayStreak = 0;
if ($displayStreak > 7) $displayStreak = 7;

// Fetch last few care logs for timeline
$logStmt = $pdo->prepare("
    SELECT l.*, p.pet_name, p.image as pet_image 
    FROM pet_care_logs l 
    INNER JOIN pets p ON l.pet_id = p.id 
    WHERE l.user_id = ? 
    ORDER BY l.created_at DESC 
    LIMIT 5
");
$logStmt->execute([$userId]);
$recentLogs = $logStmt->fetchAll();

// CSRF Token
$csrfToken = generateCSRF();
?><!-- Hero Welcome Banner -->
<div class="hero-gradient mb-4 p-4 text-white rounded-4 position-relative overflow-hidden shadow-sm" style="background: radial-gradient(circle at top right, #312e81 0%, #0f172a 100%);">
    <div class="hero-shape"></div>
    <div class="position-relative z-index-2">
        <h2 class="fw-bold mb-2">Welcome back, <?php echo sanitize(explode(' ', $currentUser['name'])[0]); ?>! 👋</h2>
        <p class="lead text-white-50 fs-6 mb-3">Your adopted pets are waiting for your care. Don't forget to interact, feed, and play with them to earn coins and XP!</p>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?php echo BASE_URL; ?>/user/pets.php" class="btn btn-accent px-4 py-2 rounded-pill shadow-sm">
                <i class="fa-solid fa-paw me-2"></i>Adopt Another Pet
            </a>
            <a href="<?php echo BASE_URL; ?>/user/shop.php" class="btn btn-outline-light px-4 py-2 rounded-pill">
                <i class="fa-solid fa-shop me-2"></i>Visit Pet Shop
            </a>
        </div>
    </div>
</div>

<!-- Quick Stats Dashboard Row -->
<div class="row g-3 mb-4">
    <!-- Stat Item 1: Adopted pets -->
    <div class="col-md-3 col-sm-6">
        <div class="glass-card p-3 d-flex align-items-center justify-content-between h-100 shadow-sm border border-light">
            <div>
                <span class="text-muted extra-small d-block text-uppercase fw-semibold">My Adopted Pets</span>
                <h3 class="fw-bold text-primary mb-0 mt-1"><?php echo count($myPets); ?></h3>
            </div>
            <div class="bg-primary-subtle text-primary rounded-3 p-3 fs-4 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                <i class="fa-solid fa-dog"></i>
            </div>
        </div>
    </div>
    
    <!-- Stat Item 2: Care Points -->
    <div class="col-md-3 col-sm-6">
        <div class="glass-card p-3 d-flex align-items-center justify-content-between h-100 shadow-sm border border-light">
            <div>
                <span class="text-muted extra-small d-block text-uppercase fw-semibold">Care Points (XP)</span>
                <h3 class="fw-bold text-accent mb-0 mt-1"><?php echo number_format($currentUser['points']); ?></h3>
            </div>
            <div class="bg-info-subtle text-accent rounded-3 p-3 fs-4 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                <i class="fa-solid fa-star"></i>
            </div>
        </div>
    </div>
    
    <!-- Stat Item 3: Coins -->
    <div class="col-md-3 col-sm-6">
        <div class="glass-card p-3 d-flex align-items-center justify-content-between h-100 shadow-sm border border-light">
            <div>
                <span class="text-muted extra-small d-block text-uppercase fw-semibold">Coins Balance</span>
                <h3 class="fw-bold text-warning mb-0 mt-1"><?php echo number_format($currentUser['coins']); ?></h3>
            </div>
            <div class="bg-warning-subtle text-warning rounded-3 p-3 fs-4 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                <i class="fa-solid fa-coins"></i>
            </div>
        </div>
    </div>
    
    <!-- Stat Item 4: Streak -->
    <div class="col-md-3 col-sm-6">
        <div class="glass-card p-3 d-flex align-items-center justify-content-between h-100 shadow-sm border border-light">
            <div>
                <span class="text-muted extra-small d-block text-uppercase fw-semibold">Daily Streak</span>
                <h3 class="fw-bold text-danger mb-0 mt-1"><?php echo $displayStreak; ?> Days</h3>
            </div>
            <div class="bg-danger-subtle text-danger rounded-3 p-3 fs-4 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                <i class="fa-solid fa-fire animate-pulse"></i>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Active Buddies List -->
    <div class="col-lg-8">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h4 class="fw-bold mb-0">My Adopted Pets</h4>
            <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2 fw-semibold">
                <?php echo count($myPets); ?> Adopted
            </span>
        </div>

        <?php if (empty($myPets)): ?>
            <div class="glass-card p-5 text-center shadow-sm border border-light">
                <div class="bg-light rounded-circle p-4 d-inline-flex mb-3">
                    <i class="fa-solid fa-dog fs-1 text-muted"></i>
                </div>
                <h5 class="fw-bold">No Pets Adopted Yet</h5>
                <p class="text-muted mb-4">Adopt your very first virtual pet today, name them, feed them, and watch them grow!</p>
                <a href="<?php echo BASE_URL; ?>/user/pets.php" class="btn btn-accent px-5 py-2 rounded-pill shadow">
                    Explore Available Pets <i class="fa-solid fa-arrow-right ms-2"></i>
                </a>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($myPets as $pet): 
                    // Apply stat decay on page view to ensure correct stats
                    applyDecay($pdo, $pet['id']);
                    // Reload pet stats after decay
                    $pet = getPetById($pdo, $pet['id']);
                    
                    $moodData = getPetMood($pet['health'], $pet['hunger'], $pet['happiness'], $pet['energy'], $pet['cleanliness']);
                    $xpNext = getXPForNextLevel($pet['level']);
                    $xpPercent = min(100, round(($pet['xp'] / $xpNext) * 100));
                ?>
                    <div class="col-md-6">
                        <div class="pet-card glass-card h-100 shadow-sm border border-light transition-all hover-translate-y">
                            <div class="pet-card-img-wrapper position-relative overflow-hidden" style="height: 180px; background-color: #f8fafc;">
                                <span class="pet-card-badge badge position-absolute top-0 start-0 m-3 px-3 py-2 text-white shadow-sm" style="background-color: <?php echo $moodData['color']; ?>; z-index: 5;">
                                    <?php echo $moodData['emoji'] . ' ' . $pet['mood']; ?>
                                </span>
                                <img src="<?php echo getPetImage($pet['image']); ?>" class="w-100 h-100 object-fit-contain p-3" alt="<?php echo sanitize($pet['pet_name']); ?>">
                            </div>
                            
                            <div class="p-4">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h5 class="fw-bold mb-1"><?php echo sanitize($pet['pet_name']); ?></h5>
                                        <span class="text-muted small">
                                            <i class="fa-solid fa-dna me-1"></i><?php echo sanitize($pet['breed'] ?: $pet['pet_type']); ?>
                                        </span>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-secondary rounded-pill px-2 py-1 small">Lvl <?php echo $pet['level']; ?></span>
                                    </div>
                                </div>
                                
                                <!-- Mini Stat bars -->
                                <div class="mb-4">
                                    <!-- Health -->
                                    <div class="stat-bar-container mb-2">
                                        <div class="stat-label-row">
                                            <span>Health</span>
                                            <span class="fw-bold"><?php echo $pet['health']; ?>%</span>
                                        </div>
                                        <div class="custom-progress">
                                            <div class="custom-progress-bar" style="width: <?php echo $pet['health']; ?>%; background-color: <?php echo getStatColor($pet['health']); ?>;"></div>
                                        </div>
                                    </div>
                                    <!-- Hunger (Inversed color: higher hunger is bad) -->
                                    <div class="stat-bar-container mb-2">
                                        <div class="stat-label-row">
                                            <span>Hunger</span>
                                            <span class="fw-bold"><?php echo $pet['hunger']; ?>%</span>
                                        </div>
                                        <div class="custom-progress">
                                            <div class="custom-progress-bar" style="width: <?php echo $pet['hunger']; ?>%; background-color: <?php echo getStatColor($pet['hunger'], true); ?>;"></div>
                                        </div>
                                    </div>
                                    <!-- Happiness -->
                                    <div class="stat-bar-container mb-0">
                                        <div class="stat-label-row">
                                            <span>Happiness</span>
                                            <span class="fw-bold"><?php echo $pet['happiness']; ?>%</span>
                                        </div>
                                        <div class="custom-progress">
                                            <div class="custom-progress-bar" style="width: <?php echo $pet['happiness']; ?>%; background-color: <?php echo getStatColor($pet['happiness']); ?>;"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-grid">
                                    <a href="<?php echo BASE_URL; ?>/user/pet_care.php?id=<?php echo $pet['id']; ?>" class="btn btn-accent py-2 rounded-pill shadow-sm">
                                        <i class="fa-solid fa-heart-pulse me-2"></i>Care & Interact
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Right Column: Daily Claims, Activities & Notices -->
    <div class="col-lg-4">
        <!-- Daily Claim Reward widget -->
        <div class="glass-card p-4 mb-4 shadow-sm border border-light">
            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                <h5 class="fw-bold mb-0">
                    <i class="fa-solid fa-gift text-accent me-2"></i>Daily Claim
                </h5>
                <span class="badge bg-warning-subtle text-warning fw-bold rounded-pill">
                    <i class="fa-solid fa-fire me-1"></i>Streak: <?php echo $displayStreak; ?>d
                </span>
            </div>
            <p class="text-muted small mb-3">Claim your daily reward to get virtual coins and grow your care streak!</p>
            
            <!-- Streak Circles Grid -->
            <div class="d-flex justify-content-between gap-1 mb-4">
                <?php 
                $rewards_map = [1=>10, 2=>20, 3=>30, 4=>40, 5=>50, 6=>75, 7=>100];
                for ($d = 1; $d <= 7; $d++): 
                    $isCompleted = ($canClaim && $d < $streakDay) || (!$canClaim && $d <= $streakDay);
                    $isCurrent = $canClaim && $d == $streakDay;
                ?>
                    <div class="text-center" style="width: 13%;">
                        <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-1 <?php 
                            if ($isCompleted) echo 'bg-success text-white';
                            elseif ($isCurrent) echo 'bg-warning text-white border border-warning shadow-sm animate-pulse';
                            else echo 'bg-light text-muted border';
                        ?>" style="width: 28px; height: 28px; font-size: 0.75rem; font-weight: 700;">
                            <?php if ($isCompleted): ?>
                                <i class="fa-solid fa-check"></i>
                            <?php else: ?>
                                <?php echo $d; ?>
                            <?php endif; ?>
                        </div>
                        <span class="text-muted extra-small d-block fw-semibold" style="font-size: 0.65rem;">+<?php echo $rewards_map[$d]; ?></span>
                    </div>
                <?php endfor; ?>
            </div>

            <?php if ($canClaim): ?>
                <form action="<?php echo BASE_URL; ?>/user/dashboard.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <button type="submit" name="claim_daily" class="btn btn-accent w-100 py-2 rounded-pill shadow-sm">
                        <i class="fa-solid fa-gift me-2"></i>Claim Today's Reward!
                    </button>
                </form>
            <?php else: ?>
                <button class="btn btn-secondary w-100 py-2 rounded-pill" disabled>
                    <i class="fa-solid fa-circle-check me-2"></i>Claimed Today
                </button>
            <?php endif; ?>
        </div>

        <!-- Recent Activities Card -->
        <div class="glass-card p-4 mb-4 shadow-sm border border-light">
            <h5 class="fw-bold mb-3 border-bottom pb-2">
                <i class="fa-solid fa-clock-rotate-left text-primary me-2"></i>Recent Activities
            </h5>
            
            <div style="max-height: 280px; overflow-y: auto;" class="pe-2">
                <?php if (empty($recentLogs)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fa-solid fa-clipboard-list fs-2 mb-2"></i>
                        <p class="mb-0 small">No care history logged yet.</p>
                    </div>
                <?php else: ?>
                    <div class="timeline-list">
                        <?php foreach ($recentLogs as $log): ?>
                            <div class="timeline-item pb-3 position-relative">
                                <div class="d-flex align-items-center mb-1">
                                    <span class="fw-bold text-dark small me-2"><?php echo sanitize($log['pet_name']); ?></span>
                                    <span class="badge bg-light text-capitalize text-muted extra-small border">
                                        <?php echo $log['action_type']; ?>
                                    </span>
                                </div>
                                <p class="mb-1 text-muted small" style="font-size: 0.8rem;"><?php echo sanitize($log['action_value']); ?></p>
                                <div class="d-flex justify-content-between align-items-center mt-1">
                                    <span class="text-muted extra-small" style="font-size: 0.7rem;"><?php echo timeAgo($log['created_at']); ?></span>
                                    <span class="extra-small text-success fw-semibold">
                                        <?php if ($log['coins_earned'] > 0): ?>
                                            <span class="text-warning"><i class="fa-solid fa-coins me-1"></i>+<?php echo $log['coins_earned']; ?></span>
                                        <?php endif; ?>
                                        <?php if ($log['xp_earned'] > 0): ?>
                                            <span class="text-accent ms-2"><i class="fa-solid fa-star me-1"></i>+<?php echo $log['xp_earned']; ?> XP</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-2 pt-2 border-top">
                        <a href="<?php echo BASE_URL; ?>/user/care_history.php" class="text-accent text-decoration-none small fw-semibold">
                            View Full History <i class="fa-solid fa-arrow-right ms-1 text-xs"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Dashboard Notifications Card -->
        <div class="glass-card p-4 shadow-sm border border-light" id="notifications">
            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                <h5 class="fw-bold mb-0">
                    <i class="fa-regular fa-bell text-primary me-2"></i>Notifications 
                    <?php if ($unreadCount > 0): ?>
                        <span class="badge bg-danger rounded-pill ms-2 text-xs"><?php echo $unreadCount; ?></span>
                    <?php endif; ?>
                </h5>
                <?php if ($unreadCount > 0): ?>
                    <form action="<?php echo BASE_URL; ?>/user/dashboard.php" method="POST" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <button type="submit" name="mark_all_read" class="btn btn-link text-accent p-0 text-decoration-none small fw-semibold">
                            Mark all
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <div style="max-height: 280px; overflow-y: auto;" class="pe-2">
                <?php if (empty($allNotifications)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fa-regular fa-bell-slash fs-2 mb-2"></i>
                        <p class="mb-0 small">No notifications found.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($allNotifications as $n): ?>
                            <div class="list-group-item px-0 py-2 border-bottom-0 <?php echo $n['is_read'] ? '' : 'bg-light-subtle rounded px-2 mb-2'; ?> position-relative">
                                <div class="d-flex align-items-start">
                                    <div class="notif-icon-circle bg-<?php echo $n['type']; ?>-subtle text-<?php echo $n['type'] == 'danger' ? 'danger' : ($n['type'] == 'warning' ? 'warning' : 'primary'); ?> me-2 shadow-sm" style="width: 28px; height: 28px; font-size: 0.75rem; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                        <?php 
                                        if ($n['type'] == 'danger') echo '<i class="fa-solid fa-triangle-exclamation"></i>';
                                        elseif ($n['type'] == 'warning') echo '<i class="fa-solid fa-circle-info"></i>';
                                        elseif ($n['type'] == 'success') echo '<i class="fa-solid fa-circle-check"></i>';
                                        else echo '<i class="fa-solid fa-bell"></i>';
                                        ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <p class="mb-1 text-dark extra-small <?php echo $n['is_read'] ? '' : 'fw-semibold'; ?>" style="font-size: 0.75rem;"><?php echo sanitize($n['message']); ?></p>
                                        <span class="text-muted extra-small" style="font-size: 0.65rem;"><?php echo timeAgo($n['created_at']); ?></span>
                                    </div>
                                    <?php if (!$n['is_read']): ?>
                                        <form action="<?php echo BASE_URL; ?>/user/dashboard.php" method="POST" class="ms-2">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                            <input type="hidden" name="mark_read_id" value="<?php echo $n['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-light rounded-circle p-0" title="Mark read" style="width: 22px; height: 22px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fa-solid fa-check" style="font-size: 0.65rem;"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php 
require_once __DIR__ . '/../includes/footer.php';
?>
