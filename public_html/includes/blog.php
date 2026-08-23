<?php
declare(strict_types=1);

const RELET_SITE_URL = 'https://relet.co.uk';
const RELET_POSTS_PER_PAGE = 6;


function relet_schema_business(bool $full = false): array
{
    $node = [
        '@type' => 'HomeAndConstructionBusiness',
        '@id' => RELET_SITE_URL . '/#business',
        'name' => 'Re-Let',
        'legalName' => 'Gifford Hanson Limited',
        'identifier' => [
            '@type' => 'PropertyValue',
            'propertyID' => 'Companies House company number',
            'value' => '03030287',
        ],
        'url' => RELET_SITE_URL . '/',
    ];
    if (!$full) {
        return $node;
    }
    $services = [
        ['/landlord-repairs.html', 'Landlord property repairs', 'Rental property repairs and planned maintenance'],
        ['/void-turnarounds.html', 'Void property turnarounds', 'Void property turnaround and between-tenancy works'],
        ['/tenancy-cleaning.html', 'End-of-tenancy and void cleaning', 'End-of-tenancy, void and deep cleaning'],
        ['/joinery.html', 'Rental property joinery, doors and windows', 'Joinery, door, frame, skirting and timber repair'],
        ['/refurbishments.html', 'Rental property refurbishments', 'Rental property refurbishment and improvement works'],
        ['/letting-agents.html', 'Property maintenance support for letting agents', 'Outsourced property maintenance for letting agents'],
    ];
    $offers = [];
    foreach ($services as [$path, $name, $serviceType]) {
        $offers[] = [
            '@type' => 'Offer',
            'url' => RELET_SITE_URL . $path,
            'itemOffered' => [
                '@type' => 'Service',
                '@id' => RELET_SITE_URL . $path . '#service',
                'name' => $name,
                'serviceType' => $serviceType,
                'url' => RELET_SITE_URL . $path,
            ],
        ];
    }
    $areas = ['Nottingham', 'West Bridgford', 'Wilford', 'Tollerton', 'Nottingham city centre', 'Arnold', 'Beeston', 'Bulwell', 'Carlton', 'Clifton', 'Gedling', 'Hucknall', 'Mapperley', 'Sherwood', 'Stapleford', 'Wollaton', 'Long Eaton', 'Ruddington'];
    $node += [
        'description' => 'Property maintenance, repairs, void turnarounds, cleaning, joinery and refurbishments for Nottingham landlords and letting agents.',
        'slogan' => 'Property maintenance without the chasing.',
        'logo' => [
            '@type' => 'ImageObject',
            '@id' => RELET_SITE_URL . '/#logo',
            'url' => RELET_SITE_URL . '/assets/relet-logo.webp',
            'contentUrl' => RELET_SITE_URL . '/assets/relet-logo.webp',
            'width' => 240,
            'height' => 176,
            'caption' => 'Re-Let',
        ],
        'image' => RELET_SITE_URL . '/assets/og-relet.jpg',
        'telephone' => '+441156612041',
        'email' => 'info@relet.co.uk',
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => '1 Kilbourn Street',
            'addressLocality' => 'Nottingham',
            'postalCode' => 'NG3 1BQ',
            'addressCountry' => 'GB',
        ],
        'areaServed' => array_map(static fn(string $area): array => ['@type' => 'Place', 'name' => $area], $areas),
        'contactPoint' => [[
            '@type' => 'ContactPoint',
            'contactType' => 'customer service',
            'telephone' => '+441156612041',
            'email' => 'info@relet.co.uk',
            'availableLanguage' => 'English',
            'areaServed' => 'GB',
        ]],
        'knowsAbout' => ['Rental property maintenance', 'Landlord property repairs', 'Void property turnarounds', 'End-of-tenancy cleaning', 'Property joinery', 'Rental property refurbishments', 'Maintenance support for letting agents'],
        'hasOfferCatalog' => [
            '@type' => 'OfferCatalog',
            'name' => 'Property maintenance services',
            'itemListElement' => $offers,
        ],
    ];
    return $node;
}

