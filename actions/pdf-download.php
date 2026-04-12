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

// ── Render PDF HTML template ─────────────────
ob_start();
require dirname(__DIR__) . '/templates/invoice-pdf.php';
$html = ob_get_clean();

// ── DomPDF ───────────────────────────────────
$vendorAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($vendorAutoload)) {
    die('<p style="font-family:sans-serif;padding:2rem;color:#900">
         <strong>DomPDF not installed.</strong><br>
         SSH into your server and run: <code>composer install</code>
         in the invoice app directory.
    </p>');
}

require_once $vendorAutoload;

use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('defaultFont',       'serif');
$options->set('isRemoteEnabled',   true);
$options->set('isHtml5ParserEnabled', true);
$options->set('fontDir',           dirname(__DIR__) . '/storage/font-cache/');
$options->set('fontCache',         dirname(__DIR__) . '/storage/font-cache/');
$options->set('tempDir',           sys_get_temp_dir());
$options->set('chroot',            dirname(__DIR__));

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = $invoice['invoice_number'] . '.pdf';

$dompdf->stream($filename, [
    'Attachment' => true,
]);
exit;
