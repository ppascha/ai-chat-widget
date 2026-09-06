<?php

/**
 * Purpose: Seed a standalone WooCommerce test storefront.
 * Highlights:
 * - Creates deterministic WooCommerce products by SKU.
 * - Assigns products to categories and sets descriptions.
 * - Keeps fixture seeding idempotent by updating existing products.
 */

if (!class_exists('WC_Product_Simple')) {
    fwrite(STDERR, "WooCommerce is unavailable; cannot seed WooTestSite1.\n");
    exit(1);
}

// Ensure WooCommerce product categories exist.
$categories = [
    'pc' => 'Personal Computers',
    'ps' => 'PlayStation',
];

foreach ($categories as $slug => $name) {
    if (!term_exists($slug, 'product_cat')) {
        wp_insert_term($name, 'product_cat', ['slug' => $slug]);
    }
}

// Seed products with categories and descriptions.
$products = [
    ['sku' => 'woo-alpha-pc', 'name' => 'Woo Alpha PC', 'price' => '999', 'category' => 'pc', 'summary' => 'Entry-level home and study PC.'],
    ['sku' => 'woo-beta-pc', 'name' => 'Woo Beta PC', 'price' => '1299', 'category' => 'pc', 'summary' => 'Balanced desktop for everyday work.'],
    ['sku' => 'woo-gamma-pc', 'name' => 'Woo Gamma PC', 'price' => '1799', 'category' => 'pc', 'summary' => 'High-performance desktop for creators.'],
    ['sku' => 'woo-ps5', 'name' => 'Woo PS Five', 'price' => '499', 'category' => 'ps', 'summary' => 'Next-gen console for sofa gaming.'],
    ['sku' => 'woo-ps5-digital', 'name' => 'Woo PS Five Digital', 'price' => '399', 'category' => 'ps', 'summary' => 'Disc-free console with a sleek build.'],
];

foreach ($products as $spec) {
    $productId = wc_get_product_id_by_sku($spec['sku']);
    $product = $productId ? wc_get_product($productId) : new WC_Product_Simple();
    $product->set_name($spec['name']);
    $product->set_sku($spec['sku']);
    $product->set_regular_price($spec['price']);
    $product->set_description($spec['summary']);
    $product->set_short_description($spec['summary']);
    $product->set_status('publish');
    
    // Assign to WooCommerce category by slug.
    $term = get_term_by('slug', $spec['category'], 'product_cat');
    if ($term) {
        wp_set_object_terms($product->get_id() ?: 0, [$term->term_id], 'product_cat');
    }
    
    $product->save();
}
