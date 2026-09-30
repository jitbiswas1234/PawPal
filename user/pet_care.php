<?php
/**
 * PawPal - Pet Care Simulator Gameplay
 */
$page_title = "Pet Care Careroom";
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/header.php';

$userId = $_SESSION['user_id'];
$petId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$petId || !isOwnPet($pdo, $userId, $petId)) {
    setFlash('danger', 'Pet not found or you do not own this pet.');
    redirect(BASE_URL . '/user/my_pets.php');
}

// Apply decay before loading stats
applyDecay($pdo, $petId);

// Get pet details
$pet = getPetById($pdo, $petId);
if (!$pet) {
    setFlash('danger', 'Pet not found.');
    redirect(BASE_URL . '/user/my_pets.php');
}

// Calculate level and XP progress
$currentXP = $pet['xp'];
$currentLevel = $pet['level'];
$prevThreshold = $currentLevel > 1 ? getXPForNextLevel($currentLevel - 1) : 0;
$nextThreshold = getXPForNextLevel($currentLevel);
$xpRange = $nextThreshold - $prevThreshold;
$xpProgress = $currentXP - $prevThreshold;
$xpPercent = ($xpRange > 0) ? max(0, min(100, ($xpProgress / $xpRange) * 100)) : 100;

// Get user's inventory
$stmt = $pdo->prepare("
    SELECT upi.id as inventory_id, upi.quantity, pi.* 
    FROM user_pet_items upi
    INNER JOIN pet_items pi ON upi.item_id = pi.id
    WHERE upi.user_id = ? AND upi.quantity > 0
    ORDER BY pi.item_name ASC
");
$stmt->execute([$userId]);
$inventory = $stmt->fetchAll();

// Group inventory by type
$foodItems = [];
$toyItems = [];
$medicineItems = [];
foreach ($inventory as $item) {
    if ($item['item_type'] === 'food') $foodItems[] = $item;
    elseif ($item['item_type'] === 'toy') $toyItems[] = $item;
    elseif ($item['item_type'] === 'medicine') $medicineItems[] = $item;
}

$moodInfo = getPetMood($pet['health'], $pet['hunger'], $pet['happiness'], $pet['energy'], $pet['cleanliness']);
?>

<div class="row" id="gameplay-container" data-pet-id="<?php echo $pet['id']; ?>" data-csrf="<?php echo generateCSRF(); ?>">
    <!-- Left Column: Pet Stage -->
    <div class="col-lg-6 mb-4">
        <div class="card glass-card border-0 h-100 text-center p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="fw-bold text-dark mb-0 pet-name-display"><?php echo sanitize($pet['pet_name']); ?></h2>
                    <span class="text-muted small"><?php echo sanitize($pet['breed'] ?: $pet['pet_type']); ?> &bull; Lvl <span class="pet-level-display"><?php echo $pet['level']; ?></span></span>
                </div>
                <span class="mood-badge pet-mood-display" style="background-color: <?php echo $moodInfo['color']; ?>22; color: <?php echo $moodInfo['color']; ?>;">
                    <span class="mood-emoji"><?php echo $moodInfo['emoji']; ?></span>
                    <span class="mood-text"><?php echo $moodInfo['mood']; ?></span>
                </span>
            </div>
            
            <!-- Interactive Stage Area -->
            <div class="pet-avatar-stage <?php echo strtolower($moodInfo['mood']); ?> position-relative mb-3" id="petStage">
                <!-- Floating Reaction Text -->
                <div class="reaction-container position-absolute top-50 start-50 translate-middle w-100 h-100" style="pointer-events: none;"></div>
                
                <!-- Cute Animated CSS Pet character rig -->
                <div class="css-pet <?php echo strtolower($pet['pet_type']); ?> <?php echo strtolower($moodInfo['mood']); ?>" id="petAvatar">
                    <div class="pet-ears">
                        <div class="ear ear-l"></div>
                        <div class="ear ear-r"></div>
                    </div>
                    <div class="pet-body">
                        <div class="tail"></div>
                        <div class="chest"></div>
                    </div>
                    <div class="pet-head">
                        <div class="face">
                            <div class="eyes">
                                <div class="eye eye-l"><div class="pupil"></div></div>
                                <div class="eye eye-r"><div class="pupil"></div></div>
                            </div>
                            <div class="muzzle">
                                <div class="nose"></div>
                                <div class="mouth"></div>
                            </div>
                            <div class="cheeks">
                                <div class="blush blush-l"></div>
                                <div class="blush blush-r"></div>
                            </div>
                        </div>
                    </div>
                    <div class="paw paw-l"></div>
                    <div class="paw paw-r"></div>
                </div>
                <div class="pet-stage-shadow"></div>
            </div>
            
            <!-- XP Progress Card -->
            <div class="card bg-light border-0 p-3 rounded-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold text-muted small"><i class="fa-solid fa-star text-accent me-1"></i> Experience (XP)</span>
                    <span class="fw-bold text-dark small"><span class="pet-xp-display"><?php echo $pet['xp']; ?></span> / <?php echo $nextThreshold; ?> XP</span>
                </div>
                <div class="progress" style="height: 12px; border-radius: 10px; background-color: #e2e8f0;">
                    <div class="progress-bar bg-accent pet-xp-progress-bar" role="progressbar" style="width: <?php echo $xpPercent; ?>%; border-radius: 10px;" aria-valuenow="<?php echo $xpPercent; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Stats & Actions -->
    <div class="col-lg-6 mb-4">
        <!-- Stats Card -->
        <div class="card glass-card border-0 mb-4 p-4">
            <h4 class="fw-bold text-dark mb-4"><i class="fa-solid fa-chart-simple text-primary me-2"></i>Pet Health & Stats</h4>
            
            <!-- Live Stat Bars -->
            <div class="stat-bars">
                <!-- Health -->
                <div class="stat-bar-container">
                    <div class="stat-label-row">
                        <span><i class="fa-solid fa-heart text-danger me-1"></i> Health (Physical Condition)</span>
                        <span class="pet-health-text"><?php echo $pet['health']; ?>/100</span>
                    </div>
                    <div class="custom-progress">
                        <div class="custom-progress-bar pet-health-bar" style="width: <?php echo $pet['health']; ?>%; background-color: <?php echo getStatColor($pet['health']); ?>"></div>
                    </div>
                </div>

                <!-- Hunger -->
                <div class="stat-bar-container">
                    <div class="stat-label-row">
                        <span><i class="fa-solid fa-bowl-food text-warning me-1"></i> Hunger (Needs Food)</span>
                        <span class="pet-hunger-text"><?php echo $pet['hunger']; ?>/100</span>
                    </div>
                    <div class="custom-progress">
                        <div class="custom-progress-bar pet-hunger-bar" style="width: <?php echo $pet['hunger']; ?>%; background-color: <?php echo getStatColor($pet['hunger'], true); ?>"></div>
                    </div>
                </div>

                <!-- Happiness -->
                <div class="stat-bar-container">
                    <div class="stat-label-row">
                        <span><i class="fa-solid fa-face-smile text-info me-1"></i> Happiness (Mental State)</span>
                        <span class="pet-happiness-text"><?php echo $pet['happiness']; ?>/100</span>
                    </div>
                    <div class="custom-progress">
                        <div class="custom-progress-bar pet-happiness-bar" style="width: <?php echo $pet['happiness']; ?>%; background-color: <?php echo getStatColor($pet['happiness']); ?>"></div>
                    </div>
                </div>

                <!-- Energy -->
                <div class="stat-bar-container">
                    <div class="stat-label-row">
                        <span><i class="fa-solid fa-bolt text-purple me-1" style="color: #8b5cf6;"></i> Energy (Tiredness)</span>
                        <span class="pet-energy-text"><?php echo $pet['energy']; ?>/100</span>
                    </div>
                    <div class="custom-progress">
                        <div class="custom-progress-bar pet-energy-bar" style="width: <?php echo $pet['energy']; ?>%; background-color: <?php echo getStatColor($pet['energy']); ?>"></div>
                    </div>
                </div>

                <!-- Cleanliness -->
                <div class="stat-bar-container">
                    <div class="stat-label-row">
                        <span><i class="fa-solid fa-soap text-success me-1"></i> Cleanliness (Grooming)</span>
                        <span class="pet-cleanliness-text"><?php echo $pet['cleanliness']; ?>/100</span>
                    </div>
                    <div class="custom-progress">
                        <div class="custom-progress-bar pet-cleanliness-bar" style="width: <?php echo $pet['cleanliness']; ?>%; background-color: <?php echo getStatColor($pet['cleanliness']); ?>"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions Card -->
        <div class="card glass-card border-0 p-4">
            <h4 class="fw-bold text-dark mb-3"><i class="fa-solid fa-hand-holding-heart text-accent me-2"></i>Care Actions</h4>
            
            <div class="row g-3 mb-4">
                <!-- Feed -->
                <div class="col-4 col-md-3">
                    <button class="care-btn w-100" id="actionFeed" data-action="feed">
                        <i class="fa-solid fa-cookie-bite text-warning"></i>
                        <span>Feed</span>
                    </button>
                </div>
                <!-- Water -->
                <div class="col-4 col-md-3">
                    <button class="care-btn w-100" id="actionWater" data-action="water">
                        <i class="fa-solid fa-droplet text-info"></i>
                        <span>Water</span>
                    </button>
                </div>
                <!-- Play -->
                <div class="col-4 col-md-3">
                    <button class="care-btn w-100" id="actionPlay" data-action="play">
                        <i class="fa-solid fa-baseball text-danger"></i>
                        <span>Play</span>
                    </button>
                </div>
                <!-- Groom -->
                <div class="col-4 col-md-3">
                    <button class="care-btn w-100" id="actionGroom" data-action="groom">
                        <i class="fa-solid fa-shower text-success"></i>
                        <span>Groom</span>
                    </button>
                </div>
                <!-- Sleep -->
                <div class="col-4 col-md-3">
                    <button class="care-btn w-100" id="actionSleep" data-action="sleep">
                        <i class="fa-solid fa-bed text-purple" style="color: #8b5cf6;"></i>
                        <span>Sleep</span>
                    </button>
                </div>
                <!-- Medicine -->
                <div class="col-4 col-md-3">
                    <button class="care-btn w-100" id="actionMedicine" data-action="medicine">
                        <i class="fa-solid fa-prescription-bottle-medical text-danger"></i>
                        <span>Medicine</span>
                    </button>
                </div>
                <!-- Train -->
                <div class="col-4 col-md-3">
                    <button class="care-btn w-100" id="actionTrain" data-action="train">
                        <i class="fa-solid fa-dumbbell text-primary"></i>
                        <span>Train</span>
                    </button>
                </div>
                <!-- History -->
                <div class="col-4 col-md-3">
                    <a href="<?php echo BASE_URL; ?>/user/care_history.php?pet_id=<?php echo $pet['id']; ?>" class="care-btn w-100 text-decoration-none">
                        <i class="fa-solid fa-history text-secondary"></i>
                        <span>Logs</span>
                    </a>
                </div>
            </div>

            <!-- Inventory Drawer -->
            <div class="border-top pt-3">
                <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-briefcase text-muted me-2"></i>My Care Items</h5>
                
                <ul class="nav nav-pills nav-fill mb-3 bg-light p-1 rounded-pill" id="inventoryTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active rounded-pill py-1.5 small fw-semibold" id="food-tab" data-bs-toggle="pill" data-bs-target="#inventoryFood" type="button" role="tab">Food</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill py-1.5 small fw-semibold" id="toys-tab" data-bs-toggle="pill" data-bs-target="#inventoryToys" type="button" role="tab">Toys</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill py-1.5 small fw-semibold" id="medicine-tab" data-bs-toggle="pill" data-bs-target="#inventoryMedicine" type="button" role="tab">Meds</button>
                    </li>
                </ul>
                
                <div class="tab-content" id="inventoryTabsContent">
                    <!-- Food Tab -->
                    <div class="tab-pane fade show active" id="inventoryFood" role="tabpanel">
                        <?php if (empty($foodItems)): ?>
                            <div class="text-center py-4 text-muted small">
                                <p class="mb-1">No food in inventory.</p>
                                <a href="<?php echo BASE_URL; ?>/user/shop.php" class="text-accent text-decoration-none fw-semibold">Buy food at Shop &rarr;</a>
                            </div>
                        <?php else: ?>
                            <div class="row g-2">
                                <?php foreach ($foodItems as $item): ?>
                                    <div class="col-md-6">
                                        <div class="card p-2 border-0 bg-light d-flex flex-row align-items-center rounded-3">
                                            <img src="<?php echo getItemImage($item['image']); ?>" alt="<?php echo sanitize($item['item_name']); ?>" class="me-2 rounded" style="width: 40px; height: 40px; object-fit: contain;">
                                            <div class="flex-grow-1 min-w-0">
                                                <div class="fw-bold text-dark text-truncate small"><?php echo sanitize($item['item_name']); ?></div>
                                                <div class="text-muted extra-small">Qty: <span class="item-qty-<?php echo $item['id']; ?>"><?php echo $item['quantity']; ?></span></div>
                                            </div>
                                            <button class="btn btn-sm btn-outline-primary rounded-pill px-2.5 py-1 text-xs use-item-btn" data-item-id="<?php echo $item['id']; ?>" data-action="feed">Use</button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Toys Tab -->
                    <div class="tab-pane fade" id="inventoryToys" role="tabpanel">
                        <?php if (empty($toyItems)): ?>
                            <div class="text-center py-4 text-muted small">
                                <p class="mb-1">No toys in inventory.</p>
                                <a href="<?php echo BASE_URL; ?>/user/shop.php" class="text-accent text-decoration-none fw-semibold">Buy toys at Shop &rarr;</a>
                            </div>
                        <?php else: ?>
                            <div class="row g-2">
                                <?php foreach ($toyItems as $item): ?>
                                    <div class="col-md-6">
                                        <div class="card p-2 border-0 bg-light d-flex flex-row align-items-center rounded-3">
                                            <img src="<?php echo getItemImage($item['image']); ?>" alt="<?php echo sanitize($item['item_name']); ?>" class="me-2 rounded" style="width: 40px; height: 40px; object-fit: contain;">
                                            <div class="flex-grow-1 min-w-0">
                                                <div class="fw-bold text-dark text-truncate small"><?php echo sanitize($item['item_name']); ?></div>
                                                <div class="text-muted extra-small">Qty: <span class="item-qty-<?php echo $item['id']; ?>"><?php echo $item['quantity']; ?></span></div>
                                            </div>
                                            <button class="btn btn-sm btn-outline-primary rounded-pill px-2.5 py-1 text-xs use-item-btn" data-item-id="<?php echo $item['id']; ?>" data-action="play">Use</button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Medicine Tab -->
                    <div class="tab-pane fade" id="inventoryMedicine" role="tabpanel">
                        <?php if (empty($medicineItems)): ?>
                            <div class="text-center py-4 text-muted small">
                                <p class="mb-1">No medicine in inventory.</p>
                                <a href="<?php echo BASE_URL; ?>/user/shop.php" class="text-accent text-decoration-none fw-semibold">Buy meds at Shop &rarr;</a>
                            </div>
                        <?php else: ?>
                            <div class="row g-2">
                                <?php foreach ($medicineItems as $item): ?>
                                    <div class="col-md-6">
                                        <div class="card p-2 border-0 bg-light d-flex flex-row align-items-center rounded-3">
                                            <img src="<?php echo getItemImage($item['image']); ?>" alt="<?php echo sanitize($item['item_name']); ?>" class="me-2 rounded" style="width: 40px; height: 40px; object-fit: contain;">
                                            <div class="flex-grow-1 min-w-0">
                                                <div class="fw-bold text-dark text-truncate small"><?php echo sanitize($item['item_name']); ?></div>
                                                <div class="text-muted extra-small">Qty: <span class="item-qty-<?php echo $item['id']; ?>"><?php echo $item['quantity']; ?></span></div>
                                            </div>
                                            <button class="btn btn-sm btn-outline-primary rounded-pill px-2.5 py-1 text-xs use-item-btn" data-item-id="<?php echo $item['id']; ?>" data-action="medicine">Use</button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Level Up Modal -->
<div class="modal fade" id="levelUpModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-card border-0 text-center p-4">
            <div class="modal-body">
                <div class="mb-4">
                    <span class="display-1">🎉</span>
                </div>
                <h2 class="fw-bold text-dark mb-2">Level Up!</h2>
                <p class="text-muted"><span class="pet-name-display-modal"></span> has reached level <span class="new-level-display-modal text-primary fw-bold"></span>!</p>
                <div class="my-4 py-3 bg-light rounded-4">
                    <h5 class="fw-semibold text-success mb-1">+50 Coins Reward!</h5>
                    <p class="text-muted small mb-0">Keep taking good care of your pet!</p>
                </div>
                <button type="button" class="btn btn-primary rounded-pill px-5 py-2.5 fw-bold" data-bs-dismiss="modal">Awesome!</button>
            </div>
        </div>
    </div>
</div>

<!-- Audio Assets (Optional SFX, falling back to Web Audio API synthesized sounds if files absent) -->
<audio id="sfxFeed" src="<?php echo BASE_URL; ?>/assets/audio/feed.mp3" preload="auto"></audio>
<audio id="sfxWater" src="<?php echo BASE_URL; ?>/assets/audio/water.mp3" preload="auto"></audio>
<audio id="sfxPlay" src="<?php echo BASE_URL; ?>/assets/audio/play.mp3" preload="auto"></audio>
<audio id="sfxGroom" src="<?php echo BASE_URL; ?>/assets/audio/groom.mp3" preload="auto"></audio>
<audio id="sfxSleep" src="<?php echo BASE_URL; ?>/assets/audio/sleep.mp3" preload="auto"></audio>
<audio id="sfxLevelUp" src="<?php echo BASE_URL; ?>/assets/audio/levelup.mp3" preload="auto"></audio>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
