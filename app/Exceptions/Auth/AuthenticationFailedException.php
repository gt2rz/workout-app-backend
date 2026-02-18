<?php

namespace App\Exceptions\Auth;

use App\Exceptions\ApiException;

class AuthenticationFailedException extends ApiException
{
    public function __construct(string $message = 'Las credenciales son incorrectas.')
    {
        parent::__construct(
            message: $message,
            statusCode: 401,
            errorCode: 'AUTH.INVALID_CREDENTIALS',
        );
    }
}
