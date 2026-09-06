<?php

/**
 * Purpose: Verify the shared MCP provisioner Template Method.
 * Highlights:
 * - Confirms concrete provisioners provide capabilities before app construction.
 * - Confirms the public provision entrypoint remains one catalog argument.
 */

namespace AICW\Tests\Unit;

use AICW\Contracts\Mcp_App_Interface;
use AICW\Contracts\Product_Catalog_Interface;
use AICW\Contracts\ValueObjects\Mcp_Provisioning_Result;
use AICW\Services\Abstract_Mcp_Provisioner;
use PHPUnit\Framework\TestCase;

final class McpProvisionerTest extends TestCase
{
    public function testTemplateMethodBuildsAnAppFromProvisionedCapabilities(): void
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

        $provisioner = new class extends Abstract_Mcp_Provisioner {
            public array $steps = [];

            protected function provisionCapabilities(Product_Catalog_Interface $catalog): Mcp_Provisioning_Result
            {
                $this->steps[] = 'capabilities';

                return new Mcp_Provisioning_Result();
            }

            protected function buildMcpApp(
                Product_Catalog_Interface $catalog,
                Mcp_Provisioning_Result $provisioningResult
            ): Mcp_App_Interface {
                $this->steps[] = 'app';

                return new class implements Mcp_App_Interface {
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
            }
        };

        $app = $provisioner->provision($catalog);

        self::assertInstanceOf(Mcp_App_Interface::class, $app);
        self::assertSame(['capabilities', 'app'], $provisioner->steps);
    }
}
