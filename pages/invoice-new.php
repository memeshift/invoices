<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
requireAuth();

$pageTitle = 'New Invoice';

// Default values
$invoice = [
    'id'             => 0,
    'invoice_number' => '',
    'client_name'    => '',
    'client_email'   => '',
    'client_address' => '',
    'currency'       => 'EUR',
    'issue_date'     => date('Y-m-d'),
    'due_date'       => date('Y-m-d', strtotime('+30 days')),
    'status'         => 'draft',
    'notes'          => '',
    'subtotal'       => '0.00',
    'total'          => '0.00',
];

$items = [
    ['description' => '', 'quantity' => '1', 'rate' => '', 'amount' => '0.00'],
];

require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-heading">
    <h2>New Invoice</h2>
    <a href="<?= SITE_URL ?>/pages/dashboard.php" class="btn btn-ghost">← Back</a>
</div>

<?php include dirname(__DIR__) . '/includes/invoice-form.php'; ?>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
<script src="<?= SITE_URL ?>/assets/js/invoice.js"></script>
