<?php
/**
 * PawPal Admin - Main Dashboard
 */
$page_title = 'System Overview';
require_once __DIR__ . '/includes/admin_header.php';

// Fetch counts
try {
    // Total Users
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $total_users = $stmt->fetchColumn();

    // Total Pets
    $stmt = $pdo->query("SELECT COUNT(*) FROM pets");
    $total_pets = $stmt->fetchColumn();

    // Pending Adoptions
    $stmt = $pdo->query("SELECT COUNT(*) FROM adoptions WHERE adoption_status = 'pending'");
    $pending_adoptions = $stmt->fetchColumn();

    // Total Care Logs
    $stmt = $pdo->query("SELECT COUNT(*) FROM pet_care_logs");
    $total_care_logs = $stmt->fetchColumn();

    // Recent Adoption Requests (Limit 5)
    $recent_adoptions_stmt = $pdo->query("
        SELECT a.id, a.adoption_status, a.created_at, u.name AS user_name, u.email AS user_email, p.pet_name, p.pet_type, p.image AS pet_image
        FROM adoptions a
        JOIN users u ON a.user_id = u.id
        JOIN pets p ON a.pet_id = p.id
        ORDER BY a.created_at DESC
        LIMIT 5
    ");
    $recent_adoptions = $recent_adoptions_stmt->fetchAll();

    // Recent Activity Logs (Limit 6)
    $recent_logs_stmt = $pdo->query("
        SELECT l.id, l.action_type, l.coins_earned, l.xp_earned, l.created_at, u.name AS user_name, p.pet_name
        FROM pet_care_logs l
        JOIN users u ON l.user_id = u.id
        JOIN pets p ON l.pet_id = p.id
        ORDER BY l.created_at DESC
        LIMIT 6
    ");
    $recent_logs = $recent_logs_stmt->fetchAll();

} catch (PDOException $e) {
    die("Error fetching dashboard statistics: " . $e->getMessage());
}
?>

<!-- Header title / greeting -->
<div class="row align-items-center mb-4">
    <div class="col-md-8">
        <h2 class="fw-bold mb-1 text-slate-800">Welcome Back, <?php echo $admin_name; ?>!</h2>
        <p class="text-muted mb-0">Here's what's happening with PawPal simulator today.</p>
    </div>
</div>

<!-- Stat Cards Grid -->
<div class="row g-4 mb-5">
    <!-- Users Card -->
    <div class="col-xl-3 col-md-6">
        <div class="admin-stat-card">
            <div class="card-info">
                <h6>Total Registered Users</h6>
                <h3><?php echo number_format($total_users); ?></h3>
            </div>
            <div class="card-icon primary-bg">
                <i class="bi bi-people-fill"></i>
            </div>
        </div>
    </div>
    
    <!-- Pets Card -->
    <div class="col-xl-3 col-md-6">
        <div class="admin-stat-card">
            <div class="card-info">
                <h6>Total Virtual Pets</h6>
                <h3><?php echo number_format($total_pets); ?></h3>
            </div>
            <div class="card-icon accent-bg">
                <i class="bi bi-heart-fill"></i>
            </div>
        </div>
    </div>
    
    <!-- Pending Adoptions Card -->
    <div class="col-xl-3 col-md-6">
        <div class="admin-stat-card">
            <div class="card-info">
                <h6>Pending Applications</h6>
                <h3><?php echo number_format($pending_adoptions); ?></h3>
            </div>
            <div class="card-icon warning-bg">
                <i class="bi bi-file-earmark-text-fill"></i>
            </div>
        </div>
    </div>
    
    <!-- Care Logs Card -->
    <div class="col-xl-3 col-md-6">
        <div class="admin-stat-card">
            <div class="card-info">
                <h6>Care Actions Logged</h6>
                <h3><?php echo number_format($total_care_logs); ?></h3>
            </div>
            <div class="card-icon success-bg">
                <i class="bi bi-clipboard2-pulse-fill"></i>
            </div>
        </div>
    </div>
</div>

<!-- Main Dashboard Split Content -->
<div class="row g-4">
    <!-- Recent Adoptions Column -->
    <div class="col-lg-7">
        <div class="admin-card h-100">
            <div class="card-header">
                <span class="d-flex align-items-center gap-2">
                    <i class="bi bi-clipboard-check text-primary"></i>
                    <span>Recent Adoption Applications</span>
                </span>
                <a href="<?php echo BASE_URL; ?>/admin/adoption_requests.php" class="btn btn-sm btn-link text-decoration-none">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recent_adoptions)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-2 text-slate-300"></i>
                        <span>No adoption requests logged yet.</span>
                    </div>
                <?php else: ?>
                    <div class="table-responsive border-0">
                        <table class="table admin-table align-middle">
                            <thead>
                                <tr>
                                    <th>Applicant</th>
                                    <th>Pet Requested</th>
                                    <th>Date Submitted</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_adoptions as $adoption): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="fw-semibold text-slate-800"><?php echo sanitize($adoption['user_name']); ?></span>
                                                <span class="text-muted extra-small" style="font-size: 0.75rem;"><?php echo sanitize($adoption['user_email']); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <?php 
                                                $p_image = !empty($adoption['pet_image']) ? BASE_URL . '/assets/images/' . $adoption['pet_image'] : BASE_URL . '/assets/images/default-pet.png';
                                                ?>
                                                <img src="<?php echo $p_image; ?>" alt="Pet" class="rounded" style="width: 32px; height: 32px; object-fit: cover;">
                                                <div>
                                                    <span class="fw-semibold"><?php echo sanitize($adoption['pet_name']); ?></span>
                                                    <span class="badge bg-secondary extra-small ms-1" style="font-size: 0.65rem;"><?php echo sanitize($adoption['pet_type']); ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="small text-slate-500">
                                            <?php echo date('M d, Y h:i A', strtotime($adoption['created_at'])); ?>
                                        </td>
                                        <td>
                                            <span class="admin-badge status-<?php echo $adoption['adoption_status']; ?>">
                                                <?php if ($adoption['adoption_status'] === 'pending'): ?>
                                                    <i class="bi bi-clock-history"></i> Pending
                                                <?php elseif ($adoption['adoption_status'] === 'approved'): ?>
                                                    <i class="bi bi-check-circle-fill"></i> Approved
                                                <?php else: ?>
                                                    <i class="bi bi-x-circle-fill"></i> Rejected
                                                <?php endif; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Actions Column -->
    <div class="col-lg-5">
        <div class="admin-card h-100">
            <div class="card-header">
                <span class="d-flex align-items-center gap-2">
                    <i class="bi bi-activity text-accent"></i>
                    <span>Real-time Care Activity</span>
                </span>
                <a href="<?php echo BASE_URL; ?>/admin/reports.php" class="btn btn-sm btn-link text-decoration-none">Analytics</a>
            </div>
            <div class="card-body">
                <?php if (empty($recent_logs)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-lightning-charge fs-1 d-block mb-2 text-slate-300"></i>
                        <span>No care activities recorded yet.</span>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($recent_logs as $log): ?>
                            <?php 
                            // Action colors & icons mappings
                            $action = $log['action_type'];
                            $icon = 'bi-question-circle-fill';
                            $color = 'bg-secondary';
                            switch ($action) {
                                case 'feed':
                                    $icon = 'fa-solid fa-bone';
                                    $color = 'bg-success';
                                    break;
                                case 'groom':
                                    $icon = 'fa-solid fa-soap';
                                    $color = 'bg-info';
                                    break;
                                case 'play':
                                    $icon = 'fa-solid fa-circle-play';
                                    $color = 'bg-warning';
                                    break;
                                case 'sleep':
                                    $icon = 'fa-solid fa-moon';
                                    $color = 'bg-primary';
                                    break;
                                case 'water':
                                    $icon = 'fa-solid fa-droplet';
                                    $color = 'bg-info';
                                    break;
                                case 'medicine':
                                    $icon = 'fa-solid fa-kit-medical';
                                    $color = 'bg-danger';
                                    break;
                                case 'training':
                                    $icon = 'fa-solid fa-award';
                                    $color = 'bg-purple';
                                    break;
                            }
                            ?>
                            <div class="d-flex align-items-center justify-content-between p-2 rounded-3 border-bottom pb-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background-color: var(--primary); flex-shrink: 0;">
                                        <i class="<?php echo $icon; ?> small"></i>
                                    </div>
                                    <div>
                                        <p class="mb-0 small fw-semibold">
                                            <strong><?php echo sanitize($log['user_name']); ?></strong> 
                                            <span class="text-muted">performed</span> 
                                            <span class="badge bg-light text-dark border"><?php echo $action; ?></span>
                                            <span class="text-muted">on</span> 
                                            <strong><?php echo sanitize($log['pet_name']); ?></strong>
                                        </p>
                                        <span class="text-muted extra-small" style="font-size: 0.7rem;">
                                            <i class="bi bi-clock me-1"></i><?php echo date('h:i A', strtotime($log['created_at'])); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="text-end" style="flex-shrink: 0;">
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle extra-small d-block mb-1">
                                        +<?php echo $log['coins_earned']; ?> coins
                                    </span>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle extra-small d-block">
                                        +<?php echo $log['xp_earned']; ?> XP
                                    </span>
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
require_once __DIR__ . '/includes/admin_footer.php';
?>
