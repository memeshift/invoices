<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/pages/dashboard.php');
}

verifyCsrf();

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    setFlash('error', 'Invalid invoice.');
    redirect(SITE_URL . '/pages/dashboard.php');
}

$invoice = getInvoice($id);
if (!$invoice) {
    setFlash('error', 'Invoice not found.');
    redirect(SITE_URL . '/pages/dashboard.php');
}

try {
    // Items are deleted via ON DELETE CASCADE
    getDB()->prepare('DELETE FROM invoices WHERE id = ?')->execute([$id]);
    setFlash('success', 'Invoice ' . $invoice['invoice_number'] . ' deleted.');
} catch (Throwable $e) {
    error_log('[InvoiceApp] Delete error: ' . $e->getMessage());
    setFlash('error', 'Failed to delete invoice.');
}

redirect(SITE_URL . '/pages/dashboard.php');
