<?php

namespace App\Exceptions\Periodization;

use App\Exceptions\ApiException;

class MacrocycleNotFoundException extends ApiException
{
    public function __construct(string $message = 'Macrociclo no encontrado.')
    {
        parent::__construct(
            message: $message,
            statusCode: 404,
            errorCode: 'PERIODIZATION.MACROCYCLE_NOT_FOUND',
        );
    }
}
