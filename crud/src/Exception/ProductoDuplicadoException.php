<?php

declare(strict_types=1);

namespace App\Exception;

final class ProductoDuplicadoException extends \RuntimeException
{
    public function __construct(string $nombre)
    {
        parent::__construct(sprintf('Ya existe un producto con el nombre "%s".', $nombre));
    }
}