function relet_schema_website(): array
{
    return [
        '@type' => 'WebSite',
        '@id' => RELET_SITE_URL . '/#website',
        'url' => RELET_SITE_URL . '/',
        'name' => 'Re-Let',
        'description' => 'Property maintenance for Nottingham landlords and letting agents.',
        'publisher' => ['@id' => RELET_SITE_URL . '/#business'],
        'inLanguage' => 'en-GB',
    ];
}

function relet_schema_breadcrumb(string $canonical, array $items): array
{
    $elements = [];
    foreach ($items as $index => $item) {
        $elements[] = [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'name' => $item[0],
            'item' => $item[1],
        ];
    }
    return [
        '@type' => 'BreadcrumbList',
        '@id' => $canonical . '#breadcrumb',
        'itemListElement' => $elements,
    ];
}

function relet_data_dir(): string
{
    $configured = getenv('RELET_DATA_DIR');
    if (is_string($configured) && trim($configured) !== '') {
        return rtrim($configured, '/\\');
    }
    return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'relet-data';
}

function relet_posts_dir(): string
{
    return relet_data_dir() . DIRECTORY_SEPARATOR . 'posts';
}

function relet_escape(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function relet_valid_slug(string $slug): bool
{
    return (bool) preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug);
}

function relet_clean_slug(string $slug): string
{
    $slug = strtolower(trim($slug));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
    return trim($slug, '-');
}

function relet_read_posts(bool $publishedOnly = true): array
{
    $posts = [];
    $dir = relet_posts_dir();
    if (!is_dir($dir)) {
        return [];
    }
    foreach (glob($dir . DIRECTORY_SEPARATOR . '*.json') ?: [] as $file) {
        $raw = @file_get_contents($file);
        $post = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($post) || empty($post['slug']) || !relet_valid_slug((string) $post['slug'])) {
            continue;
        }
        if ($publishedOnly && (($post['status'] ?? 'draft') !== 'published')) {
            continue;
        }
        $posts[] = $post;
    }
    usort($posts, static function (array $a, array $b): int {
        return strcmp((string) ($b['publish_date'] ?? ''), (string) ($a['publish_date'] ?? ''));
    });
    return $posts;
}

function relet_get_post(string $slug, bool $publishedOnly = true): ?array
{
    if (!relet_valid_slug($slug)) {
        return null;
    }
    $file = relet_posts_dir() . DIRECTORY_SEPARATOR . $slug . '.json';
    if (!is_file($file)) {
        return null;
    }
    $raw = @file_get_contents($file);
    $post = is_string($raw) ? json_decode($raw, true) : null;
    if (!is_array($post) || ($publishedOnly && (($post['status'] ?? 'draft') !== 'published'))) {
        return null;
    }
    return $post;
}

function relet_render_content(string $content): string
{
    $lines = preg_split('/\R/', trim($content)) ?: [];
    $html = '';
    $paragraph = [];
    $listOpen = false;
    $flushParagraph = static function () use (&$html, &$paragraph): void {
        if ($paragraph !== []) {
            $html .= '<p>' . relet_escape(implode(' ', $paragraph)) . '</p>';
            $paragraph = [];
        }
    };
    foreach ($lines as $line) {
        $line = trim($line);
        if (substr($line, 0, 3) === '## ') {
            $flushParagraph();
            if ($listOpen) { $html .= '</ul>'; $listOpen = false; }
            $html .= '<h2>' . relet_escape(substr($line, 3)) . '</h2>';
        } elseif (substr($line, 0, 2) === '- ') {
            $flushParagraph();
            if (!$listOpen) { $html .= '<ul>'; $listOpen = true; }
            $html .= '<li>' . relet_escape(substr($line, 2)) . '</li>';
        } elseif ($line === '') {
            $flushParagraph();
            if ($listOpen) { $html .= '</ul>'; $listOpen = false; }
        } else {
            if ($listOpen) { $html .= '</ul>'; $listOpen = false; }
            $paragraph[] = $line;
        }
    }
    $flushParagraph();
    if ($listOpen) { $html .= '</ul>'; }
    return $html;
}

