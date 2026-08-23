<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
if (!admin_is_configured()) { header('Location: /admin/'); exit; }
if (admin_is_logged_in()) { header('Location: /admin/'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_verify_csrf();
    $lockedUntil = (int) ($_SESSION['login_locked_until'] ?? 0);
    if ($lockedUntil > time()) { $error = 'Too many attempts. Wait a minute and try again.'; }
    else {
        $raw = @file_get_contents(admin_auth_file()); $auth = is_string($raw) ? json_decode($raw, true) : null;
        $password = isset($_POST['password']) ? (string) $_POST['password'] : '';
        if (is_array($auth) && isset($auth['password_hash']) && password_verify($password, (string) $auth['password_hash'])) {
            session_regenerate_id(true); $_SESSION['relet_admin_authenticated'] = true; unset($_SESSION['login_attempts'], $_SESSION['login_locked_until']); header('Location: /admin/'); exit;
        }
        $attempts = (int) ($_SESSION['login_attempts'] ?? 0) + 1; $_SESSION['login_attempts'] = $attempts;
        if ($attempts >= 5) { $_SESSION['login_locked_until'] = time() + 60; $_SESSION['login_attempts'] = 0; }
        usleep(350000); $error = 'Password not recognised.';
    }
}
admin_header('Log in'); ?><section class="panel narrow"><p class="eyebrow">Private administration</p><h1>Log in</h1><?php if ($error): ?><div class="alert error" role="alert"><?= relet_escape($error) ?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?= relet_escape(admin_csrf()) ?>"><label>Password <input type="password" name="password" autocomplete="current-password" required autofocus></label><button class="button" type="submit">Log in</button></form></section><?php admin_footer();
