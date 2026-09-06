<?php

/**
 * Purpose: Select a Storefront Integration for the active runtime.
 * Highlights:
 * - Keeps Storefront Integration selection separate from WordPress service construction.
 * - Allows new Storefront Integrations to register without changing selection logic.
 */

namespace AICW\Contracts;

interface Storefront_Integration_Factory_Interface
{
    public function register(Storefront_Integration_Interface $storefrontIntegration): void;

    /**
     * @throws \InvalidArgumentException When the Storefront Integration key is unsupported.
     * @throws \RuntimeException When the Storefront Integration is unavailable.
     */
    public function create(string $storefrontIntegrationKey): Storefront_Integration_Interface;
}