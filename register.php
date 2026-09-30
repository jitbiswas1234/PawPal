<?php
/**
 * PawPal - Registration Page
 */
$page_title = 'Register';
require_once __DIR__ . '/includes/header.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(BASE_URL . '/user/dashboard.php');
}

$errors = [];
$name = '';
$email = '';

// Process Registration Form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $terms = isset($_POST['terms']);
    $csrf_token = $_POST['csrf_token'] ?? '';

    // Verify CSRF Token
    if (!verifyCSRF($csrf_token)) {
        $errors['csrf'] = 'Invalid request. Please try again.';
    }

    // Input Validation
    if (empty($name)) {
        $errors['name'] = 'Full name is required.';
    } elseif (strlen($name) < 3) {
        $errors['name'] = 'Name must be at least 3 characters long.';
    }

    if (empty($email)) {
        $errors['email'] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    } else {
        // Check if email already exists
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                $errors['email'] = 'This email address is already registered.';
            }
        } catch (PDOException $e) {
            $errors['db'] = 'Database check failed.';
        }
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Password must be at least 6 characters long.';
    }

    if ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    if (!$terms) {
        $errors['terms'] = 'You must agree to the Terms of Service.';
    }

    // Process Insertion
    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user (100 coins, 0 points by default - matching schema)
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, coins, points) VALUES (?, ?, ?, 'user', 100, 0)");
            $stmt->execute([$name, $email, $hashed_password]);
            
            // Fetch newly created user to log them in automatically
            $userId = $pdo->lastInsertId();
            
            // Set session variables for automatic login
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_role'] = 'user';

            // Welcome Flash Message highlighting 100 Welcome Coins
            setFlash('success', "🎉 Account created! Welcome, " . sanitize($name) . ". 100 Welcome Coins have been added to your wallet! Adopt your first pet now.");
            
            // Redirect to dashboard to adopt a pet
            redirect(BASE_URL . '/user/dashboard.php');
        } catch (PDOException $e) {
            $errors['auth'] = 'Registration failed. Please try again. Code: ' . $e->getCode();
        }
    }
}

// Generate CSRF Token for form
$csrf_token = generateCSRF();
?>

<!-- Close default header container and wrapper -->
</div>
</main>

