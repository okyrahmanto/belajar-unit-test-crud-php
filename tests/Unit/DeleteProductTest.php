<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\ProductNotFoundException;
use App\ProductService;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemoryProductRepository;

final class DeleteProductTest extends TestCase
{
    private ProductService $service;

    protected function setUp(): void
    {
        $this->service = new ProductService(new InMemoryProductRepository());
    }

    public function test_deleted_product_is_gone(): void
    {
        $product = $this->service->create('Pencil', 5000);

        $this->service->delete($product->id);

        $this->expectException(ProductNotFoundException::class);
        $this->service->get($product->id);
    }

    public function test_delete_only_removes_one_product(): void
    {
        $pencil = $this->service->create('Pencil', 5000);
        $this->service->create('Eraser', 2000);

        $this->service->delete($pencil->id);

        $this->assertCount(1, $this->service->list());
        $this->assertSame('Eraser', $this->service->list()[0]->name);
    }

    public function test_delete_throws_when_product_does_not_exist(): void
    {
        $this->expectException(ProductNotFoundException::class);

        $this->service->delete(99);
    }

    public function test_deleting_twice_throws_the_second_time(): void
    {
        $product = $this->service->create('Pencil', 5000);
        $this->service->delete($product->id);

        $this->expectException(ProductNotFoundException::class);

        $this->service->delete($product->id);
    }

    public function test_name_can_be_reused_after_delete(): void
    {
        $product = $this->service->create('Pencil', 5000);
        $this->service->delete($product->id);

        $again = $this->service->create('Pencil', 6000);

        $this->assertSame('Pencil', $again->name);
    }
}
