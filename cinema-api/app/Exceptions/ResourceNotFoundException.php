<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;

class ResourceNotFoundException extends ApiException
{
    public function __construct(string $resource = 'Resource', ?array $debug = null)
    {
        $errorCode = ErrorCode::MOVIE_NOT_FOUND;
        $errorCode['message'] = "$resource not found";
        
        parent::__construct(
            errorCode: $errorCode,
            debug: $debug
        );
    }
}
