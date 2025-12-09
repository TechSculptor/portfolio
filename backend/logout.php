<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'helpers/TranslationHelper.php';

// Supprimer toutes les variables de session
session_unset();

// Détruire la session
session_destroy();

// Rediriger vers la page de connexion
header("Location: " . _route('login'));
exit;
?>