<?php

declare(strict_types=1);

namespace App\Mapper;

use App\Dto\ProductoRequestDTO;
use App\Dto\ProductoResponseDTO;
use App\Entity\Producto;

/**
 * Convierte entre el payload JSON, la entidad y la respuesta de la API.
 */
class ProductoMapper
{
    public function aEntidad(ProductoRequestDTO $dto, ?Producto $producto = null): Producto
    {
        $producto ??= new Producto();

        $descripcion = null !== $dto->descripcion ? trim($dto->descripcion) : null;

        return $producto
            ->setNombre(trim((string) $dto->nombre))
            ->setDescripcion('' === $descripcion ? null : $descripcion)
            ->setPrecio(number_format((float) $dto->precio, 2, '.', ''))
            ->setStock((int) $dto->stock)
            ->setActivo((bool) $dto->activo);
    }

    public function aRespuesta(Producto $producto): ProductoResponseDTO
    {
        $indicadores = $producto->getIndicadores();

        return new ProductoResponseDTO(
            id: (int) $producto->getId(),
            nombre: $producto->getNombre(),
            descripcion: $producto->getDescripcion(),
            precio: (float) $producto->getPrecio(),
            stock: $producto->getStock(),
            activo: $producto->isActivo(),
            stockBajo: $indicadores['stockBajo'],
            valorInventario: $indicadores['valorInventario'],
            creadoEn: $producto->getCreadoEn()->format(\DateTimeInterface::ATOM),
            actualizadoEn: $producto->getActualizadoEn()->format(\DateTimeInterface::ATOM),
        );
    }
}
