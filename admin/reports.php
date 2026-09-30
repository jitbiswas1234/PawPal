<?php
/**
 * PawPal Admin - Reports & Analytics
 */
$page_title = 'Analytics & Reports';
require_once __DIR__ . '/includes/admin_header.php';

// ============================================
// 1. Data queries
// ============================================
try {
    // A. Timeline: Care interactions over the last 7 days
    $days = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $display_date = date('M d', strtotime("-$i days"));
        $days[$date] = [
            'label' => $display_date,
            'count' => 0
        ];
    }
    
    $timeline_stmt = $pdo->query("
        SELECT DATE(created_at) as log_date, COUNT(*) as action_count 
        FROM pet_care_logs 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        GROUP BY DATE(created_at)
    ");
    $timeline_results = $timeline_stmt->fetchAll();
    
    foreach ($timeline_results as $row) {
        if (isset($days[$row['log_date']])) {
            $days[$row['log_date']]['count'] = (int)$row['action_count'];
        }
    }
    
    $chart_line_labels = [];
    $chart_line_data = [];
    foreach ($days as $day) {
        $chart_line_labels[] = $day['label'];
        $chart_line_data[] = $day['count'];
    }

    // B. Category Breakdown: Action types
    $actions_stmt = $pdo->query("
        SELECT action_type, COUNT(*) as action_count 
        FROM pet_care_logs 
        GROUP BY action_type 
        ORDER BY action_count DESC
    ");
    $actions_results = $actions_stmt->fetchAll();
    
    $chart_doughnut_labels = [];
    $chart_doughnut_data = [];
    foreach ($actions_results as $row) {
        $chart_doughnut_labels[] = ucfirst($row['action_type']);
        $chart_doughnut_data[] = (int)$row['action_count'];
    }

    // C. Ranking: Most Active Pets (Top 5)
    $active_pets_stmt = $pdo->query("
        SELECT p.id, p.pet_name, p.pet_type, p.breed, p.image, COUNT(l.id) as care_count, MAX(l.created_at) as last_care_time
        FROM pet_care_logs l
        JOIN pets p ON l.pet_id = p.id
        GROUP BY l.pet_id
        ORDER BY care_count DESC
        LIMIT 5
    ");
    $active_pets = $active_pets_stmt->fetchAll();

    // D. Ranking: Most Active Caregivers (Top 5 Users)
    $active_users_stmt = $pdo->query("
        SELECT u.id, u.name, u.email, u.avatar, COUNT(l.id) as logs_count, SUM(l.coins_earned) as total_earned
        FROM pet_care_logs l
        JOIN users u ON l.user_id = u.id
        GROUP BY l.user_id
        ORDER BY logs_count DESC
        LIMIT 5
    ");
    $active_users = $active_users_stmt->fetchAll();

} catch (PDOException $e) {
    die("Database report queries failed: " . $e->getMessage());
}
?>

<div class="row align-items-center mb-4">
    <div class="col-md-8">
        <h2 class="fw-bold mb-1">Care Logs Analytics</h2>
        <p class="text-muted mb-0">System performance graphs, interaction activity tracking, and leaderboards.</p>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-4 mb-5">
    <!-- Line Chart: 7-Day Trend -->
    <div class="col-lg-8">
        <div class="admin-card h-100">
            <div class="card-header bg-white">
                <span class="d-flex align-items-center gap-2">
                    <i class="bi bi-graph-up text-primary"></i>
                    <span class="fw-bold text-slate-700">7-Day Care Activity Trend</span>
                </span>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="careTrendChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Doughnut Chart: Actions Breakdown -->
    <div class="col-lg-4">
        <div class="admin-card h-100">
            <div class="card-header bg-white">
                <span class="d-flex align-items-center gap-2">
                    <i class="bi bi-pie-chart text-accent"></i>
                    <span class="fw-bold text-slate-700">Interactions Share</span>
                </span>
            </div>
            <div class="card-body d-flex flex-column justify-content-center">
                <div class="chart-container" style="height: 250px;">
                    <canvas id="careActionShareChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Leaderboards Grid -->
<div class="row g-4">
    <!-- Most Active Pets List -->
    <div class="col-md-6">
        <div class="admin-card h-100">
            <div class="card-header bg-white">
                <span class="d-flex align-items-center gap-2">
                    <i class="bi bi-star-fill text-warning"></i>
                    <span class="fw-bold text-slate-700">Top 5 Most Interactive Pets</span>
                </span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($active_pets)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-patch-exclamation fs-2 d-block mb-2 text-slate-300"></i>
                        <span>No interactions recorded yet.</span>
                    </div>
                <?php else: ?>
                    <div class="table-responsive border-0">
                        <table class="table admin-table align-middle">
                            <thead>
                                <tr>
                                    <th>Pet</th>
                                    <th>Interactions</th>
                                    <th>Last Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $rank = 1; foreach ($active_pets as $pet): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <span class="badge bg-dark rounded-circle text-white d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 0.75rem;">
                                                    <?php echo $rank++; ?>
                                                </span>
                                                <?php 
                                                $pet_pic = !empty($pet['image']) ? BASE_URL . '/assets/images/' . $pet['image'] : BASE_URL . '/assets/images/default-pet.png';
                                                ?>
                                                <img src="<?php echo $pet_pic; ?>" alt="Pet" class="rounded" style="width: 36px; height: 36px; object-fit: cover;">
                                                <div>
                                                    <span class="fw-bold text-slate-800 d-block"><?php echo sanitize($pet['pet_name']); ?></span>
                                                    <span class="text-muted extra-small" style="font-size: 0.7rem;"><?php echo sanitize($pet['pet_type']); ?> &bull; <?php echo sanitize($pet['breed'] ?: 'Mixed'); ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 fw-bold">
                                                <?php echo $pet['care_count']; ?> logs
                                            </span>
                                        </td>
                                        <td class="small text-muted" style="font-size: 0.75rem;">
                                            <?php echo date('M d, h:i A', strtotime($pet['last_care_time'])); ?>
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
    
    <!-- Top Caregivers List -->
    <div class="col-md-6">
        <div class="admin-card h-100">
            <div class="card-header bg-white">
                <span class="d-flex align-items-center gap-2">
                    <i class="bi bi-award text-success fs-5"></i>
                    <span class="fw-bold text-slate-700">Top 5 Active Caregivers</span>
                </span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($active_users)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-person-exclamation fs-2 d-block mb-2 text-slate-300"></i>
                        <span>No logs by standard users yet.</span>
                    </div>
                <?php else: ?>
                    <div class="table-responsive border-0">
                        <table class="table admin-table align-middle">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Logs Filed</th>
                                    <th>Coins Earned</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $rank = 1; foreach ($active_users as $user): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <span class="badge bg-custom-dark rounded-circle text-accent d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 0.75rem;">
                                                    <?php echo $rank++; ?>
                                                </span>
                                                <?php if (!empty($user['avatar'])): ?>
                                                    <img src="<?php echo BASE_URL . '/' . $user['avatar']; ?>" alt="User" class="rounded-circle" style="width: 32px; height: 32px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="rounded-circle bg-secondary-subtle text-dark border fw-bold d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <span class="fw-bold text-slate-800 d-block"><?php echo sanitize($user['name']); ?></span>
                                                    <span class="text-muted extra-small" style="font-size: 0.7rem;"><?php echo sanitize($user['email']); ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fw-bold">
                                                <?php echo $user['logs_count']; ?> times
                                            </span>
                                        </td>
                                        <td>
                                            <span class="fw-semibold text-warning">
                                                <i class="fa-solid fa-coins me-1"></i>
                                                <?php echo number_format($user['total_earned']); ?>
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
</div>

<!-- Inline Javascript triggers to draw charts on page load -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    // 1. Line Chart Trend
    const lineLabels = <?php echo json_encode($chart_line_labels); ?>;
    const lineData = <?php echo json_encode($chart_line_data); ?>;
    initLineChart('careTrendChart', lineLabels, lineData, 'Actions Count', '#4f46e5');
    
    // 2. Doughnut Chart Breakdown
    const doughnutLabels = <?php echo json_encode($chart_doughnut_labels); ?>;
    const doughnutData = <?php echo json_encode($chart_doughnut_data); ?>;
    
    // Generate distinct premium background colors for action segments
    const segmentColors = [
        '#4f46e5', // feed (indigo)
        '#f43f5e', // play (rose)
        '#10b981', // groom (emerald)
        '#f59e0b', // sleep (amber)
        '#06b6d4', // water (cyan)
        '#8b5cf6', // medicine (violet)
        '#ec4899'  // training (pink)
    ];
    
    initDoughnutChart('careActionShareChart', doughnutLabels, doughnutData, segmentColors.slice(0, doughnutLabels.length));
});
</script>

<?php
require_once __DIR__ . '/includes/admin_footer.php';
?>
