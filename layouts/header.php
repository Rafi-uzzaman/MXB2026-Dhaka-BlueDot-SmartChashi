<!DOCTYPE html>
<html lang="<?php echo get_language(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="description" content="Smart Chashi - AI Powered Smart Farming Ecosystem">
    <meta name="theme-color" content="#557A46">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title><?php echo APP_NAME; ?></title>
    
    <!-- jquery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $base_url; ?>img/logo.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo $base_url; ?>img/logo.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo $base_url; ?>img/logo.png">
    <link rel="shortcut icon" href="<?php echo $base_url; ?>img/logo.png">
    
    <!-- Google Material Icons -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    
    <link rel="stylesheet" href="<?php echo $base_url; ?>public/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="<?php echo $base_url; ?>" class="navbar-brand">
                <span class="brand-icon"><img src="<?php echo __('img/logo.png'); ?>" alt=""></span>
                <span class="brand-text"><?php echo __('smart_chashi'); ?></span>
            </a>
            
            <ul class="navbar-nav" id="navbarNav">
                <?php if (isLoggedIn()): ?>
                    <?php $currentRole = getCurrentUser()['role']; ?>
                    
                    <?php if ($currentRole === 'officer'): ?>
                        <!-- Extension Officer Navbar -->
                        <li class="nav-item">
                            <a href="<?php echo $base_url; ?>?page=officer-dashboard" class="nav-link">
                                <span class="material-icons nav-icon">dashboard</span>
                                <span class="nav-text"><?php echo __('dashboard'); ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_url; ?>?page=farmer-reports" class="nav-link">
                                <span class="material-icons nav-icon">assessment</span>
                                <span class="nav-text"><?php echo __('farmer_reports'); ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_url; ?>?page=issue-alert" class="nav-link">
                                <span class="material-icons nav-icon">notifications_active</span>
                                <span class="nav-text"><?php echo __('issue_alerts'); ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_url; ?>?page=advisory" class="nav-link">
                                <span class="material-icons nav-icon">assignment</span>
                                <span class="nav-text"><?php echo __('advisory'); ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_url; ?>?page=community" class="nav-link">
                                <span class="material-icons nav-icon">people</span>
                                <span class="nav-text"><?php echo __('community'); ?></span>
                            </a>
                        </li>
                    <?php else: ?>
                        <!-- Farmer Navbar -->
                        <li class="nav-item">
                            <a href="<?php echo $base_url; ?>?page=dashboard" class="nav-link">
                                <span class="material-icons nav-icon">dashboard</span>
                                <span class="nav-text"><?php echo __('dashboard'); ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_url; ?>?page=crops" class="nav-link">
                                <span class="material-icons nav-icon">agriculture</span>
                                <span class="nav-text"><?php echo __('crops'); ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_url; ?>?page=disease" class="nav-link">
                                <span class="material-icons nav-icon">bug_report</span>
                                <span class="nav-text"><?php echo __('disease_detection'); ?></span>
                            </a>
                        </li>
                      
                        <li class="nav-item">
                            <a href="<?php echo $base_url; ?>?page=weather" class="nav-link">
                                <span class="material-icons nav-icon">wb_sunny</span>
                                <span class="nav-text"><?php echo __('weather'); ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_url; ?>?page=marketplace" class="nav-link">
                                <span class="material-icons nav-icon">shopping_cart</span>
                                <span class="nav-text"><?php echo __('marketplace'); ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_url; ?>?page=community" class="nav-link">
                                <span class="material-icons nav-icon">people</span>
                                <span class="nav-text"><?php echo __('community'); ?></span>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php else: ?>
                    <li class="nav-item">
                        <a href="<?php echo $base_url; ?>" class="nav-link">
                            <span class="material-icons nav-icon">home</span>
                            <span class="nav-text"><?php echo __('home'); ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo $base_url; ?>?page=login" class="nav-link">
                            <span class="material-icons nav-icon">login</span>
                            <span class="nav-text"><?php echo __('login'); ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo $base_url; ?>?page=register" class="nav-link nav-link-primary">
                            <span class="material-icons nav-icon">person_add</span>
                            <span class="nav-text"><?php echo __('register'); ?></span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>

            <div class="navbar-actions">
                <?php if (isLoggedIn()): ?>
                    <!-- User Dropdown Menu -->
                    <?php $currentUser = getCurrentUser(); ?>
                    <div class="user-menu">
                        <button class="user-menu-toggle" id="userMenuToggle" aria-label="User Menu">
                            <?php if (!empty($currentUser['profile_img_url'])): ?>
                                <img src="<?php echo $base_url . 'public/' . $currentUser['profile_img_url']; ?>" alt="Profile" class="user-avatar">
                            <?php else: ?>
                                <span class="material-icons">account_circle</span>
                            <?php endif; ?>
                        </button>
                        <div class="user-dropdown" id="userDropdown">
                            <div class="user-dropdown-header">
                                <?php if (!empty($currentUser['profile_img_url'])): ?>
                                    <img src="<?php echo $base_url . 'public/' . $currentUser['profile_img_url']; ?>" alt="Profile" class="dropdown-avatar">
                                <?php else: ?>
                                    <span class="material-icons">person</span>
                                <?php endif; ?>
                                <span class="user-name"><?php echo $currentUser['first_name'] ?? 'User'; ?></span>
                            </div>
                            <a href="<?php echo $base_url; ?>?page=profile" class="user-dropdown-item">
                                <span class="material-icons">person</span>
                                <?php echo __('profile'); ?>
                            </a>
                            <div class="user-dropdown-divider"></div>
                            <div class="user-dropdown-item language-selector">
                                <span class="material-icons">language</span>
                                <span><?php echo get_language() === 'en' ? 'English' : 'বাংলা'; ?></span>
                                <span class="material-icons arrow">expand_more</span>
                            </div>
                            <div class="language-options" id="langOptionsInDropdown">
                                <button class="lang-option-btn" data-lang="en">
                                    <span class="material-icons">flag</span> English
                                </button>
                                <button class="lang-option-btn" data-lang="bn">
                                    <span class="material-icons">flag</span> বাংলা
                                </button>
                            </div>
                            <div class="user-dropdown-divider"></div>
                            <a href="<?php echo $base_url; ?>?page=logout" class="user-dropdown-item logout">
                                <span class="material-icons">logout</span>
                                <?php echo __('logout'); ?>
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Language Switcher for logged out users -->
                    <div class="language-switcher">
                        <button class="lang-toggle" id="langToggle" aria-label="Change Language">
                            <span class="material-icons lang-icon">language</span>
                            <span class="lang-current"><?php echo get_language() === 'en' ? 'EN' : 'বাংলা'; ?></span>
                        </button>
                        <div class="lang-dropdown" id="langMenu">
                            <button class="lang-option" data-lang="en">
                                <span class="material-icons flag">flag</span> English
                            </button>
                            <button class="lang-option" data-lang="bn">
                                <span class="material-icons flag">flag</span> বাংলা
                            </button>
                        </div>
                    </div>
                <?php endif; ?>

                <button class="navbar-toggler" id="menuToggle" aria-label="Toggle navigation" aria-expanded="false">
                    <span class="toggler-bar"></span>
                    <span class="toggler-bar"></span>
                    <span class="toggler-bar"></span>
                </button>
            </div>
        </div>
    </nav>
    
    <div class="navbar-overlay" id="navbarOverlay"></div>
    
    <div class="container">
    <main>
