/**
 * Memeshift Invoice Manager — invoice.js
 * Handles: line item add/remove, qty×rate→amount, live totals, currency switching
 */

'use strict';

// ── State ────────────────────────────────────
let itemIndex = document.querySelectorAll('#lineItemsBody .line-item-row').length;

// ── Init ─────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    recalcAll();
    bindExistingRows();

    // Currency switcher
    const currencySelect = document.getElementById('currency');
    if (currencySelect) {
        currencySelect.addEventListener('change', onCurrencyChange);
    }

    // Prevent accidental navigation away from unsaved changes
    let formDirty = false;
    const form = document.getElementById('invoice-form');
    if (form) {
        form.addEventListener('input', () => { formDirty = true; });
        form.addEventListener('submit', () => { formDirty = false; });
    }
    window.addEventListener('beforeunload', (e) => {
        if (formDirty) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
});

// ── Currency change ───────────────────────────
function onCurrencyChange() {
    const select = document.getElementById('currency');
    const sym    = select.value === 'EUR' ? '€' : '$';
    const name   = select.value === 'EUR' ? 'Euro' : 'US Dollar';

    // Update preview badge
    const symPreview  = document.getElementById('currencySymbolPreview');
    const namePreview = document.getElementById('currencyNamePreview');
    if (symPreview)  symPreview.textContent  = sym;
    if (namePreview) namePreview.textContent = name;

    // Update every visible currency symbol on the form
    document.querySelectorAll('.cur-sym').forEach(el => {
        el.textContent = sym;
    });
}

// ── Bind events to existing rows ─────────────
function bindExistingRows() {
    document.querySelectorAll('#lineItemsBody .line-item-row').forEach(row => {
        bindRow(row);
    });
}

function bindRow(row) {
    const qtyInput  = row.querySelector('.item-qty');
    const rateInput = row.querySelector('.item-rate');

    if (qtyInput)  qtyInput.addEventListener('input',  () => recalcRow(row));
    if (rateInput) rateInput.addEventListener('input',  () => recalcRow(row));
}

// ── Add line item ─────────────────────────────
function addLineItem() {
    const template = document.getElementById('lineItemTemplate');
    const clone    = template.content.cloneNode(true);
    const row      = clone.querySelector('tr');

    // Set name attributes with current index
    const idx = itemIndex++;
    row.setAttribute('data-index', idx);

    row.querySelector('.item-desc').setAttribute('name',          `items[${idx}][description]`);
    row.querySelector('.item-qty').setAttribute('name',           `items[${idx}][quantity]`);
    row.querySelector('.item-rate').setAttribute('name',          `items[${idx}][rate]`);
    row.querySelector('.item-amount-hidden').setAttribute('name', `items[${idx}][amount]`);

    // Sync currency symbol to current selection
    const currency = document.getElementById('currency')?.value ?? 'EUR';
    const sym      = currency === 'EUR' ? '€' : '$';
    row.querySelectorAll('.cur-sym').forEach(el => { el.textContent = sym; });

    document.getElementById('lineItemsBody').appendChild(row);
    bindRow(document.querySelector(`#lineItemsBody tr[data-index="${idx}"]`));

    // Focus the description field
    const newRow = document.querySelector(`#lineItemsBody tr[data-index="${idx}"]`);
    newRow?.querySelector('.item-desc')?.focus();
}

// ── Remove line item ──────────────────────────
function removeLineItem(btn) {
    const row  = btn.closest('tr');
    const body = document.getElementById('lineItemsBody');

    // Always keep at least one row
    if (body.querySelectorAll('.line-item-row').length <= 1) {
        // Clear fields instead of removing
        row.querySelector('.item-desc').value          = '';
        row.querySelector('.item-qty').value           = '1';
        row.querySelector('.item-rate').value          = '';
        row.querySelector('.item-amount').textContent  = '0.00';
        row.querySelector('.item-amount-hidden').value = '0.00';
        recalcAll();
        return;
    }

    row.remove();
    recalcAll();
}

// ── Recalculate a single row ──────────────────
function recalcRow(row) {
    const qty    = parseFloat(row.querySelector('.item-qty')?.value  || '0') || 0;
    const rate   = parseFloat(row.querySelector('.item-rate')?.value || '0') || 0;
    const amount = round2(qty * rate);

    const amountSpan   = row.querySelector('.item-amount');
    const amountHidden = row.querySelector('.item-amount-hidden');

    if (amountSpan)   amountSpan.textContent  = formatNum(amount);
    if (amountHidden) amountHidden.value       = amount.toFixed(2);

    recalcTotals();
}

// ── Recalculate all rows + totals ────────────
function recalcAll() {
    document.querySelectorAll('#lineItemsBody .line-item-row').forEach(row => {
        recalcRow(row);
    });
    // recalcTotals is called inside recalcRow, but call once more for safety
    recalcTotals();
}

// ── Recalculate totals ────────────────────────
function recalcTotals() {
    let subtotal = 0;

    document.querySelectorAll('#lineItemsBody .item-amount-hidden').forEach(input => {
        subtotal += parseFloat(input.value || '0') || 0;
    });

    subtotal = round2(subtotal);
    const total = subtotal; // No VAT

    // Update display
    const subtotalDisplay = document.getElementById('subtotalDisplay');
    const totalDisplay    = document.getElementById('totalDisplay');
    const subtotalInput   = document.getElementById('subtotalInput');
    const totalInput      = document.getElementById('totalInput');

    if (subtotalDisplay) subtotalDisplay.textContent = formatNum(subtotal);
    if (totalDisplay)    totalDisplay.textContent    = formatNum(total);
    if (subtotalInput)   subtotalInput.value         = subtotal.toFixed(2);
    if (totalInput)      totalInput.value            = total.toFixed(2);
}

// ── Helpers ───────────────────────────────────
function round2(n) {
    return Math.round((n + Number.EPSILON) * 100) / 100;
}

function formatNum(n) {
    return n.toLocaleString('en-GB', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}
