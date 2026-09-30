<?php
/**
 * PawPal Admin - Add a New Pet
 */
$page_title = 'Add New Pet';
require_once __DIR__ . '/includes/admin_header.php';

// Form Handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pet_name = isset($_POST['pet_name']) ? trim($_POST['pet_name']) : '';
    $pet_type = isset($_POST['pet_type']) ? trim($_POST['pet_type']) : '';
    $breed = isset($_POST['breed']) ? trim($_POST['breed']) : '';
    $gender = isset($_POST['gender']) ? $_POST['gender'] : 'Male';
    $age = isset($_POST['age']) ? intval($_POST['age']) : 1;
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    
    // Status tracking defaults
    $health = isset($_POST['health']) ? max(0, min(100, intval($_POST['health']))) : 100;
    $hunger = isset($_POST['hunger']) ? max(0, min(100, intval($_POST['hunger']))) : 30;
    $happiness = isset($_POST['happiness']) ? max(0, min(100, intval($_POST['happiness']))) : 70;
    $cleanliness = isset($_POST['cleanliness']) ? max(0, min(100, intval($_POST['cleanliness']))) : 80;
    $energy = isset($_POST['energy']) ? max(0, min(100, intval($_POST['energy']))) : 80;

    // Basic Validation
    if (empty($pet_name) || empty($pet_type)) {
        setFlash('danger', 'Please enter a Pet Name and select a Pet Type.');
    } else {
        $image_filename = null;
        
        // Secure Image Upload Processing
        if (isset($_FILES['pet_image']) && $_FILES['pet_image']['error'] === UPLOAD_ERR_OK) {
            $tmp_path = $_FILES['pet_image']['tmp_name'];
            $original_name = $_FILES['pet_image']['name'];
            $file_ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            
            if (in_array($file_ext, $allowed_exts)) {
                // Ensure unique name
                $new_filename = 'pet_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
                $destination = __DIR__ . '/../assets/images/' . $new_filename;
                
                // Ensure target directory exists
                if (!is_dir(dirname($destination))) {
                    mkdir(dirname($destination), 0777, true);
                }
                
                if (move_uploaded_file($tmp_path, $destination)) {
                    $image_filename = $new_filename;
                } else {
                    setFlash('warning', 'Could not move the uploaded image. Saved pet without photo.');
                }
            } else {
                setFlash('danger', 'Invalid file type. Only JPG, PNG, WEBP, and GIF images are supported.');
            }
        }

        // Insert Database Record
        if (getFlash() === null || getFlash()['type'] !== 'danger') {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO pets (pet_name, pet_type, breed, age, gender, image, description, health, hunger, happiness, cleanliness, energy)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $pet_name,
                    $pet_type,
                    $breed,
                    $age,
                    $gender,
                    $image_filename,
                    $description,
                    $health,
                    $hunger,
                    $happiness,
                    $cleanliness,
                    $energy
                ]);
                
                setFlash('success', "Successfully added $pet_name to the adoption roster!");
                redirect(BASE_URL . '/admin/pets.php');
            } catch (PDOException $e) {
                setFlash('danger', 'Database query failed: ' . $e->getMessage());
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
                    <i class="bi bi-patch-plus text-primary fs-5"></i>
                    <h5 class="mb-0 fw-bold">Register New Virtual Pet</h5>
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
                                    <input type="text" id="pet_name" name="pet_name" class="form-control admin-control" placeholder="e.g. Charlie" required>
                                </div>
                                
                                <!-- Pet Type -->
                                <div class="col-md-6 mb-3 admin-form-group">
                                    <label for="pet_type">Pet Type <span class="text-danger">*</span></label>
                                    <select id="pet_type" name="pet_type" class="form-select admin-control" required>
                                        <option value="" disabled selected>Select Type</option>
                                        <option value="Dog">Dog</option>
                                        <option value="Cat">Cat</option>
                                        <option value="Rabbit">Rabbit</option>
                                        <option value="Bird">Bird</option>
                                        <option value="Fish">Fish</option>
                                        <option value="Hamster">Hamster</option>
                                    </select>
                                </div>

                                <!-- Breed -->
                                <div class="col-md-6 mb-3 admin-form-group">
                                    <label for="breed">Breed</label>
                                    <input type="text" id="breed" name="breed" class="form-control admin-control" placeholder="e.g. Golden Retriever">
                                </div>

                                <!-- Gender -->
                                <div class="col-md-3 mb-3 admin-form-group">
                                    <label for="gender">Gender</label>
                                    <select id="gender" name="gender" class="form-select admin-control">
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </div>

                                <!-- Age -->
                                <div class="col-md-3 mb-3 admin-form-group">
                                    <label for="age">Age (Years)</label>
                                    <input type="number" id="age" name="age" class="form-control admin-control" value="1" min="1" max="25">
                                </div>
                                
                                <!-- Description -->
                                <div class="col-12 mb-3 admin-form-group">
                                    <label for="description">About / Description</label>
                                    <textarea id="description" name="description" class="form-control admin-control" rows="5" placeholder="Share some interesting details about this pet's behavior, history, or preferences..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Right column: Photo Upload & Initial Stats -->
                        <div class="col-md-5">
                            <!-- Image upload preview box -->
                            <div class="mb-4">
                                <label class="form-label small fw-semibold text-muted mb-2">Pet Portrait / Photo</label>
                                <div class="image-upload-preview" id="pet_image_preview" onclick="document.getElementById('pet_image').click();">
                                    <img src="" alt="Upload Preview">
                                    <div class="upload-text text-slate-400 py-4">
                                        <i class="bi bi-cloud-arrow-up fs-1 d-block mb-2 text-primary"></i>
                                        <span>Click here to select a pet portrait</span>
                                        <div class="extra-small text-muted mt-1" style="font-size: 0.75rem;">Supported formats: JPG, PNG, WEBP, GIF</div>
                                    </div>
                                </div>
                                <input type="file" id="pet_image" name="pet_image" accept="image/*" class="d-none">
                            </div>

                            <!-- Initial Stats Configurations -->
                            <div class="p-3 bg-light rounded-3 border">
                                <h6 class="fw-bold mb-3 text-slate-700"><i class="bi bi-sliders me-1 text-primary"></i> Initial Status Tracking</h6>
                                
                                <!-- Health slider -->
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between small fw-semibold text-muted mb-1">
                                        <span>Health</span>
                                        <span id="health_val">100%</span>
                                    </div>
                                    <input type="range" class="form-range" name="health" min="10" max="100" value="100" oninput="document.getElementById('health_val').innerText = this.value + '%'">
                                </div>
                                
                                <!-- Hunger slider -->
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between small fw-semibold text-muted mb-1">
                                        <span>Hunger (Lower is less hungry)</span>
                                        <span id="hunger_val">30%</span>
                                    </div>
                                    <input type="range" class="form-range" name="hunger" min="0" max="100" value="30" oninput="document.getElementById('hunger_val').innerText = this.value + '%'">
                                </div>
                                
                                <!-- Happiness slider -->
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between small fw-semibold text-muted mb-1">
                                        <span>Happiness</span>
                                        <span id="happiness_val">70%</span>
                                    </div>
                                    <input type="range" class="form-range" name="happiness" min="10" max="100" value="70" oninput="document.getElementById('happiness_val').innerText = this.value + '%'">
                                </div>
                                
                                <!-- Cleanliness slider -->
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between small fw-semibold text-muted mb-1">
                                        <span>Cleanliness</span>
                                        <span id="cleanliness_val">80%</span>
                                    </div>
                                    <input type="range" class="form-range" name="cleanliness" min="10" max="100" value="80" oninput="document.getElementById('cleanliness_val').innerText = this.value + '%'">
                                </div>

                                <!-- Energy slider -->
                                <div>
                                    <div class="d-flex justify-content-between small fw-semibold text-muted mb-1">
                                        <span>Energy</span>
                                        <span id="energy_val">80%</span>
                                    </div>
                                    <input type="range" class="form-range" name="energy" min="10" max="100" value="80" oninput="document.getElementById('energy_val').innerText = this.value + '%'">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-4 border-top pt-3 d-flex justify-content-end gap-2">
                        <a href="<?php echo BASE_URL; ?>/admin/pets.php" class="btn btn-outline-secondary px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">Save Pet Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/admin_footer.php';
?>
