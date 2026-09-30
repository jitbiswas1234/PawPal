<?php
/**
 * PawPal - Pet Care History Log
 */
$page_title = "Care History Log";
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/header.php';

$userId = $_SESSION['user_id'];

// Get all user pets for filter dropdown
$myPets = getUserPets($pdo, $userId);

// Get query filters
$filterPetId = isset($_GET['pet_id']) ? (int)$_GET['pet_id'] : 0;
$filterAction = isset($_GET['action_type']) ? sanitize($_GET['action_type']) : '';

// Build SQL query
$queryStr = "
    SELECT pcl.*, p.pet_name, p.pet_type, p.image 
    FROM pet_care_logs pcl
    INNER JOIN pets p ON pcl.pet_id = p.id
    WHERE pcl.user_id = ?
";
$params = [$userId];

if ($filterPetId) {
    $queryStr .= " AND pcl.pet_id = ?";
    $params[] = $filterPetId;
}

if ($filterAction) {
    $queryStr .= " AND pcl.action_type = ?";
    $params[] = $filterAction;
}

$queryStr .= " ORDER BY pcl.created_at DESC LIMIT 100"; // Limit to top 100 for performance

$stmt = $pdo->prepare($queryStr);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Calculate total statistics for display card
$statsStmt = $pdo->prepare("
    SELECT COUNT(*) as total_actions, SUM(coins_earned) as total_coins, SUM(xp_earned) as total_xp
    FROM pet_care_logs
    WHERE user_id = ?
");
$statsStmt->execute([$userId]);
$historyStats = $statsStmt->fetch();
?>

<div class="mb-4">
    <h1 class="fw-bold tracking-tight text-dark mb-1">Care History Log</h1>
    <p class="text-muted">Review all your previous care activities and rewards earned.</p>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card glass-card border-0 p-3 bg-primary-subtle text-primary text-center">
            <div class="card-body">
                <i class="fa-solid fa-hand-holding-heart fs-3 mb-2"></i>
                <h5 class="fw-semibold mb-1">Total Interactions</h5>
                <h2 class="fw-bold m-0"><?php echo number_format($historyStats['total_actions'] ?? 0); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card glass-card border-0 p-3 bg-warning-subtle text-warning text-center">
            <div class="card-body">
                <i class="fa-solid fa-coins fs-3 mb-2"></i>
                <h5 class="fw-semibold mb-1">Total Coins Earned</h5>
                <h2 class="fw-bold m-0"><?php echo number_format($historyStats['total_coins'] ?? 0); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card glass-card border-0 p-3 bg-accent-subtle text-accent text-center">
            <div class="card-body">
                <i class="fa-solid fa-star fs-3 mb-2"></i>
                <h5 class="fw-semibold mb-1">Total XP Generated</h5>
                <h2 class="fw-bold m-0"><?php echo number_format($historyStats['total_xp'] ?? 0); ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- Filters Panel -->
<div class="card glass-card border-0 p-4 mb-4">
    <form method="GET" action="" class="row g-3 align-items-end">
        <div class="col-md-4">
            <label for="pet_id" class="form-label fw-semibold text-muted small">Filter by Pet</label>
            <select name="pet_id" id="pet_id" class="form-select rounded-pill">
                <option value="0">-- All Pets --</option>
                <?php foreach ($myPets as $pet): ?>
                    <option value="<?php echo $pet['id']; ?>" <?php echo $filterPetId === (int)$pet['id'] ? 'selected' : ''; ?>>
                        <?php echo sanitize($pet['pet_name']); ?> (<?php echo sanitize($pet['pet_type']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label for="action_type" class="form-label fw-semibold text-muted small">Filter by Action Type</label>
            <select name="action_type" id="action_type" class="form-select rounded-pill">
                <option value="">-- All Actions --</option>
                <option value="feed" <?php echo $filterAction === 'feed' ? 'selected' : ''; ?>>Feeding (Feed)</option>
                <option value="water" <?php echo $filterAction === 'water' ? 'selected' : ''; ?>>Hydrating (Water)</option>
                <option value="play" <?php echo $filterAction === 'play' ? 'selected' : ''; ?>>Playing (Play)</option>
                <option value="groom" <?php echo $filterAction === 'groom' ? 'selected' : ''; ?>>Grooming (Groom)</option>
                <option value="sleep" <?php echo $filterAction === 'sleep' ? 'selected' : ''; ?>>Sleeping (Sleep)</option>
                <option value="medicine" <?php echo $filterAction === 'medicine' ? 'selected' : ''; ?>>Healthcare (Medicine)</option>
                <option value="training" <?php echo $filterAction === 'training' ? 'selected' : ''; ?>>Training (Train)</option>
            </select>
        </div>
        <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary rounded-pill w-100 py-2 fw-semibold">
                <i class="fa-solid fa-filter me-2"></i>Apply Filters
            </button>
            <a href="care_history.php" class="btn btn-outline-secondary rounded-pill w-100 py-2 fw-semibold">
                <i class="fa-solid fa-rotate-left me-2"></i>Reset
            </a>
        </div>
    </form>
</div>

<!-- History Logs Table -->
<div class="card glass-card border-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">Pet</th>
                    <th>Action</th>
                    <th>Detail</th>
                    <th>Reward</th>
                    <th>Experience</th>
                    <th class="pe-4">Date & Time</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-receipt fs-1 mb-2 text-secondary" style="opacity: 0.3;"></i>
                            <p class="mb-0 fw-semibold">No interaction logs found matching the filter criteria.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <img src="<?php echo getPetImage($log['image']); ?>" alt="<?php echo sanitize($log['pet_name']); ?>" class="rounded-circle me-3" style="width: 40px; height: 40px; object-fit: cover;">
                                    <div>
                                        <div class="fw-bold text-dark mb-0"><?php echo sanitize($log['pet_name']); ?></div>
                                        <span class="text-muted extra-small"><?php echo sanitize($log['pet_type']); ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge rounded-pill px-3 py-1.5 small fw-semibold <?php 
                                    if ($log['action_type'] === 'feed') echo 'bg-warning-subtle text-warning';
                                    elseif ($log['action_type'] === 'water') echo 'bg-info-subtle text-info';
                                    elseif ($log['action_type'] === 'play') echo 'bg-danger-subtle text-danger';
                                    elseif ($log['action_type'] === 'groom') echo 'bg-success-subtle text-success';
                                    elseif ($log['action_type'] === 'sleep') echo 'bg-purple-subtle text-purple';
                                    elseif ($log['action_type'] === 'medicine') echo 'bg-danger-subtle text-danger';
                                    elseif ($log['action_type'] === 'training') echo 'bg-primary-subtle text-primary';
                                    else echo 'bg-secondary-subtle text-secondary';
                                ?>">
                                    <?php 
                                        if ($log['action_type'] === 'feed') echo '🍖 Feed';
                                        elseif ($log['action_type'] === 'water') echo '💧 Water';
                                        elseif ($log['action_type'] === 'play') echo '⚾ Play';
                                        elseif ($log['action_type'] === 'groom') echo '🧼 Groom';
                                        elseif ($log['action_type'] === 'sleep') echo '😴 Sleep';
                                        elseif ($log['action_type'] === 'medicine') echo '💊 Medicine';
                                        elseif ($log['action_type'] === 'training') echo '🏋️ Training';
                                        else echo sanitize($log['action_type']);
                                    ?>
                                </span>
                            </td>
                            <td class="small text-muted">
                                <?php echo sanitize($log['action_value']); ?>
                            </td>
                            <td>
                                <?php if ($log['coins_earned'] > 0): ?>
                                    <span class="text-warning fw-bold"><i class="fa-solid fa-coins me-1"></i>+<?php echo $log['coins_earned']; ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($log['xp_earned'] > 0): ?>
                                    <span class="text-accent fw-bold"><i class="fa-solid fa-star me-1"></i>+<?php echo $log['xp_earned']; ?> XP</span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="pe-4 small text-muted">
                                <?php echo date('M d, Y h:i A', strtotime($log['created_at'])); ?>
                                <span class="d-block extra-small text-muted-dark"><?php echo timeAgo($log['created_at']); ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
