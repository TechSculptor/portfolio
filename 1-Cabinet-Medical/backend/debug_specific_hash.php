<?php
$password = "fZdv#zan'e0euICz";
$hash = '$2y$10$tbgIOzNse5vUkFoIGkMQzeMjtO9t2Ovktvhaxt.DGx5NymGq3yw4K';

echo "Testing specific password verification:\n";
echo "Password: [$password]\n";
echo "Hash:     [$hash]\n\n";


$variations = [
    'Original' => $password,
    'Escaped Quote (\')' => str_replace("'", "\'", $password),
    'HTML Entity (&#039;)' => str_replace("'", "&#039;", $password),
    'With Slashes (addslashes)' => addslashes($password),
    'Trimmed' => trim($password)
];

foreach ($variations as $name => $p) {
    echo "Testing [$name]: [$p]\n";
    if (password_verify($p, $hash)) {
        echo "MATCH FOUND with variation: $name !!!\n";
        echo "The password stored in DB is actually: $p\n";
        exit;
    }
}
echo "NO MATCH found for any variation.\n";
