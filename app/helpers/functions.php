<?php
declare(strict_types=1);

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Settings;

/** Escapes a value for HTML output (text and attribute context). */
function e(mixed $v): string
{
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Absolute URL for a clean path, e.g. url('/blog/my-post'). Absolute and special URLs pass through. */
function url(string $path = '/'): string
{
    if (preg_match('#^(https?:)?//|^(mailto|tel):|^\##i', $path)) {
        return $path;
    }
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

/** Clean admin URL, e.g. admin_url('services/3/edit') → /control-panel/services/3/edit */
function admin_url(string $path = ''): string
{
    return url('/' . ADMIN_PREFIX . ($path !== '' ? '/' . ltrim($path, '/') : '/'));
}

/** Versioned static asset URL (cache-busted by file modification time). */
function asset(string $path): string
{
    $rel = 'public/assets/' . ltrim($path, '/');
    $file = ROOT_PATH . '/' . $rel;
    return url('/' . $rel) . (is_file($file) ? '?v=' . filemtime($file) : '');
}

/** URL for an uploaded media path stored in the database (relative "uploads/..." or absolute URL). */
function media_url(?string $path): string
{
    $path = trim((string) $path);
    if ($path === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    return url('/' . ltrim($path, '/'));
}

/** Medium-size variant of an uploaded image when one exists, else the original. */
function media_medium(?string $path): string
{
    $path = trim((string) $path);
    if ($path !== '' && str_starts_with($path, 'uploads/')) {
        $m = preg_replace('#\.(\w+)$#', '-md.$1', $path);
        if ($m && is_file(ROOT_PATH . '/' . $m)) {
            return media_url($m);
        }
    }
    return media_url($path);
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443');
}

function setting(string $key, string $default = ''): string
{
    return Settings::get($key, $default);
}

function setting_on(string $key, bool $default = false): bool
{
    return Settings::bool($key, $default);
}

function csrf_token(): string
{
    return Csrf::token();
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">';
}

/** Value to redisplay in a form: old input after a failed submit, else the stored value. */
function old(string $key, mixed $default = ''): mixed
{
    $old = $GLOBALS['__old'] ?? [];
    return array_key_exists($key, $old) ? $old[$key] : $default;
}

function field_error(string $key): string
{
    $errors = $GLOBALS['__errors'] ?? [];
    return isset($errors[$key]) ? '<p class="field-error" id="err-' . e($key) . '">' . e($errors[$key]) . '</p>' : '';
}

function has_error(string $key): bool
{
    return isset(($GLOBALS['__errors'] ?? [])[$key]);
}

function can(string $permission): bool
{
    return Auth::can($permission);
}

/** Lower-case URL slug. */
function slugify(string $text, int $max = 120): string
{
    $text = (string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    $text = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $text) ?? '', '-'));
    return substr($text, 0, $max) ?: 'item';
}

/** Formats a UTC database timestamp in the site's configured timezone. */
function fmt_date(?string $utc, string $format = 'M j, Y'): string
{
    if (!$utc) {
        return '';
    }
    try {
        // Inside the terminal the member's own timezone wins over the site timezone.
        $tz = new DateTimeZone($GLOBALS['__tz'] ?? (setting('timezone', 'UTC') ?: 'UTC'));
    } catch (Throwable) {
        $tz = new DateTimeZone('UTC');
    }
    try {
        return (new DateTimeImmutable($utc, new DateTimeZone('UTC')))->setTimezone($tz)->format($format);
    } catch (Throwable) {
        return '';
    }
}

/** Converts a local (site timezone) datetime-local input value to UTC for storage. */
function local_to_utc(string $local): ?string
{
    if (trim($local) === '') {
        return null;
    }
    try {
        $tz = new DateTimeZone($GLOBALS['__tz'] ?? (setting('timezone', 'UTC') ?: 'UTC'));
        return (new DateTimeImmutable($local, $tz))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    } catch (Throwable) {
        return null;
    }
}

function utc_to_local_input(?string $utc): string
{
    return $utc ? fmt_date($utc, 'Y-m-d\TH:i') : '';
}

function excerpt(?string $html, int $len = 160): string
{
    $text = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags((string) $html), ENT_QUOTES, 'UTF-8')) ?? '');
    return mb_strlen($text) > $len ? rtrim(mb_substr($text, 0, $len - 1)) . '…' : $text;
}

