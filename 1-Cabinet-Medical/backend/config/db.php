<?php
// Database configuration for PostgreSQL
$host = getenv('DB_HOST') ?: 'db';
$port = getenv('DB_PORT') ?: '5432';
$dbname = getenv('DB_NAME') ?: 'cabinet_medical';
$username = getenv('DB_USER') ?: 'postgres';
$password = getenv('DB_PASSWORD') ?: 'postgres';

try {
    // Create PDO connection to PostgreSQL
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $conn = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>