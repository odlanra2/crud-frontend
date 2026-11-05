<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Base para pruebas de la API: prepara el esquema SQLite y ayuda a autenticar.
 */
abstract class ApiTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $schemaManager = $em->getConnection()->createSchemaManager();
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($em);

        if (!$schemaManager->tablesExist(['producto', 'user'])) {
            $schemaTool->dropSchema($metadata);
            $schemaTool->createSchema($metadata);
        } else {
            $em->getConnection()->executeStatement('DELETE FROM producto');
            $em->getConnection()->executeStatement('DELETE FROM "user"');
        }
    }

    protected function crearUsuario(string $email = 'tester@example.com', string $password = 'Password123'): User
    {
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);

        $usuario = (new User())
            ->setEmail($email)
            ->setNombre('Usuario de prueba')
            ->setRoles(['ROLE_USER']);

        // Hash compatible con el hasher "auto" de Symfony, sin depender del servicio en tests.
        $usuario->setPassword(password_hash($password, PASSWORD_BCRYPT, ['cost' => 4]));

        $em->persist($usuario);
        $em->flush();

        return $usuario;
    }

    protected function autenticarComo(User $usuario): void
    {
        $token = static::getContainer()->get(JWTTokenManagerInterface::class)->create($usuario);
        $this->client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer '.$token);
    }

    /**
     * @return array<string, mixed>
     */
    protected function json(): array
    {
        $response = $this->client->getResponse();

        self::assertStringStartsWith('application/json', (string) $response->headers->get('Content-Type'));

        return json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }
}
