<?php
/**
 * PawPal Admin - Manage Pets Directory
 */
$page_title = 'Pets Inventory';
require_once __DIR__ . '/includes/admin_header.php';

// Fetch distinct types for filter dropdown
try {
    $types_stmt = $pdo->query("SELECT DISTINCT pet_type FROM pets ORDER BY pet_type ASC");
    $pet_types = $types_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $pet_types = [];
}

// Build Query with Filters
$where = [];
$params = [];

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : '';

if ($search !== '') {
    $where[] = "(pet_name LIKE ? OR pet_type LIKE ? OR breed LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($status !== '' && in_array($status, ['available', 'adopted'])) {
    $where[] = "status = ?";
    $params[] = $status;
}

if ($type !== '') {
    $where[] = "pet_type = ?";
    $params[] = $type;
}

$sql = "SELECT * FROM pets";
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY id DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $pets = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database error fetching pets: " . $e->getMessage());
}
?>

<!-- Header Toolbar -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h2 class="fw-bold mb-1">Pets Directory</h2>
        <p class="text-muted mb-0">Total of <?php echo count($pets); ?> pets matching your criteria.</p>
    </div>
    <div>
        <a href="<?php echo BASE_URL; ?>/admin/add_pet.php" class="btn btn-primary d-flex align-items-center gap-2">
            <i class="bi bi-plus-lg"></i>
            <span>Add New Pet</span>
        </a>
    </div>
</div>

<!-- Filters Panel -->
<div class="admin-card mb-4">
    <div class="card-body py-3">
        <form method="GET" action="" class="row g-3 align-items-end">
            <!-- Search Text -->
            <div class="col-md-4">
                <label class="form-label small fw-semibold text-muted">Search Query</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Search by name, type, breed..." value="<?php echo sanitize($search); ?>">
                </div>
            </div>
            
            <!-- Type Filter -->
            <div class="col-md-2 col-sm-6">
                <label class="form-label small fw-semibold text-muted">Pet Type</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">All Types</option>
                    <?php foreach ($pet_types as $ptype): ?>
                        <option value="<?php echo sanitize($ptype); ?>" <?php echo $type === $ptype ? 'selected' : ''; ?>>
                            <?php echo sanitize($ptype); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Status Filter -->
            <div class="col-md-2 col-sm-6">
                <label class="form-label small fw-semibold text-muted">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="available" <?php echo $status === 'available' ? 'selected' : ''; ?>>Available</option>
                    <option value="adopted" <?php echo $status === 'adopted' ? 'selected' : ''; ?>>Adopted</option>
                </select>
            </div>
            
            <!-- Submit/Reset Buttons -->
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary px-3 flex-grow-1">
                    <i class="bi bi-funnel-fill me-1"></i> Filter
                </button>
                <a href="<?php echo BASE_URL; ?>/admin/pets.php" class="btn btn-sm btn-outline-secondary px-3 flex-grow-1">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Pets Data Table -->
<div class="admin-card">
    <div class="card-body p-0">
        <?php if (empty($pets)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-database-exclamation fs-1 d-block mb-2 text-slate-300"></i>
                <h5>No Pets Found</h5>
                <p class="mb-0">Try clearing filters or add a new pet using the button above.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table admin-table align-middle">
                    <thead>
                        <tr>
                            <th style="width: 70px;">Photo</th>
                            <th>Pet Name</th>
                            <th>Details</th>
                            <th>Stats Checklist</th>
                            <th>XP / Lvl</th>
                            <th>Status</th>
                            <th class="text-end" style="width: 140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pets as $pet): ?>
                            <tr>
                                <!-- Pet Photo -->
                                <td>
                                    <?php 
                                    $image_path = !empty($pet['image']) ? BASE_URL . '/assets/images/' . $pet['image'] : BASE_URL . '/assets/images/default-pet.png';
                                    ?>
                                    <img src="<?php echo $image_path; ?>" alt="<?php echo sanitize($pet['pet_name']); ?>" class="admin-pet-avatar">
                                </td>
                                
                                <!-- Pet Name -->
                                <td>
                                    <div class="fw-bold text-slate-800 fs-5"><?php echo sanitize($pet['pet_name']); ?></div>
                                    <span class="text-muted small"><?php echo sanitize($pet['gender']); ?></span>
                                </td>
                                
                                <!-- Details (Type, Breed, Age) -->
                                <td>
                                    <div class="small fw-semibold"><?php echo sanitize($pet['pet_type']); ?></div>
                                    <div class="text-muted extra-small" style="font-size: 0.75rem;"><?php echo sanitize($pet['breed'] ?: 'Mixed Breed'); ?> &bull; Age <?php echo $pet['age']; ?></div>
                                </td>
                                
                                <!-- Progress Stats (Health, Hunger, Happiness) -->
                                <td>
                                    <div class="d-flex flex-column gap-2" style="max-width: 200px;">
                                        <!-- Health -->
                                        <div class="admin-progress-stat">
                                            <div class="stat-label">
                                                <span>Health</span>
                                                <span><?php echo $pet['health']; ?>%</span>
                                            </div>
                                            <div class="progress">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $pet['health']; ?>%" aria-valuenow="<?php echo $pet['health']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                        
                                        <!-- Hunger -->
                                        <div class="admin-progress-stat">
                                            <div class="stat-label">
                                                <span>Hunger</span>
                                                <span><?php echo $pet['hunger']; ?>%</span>
                                            </div>
                                            <div class="progress">
                                                <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $pet['hunger']; ?>%" aria-valuenow="<?php echo $pet['hunger']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                        
                                        <!-- Happiness -->
                                        <div class="admin-progress-stat">
                                            <div class="stat-label">
                                                <span>Happiness</span>
                                                <span><?php echo $pet['happiness']; ?>%</span>
                                            </div>
                                            <div class="progress">
                                                <div class="progress-bar bg-accent" role="progressbar" style="width: <?php echo $pet['happiness']; ?>%" aria-valuenow="<?php echo $pet['happiness']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                
                                <!-- Level / XP -->
                                <td>
                                    <div class="fw-semibold text-slate-800">Lvl <?php echo $pet['level']; ?></div>
                                    <div class="text-muted extra-small" style="font-size: 0.7rem;"><?php echo $pet['xp']; ?> XP</div>
                                </td>
                                
                                <!-- Status Badge -->
                                <td>
                                    <span class="admin-badge status-<?php echo $pet['status']; ?>">
                                        <?php if ($pet['status'] === 'available'): ?>
                                            <i class="bi bi-patch-check"></i> Available
                                        <?php else: ?>
                                            <i class="bi bi-house-door-fill"></i> Adopted
                                        <?php endif; ?>
                                    </span>
                                </td>
                                
                                <!-- Actions -->
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="<?php echo BASE_URL; ?>/admin/edit_pet.php?id=<?php echo $pet['id']; ?>" class="btn btn-sm btn-outline-primary border-0" title="Edit Pet">
                                            <i class="bi bi-pencil-square fs-5"></i>
                                        </a>
                                        <a href="<?php echo BASE_URL; ?>/admin/delete_pet.php?id=<?php echo $pet['id']; ?>" class="btn btn-sm btn-outline-danger border-0" title="Delete Pet" onclick="return confirmDelete('Are you sure you want to delete <?php echo sanitize($pet['pet_name']); ?>? This will permanently remove the pet and all its adoption and care logs.');">
                                            <i class="bi bi-trash-fill fs-5"></i>
                                        </a>
                                    </div>
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
