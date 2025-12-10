<?php
require_once 'config/db.php';

$usernameOrEmail = 'jean.dupont@test.com';
$passwordCandidate = 'd=9gyHG6@]*8fRWv';

echo "=== DEBUG AUTHENTICATION ===\n";
echo "Testing: [$usernameOrEmail] with password length " . strlen($passwordCandidate) . "\n\n";

// Tables to check
$tables = [
    'PATIENT' => 'patient_id',
    'DOCTOR' => 'doctor_id',
    'ADMIN' => 'admin_id'
];

foreach ($tables as $table => $idCol) {
    echo "Checking Table: $table\n";
    $stmt = $conn->prepare("SELECT * FROM $table WHERE email = :u OR username = :u");
    $stmt->execute(['u' => $usernameOrEmail]);
    $users = $stmt->fetchAll();

    if (count($users) === 0) {
        echo "  No records found.\n";
    } else {
        foreach ($users as $user) {
            echo "  Found User ID: " . $user[$idCol] . "\n";
            echo "    Username: [" . $user['username'] . "]\n";
            echo "    Email:    [" . $user['email'] . "]\n";
            echo "    Hash:     [" . $user['password_hash'] . "]\n";

            $verify = password_verify($passwordCandidate, $user['password_hash']);
            if ($verify) {
                echo "    VERIFICATION: SUCCESS !!! (This user should login)\n";
            } else {
                echo "    VERIFICATION: FAILURE (Hash mismatch)\n";
            }
        }
    }
    echo "--------------------------\n";
}
echo "=== END DEBUG ===\n";
