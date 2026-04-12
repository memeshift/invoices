<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/pages/dashboard.php');
}

verifyCsrf();

$id       = (int)($_POST['id']     ?? 0);
$status   = $_POST['status']       ?? '';
$goBack   = $_POST['redirect']     ?? 'dashboard';

$allowed = ['draft', 'sent', 'paid', 'overdue'];
if ($id <= 0 || !in_array($status, $allowed, true)) {
    setFlash('error', 'Invalid request.');
    redirect(SITE_URL . '/pages/dashboard.php');
}

$invoice = getInvoice($id);
if (!$invoice) {
    setFlash('error', 'Invoice not found.');
    redirect(SITE_URL . '/pages/dashboard.php');
}

try {
    $db = getDB();

    if ($status === 'paid') {
        $db->prepare(
            'UPDATE invoices SET status = ?, paid_date = CURDATE(), updated_at = NOW() WHERE id = ?'
        )->execute(['paid', $id]);
    } else {
        $db->prepare(
            'UPDATE invoices SET status = ?, paid_date = NULL, updated_at = NOW() WHERE id = ?'
        )->execute([$status, $id]);
    }

    setFlash('success', 'Invoice ' . $invoice['invoice_number'] . ' marked as ' . statusLabel($status) . '.');
} catch (Throwable $e) {
    error_log('[InvoiceApp] Status update error: ' . $e->getMessage());
    setFlash('error', 'Failed to update invoice status.');
}

if ($goBack === 'edit') {
    redirect(SITE_URL . '/pages/invoice-edit.php?id=' . $id);
}
redirect(SITE_URL . '/pages/dashboard.php');
