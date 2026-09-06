<?php

/**
 * Purpose: WooCommerce-backed Storefront product catalog adapter.
 * Highlights:
 * - Reads published WooCommerce products and product categories.
 * - Normalizes products to the shared catalog contract.
 */

namespace AICW\Services\Integrations;

use AICW\Contracts\Product_Catalog_Interface;

class WooCommerce_Product_Catalog implements Product_Catalog_Interface
{
    public function all(): array
    {
        return $this->map(get_posts(['post_type' => 'product', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'menu_order title', 'order' => 'ASC']));
    }

    public function findByCategory(string $category): array
    {
        return $this->map(get_posts(['post_type' => 'product', 'post_status' => 'publish', 'numberposts' => -1, 'tax_query' => [['taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => sanitize_title($category)]]]));
    }

    public function findBySlug(string $slug): ?array
    {
        $post = get_page_by_path(sanitize_title($slug), OBJECT, 'product');

        return $post instanceof \WP_Post ? $this->present($post) : null;
    }

    private function map(array $posts): array
    {
        return array_values(array_map(fn (\WP_Post $post): array => $this->present($post), $posts));
    }

    private function present(\WP_Post $post): array
    {
        $terms = wp_get_post_terms($post->ID, 'product_cat', ['fields' => 'slugs']);

        return [
            'post_id' => $post->ID,
            'slug' => $post->post_name,
            'title' => get_the_title($post),
            'category' => is_wp_error($terms) || [] === $terms ? '' : (string) $terms[0],
            'summary' => wp_strip_all_tags((string) get_the_excerpt($post)),
            'productUrl' => (string) get_permalink($post),
        ];
    }
}
