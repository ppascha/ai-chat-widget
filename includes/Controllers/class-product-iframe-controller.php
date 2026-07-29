<?php

/**
 * Purpose: Render product iframe HTML for the aicw_product_iframe query endpoint.
 * Highlights:
 * - Hooks template_redirect to return standalone HTML for embedded product previews.
 * - Keeps iframe output minimal so host chat cards remain the primary visual container.
 */

namespace AICW\Controllers;

use AICW\Contracts\Product_Catalog_Interface;

class Product_Iframe_Controller
{
    private static ?Product_Catalog_Interface $catalog = null;

    public static function init(Product_Catalog_Interface $catalog): void
    {
        self::$catalog = $catalog;

        // Register the custom query var so WordPress exposes it to get_query_var().
        add_filter('query_vars', [self::class, 'register_query_var']);

        // Intercept a dedicated frontend query var so iframe content is rendered as real HTML.
        add_action('template_redirect', [self::class, 'maybe_render_iframe']);
    }

    /**
     * @param string[] $queryVars
     * @return string[]
     */
    public static function register_query_var(array $queryVars): array
    {
        $queryVars[] = 'aicw_product_iframe';

        return $queryVars;
    }

    public static function maybe_render_iframe(): void
    {
        $slug = sanitize_title((string) get_query_var('aicw_product_iframe'));

        if ('' === $slug || null === self::$catalog) {
            return;
        }

        $product = self::$catalog->findBySlug($slug);

        if (null === $product) {
            status_header(404);
            nocache_headers();
            echo 'Product not found.';
            exit;
        }

        nocache_headers();
        header('Content-Type: text/html; charset=utf-8');

        echo self::render($product);
        exit;
    }

    /**
     * @param array<string, mixed> $product
     */
    private static function render(array $product): string
    {
        ob_start();
        ?>
        <html>
            <head>
                <meta charset="utf-8" />
                <meta name="viewport" content="width=device-width, initial-scale=1" />
                <style>
                    body { font-family: sans-serif; margin: 0; padding: 12px; background: transparent; color: #1d2327; }
                    .meta { color: #646970; font-size: 13px; margin: 0 0 8px; }
                    .summary { margin: 0; font-size: 16px; line-height: 1.45; }
                    .hit-area { color: inherit; text-decoration: none; display: block; }
                    .open-link { margin-top: 10px; font-size: 13px; color: #2271b1; font-weight: 600; }
                </style>
            </head>
            <body>
                <?php $productUrl = (string) ($product['productUrl'] ?? ''); ?>
                <?php if ('' !== $productUrl) : ?>
                    <a class="hit-area" href="<?php echo esc_url($productUrl); ?>" target="_top" rel="noopener noreferrer">
                        <div>
                            <div class="meta"><?php echo esc_html(strtoupper((string) $product['category'])); ?></div>
                            <p class="summary"><?php echo esc_html((string) $product['summary']); ?></p>
                            <p class="open-link">Open full product page</p>
                        </div>
                    </a>
                <?php else : ?>
                    <div>
                        <div class="meta"><?php echo esc_html(strtoupper((string) $product['category'])); ?></div>
                        <p class="summary"><?php echo esc_html((string) $product['summary']); ?></p>
                    </div>
                <?php endif; ?>
            </body>
        </html>
        <?php

        return (string) ob_get_clean();
    }
}