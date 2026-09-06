<?php

/**
 * Purpose: Provide MCP tools and resources shared by any product-catalog-backed Storefront Integration.
 * Highlights:
 * - Owns the product tool schemas previously embedded in the generic MCP app.
 * - Maps catalog products to iframe-addressable resource definitions.
 * - Depends only on Product_Catalog_Interface, so it is reused as-is by Demo WP and WooCommerce.
 */

namespace AICW\Services\Integrations;

use AICW\Contracts\Mcp_Capability_Provider_Interface;
use AICW\Contracts\Product_Catalog_Interface;
use AICW\Contracts\ValueObjects\Mcp_Provisioning_Result;
use AICW\Contracts\ValueObjects\Mcp_Resource_Definition;
use AICW\Contracts\ValueObjects\Mcp_Tool_Definition;

class Catalog_Mcp_Capability_Provider implements Mcp_Capability_Provider_Interface
{
    public function provide(Product_Catalog_Interface $catalog): Mcp_Provisioning_Result
    {
        return new Mcp_Provisioning_Result(
            tools: $this->tools(),
            resources: $this->resources($catalog),
        );
    }

    /**
     * @return array<int, Mcp_Tool_Definition>
     */
    private function tools(): array
    {
        $productParameters = [
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
        ];

        return [
            new Mcp_Tool_Definition(
                name: 'list_products',
                description: 'Primary product retrieval tool. Use this for list, category, and specific product requests so UI can render product cards.',
                parameters: $productParameters,
            ),
            new Mcp_Tool_Definition(
                name: 'find_products',
                description: 'Alias retrieval tool for product filtering by category, search term, or slug.',
                parameters: $productParameters,
            ),
            new Mcp_Tool_Definition(
                name: 'get_product_iframe_url',
                description: 'Secondary tool for iframe resource lookup by slug. Prefer list_products/find_products first for product requests.',
                parameters: [
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
            ),
        ];
    }

    /**
     * @return array<int, Mcp_Resource_Definition>
     */
    private function resources(Product_Catalog_Interface $catalog): array
    {
        return array_map(
            static fn (array $product): Mcp_Resource_Definition => new Mcp_Resource_Definition(
                uri: add_query_arg('aicw_product_iframe', sanitize_title($product['slug']), home_url('/')),
                name: $product['slug'],
                title: $product['title'],
                description: $product['summary'],
            ),
            $catalog->all()
        );
    }
}
