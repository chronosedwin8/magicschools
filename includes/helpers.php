<?php
/**
 * Utilidades generales.
 */

function base_url(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    if (BASE_URL !== '') {
        return $base = rtrim(BASE_URL, '/');
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // Carpeta del proyecto relativa al DOCUMENT_ROOT
    $docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '');
    $appRoot = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    $path = ($docRoot !== '' && str_starts_with(strtolower($appRoot), strtolower($docRoot)))
        ? substr($appRoot, strlen($docRoot))
        : '';
    return $base = $scheme . '://' . $host . rtrim($path, '/');
}

function url(string $path = ''): string
{
    return base_url() . '/' . ltrim($path, '/');
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function money(float|int $amount): string
{
    return '$' . number_format($amount, 0, ',', '.') . ' ' . CURRENCY;
}

function money_short(float|int $amount): string
{
    return '$' . number_format($amount, 0, ',', '.');
}

function fecha(?string $date, bool $withTime = false): string
{
    if (!$date) {
        return '—';
    }
    $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    $ts = strtotime($date);
    $out = date('j', $ts) . ' ' . $meses[(int) date('n', $ts) - 1] . ' ' . date('Y', $ts);
    return $withTime ? $out . ', ' . date('g:i a', $ts) : $out;
}

function redirect(string $path): never
{
    $target = preg_match('#^https?://#', $path) ? $path : url($path);
    header('Location: ' . $target);
    exit;
}

function plan(string $code): ?array
{
    global $PLANS;
    return $PLANS[$code] ?? null;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

// --- CSRF -----------------------------------------------------------------
function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $sent = $_POST['_csrf'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('La sesión expiró. Vuelve atrás, recarga la página e inténtalo de nuevo.');
    }
}

// --- Mensajes flash ----------------------------------------------------------
function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function flashes(): array
{
    $list = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $list;
}

function render_flashes(): string
{
    $html = '';
    foreach (flashes() as $f) {
        $html .= '<div class="alert alert-' . e($f['type']) . '">' . e($f['message']) . '</div>';
    }
    return $html;
}

// --- Old input -----------------------------------------------------------
function old(string $key, string $default = ''): string
{
    return e($_SESSION['_old'][$key] ?? $default);
}

function set_old(array $data): void
{
    unset($data['password'], $data['password_confirm'], $data['_csrf']);
    $_SESSION['_old'] = $data;
}

function clear_old(): void
{
    unset($_SESSION['_old']);
}

function app_log(string $channel, string $message, array $context = []): void
{
    $dir = __DIR__ . '/../storage/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $line = sprintf("[%s] %s %s\n", date('Y-m-d H:i:s'), $message, $context ? json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '');
    @file_put_contents($dir . '/' . $channel . '-' . date('Y-m') . '.log', $line, FILE_APPEND | LOCK_EX);
}

function is_local_url(string $u): bool
{
    $host = parse_url($u, PHP_URL_HOST) ?: '';
    return in_array($host, ['localhost', '127.0.0.1', '::1'], true)
        || str_ends_with($host, '.local')
        || str_ends_with($host, '.test')
        || preg_match('/^(10|192\.168|172\.(1[6-9]|2\d|3[01]))\./', $host) === 1;
}
