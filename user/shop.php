<?php
/**
 * PawPal - Virtual Pet Shop
 */
$page_title = 'Pet Shop';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/header.php';

$userId = $_SESSION['user_id'];
$currentUser = getCurrentUser($pdo);

// Handle purchase POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_item'])) {
    if (isset($_POST['csrf_token']) && verifyCSRF($_POST['csrf_token'])) {
        $itemId = (int)$_POST['item_id'];
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

        if ($itemId <= 0 || $quantity <= 0) {
            setFlash('danger', 'Invalid item or quantity.');
            redirect(BASE_URL . '/user/shop.php');
        }

        // Fetch item details
        $stmt = $pdo->prepare("SELECT * FROM pet_items WHERE id = ?");
        $stmt->execute([$itemId]);
        $item = $stmt->fetch();

        if (!$item) {
            setFlash('danger', 'Item not found in the shop.');
            redirect(BASE_URL . '/user/shop.php');
        }

        $totalPrice = $item['price'] * $quantity;

        // Verify coin balance
        if ($currentUser['coins'] < $totalPrice) {
            setFlash('danger', "Insufficient coins! You need <strong>{$totalPrice} coins</strong> but only have <strong>{$currentUser['coins']}</strong>.");
            redirect(BASE_URL . '/user/shop.php');
        }

        // Deduct coins & add to user inventory
        $pdo->beginTransaction();
        try {
            // Deduct
            deductCoins($pdo, $userId, $totalPrice);

            // Check if item already exists in user's inventory
            $checkStmt = $pdo->prepare("SELECT id FROM user_pet_items WHERE user_id = ? AND item_id = ?");
            $checkStmt->execute([$userId, $itemId]);
            $existing = $checkStmt->fetch();

            if ($existing) {
                // Update quantity
                $upStmt = $pdo->prepare("UPDATE user_pet_items SET quantity = quantity + ? WHERE id = ?");
                $upStmt->execute([$quantity, $existing['id']]);
            } else {
                // Insert new row
                $insStmt = $pdo->prepare("INSERT INTO user_pet_items (user_id, item_id, quantity) VALUES (?, ?, ?)");
                $insStmt->execute([$userId, $itemId, $quantity]);
            }

            // Create system notification
            $notifMsg = "🛒 Purchased {$quantity}x {$item['item_name']} for {$totalPrice} coins.";
            createNotification($pdo, $userId, null, $notifMsg, 'success');

            $pdo->commit();
            setFlash('success', "🎉 Successfully purchased <strong>{$quantity}x {$item['item_name']}</strong>!");
            
            // Reload user data to update the displays
            $currentUser = getCurrentUser($pdo);
        } catch (Exception $e) {
            $pdo->rollBack();
            setFlash('danger', 'An error occurred during purchase. Please try again.');
        }
    } else {
        setFlash('danger', 'Security verification failed.');
    }
    redirect(BASE_URL . '/user/shop.php');
}

// Filter items by category
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$allowedCategories = ['food', 'toy', 'medicine', 'accessory'];

$shopQuery = "SELECT * FROM pet_items";
$shopParams = [];
if (in_array($category, $allowedCategories)) {
    $shopQuery .= " WHERE item_type = ?";
    $shopParams[] = $category;
}
$shopQuery .= " ORDER BY price ASC";

$shopStmt = $pdo->prepare($shopQuery);
$shopStmt->execute($shopParams);
$shopItems = $shopStmt->fetchAll();

// Fetch user's adopted pets
$myPets = getUserPets($pdo, $userId);

