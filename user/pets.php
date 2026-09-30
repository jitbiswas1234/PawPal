<?php
/**
 * PawPal - Available Pets Catalog
 */
$page_title = 'Browse Pets';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';

// Search & Filter Parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$gender = isset($_GET['gender']) ? trim($_GET['gender']) : '';
$age_group = isset($_GET['age_group']) ? trim($_GET['age_group']) : '';

// Build dynamic SQL query
$query = "SELECT * FROM pets WHERE status = 'available'";
$params = [];

if (!empty($search)) {
    $query .= " AND (pet_name LIKE ? OR breed LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($type)) {
    $query .= " AND pet_type = ?";
    $params[] = $type;
}

if (!empty($gender)) {
    $query .= " AND gender = ?";
    $params[] = $gender;
}

if (!empty($age_group)) {
    if ($age_group === 'baby') {
        $query .= " AND age <= 1";
    } elseif ($age_group === 'young') {
        $query .= " AND age = 2";
    } elseif ($age_group === 'adult') {
        $query .= " AND age >= 3";
    }
}

$query .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$pets = $stmt->fetchAll();

// Get unique types for filtering
$types = getPetTypes($pdo);

// Get user pending requests if logged in
$pendingAdoptions = [];
if (isLoggedIn()) {
    $userId = $_SESSION['user_id'];
    $pendingStmt = $pdo->prepare("SELECT pet_id FROM adoptions WHERE user_id = ? AND adoption_status = 'pending'");
    $pendingStmt->execute([$userId]);
    $pendingAdoptions = $pendingStmt->fetchAll(PDO::FETCH_COLUMN);
}
?>

<div class="mb-5 text-center">
    <h1 class="fw-extrabold mb-2 display-5 text-dark">Meet Our <span class="text-accent">Pets</span></h1>
    <p class="text-muted col-md-8 mx-auto lead fs-6">Find your perfect companion. Browse through our available pets, filter by type or breed, and send an adoption request today!</p>
</div>

