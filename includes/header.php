<?php
// Included at the top of every protected page
// $pageTitle must be set before including this file
declare(strict_types=1);
if (!defined('SITE_URL')) {
    require_once dirname(__DIR__) . '/config/bootstrap.php';
}
requireAuth();
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Invoices') ?> — Memeshift</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,600;0,700;1,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>

<header class="site-header">
    <div class="header-inner">
        <a href="<?= SITE_URL ?>/pages/dashboard.php" class="site-logo">
            <span class="logo-mark">M</span>
            <span class="logo-text">Memeshift <em>Invoices</em></span>
        </a>
        <nav class="site-nav">
            <a href="<?= SITE_URL ?>/pages/dashboard.php">Dashboard</a>
            <a href="<?= SITE_URL ?>/pages/invoice-new.php" class="btn btn-sm btn-primary">+ New Invoice</a>
            <a href="<?= SITE_URL ?>/auth/logout.php" class="nav-logout">Sign out</a>
        </nav>
    </div>
</header>

<main class="site-main">
    <div class="container">

    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>">
            <?= e($flash['message']) ?>
        </div>
    <?php endif; ?>
