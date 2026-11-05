<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\ProductoRequestDTO;
use App\Entity\Producto;
use App\Exception\ProductoDuplicadoException;
use App\Exception\ProductoNoEncontradoException;
use App\Mapper\ProductoMapper;
use App\Repository\ProductoRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * Capa de servicio: reglas de negocio + orquestación del repositorio.
 * El controlador nunca habla directamente con Doctrine.
 */
class ProductoService implements ProductoServiceInterface
{
    public function __construct(
        private readonly ProductoRepository $productoRepository,
        private readonly ProductoMapper $productoMapper,
    ) {
    }

    public function listar(
        ?string $termino = null,
        string $orden = 'creadoEn',
        string $direccion = 'DESC',
        int $pagina = 1,
        int $porPagina = 10,
    ): Paginator {
        // Saneamos la entrada: el repositorio también lo valida como última defensa.
        $orden = in_array($orden, ProductoRepository::ORDENES_PERMITIDOS, true) ? $orden : 'creadoEn';
        $direccion = 'ASC' === strtoupper($direccion) ? 'ASC' : 'DESC';
        $pagina = max(1, $pagina);
        $porPagina = min(100, max(1, $porPagina));

        return $this->productoRepository->buscar($termino, $orden, $direccion, $pagina, $porPagina);
    }

    public function obtener(int $id): Producto
    {
        return $this->productoRepository->find($id)
            ?? throw new ProductoNoEncontradoException($id);
    }

    public function crear(ProductoRequestDTO $dto): Producto
    {
        $nombre = trim((string) $dto->nombre);
        $this->validarNombreDisponible($nombre);

        $producto = $this->productoMapper->aEntidad($dto);
        $this->productoRepository->guardar($producto, true);

        return $producto;
    }

    public function actualizar(int $id, ProductoRequestDTO $dto): Producto
    {
        $producto = $this->obtener($id);
        $this->validarNombreDisponible(trim((string) $dto->nombre), $id);

        $this->productoMapper->aEntidad($dto, $producto);
        $this->productoRepository->guardar($producto, true);

        return $producto;
    }

    public function eliminar(int $id): void
    {
        $this->productoRepository->eliminar($this->obtener($id), true);
    }

    public function obtenerResumen(): array
    {
        return $this->productoRepository->obtenerResumen();
    }

    private function validarNombreDisponible(string $nombre, ?int $excluirId = null): void
    {
        if ('' === $nombre) {
            return; // La validación del DTO ya reporta el nombre vacío.
        }

        if ($this->productoRepository->existeNombre($nombre, $excluirId)) {
            throw new ProductoDuplicadoException($nombre);
        }
    }
}
