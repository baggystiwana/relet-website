# Re-Let cPanel deployment and blog setup

## Prerequisites

- Apache hosting with `.htaccess` and `mod_rewrite` enabled (standard cPanel configuration).
- PHP 8.1 or newer with JSON and session support.
- HTTPS enabled before the admin is used.
- Permission to place a private folder alongside `public_html` in the cPanel account home.

## Upload

The deployment ZIP is designed to be extracted in the cPanel account home, not inside `public_html`. It contains:

- `public_html/` — the public website and PHP application.
- `relet-data/` — the private blog JSON store. Keep this folder outside `public_html`.
- `README.txt` — this handover guide.

If the account already has a `public_html` folder, back it up first, then merge the supplied `public_html` contents into it. Do not move `relet-data` into the public website folder. Folder permissions should normally be `0750` for `relet-data` and its `posts` folder, with files at `0640`; the PHP account user must be able to write to `relet-data/posts`, `relet-data/admin.json` and `public_html/sitemap.xml`.

If the host uses a different account layout, set the server environment variable `RELET_DATA_DIR` to the absolute path of the private data folder. Do not place that path or a password in public JavaScript.

## First-run admin setup

1. Confirm the site is using HTTPS and select PHP 8.1 or newer in cPanel MultiPHP Manager.
2. Visit `https://relet.co.uk/admin/`.
3. Choose a unique password of at least 12 characters and store it in a password manager.
4. The server writes only a password hash to `relet-data/admin.json`. No default credentials are included in the package.
5. Log out after administration. To deliberately reset a lost password, use cPanel File Manager to delete only `relet-data/admin.json`, then revisit `/admin/` and complete first-run setup again.

## Publishing

The admin can create, edit, publish, unpublish and delete posts. Published posts appear at `/blog/slug/`, on the paginated blog index, and in `sitemap.xml`. Draft posts do not render publicly. Content supports plain paragraphs, `## Section heading` and `- bullet item` lines; output is escaped on render.

Feature images may use an HTTPS URL or a root-relative path such as `/assets/example.webp`. Supply honest alt text whenever a feature image is used. For best performance, upload an optimised 16:9 WebP into `public_html/assets/` and reference its root-relative path.

## Security and backups

- Keep `relet-data` outside `public_html`; its included `.htaccess` is defence in depth, not a reason to expose it.
- Do not email or add the admin password to files.
- Back up `relet-data` before major edits or hosting changes.
- Keep PHP and cPanel security updates current.
- `/admin/` is blocked from indexing in `robots.txt`, but authentication—not robots rules—protects it.
