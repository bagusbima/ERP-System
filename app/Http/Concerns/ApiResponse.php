<?php

namespace App\Http\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

/**
 * Uniform JSON envelopes for every API response.
 *
 * Success: {"success": true, "data": ..., "meta": {...}}
 * Failure: {"success": false, "message": "...", "errors": {...}}
 */
trait ApiResponse
{
    /**
     * Wrap a payload in a success envelope.
     *
     * @param  array<string, mixed>  $meta
     */
    protected function success(mixed $data = null, array $meta = [], int $status = 200): JsonResponse
    {
        $payload = ['success' => true, 'data' => $data];

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    /**
     * Return a failure envelope with a human readable message.
     */
    protected function failed(string $message, int $status = 400, array $errors = []): JsonResponse
    {
        $payload = ['success' => false, 'message' => $message];

        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }

    /**
     * Return a validation failure envelope.
     *
     * @param  array<string, array<int, string>>  $errors
     */
    protected function invalid(array $errors): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Data yang diberikan tidak valid.',
            'errors' => $errors,
        ], 422);
    }

    /**
     * Return a paginated list with the paginator meta.
     *
     * @param  LengthAwarePaginator<mixed>  $paginator
     */
    protected function paginated($paginator): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }
}
