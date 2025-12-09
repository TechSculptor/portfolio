<?php
// Ensure session is started for language persistence
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

class TranslationHelper
{
    private static $translations = null;
    private static $lang = 'fr';
    private static $routes = [
        'home' => ['fr' => '/accueil', 'en' => '/home'],
        'login' => ['fr' => '/connexion', 'en' => '/login'],
        'register' => ['fr' => '/inscription', 'en' => '/register'],
        'dashboard' => ['fr' => '/tableau-de-bord', 'en' => '/dashboard'],
        'book_appointment' => ['fr' => '/prendre-rendez-vous', 'en' => '/book-appointment'],
        'doctors' => ['fr' => '/medecins', 'en' => '/doctors'],
        'admin_doctors' => ['fr' => '/admin-medecins', 'en' => '/admin-doctors'],
        'about' => ['fr' => '/a-propos', 'en' => '/about'],
        'logout' => ['fr' => '/deconnexion', 'en' => '/logout'],
        'forgot_password' => ['fr' => '/mot-de-passe-oublie', 'en' => '/forgot-password'],
        'reset_password' => ['fr' => '/reinitialiser-mot-de-passe', 'en' => '/reset-password'],
        'doctor_availability' => ['fr' => '/disponibilites', 'en' => '/availability'],
        'verify_email' => ['fr' => '/verification-email', 'en' => '/verify-email'],
    ];

    public static function init()
    {
        // Check for URL override
        if (isset($_GET['lang']) && in_array($_GET['lang'], ['fr', 'en'])) {
            $_SESSION['lang'] = $_GET['lang'];
        }

        // Set current language
        self::$lang = $_SESSION['lang'] ?? 'fr';

        // Load translations
        $path = __DIR__ . '/../lang/' . self::$lang . '.php';
        if (file_exists($path)) {
            self::$translations = require $path;
        } else {
            self::$translations = [];
        }
    }

    public static function get($key)
    {
        if (self::$translations === null) {
            self::init();
        }
        return self::$translations[$key] ?? $key;
    }

    public static function getLang()
    {
        if (self::$translations === null) {
            self::init();
        }
        return self::$lang;
    }
    public static function getRoute($key, $params = [])
    {
        if (self::$translations === null) {
            self::init(); // Ensure lang is set
        }
        $route = self::$routes[$key][self::$lang] ?? self::$routes[$key]['fr'] ?? $key;

        if (!empty($params)) {
            $queryString = http_build_query($params);
            $route .= '?' . $queryString;
        }

        return $route;
    }
}

// Global short helper function
function __($key)
{
    return TranslationHelper::get($key);
}

function getLang()
{
    return TranslationHelper::getLang();
}

function _route($key, $params = [])
{
    return TranslationHelper::getRoute($key, $params);
}
?>