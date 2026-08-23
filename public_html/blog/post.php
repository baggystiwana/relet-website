<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/blog.php';
$slug = isset($_GET['slug']) && is_string($_GET['slug']) ? $_GET['slug'] : '';
$post = relet_get_post($slug, true);
if ($post === null) {
    http_response_code(404);
    relet_public_header('Article not found | Re-Let', 'The requested Re-Let article could not be found.', RELET_SITE_URL . '/blog/');
    ?><main id="main"><section class="page-hero"><div class="container"><h1>Article not found</h1><p class="lead">The article may have been moved or unpublished.</p><a class="btn" href="/blog/">View property advice</a></div></section></main><?php relet_public_footer(); exit;
}
$canonical = RELET_SITE_URL . '/blog/' . $post['slug'] . '/';
$image = !empty($post['feature_image_url']) ? $post['feature_image_url'] : RELET_SITE_URL . '/assets/og-relet.jpg';
$breadcrumb = relet_schema_breadcrumb($canonical, [
    ['Home', RELET_SITE_URL . '/'],
    ['Advice', RELET_SITE_URL . '/blog/'],
    [$post['title'], $canonical],
]);
$article = [
    '@type' => 'BlogPosting',
    '@id' => $canonical . '#article',
    'headline' => $post['title'],
    'description' => $post['meta_description'],
    'datePublished' => $post['publish_date'],
    'dateModified' => $post['modified_date'] ?? $post['publish_date'],
    'image' => [$image],
    'author' => [
        '@type' => 'Organization',
        'name' => 'Re-Let',
        'url' => RELET_SITE_URL . '/about.html',
    ],
    'publisher' => ['@id' => RELET_SITE_URL . '/#business'],
    'mainEntityOfPage' => ['@id' => $canonical . '#webpage'],
    'about' => [['@id' => RELET_SITE_URL . '/#business']],
    'articleSection' => 'Property maintenance advice',
    'inLanguage' => 'en-GB',
];
$pageSchema = [
    '@type' => 'WebPage',
    '@id' => $canonical . '#webpage',
    'url' => $canonical,
    'name' => $post['meta_title'],
    'description' => $post['meta_description'],
    'isPartOf' => ['@id' => RELET_SITE_URL . '/#website'],
    'about' => ['@id' => RELET_SITE_URL . '/#business'],
    'mainEntity' => ['@id' => $article['@id']],
    'breadcrumb' => ['@id' => $breadcrumb['@id']],
    'primaryImageOfPage' => ['@type' => 'ImageObject', 'url' => $image, 'contentUrl' => $image],
    'inLanguage' => 'en-GB',
];
$schema = ['@context' => 'https://schema.org', '@graph' => [relet_schema_business(false), relet_schema_website(), $pageSchema, $article, $breadcrumb]];
relet_public_header($post['meta_title'], $post['meta_description'], $canonical, 'article', $image, $schema);
$relatedServices = match ($post['slug']) {
    'void-property-checklist' => [
        ['/void-turnarounds.html', 'Void property turnarounds in Nottingham'],
        ['/landlord-repairs.html', 'Landlord property repairs in Nottingham'],
        ['/refurbishments.html', 'Rental property refurbishments in Nottingham'],
    ],
    'door-maintenance' => [
        ['/joinery.html', 'Joinery, door and window repairs in Nottingham'],
        ['/landlord-repairs.html', 'Landlord property repairs in Nottingham'],
        ['/void-turnarounds.html', 'Void property turnarounds in Nottingham'],
    ],
    default => [
        ['/landlord-repairs.html', 'Landlord property repairs in Nottingham'],
        ['/joinery.html', 'Joinery, door and window repairs in Nottingham'],
        ['/letting-agents.html', 'Property maintenance for Nottingham letting agents'],
    ],
};
?><main id="main"><section class="page-hero"><div class="container"><div class="breadcrumbs"><a href="/">Home</a> / <a href="/blog/">Advice</a> / <?= relet_escape($post['title']) ?></div><p class="eyebrow">Property advice</p><h1><?= relet_escape($post['title']) ?></h1><p class="meta">Published <?= relet_escape(date('j F Y', strtotime($post['publish_date']))) ?></p><p class="lead"><?= relet_escape($post['excerpt']) ?></p></div></section><section class="section"><div class="container article-layout"><article class="content"><?php if (!empty($post['feature_image_url'])): ?><figure><img src="<?= relet_escape($post['feature_image_url']) ?>" width="1200" height="675" alt="<?= relet_escape($post['feature_image_alt']) ?>" loading="eager" decoding="async"></figure><?php endif; ?><?= relet_render_content($post['content']) ?><div class="card"><h2>Related Re-Let services</h2><ul><?php foreach ($relatedServices as [$href, $label]): ?><li><a href="<?= relet_escape($href) ?>"><?= relet_escape($label) ?></a></li><?php endforeach; ?></ul></div><p><a class="btn" href="/contact.html">Discuss a property issue</a></p></article><aside class="sidebar"><h3>Need practical help?</h3><p>Tell Re-Let what the property needs and receive the next practical step.</p><a class="link" href="/contact.html">Request a quote →</a></aside></div></section></main><?php relet_public_footer();