/** Decodes a JSON column to an array (never throws). */
function json_list(?string $json): array
{
    if (!$json) {
        return [];
    }
    $v = json_decode($json, true);
    return is_array($v) ? $v : [];
}

/** Parses "Title | Text | icon" lines (used by block items) into arrays. */
function parse_lines(?string $text, array $keys = ['title', 'text', 'icon']): array
{
    $out = [];
    foreach (preg_split('/\r?\n/', (string) $text) as $line) {
        if (trim($line) === '') {
            continue;
        }
        $parts = array_map('trim', explode('|', $line));
        $row = [];
        foreach ($keys as $i => $k) {
            $row[$k] = $parts[$i] ?? '';
        }
        $out[] = $row;
    }
    return $out;
}

/** True when the current request path matches (for active navigation states). */
function is_active_path(string $href, string $current): bool
{
    $p = parse_url($href, PHP_URL_PATH) ?: '/';
    $base = rtrim((string) parse_url(BASE_URL, PHP_URL_PATH), '/');
    if ($base !== '' && str_starts_with($p, $base)) {
        $p = substr($p, strlen($base)) ?: '/';
    }
    $p = '/' . trim($p, '/');
    if ($p === '/') {
        return $current === '/';
    }
    return $current === $p || str_starts_with($current, $p . '/');
}

/** Built-in line-icon set (stroke SVG paths). Returns inline SVG; unknown names fall back to a dot. */
function icon(string $name, string $class = 'icon'): string
{
    static $paths = null;
    $paths ??= require APP_PATH . '/helpers/icons.php';
    $d = $paths[$name] ?? $paths['dot'];
    return '<svg class="' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $d . '</svg>';
}

function icon_names(): array
{
    $paths = require APP_PATH . '/helpers/icons.php';
    return array_keys($paths);
}

/** Human-readable file size. */
function human_size(int $bytes): string
{
    $u = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    $n = (float) $bytes;
    while ($n >= 1024 && $i < 3) {
        $n /= 1024;
        $i++;
    }
    return ($i ? number_format($n, 1) : (string) $bytes) . ' ' . $u[$i];
}

/** Template variable substitution for email templates: {name}, {email} … (values are escaped for HTML). */
function render_vars(string $tpl, array $vars, bool $html = true): string
{
    return preg_replace_callback('/\{([a-z_]+)\}/', static function ($m) use ($vars, $html) {
        if (!array_key_exists($m[1], $vars)) {
            return $m[0];
        }
        $v = (string) $vars[$m[1]];
        return $html ? nl2br(e($v)) : $v;
    }, $tpl) ?? $tpl;
}

/** Google Fonts offered in Appearance → Typography (allow-list, so settings can never inject CSS). */
function font_options(): array
{
    return ['Inter', 'Space Grotesk', 'Manrope', 'Plus Jakarta Sans', 'DM Sans', 'Outfit', 'Sora', 'IBM Plex Sans', 'Poppins', 'Montserrat', 'Roboto', 'Lato', 'Playfair Display', 'Lora', 'JetBrains Mono', 'System'];
}

function safe_color(string $value, string $fallback): string
{
    return preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? $value : $fallback;
}

/** CSS classes/inline style for CMS section backgrounds (default | muted | dark | brand | image). */
function section_attrs(array $s, string $extra = ''): string
{
    $bg = in_array($s['background'] ?? 'default', ['default', 'muted', 'dark', 'brand', 'image'], true) ? ($s['background'] ?? 'default') : 'default';
    $style = '';
    if ($bg === 'image' && !empty($s['background_image'])) {
        $style = ' style="--section-image:url(\'' . e(media_url($s['background_image'])) . '\')"';
    }
    return 'class="section bg-' . $bg . ($extra ? ' ' . e($extra) : '') . '"' . $style;
}

/** Renders a CMS button when both label and URL are set. */
function cms_button(?string $label, ?string $href, string $class = 'btn btn-primary', bool $arrow = false): string
{
    if (trim((string) $label) === '' || trim((string) $href) === '') {
        return '';
    }
    $ext = preg_match('#^https?://#i', (string) $href) && !str_starts_with((string) $href, BASE_URL);
    return '<a class="' . e($class) . '" href="' . e(url((string) $href)) . '"' . ($ext ? ' target="_blank" rel="noopener"' : '') . '>' . e($label) . ($arrow ? ' ' . icon('arrow-right', 'icon icon-sm') : '') . '</a>';
}

