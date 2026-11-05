<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\RegisterRequestDTO;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Registro de usuarios y consulta del usuario autenticado.
 * El login (POST /api/login_check) lo gestiona el firewall json_login de Lexik.
 */
#[Route('/api', name: 'api_auth_')]
class AuthController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly JWTTokenManagerInterface $jwtTokenManager,
    ) {
        parent::__construct($serializer, $validator);
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        /** @var RegisterRequestDTO $dto */
        $dto = $this->deserializar($request, RegisterRequestDTO::class);

        if (null !== $respuestaValidacion = $this->validar($dto)) {
            return $respuestaValidacion;
        }

        if (null !== $this->userRepository->findOneByEmail($dto->email)) {
            return $this->respuestaEmailDuplicado();
        }

        $usuario = (new User())
            ->setEmail($dto->email)
            ->setNombre($dto->nombre)
            ->setRoles(['ROLE_USER']);

        $usuario->setPassword($this->passwordHasher->hashPassword($usuario, $dto->password));

        try {
            $this->userRepository->guardar($usuario, true);
        } catch (UniqueConstraintViolationException) {
            // Otra petición registró el mismo correo entre la comprobación y el INSERT.
            return $this->respuestaEmailDuplicado();
        }

        return $this->json([
            'user' => $this->serializarUsuario($usuario),
            'token' => $this->jwtTokenManager->create($usuario),
        ], Response::HTTP_CREATED);
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var User $usuario */
        $usuario = $this->getUser();

        return $this->json($this->serializarUsuario($usuario));
    }

    private function respuestaEmailDuplicado(): JsonResponse
    {
        return $this->json([
            'status' => Response::HTTP_CONFLICT,
            'message' => 'Ya existe un usuario registrado con ese correo electrónico.',
        ], Response::HTTP_CONFLICT);
    }

    /**
     * @return array{id: int|null, email: string, nombre: string|null, roles: list<string>, creadoEn: string}
     */
    private function serializarUsuario(User $usuario): array
    {
        return [
            'id' => $usuario->getId(),
            'email' => $usuario->getEmail(),
            'nombre' => $usuario->getNombre(),
            'roles' => $usuario->getRoles(),
            'creadoEn' => $usuario->getCreadoEn()->format(\DateTimeInterface::ATOM),
        ];
    }
}
