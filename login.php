<?php
/**
 * PawPal - Login Page
 */
$page_title = 'Login';
require_once __DIR__ . '/includes/header.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect(BASE_URL . '/admin/dashboard.php');
    } else {
        redirect(BASE_URL . '/user/dashboard.php');
    }
}

$errors = [];
$email = '';

// Process Login Form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    // Verify CSRF Token
    if (!verifyCSRF($csrf_token)) {
        $errors['csrf'] = 'Invalid request. Please try again.';
    }

    // Input Validation
    if (empty($email)) {
        $errors['email'] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required.';
    }

    // Attempt Authentication if no errors
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Regenerate session ID for security (anti-session fixation)
                session_regenerate_id(true);

                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];

                // Success flash message
                setFlash('success', "Welcome back, " . sanitize($user['name']) . "! Let's check on your pets.");

                // Role-based redirection
                if ($user['role'] === 'admin') {
                    redirect(BASE_URL . '/admin/dashboard.php');
                } else {
                    redirect(BASE_URL . '/user/dashboard.php');
                }
            } else {
                $errors['auth'] = 'Invalid email address or password.';
            }
        } catch (PDOException $e) {
            $errors['db'] = 'System error. Please try again later.';
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
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                
                <!-- Glassmorphic Login Card -->
                <div class="card border-0 shadow-lg" style="background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border-radius: var(--radius-lg); border: 1px solid rgba(255, 255, 255, 0.4);">
                    <div class="card-body p-5">
                        
                        <div class="text-center mb-5">
                            <div class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-circle mb-3 shadow" style="width: 60px; height: 60px;">
                                <i class="fa-solid fa-paw fs-3"></i>
                            </div>
                            <h3 class="fw-bold mb-1 text-dark">Welcome Back</h3>
                            <p class="text-muted small">Log in to feed, play, and groom your virtual pets</p>
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

                            <!-- Email Input -->
                            <div class="mb-4">
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
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label for="password" class="form-label small fw-bold text-muted mb-0">Password</label>
                                    <!-- A mock/future feature link could be placed here if needed -->
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 text-muted" style="border-radius: var(--radius-sm) 0 0 var(--radius-sm);"><i class="fa-solid fa-lock"></i></span>
                                    <input type="password" class="form-control border-start-0 border-end-0 <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" id="password" name="password" placeholder="••••••••" required>
                                    <button class="btn btn-outline-secondary bg-white border-start-0 text-muted" type="button" id="togglePassword" style="border-radius: 0 var(--radius-sm) var(--radius-sm) 0; border-color: #dee2e6;">
                                        <i class="fa-regular fa-eye-slash" id="toggleIcon"></i>
                                    </button>
                                    <?php if (isset($errors['password'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['password']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" class="btn btn-accent w-100 py-3 rounded-pill shadow-sm fw-semibold mb-4 mt-2">
                                <i class="fa-solid fa-right-to-bracket me-2"></i> Log In
                            </button>
                        </form>

                        <div class="text-center mt-3 pt-3 border-top border-light">
                            <p class="mb-0 text-muted small">Don't have a caretaker account? <a href="<?php echo BASE_URL; ?>/register.php" class="text-primary fw-bold text-decoration-none">Register here</a></p>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('toggleIcon');

        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function() {
                // Toggle the type attribute
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                // Toggle the eye / eye-slash icon
                if (type === 'text') {
                    toggleIcon.classList.remove('fa-eye-slash');
                    toggleIcon.classList.add('fa-eye');
                    togglePassword.classList.add('text-primary');
                } else {
                    toggleIcon.classList.remove('fa-eye');
                    toggleIcon.classList.add('fa-eye-slash');
                    togglePassword.classList.remove('text-primary');
                }
            });
        }
    });
</script>

<!-- Reopen tags for footer.php -->
<main class="page-content-wrapper d-none">
<div class="container">
<?php
require_once __DIR__ . '/includes/footer.php';
?>
