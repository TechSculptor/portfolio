<?php
require_once 'config/db.php';

$password = "fZdv#zan'e0euICz";
echo "Testing Full Cycle for password: [$password]\n";

// 1. Hash
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Generated Hash in PHP: [$hash]\n";

// 2. Insert into DB (Test User)
$testEmail = 'cycle.test@example.com';
$stmt = $conn->prepare("DELETE FROM PATIENT WHERE email = :e");
$stmt->execute(['e' => $testEmail]);

$stmt = $conn->prepare("INSERT INTO PATIENT (email, username, password_hash, first_name, last_name, email_verified) VALUES (:e, 'cycletest', :h, 'Cycle', 'Test', true)");
$stmt->execute(['e' => $testEmail, 'h' => $hash]);
echo "Inserted into DB.\n";

// 3. Fetch
$stmt = $conn->prepare("SELECT password_hash FROM PATIENT WHERE email = :e");
$stmt->execute(['e' => $testEmail]);
$row = $stmt->fetch();
$fetchedHash = $row['password_hash'];
echo "Fetched Hash from DB:  [$fetchedHash]\n";

// 4. Compare Strings
if ($hash === $fetchedHash) {
    echo "Hash String Identity: MATCH (DB preserved the hash correctly)\n";
} else {
    echo "Hash String Identity: MISMATCH (DB corrupted the hash)\n";
}

// 5. Verify
if (password_verify($password, $fetchedHash)) {
    echo "password_verify: SUCCESS\n";
} else {
    echo "password_verify: FAILURE\n";
}
