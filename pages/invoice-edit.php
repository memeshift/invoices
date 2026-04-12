<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
requireAuth();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    setFlash('error', 'Invalid invoice ID.');
    redirect(SITE_URL . '/pages/dashboard.php');
}

$invoice = getInvoice($id);
if (!$invoice) {
    setFlash('error', 'Invoice not found.');
    redirect(SITE_URL . '/pages/dashboard.php');
}

$items = getInvoiceItems($id);
if (empty($items)) {
    $items = [['description' => '', 'quantity' => '1', 'rate' => '', 'amount' => '0.00']];
}

$pageTitle = 'Edit ' . $invoice['invoice_number'];
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-heading">
    <div>
        <h2><?= e($invoice['invoice_number']) ?></h2>
        <span class="badge badge-<?= e($invoice['status']) ?> badge-lg">
            <?= e(statusLabel($invoice['status'])) ?>
        </span>
    </div>
    <div class="heading-actions">
        <a href="<?= SITE_URL ?>/actions/pdf-download.php?id=<?= $id ?>"
           class="btn btn-ghost" target="_blank">📄 Download PDF</a>

        <?php if ($invoice['status'] !== 'paid'): ?>
        <form method="POST" action="<?= SITE_URL ?>/actions/invoice-status.php"
              style="display:inline"
              onsubmit="return confirm('Mark as paid?')">
            <?= csrfField() ?>
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="status" value="paid">
            <input type="hidden" name="redirect" value="edit">
            <button type="submit" class="btn btn-success">✅ Mark as Paid</button>
        </form>
        <?php endif; ?>

        <form method="POST" action="<?= SITE_URL ?>/actions/invoice-status.php"
              style="display:inline">
            <?= csrfField() ?>
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="redirect" value="edit">
            <label class="status-select-label">Status:
                <select name="status" onchange="this.form.submit()" class="status-select">
                    <?php foreach (['draft','sent','paid','overdue'] as $s): ?>
                        <option value="<?= $s ?>" <?= $invoice['status'] === $s ? 'selected' : '' ?>>
                            <?= statusLabel($s) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </form>

        <a href="<?= SITE_URL ?>/pages/dashboard.php" class="btn btn-ghost">← Back</a>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/invoice-form.php'; ?>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
<script src="<?= SITE_URL ?>/assets/js/invoice.js"></script>
