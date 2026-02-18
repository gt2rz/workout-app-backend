<?php

namespace App\Exceptions\Workout;

use App\Exceptions\ApiException;

class WorkoutNotFoundException extends ApiException
{
    public function __construct(string $message = 'Entrenamiento no encontrado.')
    {
        parent::__construct(
            message: $message,
            statusCode: 404,
            errorCode: 'WORKOUT.NOT_FOUND',
        );
    }
}