// Fetch current inventory
$invStmt = $pdo->prepare("
    SELECT u.quantity, i.item_name, i.item_type, i.image, i.effect_stat, i.effect_value 
    FROM user_pet_items u 
    INNER JOIN pet_items i ON u.item_id = i.id 
    WHERE u.user_id = ? AND u.quantity > 0
    ORDER BY u.purchased_at DESC
");
$invStmt->execute([$userId]);
$myInventory = $invStmt->fetchAll();

$csrfToken = generateCSRF();
?>

<!-- Shop Header Area -->
<div class="hero-gradient mb-4 p-4 text-white rounded-4 position-relative overflow-hidden shadow-sm" style="background: radial-gradient(circle at top right, #4338ca 0%, #1e1b4b 100%);">
    <div class="hero-shape" style="background: linear-gradient(135deg, rgba(79, 70, 229, 0.3), rgba(236, 72, 153, 0.2));"></div>
    <div class="row align-items-center position-relative z-index-2 g-3">
        <div class="col-md-7">
            <h2 class="fw-bold mb-1"><i class="fa-solid fa-shop text-accent me-2"></i>Virtual Pet Shop</h2>
            <p class="lead text-white-50 fs-6 mb-0">Buy delicious food, exciting toys, healthy medicines, and cute accessories to pamper your pets.</p>
        </div>
        <div class="col-md-5 text-md-end">
            <div class="d-inline-flex gap-3 align-items-center p-3 bg-white bg-opacity-10 rounded-3 border border-white border-opacity-10">
                <div class="text-start">
                    <span class="text-white-50 extra-small d-block text-uppercase">Your Balance</span>
                    <span class="fs-4 fw-extrabold text-warning"><i class="fa-solid fa-coins me-1 animate-pulse"></i><?php echo number_format($currentUser['coins']); ?> <span class="fs-6 text-white-50 font-normal">Coins</span></span>
                </div>
                <div class="border-start border-white border-opacity-10 ps-3 text-start">
                    <span class="text-white-50 extra-small d-block text-uppercase">Inventory</span>
                    <span class="fs-4 fw-extrabold text-info"><i class="fa-solid fa-box-open me-1"></i><?php echo count($myInventory); ?> <span class="fs-6 text-white-50 font-normal">Items</span></span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Item Grid -->
    <div class="col-lg-8">
        <!-- Category Filters -->
        <div class="d-flex flex-wrap gap-2 mb-4">
            <a href="<?php echo BASE_URL; ?>/user/shop.php" class="btn <?php echo empty($category) ? 'btn-accent shadow-sm' : 'btn-light border'; ?> px-4 rounded-pill">All Items</a>
            <a href="<?php echo BASE_URL; ?>/user/shop.php?category=food" class="btn <?php echo $category === 'food' ? 'btn-accent shadow-sm' : 'btn-light border'; ?> px-4 rounded-pill">🍖 Food & Water</a>
            <a href="<?php echo BASE_URL; ?>/user/shop.php?category=toy" class="btn <?php echo $category === 'toy' ? 'btn-accent shadow-sm' : 'btn-light border'; ?> px-4 rounded-pill">🥎 Toys & Fun</a>
            <a href="<?php echo BASE_URL; ?>/user/shop.php?category=medicine" class="btn <?php echo $category === 'medicine' ? 'btn-accent shadow-sm' : 'btn-light border'; ?> px-4 rounded-pill">💊 Medicine</a>
            <a href="<?php echo BASE_URL; ?>/user/shop.php?category=accessory" class="btn <?php echo $category === 'accessory' ? 'btn-accent shadow-sm' : 'btn-light border'; ?> px-4 rounded-pill">👑 Accessories</a>
        </div>

        <?php if (empty($shopItems)): ?>
            <div class="glass-card p-5 text-center bg-white border shadow-sm rounded-4">
                <i class="fa-solid fa-basket-shopping fs-1 text-muted mb-3"></i>
                <h5 class="fw-bold">No Items Available</h5>
                <p class="text-muted">There are no items matching this category in the shop catalog.</p>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($shopItems as $item): 
                    $statName = ucfirst($item['effect_stat']);
                    $statVal = $item['effect_value'];
                    // Format nice status values (e.g. food reduces hunger, which is negative)
                    if ($item['effect_stat'] === 'hunger') {
                        $statText = "Reduces Hunger by " . abs($statVal) . "%";
                        $badgeClass = 'bg-warning-subtle text-warning';
                        $statIcon = 'fa-utensils';
                    } elseif ($item['effect_stat'] === 'happiness') {
                        $statText = "+{$statVal}% Happiness";
                        $badgeClass = 'bg-success-subtle text-success';
                        $statIcon = 'fa-face-smile';
                    } elseif ($item['effect_stat'] === 'health') {
                        $statText = "+{$statVal}% Health";
                        $badgeClass = 'bg-danger-subtle text-danger';
                        $statIcon = 'fa-kit-medical';
                    } elseif ($item['effect_stat'] === 'energy') {
                        $statText = "+{$statVal}% Energy";
                        $badgeClass = 'bg-info-subtle text-info';
                        $statIcon = 'fa-bolt';
                    } else {
                        $statText = "+{$statVal}% Cleanliness";
                        $badgeClass = 'bg-primary-subtle text-primary';
                        $statIcon = 'fa-soap';
                    }
                ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="shop-card glass-card h-100 d-flex flex-column bg-white shadow-sm border border-light transition-all hover-translate-y">
                            <div class="item-img-wrapper position-relative p-3 overflow-hidden rounded-top-4" style="height: 140px; background-color: #f8fafc; display: flex; align-items: center; justify-content: center;">
                                <span class="position-absolute top-0 start-0 m-2 badge bg-secondary text-capitalize extra-small shadow-sm">
                                    <?php echo sanitize($item['item_type']); ?>
                                </span>
                                <img src="<?php echo getItemImage($item['image']); ?>" class="item-img" style="max-height: 90px; object-fit: contain;" alt="<?php echo sanitize($item['item_name']); ?>">
                            </div>
                            
                            <div class="p-3 d-flex flex-column flex-grow-1">
                                <h6 class="fw-bold text-dark mb-1" style="font-size: 0.95rem;"><?php echo sanitize($item['item_name']); ?></h6>
                                <p class="text-muted extra-small mb-3 flex-grow-1" style="font-size: 0.75rem;"><?php echo sanitize($item['description']); ?></p>
                                
                                <div class="mb-3">
                                    <span class="badge <?php echo $badgeClass; ?> rounded-pill py-1 px-2 extra-small fw-bold" style="font-size: 0.7rem;">
                                        <i class="fa-solid <?php echo $statIcon; ?> me-1"></i><?php echo $statText; ?>
                                    </span>
                                </div>
                                
                                <div class="d-flex align-items-center justify-content-between border-top pt-3 mt-auto">
                                    <span class="fw-extrabold text-warning fs-5">
                                        <i class="fa-solid fa-coins me-1"></i><?php echo $item['price']; ?>
                                    </span>
                                    
                                    <form action="<?php echo BASE_URL; ?>/user/shop.php" method="POST" class="d-flex align-items-center gap-1">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        <select class="form-select form-select-sm bg-light py-1 px-2 border" name="quantity" style="width: 55px; font-size: 0.75rem;">
                                            <option value="1">1</option>
                                            <option value="2">2</option>
                                            <option value="3">3</option>
                                            <option value="5">5</option>
                                        </select>
                                        <button type="submit" name="buy_item" class="btn btn-accent btn-sm py-1 px-2 rounded-pill d-flex align-items-center gap-1" style="font-size: 0.75rem; font-weight: 600;" <?php echo $currentUser['coins'] < $item['price'] ? 'disabled title="Not enough coins"' : ''; ?>>
                                            <i class="fa-solid fa-cart-shopping" style="font-size: 0.7rem;"></i> Buy
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <!-- Right Column: My Pets & Inventory -->
    <div class="col-lg-4">
        <!-- My Pets Status Widget -->
        <div class="glass-card p-4 bg-white mb-4 shadow-sm border border-light">
            <h5 class="fw-bold mb-3 border-bottom pb-2">
                <i class="fa-solid fa-heart text-danger me-2"></i>My Pets Status
            </h5>
            <div style="max-height: 250px; overflow-y: auto;" class="pe-2">
                <?php if (empty($myPets)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fa-solid fa-dog fs-2 mb-2 text-light"></i>
                        <p class="mb-0 small">No pets adopted yet.</p>
                        <a href="<?php echo BASE_URL; ?>/user/pets.php" class="extra-small text-accent text-decoration-none">Adopt one now &rarr;</a>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($myPets as $pet): 
                            // Apply stat decay
                            applyDecay($pdo, $pet['id']);
                            $pet = getPetById($pdo, $pet['id']);
                            $moodData = getPetMood($pet['health'], $pet['hunger'], $pet['happiness'], $pet['energy'], $pet['cleanliness']);
                        ?>
                            <div class="d-flex align-items-center justify-content-between p-2 rounded-3 border bg-light-subtle">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle overflow-hidden bg-light border p-1" style="width: 44px; height: 44px;">
                                        <img src="<?php echo getPetImage($pet['image']); ?>" class="w-100 h-100 object-fit-contain" alt="<?php echo sanitize($pet['pet_name']); ?>">
                                    </div>
                                    <div>
                                        <h6 class="fw-bold mb-0 text-dark small" style="font-size: 0.85rem;"><?php echo sanitize($pet['pet_name']); ?></h6>
                                        <span class="text-muted extra-small" style="font-size: 0.7rem;">Lvl <?php echo $pet['level']; ?> &bull; <?php echo $moodData['emoji'] . ' ' . $pet['mood']; ?></span>
                                    </div>
                                </div>
                                <a href="<?php echo BASE_URL; ?>/user/pet_care.php?id=<?php echo $pet['id']; ?>" class="btn btn-sm btn-accent rounded-pill px-3 py-1 extra-small" style="font-size: 0.65rem;" title="Interact">
                                    Care
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- My Inventory Card -->
        <div class="glass-card p-4 bg-white shadow-sm border border-light" style="position: sticky; top: 90px; z-index: 1;">
            <h5 class="fw-bold mb-3 border-bottom pb-2">
                <i class="fa-solid fa-box-open text-primary me-2"></i>My Inventory
            </h5>
            
            <p class="text-muted extra-small mb-4">Items in your backpack. You can feed or pamper your pets with these items directly from your pet's care dashboard!</p>

            <div style="max-height: 400px; overflow-y: auto;" class="pe-2">
                <?php if (empty($myInventory)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fa-solid fa-luggage-cart fs-2 mb-2 text-light"></i>
                        <p class="mb-0 small">Your backpack is empty.</p>
                        <p class="extra-small text-muted mb-0">Buy some treats to get started!</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($myInventory as $inv): 
                            $badgeType = 'bg-secondary';
                            if ($inv['item_type'] === 'food') $badgeType = 'bg-success';
                            elseif ($inv['item_type'] === 'toy') $badgeType = 'bg-warning text-dark';
                            elseif ($inv['item_type'] === 'medicine') $badgeType = 'bg-danger';
                            elseif ($inv['item_type'] === 'accessory') $badgeType = 'bg-primary';
                        ?>
                            <div class="list-group-item px-0 py-3 d-flex align-items-center">
                                <div class="bg-light rounded-3 p-2 me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border: 1px solid #e2e8f0;">
                                    <img src="<?php echo getItemImage($inv['image']); ?>" class="img-fluid" style="max-height: 32px;" alt="<?php echo sanitize($inv['item_name']); ?>">
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="fw-bold mb-0 text-dark small"><?php echo sanitize($inv['item_name']); ?></h6>
                                    <span class="badge <?php echo $badgeType; ?> extra-small text-capitalize py-0 px-2"><?php echo $inv['item_type']; ?></span>
                                    <span class="text-muted extra-small ms-1"><?php echo $inv['effect_value'] > 0 ? '+' : ''; ?><?php echo $inv['effect_value']; ?> <?php echo $inv['effect_stat']; ?></span>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-dark rounded-pill fs-6 px-3 py-1 font-bold">x<?php echo $inv['quantity']; ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="d-grid mt-4 pt-3 border-top">
                <a href="<?php echo BASE_URL; ?>/user/dashboard.php" class="btn btn-outline-primary py-2 rounded-pill">
                    <i class="fa-solid fa-heart-pulse me-2"></i>Go Care For Pets
                </a>
            </div>
        </div>
    </div>
</div>

<?php 
require_once __DIR__ . '/../includes/footer.php';
?>
