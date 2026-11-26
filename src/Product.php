<?php

declare(strict_types=1);

namespace App;

/**
 * A simple data object: what we store in the database.
 */
final class Product
{
    public function __construct(
        public ?int $id,
        public string $name,
        public int $price,
        public int $stock = 0,
    ) {
    }
}
