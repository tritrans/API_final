<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    description: "Cinema API - Backend API cho hệ thống rạp chiếu phim",
    title: "Cinema API Documentation",
)]
#[OA\Server(url: "http://localhost:8000", description: "Local Development Server")]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    name: "Authorization",
    in: "header",
    scheme: "bearer",
    bearerFormat: "JWT"
)]
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}