<?php

/**
 * Purpose: In-process MCP app facade that declares and executes product tools/resources.
 * Highlights:
 * - Publishes model-callable function schemas used by the chat loop.
 * - Resolves product data and iframe resource URLs from site-owned catalog content.
 */

namespace AICW\Services;

use AICW\Contracts\Mcp_App_Interface;
use AICW\Contracts\Product_Catalog_Interface;

class WordPress_Mcp_App implements Mcp_App_Interface
{
    public function __construct(
        private readonly Product_Catalog_Interface $catalog
    ) {
    }

    public function tools(): array
    {
        // Tool schemas are OpenAI-compatible function declarations; easy to map to remote MCP later.
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'list_products',
                    'description' => 'Primary product retrieval tool. Use this for list, category, and specific product requests so UI can render product cards.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'category' => [
                                'type' => 'string',
                                'description' => 'Optional product category such as pc or ps.',
                            ],
                            'slug' => [
                                'type' => 'string',
                                'description' => 'Optional product slug.',
                            ],
                            'search' => [
                                'type' => 'string',
                                'description' => 'Optional text search against product title or summary.',
                            ],
                            'limit' => [
                                'type' => 'integer',
                                'description' => 'Optional maximum number of products to return.',
                                'minimum' => 1,
                            ],
                        ],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'find_products',
                    'description' => 'Alias retrieval tool for product filtering by category, search term, or slug.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'category' => [
                                'type' => 'string',
                                'description' => 'Optional product category such as pc or ps.',
                            ],
                            'slug' => [
                                'type' => 'string',
                                'description' => 'Optional product slug.',
                            ],
                            'search' => [
                                'type' => 'string',
                                'description' => 'Optional text search against product title or summary.',
                            ],
                            'limit' => [
                                'type' => 'integer',
                                'description' => 'Optional maximum number of products to return.',
                                'minimum' => 1,
                            ],
                        ],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_product_iframe_url',
                    'description' => 'Secondary tool for iframe resource lookup by slug. Prefer list_products/find_products first for product requests.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'slug' => [
                                'type' => 'string',
                                'description' => 'Product slug.',
                            ],
                        ],
                        'required' => ['slug'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
        ];
    }

    public function executeTool(string $toolName, array $arguments): array
    {
        // Central dispatch keeps all tool execution behavior in one boundary class.
        return match ($toolName) {
            'list_products' => $this->resolveProducts($arguments),
            'find_products' => $this->resolveProducts($arguments),
            'get_product_iframe_url' => $this->resolveIframeResource($arguments),
            default => [
                'error' => 'Unknown tool: ' . $toolName,
            ],
        };
    }

    public function resources(): array
    {
        // Resource list bridges site products to iframe-addressable URIs for chat card rendering.
        return array_map(
            fn (array $product): array => [
                'uri' => $this->productIframeUrl($product['slug']),
                'name' => $product['slug'],
                'title' => $product['title'],
                'description' => $product['summary'],
            ],
            $this->catalog->all()
        );
    }

    public function productIframeUrl(string $slug): string
    {
        // Keep URL construction here so route strategy changes do not leak into controllers or loop logic.
        return add_query_arg(
            'aicw_product_iframe',
            sanitize_title($slug),
            home_url('/')
        );
    }

    /**
     * @param array<string, mixed> $arguments
     */
    private function resolveProducts(array $arguments): array
    {
        // Resolve canonical filter arguments first; this mirrors how WooCommerce query params are usually normalized.
        $slug = sanitize_title((string) ($arguments['slug'] ?? ''));
        $category = strtolower(trim((string) ($arguments['category'] ?? '')));
        $search = strtolower(trim((string) ($arguments['search'] ?? '')));
        $limit = isset($arguments['limit']) ? max(1, (int) $arguments['limit']) : 0;

        // Slug has highest specificity and bypasses broader category/search lookups.
        if ('' !== $slug) {
            $product = $this->catalog->findBySlug($slug);

            return [
                'products' => null === $product ? [] : [$product],
            ];
        }

        if ('' !== $category) {
            $products = $this->catalog->findByCategory($category);
        } else {
            $products = $this->catalog->all();
        }

        // Search is applied after category narrowing to emulate layered storefront filtering.
        if ('' !== $search) {
            $products = array_values(array_filter(
                $products,
                static fn (array $product): bool => str_contains(strtolower((string) $product['title']), $search) || str_contains(strtolower((string) $product['summary']), $search)
            ));
        }

        // Limit is optional and applied last so callers can cap payload size.
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

        if ('' === $slug) {
            return [
                'error' => 'slug is required for get_product_iframe_url.',
                'products' => [],
            ];
        }

        $product = $this->catalog->findBySlug($slug);

        // Return products with iframeUrl so chat loop can keep kind=products and frontend can render cards.
        if (null !== $product) {
            $product['iframeUrl'] = $this->productIframeUrl($slug);

            return [
                'slug' => $slug,
                'iframeUrl' => $product['iframeUrl'],
                'products' => [$product],
            ];
        }

        return [
            'slug' => $slug,
            'iframeUrl' => $this->productIframeUrl($slug),
            'products' => [],
        ];
    }
}