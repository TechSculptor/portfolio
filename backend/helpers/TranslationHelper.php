<?php
// Ensure session is started for language persistence
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

class TranslationHelper
{
    private static $translations = null;
    private static $lang = 'fr';

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
}

// Global short helper function
function __($key)
{
    return TranslationHelper::get($key);
}
?>