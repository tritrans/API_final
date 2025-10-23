<?php

namespace App\Enums;

use Symfony\Component\HttpFoundation\Response;

class ErrorCode
{
    public const UNCATEGORIZED_EXCEPTION = [
        'code' => 9999,
        'message' => 'Uncategorized error',
        'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
    ];

    public const INTERNAL_ERROR = [
        'code' => 9998,
        'message' => 'Internal server error',
        'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
    ];

    public const INVALID_KEY = [
        'code' => 1001,
        'message' => 'Invalid key',
        'status' => Response::HTTP_BAD_REQUEST,
    ];

    public const USER_EXISTED = [
        'code' => 1002,
        'message' => 'User existed',
        'status' => Response::HTTP_BAD_REQUEST,
    ];

    public const USERNAME_INVALID = [
        'code' => 1003,
        'message' => 'Username must be at least {min} characters',
        'status' => Response::HTTP_BAD_REQUEST,
    ];

    public const INVALID_PASSWORD = [
        'code' => 1004,
        'message' => 'Password must be at least {min} characters',
        'status' => Response::HTTP_BAD_REQUEST,
    ];

    public const USER_NOT_EXISTED = [
        'code' => 1005,
        'message' => 'User not existed',
        'status' => Response::HTTP_NOT_FOUND,
    ];

    public const UNAUTHENTICATED = [
        'code' => 1006,
        'message' => 'Unauthenticated',
        'status' => Response::HTTP_UNAUTHORIZED,
    ];

    public const UNAUTHORIZED = [
        'code' => 1007,
        'message' => 'You do not have permission',
        'status' => Response::HTTP_FORBIDDEN,
    ];

    public const FORBIDDEN = [
        'code' => 1008,
        'message' => 'Access forbidden',
        'status' => Response::HTTP_FORBIDDEN,
    ];

    public const INVALID_DOB = [
        'code' => 1008,
        'message' => 'Your age must be at least {min}',
        'status' => Response::HTTP_BAD_REQUEST,
    ];

    // Cinema API specific error codes
    public const MOVIE_NOT_FOUND = [
        'code' => 2001,
        'message' => 'Movie not found',
        'status' => Response::HTTP_NOT_FOUND,
    ];

    public const TICKET_NOT_FOUND = [
        'code' => 2002,
        'message' => 'Ticket not found',
        'status' => Response::HTTP_NOT_FOUND,
    ];

    public const REVIEW_NOT_FOUND = [
        'code' => 2003,
        'message' => 'Review not found',
        'status' => Response::HTTP_NOT_FOUND,
    ];

    public const COMMENT_NOT_FOUND = [
        'code' => 2004,
        'message' => 'Comment not found',
        'status' => Response::HTTP_NOT_FOUND,
    ];

    public const FAVORITE_NOT_FOUND = [
        'code' => 2005,
        'message' => 'Favorite not found',
        'status' => Response::HTTP_NOT_FOUND,
    ];

    public const THEATER_NOT_FOUND = [
        'code' => 2011,
        'message' => 'Theater not found',
        'status' => Response::HTTP_NOT_FOUND,
    ];

    public const SCHEDULE_NOT_FOUND = [
        'code' => 2012,
        'message' => 'Schedule not found',
        'status' => Response::HTTP_NOT_FOUND,
    ];

    public const MOVIE_ALREADY_EXISTS = [
        'code' => 2006,
        'message' => 'Movie already exists',
        'status' => Response::HTTP_BAD_REQUEST,
    ];

    public const TICKET_ALREADY_BOOKED = [
        'code' => 2007,
        'message' => 'Ticket already booked',
        'status' => Response::HTTP_BAD_REQUEST,
    ];

    public const SEAT_ALREADY_TAKEN = [
        'code' => 2008,
        'message' => 'Seat already taken',
        'status' => Response::HTTP_BAD_REQUEST,
    ];

    public const INVALID_MOVIE_DATA = [
        'code' => 2009,
        'message' => 'Invalid movie data',
        'status' => Response::HTTP_BAD_REQUEST,
    ];

    public const INVALID_TICKET_DATA = [
        'code' => 2010,
        'message' => 'Invalid ticket data',
        'status' => Response::HTTP_BAD_REQUEST,
    ];

    // JWT specific error codes
    public const JWT_TOKEN_EXPIRED = [
        'code' => 3001,
        'message' => 'Token has expired',
        'status' => Response::HTTP_UNAUTHORIZED,
    ];

    public const JWT_TOKEN_INVALID = [
        'code' => 3002,
        'message' => 'Token is invalid',
        'status' => Response::HTTP_UNAUTHORIZED,
    ];

    public const JWT_TOKEN_MISSING = [
        'code' => 3003,
        'message' => 'Token could not be parsed',
        'status' => Response::HTTP_UNAUTHORIZED,
    ];

    public const JWT_TOKEN_BLACKLISTED = [
        'code' => 3004,
        'message' => 'Token has been blacklisted',
        'status' => Response::HTTP_UNAUTHORIZED,
    ];

    // Validation error codes
    public const VALIDATION_ERROR = [
        'code' => 4001,
        'message' => 'Validation failed',
        'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
    ];

    public const INVALID_EMAIL = [
        'code' => 4002,
        'message' => 'Invalid email format',
        'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
    ];

    public const INVALID_DATE_FORMAT = [
        'code' => 4003,
        'message' => 'Invalid date format',
        'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
    ];

    public const INVALID_URL = [
        'code' => 4004,
        'message' => 'Invalid URL format',
        'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
    ];

    public const INVALID_RATING = [
        'code' => 4005,
        'message' => 'Rating must be between 0 and 10',
        'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
    ];

    public const INVALID_DURATION = [
        'code' => 4006,
        'message' => 'Duration must be at least 1 minute',
        'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
    ];

    // Database error codes
    public const DATABASE_ERROR = [
        'code' => 5001,
        'message' => 'Database error occurred',
        'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
    ];

    public const DATABASE_CONNECTION_ERROR = [
        'code' => 5002,
        'message' => 'Database connection error',
        'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
    ];

    public const DATABASE_QUERY_ERROR = [
        'code' => 5003,
        'message' => 'Database query error',
        'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
    ];

    // File upload error codes
    public const FILE_UPLOAD_ERROR = [
        'code' => 6001,
        'message' => 'File upload error',
        'status' => Response::HTTP_BAD_REQUEST,
    ];

    public const FILE_TOO_LARGE = [
        'code' => 6002,
        'message' => 'File size too large',
        'status' => Response::HTTP_BAD_REQUEST,
    ];

    public const INVALID_FILE_TYPE = [
        'code' => 6003,
        'message' => 'Invalid file type',
        'status' => Response::HTTP_BAD_REQUEST,
    ];

    public const FILE_NOT_FOUND = [
        'code' => 6004,
        'message' => 'File not found',
        'status' => Response::HTTP_NOT_FOUND,
    ];

    /**
     * Get error information by code
     */
    public static function getError(int $code): ?array
    {
        $reflection = new \ReflectionClass(self::class);
        $constants = $reflection->getConstants();

        foreach ($constants as $constant) {
            if ($constant['code'] === $code) {
                return $constant;
            }
        }

        return null;
    }

    /**
     * Get all error codes
     */
    public static function getAllErrors(): array
    {
        $reflection = new \ReflectionClass(self::class);
        return $reflection->getConstants();
    }

    /**
     * Check if error code exists
     */
    public static function hasError(int $code): bool
    {
        return self::getError($code) !== null;
    }
}
