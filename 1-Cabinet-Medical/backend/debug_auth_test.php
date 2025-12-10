<?php
require_once __DIR__ . '/config/db.php';

$output = [];
$email = 'jean.dupont@test.com';
$password = 'd=9gyHG6@]*8fRWv';

$output[] = "Debugging Auth for: [$email]";
$output[] = "Password to test: [$password] (Length: " . strlen($password) . ")";

$stmt = $conn->prepare("SELECT * FROM PATIENT WHERE email = :email");
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

if (!$user) {
    $output[] = "USER NOT FOUND!";
} else {
    $output[] = "User found: " . $user['username'] . " (ID: " . $user['patient_id'] . ")";
    $output[] = "Stored Hash: [" . $user['password_hash'] . "]";
    $output[] = "Hash Length: " . strlen($user['password_hash']);
    $output[] = "Email Verified: " . ($user['email_verified'] ? 'Yes' : 'No');

    $verify = password_verify($password, $user['password_hash']);
    $output[] = "password_verify() Result: " . ($verify ? 'SUCCESS' : 'FAILURE');


    // Re-hash to see using PASSWORD_DEFAULT
    $newHash = password_hash($password, PASSWORD_DEFAULT);
    $output[] = "Generated New Hash: [$newHash]";
    $output[] = "New Hash Length: " . strlen($newHash);

    // Verify the new hash immediately in memory
    if (password_verify($password, $newHash)) {
        $output[] = "Immediate in-memory verification: SUCCESS";

        // Update DB
        $updateStmt = $conn->prepare("UPDATE PATIENT SET password_hash = :h WHERE patient_id = :id");
        $updateStmt->execute(['h' => $newHash, 'id' => $user['patient_id']]);
        $output[] = "Database updated with new hash.";

        // Re-fetch to verify storage
        $stmt->execute(['email' => $email]);
        $userRefreshed = $stmt->fetch();
        $output[] = "Refetched Hash: [" . $userRefreshed['password_hash'] . "]";

        if (password_verify($password, $userRefreshed['password_hash'])) {
            $output[] = "Final DB verification: SUCCESS! Login should work now.";
        } else {
            $output[] = "Final DB verification: FAILURE. DB might be corrupting data?";
        }

    } else {
        $output[] = "Immediate in-memory verification: FAILURE. System crypto issue?";
    }
}

file_put_contents(__DIR__ . '/debug_output.txt', implode("\n", $output));
echo "Done.";
