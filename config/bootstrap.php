<?php
declare(strict_types=1);

// ─────────────────────────────────────────────
//  Bootstrap — loaded by every page
// ─────────────────────────────────────────────

// Load .env
$envPath = dirname(__DIR__) . '/.env';
if (!file_exists($envPath)) {
    die('<pre>ERROR: .env file not found. Copy .env.example to .env and configure it.</pre>');
}
foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
    [$key, $val] = explode('=', $line, 2);
    $key = trim($key);
    $val = trim(trim($val), '"\'');
    $_ENV[$key] = $val;
}

// App constants
define('APP_USERNAME',           $_ENV['APP_USERNAME']           ?? '');
define('APP_PASSWORD_HASH',      $_ENV['APP_PASSWORD_HASH']      ?? '');
define('DB_HOST',                $_ENV['DB_HOST']                ?? 'localhost');
define('DB_NAME',                $_ENV['DB_NAME']                ?? '');
define('DB_USER',                $_ENV['DB_USER']                ?? '');
define('DB_PASS',                $_ENV['DB_PASS']                ?? '');
define('SITE_URL',               rtrim($_ENV['SITE_URL'] ?? '', '/'));

// Fields editable on pages/settings.php (.env key => label)
const SETTINGS_FIELDS = [
    'FREELANCER_NAME'          => 'Name',
    'FREELANCER_COMPANY'       => 'Company / trade name',
    'FREELANCER_ADDRESS_LINE1' => 'Address line 1',
    'FREELANCER_ADDRESS_LINE2' => 'Address line 2',
    'FREELANCER_EMAIL'         => 'Email',
    'FREELANCER_PHONE'         => 'Phone',
    'FREELANCER_WEBSITE'       => 'Website',
    'FREELANCER_BANK_NAME'     => 'Bank name',
    'FREELANCER_IBAN'          => 'IBAN',
    'FREELANCER_BIC'           => 'BIC / SWIFT',
    'FREELANCER_TAX_ID_LABEL'  => 'Name of tax ID',
    'FREELANCER_TAX_ID'        => 'Tax ID number',
    'FREELANCER_INVOICE_TEXT'  => 'Invoice text',
];

// Saved settings override .env; if the table doesn't exist yet, .env values stand
try {
    $rows = getDB()->query('SELECT name, value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
    $_ENV = array_replace($_ENV, array_intersect_key($rows, SETTINGS_FIELDS));
} catch (PDOException) {}

define('FREELANCER_NAME',        $_ENV['FREELANCER_NAME']        ?? 'Your Name');
define('FREELANCER_COMPANY',     $_ENV['FREELANCER_COMPANY']     ?? '');
define('FREELANCER_ADDR1',       $_ENV['FREELANCER_ADDRESS_LINE1'] ?? '');
define('FREELANCER_ADDR2',       $_ENV['FREELANCER_ADDRESS_LINE2'] ?? '');
define('FREELANCER_EMAIL',       $_ENV['FREELANCER_EMAIL']       ?? '');
define('FREELANCER_PHONE',       $_ENV['FREELANCER_PHONE']       ?? '');
define('FREELANCER_WEBSITE',     $_ENV['FREELANCER_WEBSITE']     ?? '');
define('FREELANCER_BANK',        $_ENV['FREELANCER_BANK_NAME']   ?? '');
define('FREELANCER_IBAN',        $_ENV['FREELANCER_IBAN']        ?? '');
define('FREELANCER_BIC',         $_ENV['FREELANCER_BIC']         ?? '');
define('FREELANCER_TAX_ID_LABEL', $_ENV['FREELANCER_TAX_ID_LABEL'] ?? '');
define('FREELANCER_TAX_ID',      $_ENV['FREELANCER_TAX_ID']      ?? '');
define('FREELANCER_INVOICE_TEXT', $_ENV['FREELANCER_INVOICE_TEXT'] ?? '');

// Session security
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure',   '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime',  '28800'); // 8 hours

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ─── Database ────────────────────────────────
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            error_log('[InvoiceApp] DB error: ' . $e->getMessage());
            die('<p style="font-family:sans-serif;color:#900;padding:2rem">Database connection failed. Check your .env DB settings.</p>');
        }
    }
    return $pdo;
}

// ─── IP rate limiting ────────────────────────
function getClientIp(): string {
    // REMOTE_ADDR is the real client IP behind Hostinger's CDN (checked 7 Oct 2026).
    // If you add Cloudflare in future, swap to HTTP_CF_CONNECTING_IP.
    $ip  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $bin = inet_pton($ip);
    // IPv6 clients control a whole /64, so rate-limit on the prefix, not the single address
    if ($bin !== false && strlen($bin) === 16 && !str_starts_with($bin, "\0\0\0\0\0\0\0\0\0\0\xff\xff")) {
        return inet_ntop(substr($bin, 0, 8) . str_repeat("\0", 8));
    }
    return $ip;
}

