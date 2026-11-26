<?php

declare(strict_types=1);

namespace App;

/**
 * Business logic for products. THIS is what we unit test.
 *
 * Rules:
 *  - name is required, max 100 characters, and must be unique
 *  - price must be greater than 0
 *  - stock cannot be negative
 */
final class ProductService
{
    public function __construct(private ProductRepositoryInterface $repository)
    {
    }

    // ---------- CREATE ----------
    public function create(string $name, int $price, int $stock = 0): Product
    {
        $name = trim($name);
        $this->validate($name, $price, $stock);

        if ($this->repository->existsByName($name)) {
            throw new ValidationException("Product '{$name}' already exists.");
        }

        $product = new Product(null, $name, $price, $stock);
        $product->id = $this->repository->insert($product);

        return $product;
    }

    // ---------- READ ----------
    public function get(int $id): Product
    {
        $product = $this->repository->findById($id);

        if ($product === null) {
            throw ProductNotFoundException::withId($id);
        }

        return $product;
    }

    /** @return Product[] */
    public function list(): array
    {
        return $this->repository->findAll();
    }

    // ---------- UPDATE ----------
    public function update(int $id, string $name, int $price, int $stock): Product
    {
        $product = $this->get($id); // throws if not found

        $name = trim($name);
        $this->validate($name, $price, $stock);

        if ($name !== $product->name && $this->repository->existsByName($name)) {
            throw new ValidationException("Product '{$name}' already exists.");
        }

        $product->name = $name;
        $product->price = $price;
        $product->stock = $stock;
        $this->repository->update($product);

        return $product;
    }

    // ---------- DELETE ----------
    public function delete(int $id): void
    {
        $this->get($id); // throws if not found
        $this->repository->delete($id);
    }

    private function validate(string $name, int $price, int $stock): void
    {
        if ($name === '') {
            throw new ValidationException('Name is required.');
        }
        if (mb_strlen($name) > 100) {
            throw new ValidationException('Name must be at most 100 characters.');
        }
        if ($price <= 0) {
            throw new ValidationException('Price must be greater than 0.');
        }
        if ($stock < 0) {
            throw new ValidationException('Stock cannot be negative.');
        }
    }
}
