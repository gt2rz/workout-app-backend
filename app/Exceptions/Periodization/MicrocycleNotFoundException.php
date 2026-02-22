<?php

namespace App\Exceptions\Periodization;

use App\Exceptions\ApiException;

class MicrocycleNotFoundException extends ApiException
{
    public function __construct(string $message = 'Microciclo no encontrado.')
    {
        parent::__construct(
            message: $message,
            statusCode: 404,
            errorCode: 'PERIODIZATION.MICROCYCLE_NOT_FOUND',
        );
    }
}
