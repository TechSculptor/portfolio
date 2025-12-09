<?php
// Write hashes to file to avoid terminal truncation
$output = "";

$passwords = [
    'admin123' => 'admin123',
    'doctor1' => 'doctor1',
    'doctor2' => 'doctor2',
    'doctor3' => 'doctor3'
];

foreach ($passwords as $name => $pwd) {
    $hash = password_hash($pwd, PASSWORD_DEFAULT);
    $output .= "$name: $hash\n";
}

file_put_contents('/var/www/html/hashes_output.txt', $output);
echo "Written to hashes_output.txt\n";
