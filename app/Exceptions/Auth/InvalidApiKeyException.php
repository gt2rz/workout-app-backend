<?php

namespace App\Exceptions\Auth;

use App\Exceptions\ApiException;

class InvalidApiKeyException extends ApiException
{
    public function __construct(string $message = 'API Key inválida o inactiva.')
    {
        parent::__construct(
            message: $message,
            statusCode: 401,
            errorCode: 'AUTH.INVALID_API_KEY',
        );
    }
}
