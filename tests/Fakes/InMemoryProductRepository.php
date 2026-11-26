<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Product;
use App\ProductRepositoryInterface;

/**
 * A FAKE repository: stores products in a PHP array instead of a database.
 * Fast, no setup, and every test starts with a clean, empty store.
 */
final class InMemoryProductRepository implements ProductRepositoryInterface
{
    /** @var array<int, Product> */
    private array $items = [];
    private int $nextId = 1;

    public function insert(Product $product): int
    {
        $id = $this->nextId++;
        $this->items[$id] = new Product($id, $product->name, $product->price, $product->stock);

        return $id;
    }

    public function findById(int $id): ?Product
    {
        $p = $this->items[$id] ?? null;

        // return a copy, like a real database would
        return $p ? new Product($p->id, $p->name, $p->price, $p->stock) : null;
    }

    public function findAll(): array
    {
        return array_values(array_map(fn (Product $p) => clone $p, $this->items));
    }

    public function update(Product $product): void
    {
        $this->items[$product->id] = clone $product;
    }

    public function delete(int $id): void
    {
        unset($this->items[$id]);
    }

    public function existsByName(string $name): bool
    {
        foreach ($this->items as $p) {
            if ($p->name === $name) {
                return true;
            }
        }

        return false;
    }
}
