<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\ProductService;
use App\ValidationException;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemoryProductRepository;

/**
 * The same check as this MANUAL test, written as code:
 *
 *   1. Open /products/create in the browser
 *   2. Type name "Pencil" and price 0
 *   3. Click Save
 *   4. Check the error "Price must be greater than 0" shows
 *   5. Open the list and check Pencil was NOT saved
 */
final class ManualVsAutomatedTest extends TestCase
{
    public function test_create_rejects_zero_price(): void
    {
        $service = new ProductService(
            new InMemoryProductRepository());

        try {
            $service->create('Pencil', 0); // steps 1–3
        } catch (ValidationException $e) {
            $this->assertSame( // step 4
                'Price must be greater than 0.',
                $e->getMessage()
            );
        }

        $this->assertCount(0, $service->list()); // step 5
    }
}
