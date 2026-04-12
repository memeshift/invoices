<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
requireAuth();

// Auto-flag overdue invoices
updateInvoiceOverdue();

$db = getDB();

// Filters
$statusFilter   = $_GET['status']   ?? '';
$currencyFilter = $_GET['currency'] ?? '';

$where  = ['1=1'];
$params = [];

if (in_array($statusFilter, ['draft','sent','paid','overdue'], true)) {
    $where[]  = 'status = ?';
    $params[] = $statusFilter;
}
if (in_array($currencyFilter, ['EUR','USD'], true)) {
    $where[]  = 'currency = ?';
    $params[] = $currencyFilter;
}

$sql = 'SELECT * FROM invoices WHERE ' . implode(' AND ', $where) . ' ORDER BY created_at DESC';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$invoices = $stmt->fetchAll();

// Summary totals
$summaryStmt = $db->query(
    "SELECT
        COUNT(*) AS total_count,
        SUM(CASE WHEN status = 'paid' THEN total ELSE 0 END) AS total_paid,
        SUM(CASE WHEN status IN ('sent','overdue') THEN total ELSE 0 END) AS total_outstanding,
        SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) AS overdue_count
     FROM invoices"
);
$summary = $summaryStmt->fetch();

$pageTitle = 'Dashboard';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-heading">
    <h2>Dashboard</h2>
    <a href="<?= SITE_URL ?>/pages/invoice-new.php" class="btn btn-primary">+ New Invoice</a>
</div>

<!-- Summary cards -->
<div class="summary-grid">
    <div class="summary-card">
        <span class="summary-label">Total Invoices</span>
        <span class="summary-value"><?= (int) $summary['total_count'] ?></span>
    </div>
    <div class="summary-card summary-card--paid">
        <span class="summary-label">Total Paid</span>
        <span class="summary-value">€<?= number_format((float)$summary['total_paid'], 2, '.', ',') ?></span>
    </div>
    <div class="summary-card summary-card--outstanding">
        <span class="summary-label">Outstanding</span>
        <span class="summary-value">€<?= number_format((float)$summary['total_outstanding'], 2, '.', ',') ?></span>
    </div>
    <?php if ((int)$summary['overdue_count'] > 0): ?>
    <div class="summary-card summary-card--overdue">
        <span class="summary-label">Overdue</span>
        <span class="summary-value"><?= (int) $summary['overdue_count'] ?></span>
    </div>
    <?php endif; ?>
</div>

<!-- Filters -->
<div class="filter-bar">
    <span class="filter-label">Filter:</span>

    <?php
    $statuses   = ['' => 'All', 'draft' => 'Draft', 'sent' => 'Sent', 'paid' => 'Paid', 'overdue' => 'Overdue'];
    $currencies = ['' => 'All currencies', 'EUR' => '€ EUR', 'USD' => '$ USD'];
    ?>

    <div class="filter-group">
        <?php foreach ($statuses as $val => $label): ?>
            <a href="?status=<?= e($val) ?>&currency=<?= e($currencyFilter) ?>"
               class="filter-pill <?= $statusFilter === $val ? 'active' : '' ?>">
                <?= e($label) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="filter-group">
        <?php foreach ($currencies as $val => $label): ?>
            <a href="?status=<?= e($statusFilter) ?>&currency=<?= e($val) ?>"
               class="filter-pill <?= $currencyFilter === $val ? 'active' : '' ?>">
                <?= e($label) ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Invoice table -->
<?php if (empty($invoices)): ?>
    <div class="empty-state">
        <p>No invoices yet.</p>
        <a href="<?= SITE_URL ?>/pages/invoice-new.php" class="btn btn-primary">Create your first invoice</a>
    </div>
<?php else: ?>
<div class="table-wrap">
    <table class="invoice-table">
        <thead>
            <tr>
                <th>Invoice #</th>
                <th>Client</th>
                <th>Issue Date</th>
                <th>Due Date</th>
                <th>Currency</th>
                <th class="text-right">Amount</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($invoices as $inv): ?>
            <tr class="status-<?= e($inv['status']) ?>">
                <td class="mono">
                    <a href="<?= SITE_URL ?>/pages/invoice-edit.php?id=<?= (int)$inv['id'] ?>">
                        <?= e($inv['invoice_number']) ?>
                    </a>
                </td>
                <td><?= e($inv['client_name']) ?></td>
                <td class="mono"><?= e($inv['issue_date']) ?></td>
                <td class="mono"><?= e($inv['due_date']) ?></td>
                <td><span class="currency-tag"><?= e($inv['currency']) ?></span></td>
                <td class="text-right mono"><?= formatMoney((float)$inv['total'], $inv['currency']) ?></td>
                <td>
                    <span class="badge badge-<?= e($inv['status']) ?>">
                        <?= e(statusLabel($inv['status'])) ?>
                    </span>
                </td>
                <td class="actions-cell">
                    <a href="<?= SITE_URL ?>/pages/invoice-edit.php?id=<?= (int)$inv['id'] ?>"
                       class="action-link" title="Edit">✏️</a>

                    <a href="<?= SITE_URL ?>/actions/pdf-download.php?id=<?= (int)$inv['id'] ?>"
                       class="action-link" title="Download PDF" target="_blank">📄</a>

                    <?php if ($inv['status'] !== 'paid'): ?>
                    <form method="POST" action="<?= SITE_URL ?>/actions/invoice-status.php"
                          style="display:inline"
                          onsubmit="return confirm('Mark <?= e($inv['invoice_number']) ?> as paid?')">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= (int)$inv['id'] ?>">
                        <input type="hidden" name="status" value="paid">
                        <button type="submit" class="action-btn" title="Mark paid">✅</button>
                    </form>
                    <?php endif; ?>

                    <form method="POST" action="<?= SITE_URL ?>/actions/invoice-delete.php"
                          style="display:inline"
                          onsubmit="return confirm('Delete <?= e($inv['invoice_number']) ?>? This cannot be undone.')">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= (int)$inv['id'] ?>">
                        <button type="submit" class="action-btn action-btn--danger" title="Delete">🗑️</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
