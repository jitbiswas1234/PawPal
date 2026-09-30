<?php
/**
 * PawPal Admin - Edit Existing Pet
 */
$page_title = 'Edit Pet Details';
require_once __DIR__ . '/includes/admin_header.php';

// Validate ID
$pet_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($pet_id <= 0) {
    setFlash('danger', 'Invalid pet ID specified.');
    redirect(BASE_URL . '/admin/pets.php');
}

// Fetch Pet
try {
    $stmt = $pdo->prepare("SELECT * FROM pets WHERE id = ?");
    $stmt->execute([$pet_id]);
    $pet = $stmt->fetch();
    
    if (!$pet) {
        setFlash('danger', 'Requested pet record could not be found.');
        redirect(BASE_URL . '/admin/pets.php');
    }
} catch (PDOException $e) {
    die("Database query error: " . $e->getMessage());
}

// Form Handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pet_name = isset($_POST['pet_name']) ? trim($_POST['pet_name']) : '';
    $pet_type = isset($_POST['pet_type']) ? trim($_POST['pet_type']) : '';
    $breed = isset($_POST['breed']) ? trim($_POST['breed']) : '';
    $gender = isset($_POST['gender']) ? $_POST['gender'] : 'Male';
    $age = isset($_POST['age']) ? intval($_POST['age']) : 1;
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $status = isset($_POST['status']) ? $_POST['status'] : 'available';
    
    // Status metrics
    $health = isset($_POST['health']) ? max(0, min(100, intval($_POST['health']))) : 100;
    $hunger = isset($_POST['hunger']) ? max(0, min(100, intval($_POST['hunger']))) : 30;
    $happiness = isset($_POST['happiness']) ? max(0, min(100, intval($_POST['happiness']))) : 70;
    $cleanliness = isset($_POST['cleanliness']) ? max(0, min(100, intval($_POST['cleanliness']))) : 80;
    $energy = isset($_POST['energy']) ? max(0, min(100, intval($_POST['energy']))) : 80;

    // Basic Validation
    if (empty($pet_name) || empty($pet_type)) {
        setFlash('danger', 'Please enter a Pet Name and select a Pet Type.');
    } else {
        $image_filename = $pet['image']; // Default to existing image
        
        // Handle Photo Upload replacement
        if (isset($_FILES['pet_image']) && $_FILES['pet_image']['error'] === UPLOAD_ERR_OK) {
            $tmp_path = $_FILES['pet_image']['tmp_name'];
            $original_name = $_FILES['pet_image']['name'];
            $file_ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            
            if (in_array($file_ext, $allowed_exts)) {
                $new_filename = 'pet_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
                $destination = __DIR__ . '/../assets/images/' . $new_filename;
                
                // Ensure target directory exists
                if (!is_dir(dirname($destination))) {
                    mkdir(dirname($destination), 0777, true);
                }
                
                if (move_uploaded_file($tmp_path, $destination)) {
                    // Delete old pet photo from filesystem to keep it clean, if not a seed photo
                    if (!empty($pet['image'])) {
                        $old_image_path = __DIR__ . '/../assets/images/' . $pet['image'];
                        // Avoid deleting default/seed images like dog1.jpg, dog2.jpg, cat1.jpg etc. unless they were custom uploads
                        if (file_exists($old_image_path) && strpos($pet['image'], 'pet_') === 0) {
                            @unlink($old_image_path);
                        }
                    }
                    $image_filename = $new_filename;
                } else {
                    setFlash('warning', 'Could not save the new portrait image. Retained old photo.');
                }
            } else {
                setFlash('danger', 'Invalid file type. Only JPG, PNG, WEBP, and GIF images are supported.');
            }
        }

        // Update database if no error
        if (getFlash() === null || getFlash()['type'] !== 'danger') {
            try {
                // Fetch mood based on new stats
                $moodInfo = getPetMood($health, $hunger, $happiness, $energy, $cleanliness);
                $mood = $moodInfo['mood'];

                $stmt = $pdo->prepare("
                    UPDATE pets SET 
                        pet_name = ?, pet_type = ?, breed = ?, age = ?, gender = ?, 
                        image = ?, description = ?, status = ?, health = ?, 
                        hunger = ?, happiness = ?, cleanliness = ?, energy = ?, mood = ?
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $pet_name,
                    $pet_type,
                    $breed,
                    $age,
                    $gender,
                    $image_filename,
                    $description,
                    $status,
                    $health,
                    $hunger,
                    $happiness,
                    $cleanliness,
                    $energy,
                    $mood,
                    $pet_id
                ]);
                
                setFlash('success', "Successfully updated $pet_name's details!");
                redirect(BASE_URL . '/admin/pets.php');
            } catch (PDOException $e) {
                setFlash('danger', 'Database update query failed: ' . $e->getMessage());
            }
        }
    }
}
?>

