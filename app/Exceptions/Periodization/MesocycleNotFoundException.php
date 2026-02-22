<?php

namespace App\Exceptions\Periodization;

use App\Exceptions\ApiException;

class MesocycleNotFoundException extends ApiException
{
    public function __construct(string $message = 'Mesociclo no encontrado.')
    {
        parent::__construct(
            message: $message,
            statusCode: 404,
            errorCode: 'PERIODIZATION.MESOCYCLE_NOT_FOUND',
        );
    }
}
