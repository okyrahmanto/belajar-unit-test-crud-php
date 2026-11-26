<?php

declare(strict_types=1);

namespace App;

use PDO;

/**
 * The real repository, talking to a database through PDO.
 * Works with MySQL in production and SQLite in integration tests.
 */
final class PdoProductRepository implements ProductRepositoryInterface
{
    public function __construct(private PDO $pdo)
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function insert(Product $product): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO products (name, price, stock) VALUES (:name, :price, :stock)'
        );
        $stmt->execute([
            'name' => $product->name,
            'price' => $product->price,
            'stock' => $product->stock,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function findById(int $id): ?Product
    {
        $stmt = $this->pdo->prepare('SELECT * FROM products WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->toProduct($row) : null;
    }

    public function findAll(): array
    {
        $rows = $this->pdo->query('SELECT * FROM products ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn (array $row) => $this->toProduct($row), $rows);
    }

    public function update(Product $product): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE products SET name = :name, price = :price, stock = :stock WHERE id = :id'
        );
        $stmt->execute([
            'id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
            'stock' => $product->stock,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM products WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function existsByName(string $name): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM products WHERE name = :name');
        $stmt->execute(['name' => $name]);

        return (int) $stmt->fetchColumn() > 0;
    }

    private function toProduct(array $row): Product
    {
        return new Product((int) $row['id'], $row['name'], (int) $row['price'], (int) $row['stock']);
    }
}
