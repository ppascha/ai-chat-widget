<?php

namespace AICW\Contracts;

interface Product_Catalog_Interface
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findByCategory(string $category): array;

    /**
     * @return array<string, mixed>|null
     */
    public function findBySlug(string $slug): ?array;
}