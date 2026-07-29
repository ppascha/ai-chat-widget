<?php

/**
 * Purpose: Expose product data and iframe HTML through REST endpoints.
 * Highlights:
 * - Returns catalog records normalized for widget rendering.
 * - Provides HTML fallback iframe endpoint by slug.
 */

namespace AICW\Controllers;

use AICW\Contracts\Product_Catalog_Interface;
use AICW\Contracts\Mcp_App_Interface;

class Product_Controller
{
    public function __construct(
        private readonly Product_Catalog_Interface $catalog,
        private readonly Mcp_App_Interface $mcpApp
    ) {
    }

    public function index($request)
    {
        // Normalize all products into response-safe payloads expected by the frontend cards.
        $products = array_map(
            fn (array $product): array => $this->presentProduct($product),
            $this->catalog->all()
        );

        return [
            'success' => true,
            'products' => $products,
        ];
    }

    public function iframe($request)
    {
        // Slug comes from route param and must be normalized before catalog lookup.
        $slug = sanitize_title((string) $request->get_param('slug'));
        $product = $this->catalog->findBySlug($slug);

        if (null === $product) {
            // Keep 404 semantics explicit so embedding clients can branch correctly.
            return new \WP_Error(
                'aicw_product_not_found',
                'Product not found.',
                ['status' => 404]
            );
        }

        $html = $this->renderIframeHtml($product);

        // This route serves actual HTML, not JSON, so iframe consumers can render it directly.
        $response = new \WP_REST_Response($html, 200);
        $response->header('Content-Type', 'text/html; charset=utf-8');

        return $response;
    }

    /**
     * @param array<string, mixed> $product
     * @return array<string, mixed>
     */
    private function presentProduct(array $product): array
    {
        return [
            'post_id' => $product['post_id'] ?? null,
            'slug' => $product['slug'],
            'title' => $product['title'],
            'category' => $product['category'] ?? '',
            'summary' => $product['summary'],
            'productUrl' => $product['productUrl'] ?? '',
            // URL is delegated to MCP app so resource hosting can move out-of-process later.
            'iframeUrl' => $this->mcpApp->productIframeUrl($product['slug']),
        ];
    }

    /**
     * @param array<string, mixed> $product
     */
    private function renderIframeHtml(array $product): string
    {
        // Template is intentionally minimal: this endpoint demonstrates iframe rendering without theme coupling.
        ob_start();
        ?>
        <html>
            <head>
                <meta charset="utf-8" />
                <meta name="viewport" content="width=device-width, initial-scale=1" />
                <style>
                    body { font-family: sans-serif; margin: 0; padding: 12px; background: transparent; color: #1d2327; }
                    .content { padding: 0; }
                    .meta { color: #646970; font-size: 14px; margin: 0 0 8px; }
                    .summary { margin: 0; font-size: 16px; line-height: 1.4; }
                </style>
            </head>
            <body>
                <div class="content">
                    <p class="meta"><?php echo esc_html(strtoupper((string) $product['category'])); ?></p>
                    <p class="summary"><?php echo esc_html((string) $product['summary']); ?></p>
                </div>
            </body>
        </html>
        <?php

        return (string) ob_get_clean();
    }
}