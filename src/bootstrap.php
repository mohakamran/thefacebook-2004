<?php
/**
 * thefacebook-2004 — shared bootstrap: config, session, database, helpers.
 * Unofficial educational recreation. Not affiliated with Meta Platforms, Inc.
 */
declare(strict_types=1);

const APP_ROOT = __DIR__ . '/..';
const DATA_DIR = APP_ROOT . '/data';
const DB_PATH = DATA_DIR . '/thefacebook.sqlite';
const UPLOAD_DIR = APP_ROOT . '/public/uploads';
const UPLOAD_URL = 'uploads';
const SCHOOL_NAME = 'Harvard';
// Registration only accepts addresses at this domain, like the 2004 site did.
// Set to '' to accept any email address.
const ALLOWED_EMAIL_DOMAIN = 'harvard.edu';
const MAX_UPLOAD_BYTES = 2 * 1024 * 1024;
const STATUSES = ['Student', 'Alumnus/Alumna', 'Faculty', 'Staff'];
const SEXES = ['Male', 'Female', 'Other'];

// Fail with a readable message (instead of a blank page) when a required PHP extension is off.
$missing = array_filter(['pdo_sqlite', 'mbstring', 'gd'], fn($x) => !extension_loaded($x));
if ($missing) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    exit("thefacebook-2004 needs these PHP extensions enabled: " . implode(', ', $missing) . "\n"
        . "See the \"Install and run locally\" section of README.md.\n"
        . "Loaded php.ini: " . (php_ini_loaded_file() ?: 'none') . "\n");
}

error_reporting(E_ALL);
ini_set('display_errors', '0');
date_default_timezone_set('America/New_York');

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => !empty($_SERVER['HTTPS']),
]);
session_name('tfb2004');
session_start();

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

require __DIR__ . '/db.php';
require __DIR__ . '/layout.php';

/* ---------- output ---------- */

function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}

function fmt_date(?string $ts): string
{
    if (!$ts) return '';
    $t = strtotime($ts);
    return $t ? date('F j, Y', $t) : '';
}

function fmt_datetime(?string $ts): string
{
    if (!$ts) return '';
    $t = strtotime($ts);
    return $t ? date('g:ia F jS, Y', $t) : '';
}

/* ---------- flash messages ---------- */

function flash(string $msg, string $kind = 'info'): void
{
    $_SESSION['flash'][] = ['msg' => $msg, 'kind' => $kind];
}

function take_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/* ---------- CSRF ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function require_post_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Method not allowed');
    }
    $t = $_POST['csrf'] ?? '';
    if (!is_string($t) || !hash_equals(csrf_token(), $t)) {
        http_response_code(400);
        flash('Your session expired. Please try again.', 'error');
        redirect('index.php');
    }
}

/* ---------- input ---------- */

function post_str(string $key, int $max = 255): string
{
    $v = $_POST[$key] ?? '';
    if (!is_string($v)) return '';
    return mb_substr(trim($v), 0, $max);
}

function get_str(string $key, int $max = 255): string
{
    $v = $_GET[$key] ?? '';
    if (!is_string($v)) return '';
    return mb_substr(trim($v), 0, $max);
}

function get_int(string $key): int
{
    $v = $_GET[$key] ?? $_POST[$key] ?? 0;
    return is_numeric($v) ? (int)$v : 0;
}

/* ---------- auth ---------- */

function current_user(): ?array
{
    static $cache = false;
    if ($cache !== false) return $cache;
    $id = $_SESSION['uid'] ?? null;
    if (!$id) return $cache = null;
    $u = find_user((int)$id);
    if (!$u) {
        unset($_SESSION['uid']);
    }
    return $cache = $u;
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        flash('You must be logged in to see that page.', 'error');
        redirect('index.php');
    }
    return $u;
}

function log_in(int $uid): void
{
    session_regenerate_id(true);
    $_SESSION['uid'] = $uid;
}

/* ---------- domain helpers ---------- */

function find_user(int $id): ?array
{
    $st = db()->prepare('SELECT * FROM users WHERE id = ?');
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

function avatar_url(?array $u): string
{
    if ($u && !empty($u['picture'])) {
        return UPLOAD_URL . '/' . rawurlencode($u['picture']);
    }
    return 'assets/avatar.svg';
}

function first_name(array $u): string
{
    return explode(' ', $u['name'])[0];
}

function pronoun_obj(array $u): string
{
    return match ($u['sex'] ?? '') {
        'Male' => 'Him',
        'Female' => 'Her',
        default => 'Them',
    };
}

/** 'none' | 'friends' | 'sent' (I asked them) | 'received' (they asked me) | 'self' */
function friendship_state(int $me, int $other): string
{
    if ($me === $other) return 'self';
    $st = db()->prepare('SELECT requester_id, status FROM friendships
        WHERE (requester_id = :a AND addressee_id = :b) OR (requester_id = :b AND addressee_id = :a)');
    $st->execute([':a' => $me, ':b' => $other]);
    $row = $st->fetch();
    if (!$row) return 'none';
    if ($row['status'] === 'accepted') return 'friends';
    return ((int)$row['requester_id'] === $me) ? 'sent' : 'received';
}

function friends_of(int $uid, int $limit = 1000): array
{
    $st = db()->prepare('SELECT u.* FROM users u JOIN friendships f
        ON (f.requester_id = :id AND f.addressee_id = u.id) OR (f.addressee_id = :id AND f.requester_id = u.id)
        WHERE f.status = \'accepted\' ORDER BY u.name LIMIT ' . (int)$limit);
    $st->execute([':id' => $uid]);
    return $st->fetchAll();
}

/** Whether $viewer may see the full profile of $owner, honoring the owner's privacy setting. */
function can_view_profile(?array $viewer, array $owner): bool
{
    if (!$viewer) return false;
    if ((int)$viewer['id'] === (int)$owner['id']) return true;
    if ($owner['privacy'] === 'friends') {
        return friendship_state((int)$viewer['id'], (int)$owner['id']) === 'friends';
    }
    return true;
}

function unread_count(int $uid): int
{
    $st = db()->prepare('SELECT COUNT(*) FROM messages WHERE recipient_id = ? AND read_at IS NULL AND deleted_by_recipient = 0');
    $st->execute([$uid]);
    return (int)$st->fetchColumn();
}

function pending_request_count(int $uid): int
{
    $st = db()->prepare("SELECT COUNT(*) FROM friendships WHERE addressee_id = ? AND status = 'pending'");
    $st->execute([$uid]);
    return (int)$st->fetchColumn();
}

function now(): string
{
    return date('Y-m-d H:i:s');
}
