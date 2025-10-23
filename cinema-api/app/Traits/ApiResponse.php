<?php

namespace App\Traits;

use App\Enums\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponse
{
    /**
     * Success response
     */
    protected function successResponse(
        $data = null,
        string $message = 'Success',
        int $statusCode = 200
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
            'status_code' => $statusCode
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Error response
     */
    protected function errorResponse(
        array $errorCode = ErrorCode::UNCATEGORIZED_EXCEPTION,
        $errors = null,
        ?string $customMessage = null,
        $debug = null
    ): JsonResponse {
        $response = [
            'success' => false,
            'error_code' => $errorCode['code'],
            'message' => $customMessage ?? $errorCode['message'],
            'status_code' => $errorCode['status']
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        if ($debug && config('app.debug')) {
            $response['debug'] = $debug;
        }

        return response()->json($response, $errorCode['status']);
    }

    /**
     * Created response
     */
    protected function createdResponse(
        $data = null,
        string $message = 'Resource created successfully'
    ): JsonResponse {
        return $this->successResponse($data, $message, 201);
    }

    /**
     * No content response
     */
    protected function noContentResponse(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Paginated response
     */
    protected function paginatedResponse(
        LengthAwarePaginator $paginator,
        string $message = 'Data retrieved successfully'
    ): JsonResponse {
        $data = [
            'items' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'has_more_pages' => $paginator->hasMorePages()
            ]
        ];

        return $this->successResponse($data, $message);
    }

    /**
     * Resource response
     */
    protected function resourceResponse(
        JsonResource $resource,
        string $message = 'Resource retrieved successfully'
    ): JsonResponse {
        return $this->successResponse($resource, $message);
    }

    /**
     * Resource collection response
     */
    protected function resourceCollectionResponse(
        ResourceCollection $collection,
        string $message = 'Resources retrieved successfully'
    ): JsonResponse {
        return $this->successResponse($collection, $message);
    }

    /**
     * Validation error response
     */
    protected function validationErrorResponse(
        array $errors,
        string $message = 'Validation failed'
    ): JsonResponse {
        return $this->errorResponse(ErrorCode::VALIDATION_ERROR, $errors, $message);
    }

    /**
     * Not found response
     */
    protected function notFoundResponse(
        string $message = 'Resource not found'
    ): JsonResponse {
        return $this->errorResponse(ErrorCode::MOVIE_NOT_FOUND, null, $message);
    }

    /**
     * Unauthorized response
     */
    protected function unauthorizedResponse(
        string $message = 'Unauthorized'
    ): JsonResponse {
        return $this->errorResponse(ErrorCode::UNAUTHENTICATED, null, $message);
    }

    /**
     * Forbidden response
     */
    protected function forbiddenResponse(
        string $message = 'Forbidden'
    ): JsonResponse {
        return $this->errorResponse(ErrorCode::UNAUTHORIZED, null, $message);
    }

    /**
     * Server error response
     */
    protected function serverErrorResponse(
        string $message = 'Internal server error',
        $debug = null
    ): JsonResponse {
        return $this->errorResponse(ErrorCode::UNCATEGORIZED_EXCEPTION, null, $message, $debug);
    }
}
