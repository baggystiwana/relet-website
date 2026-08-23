<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php'; admin_require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
admin_verify_csrf();
$slug = isset($_POST['slug']) && is_string($_POST['slug']) ? $_POST['slug'] : '';
if (!relet_valid_slug($slug)) { http_response_code(400); admin_error_page('Invalid post', 'The post identifier was not valid.'); }
$file = relet_posts_dir() . DIRECTORY_SEPARATOR . $slug . '.json';
if (is_file($file) && !@unlink($file)) { http_response_code(500); admin_error_page('Could not delete', 'The post file could not be removed. Check private folder permissions.'); }
relet_rebuild_sitemap(); admin_flash('Post deleted.'); header('Location: /admin/'); exit;
