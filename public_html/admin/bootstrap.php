<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/blog.php';

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_name('relet_admin');
session_set_cookie_params(['lifetime' => 0, 'path' => '/admin/', 'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'), 'httponly' => true, 'samesite' => 'Strict']);
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; style-src 'self'; img-src 'self' https:; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");
header('Cache-Control: no-store, private');

function admin_auth_file(): string { return relet_data_dir() . DIRECTORY_SEPARATOR . 'admin.json'; }
function admin_is_configured(): bool { return is_file(admin_auth_file()); }
function admin_is_logged_in(): bool { return !empty($_SESSION['relet_admin_authenticated']); }
function admin_require_login(): void { if (!admin_is_logged_in()) { header('Location: /admin/login.php'); exit; } }
function admin_csrf(): string { if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); } return (string) $_SESSION['csrf']; }
function admin_verify_csrf(): void
{
    $sent = isset($_POST['csrf']) && is_string($_POST['csrf']) ? $_POST['csrf'] : '';
    if ($sent === '' || !hash_equals(admin_csrf(), $sent)) { http_response_code(400); admin_error_page('Invalid request', 'The form expired or could not be verified. Go back and try again.'); }
}
function admin_flash(string $message): void { $_SESSION['flash'] = $message; }
function admin_take_flash(): string { $message = (string) ($_SESSION['flash'] ?? ''); unset($_SESSION['flash']); return $message; }
function admin_header(string $title): void
{
    ?><!doctype html><html lang="en-GB"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= relet_escape($title) ?> | Re-Let Admin</title><link rel="stylesheet" href="/assets/admin.css"></head><body><header class="admin-header"><a href="/admin/"><img src="/assets/relet-logo.webp" width="120" height="88" alt="Re-Let"></a><?php if (admin_is_logged_in()): ?><nav><a href="/admin/">Posts</a><a href="/admin/edit.php">New post</a><form method="post" action="/admin/logout.php"><input type="hidden" name="csrf" value="<?= relet_escape(admin_csrf()) ?>"><button type="submit">Log out</button></form></nav><?php endif; ?></header><main class="admin-main"><?php
}
function admin_footer(): void { ?></main></body></html><?php }
function admin_error_page(string $title, string $message): void { admin_header($title); ?><section class="panel"><h1><?= relet_escape($title) ?></h1><p><?= relet_escape($message) ?></p><p><a class="button" href="/admin/">Return to admin</a></p></section><?php admin_footer(); exit; }

function admin_validate_post(array $input): array
{
    $post = [];
    $post['title'] = trim((string) ($input['title'] ?? ''));
    $post['slug'] = relet_clean_slug((string) ($input['slug'] ?? $post['title']));
    $post['excerpt'] = trim((string) ($input['excerpt'] ?? ''));
    $post['content'] = trim((string) ($input['content'] ?? ''));
    $post['meta_title'] = trim((string) ($input['meta_title'] ?? ''));
    $post['meta_description'] = trim((string) ($input['meta_description'] ?? ''));
    $post['feature_image_url'] = trim((string) ($input['feature_image_url'] ?? ''));
    $post['feature_image_alt'] = trim((string) ($input['feature_image_alt'] ?? ''));
    $post['publish_date'] = trim((string) ($input['publish_date'] ?? ''));
    $post['status'] = (($input['status'] ?? 'draft') === 'published') ? 'published' : 'draft';
    $errors = [];
    if ($post['title'] === '' || strlen($post['title']) > 140) { $errors[] = 'Title is required and must be 140 characters or fewer.'; }
    if (!relet_valid_slug($post['slug']) || strlen($post['slug']) > 100) { $errors[] = 'Slug must contain lowercase letters, numbers and single hyphens only.'; }
    if ($post['excerpt'] === '' || strlen($post['excerpt']) > 300) { $errors[] = 'Excerpt is required and must be 300 characters or fewer.'; }
    if ($post['content'] === '' || strlen($post['content']) > 50000) { $errors[] = 'Content is required and must be under 50,000 characters.'; }
    if ($post['meta_title'] === '' || strlen($post['meta_title']) > 70) { $errors[] = 'Meta title is required and must be 70 characters or fewer.'; }
    if ($post['meta_description'] === '' || strlen($post['meta_description']) > 170) { $errors[] = 'Meta description is required and must be 170 characters or fewer.'; }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $post['publish_date']);
    if (!$date || $date->format('Y-m-d') !== $post['publish_date']) { $errors[] = 'Publish date must be a valid date.'; }
    if ($post['feature_image_url'] !== '') {
        $isRootRelative = substr($post['feature_image_url'], 0, 1) === '/' && substr($post['feature_image_url'], 0, 2) !== '//';
        $isHttps = filter_var($post['feature_image_url'], FILTER_VALIDATE_URL) && strtolower((string) parse_url($post['feature_image_url'], PHP_URL_SCHEME)) === 'https';
        if (!$isRootRelative && !$isHttps) { $errors[] = 'Feature image must use an HTTPS URL or a root-relative path beginning with /.'; }
        if ($post['feature_image_alt'] === '' || strlen($post['feature_image_alt']) > 180) { $errors[] = 'Image alt text is required when a feature image is used.'; }
    }
    return [$post, $errors];
}

function admin_save_post(array $post, ?string $originalSlug): bool
{
    $post['modified_date'] = date('Y-m-d');
    $json = json_encode($post, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
    if (!is_string($json) || !relet_atomic_write(relet_posts_dir() . DIRECTORY_SEPARATOR . $post['slug'] . '.json', $json . "\n")) { return false; }
    if ($originalSlug && $originalSlug !== $post['slug'] && relet_valid_slug($originalSlug)) { @unlink(relet_posts_dir() . DIRECTORY_SEPARATOR . $originalSlug . '.json'); }
    return relet_rebuild_sitemap();
}