<!-- Filters Sidebar & Search Section -->
<div class="row g-4 mb-5">
    <div class="col-lg-3">
        <!-- Filters panel -->
        <div class="glass-card p-4">
            <h5 class="fw-bold mb-4 d-flex align-items-center justify-content-between">
                <span><i class="fa-solid fa-sliders text-accent me-2"></i>Filters</span>
                <a href="<?php echo BASE_URL; ?>/user/pets.php" class="text-muted extra-small text-decoration-none">Clear All</a>
            </h5>
            
            <form action="<?php echo BASE_URL; ?>/user/pets.php" method="GET">
                <!-- Search -->
                <div class="mb-4">
                    <label for="search" class="form-label small fw-bold text-muted text-uppercase">Search</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" class="form-control border-start-0 bg-light" id="search" name="search" placeholder="Name, breed..." value="<?php echo sanitize($search); ?>">
                    </div>
                </div>

                <!-- Pet Type -->
                <div class="mb-4">
                    <label for="type" class="form-label small fw-bold text-muted text-uppercase">Pet Type</label>
                    <select class="form-select bg-light" id="type" name="type">
                        <option value="">All Types</option>
                        <?php foreach ($types as $t): ?>
                            <option value="<?php echo sanitize($t); ?>" <?php echo $type === $t ? 'selected' : ''; ?>>
                                <?php echo sanitize($t); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Gender -->
                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted text-uppercase d-block">Gender</label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="gender" id="gender_all" value="" <?php echo empty($gender) ? 'checked' : ''; ?>>
                        <label class="form-check-label small" for="gender_all">All</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="gender" id="gender_male" value="Male" <?php echo $gender === 'Male' ? 'checked' : ''; ?>>
                        <label class="form-check-label small" for="gender_male">Male</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="gender" id="gender_female" value="Female" <?php echo $gender === 'Female' ? 'checked' : ''; ?>>
                        <label class="form-check-label small" for="gender_female">Female</label>
                    </div>
                </div>

                <!-- Age Group -->
                <div class="mb-4">
                    <label for="age_group" class="form-label small fw-bold text-muted text-uppercase">Age Group</label>
                    <select class="form-select bg-light" id="age_group" name="age_group">
                        <option value="">Any Age</option>
                        <option value="baby" <?php echo $age_group === 'baby' ? 'selected' : ''; ?>>Baby (0-1 year)</option>
                        <option value="young" <?php echo $age_group === 'young' ? 'selected' : ''; ?>>Young (2 years)</option>
                        <option value="adult" <?php echo $age_group === 'adult' ? 'selected' : ''; ?>>Adult (3+ years)</option>
                    </select>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-accent py-2 rounded-pill shadow-sm">
                        Apply Filters <i class="fa-solid fa-chevron-right ms-1 small"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Pets Grid Display -->
    <div class="col-lg-9">
        <?php if (empty($pets)): ?>
            <div class="glass-card p-5 text-center bg-white">
                <div class="bg-light rounded-circle p-4 d-inline-flex mb-3">
                    <i class="fa-solid fa-circle-question fs-1 text-muted"></i>
                </div>
                <h4 class="fw-bold">No Pets Match Your Search</h4>
                <p class="text-muted mb-4 col-md-8 mx-auto">We couldn't find any available pets with your specified filter criteria. Try adjusting your filters or clearing search filters to see more results.</p>
                <a href="<?php echo BASE_URL; ?>/user/pets.php" class="btn btn-accent px-4 py-2 rounded-pill">Reset Filters</a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($pets as $pet): 
                    $isPending = in_array($pet['id'], $pendingAdoptions);
                    $moodData = getPetMood($pet['health'], $pet['hunger'], $pet['happiness'], $pet['energy'], $pet['cleanliness']);
                ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="pet-card glass-card h-100 d-flex flex-column bg-white">
                            <div class="pet-card-img-wrapper position-relative">
                                <!-- Mood/Status Badge -->
                                <span class="pet-card-badge" style="background-color: <?php echo $moodData['color']; ?>;">
                                    <?php echo $moodData['emoji'] . ' ' . $pet['mood']; ?>
                                </span>
                                
                                <img src="<?php echo getPetImage($pet['image']); ?>" class="pet-card-img" alt="<?php echo sanitize($pet['pet_name']); ?>">
                            </div>
                            
                            <div class="p-4 d-flex flex-column flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h5 class="fw-bold mb-0 text-dark"><?php echo sanitize($pet['pet_name']); ?></h5>
                                        <span class="text-muted extra-small">
                                            <i class="fa-solid fa-tag me-1"></i><?php echo sanitize($pet['breed'] ?: $pet['pet_type']); ?>
                                        </span>
                                    </div>
                                    <span class="badge bg-light text-dark border extra-small px-2 py-1">
                                        <?php echo sanitize($pet['pet_type']); ?>
                                    </span>
                                </div>
                                
                                <div class="d-flex gap-3 mb-3 text-muted extra-small fw-semibold border-top border-bottom py-2">
                                    <span>
                                        <i class="fa-solid <?php echo $pet['gender'] === 'Male' ? 'fa-mars text-primary' : 'fa-venus text-accent'; ?> me-1"></i>
                                        <?php echo $pet['gender']; ?>
                                    </span>
                                    <span>
                                        <i class="fa-regular fa-calendar-alt text-muted me-1"></i>
                                        <?php echo $pet['age']; ?> Yr<?php echo $pet['age'] > 1 ? 's' : ''; ?>
                                    </span>
                                    <span>
                                        <i class="fa-solid fa-gauge-high text-muted me-1"></i>
                                        Lvl <?php echo $pet['level']; ?>
                                    </span>
                                </div>

                                <p class="text-muted small mb-4 flex-grow-1">
                                    <?php 
                                    $desc = sanitize($pet['description']);
                                    echo strlen($desc) > 110 ? substr($desc, 0, 105) . '...' : $desc; 
                                    ?>
                                </p>

                                <div class="d-grid mt-auto">
                                    <?php if ($isPending): ?>
                                        <a href="<?php echo BASE_URL; ?>/user/pet_details.php?id=<?php echo $pet['id']; ?>" class="btn btn-outline-warning py-2 rounded-pill">
                                            <i class="fa-solid fa-clock-rotate-left me-2"></i>Adoption Pending
                                        </a>
                                    <?php else: ?>
                                        <a href="<?php echo BASE_URL; ?>/user/pet_details.php?id=<?php echo $pet['id']; ?>" class="btn btn-accent py-2 rounded-pill shadow-sm">
                                            Meet <?php echo sanitize($pet['pet_name']); ?> <i class="fa-solid fa-arrow-right ms-1"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 
require_once __DIR__ . '/../includes/footer.php';
?>
