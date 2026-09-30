<?php
/**
 * PawPal - User Profile Management & Caregiver Statistics
 */
$page_title = 'My Profile';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/header.php';

$userId = $_SESSION['user_id'];
$currentUser = getCurrentUser($pdo);

// Handle Profile Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['csrf_token']) && verifyCSRF($_POST['csrf_token'])) {
        $name = sanitize($_POST['name']);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $errors = [];

        // Validate basic inputs
        if (empty($name)) {
            $errors[] = "Name cannot be empty.";
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please enter a valid email address.";
        }

        // Email uniqueness check if changing
        if ($email !== $currentUser['email']) {
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $checkStmt->execute([$email, $userId]);
            if ($checkStmt->fetch()) {
                $errors[] = "This email is already in use by another user.";
            }
        }

        // Password change logic
        $passwordChanged = false;
        $hashedPassword = $currentUser['password'];

        if (!empty($newPassword)) {
            if (empty($currentPassword)) {
                $errors[] = "You must enter your current password to change your password.";
            } elseif (!password_verify($currentPassword, $currentUser['password'])) {
                $errors[] = "Your current password was incorrect.";
            } elseif ($newPassword !== $confirmPassword) {
                $errors[] = "New password and password confirmation do not match.";
            } elseif (strlen($newPassword) < 6) {
                $errors[] = "New password must be at least 6 characters long.";
            } else {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $passwordChanged = true;
            }
        }

        if (empty($errors)) {
            try {
                $upStmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, password = ?, updated_at = NOW() WHERE id = ?");
                $upStmt->execute([$name, $email, $hashedPassword, $userId]);
                
                // Update session name if changed
                $_SESSION['user_name'] = $name;

                setFlash('success', "🎉 Profile updated successfully!" . ($passwordChanged ? " Your password was also updated." : ""));
                redirect(BASE_URL . '/user/profile.php');
            } catch (PDOException $e) {
                setFlash('danger', "An error occurred while updating your profile. Please try again.");
            }
        } else {
            // Flash multiple errors
            setFlash('danger', implode('<br>', $errors));
            redirect(BASE_URL . '/user/profile.php');
        }
    } else {
        setFlash('danger', 'Security verification failed.');
        redirect(BASE_URL . '/user/profile.php');
    }
}

// Fetch stats for profile
// 1. Pets Adopted
$petsAdopted = getUserPetCount($pdo, $userId);

// 2. Total care logs
$logCountStmt = $pdo->prepare("SELECT COUNT(*) as count FROM pet_care_logs WHERE user_id = ?");
$logCountStmt->execute([$userId]);
$totalCareLogs = $logCountStmt->fetch()['count'];

// 3. Care Earnings
$coinsEarnedStmt = $pdo->prepare("SELECT SUM(coins_earned) as total_coins, SUM(xp_earned) as total_xp FROM pet_care_logs WHERE user_id = ?");
$coinsEarnedStmt->execute([$userId]);
$careEarnings = $coinsEarnedStmt->fetch();
$totalCoinsFromCare = $careEarnings['total_coins'] ?? 0;
$totalXpFromCare = $careEarnings['total_xp'] ?? 0;

