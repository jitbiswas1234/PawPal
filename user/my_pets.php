<?php
/**
 * PawPal - My Pets List
 */
$page_title = "My Pets";
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/header.php';

$userId = $_SESSION['user_id'];

// Apply decay to all user's pets to ensure stats are updated before display
$petsRaw = getUserPets($pdo, $userId);
foreach ($petsRaw as $petRaw) {
    applyDecay($pdo, $petRaw['id']);
}

// Fetch updated pets list
$myPets = getUserPets($pdo, $userId);
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h1 class="fw-bold tracking-tight text-dark mb-1">My Adopted Pets</h1>
        <p class="text-muted">Keep an eye on their stats and interact with them to earn coins and level them up!</p>
    </div>
    <div class="col-md-4 text-md-end d-flex align-items-center justify-content-md-end">
        <a href="<?php echo BASE_URL; ?>/user/pets.php" class="btn btn-accent rounded-pill px-4">
            <i class="fa-solid fa-plus me-2"></i>Adopt a New Pet
        </a>
    </div>
</div>

<?php if (empty($myPets)): ?>
    <div class="card glass-card border-0 text-center p-5 my-5">
        <div class="card-body">
            <div class="mb-4">
                <i class="fa-solid fa-paw text-muted" style="font-size: 4rem; opacity: 0.3;"></i>
            </div>
            <h3 class="fw-bold">No pets adopted yet!</h3>
            <p class="text-muted mx-auto" style="max-width: 500px;">
                You haven't adopted any pets yet. Visit our shelter to find a loving companion to take care of!
            </p>
            <a href="<?php echo BASE_URL; ?>/user/pets.php" class="btn btn-primary rounded-pill px-4 py-2 mt-2">
                <i class="fa-solid fa-house-chimney me-2"></i>Visit Pet Shelter
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($myPets as $pet): ?>
            <?php 
            $moodInfo = getPetMood($pet['health'], $pet['hunger'], $pet['happiness'], $pet['energy'], $pet['cleanliness']);
            ?>
            <div class="col-xl-4 col-md-6">
                <div class="card pet-card glass-card border-0 h-100">
                    <div class="pet-card-img-wrapper">
                        <img src="<?php echo getPetImage($pet['image']); ?>" alt="<?php echo sanitize($pet['pet_name']); ?>" class="pet-card-img">
                        <span class="pet-card-badge bg-primary text-white">Lvl <?php echo $pet['level']; ?></span>
                    </div>
                    
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h4 class="card-title fw-bold mb-1 text-dark"><?php echo sanitize($pet['pet_name']); ?></h4>
                                <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2.5 py-1 text-xs">
                                    <?php echo sanitize($pet['breed'] ?: $pet['pet_type']); ?>
                                </span>
                            </div>
                            <span class="mood-badge" style="background-color: <?php echo $moodInfo['color']; ?>22; color: <?php echo $moodInfo['color']; ?>;">
                                <span><?php echo $moodInfo['emoji']; ?></span>
                                <span><?php echo $moodInfo['mood']; ?></span>
                            </span>
                        </div>
                        
                        <!-- Progress stats -->
                        <div class="mt-4">
                            <!-- Health -->
                            <div class="stat-bar-container">
                                <div class="stat-label-row">
                                    <span><i class="fa-solid fa-heart text-danger me-1"></i> Health</span>
                                    <span><?php echo $pet['health']; ?>/100</span>
                                </div>
                                <div class="custom-progress">
                                    <div class="custom-progress-bar" style="width: <?php echo $pet['health']; ?>%; background-color: <?php echo getStatColor($pet['health']); ?>"></div>
                                </div>
                            </div>
                            
                            <!-- Hunger -->
                            <div class="stat-bar-container">
                                <div class="stat-label-row">
                                    <span><i class="fa-solid fa-bowl-food text-warning me-1"></i> Hunger</span>
                                    <span><?php echo $pet['hunger']; ?>/100</span>
                                </div>
                                <div class="custom-progress">
                                    <div class="custom-progress-bar" style="width: <?php echo $pet['hunger']; ?>%; background-color: <?php echo getStatColor($pet['hunger'], true); ?>"></div>
                                </div>
                            </div>

                            <!-- Happiness -->
                            <div class="stat-bar-container">
                                <div class="stat-label-row">
                                    <span><i class="fa-solid fa-face-smile text-info me-1"></i> Happiness</span>
                                    <span><?php echo $pet['happiness']; ?>/100</span>
                                </div>
                                <div class="custom-progress">
                                    <div class="custom-progress-bar" style="width: <?php echo $pet['happiness']; ?>%; background-color: <?php echo getStatColor($pet['happiness']); ?>"></div>
                                </div>
                            </div>

                            <!-- Energy -->
                            <div class="stat-bar-container">
                                <div class="stat-label-row">
                                    <span><i class="fa-solid fa-bolt text-purple me-1"></i> Energy</span>
                                    <span><?php echo $pet['energy']; ?>/100</span>
                                </div>
                                <div class="custom-progress">
                                    <div class="custom-progress-bar" style="width: <?php echo $pet['energy']; ?>%; background-color: <?php echo getStatColor($pet['energy']); ?>"></div>
                                </div>
                            </div>

                            <!-- Cleanliness -->
                            <div class="stat-bar-container">
                                <div class="stat-label-row">
                                    <span><i class="fa-solid fa-soap text-success me-1"></i> Cleanliness</span>
                                    <span><?php echo $pet['cleanliness']; ?>/100</span>
                                </div>
                                <div class="custom-progress">
                                    <div class="custom-progress-bar" style="width: <?php echo $pet['cleanliness']; ?>%; background-color: <?php echo getStatColor($pet['cleanliness']); ?>"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-footer bg-transparent border-top-0 p-4 pt-0">
                        <a href="<?php echo BASE_URL; ?>/user/pet_care.php?id=<?php echo $pet['id']; ?>" class="btn btn-primary w-100 rounded-pill py-2.5 fw-bold d-flex align-items-center justify-content-center gap-2 shadow-sm">
                            <i class="fa-solid fa-hand-holding-heart"></i> Care & Interact
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
