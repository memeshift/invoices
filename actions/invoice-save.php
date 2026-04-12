<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/pages/dashboard.php');
}

verifyCsrf();

$db     = getDB();
$id     = (int)($_POST['id'] ?? 0);
$action = $_POST['action'] ?? 'save';

// ── Sanitise inputs ──────────────────────────
$clientName    = trim($_POST['client_name']    ?? '');
$clientEmail   = trim($_POST['client_email']   ?? '');
$clientAddress = trim($_POST['client_address'] ?? '');
$currency      = in_array($_POST['currency'] ?? '', ['EUR','USD'], true) ? $_POST['currency'] : 'EUR';
$issueDate     = $_POST['issue_date'] ?? date('Y-m-d');
$dueDate       = $_POST['due_date']   ?? date('Y-m-d', strtotime('+30 days'));
$notes         = trim($_POST['notes'] ?? '');
$subtotal      = round((float)($_POST['subtotal'] ?? 0), 2);
$total         = round((float)($_POST['total']    ?? 0), 2);

// Validate required fields
if ($clientName === '') {
    setFlash('error', 'Client name is required.');
    redirect($id > 0
        ? SITE_URL . '/pages/invoice-edit.php?id=' . $id
        : SITE_URL . '/pages/invoice-new.php'
    );
}

// Validate dates
$issueDate = date('Y-m-d', strtotime($issueDate)) ?: date('Y-m-d');
$dueDate   = date('Y-m-d', strtotime($dueDate))   ?: date('Y-m-d', strtotime('+30 days'));

// ── Line items ───────────────────────────────
$rawItems = $_POST['items'] ?? [];
$items = [];

foreach ($rawItems as $item) {
    $desc   = trim($item['description'] ?? '');
    $qty    = round((float)($item['quantity'] ?? 1), 2);
    $rate   = round((float)($item['rate']     ?? 0), 2);
    $amount = round($qty * $rate, 2);

    if ($desc === '' && $rate == 0) continue; // skip empty rows

    $items[] = [
        'description' => $desc,
        'quantity'    => $qty,
        'rate'        => $rate,
        'amount'      => $amount,
    ];
}

// Recalculate totals server-side (never trust client)
$subtotal = round(array_sum(array_column($items, 'amount')), 2);
$total    = $subtotal; // No VAT (Kleinunternehmerregelung)

try {
    if ($id === 0) {
        // ── Create new invoice ───────────────
        $invoiceNumber = generateInvoiceNumber();

        $stmt = $db->prepare(
            'INSERT INTO invoices
             (invoice_number, client_name, client_email, client_address,
              currency, issue_date, due_date, notes, subtotal, total, status)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            $invoiceNumber, $clientName, $clientEmail, $clientAddress,
            $currency, $issueDate, $dueDate, $notes, $subtotal, $total, 'draft',
        ]);
        $id = (int) $db->lastInsertId();

    } else {
        // ── Update existing invoice ──────────
        $existing = getInvoice($id);
        if (!$existing) {
            setFlash('error', 'Invoice not found.');
            redirect(SITE_URL . '/pages/dashboard.php');
        }

        $stmt = $db->prepare(
            'UPDATE invoices SET
                client_name=?, client_email=?, client_address=?,
                currency=?, issue_date=?, due_date=?, notes=?,
                subtotal=?, total=?, updated_at=NOW()
             WHERE id=?'
        );
        $stmt->execute([
            $clientName, $clientEmail, $clientAddress,
            $currency, $issueDate, $dueDate, $notes,
            $subtotal, $total, $id,
        ]);
    }

    // ── Save line items (delete + re-insert) ─
    $db->prepare('DELETE FROM invoice_items WHERE invoice_id = ?')->execute([$id]);

    $itemStmt = $db->prepare(
        'INSERT INTO invoice_items (invoice_id, description, quantity, rate, amount, sort_order)
         VALUES (?,?,?,?,?,?)'
    );
    foreach ($items as $order => $item) {
        $itemStmt->execute([
            $id, $item['description'], $item['quantity'],
            $item['rate'], $item['amount'], $order,
        ]);
    }

    setFlash('success', 'Invoice saved successfully.');

    // Save & Download PDF
    if ($action === 'save_download') {
        redirect(SITE_URL . '/actions/pdf-download.php?id=' . $id);
    }

    redirect(SITE_URL . '/pages/invoice-edit.php?id=' . $id);

} catch (Throwable $e) {
    error_log('[InvoiceApp] Save error: ' . $e->getMessage());
    setFlash('error', 'Failed to save invoice. Please try again.');
    redirect($id > 0
        ? SITE_URL . '/pages/invoice-edit.php?id=' . $id
        : SITE_URL . '/pages/invoice-new.php'
    );
}
