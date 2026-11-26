<?php

declare(strict_types=1);

namespace App;

final class ProductNotFoundException extends \RuntimeException
{
    public static function withId(int $id): self
    {
        return new self("Product with id {$id} not found.");
    }
}
