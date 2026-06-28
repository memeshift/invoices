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
define('SITE_URL',               rtrim($_ENV['SITE_URL'] ?? 'https://your-domain.com', '/'));
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
    // REMOTE_ADDR is the real IP on SiteGround shared hosting.
    // If you add Cloudflare in future, swap to HTTP_CF_CONNECTING_IP.
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
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
    )->exec();
}

function clearLoginAttempts(): void {
    $db = getDB();
    $db->prepare('DELETE FROM login_attempts WHERE ip = ?')->execute([getClientIp()]);
}

// ─── Auth ────────────────────────────────────
function isLoggedIn(): bool {
    return isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
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
