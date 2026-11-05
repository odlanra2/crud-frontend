<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Datos que acepta POST /api/register.
 */
class RegisterRequestDTO
{
    #[Assert\NotBlank(message: 'El correo electrónico es obligatorio.')]
    #[Assert\Email(message: 'El correo electrónico no es válido.')]
    #[Assert\Length(max: 180, maxMessage: 'El correo electrónico no puede superar los {{ limit }} caracteres.')]
    public string $email = '';

    #[Assert\NotBlank(message: 'La contraseña es obligatoria.')]
    #[Assert\Length(
        min: 8,
        max: 72,
        minMessage: 'La contraseña debe tener al menos {{ limit }} caracteres.',
        maxMessage: 'La contraseña no puede superar los {{ limit }} caracteres.',
    )]
    public string $password = '';

    #[Assert\Length(max: 100, maxMessage: 'El nombre no puede superar los {{ limit }} caracteres.')]
    public ?string $nombre = null;
}
