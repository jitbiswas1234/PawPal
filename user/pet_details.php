<?php
/**
 * PawPal - Pet Details & Adoption Request Page
 */
$page_title = 'Pet Details';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';

// Validate pet ID
$petId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$pet = getPetById($pdo, $petId);

if (!$pet) {
    setFlash('danger', 'Pet not found.');
    redirect(BASE_URL . '/user/pets.php');
}

// Apply decay dynamically on page view if adopted
if ($pet['status'] === 'adopted') {
    applyDecay($pdo, $pet['id']);
    // Reload pet to get updated stats
    $pet = getPetById($pdo, $pet['id']);
}

$moodData = getPetMood($pet['health'], $pet['hunger'], $pet['happiness'], $pet['energy'], $pet['cleanliness']);
$xpNext = getXPForNextLevel($pet['level']);

// Adoption Request Status checking for logged-in user
$adoptionStatus = null;
$adoptionRecord = null;
$isAlreadyOwner = false;

if (isLoggedIn()) {
    $userId = $_SESSION['user_id'];
    
    // Check if current user is owner
    $isAlreadyOwner = isOwnPet($pdo, $userId, $petId);
    
    // Check general request status
    $stmt = $pdo->prepare("SELECT * FROM adoptions WHERE user_id = ? AND pet_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$userId, $petId]);
    $adoptionRecord = $stmt->fetch();
    if ($adoptionRecord) {
        $adoptionStatus = $adoptionRecord['adoption_status'];
    }
}

// Check who adopted the pet if its status is adopted
$currentOwner = null;
if ($pet['status'] === 'adopted') {
    $ownerStmt = $pdo->prepare("
        SELECT u.name, u.id 
        FROM users u 
        INNER JOIN adoptions a ON u.id = a.user_id 
        WHERE a.pet_id = ? AND a.adoption_status = 'approved'
    ");
    $ownerStmt->execute([$petId]);
    $currentOwner = $ownerStmt->fetch();
}

$csrfToken = generateCSRF();
?>

<div class="mb-4">
    <a href="<?php echo BASE_URL; ?>/user/pets.php" class="text-muted text-decoration-none small fw-semibold">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Browse Catalog
    </a>
</div>

<div class="row g-4">
    <!-- Left Column: Pet Image & Mood Badge -->
    <div class="col-lg-6">
        <div class="glass-card p-2 mb-4 bg-white">
            <div class="position-relative rounded-3 overflow-hidden bg-light" style="height: 450px;">
                <img src="<?php echo getPetImage($pet['image']); ?>" class="w-100 h-100 object-fit-cover" alt="<?php echo sanitize($pet['pet_name']); ?>">
                <span class="position-absolute top-3 end-3 badge px-3 py-2 fs-6 shadow-sm rounded-pill text-uppercase" style="top: 15px; right: 15px; z-index: 5; background-color: <?php echo $moodData['color']; ?>;">
                    <?php echo $moodData['emoji'] . ' ' . $pet['mood']; ?>
                </span>
                
                <?php if ($pet['status'] === 'adopted'): ?>
                    <span class="position-absolute bg-dark text-white px-4 py-2 fs-6 shadow rounded-pill text-uppercase" style="top: 15px; left: 15px; z-index: 5;">
                        Adopted 🎉
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Characteristics / Quick Facts -->
        <div class="glass-card p-4 mb-4 bg-white">
            <h5 class="fw-bold mb-3 border-bottom pb-2">Quick Facts</h5>
            <div class="row g-3">
                <div class="col-sm-6">
                    <div class="p-3 bg-light rounded-3 d-flex align-items-center">
                        <span class="fs-4 text-primary me-3"><i class="fa-solid fa-paw"></i></span>
                        <div>
                            <span class="text-muted extra-small d-block text-uppercase">Type</span>
                            <strong class="text-dark"><?php echo sanitize($pet['pet_type']); ?></strong>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="p-3 bg-light rounded-3 d-flex align-items-center">
                        <span class="fs-4 text-accent me-3"><i class="fa-solid fa-dna"></i></span>
                        <div>
                            <span class="text-muted extra-small d-block text-uppercase">Breed</span>
                            <strong class="text-dark"><?php echo sanitize($pet['breed'] ?: 'Mixed Breed'); ?></strong>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="p-3 bg-light rounded-3 d-flex align-items-center">
                        <span class="fs-4 text-warning me-3"><i class="fa-solid <?php echo $pet['gender'] === 'Male' ? 'fa-mars text-primary' : 'fa-venus text-accent'; ?>"></i></span>
                        <div>
                            <span class="text-muted extra-small d-block text-uppercase">Gender</span>
                            <strong class="text-dark"><?php echo $pet['gender']; ?></strong>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="p-3 bg-light rounded-3 d-flex align-items-center">
                        <span class="fs-4 text-success me-3"><i class="fa-regular fa-calendar-alt"></i></span>
                        <div>
                            <span class="text-muted extra-small d-block text-uppercase">Age</span>
                            <strong class="text-dark"><?php echo $pet['age']; ?> Year<?php echo $pet['age'] > 1 ? 's' : ''; ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Stats, Description & Adoption Actions -->
    <div class="col-lg-6">
        <div class="glass-card p-4 mb-4 bg-white">
            <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-3">
                <div>
                    <h2 class="fw-extrabold text-dark mb-0"><?php echo sanitize($pet['pet_name']); ?></h2>
                    <p class="text-muted small mb-0">Level <?php echo $pet['level']; ?> &bull; XP: <?php echo $pet['xp']; ?> / <?php echo $xpNext; ?></p>
                </div>
                <div class="xp-progress-circle" style="--percent: <?php echo min(100, round(($pet['xp'] / $xpNext) * 100)); ?>;">
                    <span class="xp-progress-text">Lvl <?php echo $pet['level']; ?></span>
                </div>
            </div>

            <!-- Health Status Tracker Bars -->
            <h5 class="fw-bold mb-3">Pet Vitality Status</h5>
            
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <!-- Health -->
                    <div class="stat-bar-container mb-2">
                        <div class="stat-label-row">
                            <span><i class="fa-solid fa-heartbeat text-danger me-1"></i> Health</span>
                            <span class="fw-bold"><?php echo $pet['health']; ?>%</span>
                        </div>
                        <div class="custom-progress">
                            <div class="custom-progress-bar" style="width: <?php echo $pet['health']; ?>%; background-color: <?php echo getStatColor($pet['health']); ?>;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <!-- Hunger -->
                    <div class="stat-bar-container mb-2">
                        <div class="stat-label-row">
                            <span><i class="fa-solid fa-utensils text-warning me-1"></i> Hunger</span>
                            <span class="fw-bold"><?php echo $pet['hunger']; ?>%</span>
                        </div>
                        <div class="custom-progress">
                            <div class="custom-progress-bar" style="width: <?php echo $pet['hunger']; ?>%; background-color: <?php echo getStatColor($pet['hunger'], true); ?>;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <!-- Happiness -->
                    <div class="stat-bar-container mb-2">
                        <div class="stat-label-row">
                            <span><i class="fa-solid fa-face-smile text-success me-1"></i> Happiness</span>
                            <span class="fw-bold"><?php echo $pet['happiness']; ?>%</span>
                        </div>
                        <div class="custom-progress">
                            <div class="custom-progress-bar" style="width: <?php echo $pet['happiness']; ?>%; background-color: <?php echo getStatColor($pet['happiness']); ?>;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <!-- Energy -->
                    <div class="stat-bar-container mb-2">
                        <div class="stat-label-row">
                            <span><i class="fa-solid fa-bolt text-info me-1"></i> Energy</span>
                            <span class="fw-bold"><?php echo $pet['energy']; ?>%</span>
                        </div>
                        <div class="custom-progress">
                            <div class="custom-progress-bar" style="width: <?php echo $pet['energy']; ?>%; background-color: <?php echo getStatColor($pet['energy']); ?>;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <!-- Cleanliness -->
                    <div class="stat-bar-container mb-2">
                        <div class="stat-label-row">
                            <span><i class="fa-solid fa-soap text-primary me-1"></i> Cleanliness</span>
                            <span class="fw-bold"><?php echo $pet['cleanliness']; ?>%</span>
                        </div>
                        <div class="custom-progress">
                            <div class="custom-progress-bar" style="width: <?php echo $pet['cleanliness']; ?>%; background-color: <?php echo getStatColor($pet['cleanliness']); ?>;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Description -->
            <h5 class="fw-bold mb-2">About <?php echo sanitize($pet['pet_name']); ?></h5>
            <p class="text-muted small text-justify leading-relaxed mb-0">
                <?php echo nl2br(sanitize($pet['description'])); ?>
            </p>
        </div>

        <!-- Adoption Request Section -->
        <div class="glass-card p-4 bg-white border-top border-5 border-primary">
            <h5 class="fw-bold text-dark mb-3">Adoption Information</h5>
            
            <?php if ($pet['status'] === 'adopted'): ?>
                <!-- Already Adopted Case -->
                <?php if ($isAlreadyOwner): ?>
                    <div class="alert alert-success border-0 rounded-3 mb-0">
                        <h6 class="fw-bold mb-1"><i class="fa-solid fa-circle-check me-2"></i>You adopted <?php echo sanitize($pet['pet_name']); ?>!</h6>
                        <p class="small mb-3">You are responsible for this pet. Head over to the Care screen to feed, groom, play, and interact with them!</p>
                        <a href="<?php echo BASE_URL; ?>/user/my_pets.php?pet_id=<?php echo $pet['id']; ?>" class="btn btn-success btn-sm w-100 rounded-pill py-2 shadow-sm">
                            <i class="fa-solid fa-heart-pulse me-2"></i>Go to Care Screen
                        </a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-secondary border-0 rounded-3 mb-0">
                        <h6 class="fw-bold mb-1"><i class="fa-solid fa-lock me-2"></i>Adopted</h6>
                        <p class="small mb-0">
                            <?php if ($currentOwner): ?>
                                This pet was adopted by <strong><?php echo sanitize($currentOwner['name']); ?></strong>. They are currently providing excellent care.
                            <?php else: ?>
                                This pet has already been adopted by another user.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <!-- Available for Adoption Case -->
                
                <?php if (!isLoggedIn()): ?>
                    <!-- Guest User Case -->
                    <div class="text-center p-3 bg-light rounded-3">
                        <i class="fa-solid fa-circle-info text-primary fs-3 mb-2"></i>
                        <p class="small text-muted mb-3">You must be logged in to request the adoption of this virtual pet.</p>
                        <div class="d-flex justify-content-center gap-2">
                            <a href="<?php echo BASE_URL; ?>/login.php" class="btn btn-outline-primary btn-sm px-4 rounded-pill">Log In</a>
                            <a href="<?php echo BASE_URL; ?>/register.php" class="btn btn-accent btn-sm px-4 rounded-pill">Register</a>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- Logged In User Case -->
                    
                    <?php if ($adoptionStatus === 'pending'): ?>
                        <!-- Pending Adoption Request -->
                        <div class="alert alert-warning border-0 rounded-3 mb-0">
                            <h6 class="fw-bold mb-1"><i class="fa-solid fa-hourglass-half me-2"></i>Request Pending</h6>
                            <p class="small mb-0">You submitted an adoption request for <?php echo sanitize($pet['pet_name']); ?> on <strong><?php echo date('M d, Y', strtotime($adoptionRecord['created_at'])); ?></strong>.</p>
                            <p class="small mt-2 mb-0">Please wait for an administrator to review and approve your request in the admin dashboard.</p>
                        </div>
                    
                    <?php elseif ($adoptionStatus === 'approved'): ?>
                        <!-- User's request is approved, but status in pets is somehow available? (unlikely fallback) -->
                        <div class="alert alert-success border-0 rounded-3 mb-0">
                            <h6 class="fw-bold mb-1"><i class="fa-solid fa-circle-check me-2"></i>Request Approved!</h6>
                            <p class="small mb-3">Your adoption request has been approved! You can now access your pet's care control dashboard.</p>
                            <a href="<?php echo BASE_URL; ?>/user/my_pets.php?pet_id=<?php echo $pet['id']; ?>" class="btn btn-success btn-sm w-100 rounded-pill py-2">Go to My Pets</a>
                        </div>

                    <?php else: ?>
                        <!-- No active request or rejected request (can request again) -->
                        <?php if ($adoptionStatus === 'rejected'): ?>
                            <div class="alert alert-danger-subtle text-danger border-0 rounded-3 p-3 mb-3 small">
                                <i class="fa-solid fa-triangle-exclamation me-2"></i>Your previous adoption request for this pet was declined. However, you can write a new request explaining why you are a good fit.
                            </div>
                        <?php endif; ?>

                        <form action="<?php echo BASE_URL; ?>/user/adopt_pet.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            <input type="hidden" name="pet_id" value="<?php echo $pet['id']; ?>">
                            
                            <div class="mb-3">
                                <label for="message" class="form-label small fw-bold text-muted text-uppercase">Adoption Cover Message</label>
                                <textarea class="form-control bg-light" id="message" name="message" rows="4" placeholder="Explain why you want to adopt <?php echo sanitize($pet['pet_name']); ?>. How will you ensure they stay healthy and happy?" required></textarea>
                                <div class="form-text extra-small text-muted">A compelling message increases your chances of getting approved by our sanctuary administrators.</div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-accent py-2 rounded-pill shadow-sm">
                                    <i class="fa-solid fa-paper-plane me-2"></i>Submit Adoption Request
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>

                <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
</div>

<?php 
require_once __DIR__ . '/../includes/footer.php';
?>
