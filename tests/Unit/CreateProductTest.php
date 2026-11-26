<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\ProductService;
use App\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemoryProductRepository;

final class CreateProductTest extends TestCase
{
    private ProductService $service;

    // Runs before EVERY test: each test gets a fresh, empty repository.
    protected function setUp(): void
    {
        $this->service = new ProductService(new InMemoryProductRepository());
    }

    public function test_create_product_with_valid_data(): void
    {
        // Arrange: done in setUp()

        // Act
        $product = $this->service->create('Pencil', 5000, 10);

        // Assert
        $this->assertSame(1, $product->id);
        $this->assertSame('Pencil', $product->name);
        $this->assertSame(5000, $product->price);
        $this->assertSame(10, $product->stock);
    }

    public function test_created_product_can_be_found_again(): void
    {
        $created = $this->service->create('Pencil', 5000);

        $found = $this->service->get($created->id);

        $this->assertEquals($created, $found);
    }

    public function test_name_is_trimmed(): void
    {
        $product = $this->service->create('   Pencil   ', 5000);

        $this->assertSame('Pencil', $product->name);
    }

    public function test_stock_defaults_to_zero(): void
    {
        $product = $this->service->create('Pencil', 5000);

        $this->assertSame(0, $product->stock);
    }

    public function test_create_rejects_empty_name(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Name is required.');

        $this->service->create('   ', 5000);
    }

    public function test_create_rejects_name_longer_than_100_characters(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->create(str_repeat('a', 101), 5000);
    }

    public function test_create_accepts_name_of_exactly_100_characters(): void
    {
        // Boundary value: 100 is allowed, 101 is not.
        $product = $this->service->create(str_repeat('a', 100), 5000);

        $this->assertSame(100, mb_strlen($product->name));
    }

    // One test, many inputs: the data provider feeds each row into the test.
    #[DataProvider('invalidPrices')]
    public function test_create_rejects_invalid_price(int $price): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Price must be greater than 0.');

        $this->service->create('Pencil', $price);
    }

    public static function invalidPrices(): array
    {
        return [
            'zero' => [0],
            'negative' => [-1],
            'very negative' => [-99999],
        ];
    }

    public function test_create_rejects_negative_stock(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Stock cannot be negative.');

        $this->service->create('Pencil', 5000, -1);
    }

    public function test_create_rejects_duplicate_name(): void
    {
        $this->service->create('Pencil', 5000);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Product 'Pencil' already exists.");

        $this->service->create('Pencil', 7000);
    }
}
