<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    /* ── Local fonts (DomPDF loads from disk — no Google Fonts CDN) ── */
    @font-face {
        font-family: 'Lora';
        font-style: normal;
        font-weight: 400;
        src: url('<?= SITE_URL ?>/assets/fonts/Lora-Regular.ttf') format('truetype');
    }
    @font-face {
        font-family: 'Lora';
        font-style: italic;
        font-weight: 400;
        src: url('<?= SITE_URL ?>/assets/fonts/Lora-Italic.ttf') format('truetype');
    }
    @font-face {
        font-family: 'Lora';
        font-style: normal;
        font-weight: 600;
        src: url('<?= SITE_URL ?>/assets/fonts/Lora-SemiBold.ttf') format('truetype');
    }
    @font-face {
        font-family: 'Lora';
        font-style: normal;
        font-weight: 700;
        src: url('<?= SITE_URL ?>/assets/fonts/Lora-Bold.ttf') format('truetype');
    }
    @font-face {
        font-family: 'Lora';
        font-style: italic;
        font-weight: 700;
        src: url('<?= SITE_URL ?>/assets/fonts/Lora-BoldItalic.ttf') format('truetype');
    }

    /* ── Reset ── */
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: 'Lora', Georgia, 'Times New Roman', serif;
        font-size: 11pt;
        color: #404040;
        background: #fff;
        line-height: 1.5;
    }

    /* ── Page layout ── */
    .page {
        padding: 0;
        width: 100%;
    }

    /* ── Header band ── */
    .pdf-header {
        background: #FAC946;
        padding: 28pt 36pt 22pt;
        border-bottom: 4pt solid #F90002;
    }

    .header-top {
        display: table;
        width: 100%;
    }

    .header-left {
        display: table-cell;
        vertical-align: top;
        width: 55%;
    }

    .header-right {
        display: table-cell;
        vertical-align: top;
        text-align: right;
        width: 45%;
    }

    .freelancer-name {
        font-family: 'Lora', Georgia, serif;
        font-size: 18pt;
        font-weight: 700;
        color: #333333;
        letter-spacing: -0.3pt;
        margin-bottom: 2pt;
    }

    .freelancer-company {
        font-family: 'Lora', Georgia, serif;
        font-size: 11pt;
        color: #404040;
    }

    .freelancer-detail {
        font-family: 'Lora', Georgia, serif;
        font-size: 9pt;
        color: #555;
        line-height: 1.6;
        margin-top: 6pt;
    }

    .invoice-label {
        font-family: 'Lora', Georgia, serif;
        font-size: 28pt;
        font-weight: 700;
        color: #333333;
        letter-spacing: -1pt;
        text-transform: uppercase;
        line-height: 1;
    }

    .invoice-number {
        font-family: 'Courier New', monospace;
        font-size: 11pt;
        color: #007998;
        margin-top: 4pt;
    }

    /* ── Meta row (dates + client) ── */
    .meta-section {
        padding: 20pt 36pt;
        display: table;
        width: 100%;
        border-bottom: 1.5pt solid #F90002;
    }

    .meta-left {
        display: table-cell;
        width: 55%;
        vertical-align: top;
    }

    .meta-right {
        display: table-cell;
        width: 45%;
        vertical-align: top;
    }

    .meta-block {
        margin-bottom: 10pt;
    }

    .meta-label {
        font-family: 'Courier New', monospace;
        font-size: 7.5pt;
        text-transform: uppercase;
        letter-spacing: 1pt;
        color: #007998;
        margin-bottom: 2pt;
    }

    .meta-value {
        font-family: 'Lora', Georgia, serif;
        font-size: 10.5pt;
        color: #333333;
        font-weight: 700;
    }

    .meta-value.normal {
        font-weight: 400;
        color: #404040;
    }

    .bill-to-label {
        font-family: 'Courier New', monospace;
        font-size: 7.5pt;
        text-transform: uppercase;
        letter-spacing: 1pt;
        color: #007998;
        margin-bottom: 4pt;
    }

    .bill-to-name {
        font-family: 'Lora', Georgia, serif;
        font-size: 12pt;
        font-weight: 700;
        color: #333333;
    }

    .bill-to-address {
        font-family: 'Lora', Georgia, serif;
        font-size: 9.5pt;
        color: #404040;
        line-height: 1.6;
        margin-top: 3pt;
        white-space: pre-line;
    }

    /* ── Line items table ── */
    .items-section {
        padding: 0 36pt;
        margin-top: 6pt;
    }

    .items-table {
        width: 100%;
        border-collapse: collapse;
    }

    .items-table thead tr {
        background: #333333;
        color: #FAC946;
    }

    .items-table thead th {
        padding: 7pt 10pt;
        font-family: 'Courier New', monospace;
        font-size: 8pt;
        text-transform: uppercase;
        letter-spacing: 0.8pt;
        font-weight: normal;
        text-align: left;
    }

    .items-table thead th.right {
        text-align: right;
    }

    .items-table tbody tr {
        border-bottom: 0.5pt solid #e8e8e8;
    }

    .items-table tbody tr:nth-child(even) {
        background: #fafafa;
    }

    .items-table tbody td {
        font-family: 'Lora', Georgia, serif;
        padding: 8pt 10pt;
        font-size: 10pt;
        vertical-align: top;
    }

    .items-table tbody td.right {
        text-align: right;
        font-family: 'Courier New', monospace;
    }

    .items-table tbody td.mono {
        font-family: 'Courier New', monospace;
    }

    .col-desc  { width: 50%; }
    .col-qty   { width: 12%; text-align: center; }
    .col-rate  { width: 18%; text-align: right; }
    .col-amt   { width: 20%; text-align: right; }

    /* ── Totals ── */
    .totals-section {
        padding: 0 36pt;
        margin-top: 4pt;
    }

    .totals-table {
        width: 100%;
    }

    .totals-table td {
        font-family: 'Lora', Georgia, serif;
        padding: 3pt 10pt;
        font-size: 10pt;
    }

    .totals-table .label-cell {
        text-align: right;
        color: #555;
        width: 80%;
    }

    .totals-table .value-cell {
        text-align: right;
        font-family: 'Courier New', monospace;
        font-size: 10pt;
        width: 20%;
    }

    .totals-row-total {
        border-top: 2pt solid #F90002;
        margin-top: 4pt;
    }

    .totals-table .total-label {
        font-family: 'Lora', Georgia, serif;
        font-size: 12pt;
        font-weight: 700;
        color: #333333;
        text-align: right;
        padding-top: 6pt;
    }

    .totals-table .total-value {
        font-size: 14pt;
        font-weight: 700;
        color: #007998;
        text-align: right;
        font-family: 'Courier New', monospace;
        padding-top: 6pt;
    }

    /* ── VAT notice ── */
    .vat-notice-section {
        padding: 10pt 36pt 0;
    }

    .vat-notice {
        font-family: 'Lora', Georgia, serif;
        font-size: 8pt;
        color: #777;
        font-style: italic;
        border-left: 2pt solid #FAC946;
        padding-left: 8pt;
        line-height: 1.5;
    }

    /* ── Notes ── */
    .notes-section {
        padding: 14pt 36pt 0;
    }

    .notes-label {
        font-family: 'Courier New', monospace;
        font-size: 7.5pt;
        text-transform: uppercase;
        letter-spacing: 1pt;
        color: #007998;
        margin-bottom: 4pt;
    }

    .notes-body {
        font-family: 'Lora', Georgia, serif;
        font-size: 9.5pt;
        color: #404040;
        line-height: 1.6;
        white-space: pre-line;
    }

    /* ── Banking / payment details ── */
    .bank-section {
        padding: 14pt 36pt 0;
        border-top: 1pt solid #e8e8e8;
        margin-top: 14pt;
    }

    .bank-grid {
        display: table;
        width: 100%;
    }

    .bank-col {
        display: table-cell;
        vertical-align: top;
        width: 33.33%;
    }

    .bank-label {
        font-family: 'Courier New', monospace;
        font-size: 7pt;
        text-transform: uppercase;
        letter-spacing: 0.8pt;
        color: #888;
        margin-bottom: 2pt;
    }

    .bank-value {
        font-family: 'Courier New', monospace;
        font-size: 9.5pt;
        color: #333;
    }

    /* ── Footer band ── */
    .pdf-footer {
        background: #FAC946;
        border-top: 4pt solid #F90002;
        padding: 10pt 36pt;
        margin-top: 20pt;
        display: table;
        width: 100%;
    }

    .footer-left {
        display: table-cell;
        font-family: 'Lora', Georgia, serif;
        font-size: 8.5pt;
        color: #333;
        vertical-align: middle;
    }

    .footer-right {
        display: table-cell;
        text-align: right;
        font-family: 'Lora', Georgia, serif;
        font-size: 8.5pt;
        color: #333;
        vertical-align: middle;
    }

    .status-stamp {
        display: inline-block;
        border: 3pt solid #007998;
        color: #007998;
        font-family: 'Courier New', monospace;
        font-size: 14pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 2pt;
        padding: 4pt 10pt;
        transform: rotate(-12deg);
        opacity: 0.75;
        float: right;
        margin: -40pt 36pt 0 0;
    }

    .status-stamp.paid {
        border-color: #1a7a3c;
        color: #1a7a3c;
    }
