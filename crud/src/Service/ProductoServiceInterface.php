<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\ProductoRequestDTO;
use App\Entity\Producto;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * Contrato de la capa de servicio. El controlador depende de esta interfaz,
 * no de la implementación (permite sustituirla o mockearla en tests).
 */
interface ProductoServiceInterface
{
    /**
     * @return Paginator<Producto>
     */
    public function listar(
        ?string $termino = null,
        string $orden = 'creadoEn',
        string $direccion = 'DESC',
        int $pagina = 1,
        int $porPagina = 10,
    ): Paginator;

    public function obtener(int $id): Producto;

    public function crear(ProductoRequestDTO $dto): Producto;

    public function actualizar(int $id, ProductoRequestDTO $dto): Producto;

    public function eliminar(int $id): void;

    /**
     * @return array{total: int, activos: int, stockTotal: int, valorInventario: float}
     */
    public function obtenerResumen(): array;
}
