<?php
/**
 * PawPal Admin - Shop Inventory Management (Single-Page CRUD)
 */
require_once __DIR__ . '/../includes/functions.php';

// Enforce admin permission
requireAdmin();

global $pdo;

$action = isset($_GET['action']) ? trim($_GET['action']) : 'list';
$item_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// ============================================
// 1. Action Handler: Delete Item
// ============================================
if ($action === 'delete' && $item_id > 0) {
    try {
        // Fetch item details first for file clean-up
        $stmt = $pdo->prepare("SELECT item_name, image FROM pet_items WHERE id = ?");
        $stmt->execute([$item_id]);
        $item = $stmt->fetch();
        
        if ($item) {
            $item_name = sanitize($item['item_name']);
            $image_filename = $item['image'];
            
            // Delete record (cascading deletes user inventory records)
            $delete_stmt = $pdo->prepare("DELETE FROM pet_items WHERE id = ?");
            $delete_stmt->execute([$item_id]);
            
            // Clean up custom upload images (prefix 'item_')
            if (!empty($image_filename)) {
                $image_path = __DIR__ . '/../assets/images/' . $image_filename;
                if (file_exists($image_path) && strpos($image_filename, 'item_') === 0) {
                    @unlink($image_path);
                }
            }
            
            setFlash('success', "Successfully deleted shop item '$item_name'.");
        } else {
            setFlash('danger', 'Shop item not found.');
        }
    } catch (PDOException $e) {
        setFlash('danger', 'Database error on deleting item: ' . $e->getMessage());
    }
    redirect(BASE_URL . '/admin/items.php');
}

// ============================================
// 2. Action Handler: Add or Edit Post Submit
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['add', 'edit'])) {
    $item_name = isset($_POST['item_name']) ? trim($_POST['item_name']) : '';
    $item_type = isset($_POST['item_type']) ? trim($_POST['item_type']) : 'food';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $price = isset($_POST['price']) ? intval($_POST['price']) : 10;
    $effect_stat = isset($_POST['effect_stat']) ? trim($_POST['effect_stat']) : 'hunger';
    $effect_value = isset($_POST['effect_value']) ? intval($_POST['effect_value']) : 10;
    
    if (empty($item_name) || $price < 0) {
        setFlash('danger', 'Please enter a valid Item Name and non-negative Price.');
    } else {
        // Fetch existing image if editing
        $image_filename = null;
        if ($action === 'edit' && $item_id > 0) {
            $check_stmt = $pdo->prepare("SELECT image FROM pet_items WHERE id = ?");
            $check_stmt->execute([$item_id]);
            $image_filename = $check_stmt->fetchColumn();
        }
        
        // Handle image uploading
        if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] === UPLOAD_ERR_OK) {
            $tmp_path = $_FILES['item_image']['tmp_name'];
            $original_name = $_FILES['item_image']['name'];
            $file_ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            
            if (in_array($file_ext, $allowed_exts)) {
                $new_filename = 'item_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
                $destination = __DIR__ . '/../assets/images/' . $new_filename;
                
                if (move_uploaded_file($tmp_path, $destination)) {
                    // Delete old file if replacing
                    if ($action === 'edit' && !empty($image_filename)) {
                        $old_image_path = __DIR__ . '/../assets/images/' . $image_filename;
                        if (file_exists($old_image_path) && strpos($image_filename, 'item_') === 0) {
                            @unlink($old_image_path);
                        }
                    }
                    $image_filename = $new_filename;
                } else {
                    setFlash('warning', 'Could not save the item graphic. Used placeholder/retained old image.');
                }
            } else {
                setFlash('danger', 'Invalid file type. Only JPG, PNG, WEBP, and GIF are allowed.');
            }
        }
        
        // Save database record
        if (getFlash() === null || getFlash()['type'] !== 'danger') {
            try {
                if ($action === 'add') {
                    $insert_stmt = $pdo->prepare("
                        INSERT INTO pet_items (item_name, item_type, description, price, effect_stat, effect_value, image)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $insert_stmt->execute([$item_name, $item_type, $description, $price, $effect_stat, $effect_value, $image_filename]);
                    setFlash('success', "Successfully added '$item_name' to the pet shop inventory!");
                } else {
                    $update_stmt = $pdo->prepare("
                        UPDATE pet_items SET 
                            item_name = ?, item_type = ?, description = ?, 
                            price = ?, effect_stat = ?, effect_value = ?, image = ?
                        WHERE id = ?
                    ");
                    $update_stmt->execute([$item_name, $item_type, $description, $price, $effect_stat, $effect_value, $image_filename, $item_id]);
                    setFlash('success', "Successfully updated shop item '$item_name'!");
                }
                redirect(BASE_URL . '/admin/items.php');
            } catch (PDOException $e) {
                setFlash('danger', 'Database error occurred: ' . $e->getMessage());
            }
        }
    }
}

