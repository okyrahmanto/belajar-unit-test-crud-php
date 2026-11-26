<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\ProductNotFoundException;
use App\ProductService;
use App\ValidationException;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemoryProductRepository;

final class UpdateProductTest extends TestCase
{
    private ProductService $service;

    protected function setUp(): void
    {
        $this->service = new ProductService(new InMemoryProductRepository());
    }

    public function test_update_changes_are_saved(): void
    {
        $product = $this->service->create('Pencil', 5000, 10);

        $this->service->update($product->id, 'Blue Pencil', 6000, 5);

        $saved = $this->service->get($product->id);
        $this->assertSame('Blue Pencil', $saved->name);
        $this->assertSame(6000, $saved->price);
        $this->assertSame(5, $saved->stock);
    }

    public function test_update_can_keep_the_same_name(): void
    {
        // Not a "duplicate" just because it matches its own current name.
        $product = $this->service->create('Pencil', 5000);

        $updated = $this->service->update($product->id, 'Pencil', 9000, 0);

        $this->assertSame(9000, $updated->price);
    }

    public function test_update_does_not_change_other_products(): void
    {
        $pencil = $this->service->create('Pencil', 5000);
        $eraser = $this->service->create('Eraser', 2000);

        $this->service->update($pencil->id, 'Pencil', 9999, 0);

        $this->assertSame(2000, $this->service->get($eraser->id)->price);
    }

    public function test_update_throws_when_product_does_not_exist(): void
    {
        $this->expectException(ProductNotFoundException::class);

        $this->service->update(99, 'Pencil', 5000, 0);
    }

    public function test_update_rejects_invalid_price(): void
    {
        $product = $this->service->create('Pencil', 5000);

        $this->expectException(ValidationException::class);

        $this->service->update($product->id, 'Pencil', 0, 0);
    }

    public function test_update_rejects_name_used_by_another_product(): void
    {
        $this->service->create('Pencil', 5000);
        $eraser = $this->service->create('Eraser', 2000);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Product 'Pencil' already exists.");

        $this->service->update($eraser->id, 'Pencil', 2000, 0);
    }

    public function test_failed_update_leaves_product_unchanged(): void
    {
        $product = $this->service->create('Pencil', 5000);

        try {
            $this->service->update($product->id, '', 5000, 0);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException) {
            // expected
        }

        $this->assertSame('Pencil', $this->service->get($product->id)->name);
    }
}
