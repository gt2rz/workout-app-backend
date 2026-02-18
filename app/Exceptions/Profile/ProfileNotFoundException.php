<?php

namespace App\Exceptions\Profile;

use App\Exceptions\ApiException;

class ProfileNotFoundException extends ApiException
{
    public function __construct(string $message = 'Perfil no encontrado.')
    {
        parent::__construct(
            message: $message,
            statusCode: 404,
            errorCode: 'PROFILE.NOT_FOUND',
        );
    }
}
