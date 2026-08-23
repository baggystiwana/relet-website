<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/blog.php';
$posts = relet_read_posts(true);
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
$totalPages = max(1, (int) ceil(count($posts) / RELET_POSTS_PER_PAGE));
if ($page > $totalPages) { $page = $totalPages; }
$visible = array_slice($posts, ($page - 1) * RELET_POSTS_PER_PAGE, RELET_POSTS_PER_PAGE);
$postPublicUrl = static function (array $post): string {
    $slug = (string) $post['slug'];
    return is_file(__DIR__ . '/' . $slug . '.html')
        ? RELET_SITE_URL . '/blog/' . rawurlencode($slug) . '.html'
        : RELET_SITE_URL . '/blog/post.php?slug=' . rawurlencode($slug);
};
$canonical = RELET_SITE_URL . '/blog/' . ($page > 1 ? '?page=' . $page : '');
$breadcrumb = relet_schema_breadcrumb($canonical, [
    ['Home', RELET_SITE_URL . '/'],
    ['Advice', RELET_SITE_URL . '/blog/'],
]);
$items = [];
foreach ($visible as $index => $post) {
    $postUrl = $postPublicUrl($post);
    $items[] = [
        '@type' => 'ListItem',
        'position' => $index + 1,
        'url' => $postUrl,
        'name' => $post['title'],
    ];
}
$list = [
    '@type' => 'ItemList',
    '@id' => $canonical . '#articles',
    'name' => 'Re-Let property advice',
    'itemListElement' => $items,
];
$pageSchema = [
    '@type' => 'CollectionPage',
    '@id' => $canonical . '#webpage',
    'url' => $canonical,
    'name' => 'Landlord Property Maintenance Advice | Re-Let Nottingham',
    'description' => 'Practical maintenance, void turnaround and joinery guidance for Nottingham landlords and letting agents from Re-Let.',
    'isPartOf' => ['@id' => RELET_SITE_URL . '/#website'],
    'about' => ['@id' => RELET_SITE_URL . '/#business'],
    'mainEntity' => ['@id' => $list['@id']],
    'breadcrumb' => ['@id' => $breadcrumb['@id']],
    'inLanguage' => 'en-GB',
];
$schema = ['@context' => 'https://schema.org', '@graph' => [relet_schema_business(false), relet_schema_website(), $pageSchema, $list, $breadcrumb]];
relet_public_header('Landlord Property Maintenance Advice | Re-Let Nottingham', 'Practical maintenance, void turnaround and joinery guidance for Nottingham landlords and letting agents from Re-Let.', $canonical, 'website', null, $schema);
?><main id="main"><section class="page-hero"><div class="container"><div class="breadcrumbs"><a href="/">Home</a> / Advice</div><p class="eyebrow">Property notes</p><h1>Property maintenance advice for Nottingham landlords and letting agents.</h1><p class="lead">Practical guidance on landlord repairs, void property turnarounds, cleaning, joinery and rental-property decisions.</p></div></section><section class="section"><div class="container"><?php if ($visible === []): ?><div class="card"><h2>No published articles yet</h2><p>New practical property guidance will appear here when it is published.</p></div><?php else: ?><div class="grid-3"><?php foreach ($visible as $post): ?><article class="card"><span class="tag">Property advice</span><h2 style="font-size:1.6rem"><a href="<?= relet_escape($postPublicUrl($post)) ?>"><?= relet_escape($post['title']) ?></a></h2><p><?= relet_escape($post['excerpt']) ?></p><a class="link" href="<?= relet_escape($postPublicUrl($post)) ?>">Read article →</a></article><?php endforeach; ?></div><?php endif; ?><?php if ($totalPages > 1): ?><nav class="pagination" aria-label="Blog pages"><?php if ($page > 1): ?><a class="btn btn-outline" href="?page=<?= $page - 1 ?>">Previous</a><?php endif; ?><span>Page <?= $page ?> of <?= $totalPages ?></span><?php if ($page < $totalPages): ?><a class="btn btn-outline" href="?page=<?= $page + 1 ?>">Next</a><?php endif; ?></nav><?php endif; ?></div></section><section class="section section-soft"><div class="container"><div class="section-head"><div><p class="eyebrow">Case studies</p><h2>Property work in practice</h2></div><a class="link" href="/case-studies.html">Nottingham property maintenance case studies →</a></div><p>Looking for practical help now? Explore <a href="/landlord-repairs.html">landlord property repairs in Nottingham</a>, <a href="/void-turnarounds.html">void property turnarounds</a> or <a href="/letting-agents.html">property maintenance for Nottingham letting agents</a>.</p></div></section></main><?php relet_public_footer();