// 4. Daily Reward Stats
$dailyStmt = $pdo->prepare("
    SELECT COUNT(*) as claims, MAX(streak_day) as max_streak, SUM(coins_earned) as reward_coins 
    FROM daily_rewards 
    WHERE user_id = ?
");
$dailyStmt->execute([$userId]);
$dailyStats = $dailyStmt->fetch();
$totalClaims = $dailyStats['claims'] ?? 0;
$maxStreak = $dailyStats['max_streak'] ?? 0;
$totalCoinsFromRewards = $dailyStats['reward_coins'] ?? 0;

$csrfToken = generateCSRF();
?>

<div class="row g-4">
    <!-- Left Column: Edit Profile Form -->
    <div class="col-lg-7">
        <div class="glass-card p-4 bg-white">
            <h4 class="fw-bold mb-4"><i class="fa-regular fa-id-card text-accent me-2"></i>Edit Profile Info</h4>
            
            <form action="<?php echo BASE_URL; ?>/user/profile.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                
                <div class="row g-3">
                    <!-- Full Name -->
                    <div class="col-12">
                        <label for="name" class="form-label small fw-bold text-muted text-uppercase">Full Name</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fa-regular fa-user text-muted"></i></span>
                            <input type="text" class="form-control bg-light" id="name" name="name" value="<?php echo sanitize($currentUser['name']); ?>" required>
                        </div>
                    </div>

                    <!-- Email Address -->
                    <div class="col-12">
                        <label for="email" class="form-label small fw-bold text-muted text-uppercase">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fa-regular fa-envelope text-muted"></i></span>
                            <input type="email" class="form-control bg-light" id="email" name="email" value="<?php echo sanitize($currentUser['email']); ?>" required>
                        </div>
                    </div>

                    <!-- Password Section Title -->
                    <div class="col-12 mt-4">
                        <h6 class="fw-bold text-dark border-bottom pb-2 mb-0">Change Password</h6>
                        <p class="extra-small text-muted mb-3">Leave these fields blank if you do not want to change your password.</p>
                    </div>

                    <!-- Current Password -->
                    <div class="col-12">
                        <label for="current_password" class="form-label small fw-bold text-muted text-uppercase">Current Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fa-solid fa-lock text-muted"></i></span>
                            <input type="password" class="form-control bg-light" id="current_password" name="current_password" placeholder="••••••••">
                        </div>
                    </div>

                    <!-- New Password -->
                    <div class="col-md-6">
                        <label for="new_password" class="form-label small fw-bold text-muted text-uppercase">New Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fa-solid fa-key text-muted"></i></span>
                            <input type="password" class="form-control bg-light" id="new_password" name="new_password" placeholder="Min 6 characters">
                        </div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="col-md-6">
                        <label for="confirm_password" class="form-label small fw-bold text-muted text-uppercase">Confirm New Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fa-solid fa-key text-muted"></i></span>
                            <input type="password" class="form-control bg-light" id="confirm_password" name="confirm_password" placeholder="Repeat password">
                        </div>
                    </div>
                </div>

                <div class="d-grid mt-4 pt-2">
                    <button type="submit" class="btn btn-accent py-2 rounded-pill shadow-sm">
                        <i class="fa-solid fa-floppy-disk me-2"></i>Save Profile Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Right Column: User Lifetime Care Statistics -->
    <div class="col-lg-5">
        <!-- Stats Summary Card -->
        <div class="glass-card p-4 bg-white mb-4">
            <h4 class="fw-bold mb-3 border-bottom pb-2">
                <i class="fa-solid fa-chart-pie text-primary me-2"></i>Caregiver Statistics
            </h4>
            
            <p class="text-muted extra-small mb-4">Your lifetime performance and stats overview on PawPal. Level up pets and claim daily rewards to boost these stats!</p>

            <div class="row g-3">
                <!-- Pets Adopted -->
                <div class="col-6">
                    <div class="p-3 bg-light rounded-3 text-center">
                        <span class="fs-3 text-primary d-block mb-1"><i class="fa-solid fa-dog"></i></span>
                        <span class="text-muted extra-small d-block text-uppercase">Pets Adopted</span>
                        <h4 class="fw-extrabold text-dark mb-0"><?php echo $petsAdopted; ?></h4>
                    </div>
                </div>
                
                <!-- Care Activities -->
                <div class="col-6">
                    <div class="p-3 bg-light rounded-3 text-center">
                        <span class="fs-3 text-success d-block mb-1"><i class="fa-solid fa-heart-pulse"></i></span>
                        <span class="text-muted extra-small d-block text-uppercase">Care Actions</span>
                        <h4 class="fw-extrabold text-dark mb-0"><?php echo $totalCareLogs; ?></h4>
                    </div>
                </div>

                <!-- Care Coin Earnings -->
                <div class="col-6">
                    <div class="p-3 bg-light rounded-3 text-center">
                        <span class="fs-3 text-warning d-block mb-1"><i class="fa-solid fa-coins"></i></span>
                        <span class="text-muted extra-small d-block text-uppercase">Caring Income</span>
                        <h4 class="fw-extrabold text-warning mb-0">+<?php echo number_format($totalCoinsFromCare); ?></h4>
                    </div>
                </div>

                <!-- Daily Reward Earnings -->
                <div class="col-6">
                    <div class="p-3 bg-light rounded-3 text-center">
                        <span class="fs-3 text-danger d-block mb-1"><i class="fa-solid fa-gift"></i></span>
                        <span class="text-muted extra-small d-block text-uppercase">Reward Income</span>
                        <h4 class="fw-extrabold text-danger mb-0">+<?php echo number_format($totalCoinsFromRewards); ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daily Reward & Streak Statistics -->
        <div class="glass-card p-4 bg-white">
            <h5 class="fw-bold mb-3 border-bottom pb-2">
                <i class="fa-solid fa-calendar-check text-warning me-2"></i>Daily reward stats
            </h5>
            
            <ul class="list-group list-group-flush small">
                <li class="list-group-item px-0 d-flex justify-content-between align-items-center bg-transparent">
                    <span class="text-muted">Total reward claims</span>
                    <strong class="text-dark"><?php echo $totalClaims; ?> day<?php echo $totalClaims != 1 ? 's' : ''; ?></strong>
                </li>
                <li class="list-group-item px-0 d-flex justify-content-between align-items-center bg-transparent">
                    <span class="text-muted">Highest daily streak</span>
                    <span class="badge bg-warning text-dark fw-bold rounded-pill px-3 py-1">
                        <i class="fa-solid fa-fire me-1"></i>Day <?php echo $maxStreak; ?>
                    </span>
                </li>
                <li class="list-group-item px-0 d-flex justify-content-between align-items-center bg-transparent">
                    <span class="text-muted">Care points gained</span>
                    <strong class="text-accent"><?php echo number_format($currentUser['points']); ?> XP</strong>
                </li>
                <li class="list-group-item px-0 d-flex justify-content-between align-items-center bg-transparent">
                    <span class="text-muted">Account Registered</span>
                    <strong class="text-dark"><?php echo date('F d, Y', strtotime($currentUser['created_at'])); ?></strong>
                </li>
            </ul>
        </div>
    </div>
</div>

<?php 
require_once __DIR__ . '/../includes/footer.php';
?>
