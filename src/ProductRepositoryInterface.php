<?php

declare(strict_types=1);

namespace App;

/**
 * The "contract" for storage. The service only knows this interface,
 * so in tests we can swap the real database for a fake one.
 */
interface ProductRepositoryInterface
{
    /** Saves a new product and returns its new id. */
    public function insert(Product $product): int;

    public function findById(int $id): ?Product;

    /** @return Product[] */
    public function findAll(): array;

    public function update(Product $product): void;

    public function delete(int $id): void;

    public function existsByName(string $name): bool;
}