<div class="d-flex align-items-center justify-content-center py-5" style="min-height: calc(100vh - 180px); background: radial-gradient(circle at center, #f1f5f9 0%, #e2e8f0 100%);">
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-5">
                
                <!-- Welcome Coins Alert Banner -->
                <div class="alert alert-warning border-0 rounded-4 shadow-sm mb-4 text-center py-3 animate-pulse" style="background: linear-gradient(135deg, #fffbeb, #fef3c7); border: 1px solid rgba(245, 158, 11, 0.2) !important;">
                    <div class="d-flex align-items-center justify-content-center gap-2">
                        <i class="fa-solid fa-coins text-warning fs-4 spin-animation" style="animation: spin 3s linear infinite;"></i>
                        <span class="fw-bold text-amber-900" style="color: #78350f;">Registration Gift: Get 100 Free Coins!</span>
                    </div>
                </div>

                <!-- Glassmorphic Registration Card -->
                <div class="card border-0 shadow-lg" style="background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border-radius: var(--radius-lg); border: 1px solid rgba(255, 255, 255, 0.4);">
                    <div class="card-body p-5">
                        
                        <div class="text-center mb-4">
                            <h3 class="fw-bold mb-1 text-dark">Create Account</h3>
                            <p class="text-muted small">Begin your virtual pet care simulator journey</p>
                        </div>

                        <?php if (isset($errors['auth'])): ?>
                            <div class="alert alert-danger border-0 rounded-4 shadow-sm mb-4 small d-flex align-items-center" role="alert">
                                <i class="fa-solid fa-circle-xmark fs-5 me-2"></i>
                                <div><?php echo $errors['auth']; ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($errors['csrf'])): ?>
                            <div class="alert alert-danger border-0 rounded-4 shadow-sm mb-4 small d-flex align-items-center" role="alert">
                                <i class="fa-solid fa-triangle-exclamation fs-5 me-2"></i>
                                <div><?php echo $errors['csrf']; ?></div>
                            </div>
                        <?php endif; ?>

                        <form action="<?php echo sanitize($_SERVER['PHP_SELF']); ?>" method="POST" class="needs-validation" novalidate>
                            <!-- CSRF Token -->
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                            <!-- Name Input -->
                            <div class="mb-3">
                                <label for="name" class="form-label small fw-bold text-muted">Full Name</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 text-muted" style="border-radius: var(--radius-sm) 0 0 var(--radius-sm);"><i class="fa-regular fa-user"></i></span>
                                    <input type="text" class="form-control border-start-0 <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" id="name" name="name" value="<?php echo sanitize($name); ?>" placeholder="John Doe" required style="border-radius: 0 var(--radius-sm) var(--radius-sm) 0;">
                                    <?php if (isset($errors['name'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['name']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Email Input -->
                            <div class="mb-3">
                                <label for="email" class="form-label small fw-bold text-muted">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 text-muted" style="border-radius: var(--radius-sm) 0 0 var(--radius-sm);"><i class="fa-regular fa-envelope"></i></span>
                                    <input type="email" class="form-control border-start-0 <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?php echo sanitize($email); ?>" placeholder="name@domain.com" required style="border-radius: 0 var(--radius-sm) var(--radius-sm) 0;">
                                    <?php if (isset($errors['email'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Password Input -->
                            <div class="mb-3">
                                <label for="password" class="form-label small fw-bold text-muted">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 text-muted" style="border-radius: var(--radius-sm) 0 0 var(--radius-sm);"><i class="fa-solid fa-lock"></i></span>
                                    <input type="password" class="form-control border-start-0 border-end-0 <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" id="password" name="password" placeholder="Min. 6 characters" required>
                                    <button class="btn btn-outline-secondary bg-white border-start-0 text-muted" type="button" id="togglePassword" style="border-radius: 0 var(--radius-sm) var(--radius-sm) 0; border-color: #dee2e6;">
                                        <i class="fa-regular fa-eye-slash" id="toggleIcon"></i>
                                    </button>
                                    <?php if (isset($errors['password'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['password']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Confirm Password Input -->
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label small fw-bold text-muted">Confirm Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 text-muted" style="border-radius: var(--radius-sm) 0 0 var(--radius-sm);"><i class="fa-solid fa-circle-check"></i></span>
                                    <input type="password" class="form-control border-start-0 border-end-0 <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>" id="confirm_password" name="confirm_password" placeholder="Retype password" required>
                                    <button class="btn btn-outline-secondary bg-white border-start-0 text-muted" type="button" id="toggleConfirmPassword" style="border-radius: 0 var(--radius-sm) var(--radius-sm) 0; border-color: #dee2e6;">
                                        <i class="fa-regular fa-eye-slash" id="toggleConfirmIcon"></i>
                                    </button>
                                    <?php if (isset($errors['confirm_password'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['confirm_password']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Terms and Conditions Checkbox -->
                            <div class="mb-4 form-check text-start">
                                <input type="checkbox" class="form-check-input <?php echo isset($errors['terms']) ? 'is-invalid' : ''; ?>" id="terms" name="terms" <?php echo isset($_POST['terms']) ? 'checked' : ''; ?> required>
                                <label class="form-check-label small text-muted" for="terms">I agree to the <a href="#" class="text-decoration-none">Terms of Service</a> &amp; <a href="#" class="text-decoration-none">Privacy Policy</a></label>
                                <?php if (isset($errors['terms'])): ?>
                                    <div class="invalid-feedback d-block"><?php echo $errors['terms']; ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" class="btn btn-accent w-100 py-3 rounded-pill shadow-sm fw-semibold mb-4">
                                <i class="fa-solid fa-user-plus me-2"></i> Register &amp; Claim Coins
                            </button>
                        </form>

                        <div class="text-center mt-2 pt-3 border-top border-light">
                            <p class="mb-0 text-muted small">Already a pet caretaker? <a href="<?php echo BASE_URL; ?>/login.php" class="text-primary fw-bold text-decoration-none">Login here</a></p>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Password toggles helper
        function setupPasswordToggle(buttonId, inputId, iconId) {
            const btn = document.getElementById(buttonId);
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);

            if (btn && input && icon) {
                btn.addEventListener('click', function() {
                    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                    input.setAttribute('type', type);
                    
                    if (type === 'text') {
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                        btn.classList.add('text-primary');
                    } else {
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                        btn.classList.remove('text-primary');
                    }
                });
            }
        }

        setupPasswordToggle('togglePassword', 'password', 'toggleIcon');
        setupPasswordToggle('toggleConfirmPassword', 'confirm_password', 'toggleConfirmIcon');
    });
</script>

<!-- Reopen tags for footer.php -->
<main class="page-content-wrapper d-none">
<div class="container">
<?php
require_once __DIR__ . '/includes/footer.php';
?>
