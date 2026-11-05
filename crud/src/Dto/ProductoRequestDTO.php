<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Payload de entrada de la API (POST / PUT).
 * El Serializer de Symfony convierte el JSON a este objeto y el Validator
 * comprueba las restricciones antes de llegar al servicio.
 */
class ProductoRequestDTO
{
    #[Assert\NotBlank(message: 'El nombre es obligatorio.')]
    #[Assert\Length(
        min: 3,
        max: 150,
        minMessage: 'El nombre debe tener al menos {{ limit }} caracteres.',
        maxMessage: 'El nombre no puede superar los {{ limit }} caracteres.',
    )]
    public ?string $nombre = null;

    #[Assert\Length(
        max: 2000,
        maxMessage: 'La descripción no puede superar los {{ limit }} caracteres.',
    )]
    public ?string $descripcion = null;

    #[Assert\NotNull(message: 'El precio es obligatorio.')]
    #[Assert\Positive(message: 'El precio debe ser mayor que cero.')]
    #[Assert\LessThanOrEqual(value: 99999999.99, message: 'El precio es demasiado alto.')]
    public ?float $precio = null;

    #[Assert\NotNull(message: 'El stock es obligatorio.')]
    #[Assert\PositiveOrZero(message: 'El stock no puede ser negativo.')]
    #[Assert\LessThanOrEqual(value: 1000000, message: 'El stock es demasiado alto.')]
    public ?int $stock = null;

    #[Assert\NotNull(message: 'El campo activo es obligatorio.')]
    public ?bool $activo = null;
}
