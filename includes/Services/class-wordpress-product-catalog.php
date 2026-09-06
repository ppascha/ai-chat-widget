<?php

/**
 * Purpose: WordPress-backed product catalog adapter used by chat and storefront integrations.
 * Highlights:
 * - Reads site-owned product posts and normalizes them into transport-safe arrays.
 * - Exposes canonical product URLs so chat cards and iframe previews can deep-link to real product pages.
 */

namespace AICW\Services;

use AICW\Content\Product_Post_Type;
use AICW\Contracts\Product_Catalog_Interface;

class WordPress_Product_Catalog implements Product_Catalog_Interface
{
    public function all(): array
    {
        $posts = get_posts(
            [
                'post_type' => Product_Post_Type::POST_TYPE,
                'post_status' => 'publish',
                'numberposts' => -1,
                'orderby' => 'menu_order title',
                'order' => 'ASC',
            ]
        );

        return array_values(array_map(
            fn (\WP_Post $post): array => $this->presentPost($post),
            $posts
        ));
    }

    public function findByCategory(string $category): array
    {
        $normalizedCategory = $this->normalizeCategory($category);

        $posts = get_posts(
            [
                'post_type' => Product_Post_Type::POST_TYPE,
                'post_status' => 'publish',
                'numberposts' => -1,
                'meta_key' => '_aicw_category',
                'meta_value' => $normalizedCategory,
                'orderby' => 'menu_order title',
                'order' => 'ASC',
            ]
        );

        return array_values(array_map(
            fn (\WP_Post $post): array => $this->presentPost($post),
            $posts
        ));
    }

    public function findBySlug(string $slug): ?array
    {
        $post = get_page_by_path($slug, OBJECT, Product_Post_Type::POST_TYPE);

        return $post instanceof \WP_Post ? $this->presentPost($post) : null;
    }

    private function normalizeCategory(string $category): string
    {
        $normalized = strtolower(trim($category));

        if (in_array($normalized, ['ps', 'pss', 'playstation', 'playstations'], true)) {
            return 'ps';
        }

        return 'pc';
    }

    /**
     * @return array<string, mixed>
     */
    private function presentPost(\WP_Post $post): array
    {
        $category = (string) get_post_meta($post->ID, '_aicw_category', true);
        $permalink = get_permalink($post);

        return [
            'post_id' => $post->ID,
            'slug' => $post->post_name,
            'title' => get_the_title($post),
            'category' => $category,
            'summary' => wp_strip_all_tags((string) get_the_excerpt($post)),
            // Canonical storefront URL keeps chat links aligned with the main site product pages.
            'productUrl' => is_string($permalink) ? $permalink : '',
        ];
    }
}