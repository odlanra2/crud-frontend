<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Repository\UserRepository;

class AuthApiTest extends ApiTestCase
{
    public function testRegistroCreaUsuarioYDevuelveToken(): void
    {
        $this->client->jsonRequest('POST', '/api/register', [
            'email' => 'Nuevo@Example.com',
            'password' => 'Password123',
            'nombre' => 'Ana',
        ]);

        self::assertResponseStatusCodeSame(201);
        $json = $this->json();

        self::assertArrayHasKey('token', $json);
        self::assertNotEmpty($json['token']);
        self::assertSame('nuevo@example.com', $json['user']['email']);
        self::assertSame('Ana', $json['user']['nombre']);
        self::assertContains('ROLE_USER', $json['user']['roles']);
        self::assertArrayNotHasKey('password', $json['user']);

        $guardado = static::getContainer()->get(UserRepository::class)->findOneByEmail('nuevo@example.com');
        self::assertNotNull($guardado);
        self::assertNotSame('Password123', $guardado->getPassword());
    }

    public function testRegistroConDatosInvalidosDevuelve422(): void
    {
        $this->client->jsonRequest('POST', '/api/register', [
            'email' => 'no-es-un-email',
            'password' => '123',
        ]);

        self::assertResponseStatusCodeSame(422);
        $json = $this->json();

        self::assertArrayHasKey('email', $json['errors']);
        self::assertArrayHasKey('password', $json['errors']);
    }

    public function testRegistroDuplicadoDevuelve409(): void
    {
        $this->crearUsuario('dup@example.com');

        $this->client->jsonRequest('POST', '/api/register', [
            'email' => 'DUP@example.com',
            'password' => 'Password123',
        ]);

        self::assertResponseStatusCodeSame(409);
        self::assertStringContainsString('Ya existe un usuario', $this->json()['message']);
    }

    public function testLoginDevuelveToken(): void
    {
        $this->crearUsuario('login@example.com', 'Password123');

        $this->client->jsonRequest('POST', '/api/login_check', [
            'email' => 'login@example.com',
            'password' => 'Password123',
        ]);

        self::assertResponseIsSuccessful();
        $json = $this->json();

        self::assertArrayHasKey('token', $json);
        self::assertNotEmpty($json['token']);
    }

    public function testLoginConCredencialesInvalidasDevuelve401(): void
    {
        $this->crearUsuario('login@example.com', 'Password123');

        $this->client->jsonRequest('POST', '/api/login_check', [
            'email' => 'login@example.com',
            'password' => 'incorrecta',
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function testEndpointProtegidoSinTokenDevuelve401(): void
    {
        $this->client->request('GET', '/api/productos');

        self::assertResponseStatusCodeSame(401);
    }

    public function testEndpointProtegidoConTokenInvalidoDevuelve401(): void
    {
        $this->client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer token-invalido');
        $this->client->request('GET', '/api/productos');

        self::assertResponseStatusCodeSame(401);
    }

    public function testMeDevuelveUsuarioAutenticado(): void
    {
        $usuario = $this->crearUsuario('me@example.com');
        $this->autenticarComo($usuario);

        $this->client->request('GET', '/api/me');

        self::assertResponseIsSuccessful();
        $json = $this->json();

        self::assertSame('me@example.com', $json['email']);
        self::assertSame('Usuario de prueba', $json['nombre']);
        self::assertContains('ROLE_USER', $json['roles']);
    }
}