<div class="mb-4">
    <a href="<?php echo BASE_URL; ?>/admin/pets.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
        <i class="bi bi-arrow-left me-1"></i> Back to Inventory
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="admin-card">
            <div class="card-header bg-white">
                <span class="d-flex align-items-center gap-2 py-1">
                    <i class="bi bi-pencil-square text-primary fs-5"></i>
                    <h5 class="mb-0 fw-bold">Modify Pet Record: <?php echo sanitize($pet['pet_name']); ?></h5>
                </span>
            </div>
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="row g-4">
                        <!-- Left column: Primary metadata -->
                        <div class="col-md-7">
                            <div class="row">
                                <!-- Pet Name -->
                                <div class="col-md-6 mb-3 admin-form-group">
                                    <label for="pet_name">Pet Name <span class="text-danger">*</span></label>
                                    <input type="text" id="pet_name" name="pet_name" class="form-control admin-control" value="<?php echo sanitize($pet['pet_name']); ?>" required>
                                </div>
                                
                                <!-- Pet Type -->
                                <div class="col-md-6 mb-3 admin-form-group">
                                    <label for="pet_type">Pet Type <span class="text-danger">*</span></label>
                                    <select id="pet_type" name="pet_type" class="form-select admin-control" required>
                                        <option value="Dog" <?php echo $pet['pet_type'] === 'Dog' ? 'selected' : ''; ?>>Dog</option>
                                        <option value="Cat" <?php echo $pet['pet_type'] === 'Cat' ? 'selected' : ''; ?>>Cat</option>
                                        <option value="Rabbit" <?php echo $pet['pet_type'] === 'Rabbit' ? 'selected' : ''; ?>>Rabbit</option>
                                        <option value="Bird" <?php echo $pet['pet_type'] === 'Bird' ? 'selected' : ''; ?>>Bird</option>
                                        <option value="Fish" <?php echo $pet['pet_type'] === 'Fish' ? 'selected' : ''; ?>>Fish</option>
                                        <option value="Hamster" <?php echo $pet['pet_type'] === 'Hamster' ? 'selected' : ''; ?>>Hamster</option>
                                    </select>
                                </div>

                                <!-- Breed -->
                                <div class="col-md-6 mb-3 admin-form-group">
                                    <label for="breed">Breed</label>
                                    <input type="text" id="breed" name="breed" class="form-control admin-control" value="<?php echo sanitize($pet['breed']); ?>">
                                </div>

                                <!-- Gender -->
                                <div class="col-md-3 mb-3 admin-form-group">
                                    <label for="gender">Gender</label>
                                    <select id="gender" name="gender" class="form-select admin-control">
                                        <option value="Male" <?php echo $pet['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo $pet['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                                    </select>
                                </div>

                                <!-- Age -->
                                <div class="col-md-3 mb-3 admin-form-group">
                                    <label for="age">Age (Years)</label>
                                    <input type="number" id="age" name="age" class="form-control admin-control" value="<?php echo $pet['age']; ?>" min="1" max="25">
                                </div>

                                <!-- Status -->
                                <div class="col-md-6 mb-3 admin-form-group">
                                    <label for="status">Adoption Status</label>
                                    <select id="status" name="status" class="form-select admin-control">
                                        <option value="available" <?php echo $pet['status'] === 'available' ? 'selected' : ''; ?>>Available for Adoption</option>
                                        <option value="adopted" <?php echo $pet['status'] === 'adopted' ? 'selected' : ''; ?>>Adopted (Assigned to User)</option>
                                    </select>
                                </div>
                                
                                <!-- Description -->
                                <div class="col-12 mb-3 admin-form-group">
                                    <label for="description">About / Description</label>
                                    <textarea id="description" name="description" class="form-control admin-control" rows="4"><?php echo sanitize($pet['description']); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Right column: Photo Upload & Stats -->
                        <div class="col-md-5">
                            <!-- Image upload preview box -->
                            <div class="mb-4">
                                <label class="form-label small fw-semibold text-muted mb-2">Pet Portrait / Photo</label>
                                <?php 
                                $image_url = !empty($pet['image']) ? BASE_URL . '/assets/images/' . $pet['image'] : BASE_URL . '/assets/images/default-pet.png';
                                ?>
                                <div class="image-upload-preview" id="pet_image_preview" onclick="document.getElementById('pet_image').click();">
                                    <img src="<?php echo $image_url; ?>" alt="Upload Preview" style="display: inline-block;">
                                    <div class="upload-text text-slate-400 py-4" style="display: none;">
                                        <i class="bi bi-cloud-arrow-up fs-1 d-block mb-2 text-primary"></i>
                                        <span>Click here to select a pet portrait</span>
                                    </div>
                                </div>
                                <input type="file" id="pet_image" name="pet_image" accept="image/*" class="d-none">
                            </div>

                            <!-- Current Stats Configurations -->
                            <div class="p-3 bg-light rounded-3 border">
                                <h6 class="fw-bold mb-3 text-slate-700">
                                    <span class="d-flex align-items-center justify-content-between">
                                        <span><i class="bi bi-sliders me-1 text-primary"></i> Stats Adjustment</span>
                                        <span class="badge bg-custom-dark text-accent">Mood: <?php echo sanitize($pet['mood']); ?></span>
                                    </span>
                                </h6>
                                
                                <!-- Health slider -->
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between small fw-semibold text-muted mb-1">
                                        <span>Health</span>
                                        <span id="health_val"><?php echo $pet['health']; ?>%</span>
                                    </div>
                                    <input type="range" class="form-range" name="health" min="10" max="100" value="<?php echo $pet['health']; ?>" oninput="document.getElementById('health_val').innerText = this.value + '%'">
                                </div>
                                
                                <!-- Hunger slider -->
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between small fw-semibold text-muted mb-1">
                                        <span>Hunger</span>
                                        <span id="hunger_val"><?php echo $pet['hunger']; ?>%</span>
                                    </div>
                                    <input type="range" class="form-range" name="hunger" min="0" max="100" value="<?php echo $pet['hunger']; ?>" oninput="document.getElementById('hunger_val').innerText = this.value + '%'">
                                </div>
                                
                                <!-- Happiness slider -->
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between small fw-semibold text-muted mb-1">
                                        <span>Happiness</span>
                                        <span id="happiness_val"><?php echo $pet['happiness']; ?>%</span>
                                    </div>
                                    <input type="range" class="form-range" name="happiness" min="10" max="100" value="<?php echo $pet['happiness']; ?>" oninput="document.getElementById('happiness_val').innerText = this.value + '%'">
                                </div>
                                
                                <!-- Cleanliness slider -->
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between small fw-semibold text-muted mb-1">
                                        <span>Cleanliness</span>
                                        <span id="cleanliness_val"><?php echo $pet['cleanliness']; ?>%</span>
                                    </div>
                                    <input type="range" class="form-range" name="cleanliness" min="10" max="100" value="<?php echo $pet['cleanliness']; ?>" oninput="document.getElementById('cleanliness_val').innerText = this.value + '%'">
                                </div>

                                <!-- Energy slider -->
                                <div>
                                    <div class="d-flex justify-content-between small fw-semibold text-muted mb-1">
                                        <span>Energy</span>
                                        <span id="energy_val"><?php echo $pet['energy']; ?>%</span>
                                    </div>
                                    <input type="range" class="form-range" name="energy" min="10" max="100" value="<?php echo $pet['energy']; ?>" oninput="document.getElementById('energy_val').innerText = this.value + '%'">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-4 border-top pt-3 d-flex justify-content-end gap-2">
                        <a href="<?php echo BASE_URL; ?>/admin/pets.php" class="btn btn-outline-secondary px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">Update Pet Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/admin_footer.php';
?>
