<?php

declare(strict_types=1);

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Utilidades comunes a los controladores JSON: deserialización y validación.
 */
abstract class AbstractApiController extends AbstractController
{
    public function __construct(
        protected readonly SerializerInterface $serializer,
        protected readonly ValidatorInterface $validator,
    ) {
    }

    protected function deserializar(Request $request, string $clase): object
    {
        try {
            return $this->serializer->deserialize($request->getContent(), $clase, 'json');
        } catch (SerializerException $e) {
            throw new BadRequestHttpException(
                'El cuerpo de la petición no es un JSON válido o contiene tipos incorrectos.',
                $e,
            );
        }
    }

    /**
     * Devuelve una respuesta 422 con los errores por campo, o null si todo es válido.
     */
    protected function validar(object $dto): ?JsonResponse
    {
        $violaciones = $this->validator->validate($dto);

        if (0 === count($violaciones)) {
            return null;
        }

        $errores = [];
        foreach ($violaciones as $violacion) {
            $errores[$violacion->getPropertyPath()][] = $violacion->getMessage();
        }

        return $this->json([
            'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'message' => 'Los datos enviados no son válidos.',
            'errors' => $errores,
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
