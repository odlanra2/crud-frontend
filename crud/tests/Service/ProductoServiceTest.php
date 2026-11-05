<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\ProductoRequestDTO;
use App\Entity\Producto;
use App\Exception\ProductoDuplicadoException;
use App\Exception\ProductoNoEncontradoException;
use App\Mapper\ProductoMapper;
use App\Repository\ProductoRepository;
use App\Service\ProductoService;
use Doctrine\ORM\Tools\Pagination\Paginator;
use PHPUnit\Framework\TestCase;

class ProductoServiceTest extends TestCase
{
    private ProductoRepository $repository;
    private ProductoService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ProductoRepository::class);
        $this->service = new ProductoService($this->repository, new ProductoMapper());
    }

    public function testCrearNormalizaLosDatosYGuarda(): void
    {
        $this->repository->expects(self::once())
            ->method('existeNombre')
            ->with('Teclado', null)
            ->willReturn(false);

        $this->repository->expects(self::once())
            ->method('guardar')
            ->with(
                self::callback(static function (Producto $producto): bool {
                    return 'Teclado' === $producto->getNombre()
                        && '99.90' === $producto->getPrecio()
                        && 5 === $producto->getStock()
                        && true === $producto->isActivo();
                }),
                true,
            );

        $producto = $this->service->crear($this->crearDTO());

        self::assertSame('Teclado', $producto->getNombre());
        self::assertSame('99.90', $producto->getPrecio());
        self::assertSame(5, $producto->getStock());
    }

    public function testCrearRechazaNombreDuplicado(): void
    {
        $this->repository->method('existeNombre')->willReturn(true);
        $this->repository->expects(self::never())->method('guardar');

        $this->expectException(ProductoDuplicadoException::class);
        $this->service->crear($this->crearDTO());
    }

    public function testActualizarFallaSiNoExiste(): void
    {
        $this->repository->method('find')->with(99)->willReturn(null);

        $this->expectException(ProductoNoEncontradoException::class);
        $this->service->actualizar(99, $this->crearDTO());
    }

    public function testActualizarExcluyeElPropioIdAlValidarNombre(): void
    {
        $producto = (new Producto())->setNombre('Anterior');
        $this->repository->method('find')->with(7)->willReturn($producto);
        $this->repository->expects(self::once())
            ->method('existeNombre')
            ->with('Teclado', 7)
            ->willReturn(false);
        $this->repository->expects(self::once())
            ->method('guardar')
            ->with($producto, true);

        $this->service->actualizar(7, $this->crearDTO());

        self::assertSame('Teclado', $producto->getNombre());
    }

    public function testEliminarBuscaYPersisteLaEliminacion(): void
    {
        $producto = (new Producto())->setNombre('Temporal');
        $this->repository->method('find')->with(4)->willReturn($producto);
        $this->repository->expects(self::once())
            ->method('eliminar')
            ->with($producto, true);

        $this->service->eliminar(4);
    }

    public function testListarSaneaOrdenPaginaYTamanio(): void
    {
        $paginator = $this->createMock(Paginator::class);

        $this->repository->expects(self::once())
            ->method('buscar')
            ->with(null, 'creadoEn', 'DESC', 1, 100)
            ->willReturn($paginator);

        $resultado = $this->service->listar(null, 'invalido', 'otra', 0, 999);

        self::assertSame($paginator, $resultado);
    }

    private function crearDTO(): ProductoRequestDTO
    {
        $dto = new ProductoRequestDTO();
        $dto->nombre = ' Teclado ';
        $dto->descripcion = ' Descripción de prueba ';
        $dto->precio = 99.9;
        $dto->stock = 5;
        $dto->activo = true;

        return $dto;
    }
}
