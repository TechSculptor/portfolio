<?php
// Ne démarrer la session QUE si elle n'est pas déjà active
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// Include Translation Helper
require_once __DIR__ . '/../../backend/helpers/TranslationHelper.php';

$isLoggedIn = isset($_SESSION['user_id']);
$userType = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : '';
$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';

// Get current URI to append lang parameter
$currentUri = $_SERVER['REQUEST_URI'];
function getLangUrl($lang)
{
    global $currentUri;
    $parsed = parse_url($currentUri);
    $query = [];
    if (isset($parsed['query'])) {
        parse_str($parsed['query'], $query);
    }
    $query['lang'] = $lang;
    return $parsed['path'] . '?' . http_build_query($query);
}
?>

<?php
// Function to check if link is active

function isActive($path)
{
    global $currentUri;
    $pathOnly = parse_url($currentUri, PHP_URL_PATH);
    if ($path === '/' && ($pathOnly === '/' || $pathOnly === '/index.php'))
        return true;
    if ($path !== '/' && strpos($pathOnly, $path) === 0)
        return true;
    return false;
}
function activeClass($path)
{
    return isActive($path) ? 'active-tab' : '';
}
?>
<!-- Fixed top navigation -->
<div class="w3-top">
    <div class="w3-bar w3-red w3-card w3-left-align w3-large">
        <a class="w3-bar-item w3-button w3-hide-medium w3-hide-large w3-right w3-padding-large w3-hover-white w3-large w3-red"
            href="javascript:void(0);" onclick="myFunction()" title="Toggle Navigation Menu"><i
                class="fa fa-bars"></i></a>

        <a href="/"
            class="w3-bar-item w3-button w3-padding-large <?php echo activeClass('/'); ?>"><?php echo __('nav_home'); ?></a>
        <a href="/about"
            class="w3-bar-item w3-button w3-hide-small w3-padding-large <?php echo activeClass('/about'); ?>"><?php echo __('nav_about'); ?></a>
        <a href="/doctors"
            class="w3-bar-item w3-button w3-hide-small w3-padding-large <?php echo activeClass('/doctors'); ?>"><?php echo __('nav_doctors'); ?></a>
        <?php if ($isLoggedIn) { ?>
            <a href="/dashboard"
                class="w3-bar-item w3-button w3-hide-small w3-padding-large <?php echo activeClass('/dashboard'); ?>"><?php echo __('nav_dashboard'); ?></a>
            <?php if ($userType === 'admin') { ?>
                <a href="/admin_doctors"
                    class="w3-bar-item w3-button w3-hide-small w3-padding-large <?php echo activeClass('/admin_doctors'); ?>"><?php echo __('dashboard_manage_docs'); ?></a>
            <?php } ?>
            <a href="javascript:void(0)" onclick="openLogoutModal()"
                class="w3-bar-item w3-button w3-hide-small w3-padding-large w3-right-align-flex"
                style="margin-left: auto;"><?php echo __('nav_logout'); ?></a>
        <?php } else { ?>
            <a href="/login"
                class="w3-bar-item w3-button w3-hide-small w3-padding-large w3-right-align-flex <?php echo activeClass('/login'); ?>"
                style="margin-left: auto;"><?php echo __('nav_login'); ?></a>
            <a href="/register"
                class="w3-bar-item w3-button w3-hide-small w3-padding-large <?php echo activeClass('/register'); ?>"><?php echo __('nav_register'); ?></a>
        <?php } ?>

        <!-- Language Switcher -->
        <a href="<?php echo getLangUrl('fr'); ?>"
            class="w3-bar-item w3-button w3-hide-small w3-padding-large lang-btn">FR</a>
        <a href="<?php echo getLangUrl('en'); ?>"
            class="w3-bar-item w3-button w3-hide-small w3-padding-large lang-btn">EN</a>
    </div>

    <!-- Mobile menu -->
    <div id="navDemo" class="w3-bar-block w3-white w3-hide w3-hide-large w3-hide-medium w3-large">
        <a href="/"
            class="w3-bar-item w3-button w3-padding-large <?php echo activeClass('/'); ?>"><?php echo __('nav_home'); ?></a>
        <a href="/about"
            class="w3-bar-item w3-button w3-padding-large <?php echo activeClass('/about'); ?>"><?php echo __('nav_about'); ?></a>
        <a href="/doctors"
            class="w3-bar-item w3-button w3-padding-large <?php echo activeClass('/doctors'); ?>"><?php echo __('nav_doctors'); ?></a>
        <?php if ($isLoggedIn) { ?>
            <a href="/dashboard"
                class="w3-bar-item w3-button w3-padding-large <?php echo activeClass('/dashboard'); ?>"><?php echo __('nav_dashboard'); ?></a>
            <?php if ($userType === 'admin') { ?>
                <a href="/admin_doctors"
                    class="w3-bar-item w3-button w3-padding-large <?php echo activeClass('/admin_doctors'); ?>"><?php echo __('dashboard_manage_docs'); ?></a>
            <?php } ?>
            <a href="javascript:void(0)" onclick="openLogoutModal()"
                class="w3-bar-item w3-button w3-padding-large"><?php echo __('nav_logout'); ?></a>
        <?php } else { ?>
            <a href="/login"
                class="w3-bar-item w3-button w3-padding-large <?php echo activeClass('/login'); ?>"><?php echo __('nav_login'); ?></a>
            <a href="/register"
                class="w3-bar-item w3-button w3-padding-large <?php echo activeClass('/register'); ?>"><?php echo __('nav_register'); ?></a>
        <?php } ?>
        <div class="w3-bar-item w3-light-grey">
            <a href="<?php echo getLangUrl('fr'); ?>" class="w3-button lang-btn">FR</a>
            <a href="<?php echo getLangUrl('en'); ?>" class="w3-button lang-btn">EN</a>
        </div>
    </div>
</div>

<!-- Logout Confirmation Modal -->
<div id="logoutModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fa fa-sign-out"></i> <?php echo __('nav_logout'); ?></h3>
        </div>
        <div class="modal-body">
            <p>Êtes-vous sûr(e) de vouloir vous déconnecter ?</p>
        </div>
        <div class="modal-actions">
            <button class="btn-cancel-logout" onclick="closeLogoutModal()">Annuler</button>
            <a href="/logout" class="btn-confirm-logout">Oui se déconnecter</a>
        </div>
    </div>
</div>

<style>
    /* Custom styles removed - using standard W3.CSS */
</style>

<script>
    // Toggle mobile menu
    function myFunction() {
        var x = document.getElementById("navDemo");
        if (x.className.indexOf("w3-show") == -1) {
            x.className += " w3-show";
        } else {
            x.className = x.className.replace(" w3-show", "");
        }
    }

    // Modal Logic
    function openLogoutModal() {
        document.getElementById('logoutModal').classList.add('show');
    }

    function closeLogoutModal() {
        document.getElementById('logoutModal').classList.remove('show');
    }

    // Close modal if clicking outside
    window.onclick = function (event) {
        var modal = document.getElementById('logoutModal');
        if (event.target == modal) {
            closeLogoutModal();
        }
    }
</script>