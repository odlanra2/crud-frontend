<?php

declare(strict_types=1);

namespace App\Exception;

final class ProductoNoEncontradoException extends \RuntimeException
{
    public function __construct(int $id)
    {
        parent::__construct(sprintf('No se encontró el producto con ID %d.', $id));
    }
}
