<?php
// Shared form partial for new + edit invoice
// Expects: $invoice (array), $items (array)
// $invoice['id'] === 0 means new
$isNew = ($invoice['id'] === 0);
$actionUrl = SITE_URL . '/actions/invoice-save.php';
?>

<form
    id="invoice-form"
    method="POST"
    action="<?= e($actionUrl) ?>"
    novalidate
>
    <?= csrfField() ?>
    <input type="hidden" name="id" value="<?= (int)$invoice['id'] ?>">

    <div class="form-grid">

        <!-- ── Left column ── -->
        <section class="form-section">
            <h3 class="section-title">Client Details</h3>

            <div class="field">
                <label for="client_name">Client / Company Name <span class="req">*</span></label>
                <input type="text" id="client_name" name="client_name" required
                    value="<?= e($invoice['client_name']) ?>">
            </div>

            <div class="field">
                <label for="client_email">Client Email</label>
                <input type="email" id="client_email" name="client_email"
                    value="<?= e($invoice['client_email']) ?>">
            </div>

            <div class="field">
                <label for="client_address">Client Address</label>
                <textarea id="client_address" name="client_address" rows="4"><?= e($invoice['client_address']) ?></textarea>
            </div>
        </section>

        <!-- ── Right column ── -->
        <section class="form-section">
            <h3 class="section-title">Invoice Details</h3>

            <div class="field-row">
                <div class="field">
                    <label for="issue_date">Issue Date <span class="req">*</span></label>
                    <input type="date" id="issue_date" name="issue_date" required
                        value="<?= e($invoice['issue_date']) ?>">
                </div>
                <div class="field">
                    <label for="due_date">Due Date <span class="req">*</span></label>
                    <input type="date" id="due_date" name="due_date" required
                        value="<?= e($invoice['due_date']) ?>">
                </div>
            </div>

            <div class="field">
                <label for="currency">Currency</label>
                <div class="currency-toggle-wrap">
                    <select id="currency" name="currency" class="currency-select" id="currencySelect">
                        <option value="EUR" <?= $invoice['currency'] === 'EUR' ? 'selected' : '' ?>>€ EUR — Euro</option>
                        <option value="USD" <?= $invoice['currency'] === 'USD' ? 'selected' : '' ?>>$ USD — US Dollar</option>
                    </select>
                    <div class="currency-preview">
                        <span class="currency-symbol-preview" id="currencySymbolPreview">
                            <?= $invoice['currency'] === 'EUR' ? '€' : '$' ?>
                        </span>
                        <span class="currency-name-preview" id="currencyNamePreview">
                            <?= $invoice['currency'] === 'EUR' ? 'Euro' : 'US Dollar' ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="field">
                <label for="notes">Notes / Payment Instructions</label>
                <textarea id="notes" name="notes" rows="4"><?= e($invoice['notes']) ?></textarea>
            </div>
        </section>

    </div><!-- /.form-grid -->

    <!-- ── Line Items ── -->
    <section class="form-section line-items-section">
        <div class="section-header">
            <h3 class="section-title">Line Items</h3>
        </div>

        <div class="line-items-table-wrap">
            <table class="line-items-table" id="lineItemsTable">
                <thead>
                    <tr>
                        <th class="col-desc">Description</th>
                        <th class="col-qty">Qty</th>
                        <th class="col-rate">
                            Rate (<span class="cur-sym"><?= $invoice['currency'] === 'EUR' ? '€' : '$' ?></span>)
                        </th>
                        <th class="col-amount">
                            Amount (<span class="cur-sym"><?= $invoice['currency'] === 'EUR' ? '€' : '$' ?></span>)
                        </th>
                        <th class="col-del"></th>
                    </tr>
                </thead>
                <tbody id="lineItemsBody">
                    <?php foreach ($items as $i => $item): ?>
                    <tr class="line-item-row" data-index="<?= $i ?>">
                        <td>
                            <input type="text"
                                name="items[<?= $i ?>][description]"
                                class="item-desc"
                                placeholder="Service or product description"
                                value="<?= e($item['description']) ?>"
                                required>
                        </td>
                        <td>
                            <input type="number"
                                name="items[<?= $i ?>][quantity]"
                                class="item-qty"
                                min="0.01" step="0.01"
                                value="<?= e((string)$item['quantity']) ?>"
                                required>
                        </td>
                        <td>
                            <div class="rate-input-wrap">
                                <span class="input-prefix cur-sym"><?= $invoice['currency'] === 'EUR' ? '€' : '$' ?></span>
                                <input type="number"
                                    name="items[<?= $i ?>][rate]"
                                    class="item-rate"
                                    min="0" step="0.01"
                                    value="<?= e((string)$item['rate']) ?>"
                                    placeholder="0.00"
                                    required>
                            </div>
                        </td>
                        <td>
                            <div class="amount-display">
                                <span class="cur-sym"><?= $invoice['currency'] === 'EUR' ? '€' : '$' ?></span><span class="item-amount"><?= number_format((float)$item['amount'], 2) ?></span>
                                <input type="hidden" name="items[<?= $i ?>][amount]" class="item-amount-hidden" value="<?= e((string)$item['amount']) ?>">
                            </div>
                        </td>
                        <td>
                            <button type="button" class="remove-item-btn" title="Remove row"
                                onclick="removeLineItem(this)">×</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="totals-row">
                        <td colspan="3" class="totals-label">Subtotal</td>
                        <td class="totals-value">
                            <span class="cur-sym"><?= $invoice['currency'] === 'EUR' ? '€' : '$' ?></span><span id="subtotalDisplay"><?= number_format((float)$invoice['subtotal'], 2) ?></span>
                            <input type="hidden" name="subtotal" id="subtotalInput" value="<?= e($invoice['subtotal']) ?>">
                        </td>
                        <td></td>
                    </tr>
                    <tr class="totals-row totals-row--total">
                        <td colspan="3" class="totals-label"><strong>Total</strong></td>
                        <td class="totals-value">
                            <strong>
                                <span class="cur-sym"><?= $invoice['currency'] === 'EUR' ? '€' : '$' ?></span><span id="totalDisplay"><?= number_format((float)$invoice['total'], 2) ?></span>
                            </strong>
                            <input type="hidden" name="total" id="totalInput" value="<?= e($invoice['total']) ?>">
                        </td>
                        <td></td>
                    </tr>
                    <tr class="vat-notice-row">
                        <td colspan="5">
                            <span class="vat-notice">
                                Kein Umsatzsteuerausweis aufgrund Anwendung der Kleinunternehmerregelung gemäß § 19 UStG.
                            </span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <button type="button" class="btn btn-ghost btn-add-item" onclick="addLineItem()">
            + Add Line Item
        </button>
    </section>

    <!-- ── Form Actions ── -->
    <div class="form-actions">
        <button type="submit" name="action" value="save" class="btn btn-primary btn-lg">
            💾 Save Invoice
        </button>
        <button type="submit" name="action" value="save_download" class="btn btn-secondary btn-lg">
            📄 Save &amp; Download PDF
        </button>
        <a href="<?= SITE_URL ?>/pages/dashboard.php" class="btn btn-ghost btn-lg">Cancel</a>
    </div>

</form>

<!-- Line item row template (hidden, cloned by JS) -->
<template id="lineItemTemplate">
    <tr class="line-item-row">
        <td>
            <input type="text" name="" class="item-desc"
                placeholder="Service or product description" required>
        </td>
        <td>
            <input type="number" name="" class="item-qty"
                min="0.01" step="0.01" value="1" required>
        </td>
        <td>
            <div class="rate-input-wrap">
                <span class="input-prefix cur-sym">€</span>
                <input type="number" name="" class="item-rate"
                    min="0" step="0.01" placeholder="0.00" required>
            </div>
        </td>
        <td>
            <div class="amount-display">
                <span class="cur-sym">€</span><span class="item-amount">0.00</span>
                <input type="hidden" name="" class="item-amount-hidden" value="0.00">
            </div>
        </td>
        <td>
            <button type="button" class="remove-item-btn" title="Remove row"
                onclick="removeLineItem(this)">×</button>
        </td>
    </tr>
</template>
