<?php
/**
 * PawPal Admin - Manage Adoption Applications
 */
require_once __DIR__ . '/../includes/functions.php';

// Enforce admin permission
requireAdmin();

global $pdo;

// AJAX Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    
    $adoption_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $action = isset($_POST['status_action']) ? trim($_POST['status_action']) : '';
    
    if ($adoption_id <= 0 || !in_array($action, ['approve', 'reject'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters provided.']);
        exit;
    }

    try {
        // Fetch current application details
        $stmt = $pdo->prepare("SELECT * FROM adoptions WHERE id = ?");
        $stmt->execute([$adoption_id]);
        $adoption = $stmt->fetch();

        if (!$adoption) {
            echo json_encode(['success' => false, 'message' => 'Adoption application not found.']);
            exit;
        }

        if ($adoption['adoption_status'] !== 'pending') {
            echo json_encode(['success' => false, 'message' => 'This application is already finalized.']);
            exit;
        }

        $user_id = $adoption['user_id'];
        $pet_id = $adoption['pet_id'];

        // Get pet name
        $pet_stmt = $pdo->prepare("SELECT pet_name, status FROM pets WHERE id = ?");
        $pet_stmt->execute([$pet_id]);
        $pet = $pet_stmt->fetch();
        $pet_name = $pet ? sanitize($pet['pet_name']) : 'Virtual Pet';

        if ($action === 'approve') {
            // Check if pet is still available
            if ($pet && $pet['status'] === 'adopted') {
                echo json_encode(['success' => false, 'message' => 'This pet has already been adopted by another user.']);
                exit;
            }

            // Start transaction
            $pdo->beginTransaction();

            // 1. Approve current request
            $update_stmt = $pdo->prepare("UPDATE adoptions SET adoption_status = 'approved', adopted_at = CURRENT_TIMESTAMP WHERE id = ?");
            $update_stmt->execute([$adoption_id]);

            // 2. Set pet status to 'adopted'
            $pet_update = $pdo->prepare("UPDATE pets SET status = 'adopted' WHERE id = ?");
            $pet_update->execute([$pet_id]);

            // 3. Reject all other pending adoptions for the SAME pet
            $reject_others = $pdo->prepare("
                UPDATE adoptions 
                SET adoption_status = 'rejected', message = CONCAT(IFNULL(message,''), '\n\n[System: Automatically rejected because this pet has been adopted by someone else.]') 
                WHERE pet_id = ? AND adoption_status = 'pending' AND id != ?
            ");
            $reject_others->execute([$pet_id, $adoption_id]);

            // 4. Send notification to the approved user
            $notif_stmt = $pdo->prepare("
                INSERT INTO pet_notifications (user_id, pet_id, message, type) 
                VALUES (?, ?, ?, 'success')
            ");
            $notif_stmt->execute([
                $user_id, 
                $pet_id, 
                "Congratulations! Your application to adopt $pet_name has been approved! Visit your dashboard to care for your new pet."
            ]);

            // 5. Send notifications to other applicants who got auto-rejected
            // Find other applicants
            $find_others = $pdo->prepare("SELECT user_id FROM adoptions WHERE pet_id = ? AND id != ? AND adoption_status = 'rejected' AND message LIKE '%[System: Automatically rejected%'");
            $find_others->execute([$pet_id, $adoption_id]);
            $other_users = $find_others->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($other_users)) {
                $other_notif = $pdo->prepare("
                    INSERT INTO pet_notifications (user_id, pet_id, message, type) 
                    VALUES (?, ?, ?, 'warning')
                ");
                foreach ($other_users as $other_uid) {
                    $other_notif->execute([
                        $other_uid,
                        $pet_id,
                        "Your request to adopt $pet_name has been closed because another application was approved."
                    ]);
                }
            }

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => "Application approved successfully. $pet_name is now adopted."]);
            exit;

        } else {
            // Reject Request
            $pdo->beginTransaction();

            // 1. Update request status to rejected
            $update_stmt = $pdo->prepare("UPDATE adoptions SET adoption_status = 'rejected' WHERE id = ?");
            $update_stmt->execute([$adoption_id]);

            // 2. Notify the applicant
            $notif_stmt = $pdo->prepare("
                INSERT INTO pet_notifications (user_id, pet_id, message, type) 
                VALUES (?, ?, ?, 'danger')
            ");
            $notif_stmt->execute([
                $user_id, 
                $pet_id, 
                "Thank you for your interest in $pet_name. Unfortunately, your adoption request was not approved at this time."
            ]);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Application rejected successfully.']);
            exit;
        }

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['success' => false, 'message' => 'Database failure: ' . $e->getMessage()]);
        exit;
    }
}

// Fetch requests with filters
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$where = [];
$params = [];

if ($status_filter !== '' && in_array($status_filter, ['pending', 'approved', 'rejected'])) {
    $where[] = "a.adoption_status = ?";
    $params[] = $status_filter;
}

$sql = "
    SELECT a.id, a.message, a.adoption_status, a.created_at, a.adopted_at,
           u.name AS user_name, u.email AS user_email,
           p.pet_name, p.pet_type, p.breed, p.image AS pet_image, p.status AS pet_current_status
    FROM adoptions a
    JOIN users u ON a.user_id = u.id
    JOIN pets p ON a.pet_id = p.id
";

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY a.created_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $requests = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error fetching adoption applications: " . $e->getMessage());
}

