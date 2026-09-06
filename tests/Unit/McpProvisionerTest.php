<?php

/**
 * Purpose: Verify composed MCP provisioner collaborators.
 * Highlights:
 * - Confirms capability provision happens before MCP app construction.
 * - Confirms the public provision entrypoint remains one catalog argument.
 */

namespace AICW\Tests\Unit;

use AICW\Contracts\Mcp_App_Factory_Interface;
use AICW\Contracts\Mcp_App_Interface;
use AICW\Contracts\Mcp_Capability_Provider_Interface;
use AICW\Contracts\Mcp_Tool_Executor_Interface;
use AICW\Contracts\Product_Catalog_Interface;
use AICW\Contracts\ValueObjects\Mcp_Provisioning_Result;
use AICW\Services\WordPress_Mcp_Provisioner;
use PHPUnit\Framework\TestCase;

final class McpProvisionerTest extends TestCase
{
    public function testProvisionerComposesCapabilityProviderAndAppFactory(): void
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

        $state = new class {
            public array $steps = [];
        };
        $result = new Mcp_Provisioning_Result();
        $app = new class implements Mcp_App_Interface {
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

        $capabilityProvider = new class($state, $result) implements Mcp_Capability_Provider_Interface {
            public function __construct(
                private readonly object $state,
                private readonly Mcp_Provisioning_Result $result,
            ) {
            }

            public function provide(Product_Catalog_Interface $catalog): Mcp_Provisioning_Result
            {
                $this->state->steps[] = 'capabilities';

                return $this->result;
            }
        };

        $appFactory = new class($state, $app) implements Mcp_App_Factory_Interface {
            public function __construct(
                private readonly object $state,
                private readonly Mcp_App_Interface $app,
            ) {
            }

            public function create(
                Product_Catalog_Interface $catalog,
                Mcp_Provisioning_Result $provisioningResult,
                Mcp_Tool_Executor_Interface $toolExecutor
            ): Mcp_App_Interface {
                $this->state->steps[] = 'app';

                return $this->app;
            }
        };

        $toolExecutor = new class implements Mcp_Tool_Executor_Interface {
            public function execute(string $toolName, array $arguments): array
            {
                return [];
            }
        };

        $provisioner = new WordPress_Mcp_Provisioner($capabilityProvider, $appFactory, $toolExecutor);

        self::assertSame($app, $provisioner->provision($catalog));
        self::assertSame(['capabilities', 'app'], $state->steps);
    }
}
