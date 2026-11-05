<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Exception\ProductoDuplicadoException;
use App\Exception\ProductoNoEncontradoException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Convierte cualquier excepción de las rutas /api en una respuesta JSON coherente.
 */
class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire('%kernel.debug%')]
        private readonly bool $debug = false,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 0],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if ($event->hasResponse()) {
            return;
        }

        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api')) {
            return;
        }

        $excepcion = $event->getThrowable();

        if ($excepcion instanceof ProductoNoEncontradoException) {
            $status = Response::HTTP_NOT_FOUND;
            $mensaje = $excepcion->getMessage();
        } elseif ($excepcion instanceof ProductoDuplicadoException) {
            $status = Response::HTTP_CONFLICT;
            $mensaje = $excepcion->getMessage();
        } elseif ($excepcion instanceof HttpExceptionInterface) {
            $status = $excepcion->getStatusCode();
            $mensaje = '' !== $excepcion->getMessage()
                ? $excepcion->getMessage()
                : (Response::$statusTexts[$status] ?? 'Error HTTP');
        } else {
            $status = Response::HTTP_INTERNAL_SERVER_ERROR;
            $mensaje = $this->debug ? $excepcion->getMessage() : 'Error interno del servidor.';
        }

        $cuerpo = [
            'status' => $status,
            'message' => $mensaje,
        ];

        // En desarrollo añadimos la clase de la excepción para facilitar el diagnóstico.
        if ($this->debug && !$excepcion instanceof HttpExceptionInterface
            && !$excepcion instanceof ProductoNoEncontradoException
            && !$excepcion instanceof ProductoDuplicadoException) {
            $cuerpo['exception'] = $excepcion::class;
        }

        $event->setResponse(new JsonResponse($cuerpo, $status));
    }
}
