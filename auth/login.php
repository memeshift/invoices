<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';

if (isLoggedIn()) {
    redirect(SITE_URL . '/pages/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Rate limiting: max 5 attempts per 15 min, keyed by IP
    if (isIpRateLimited()) {
        $error = 'Too many login attempts. Please wait 15 minutes.';
    } elseif (
        hash_equals(APP_USERNAME, $username) &&
        password_verify($password, APP_PASSWORD_HASH)
    ) {
        clearLoginAttempts();
        session_regenerate_id(true);
        $_SESSION['authenticated'] = true;
        redirect(SITE_URL . '/pages/dashboard.php');
    } else {
        recordFailedLogin();
        // Consistent timing to prevent user enumeration
        usleep(random_int(200000, 500000));
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Memeshift Invoices</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,600;0,700;1,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body class="login-page">

<div class="login-wrap">
    <div class="login-card">
        <div class="login-logo">
            <span class="login-mark">M</span>
            <h1>Memeshift<br><span>Invoices</span></h1>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="" novalidate>
            <?= csrfField() ?>

            <div class="field">
                <label for="username">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    autocomplete="username"
                    required
                    autofocus
                    value="<?= e($_POST['username'] ?? '') ?>"
                >
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="current-password"
                    required
                >
            </div>

            <button type="submit" class="btn btn-primary btn-full">Sign In</button>
        </form>
    </div>
</div>

</body>
</html>
