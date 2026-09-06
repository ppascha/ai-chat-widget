<?php

/**
 * Purpose: Verify Storefront Integration registry selection behavior.
 * Highlights:
 * - Confirms registered Storefront Integrations are selected by one key.
 * - Confirms unsupported and unavailable selections fail explicitly.
 */

namespace AICW\Tests\Unit;

use AICW\Contracts\Mcp_App_Interface;
use AICW\Contracts\Product_Catalog_Interface;
use AICW\Contracts\Storefront_Integration_Interface;
use AICW\Services\Storefront_Integration_Factory;
use PHPUnit\Framework\TestCase;

final class StorefrontIntegrationFactoryTest extends TestCase
{
    public function testItSelectsARegisteredStorefrontIntegrationByKey(): void
    {
        $integration = $this->integration('demo-wp', true);
        $factory = new Storefront_Integration_Factory();
        $factory->register($integration);

        self::assertSame($integration, $factory->create('demo-wp'));
    }

    public function testItRejectsUnsupportedStorefrontIntegrationKeys(): void
    {
        $factory = new Storefront_Integration_Factory();

        $this->expectException(\InvalidArgumentException::class);
        $factory->create('missing');
    }

    public function testItRejectsUnavailableStorefrontIntegrations(): void
    {
        $factory = new Storefront_Integration_Factory();
        $factory->register($this->integration('demo-wp', false));

        $this->expectException(\RuntimeException::class);
        $factory->create('demo-wp');
    }

    private function integration(string $key, bool $available): Storefront_Integration_Interface
    {
        $catalog = new class implements Product_Catalog_Interface {
            public function all(): array
            {
                return [];
            }

            public function findByCategory(string $category): array
            {
                return [];
            }

            public function findBySlug(string $slug): ?array
            {
                return null;
            }
        };

        $mcpApp = new class implements Mcp_App_Interface {
            public function tools(): array
            {
                return [];
            }

            public function executeTool(string $toolName, array $arguments): array
            {
                return [];
            }

            public function resources(): array
            {
                return [];
            }

            public function productIframeUrl(string $slug): string
            {
                return '';
            }
        };

        return new class($key, $available, $catalog, $mcpApp) implements Storefront_Integration_Interface {
            public function __construct(
                private readonly string $key,
                private readonly bool $available,
                private readonly Product_Catalog_Interface $catalog,
                private readonly Mcp_App_Interface $mcpApp,
            ) {
            }

            public function key(): string
            {
                return $this->key;
            }

            public function isAvailable(): bool
            {
                return $this->available;
            }

            public function productCatalog(): Product_Catalog_Interface
            {
                return $this->catalog;
            }

            public function mcpApp(): Mcp_App_Interface
            {
                return $this->mcpApp;
            }
        };
    }
}
