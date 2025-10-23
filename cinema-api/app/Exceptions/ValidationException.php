<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;
use Illuminate\Validation\ValidationException as BaseValidationException;

class ValidationException extends BaseValidationException
{
    public function render($request)
    {
        return response()->json([
            'success' => false,
            'error_code' => ErrorCode::VALIDATION_ERROR['code'],
            'message' => ErrorCode::VALIDATION_ERROR['message'],
            'status_code' => ErrorCode::VALIDATION_ERROR['status'],
            'errors' => $this->errors()
        ], ErrorCode::VALIDATION_ERROR['status']);
    }
}
