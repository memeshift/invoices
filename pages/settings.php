<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
requireAuth();

$pageTitle = 'Settings';
$values = [];
foreach (SETTINGS_FIELDS as $key => $label) {
    $values[$key] = $_ENV[$key] ?? '';
}
$error  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    foreach (SETTINGS_FIELDS as $key => $label) {
        $values[$key] = trim($_POST[$key] ?? '');
        if (mb_strlen($values[$key]) > 255) {
            $error = "$label is too long (255 characters max).";
        }
    }

    if (!$error && $values['FREELANCER_EMAIL'] !== '' && !filter_var($values['FREELANCER_EMAIL'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Email address is not valid.';
    }

    if (!$error && $values['FREELANCER_TAX_ID'] !== '' && !preg_match('/^[\p{L}\p{N} \/.\-]+$/u', $values['FREELANCER_TAX_ID'])) {
        $error = 'Tax ID may only contain letters, numbers, spaces, dashes, slashes and dots.';
    }

    if (!$error && $values['FREELANCER_IBAN'] !== '') {
        if (!isValidIban($values['FREELANCER_IBAN'])) {
            $error = 'IBAN failed the checksum. Check for a typo, or leave it blank.';
        } else {
            $values['FREELANCER_IBAN'] = trim(chunk_split(strtoupper(preg_replace('/\s+/', '', $values['FREELANCER_IBAN'])), 4, ' '));
        }
    }

    if (!$error) {
        $stmt = getDB()->prepare(
            'INSERT INTO settings (name, value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE value = VALUES(value)'
        );
        foreach ($values as $key => $val) {
            $stmt->execute([$key, $val]);
        }
        setFlash('success', 'Settings saved.');
        redirect(SITE_URL . '/pages/settings.php');
    }
}

$groups = [
    'Your details'    => ['FREELANCER_NAME', 'FREELANCER_COMPANY', 'FREELANCER_ADDRESS_LINE1', 'FREELANCER_ADDRESS_LINE2', 'FREELANCER_EMAIL', 'FREELANCER_PHONE', 'FREELANCER_WEBSITE'],
    'Tax details'     => ['FREELANCER_TAX_ID_LABEL', 'FREELANCER_TAX_ID'],
    'Payment details' => ['FREELANCER_BANK_NAME', 'FREELANCER_IBAN', 'FREELANCER_BIC'],
];

$notes = [
    'FREELANCER_IBAN'         => "Optional. Leave blank for countries that don't use IBAN.",
    'FREELANCER_TAX_ID_LABEL' => 'What your country calls it, as it should print on invoices, e.g. Steuernummer, USt-IdNr., VAT ID, EIN. Optional.',
    'FREELANCER_TAX_ID'       => 'Letters, numbers, spaces, dashes, slashes and dots. Optional.',
];

require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-heading">
    <h2>Settings</h2>
</div>

<?php if ($error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<form method="POST" action="<?= e(SITE_URL . '/pages/settings.php') ?>" novalidate>
    <?= csrfField() ?>

    <div class="form-grid">
        <?php foreach ($groups as $title => $keys): ?>
        <section class="form-section">
            <h3 class="section-title"><?= e($title) ?></h3>
            <?php foreach ($keys as $key): ?>
            <div class="field">
                <label for="<?= e($key) ?>"><?= e(SETTINGS_FIELDS[$key]) ?></label>
                <input type="<?= $key === 'FREELANCER_EMAIL' ? 'email' : 'text' ?>"
                    id="<?= e($key) ?>" name="<?= e($key) ?>" maxlength="255"
                    value="<?= e($values[$key]) ?>"
                    <?= isset($notes[$key]) ? 'aria-describedby="' . e($key) . '-note"' : '' ?>>
                <?php if (isset($notes[$key])): ?>
                <small id="<?= e($key) ?>-note"><?= e($notes[$key]) ?></small>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </section>
        <?php endforeach; ?>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save settings</button>
    </div>
</form>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
