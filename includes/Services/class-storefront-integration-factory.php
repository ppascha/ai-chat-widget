<?php

/**
 * Purpose: Registry-backed selector for Storefront Integrations.
 * Highlights:
 * - Selects Storefront Integrations by a primitive configuration key.
 * - Allows extensions to register Storefront Integrations without changing selection logic.
 */

namespace AICW\Services;

use AICW\Contracts\Storefront_Integration_Interface;
use AICW\Contracts\Storefront_Integration_Factory_Interface;

class Storefront_Integration_Factory implements Storefront_Integration_Factory_Interface
{
    /**
     * @var array<string, Storefront_Integration_Interface>
     */
    private array $storefrontIntegrations = [];

    public function register(Storefront_Integration_Interface $storefrontIntegration): void
    {
        $this->storefrontIntegrations[$storefrontIntegration->key()] = $storefrontIntegration;
    }

    public function create(string $storefrontIntegrationKey): Storefront_Integration_Interface
    {
        $storefrontIntegration = $this->storefrontIntegrations[$storefrontIntegrationKey] ?? null;

        if (null === $storefrontIntegration) {
            throw new \InvalidArgumentException(
                sprintf('Unsupported Storefront Integration: %s', $storefrontIntegrationKey)
            );
        }

        if (!$storefrontIntegration->isAvailable()) {
            throw new \RuntimeException(
                sprintf('Storefront Integration is unavailable: %s', $storefrontIntegrationKey)
            );
        }

        return $storefrontIntegration;
    }
}