$page_title = 'Adoption Applications';
require_once __DIR__ . '/includes/admin_header.php';
?>

<!-- Header Toolbar -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h2 class="fw-bold mb-1">Adoption Requests</h2>
        <p class="text-muted mb-0">Review pending requests and match pets with loving owners.</p>
    </div>
</div>

<!-- Filters Panel -->
<div class="admin-card mb-4">
    <div class="card-body py-3">
        <form method="GET" action="" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold text-muted">Filter by Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Applications</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending Review</option>
                    <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved Applications</option>
                    <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected Applications</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-funnel-fill me-1"></i> Apply Filter</button>
            </div>
            <div class="col-md-3">
                <a href="<?php echo BASE_URL; ?>/admin/adoption_requests.php" class="btn btn-sm btn-outline-secondary w-100"><i class="bi bi-arrow-counterclockwise me-1"></i> Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Requests Table -->
<div class="admin-card">
    <div class="card-body p-0">
        <?php if (empty($requests)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-file-earmark-x fs-1 d-block mb-2 text-slate-300"></i>
                <h5>No Applications Found</h5>
                <p class="mb-0">There are no adoption requests matching your filter.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table admin-table align-middle">
                    <thead>
                        <tr>
                            <th>Applicant</th>
                            <th>Pet Info</th>
                            <th>Cover Note / Message</th>
                            <th>Date Filed</th>
                            <th>Status</th>
                            <th class="text-center" style="width: 220px;">Decision Panel</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                            <tr data-request-id="<?php echo $req['id']; ?>">
                                <!-- Applicant Info -->
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="fw-bold text-slate-800"><?php echo sanitize($req['user_name']); ?></span>
                                        <span class="text-muted extra-small" style="font-size: 0.75rem;"><i class="bi bi-envelope-at me-1"></i><?php echo sanitize($req['user_email']); ?></span>
                                    </div>
                                </td>
                                
                                <!-- Pet Info -->
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <?php 
                                        $pet_img = !empty($req['pet_image']) ? BASE_URL . '/assets/images/' . $req['pet_image'] : BASE_URL . '/assets/images/default-pet.png';
                                        ?>
                                        <img src="<?php echo $pet_img; ?>" alt="Pet" class="rounded" style="width: 40px; height: 40px; object-fit: cover;">
                                        <div>
                                            <span class="fw-semibold text-slate-800"><?php echo sanitize($req['pet_name']); ?></span>
                                            <div class="text-muted extra-small" style="font-size: 0.7rem;">
                                                <?php echo sanitize($req['pet_type']); ?> &bull; <?php echo sanitize($req['breed'] ?: 'Mixed'); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                
                                <!-- Cover Note message -->
                                <td>
                                    <div class="text-muted small border-start ps-2 py-1 text-wrap" style="max-width: 320px; font-style: italic;">
                                        "<?php echo nl2br(sanitize($req['message'] ?: 'No message provided.')); ?>"
                                    </div>
                                </td>
                                
                                <!-- Request Date -->
                                <td class="small text-slate-500">
                                    <?php echo date('M d, Y h:i A', strtotime($req['created_at'])); ?>
                                </td>
                                
                                <!-- Status Badge -->
                                <td>
                                    <span class="admin-badge status-<?php echo $req['adoption_status']; ?> adoption-status-badge">
                                        <?php if ($req['adoption_status'] === 'pending'): ?>
                                            <i class="bi bi-clock-history"></i> Pending
                                        <?php elseif ($req['adoption_status'] === 'approved'): ?>
                                            <i class="bi bi-check-circle-fill"></i> Approved
                                        <?php else: ?>
                                            <i class="bi bi-x-circle-fill"></i> Rejected
                                        <?php endif; ?>
                                    </span>
                                </td>
                                
                                <!-- Action Cell -->
                                <td class="text-center action-buttons-cell">
                                    <?php if ($req['adoption_status'] === 'pending'): ?>
                                        <?php if ($req['pet_current_status'] === 'adopted'): ?>
                                            <span class="text-muted extra-small" style="font-size: 0.75rem;">Pet adopted by other owner</span>
                                        <?php else: ?>
                                            <div class="d-flex justify-content-center gap-2">
                                                <button class="btn btn-sm btn-success btn-action-adoption rounded-pill px-3 d-flex align-items-center gap-1" data-id="<?php echo $req['id']; ?>" data-action="approve">
                                                    <i class="bi bi-check-lg"></i> Approve
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger btn-action-adoption rounded-pill px-3 d-flex align-items-center gap-1" data-id="<?php echo $req['id']; ?>" data-action="reject">
                                                    <i class="bi bi-x-lg"></i> Reject
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    <?php elseif ($req['adoption_status'] === 'approved'): ?>
                                        <span class="text-success small fw-semibold"><i class="bi bi-check2"></i> Approved</span>
                                        <?php if ($req['adopted_at']): ?>
                                            <div class="text-muted extra-small" style="font-size: 0.65rem;"><?php echo date('M d, Y', strtotime($req['adopted_at'])); ?></div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-danger small fw-semibold"><i class="bi bi-dash-circle"></i> Rejected</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/admin_footer.php';
?>
