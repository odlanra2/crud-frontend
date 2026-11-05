<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProductoRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entidad de dominio: solo representa los datos y su mapeo a base de datos.
 * La lógica de negocio vive en App\Service\ProductoService.
 */
#[ORM\Entity(repositoryClass: ProductoRepository::class)]
#[ORM\Table(name: 'producto')]
#[ORM\UniqueConstraint(name: 'uniq_producto_nombre', columns: ['nombre'])]
#[ORM\HasLifecycleCallbacks]
class Producto
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private string $nombre = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $descripcion = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $precio = '0.00';

    #[ORM\Column]
    private int $stock = 0;

    #[ORM\Column]
    private bool $activo = true;

    #[ORM\Column(name: 'creado_en', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $creadoEn;

    #[ORM\Column(name: 'actualizado_en', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $actualizadoEn;

    public function __construct()
    {
        $this->creadoEn = new \DateTimeImmutable();
        $this->actualizadoEn = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function refrescarFechaActualizacion(): void
    {
        $this->actualizadoEn = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): static
    {
        $this->nombre = $nombre;

        return $this;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(?string $descripcion): static
    {
        $this->descripcion = $descripcion;

        return $this;
    }

    public function getPrecio(): string
    {
        return $this->precio;
    }

    public function setPrecio(string $precio): static
    {
        $this->precio = $precio;

        return $this;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = $stock;

        return $this;
    }

    public function isActivo(): bool
    {
        return $this->activo;
    }

    public function setActivo(bool $activo): static
    {
        $this->activo = $activo;

        return $this;
    }

    public function getCreadoEn(): \DateTimeImmutable
    {
        return $this->creadoEn;
    }

    public function getActualizadoEn(): \DateTimeImmutable
    {
        return $this->actualizadoEn;
    }

    /**
     * @return array{stockBajo: bool, valorInventario: float}
     */
    public function getIndicadores(): array
    {
        return [
            'stockBajo' => $this->stock <= 5,
            'valorInventario' => (float) $this->precio * $this->stock,
        ];
    }
}
