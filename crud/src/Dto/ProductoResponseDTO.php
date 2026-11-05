<?php

declare(strict_types=1);

namespace App\Dto;

/**
 * Representación JSON de un producto devuelta por la API.
 * Evita exponer la entidad de Doctrine y fija el contrato con el frontend.
 */
final class ProductoResponseDTO implements \JsonSerializable
{
    public function __construct(
        public readonly int $id,
        public readonly string $nombre,
        public readonly ?string $descripcion,
        public readonly float $precio,
        public readonly int $stock,
        public readonly bool $activo,
        public readonly bool $stockBajo,
        public readonly float $valorInventario,
        public readonly string $creadoEn,
        public readonly string $actualizadoEn,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
