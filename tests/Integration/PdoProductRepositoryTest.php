<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\PdoProductRepository;
use App\Product;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * INTEGRATION test: checks the real SQL against a real (but temporary) database.
 * SQLite ":memory:" lives only in RAM and disappears after each test.
 */
final class PdoProductRepositoryTest extends TestCase
{
    private PdoProductRepository $repo;

    protected function setUp(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec(file_get_contents(__DIR__ . '/../../database/schema.sql'));
        $this->repo = new PdoProductRepository($pdo);
    }

    public function test_insert_and_find_by_id(): void
    {
        $id = $this->repo->insert(new Product(null, 'Pencil', 5000, 3));

        $found = $this->repo->findById($id);

        $this->assertEquals(new Product($id, 'Pencil', 5000, 3), $found);
    }

    public function test_find_by_id_returns_null_when_missing(): void
    {
        $this->assertNull($this->repo->findById(123));
    }

    public function test_find_all_returns_rows_in_id_order(): void
    {
        $this->repo->insert(new Product(null, 'Pencil', 5000));
        $this->repo->insert(new Product(null, 'Eraser', 2000));

        $names = array_map(fn ($p) => $p->name, $this->repo->findAll());

        $this->assertSame(['Pencil', 'Eraser'], $names);
    }

    public function test_update_writes_new_values(): void
    {
        $id = $this->repo->insert(new Product(null, 'Pencil', 5000));

        $this->repo->update(new Product($id, 'Pen', 7000, 9));

        $this->assertEquals(new Product($id, 'Pen', 7000, 9), $this->repo->findById($id));
    }

    public function test_delete_removes_row(): void
    {
        $id = $this->repo->insert(new Product(null, 'Pencil', 5000));

        $this->repo->delete($id);

        $this->assertNull($this->repo->findById($id));
    }

    public function test_exists_by_name(): void
    {
        $this->repo->insert(new Product(null, 'Pencil', 5000));

        $this->assertTrue($this->repo->existsByName('Pencil'));
        $this->assertFalse($this->repo->existsByName('Eraser'));
    }
}
