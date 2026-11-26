<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Product;
use App\ProductRepositoryInterface;
use App\ProductService;
use App\ValidationException;
use PHPUnit\Framework\TestCase;

/**
 * Advanced: MOCKS.
 * A fake (InMemoryProductRepository) checks the RESULT.
 * A mock checks the CONVERSATION: "was insert() called? with what? how many times?"
 */
final class ProductServiceMockTest extends TestCase
{
    public function test_create_calls_insert_exactly_once(): void
    {
        $repo = $this->createMock(ProductRepositoryInterface::class);
        $repo->method('existsByName')->willReturn(false);

        $repo->expects($this->once())
            ->method('insert')
            ->with($this->callback(fn (Product $p) => $p->name === 'Pencil' && $p->price === 5000))
            ->willReturn(42);

        $product = (new ProductService($repo))->create('Pencil', 5000);

        $this->assertSame(42, $product->id);
    }

    public function test_invalid_product_never_reaches_the_database(): void
    {
        $repo = $this->createMock(ProductRepositoryInterface::class);
        $repo->expects($this->never())->method('insert');

        $this->expectException(ValidationException::class);

        (new ProductService($repo))->create('Pencil', -5);
    }

    public function test_delete_of_missing_product_never_calls_repository_delete(): void
    {
        $repo = $this->createMock(ProductRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);
        $repo->expects($this->never())->method('delete');

        $this->expectException(\App\ProductNotFoundException::class);

        (new ProductService($repo))->delete(7);
    }
}
