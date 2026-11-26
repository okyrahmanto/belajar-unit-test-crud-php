<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\ProductNotFoundException;
use App\ProductService;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemoryProductRepository;

final class ReadProductTest extends TestCase
{
    private ProductService $service;

    protected function setUp(): void
    {
        $this->service = new ProductService(new InMemoryProductRepository());
    }

    public function test_get_returns_existing_product(): void
    {
        $this->service->create('Pencil', 5000);
        $eraser = $this->service->create('Eraser', 2000);

        $found = $this->service->get($eraser->id);

        $this->assertSame('Eraser', $found->name);
    }

    public function test_get_throws_when_product_does_not_exist(): void
    {
        $this->expectException(ProductNotFoundException::class);
        $this->expectExceptionMessage('Product with id 99 not found.');

        $this->service->get(99);
    }

    public function test_list_returns_empty_array_when_no_products(): void
    {
        $this->assertSame([], $this->service->list());
    }

    public function test_list_returns_all_products_in_order(): void
    {
        $this->service->create('Pencil', 5000);
        $this->service->create('Eraser', 2000);
        $this->service->create('Ruler', 8000);

        $products = $this->service->list();

        $this->assertCount(3, $products);
        $this->assertSame(
            ['Pencil', 'Eraser', 'Ruler'],
            array_map(fn ($p) => $p->name, $products)
        );
    }
}
