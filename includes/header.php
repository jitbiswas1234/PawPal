<?php
/**
 * PawPal - Main Header
 */
require_once __DIR__ . '/functions.php';

// Active page helper
$current_page = basename($_SERVER['PHP_SELF']);

// Handle notifications check for logged in user
$unread_notifications = 0;
$user_coins = 0;
$user_points = 0;
$user_name = '';

if (isLoggedIn()) {
    $current_user = getCurrentUser($pdo);
    if ($current_user) {
        $user_coins = $current_user['coins'];
        $user_points = $current_user['points'];
        $user_name = $current_user['name'];
        $unread_notifications = getUnreadNotificationCount($pdo, $_SESSION['user_id']);
    }
}

$is_user_page = isLoggedIn() && (strpos($_SERVER['PHP_SELF'], '/user/') !== false);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME . ' | Virtual Pet Adoption & Care'; ?></title>
    
    <!-- Meta tags for SEO -->
    <meta name="description" content="Adopt and care for your virtual pets. Feed, groom, play, and watch them grow in PawPal Virtual Pet Simulator.">
    <meta name="keywords" content="virtual pet, pet adoption, care simulator, online pet game, pet buddy">
    
    <!-- Security & Routing Tokens -->
    <meta name="csrf-token" content="<?php echo generateCSRF(); ?>">
    <meta name="base-url" content="<?php echo BASE_URL; ?>">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>

    <?php if ($is_user_page): ?>
        <div class="user-layout-wrapper">
            <!-- Sidebar -->
            <aside class="user-sidebar" id="userSidebar">
                <!-- Sidebar Brand -->
                <div class="sidebar-brand d-flex align-items-center justify-content-between border-bottom border-secondary-subtle">
                    <a class="navbar-brand d-flex align-items-center text-decoration-none" href="<?php echo BASE_URL; ?>/index.php">
                        <i class="fa-solid fa-paw me-2 brand-icon text-accent fs-3"></i>
                        <span class="brand-text text-white fw-bold fs-4">Paw<span class="text-accent">Pal</span></span>
                    </a>
                    <button class="btn btn-sm btn-link text-white d-lg-none" id="closeSidebarBtn">
                        <i class="fa-solid fa-xmark fs-4"></i>
                    </button>
                </div>
                
                <!-- Sidebar Nav Links -->
                <nav class="sidebar-nav">
                    <a href="<?php echo BASE_URL; ?>/user/dashboard.php" class="sidebar-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-gauge-high"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/user/my_pets.php" class="sidebar-link <?php echo ($current_page == 'my_pets.php' || $current_page == 'pet_care.php' || $current_page == 'pet_details.php') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-paw"></i>
                        <span>My Pets</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/user/pets.php" class="sidebar-link <?php echo $current_page == 'pets.php' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-circle-plus"></i>
                        <span>Adopt a Pet</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/user/shop.php" class="sidebar-link <?php echo $current_page == 'shop.php' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-cart-shopping"></i>
                        <span>Pet Shop</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/user/care_history.php" class="sidebar-link <?php echo $current_page == 'care_history.php' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        <span>Care History</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/user/profile.php" class="sidebar-link <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                        <i class="fa-regular fa-id-card"></i>
                        <span>My Profile</span>
                    </a>
                    
                    <hr class="border-secondary-subtle my-3">
                    
                    <a href="<?php echo BASE_URL; ?>/index.php" class="sidebar-link">
                        <i class="fa-solid fa-house"></i>
                        <span>View Main Website</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/logout.php" class="sidebar-link text-danger">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        <span>Logout</span>
                    </a>
                </nav>
            </aside>

            <!-- Content Wrapper -->
            <div class="user-main-wrapper">
                <!-- User Topbar -->
                <header class="user-top-bar d-flex align-items-center justify-content-between px-4 py-3 sticky-top">
                    <div class="d-flex align-items-center gap-3">
                        <button class="btn btn-outline-secondary d-lg-none" id="toggleSidebarBtn">
                            <i class="fa-solid fa-bars"></i>
                        </button>
                        <h4 class="mb-0 fw-bold d-none d-md-block text-dark"><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h4>
                    </div>
                    
                    <div class="d-flex align-items-center gap-3">
                        <!-- Stats Badges -->
                        <div class="nav-stats-badge d-flex py-1 px-3 bg-light rounded-pill border">
                            <span class="stat-item me-3" title="Virtual Coins">
                                <i class="fa-solid fa-coins text-warning me-1"></i>
                                <span class="user-coins-display fw-bold text-dark"><?php echo number_format($user_coins); ?></span>
                            </span>
                            <span class="stat-item d-none d-sm-inline text-muted" title="Care Points (XP)">
                                <i class="fa-solid fa-star text-accent me-1"></i>
                                <span><?php echo number_format($user_points); ?> pts</span>
                            </span>
                        </div>

                        <!-- Notifications Dropdown -->
                        <div class="dropdown position-relative" id="notificationsDropdownWrapper">
                            <a class="nav-link position-relative py-2 px-3 rounded-circle hover-bg-light" href="#" role="button" id="notifDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-regular fa-bell fs-5 text-dark"></i>
                                <?php if ($unread_notifications > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger shadow notif-badge">
                                        <?php echo $unread_notifications; ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end notif-dropdown shadow py-0" aria-labelledby="notifDropdown">
                                <li class="dropdown-header d-flex justify-content-between align-items-center py-3 border-bottom">
                                    <span class="fw-bold">Notifications</span>
                                    <?php if ($unread_notifications > 0): ?>
                                        <a href="#" class="text-accent text-decoration-none small" id="markAllReadBtn">Mark all read</a>
                                    <?php endif; ?>
                                </li>
                                <div class="notif-scroll-area" style="max-height: 320px; overflow-y: auto;">
                                    <?php 
                                    $notifs = getNotifications($pdo, $_SESSION['user_id'], 5);
                                    if (empty($notifs)): 
                                    ?>
                                        <li class="p-4 text-center text-muted">
                                            <i class="fa-regular fa-bell-slash fs-3 mb-2"></i>
                                            <p class="mb-0 small">No new notifications</p>
                                        </li>
                                    <?php else: ?>
                                        <?php foreach ($notifs as $n): ?>
                                            <li class="notif-item p-3 border-bottom <?php echo $n['is_read'] ? '' : 'unread'; ?>" data-id="<?php echo $n['id']; ?>">
                                                <div class="d-flex align-items-start">
                                                    <span class="notif-icon-circle bg-<?php echo $n['type']; ?>-subtle text-<?php echo $n['type'] == 'danger' ? 'danger' : ($n['type'] == 'warning' ? 'warning' : 'primary'); ?> me-3">
                                                        <?php 
                                                        if ($n['type'] == 'danger') echo '<i class="fa-solid fa-triangle-exclamation"></i>';
                                                        elseif ($n['type'] == 'warning') echo '<i class="fa-solid fa-circle-info"></i>';
                                                        else echo '<i class="fa-solid fa-bell"></i>';
                                                        ?>
                                                    </span>
                                                    <div class="flex-grow-1">
                                                        <p class="mb-1 text-dark small <?php echo $n['is_read'] ? '' : 'fw-semibold'; ?>"><?php echo $n['message']; ?></p>
                                                        <span class="text-muted extra-small"><?php echo timeAgo($n['created_at']); ?></span>
                                                    </div>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <li class="text-center py-2 border-top bg-light rounded-bottom">
                                    <a href="<?php echo BASE_URL; ?>/user/dashboard.php#notifications" class="text-accent text-decoration-none small fw-semibold">View all notifications</a>
                                </li>
                            </ul>
                        </div>

                        <!-- User Profile Dropdown -->
                        <div class="dropdown">
                            <button class="btn btn-outline-dark dropdown-toggle d-flex align-items-center profile-btn px-3" type="button" id="userMenuBtn" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-solid fa-circle-user fs-5 me-md-2"></i>
                                <span class="d-none d-md-inline"><?php echo sanitize($user_name); ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" aria-labelledby="userMenuBtn">
                                <li><a class="dropdown-item py-2" href="<?php echo BASE_URL; ?>/user/profile.php"><i class="fa-regular fa-id-card me-2 text-muted"></i>My Profile</a></li>
                                <li><a class="dropdown-item py-2" href="<?php echo BASE_URL; ?>/user/care_history.php"><i class="fa-solid fa-clock-rotate-left me-2 text-muted"></i>Care History</a></li>
                                <?php if (isAdmin()): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item py-2 text-primary" href="<?php echo BASE_URL; ?>/admin/dashboard.php"><i class="fa-solid fa-gauge-high me-2"></i>Admin Panel</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item py-2 text-danger" href="<?php echo BASE_URL; ?>/logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </header>

                <!-- User Content Area -->
                <main class="user-content-area p-4">
    <?php else: ?>
        <!-- Navigation Header -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-custom-dark py-3 sticky-top">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center" href="<?php echo BASE_URL; ?>/index.php">
                    <i class="fa-solid fa-paw me-2 brand-icon"></i>
                    <span class="brand-text">Paw<span class="text-accent">Pal</span></span>
                </a>
                
                <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0 align-items-center">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/index.php">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'pets.php' && strpos($_SERVER['PHP_SELF'], '/user/') === false) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/user/pets.php">Adopt a Pet</a>
                        </li>
                        <?php if (isLoggedIn()): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/user/dashboard.php">Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $current_page == 'my_pets.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/user/my_pets.php">My Pets</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $current_page == 'shop.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/user/shop.php">Pet Shop</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                    
                    <div class="navbar-nav d-flex align-items-center">
                        <?php if (isLoggedIn()): ?>
                            <!-- Coins and Points display -->
                            <div class="nav-stats-badge me-3 d-none d-md-flex">
                                <span class="stat-item me-3" title="Virtual Coins">
                                    <i class="fa-solid fa-coins text-warning me-1"></i>
                                    <span class="user-coins-display"><?php echo number_format($user_coins); ?></span>
                                </span>
                                <span class="stat-item" title="Care Points (XP)">
                                    <i class="fa-solid fa-star text-accent me-1"></i>
                                    <span><?php echo number_format($user_points); ?> pts</span>
                                </span>
                            </div>
                            
                            <!-- Notifications Dropdown -->
                            <div class="dropdown me-3 position-relative" id="notificationsDropdownWrapper">
                                <a class="nav-link position-relative py-2 px-3 rounded-circle hover-bg-light" href="#" role="button" id="notifDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-regular fa-bell fs-5 text-white"></i>
                                    <?php if ($unread_notifications > 0): ?>
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger shadow notif-badge">
                                            <?php echo $unread_notifications; ?>
                                        </span>
                                    <?php endif; ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end notif-dropdown shadow py-0" aria-labelledby="notifDropdown">
                                    <li class="dropdown-header d-flex justify-content-between align-items-center py-3 border-bottom">
                                        <span class="fw-bold">Notifications</span>
                                        <?php if ($unread_notifications > 0): ?>
                                            <a href="#" class="text-accent text-decoration-none small" id="markAllReadBtn">Mark all read</a>
                                        <?php endif; ?>
                                    </li>
                                    <div class="notif-scroll-area" style="max-height: 320px; overflow-y: auto;">
                                        <!-- Dynamic contents loaded via AJAX or PHP -->
                                        <?php 
                                        $notifs = getNotifications($pdo, $_SESSION['user_id'], 5);
                                        if (empty($notifs)): 
                                        ?>
                                            <li class="p-4 text-center text-muted">
                                                <i class="fa-regular fa-bell-slash fs-3 mb-2"></i>
                                                <p class="mb-0 small">No new notifications</p>
                                            </li>
                                        <?php else: ?>
                                            <?php foreach ($notifs as $n): ?>
                                                <li class="notif-item p-3 border-bottom <?php echo $n['is_read'] ? '' : 'unread'; ?>" data-id="<?php echo $n['id']; ?>">
                                                    <div class="d-flex align-items-start">
                                                        <span class="notif-icon-circle bg-<?php echo $n['type']; ?>-subtle text-<?php echo $n['type'] == 'danger' ? 'danger' : ($n['type'] == 'warning' ? 'warning' : 'primary'); ?> me-3">
                                                            <?php 
                                                            if ($n['type'] == 'danger') echo '<i class="fa-solid fa-triangle-exclamation"></i>';
                                                            elseif ($n['type'] == 'warning') echo '<i class="fa-solid fa-circle-info"></i>';
                                                            else echo '<i class="fa-solid fa-bell"></i>';
                                                            ?>
                                                        </span>
                                                        <div class="flex-grow-1">
                                                            <p class="mb-1 text-dark small <?php echo $n['is_read'] ? '' : 'fw-semibold'; ?>"><?php echo $n['message']; ?></p>
                                                            <span class="text-muted extra-small"><?php echo timeAgo($n['created_at']); ?></span>
                                                        </div>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    <li class="text-center py-2 border-top bg-light rounded-bottom">
                                        <a href="<?php echo BASE_URL; ?>/user/dashboard.php#notifications" class="text-accent text-decoration-none small fw-semibold">View all dashboard notifications</a>
                                    </li>
                                </ul>
                            </div>

                            <!-- User Profile Dropdown -->
                            <div class="dropdown">
                                <button class="btn btn-outline-light dropdown-toggle d-flex align-items-center profile-btn px-3" type="button" id="userMenuBtn" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-circle-user fs-5 me-2"></i>
                                    <span><?php echo sanitize($user_name); ?></span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" aria-labelledby="userMenuBtn">
                                    <li>
                                        <div class="dropdown-item-text py-2 border-bottom d-md-none">
                                            <div class="d-flex align-items-center mb-1 text-muted">
                                                <i class="fa-solid fa-coins text-warning me-2"></i>
                                                <span class="user-coins-display"><?php echo number_format($user_coins); ?> Coins</span>
                                            </div>
                                            <div class="d-flex align-items-center text-muted">
                                                <i class="fa-solid fa-star text-accent me-2"></i>
                                                <span><?php echo number_format($user_points); ?> Points</span>
                                            </div>
                                        </div>
                                    </li>
                                    <li><a class="dropdown-item py-2" href="<?php echo BASE_URL; ?>/user/profile.php"><i class="fa-regular fa-id-card me-2 text-muted"></i>My Profile</a></li>
                                    <li><a class="dropdown-item py-2" href="<?php echo BASE_URL; ?>/user/care_history.php"><i class="fa-solid fa-clock-rotate-left me-2 text-muted"></i>Care History</a></li>
                                    <?php if (isAdmin()): ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item py-2 text-primary" href="<?php echo BASE_URL; ?>/admin/dashboard.php"><i class="fa-solid fa-gauge-high me-2"></i>Admin Panel</a></li>
                                    <?php endif; ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item py-2 text-danger" href="<?php echo BASE_URL; ?>/logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
                                </ul>
                            </div>
                        <?php else: ?>
                            <a href="<?php echo BASE_URL; ?>/login.php" class="btn btn-outline-light me-2 px-4 rounded-pill">Login</a>
                            <a href="<?php echo BASE_URL; ?>/register.php" class="btn btn-accent px-4 rounded-pill shadow-sm">Register</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Content Wrapper -->
        <main class="page-content-wrapper">
            <div class="container mt-4">
    <?php endif; ?>
            <!-- Flash messages -->
            <?php 
            $flash = getFlash();
            if ($flash): 
            ?>
                <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show shadow-sm border-0 mb-4 rounded-4" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="fa-solid <?php 
                            if ($flash['type'] == 'success') echo 'fa-circle-check text-success';
                            elseif ($flash['type'] == 'danger') echo 'fa-circle-xmark text-danger';
                            elseif ($flash['type'] == 'warning') echo 'fa-circle-exclamation text-warning';
                            else echo 'fa-circle-info text-info';
                        ?> fs-4 me-3"></i>
                        <div><?php echo $flash['message']; ?></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
