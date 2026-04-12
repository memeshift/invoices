<?php
// ─────────────────────────────────────────────
//  Password Hash Generator
//  Run via SSH: php setup/generate-password.php
//  Then paste the hash into .env as APP_PASSWORD_HASH
//  DELETE this file after use!
// ─────────────────────────────────────────────

if (PHP_SAPI !== 'cli') {
    die("Run this script from the command line only.\n");
}

echo "\n Memeshift Invoice — Password Setup\n";
echo " ────────────────────────────────────\n";
echo " Enter your desired password: ";

// Hide input on *nix systems
system('stty -echo');
$password = trim(fgets(STDIN));
system('stty echo');
echo "\n";

if (strlen($password) < 8) {
    echo " ✗ Password must be at least 8 characters.\n\n";
    exit(1);
}

$hash = password_hash($password, PASSWORD_ARGON2ID);

echo " ✓ Password hash generated!\n\n";
echo " Copy this line into your .env file:\n\n";
echo " APP_PASSWORD_HASH=" . $hash . "\n\n";
echo " ⚠  Delete this file after use: setup/generate-password.php\n\n";