// Fetch list of items if list view
$items = [];
$edit_item = null;

if ($action === 'edit' && $item_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM pet_items WHERE id = ?");
    $stmt->execute([$item_id]);
    $edit_item = $stmt->fetch();
    
    if (!$edit_item) {
        setFlash('danger', 'Requested item not found.');
        redirect(BASE_URL . '/admin/items.php');
    }
}

if ($action === 'list' || $action === 'add' || $action === 'edit') {
    try {
        $list_stmt = $pdo->query("SELECT * FROM pet_items ORDER BY item_type ASC, price ASC");
        $items = $list_stmt->fetchAll();
    } catch (PDOException $e) {
        die("Error fetching shop items: " . $e->getMessage());
    }
}

// Page title overrides
if ($action === 'add') {
    $page_title = 'Create Shop Item';
} elseif ($action === 'edit') {
    $page_title = 'Modify Shop Item';
} else {
    $page_title = 'Shop Inventory';
}

require_once __DIR__ . '/includes/admin_header.php';
?>

<!-- Action Panel (Add / Edit Form Overlay) -->
<?php if ($action === 'add' || $action === 'edit'): ?>
    <div class="mb-4">
        <a href="<?php echo BASE_URL; ?>/admin/items.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Back to Inventory Listing
        </a>
    </div>
    
    <div class="row justify-content-center mb-5">
        <div class="col-lg-8">
            <div class="admin-card">
                <div class="card-header bg-white">
                    <span class="d-flex align-items-center gap-2 py-1">
                        <i class="bi <?php echo $action === 'add' ? 'bi-plus-circle-fill text-success' : 'bi-pencil-fill text-primary'; ?> fs-5"></i>
                        <h5 class="mb-0 fw-bold"><?php echo $action === 'add' ? 'Add New Item to Shop' : 'Edit Item: ' . sanitize($edit_item['item_name']); ?></h5>
                    </span>
                </div>
                <div class="card-body">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="row">
                            <!-- Left form parameters -->
                            <div class="col-md-7">
                                <!-- Name -->
                                <div class="mb-3 admin-form-group">
                                    <label for="item_name">Item Name <span class="text-danger">*</span></label>
                                    <input type="text" id="item_name" name="item_name" class="form-control admin-control" placeholder="e.g. Chew Toy" value="<?php echo $action === 'edit' ? sanitize($edit_item['item_name']) : ''; ?>" required>
                                </div>
                                
                                <div class="row">
                                    <!-- Type -->
                                    <div class="col-6 mb-3 admin-form-group">
                                        <label for="item_type">Item Type</label>
                                        <select id="item_type" name="item_type" class="form-select admin-control">
                                            <option value="food" <?php echo ($action === 'edit' && $edit_item['item_type'] === 'food') ? 'selected' : ''; ?>>Food / Treats</option>
                                            <option value="toy" <?php echo ($action === 'edit' && $edit_item['item_type'] === 'toy') ? 'selected' : ''; ?>>Toys / Fun</option>
                                            <option value="medicine" <?php echo ($action === 'edit' && $edit_item['item_type'] === 'medicine') ? 'selected' : ''; ?>>Medicine / Health</option>
                                            <option value="accessory" <?php echo ($action === 'edit' && $edit_item['item_type'] === 'accessory') ? 'selected' : ''; ?>>Accessory</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Price -->
                                    <div class="col-6 mb-3 admin-form-group">
                                        <label for="price">Price (Coins) <span class="text-danger">*</span></label>
                                        <input type="number" id="price" name="price" class="form-control admin-control" min="1" value="<?php echo $action === 'edit' ? $edit_item['price'] : '15'; ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <!-- Effect Stat -->
                                    <div class="col-6 mb-3 admin-form-group">
                                        <label for="effect_stat">Effect Stat Target</label>
                                        <select id="effect_stat" name="effect_stat" class="form-select admin-control">
                                            <option value="hunger" <?php echo ($action === 'edit' && $edit_item['effect_stat'] === 'hunger') ? 'selected' : ''; ?>>Hunger (reduces hunger)</option>
                                            <option value="happiness" <?php echo ($action === 'edit' && $edit_item['effect_stat'] === 'happiness') ? 'selected' : ''; ?>>Happiness (raises fun)</option>
                                            <option value="health" <?php echo ($action === 'edit' && $edit_item['effect_stat'] === 'health') ? 'selected' : ''; ?>>Health (heals/cures)</option>
                                            <option value="cleanliness" <?php echo ($action === 'edit' && $edit_item['effect_stat'] === 'cleanliness') ? 'selected' : ''; ?>>Cleanliness (washes/grooms)</option>
                                            <option value="energy" <?php echo ($action === 'edit' && $edit_item['effect_stat'] === 'energy') ? 'selected' : ''; ?>>Energy (rests/boosts)</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Effect Value -->
                                    <div class="col-6 mb-3 admin-form-group">
                                        <label for="effect_value">Stat Modifier Value</label>
                                        <input type="number" id="effect_value" name="effect_value" class="form-control admin-control" placeholder="e.g. 15 or -20" value="<?php echo $action === 'edit' ? $edit_item['effect_value'] : '15'; ?>">
                                        <span class="text-muted extra-small d-block mt-1" style="font-size: 0.7rem;">Use negative values for reducing hunger (e.g. food eats -25 hunger). Use positive values to increase health/happiness.</span>
                                    </div>
                                </div>
                                
                                <!-- Description -->
                                <div class="mb-3 admin-form-group">
                                    <label for="description">Item Description</label>
                                    <textarea id="description" name="description" class="form-control admin-control" rows="3" placeholder="Explain what the item does..."><?php echo $action === 'edit' ? sanitize($edit_item['description']) : ''; ?></textarea>
                                </div>
                            </div>
                            
                            <!-- Right form image upload -->
                            <div class="col-md-5">
                                <label class="form-label small fw-semibold text-muted mb-2">Item Graphic / Icon</label>
                                <?php 
                                $item_img = ($action === 'edit' && !empty($edit_item['image'])) ? BASE_URL . '/assets/images/' . $edit_item['image'] : '';
                                ?>
                                <div class="image-upload-preview" id="item_image_preview" onclick="document.getElementById('item_image').click();">
                                    <img src="<?php echo $item_img; ?>" alt="Upload Preview" style="<?php echo $item_img ? 'display:inline-block;' : ''; ?>">
                                    <div class="upload-text text-slate-400 py-4" style="<?php echo $item_img ? 'display:none;' : ''; ?>">
                                        <i class="bi bi-shop fs-1 d-block mb-2 text-success"></i>
                                        <span>Click here to select an item icon</span>
                                        <div class="extra-small text-muted mt-1" style="font-size: 0.75rem;">Supported: PNG, JPG, WEBP, GIF</div>
                                    </div>
                                </div>
                                <input type="file" id="item_image" name="item_image" accept="image/*" class="d-none">
                            </div>
                        </div>
                        
                        <div class="mt-4 border-top pt-3 d-flex justify-content-end gap-2">
                            <a href="<?php echo BASE_URL; ?>/admin/items.php" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4">Save Shop Item</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Default: Shop Items Table List -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h2 class="fw-bold mb-1">Shop Inventory</h2>
        <p class="text-muted mb-0">Total of <?php echo count($items); ?> items active in PawPal Virtual Shop.</p>
    </div>
    <?php if ($action === 'list'): ?>
        <div>
            <a href="<?php echo BASE_URL; ?>/admin/items.php?action=add" class="btn btn-primary d-flex align-items-center gap-2">
                <i class="bi bi-plus-lg"></i>
                <span>Add Shop Item</span>
            </a>
        </div>
    <?php endif; ?>
