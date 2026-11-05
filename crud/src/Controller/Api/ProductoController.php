<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\ProductoRequestDTO;
use App\Mapper\ProductoMapper;
use App\Service\ProductoServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * API REST de productos. Solo gestiona HTTP/JSON y delega en el servicio.
 */
#[Route('/api/productos', name: 'api_producto_')]
class ProductoController extends AbstractApiController
{
    private const POR_PAGINA = 10;

    public function __construct(
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        private readonly ProductoServiceInterface $productoService,
        private readonly ProductoMapper $productoMapper,
    ) {
        parent::__construct($serializer, $validator);
    }

    /**
     * GET /api/productos?q=&orden=&direccion=&pagina=&porPagina=
     */
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $termino = $request->query->getString('q');
        $orden = $request->query->getString('orden', 'creadoEn');
        $direccion = $request->query->getString('direccion', 'DESC');
        $pagina = max(1, $request->query->getInt('pagina', 1));
        $porPagina = min(100, max(1, $request->query->getInt('porPagina', self::POR_PAGINA)));

        $productos = $this->productoService->listar(
            '' === $termino ? null : $termino,
            $orden,
            $direccion,
            $pagina,
            $porPagina,
        );

        $total = $productos->count();
        $data = [];
        foreach ($productos as $producto) {
            $data[] = $this->productoMapper->aRespuesta($producto);
        }

        return $this->json([
            'data' => $data,
            'meta' => [
                'total' => $total,
                'pagina' => $pagina,
                'porPagina' => $porPagina,
                'totalPaginas' => (int) ceil($total / $porPagina),
                'termino' => '' === $termino ? null : $termino,
                'orden' => in_array($orden, ['nombre', 'precio', 'stock', 'activo', 'creadoEn', 'actualizadoEn'], true) ? $orden : 'creadoEn',
                'direccion' => 'ASC' === strtoupper($direccion) ? 'ASC' : 'DESC',
            ],
        ]);
    }

    /**
     * GET /api/productos/estadisticas
     */
    #[Route('/estadisticas', name: 'stats', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        return $this->json($this->productoService->obtenerResumen());
    }

    /**
     * GET /api/productos/{id}
     */
    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        return $this->json(
            $this->productoMapper->aRespuesta($this->productoService->obtener($id)),
        );
    }

    /**
     * POST /api/productos
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $dto = $this->deserializar($request, ProductoRequestDTO::class);

        if (null !== $error = $this->validar($dto)) {
            return $error;
        }

        $producto = $this->productoService->crear($dto);

        return $this->json(
            $this->productoMapper->aRespuesta($producto),
            Response::HTTP_CREATED,
            [
                'Location' => $this->generateUrl(
                    'api_producto_show',
                    ['id' => $producto->getId()],
                    UrlGeneratorInterface::ABSOLUTE_URL,
                ),
            ],
        );
    }

    /**
     * PUT /api/productos/{id} (reemplazo completo del recurso)
     */
    #[Route('/{id}', name: 'update', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function update(Request $request, int $id): JsonResponse
    {
        $dto = $this->deserializar($request, ProductoRequestDTO::class);

        if (null !== $error = $this->validar($dto)) {
            return $error;
        }

        $producto = $this->productoService->actualizar($id, $dto);

        return $this->json($this->productoMapper->aRespuesta($producto));
    }

    /**
     * DELETE /api/productos/{id}
     */
    #[Route('/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        $this->productoService->eliminar($id);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
