<?php

namespace App\Exceptions;

use Exception;

class ApiException extends Exception
{
    public function __construct(
        string $message,
        public readonly int $statusCode = 400,
        public readonly string $errorCode = 'GENERAL.ERROR',
        public readonly array $context = [],
    ) {
        parent::__construct($message, $statusCode);
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        $response = [
            'status' => 'error',
            'message' => $this->message,
            'code' => $this->errorCode,
        ];

        if (! empty($this->context) && config('app.debug')) {
            $response['context'] = $this->context;
        }

        return response()->json($response, $this->statusCode);
    }
}