/** Responsive <img> for an uploaded image with lazy loading and the medium variant as a srcset candidate. */
function cms_image(?string $path, string $alt = '', string $class = '', bool $eager = false, string $sizes = '100vw'): string
{
    if (!$path) {
        return '';
    }
    $src = media_url($path);
    $md = media_medium($path);
    $srcset = $md !== $src ? ' srcset="' . e($md) . ' 900w, ' . e($src) . ' 2000w" sizes="' . e($sizes) . '"' : '';
    return '<img src="' . e($src) . '"' . $srcset . ' alt="' . e($alt) . '"' . ($class ? ' class="' . e($class) . '"' : '') . ($eager ? ' fetchpriority="high"' : ' loading="lazy" decoding="async"') . '>';
}

function role_is_super(int $roleId): bool
{
    $p = (string) App\Core\Database::value('SELECT permissions FROM roles WHERE id = :id', ['id' => $roleId]);
    return in_array('*', json_list($p), true);
}

/** True when $adminId is the only active admin holding a full-access role. */
function admin_is_last_super(int $adminId): bool
{
    $supers = App\Core\Database::all("SELECT a.id FROM admins a JOIN roles r ON r.id = a.role_id WHERE a.status = 'active' AND r.permissions LIKE '%\"*\"%'");
    $ids = array_map('intval', array_column($supers, 'id'));
    return $ids === [$adminId];
}

/** Decrypted secret setting (API keys saved in the Control Panel are stored encrypted). Config constants win. */
function secret_setting(string $key): string
{
    $const = strtoupper($key);
    if (defined($const) && constant($const) !== '') {
        return (string) constant($const);
    }
    $v = App\Core\Settings::get($key);
    if ($v === '') {
        return '';
    }
    return str_starts_with($v, 'v1:') ? (App\Core\Crypto::decrypt($v) ?? '') : $v;
}

/** Formats money with the account currency. */
function money(mixed $v, string $currency = 'USD', bool $sign = false): string
{
    if ($v === null || $v === '') {
        return '—';
    }
    $f = (float) $v;
    $sym = ['USD' => '$', 'EUR' => '€', 'GBP' => '£', 'JPY' => '¥', 'INR' => '₹', 'AUD' => 'A$', 'CAD' => 'C$', 'SGD' => 'S$', 'CNY' => '¥', 'BRL' => 'R$', 'RUB' => '₽', 'CHF' => 'CHF ', 'AED' => 'AED '][$currency] ?? $currency . ' ';
    $dec = $currency === 'JPY' ? 0 : 2;
    return ($f < 0 ? '−' : ($sign && $f > 0 ? '+' : '')) . $sym . number_format(abs($f), $dec);
}

function pct(?float $v, int $dec = 1): string
{
    return $v === null ? '—' : number_format($v * 100, $dec) . '%';
}

/** Member currently signed in to the website (not the Control Panel). */
function member(): ?array
{
    return App\Trading\Members::current();
}

/** Translation lookup for the terminal UI (lang/{code}.php, falls back to English). */
function t(string $key, array $vars = []): string
{
    static $cache = [];
    $lang = $GLOBALS['__lang'] ?? 'en';
    if (!isset($cache[$lang])) {
        $f = APP_PATH . '/lang/' . $lang . '.php';
        $cache[$lang] = is_file($f) ? require $f : [];
        if ($lang !== 'en' && !isset($cache['en'])) {
            $cache['en'] = require APP_PATH . '/lang/en.php';
        }
    }
    $s = $cache[$lang][$key] ?? ($cache['en'][$key] ?? $key);
    foreach ($vars as $k => $v) {
        $s = str_replace('{' . $k . '}', (string) $v, $s);
    }
    return $s;
}


/** Escapes AI coach text and turns **key phrase** into a highlighted <mark>. */
function coach_text(?string $text): string
{
    return preg_replace('/\*\*(.+?)\*\*/s', '<mark>$1</mark>', e($text)) ?? e($text);
}
