<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;

class UnauthorizedException extends ApiException
{
    public function __construct(string $message = 'Unauthorized access', ?array $debug = null)
    {
        $errorCode = ErrorCode::UNAUTHORIZED;
        $errorCode['message'] = $message;
        
        parent::__construct(
            errorCode: $errorCode,
            debug: $debug
        );
    }
}
