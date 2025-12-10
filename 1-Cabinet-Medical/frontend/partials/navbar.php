<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/helpers/TranslationHelper.php';
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

// Determine current page for active state
$currentPage = basename($_SERVER['PHP_SELF']);
function isActive($page)
{
    global $currentPage;
    // Remap PHP filename to route for highlighting if needed, but simple string match might fail if URL is /accueil
    // Ideally we check URI, but sticking to simple active class for now. 
    // Actually, PHP_SELF shows the executing script (e.g. backend/index.php).
    // So we need to map backend scripts to highlighting logic.
    // If $page passed is 'index.php', it matches.
    // So we can keep calling isActive('index.php') but the link hrefs change.
    return $currentPage === $page ? 'w3-orange' : 'w3-hover-white';
}
?>
<!-- Navbar -->
<div class="w3-top">
    <div class="w3-bar w3-red w3-card w3-left-align w3-large">
        <a class="w3-bar-item w3-button w3-hide-medium w3-hide-large w3-right w3-padding-large w3-hover-white w3-large w3-red"
            href="javascript:void(0);" onclick="myFunction()" title="Toggle Navigation Menu"><i
                class="fa fa-bars"></i></a>
        <a href="<?php echo _route('home'); ?>"
            class="w3-bar-item w3-button w3-hide-small w3-padding-large <?php echo isActive('index.php'); ?>"><?php echo __('nav_home'); ?></a>
        <a href="<?php echo _route('about'); ?>"
            class="w3-bar-item w3-button w3-hide-small w3-padding-large <?php echo isActive('about.php'); ?>"><?php echo __('nav_about'); ?></a>
        <a href="<?php echo _route('doctors'); ?>"
            class="w3-bar-item w3-button w3-hide-small w3-padding-large <?php echo isActive('doctors.php'); ?>"><?php echo __('nav_doctors'); ?></a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="<?php echo _route('dashboard'); ?>"
                class="w3-bar-item w3-button w3-hide-small w3-padding-large <?php echo isActive('dashboard.php'); ?>"><?php echo __('nav_dashboard'); ?></a>
            <!-- Logout removed from main bar, moved to user panel below -->
        <?php else: ?>
            <a href="<?php echo _route('login'); ?>"
                class="w3-bar-item w3-button w3-hide-small w3-padding-large <?php echo isActive('login.php'); ?>"><?php echo __('nav_login'); ?></a>
            <a href="<?php echo _route('register'); ?>"
                class="w3-bar-item w3-button w3-hide-small w3-padding-large <?php echo isActive('register.php'); ?>"><?php echo __('nav_register'); ?></a>
        <?php endif; ?>

        <!-- Language Switcher (desktop only) -->
        <div class="w3-hide-small" style="position: absolute; right: 0;">
            <a href="?lang=en" class="w3-bar-item w3-button w3-hide-small w3-padding-large w3-hover-white">EN</a>
            <a href="?lang=fr" class="w3-bar-item w3-button w3-hide-small w3-padding-large w3-hover-white">FR</a>
        </div>
    </div>

    <!-- Navbar on small screens -->
    <div id="navDemo" class="w3-bar-block w3-hide w3-hide-large w3-hide-medium w3-large">
        <a href="<?php echo _route('home'); ?>"
            class="w3-bar-item w3-button w3-padding-large"><?php echo __('nav_home'); ?></a>
        <a href="<?php echo _route('about'); ?>"
            class="w3-bar-item w3-button w3-padding-large"><?php echo __('nav_about'); ?></a>
        <a href="<?php echo _route('doctors'); ?>"
            class="w3-bar-item w3-button w3-padding-large"><?php echo __('nav_doctors'); ?></a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="<?php echo _route('dashboard'); ?>"
                class="w3-bar-item w3-button w3-padding-large"><?php echo __('nav_dashboard'); ?></a>
            <a href="<?php echo _route('logout'); ?>"
                class="w3-bar-item w3-button w3-padding-large"><?php echo __('nav_logout'); ?></a>
        <?php else: ?>
            <a href="<?php echo _route('login'); ?>"
                class="w3-bar-item w3-button w3-padding-large"><?php echo __('nav_login'); ?></a>
            <a href="<?php echo _route('register'); ?>"
                class="w3-bar-item w3-button w3-padding-large"><?php echo __('nav_register'); ?></a>
        <?php endif; ?>
        <div class="w3-bar-item lang-container">
            <span style="font-weight: bold; margin-right: 10px;">Langue :</span>
            <a href="?lang=en" class="lang-btn">EN</a>
            <a href="?lang=fr" class="lang-btn">FR</a>
        </div>
    </div>
</div>

<?php if (isset($_SESSION['user_id'])): ?>
    <div style="position: fixed; top: 10px; right: 10px; z-index: 1001;">
            <div class="w3-card w3-white w3-padding-small w3-round-large w3-border">
                <span class="w3-small w3-text-dark-grey" style="font-weight:bold;">
                    <i class="fa fa-user-circle w3-text-blue"></i>
                    <?php echo htmlspecialchars($_SESSION['username'] ?? 'Utilisateur'); ?>
                </span>
                <a href="<?php echo _route('logout'); ?>"
                    class="w3-button w3-red w3-tiny w3-round w3-margin-left w3-hover-dark-red"
                    title="<?php echo __('nav_logout'); ?>">
                    <i class="fa fa-sign-out"></i>
                </a>
            </div>
        </div>
<?php endif; ?>