function isIpRateLimited(): bool {
    $ip      = getClientIp();
    $window  = 900; // 15 minutes
    $maxHits = 5;

    $db   = getDB();
    $stmt = $db->prepare(
        'SELECT COUNT(*) FROM login_attempts
         WHERE ip = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)'
    );
    $stmt->execute([$ip, $window]);
    return (int) $stmt->fetchColumn() >= $maxHits;
}

function recordFailedLogin(): void {
    $ip = getClientIp();
    $db = getDB();

    $db->prepare('INSERT INTO login_attempts (ip) VALUES (?)')->execute([$ip]);

    // Prune rows older than 15 minutes to keep the table small
    $db->prepare(
        'DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 900 SECOND)'
    )->execute();
}

function clearLoginAttempts(): void {
    $db = getDB();
    $db->prepare('DELETE FROM login_attempts WHERE ip = ?')->execute([getClientIp()]);
}

// ─── Auth ────────────────────────────────────
const SESSION_IDLE_LIMIT     = 7200;   // 2 hours without a request
const SESSION_ABSOLUTE_LIMIT = 28800;  // 8 hours from login

function isLoggedIn(): bool {
    if (($_SESSION['authenticated'] ?? false) !== true) {
        return false;
    }
    $now = time();
    if ($now - ($_SESSION['login_at'] ?? 0) > SESSION_ABSOLUTE_LIMIT
        || $now - ($_SESSION['last_seen'] ?? 0) > SESSION_IDLE_LIMIT) {
        $_SESSION = [];
        setFlash('error', 'Your session expired. Please sign in again.');
        return false;
    }
    $_SESSION['last_seen'] = $now;
    return true;
}

function requireAuth(): void {
    if (!isLoggedIn()) {
        redirect(SITE_URL . '/auth/login.php');
    }
}

// ─── CSRF ────────────────────────────────────
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function verifyCsrf(): void {
    $submitted = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrfToken(), $submitted)) {
        http_response_code(403);
        die('CSRF validation failed. Please go back and try again.');
    }
}

// ─── Flash messages ──────────────────────────
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ─── Output helpers ──────────────────────────
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): never {
    header('Location: ' . $url);
    exit;
}

function formatMoney(float|string $amount, string $currency): string {
    $f = number_format((float)$amount, 2, '.', ',');
    return $currency === 'EUR' ? '€' . $f : '$' . $f;
}

function currencySymbol(string $currency): string {
    return $currency === 'EUR' ? '€' : '$';
}

function statusLabel(string $status): string {
    return match($status) {
        'draft'   => 'Draft',
        'sent'    => 'Sent',
        'paid'    => 'Paid',
        'overdue' => 'Overdue',
        default   => ucfirst($status),
    };
}

function isValidIban(string $iban): bool {
    $iban = strtoupper(preg_replace('/\s+/', '', $iban));
    if (!preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{11,30}$/', $iban)) return false;

    $digits = '';
    foreach (str_split(substr($iban, 4) . substr($iban, 0, 4)) as $ch) {
        $digits .= ctype_alpha($ch) ? (string)(ord($ch) - 55) : $ch;
    }
    $rem = 0;
    foreach (str_split($digits) as $d) {
        $rem = ($rem * 10 + (int)$d) % 97;
    }
    return $rem === 1;
}

// ─── Invoice helpers ─────────────────────────
function generateInvoiceNumber(): string {
    $db   = getDB();
    $year = (int) date('Y');

    $db->beginTransaction();
    try {
        $db->prepare(
            'INSERT INTO invoice_sequence (year, last_number) VALUES (?, 1)
             ON DUPLICATE KEY UPDATE last_number = last_number + 1'
        )->execute([$year]);

        $stmt = $db->prepare('SELECT last_number FROM invoice_sequence WHERE year = ?');
        $stmt->execute([$year]);
        $seq = (int) $stmt->fetchColumn();

        $db->commit();
        return sprintf('INV-%d-%04d', $year, $seq);
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

function getInvoice(int $id): array|false {
    $stmt = getDB()->prepare('SELECT * FROM invoices WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getInvoiceItems(int $invoiceId): array {
    $stmt = getDB()->prepare(
        'SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY sort_order, id'
    );
    $stmt->execute([$invoiceId]);
    return $stmt->fetchAll();
}

function updateInvoiceOverdue(): void {
    getDB()->exec(
        "UPDATE invoices SET status = 'overdue'
         WHERE status = 'sent' AND due_date < CURDATE()"
    );
}