function relet_public_header(string $title, string $description, string $canonical, string $type = 'website', ?string $image = null, ?array $jsonLd = null): void
{
    $image = $image ?: RELET_SITE_URL . '/assets/og-relet.jpg';
    ?><!doctype html><html lang="en-GB"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= relet_escape($title) ?></title><meta name="description" content="<?= relet_escape($description) ?>"><link rel="canonical" href="<?= relet_escape($canonical) ?>"><meta property="og:type" content="<?= relet_escape($type) ?>"><meta property="og:title" content="<?= relet_escape($title) ?>"><meta property="og:description" content="<?= relet_escape($description) ?>"><meta property="og:url" content="<?= relet_escape($canonical) ?>"><meta property="og:image" content="<?= relet_escape($image) ?>"><link rel="stylesheet" href="/assets/styles.css"><link rel="stylesheet" href="/assets/brand.css"><?php if ($jsonLd !== null): ?><script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script><?php endif; ?><link rel="icon" type="image/png" sizes="64x64" href="/assets/favicon.png"><link rel="apple-touch-icon" sizes="180x180" href="/assets/apple-touch-icon.png"></head><body><a class="skip" href="#main">Skip to content</a><header class="site-header"><nav class="nav container" aria-label="Main navigation"><a class="brand brand-image" href="/" aria-label="Re-Let home"><img class="brand-logo" src="/assets/relet-logo.webp" width="240" height="176" alt="Re-Let"></a><button class="nav-toggle" aria-expanded="false" aria-label="Open menu">☰</button><div class="nav-links"><a href="/landlord-repairs.html">Repairs</a><a href="/void-turnarounds.html">Voids</a><a href="/tenancy-cleaning.html">Cleaning</a><a href="/joinery.html">Joinery</a><a href="/refurbishments.html">Refurbishments</a><a href="/letting-agents.html">Letting agents</a><a href="/blog/">Advice</a><a class="btn" href="/contact.html">Request a quote</a></div></nav></header><?php
}

function relet_public_footer(): void
{
    ?><footer class="footer"><div class="container"><a class="brand brand-image" href="/" aria-label="Re-Let home"><img class="brand-logo" src="/assets/relet-logo.webp" width="240" height="176" alt="Re-Let"></a><p><a href="/contact.html">Request a quote</a> · <a href="tel:+441156612041">0115 661 2041</a></p><div class="footer-bottom"><span>© <span data-year></span> Re-Let.</span><span><a href="/privacy.html">Privacy Policy</a> · <a href="/cookie-policy.html">Cookie Policy</a> · <a href="/terms.html">Terms of Service</a></span></div><p class="company-disclosure notice">Re-Let is a trading name of Gifford Hanson Limited · Registered in England and Wales · Company No. 03030287 · Registered office: 7 St John Street, Mansfield, Nottinghamshire, NG18 1QH.</p></div></footer><script src="/assets/site.js"></script></body></html><?php
}

function relet_rebuild_sitemap(): bool
{
    $urls = ['/', '/landlord-repairs.html', '/void-turnarounds.html', '/tenancy-cleaning.html', '/joinery.html', '/refurbishments.html', '/letting-agents.html', '/areas-covered.html', '/about.html', '/contact.html', '/services.html', '/case-studies.html', '/case-study-kilbourn-street.html', '/case-study-main-street.html', '/blog/'];
    foreach (relet_read_posts(true) as $post) {
        $urls[] = '/blog/' . $post['slug'] . '/';
    }
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
    foreach ($urls as $path) {
        $xml .= '  <url><loc>' . htmlspecialchars(RELET_SITE_URL . $path, ENT_XML1, 'UTF-8') . "</loc></url>\n";
    }
    $xml .= "</urlset>\n";
    return relet_atomic_write(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'sitemap.xml', $xml);
}

function relet_atomic_write(string $path, string $contents): bool
{
    $dir = dirname($path);
    if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) {
        return false;
    }
    $temp = @tempnam($dir, '.relet-');
    if (!is_string($temp)) {
        return false;
    }
    $ok = @file_put_contents($temp, $contents, LOCK_EX) !== false;
    if ($ok) { @chmod($temp, 0640); $ok = @rename($temp, $path); }
    if (is_file($temp)) { @unlink($temp); }
    return $ok;
}
