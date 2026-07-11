<?php

namespace App\Http\Responses;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;

/**
 * Canonical API response envelope: { success, message, data, meta, errors }.
 *
 * Every /api/* response — success, validation failure, or exception — uses
 * this shape so mobile and web clients can unwrap responses uniformly.
 */
class Envelope
{
    public static function success(mixed $data = null, string $message = '', array $meta = [], int $status = 200): JsonResponse
    {
        [$data, $meta] = self::normalize($data, $meta);

        return new JsonResponse([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => (object) $meta,
            'errors' => (object) [],
        ], $status);
    }

    public static function error(string $message, array $errors = [], int $status = 400, array $meta = []): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'message' => $message,
            'data' => null,
            'meta' => (object) $meta,
            'errors' => (object) $errors,
        ], $status);
    }

    /**
     * Unwrap resources and fold paginator metadata into meta.pagination.
     *
     * @return array{0: mixed, 1: array}
     */
    private static function normalize(mixed $data, array $meta): array
    {
        $resource = $data instanceof ResourceCollection ? $data->resource : $data;

        if ($resource instanceof AbstractPaginator) {
            $meta['pagination'] = [
                'current_page' => $resource->currentPage(),
                'per_page' => $resource->perPage(),
                'last_page' => method_exists($resource, 'lastPage') ? $resource->lastPage() : null,
                'total' => method_exists($resource, 'total') ? $resource->total() : null,
            ];

            $data = $data instanceof ResourceCollection
                ? $data->collection->map->resolve(request())->all()
                : $resource->items();
        }

        if ($data instanceof JsonResource) {
            $data = $data->resolve(request());
        } elseif ($data instanceof Arrayable) {
            $data = $data->toArray();
        }

        return [$data, $meta];
    }
}
