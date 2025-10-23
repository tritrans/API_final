<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Enums\ErrorCode;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
                'error_code' => ErrorCode::UNAUTHENTICATED['code'],
                'status_code' => ErrorCode::UNAUTHENTICATED['status'],
            ], ErrorCode::UNAUTHENTICATED['status']);
        }

        if (!auth()->user()->hasPermissionTo($permission)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient permissions',
                'error_code' => ErrorCode::FORBIDDEN['code'],
                'status_code' => ErrorCode::FORBIDDEN['status'],
            ], ErrorCode::FORBIDDEN['status']);
        }

        return $next($request);
    }
}
