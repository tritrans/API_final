<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // Handle JWT Exceptions
        $this->renderable(function (TokenExpiredException $e, $request) {
            return $this->errorResponse(ErrorCode::JWT_TOKEN_EXPIRED);
        });

        $this->renderable(function (TokenInvalidException $e, $request) {
            return $this->errorResponse(ErrorCode::JWT_TOKEN_INVALID);
        });

        $this->renderable(function (JWTException $e, $request) {
            return $this->errorResponse(ErrorCode::JWT_TOKEN_MISSING);
        });

        // Handle Authentication Exceptions
        $this->renderable(function (AuthenticationException $e, $request) {
            return $this->errorResponse(ErrorCode::UNAUTHENTICATED);
        });

        // Handle Validation Exceptions
        $this->renderable(function (ValidationException $e, $request) {
            return $this->errorResponse(
                ErrorCode::VALIDATION_ERROR,
                $e->errors()
            );
        });

        // Handle Model Not Found Exceptions
        $this->renderable(function (ModelNotFoundException $e, $request) {
            return $this->errorResponse(ErrorCode::MOVIE_NOT_FOUND);
        });

        // Handle Not Found HTTP Exceptions
        $this->renderable(function (NotFoundHttpException $e, $request) {
            return $this->errorResponse(ErrorCode::MOVIE_NOT_FOUND, null, 'Route not found');
        });

        // Handle General HTTP Exceptions
        $this->renderable(function (HttpException $e, $request) {
            return $this->errorResponse(
                ErrorCode::UNCATEGORIZED_EXCEPTION,
                null,
                $e->getMessage()
            );
        });

        // Handle General Exceptions
        $this->renderable(function (Throwable $e, $request) {
            if (config('app.debug')) {
                return $this->errorResponse(
                    ErrorCode::UNCATEGORIZED_EXCEPTION,
                    null,
                    $e->getMessage(),
                    [
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString()
                    ]
                );
            }

            return $this->errorResponse(ErrorCode::UNCATEGORIZED_EXCEPTION);
        });
    }

    /**
     * Create a standardized error response
     */
    private function errorResponse(
        array $errorCode,
        ?array $errors = null,
        ?string $customMessage = null,
        ?array $debug = null
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
}
