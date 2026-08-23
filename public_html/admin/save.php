<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php'; admin_require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
admin_verify_csrf();
[$post, $errors] = admin_validate_post($_POST);
$original = isset($_POST['original_slug']) && is_string($_POST['original_slug']) ? $_POST['original_slug'] : null;
$existing = relet_get_post($post['slug'], false);
if ($existing !== null && $original !== $post['slug']) { $errors[] = 'That slug is already in use.'; }
if ($errors !== []) { admin_header('Check the post'); ?><section class="panel"><h1>Check the post</h1><div class="alert error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= relet_escape($error) ?></li><?php endforeach; ?></ul></div><p>Use your browser’s back button to correct the fields. Your entered content remains in the previous page.</p></section><?php admin_footer(); exit; }
if (!admin_save_post($post, $original)) { http_response_code(500); admin_error_page('Could not save', 'The post or sitemap could not be written. Check private folder and public sitemap permissions.'); }
admin_flash($post['status'] === 'published' ? 'Post published and sitemap updated.' : 'Draft saved and sitemap updated.'); header('Location: /admin/'); exit;
