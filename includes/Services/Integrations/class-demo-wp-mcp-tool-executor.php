<?php

/**
 * Purpose: Execute product tools for the Demo WP Storefront Integration.
 * Highlights:
 * - Owns demo product filtering and iframe-resource lookup behavior.
 * - Keeps Storefront-specific tool dispatch outside the generic MCP app.
 */

namespace AICW\Services\Integrations;

use AICW\Contracts\Mcp_Tool_Executor_Interface;
use AICW\Contracts\Product_Catalog_Interface;

class Demo_WP_Mcp_Tool_Executor implements Mcp_Tool_Executor_Interface
{
    public function __construct(
        private readonly Product_Catalog_Interface $catalog,
    ) {
    }

    public function execute(string $toolName, array $arguments): array
    {
        return match ($toolName) {
            'list_products', 'find_products' => $this->resolveProducts($arguments),
            'get_product_iframe_url' => $this->resolveIframeResource($arguments),
            default => [
                'error' => 'Unknown tool: ' . $toolName,
            ],
        };
    }

    /**
     * @param array<string, mixed> $arguments
     * @return array<string, mixed>
     */
    private function resolveProducts(array $arguments): array
    {
        $slug = sanitize_title((string) ($arguments['slug'] ?? ''));
        $category = strtolower(trim((string) ($arguments['category'] ?? '')));
        $search = strtolower(trim((string) ($arguments['search'] ?? '')));
        $limit = isset($arguments['limit']) ? max(1, (int) $arguments['limit']) : 0;

        if ('' !== $slug) {
            $product = $this->catalog->findBySlug($slug);

            return [
                'products' => null === $product ? [] : [$product],
            ];
        }

        $products = '' !== $category
            ? $this->catalog->findByCategory($category)
            : $this->catalog->all();

        if ('' !== $search) {
            $products = array_values(array_filter(
                $products,
                static fn (array $product): bool => str_contains(strtolower((string) $product['title']), $search) || str_contains(strtolower((string) $product['summary']), $search)
            ));
        }

        if ($limit > 0) {
            $products = array_slice($products, 0, $limit);
        }

        return [
            'products' => $products,
        ];
    }

    /**
     * @param array<string, mixed> $arguments
     * @return array<string, mixed>
     */
    private function resolveIframeResource(array $arguments): array
    {
        $slug = sanitize_title((string) ($arguments['slug'] ?? ''));
        $iframeUrl = $this->iframeUrl($slug);

        if ('' === $slug) {
            return [
                'error' => 'slug is required for get_product_iframe_url.',
                'products' => [],
            ];
        }

        $product = $this->catalog->findBySlug($slug);

        if (null !== $product) {
            $product['iframeUrl'] = $iframeUrl;

            return [
                'slug' => $slug,
                'iframeUrl' => $iframeUrl,
                'products' => [$product],
            ];
        }

        return [
            'slug' => $slug,
            'iframeUrl' => $iframeUrl,
            'products' => [],
        ];
    }

    private function iframeUrl(string $slug): string
    {
        return add_query_arg(
            'aicw_product_iframe',
            $slug,
            home_url('/')
        );
    }
}
