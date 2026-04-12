<?php
declare(strict_types=1);
require_once __DIR__ . '/config/bootstrap.php';

if (isLoggedIn()) {
    redirect(SITE_URL . '/pages/dashboard.php');
} else {
    redirect(SITE_URL . '/auth/login.php');
}
