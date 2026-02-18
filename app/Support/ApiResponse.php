<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\CursorPaginator;

trait ApiResponse
{
    protected function success(mixed $data = null, int $status = 200, array $meta = []): JsonResponse
    {
        $response = [
            'status' => 'success',
            'data' => $data,
        ];

        if (! empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $status);
    }

    protected function error(string $message, int $status = 400, ?string $code = null, array $errors = []): JsonResponse
    {
        $response = [
            'status' => 'error',
            'message' => $message,
        ];

        if ($code) {
            $response['code'] = $code;
        }

        if (! empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }

    protected function paginated(CursorPaginator $paginator, string $resourceClass): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $resourceClass::collection($paginator->items()),
            'meta' => [
                'per_page' => $paginator->perPage(),
                'next_cursor' => $paginator->nextCursor()?->encode(),
                'prev_cursor' => $paginator->previousCursor()?->encode(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ]);
    }
}
