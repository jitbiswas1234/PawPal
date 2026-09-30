<?php
/**
 * PawPal - Admin Sidebar & Header Layout
 */
require_once __DIR__ . '/../../includes/functions.php';

// Enforce admin permission
requireAdmin();

// Get database connection
global $pdo;

// Fetch current user details
$admin_user = getCurrentUser($pdo);
$admin_name = $admin_user ? sanitize($admin_user['name']) : 'Admin';
$admin_email = $admin_user ? sanitize($admin_user['email']) : 'admin@pawpal.com';
$admin_avatar = ($admin_user && !empty($admin_user['avatar'])) ? BASE_URL . '/' . $admin_user['avatar'] : null;

// Determine current page for active state styling
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' | PawPal Admin' : 'PawPal Admin Dashboard'; ?></title>
    
    <!-- Google Fonts (Outfit) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Core Style (for color variables) -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    
    <!-- Admin Specific CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin.css">
</head>
<body class="admin-body">

    <!-- Dark Sidebar -->
    <aside class="admin-sidebar" id="adminSidebar">
        <!-- Brand / Logo Area -->
        <div class="brand-area d-flex align-items-center justify-content-between">
            <a class="d-flex align-items-center text-decoration-none" href="<?php echo BASE_URL; ?>/admin/dashboard.php">
                <i class="fa-solid fa-paw me-2 brand-icon"></i>
                <span class="brand-text text-white">Paw<span class="text-accent">Pal</span> <span class="badge bg-primary fs-6 py-1 px-2 ms-1">Admin</span></span>
            </a>
            <button class="admin-toggle-btn text-white d-lg-none" onclick="toggleSidebar()"><i class="bi bi-x"></i></button>
        </div>

        <!-- Sidebar Navigation Links -->
        <nav class="sidebar-nav">
            <!-- Core Navigation -->
            <div class="nav-group-title">Core</div>
            <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/reports.php" class="nav-link <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                <i class="bi bi-graph-up-arrow"></i>
                <span>Analytics & Reports</span>
            </a>

            <!-- Management Section -->
            <div class="nav-group-title mt-4">Management</div>
            <a href="<?php echo BASE_URL; ?>/admin/pets.php" class="nav-link <?php echo in_array($current_page, ['pets.php', 'add_pet.php', 'edit_pet.php']) ? 'active' : ''; ?>">
                <i class="bi bi-heart-pulse"></i>
                <span>Pets Inventory</span>
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/adoption_requests.php" class="nav-link <?php echo $current_page == 'adoption_requests.php' ? 'active' : ''; ?>">
                <i class="bi bi-clipboard2-check"></i>
                <span>Adoption Requests</span>
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/users.php" class="nav-link <?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
                <i class="bi bi-people"></i>
                <span>User Accounts</span>
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/items.php" class="nav-link <?php echo $current_page == 'items.php' ? 'active' : ''; ?>">
                <i class="bi bi-shop"></i>
                <span>Shop Items</span>
            </a>

            <!-- User Utilities -->
            <div class="nav-group-title mt-4">Utilities</div>
            <a href="<?php echo BASE_URL; ?>/index.php" class="nav-link">
                <i class="bi bi-arrow-left-circle"></i>
                <span>View Main Website</span>
            </a>
            <a href="<?php echo BASE_URL; ?>/logout.php" class="nav-link text-danger">
                <i class="bi bi-box-arrow-right text-danger"></i>
                <span>Sign Out</span>
            </a>
        </nav>

        <!-- User Widget Profile -->
        <div class="user-profile-widget d-flex align-items-center gap-3">
            <?php if ($admin_avatar): ?>
                <img src="<?php echo $admin_avatar; ?>" alt="Admin Avatar" class="admin-avatar">
            <?php else: ?>
                <div class="admin-avatar bg-primary text-white d-flex align-items-center justify-content-center fw-bold">
                    <?php echo strtoupper(substr($admin_name, 0, 1)); ?>
                </div>
            <?php endif; ?>
            <div class="d-flex flex-column text-truncate">
                <span class="text-white fw-semibold small text-truncate"><?php echo $admin_name; ?></span>
                <span class="text-muted-dark extra-small text-truncate" style="font-size: 0.75rem;"><?php echo $admin_email; ?></span>
            </div>
        </div>
    </aside>

    <!-- Content Wrapper -->
    <div class="admin-wrapper">
        
        <!-- Header Navbar -->
        <header class="admin-header">
            <div class="d-flex align-items-center gap-3 w-100">
                <!-- Sidebar Toggle for Mobile -->
                <button class="admin-toggle-btn me-2" onclick="toggleSidebar()">
                    <i class="bi bi-list"></i>
                </button>
                
                <h4 class="mb-0 fw-bold d-none d-sm-block"><?php echo isset($page_title) ? $page_title : 'Admin Dashboard'; ?></h4>
                
                <div class="ms-auto d-flex align-items-center gap-3">
                    <!-- Right Actions (e.g. Quick Date, User dropdown) -->
                    <span class="badge bg-light text-dark py-2 px-3 border rounded-pill d-none d-md-flex align-items-center gap-2">
                        <i class="bi bi-calendar3 text-primary"></i>
                        <span><?php echo date('l, F j, Y'); ?></span>
                    </span>
                    <a href="<?php echo BASE_URL; ?>/user/dashboard.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                        <i class="bi bi-person me-1"></i> User Hub
                    </a>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="admin-content">
            <!-- Flash Alerts -->
            <?php $flash = getFlash(); if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show rounded-3 mb-4" role="alert">
                    <div class="d-flex align-items-center gap-2">
                        <?php if ($flash['type'] === 'success'): ?>
                            <i class="bi bi-check-circle-fill fs-5"></i>
                        <?php elseif ($flash['type'] === 'danger'): ?>
                            <i class="bi bi-x-circle-fill fs-5"></i>
                        <?php else: ?>
                            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                        <?php endif; ?>
                        <div><?php echo $flash['message']; ?></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