</style>
</head>
<body>
<div class="page">

    <!-- ── Header ── -->
    <div class="pdf-header">
        <div class="header-top">
            <div class="header-left">
                <div class="freelancer-name"><?= e(FREELANCER_NAME) ?></div>
                <?php if (FREELANCER_COMPANY): ?>
                    <div class="freelancer-company"><?= e(FREELANCER_COMPANY) ?></div>
                <?php endif; ?>
                <div class="freelancer-detail">
                    <?php if (FREELANCER_ADDR1): ?><?= e(FREELANCER_ADDR1) ?><br><?php endif; ?>
                    <?php if (FREELANCER_ADDR2): ?><?= e(FREELANCER_ADDR2) ?><br><?php endif; ?>
                    <?php if (FREELANCER_EMAIL): ?><?= e(FREELANCER_EMAIL) ?><br><?php endif; ?>
                    <?php if (FREELANCER_PHONE): ?><?= e(FREELANCER_PHONE) ?><?php endif; ?>
                </div>
            </div>
            <div class="header-right">
                <div class="invoice-label">Invoice</div>
                <div class="invoice-number"><?= e($invoice['invoice_number']) ?></div>
            </div>
        </div>
    </div>

    <!-- ── Status stamp (if paid) ── -->
    <?php if ($invoice['status'] === 'paid'): ?>
        <div class="status-stamp paid">PAID</div>
    <?php elseif ($invoice['status'] === 'overdue'): ?>
        <div class="status-stamp">OVERDUE</div>
    <?php endif; ?>

    <!-- ── Meta row ── -->
    <div class="meta-section">
        <div class="meta-left">
            <div class="bill-to-label">Bill To</div>
            <div class="bill-to-name"><?= e($invoice['client_name']) ?></div>
            <?php if ($invoice['client_email']): ?>
                <div class="bill-to-address"><?= e($invoice['client_email']) ?></div>
            <?php endif; ?>
            <?php if ($invoice['client_address']): ?>
                <div class="bill-to-address"><?= e($invoice['client_address']) ?></div>
            <?php endif; ?>
        </div>
        <div class="meta-right">
            <div class="meta-block">
                <div class="meta-label">Issue Date</div>
                <div class="meta-value"><?= e(date('d M Y', strtotime($invoice['issue_date']))) ?></div>
            </div>
            <div class="meta-block">
                <div class="meta-label">Due Date</div>
                <div class="meta-value"><?= e(date('d M Y', strtotime($invoice['due_date']))) ?></div>
            </div>
            <?php if ($invoice['paid_date']): ?>
            <div class="meta-block">
                <div class="meta-label">Paid Date</div>
                <div class="meta-value"><?= e(date('d M Y', strtotime($invoice['paid_date']))) ?></div>
            </div>
            <?php endif; ?>
            <div class="meta-block">
                <div class="meta-label">Currency</div>
                <div class="meta-value"><?= e($invoice['currency']) ?></div>
            </div>
        </div>
    </div>

    <!-- ── Line Items ── -->
    <div class="items-section">
        <table class="items-table">
            <thead>
                <tr>
                    <th class="col-desc">Description</th>
                    <th class="col-qty right">Qty</th>
                    <th class="col-rate right">Rate (<?= currencySymbol($invoice['currency']) ?>)</th>
                    <th class="col-amt right">Amount (<?= currencySymbol($invoice['currency']) ?>)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= e($item['description']) ?></td>
                    <td class="right mono"><?= rtrim(rtrim(number_format((float)$item['quantity'], 2), '0'), '.') ?></td>
                    <td class="right mono"><?= currencySymbol($invoice['currency']) ?><?= number_format((float)$item['rate'], 2) ?></td>
                    <td class="right mono"><?= currencySymbol($invoice['currency']) ?><?= number_format((float)$item['amount'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- ── Totals ── -->
    <div class="totals-section">
        <table class="totals-table">
            <tr>
                <td class="label-cell">Subtotal</td>
                <td class="value-cell"><?= currencySymbol($invoice['currency']) ?><?= number_format((float)$invoice['subtotal'], 2) ?></td>
            </tr>
            <tr class="totals-row-total">
                <td class="total-label">Total Due</td>
                <td class="total-value"><?= currencySymbol($invoice['currency']) ?><?= number_format((float)$invoice['total'], 2) ?></td>
            </tr>
        </table>
    </div>

    <!-- ── VAT Notice ── -->
    <div class="vat-notice-section">
        <div class="vat-notice">
            Kein Umsatzsteuerausweis aufgrund Anwendung der Kleinunternehmerregelung gemäß § 19 UStG.
        </div>
    </div>

    <!-- ── Notes ── -->
    <?php if (trim($invoice['notes'])): ?>
    <div class="notes-section">
        <div class="notes-label">Notes &amp; Payment Instructions</div>
        <div class="notes-body"><?= e($invoice['notes']) ?></div>
    </div>
    <?php endif; ?>

    <!-- ── Bank Details ── -->
    <?php if (FREELANCER_IBAN || FREELANCER_BANK): ?>
    <div class="bank-section">
        <div class="bank-grid">
            <?php if (FREELANCER_BANK): ?>
            <div class="bank-col">
                <div class="bank-label">Bank</div>
                <div class="bank-value"><?= e(FREELANCER_BANK) ?></div>
            </div>
            <?php endif; ?>
            <?php if (FREELANCER_IBAN): ?>
            <div class="bank-col">
                <div class="bank-label">IBAN</div>
                <div class="bank-value"><?= e(FREELANCER_IBAN) ?></div>
            </div>
            <?php endif; ?>
            <?php if (FREELANCER_BIC): ?>
            <div class="bank-col">
                <div class="bank-label">BIC / SWIFT</div>
                <div class="bank-value"><?= e(FREELANCER_BIC) ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── Footer ── -->
    <div class="pdf-footer">
        <div class="footer-left">
            <?= e(FREELANCER_NAME) ?>
            <?php if (FREELANCER_WEBSITE): ?> · <?= e(FREELANCER_WEBSITE) ?><?php endif; ?>
        </div>
        <div class="footer-right">
            Invoice <?= e($invoice['invoice_number']) ?>
        </div>
    </div>

</div><!-- /.page -->
</body>
</html>
