<?php

/**
 * Purpose: Seed the toy 5-product WordPress fixture used by local AI chat demos.
 * Highlights:
 * - Ensures product CPT entries exist with stable slugs and categories.
 * - Sets an explicit front page that links to seeded product routes.
 * - Keeps fixture seeding idempotent by updating existing records when present.
 */

$products = [
    [
        'title' => 'Alpha PC',
        'slug' => 'alpha-pc',
        'category' => 'pc',
        'summary' => 'Entry-level home and study PC.',
    ],
    [
        'title' => 'Delta PC',
        'slug' => 'delta-pc',
        'category' => 'pc',
        'summary' => 'Balanced desktop for everyday work.',
    ],
    [
        'title' => 'Omega PC',
        'slug' => 'omega-pc',
        'category' => 'pc',
        'summary' => 'High-performance desktop for creators.',
    ],
    [
        'title' => 'PS Five',
        'slug' => 'ps-five',
        'category' => 'ps',
        'summary' => 'Next-gen console for sofa gaming.',
    ],
    [
        'title' => 'PS Five Digital',
        'slug' => 'ps-five-digital',
        'category' => 'ps',
        'summary' => 'Disc-free console with a sleek build.',
    ],
];

// Ensure plugin hooks run so custom post types and related Storefront Integrations are registered.
do_action('init');

foreach ($products as $product) {
    // Reuse existing slugs so repeated seeding updates state instead of duplicating content.
    $existing = get_page_by_path($product['slug'], OBJECT, 'aicw_product');
    $post = [
        'post_title' => $product['title'],
        'post_name' => $product['slug'],
        'post_type' => 'aicw_product',
        'post_status' => 'publish',
        'post_excerpt' => $product['summary'],
        'post_content' => $product['summary'],
    ];

    if ($existing instanceof WP_Post) {
        $post['ID'] = $existing->ID;
        $id = wp_update_post($post, true);
    } else {
        $id = wp_insert_post($post, true);
    }

    if (!is_wp_error($id)) {
        // Category meta drives catalog filtering logic in the chat product flow.
        update_post_meta((int) $id, '_aicw_category', $product['category']);
    }
}

$homepageTemplate = __DIR__ . '/frontend-homepage.html';
// Keep homepage markup in a fixture file so site variants can swap frontend content easily.
$homepageContent = file_exists($homepageTemplate)
    ? (string) file_get_contents($homepageTemplate)
    : '<h1>AI Gadget Store</h1>';

$frontPage = get_page_by_path('toy-products', OBJECT, 'page');
$frontPost = [
    'post_title' => 'AI Gadget Store',
    'post_name' => 'toy-products',
    'post_type' => 'page',
    'post_status' => 'publish',
    'post_content' => $homepageContent,
];

if ($frontPage instanceof WP_Post) {
    $frontPost['ID'] = $frontPage->ID;
    $frontId = wp_update_post($frontPost, true);
} else {
    $frontId = wp_insert_post($frontPost, true);
}

if (!is_wp_error($frontId)) {
    // Route the site root to the fixture landing page so demos always open at the same UX.
    update_option('show_on_front', 'page');
    update_option('page_on_front', (int) $frontId);
    update_option('blogname', 'AI Gadget Store');
}
