<?php
require_once 'config/db.php';

$email = 'jean.dupont@test.com';
$password = "fZdv#zan'e0euICz";
$newHash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE PATIENT SET password_hash = :h WHERE email = :e");
$stmt->execute(['h' => $newHash, 'e' => $email]);

echo "Updated password hash for $email to match '$password'.";