</div>

<div class="admin-card">
    <div class="card-body p-0">
        <?php if (empty($items)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-basket3 fs-1 d-block mb-2 text-slate-300"></i>
                <h5>Shop is Empty</h5>
                <p class="mb-0">There are no items created yet. Click "Add Shop Item" to create one.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table admin-table align-middle">
                    <thead>
                        <tr>
                            <th style="width: 80px;">Icon</th>
                            <th>Item Name</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Cost</th>
                            <th>Stat Modifier</th>
                            <th class="text-end" style="width: 140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <!-- Icon image -->
                                <td>
                                    <?php 
                                    $item_pic = !empty($item['image']) ? BASE_URL . '/assets/images/' . $item['image'] : BASE_URL . '/assets/images/default-item.png';
                                    ?>
                                    <div class="p-1 border rounded bg-light d-inline-block">
                                        <img src="<?php echo $item_pic; ?>" alt="<?php echo sanitize($item['item_name']); ?>" style="width: 44px; height: 44px; object-fit: contain;">
                                    </div>
                                </td>
                                
                                <!-- Name -->
                                <td>
                                    <span class="fw-bold text-slate-800 fs-6"><?php echo sanitize($item['item_name']); ?></span>
                                </td>
                                
                                <!-- Category -->
                                <td>
                                    <span class="badge bg-secondary-subtle text-dark border px-2 py-1 font-medium text-capitalize">
                                        <?php echo sanitize($item['item_type']); ?>
                                    </span>
                                </td>
                                
                                <!-- Description -->
                                <td class="small text-muted text-wrap" style="max-width: 250px;">
                                    <?php echo sanitize($item['description']); ?>
                                </td>
                                
                                <!-- Cost -->
                                <td>
                                    <span class="fw-bold text-warning">
                                        <i class="fa-solid fa-coins me-1"></i>
                                        <?php echo number_format($item['price']); ?>
                                    </span>
                                </td>
                                
                                <!-- Modifier Details -->
                                <td>
                                    <?php 
                                    $val = $item['effect_value'];
                                    $stat = sanitize($item['effect_stat']);
                                    $sign = $val > 0 ? '+' : '';
                                    
                                    // Visual color indicators
                                    $badgeColor = 'bg-success';
                                    if ($stat === 'hunger') {
                                        $badgeColor = 'bg-warning-subtle text-warning border-warning';
                                    } elseif ($stat === 'happiness') {
                                        $badgeColor = 'bg-danger-subtle text-danger border-danger';
                                    } elseif ($stat === 'energy') {
                                        $badgeColor = 'bg-primary-subtle text-primary border-primary';
                                    } elseif ($stat === 'cleanliness') {
                                        $badgeColor = 'bg-info-subtle text-info border-info';
                                    }
                                    ?>
                                    <span class="badge <?php echo $badgeColor; ?> border py-1 px-2 font-semibold">
                                        <?php echo strtoupper($stat) . ' (' . $sign . $val . ')'; ?>
                                    </span>
                                </td>
                                
                                <!-- Actions -->
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="<?php echo BASE_URL; ?>/admin/items.php?action=edit&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-primary border-0" title="Edit Item">
                                            <i class="bi bi-pencil-square fs-5"></i>
                                        </a>
                                        <a href="<?php echo BASE_URL; ?>/admin/items.php?action=delete&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-danger border-0" title="Delete Item" onclick="return confirmDelete('Are you sure you want to delete <?php echo sanitize($item['item_name']); ?>? It will be removed from shop listings.');">
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
