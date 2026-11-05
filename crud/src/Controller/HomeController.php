<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Punto de entrada de la API: describe los recursos disponibles.
 */
class HomeController extends AbstractController
{
    #[Route('/', name: 'api_home', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json([
            'name' => 'CRUD de Productos API',
            'version' => '1.0.0',
            'description' => 'API REST en Symfony 6.4 con arquitectura Controller → Service → Repository.',
            'endpoints' => [
                ['method' => 'GET', 'path' => '/api/productos', 'description' => 'Listado paginado con búsqueda y ordenamiento.'],
                ['method' => 'GET', 'path' => '/api/productos/estadisticas', 'description' => 'Totales para el dashboard.'],
                ['method' => 'GET', 'path' => '/api/productos/{id}', 'description' => 'Detalle de un producto.'],
                ['method' => 'POST', 'path' => '/api/productos', 'description' => 'Crear un producto (JSON).'],
                ['method' => 'PUT', 'path' => '/api/productos/{id}', 'description' => 'Actualizar un producto (JSON completo).'],
                ['method' => 'DELETE', 'path' => '/api/productos/{id}', 'description' => 'Eliminar un producto.'],
            ],
        ]);
    }
}
