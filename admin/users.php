<?php
/**
 * PawPal Admin - Manage User Accounts
 */
require_once __DIR__ . '/../includes/functions.php';

// Enforce admin permission
requireAdmin();

global $pdo;
$current_admin_id = $_SESSION['user_id'];

// Handle Role Toggle Action
if (isset($_GET['action']) && $_GET['action'] === 'toggle_role') {
    $user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($user_id <= 0) {
        setFlash('danger', 'Invalid user ID specified.');
    } elseif ($user_id === $current_admin_id) {
        setFlash('danger', 'Security Restriction: You cannot modify or demote your own administrator account.');
    } else {
        try {
            // Fetch user current role
            $stmt = $pdo->prepare("SELECT name, role FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if ($user) {
                $new_role = $user['role'] === 'admin' ? 'user' : 'admin';
                
                $update_stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                $update_stmt->execute([$new_role, $user_id]);
                
                setFlash('success', "Successfully changed role of '" . sanitize($user['name']) . "' to " . strtoupper($new_role) . ".");
            } else {
                setFlash('danger', 'User account not found.');
            }
        } catch (PDOException $e) {
            setFlash('danger', 'Database error: ' . $e->getMessage());
        }
    }
    redirect(BASE_URL . '/admin/users.php');
}

// Build Search and Filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role = isset($_GET['role']) ? trim($_GET['role']) : '';

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(name LIKE ? OR email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($role !== '' && in_array($role, ['user', 'admin'])) {
    $where[] = "role = ?";
    $params[] = $role;
}

$sql = "SELECT * FROM users";
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY id DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database query error: " . $e->getMessage());
}

$page_title = 'User Accounts';
require_once __DIR__ . '/includes/admin_header.php';
?>

<!-- Header Toolbar -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h2 class="fw-bold mb-1">User Directory</h2>
        <p class="text-muted mb-0">Manage registered user accounts, roles, and game balances.</p>
    </div>
</div>

<!-- Filters Panel -->
<div class="admin-card mb-4">
    <div class="card-body py-3">
        <form method="GET" action="" class="row g-3 align-items-end">
            <!-- Search Text -->
            <div class="col-md-5">
                <label class="form-label small fw-semibold text-muted">Search Name or Email</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="e.g. John Doe, user@domain.com" value="<?php echo sanitize($search); ?>">
                </div>
            </div>
            
            <!-- Role Filter -->
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted">Filter by Role</label>
                <select name="role" class="form-select form-select-sm">
                    <option value="">All Roles</option>
                    <option value="user" <?php echo $role === 'user' ? 'selected' : ''; ?>>Standard User</option>
                    <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Administrator</option>
                </select>
            </div>
            
            <!-- Submit/Reset Buttons -->
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary px-3 flex-grow-1">
                    <i class="bi bi-funnel-fill me-1"></i> Filter
                </button>
                <a href="<?php echo BASE_URL; ?>/admin/users.php" class="btn btn-sm btn-outline-secondary px-3 flex-grow-1">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Users Table -->
<div class="admin-card">
    <div class="card-body p-0">
        <?php if (empty($users)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-person-x fs-1 d-block mb-2 text-slate-300"></i>
                <h5>No Users Found</h5>
                <p class="mb-0">Try clearing filters or search for another query.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table admin-table align-middle">
                    <thead>
                        <tr>
                            <th style="width: 80px;">Avatar</th>
                            <th>Full Name</th>
                            <th>Email Address</th>
                            <th>System Role</th>
                            <th>Virtual Wealth</th>
                            <th>Care Points (XP)</th>
                            <th>Date Joined</th>
                            <th class="text-end" style="width: 160px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <!-- Avatar -->
                                <td>
                                    <?php if (!empty($user['avatar'])): ?>
                                        <img src="<?php echo BASE_URL . '/' . $user['avatar']; ?>" alt="Avatar" class="admin-avatar">
                                    <?php else: ?>
                                        <div class="admin-avatar bg-secondary-subtle text-dark fw-bold d-flex align-items-center justify-content-center border">
                                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                
                                <!-- Name -->
                                <td>
                                    <span class="fw-bold text-slate-800"><?php echo sanitize($user['name']); ?></span>
                                    <?php if ($user['id'] === $current_admin_id): ?>
                                        <span class="badge bg-custom-dark text-accent ms-1">You</span>
                                    <?php endif; ?>
                                </td>
                                
                                <!-- Email -->
                                <td class="small text-slate-600">
                                    <?php echo sanitize($user['email']); ?>
                                </td>
                                
                                <!-- Role -->
                                <td>
                                    <span class="badge <?php echo $user['role'] === 'admin' ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-primary-subtle text-primary border border-primary-subtle'; ?> rounded-pill px-3 py-1 font-semibold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                        <?php echo $user['role']; ?>
                                    </span>
                                </td>
                                
                                <!-- Coins -->
                                <td>
                                    <span class="fw-semibold text-slate-800">
                                        <i class="fa-solid fa-coins text-warning me-1"></i>
                                        <?php echo number_format($user['coins']); ?>
                                    </span>
                                </td>
                                
                                <!-- XP Points -->
                                <td>
                                    <span class="fw-semibold text-slate-800">
                                        <i class="fa-solid fa-star text-accent me-1"></i>
                                        <?php echo number_format($user['points']); ?> XP
                                    </span>
                                </td>
                                
                                <!-- Join Date -->
                                <td class="small text-muted">
                                    <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                </td>
                                
                                <!-- Action Buttons -->
                                <td class="text-end">
                                    <?php if ($user['id'] !== $current_admin_id): ?>
                                        <a href="<?php echo BASE_URL; ?>/admin/users.php?action=toggle_role&id=<?php echo $user['id']; ?>" 
                                           class="btn btn-sm <?php echo $user['role'] === 'admin' ? 'btn-outline-warning' : 'btn-outline-danger'; ?> rounded-pill px-3"
                                           onclick="return confirm('Are you sure you want to change the role of <?php echo sanitize($user['name']); ?> to <?php echo $user['role'] === 'admin' ? 'Standard User' : 'Administrator'; ?>?');">
                                            <i class="bi <?php echo $user['role'] === 'admin' ? 'bi-shield-slash' : 'bi-shield-check'; ?> me-1"></i>
                                            <?php echo $user['role'] === 'admin' ? 'Revoke Admin' : 'Make Admin'; ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">No modifications</span>
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
