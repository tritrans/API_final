<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;
use Exception;
use Illuminate\Http\JsonResponse;

class ApiException extends Exception
{
    protected $errorCode;
    protected $statusCode;
    protected $errors = null;
    protected $debug = null;

    public function __construct(
        array $errorCode = ErrorCode::UNCATEGORIZED_EXCEPTION,
        ?array $errors = null,
        ?array $debug = null,
        ?string $customMessage = null
    ) {
        $this->errorCode = $errorCode['code'];
        $this->statusCode = $errorCode['status'];
        $this->errors = $errors;
        $this->debug = $debug;

        $message = $customMessage ?? $errorCode['message'];
        parent::__construct($message);
    }

    public function getErrorCode(): int
    {
        return $this->errorCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrors(): ?array
    {
        return $this->errors;
    }

    public function getDebug(): ?array
    {
        return $this->debug;
    }

    public function render(): JsonResponse
    {
        $response = [
            'success' => false,
            'error_code' => $this->errorCode,
            'message' => $this->getMessage(),
            'status_code' => $this->statusCode
        ];

        if ($this->errors) {
            $response['errors'] = $this->errors;
        }

        if ($this->debug && config('app.debug')) {
            $response['debug'] = $this->debug;
        }

        return response()->json($response, $this->statusCode);
    }
}
