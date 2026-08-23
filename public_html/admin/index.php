<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
if (!admin_is_configured()) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        admin_verify_csrf();
        $password = isset($_POST['password']) ? (string) $_POST['password'] : '';
        $confirm = isset($_POST['confirm_password']) ? (string) $_POST['confirm_password'] : '';
        $errors = [];
        if (strlen($password) < 12 || strlen($password) > 200) { $errors[] = 'Choose a password between 12 and 200 characters.'; }
        if ($password !== $confirm) { $errors[] = 'The passwords do not match.'; }
        if ($errors === []) {
            $payload = json_encode(['password_hash' => password_hash($password, PASSWORD_DEFAULT), 'created_at' => gmdate('c')], JSON_PRETTY_PRINT);
            if (admin_is_configured()) { $errors[] = 'Setup has already been completed. Reload the page and log in.'; }
            elseif (!is_string($payload) || !relet_atomic_write(admin_auth_file(), $payload . "\n")) { $errors[] = 'The private data folder is not writable. Check the cPanel folder permissions and try again.'; }
            else { session_regenerate_id(true); $_SESSION['relet_admin_authenticated'] = true; admin_flash('Admin password created. Keep it in your password manager.'); header('Location: /admin/'); exit; }
        }
    }
    admin_header('First-run setup'); ?><section class="panel narrow"><p class="eyebrow">First-run setup</p><h1>Create the Re-Let admin password</h1><p>No default password exists. Choose a unique password now; only its secure hash will be stored in the private server folder.</p><?php if (!empty($errors)): ?><div class="alert error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= relet_escape($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?= relet_escape(admin_csrf()) ?>"><label>Password <input type="password" name="password" minlength="12" maxlength="200" autocomplete="new-password" required></label><label>Confirm password <input type="password" name="confirm_password" minlength="12" maxlength="200" autocomplete="new-password" required></label><button class="button" type="submit">Create password</button></form></section><?php admin_footer(); exit;
}
admin_require_login();
$posts = relet_read_posts(false); $flash = admin_take_flash();
admin_header('Posts'); ?><section class="panel"><div class="admin-title"><div><p class="eyebrow">Blog administration</p><h1>Posts</h1></div><a class="button" href="/admin/edit.php">Create post</a></div><?php if ($flash): ?><div class="alert" role="status"><?= relet_escape($flash) ?></div><?php endif; ?><?php if ($posts === []): ?><p>No posts yet. Create the first post when ready.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Title</th><th>Status</th><th>Publish date</th><th>Actions</th></tr></thead><tbody><?php foreach ($posts as $post): ?><tr><td><strong><?= relet_escape($post['title']) ?></strong><br><small><?= relet_escape($postPublicUrl($post)) ?></small></td><td><span class="status <?= relet_escape($post['status']) ?>"><?= relet_escape(ucfirst($post['status'])) ?></span></td><td><?= relet_escape($post['publish_date']) ?></td><td><a href="/admin/edit.php?slug=<?= rawurlencode($post['slug']) ?>">Edit</a><?php if ($post['status'] === 'published'): ?> · <a href="<?= relet_escape($postPublicUrl($post)) ?>" target="_blank" rel="noopener">View</a><?php endif; ?><form class="inline" method="post" action="/admin/delete.php"><input type="hidden" name="csrf" value="<?= relet_escape(admin_csrf()) ?>"><input type="hidden" name="slug" value="<?= relet_escape($post['slug']) ?>"><button class="link-button" type="submit">Delete</button></form></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section><?php admin_footer();
