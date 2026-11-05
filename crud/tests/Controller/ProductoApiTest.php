<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Producto;
use App\Repository\ProductoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProductoApiTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $schemaManager = $em->getConnection()->createSchemaManager();

        if (!$schemaManager->tablesExist(['producto'])) {
            $schemaTool = new SchemaTool($em);
            $schemaTool->createSchema($em->getMetadataFactory()->getAllMetadata());
        } else {
            $em->getConnection()->executeStatement('DELETE FROM producto');
        }
    }

    public function testListadoVacio(): void
    {
        $this->client->request('GET', '/api/productos');

        self::assertResponseIsSuccessful();
        $json = $this->json();

        self::assertSame([], $json['data']);
        self::assertSame(0, $json['meta']['total']);
        self::assertSame(0, $json['meta']['totalPaginas']);
    }

    public function testListadoConBusquedaYPaginacion(): void
    {
        for ($i = 1; $i <= 12; ++$i) {
            $this->crearProducto(sprintf('Producto %02d', $i), 1000.0 * $i, 10);
        }
        $this->crearProducto('Monitor especial', 999.0, 2);

        $this->client->request('GET', '/api/productos?q=producto&porPagina=5&pagina=2&orden=nombre&direccion=ASC');

        self::assertResponseIsSuccessful();
        $json = $this->json();

        self::assertCount(5, $json['data']);
        self::assertSame(12, $json['meta']['total']);
        self::assertSame(5, $json['meta']['porPagina']);
        self::assertSame(3, $json['meta']['totalPaginas']);
        self::assertSame(2, $json['meta']['pagina']);
        self::assertSame('ASC', $json['meta']['direccion']);
        self::assertStringStartsWith('Producto 06', $json['data'][0]['nombre']);
    }

    public function testCrearProducto(): void
    {
        $this->client->jsonRequest('POST', '/api/productos', [
            'nombre' => 'Teclado inalámbrico',
            'descripcion' => 'Producto creado desde la prueba funcional.',
            'precio' => 129900.5,
            'stock' => 8,
            'activo' => true,
        ]);

        self::assertResponseStatusCodeSame(201);
        $json = $this->json();

        self::assertSame('Teclado inalámbrico', $json['nombre']);
        self::assertSame(129900.5, $json['precio']);
        self::assertSame(8, $json['stock']);
        self::assertTrue($json['activo']);
        self::assertFalse($json['stockBajo']);
        self::assertEqualsWithDelta(1039204.0, (float) $json['valorInventario'], 0.001);

        $location = $this->client->getResponse()->headers->get('Location');
        self::assertNotNull($location);
        self::assertStringEndsWith('/api/productos/'.$json['id'], $location);

        self::assertNotNull(
            static::getContainer()->get(ProductoRepository::class)->findOneBy(['nombre' => 'Teclado inalámbrico']),
        );
    }

    public function testValidacionDevuelveErroresPorCampo(): void
    {
        $this->client->jsonRequest('POST', '/api/productos', ['precio' => 0]);

        self::assertResponseStatusCodeSame(422);
        $json = $this->json();

        self::assertSame('Los datos enviados no son válidos.', $json['message']);
        self::assertArrayHasKey('nombre', $json['errors']);
        self::assertArrayHasKey('stock', $json['errors']);
        self::assertArrayHasKey('activo', $json['errors']);
        self::assertContains('El precio debe ser mayor que cero.', $json['errors']['precio']);
    }

    public function testNombreDuplicadoDevuelve409(): void
    {
        $this->crearProducto('Duplicado', 1000.0, 1);

        $this->client->jsonRequest('POST', '/api/productos', [
            'nombre' => 'Duplicado',
            'precio' => 1000,
            'stock' => 1,
            'activo' => true,
        ]);

        self::assertResponseStatusCodeSame(409);
        $json = $this->json();
        self::assertStringContainsString('Ya existe un producto', $json['message']);
    }

    public function testActualizarProducto(): void
    {
        $producto = $this->crearProducto('Original', 1000.0, 5);

        $this->client->jsonRequest('PUT', '/api/productos/'.$producto->getId(), [
            'nombre' => 'Actualizado',
            'descripcion' => 'Nueva descripción',
            'precio' => 2500.75,
            'stock' => 3,
            'activo' => false,
        ]);

        self::assertResponseIsSuccessful();
        $json = $this->json();

        self::assertSame('Actualizado', $json['nombre']);
        self::assertSame(2500.75, $json['precio']);
        self::assertSame(3, $json['stock']);
        self::assertFalse($json['activo']);
        self::assertTrue($json['stockBajo']);

        $actualizado = static::getContainer()->get(ProductoRepository::class)->find($producto->getId());
        self::assertSame('Actualizado', $actualizado?->getNombre());
    }

    public function testEliminarProductoDevuelve204(): void
    {
        $producto = $this->crearProducto('Producto a eliminar', 1500.0, 2);
        $id = $producto->getId();

        $this->client->request('DELETE', '/api/productos/'.$id);

        self::assertResponseStatusCodeSame(204);
        self::assertSame('', (string) $this->client->getResponse()->getContent());
        self::assertNull(static::getContainer()->get(ProductoRepository::class)->find($id));
    }

    public function testProductoInexistenteDevuelve404Json(): void
    {
        $this->client->request('GET', '/api/productos/999999');

        self::assertResponseStatusCodeSame(404);
        $json = $this->json();
        self::assertSame(404, $json['status']);
        self::assertStringContainsString('No se encontró el producto', $json['message']);
    }

    public function testEstadisticas(): void
    {
        $this->crearProducto('Uno', 1000.0, 5);
        $this->crearProducto('Dos', 2000.0, 3);

        $this->client->request('GET', '/api/productos/estadisticas');

        self::assertResponseIsSuccessful();
        $json = $this->json();

        self::assertSame(2, $json['total']);
        self::assertSame(2, $json['activos']);
        self::assertSame(8, $json['stockTotal']);
        self::assertEqualsWithDelta(11000.0, (float) $json['valorInventario'], 0.001);
    }

    public function testJsonInvalidoDevuelve400(): void
    {
        $this->client->request(
            'POST',
            '/api/productos',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            '{esto no es json',
        );

        self::assertResponseStatusCodeSame(400);
        $json = $this->json();
        self::assertStringContainsString('JSON', $json['message']);
    }

    public function testCorsPermiteAlFrontendDeVue(): void
    {
        $this->client->request('OPTIONS', '/api/productos', server: [
            'HTTP_ORIGIN' => 'http://localhost:5173',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'content-type',
        ]);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Access-Control-Allow-Origin', 'http://localhost:5173');
        self::assertStringContainsString(
            'POST',
            (string) $this->client->getResponse()->headers->get('Access-Control-Allow-Methods'),
        );
    }

    private function crearProducto(string $nombre, float $precio, int $stock): Producto
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $producto = (new Producto())
            ->setNombre($nombre)
            ->setDescripcion('Descripción de prueba')
            ->setPrecio(number_format($precio, 2, '.', ''))
            ->setStock($stock)
            ->setActivo(true);

        $em->persist($producto);
        $em->flush();

        return $producto;
    }

    /**
     * @return array<string, mixed>
     */
    private function json(): array
    {
        $response = $this->client->getResponse();

        self::assertStringStartsWith('application/json', (string) $response->headers->get('Content-Type'));

        return json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }
}
