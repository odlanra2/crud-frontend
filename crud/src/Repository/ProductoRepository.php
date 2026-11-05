<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Producto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Capa de persistencia. Es la única clase que conoce Doctrine/QueryBuilder.
 *
 * @extends ServiceEntityRepository<Producto>
 */
class ProductoRepository extends ServiceEntityRepository
{
    public const ORDENES_PERMITIDOS = ['nombre', 'precio', 'stock', 'activo', 'creadoEn', 'actualizadoEn'];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Producto::class);
    }

    public function guardar(Producto $producto, bool $flush = false): void
    {
        $this->getEntityManager()->persist($producto);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function eliminar(Producto $producto, bool $flush = false): void
    {
        $this->getEntityManager()->remove($producto);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function existeNombre(string $nombre, ?int $excluirId = null): bool
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('LOWER(p.nombre) = LOWER(:nombre)')
            ->setParameter('nombre', $nombre);

        if (null !== $excluirId) {
            $qb->andWhere('p.id != :id')
                ->setParameter('id', $excluirId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * Devuelve una página de productos, con búsqueda y ordenamiento seguros.
     *
     * @return Paginator<Producto>
     */
    public function buscar(
        ?string $termino,
        string $orden = 'creadoEn',
        string $direccion = 'DESC',
        int $pagina = 1,
        int $porPagina = 10,
    ): Paginator {
        $qb = $this->createQueryBuilder('p');

        if (null !== $termino && '' !== trim($termino)) {
            $qb->andWhere('LOWER(p.nombre) LIKE :termino OR LOWER(p.descripcion) LIKE :termino')
                ->setParameter('termino', '%'.mb_strtolower(trim($termino)).'%');
        }

        $orden = in_array($orden, self::ORDENES_PERMITIDOS, true) ? $orden : 'creadoEn';
        $direccion = 'ASC' === strtoupper($direccion) ? 'ASC' : 'DESC';
        $sortDirection = 'ASC' === $direccion ? \SortDirection::Ascending : \SortDirection::Descending;

        $qb->orderBy('p.'.$orden, $sortDirection)
            ->addOrderBy('p.id', \SortDirection::Descending);

        $query = $qb->getQuery()
            ->setFirstResult(max(0, $pagina - 1) * $porPagina)
            ->setMaxResults($porPagina);

        $paginator = new Paginator($query);
        $paginator->setUseOutputWalkers(false);

        return $paginator;
    }

    /**
     * @return array{total: int, activos: int, stockTotal: int, valorInventario: float}
     */
    public function obtenerResumen(): array
    {
        $fila = $this->createQueryBuilder('p')
            ->select(
                'COUNT(p.id) AS total',
                'SUM(CASE WHEN p.activo = true THEN 1 ELSE 0 END) AS activos',
                'COALESCE(SUM(p.stock), 0) AS stockTotal',
                'COALESCE(SUM(p.precio * p.stock), 0) AS valorInventario',
            )
            ->getQuery()
            ->getSingleResult();

        return [
            'total' => (int) $fila['total'],
            'activos' => (int) $fila['activos'],
            'stockTotal' => (int) $fila['stockTotal'],
            'valorInventario' => (float) $fila['valorInventario'],
        ];
    }
}
