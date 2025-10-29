<?php

namespace App\Http\Controllers\Api;

use App\Enums\ErrorCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Models\EmailVerification;
use App\Mail\OtpMail;
use App\Mail\PasswordResetMail;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    use ApiResponse;

    #[OA\Post(
        path: "/api/auth/register",
        summary: "Register a new user",
        description: "Create a new user account",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "email", "password", "password_confirmation"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "John Doe"),
                    new OA\Property(property: "email", type: "string", format: "email", example: "john@example.com"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "password123"),
                    new OA\Property(property: "password_confirmation", type: "string", format: "password", example: "password123")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "User registered successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "User registered successfully"),
                        new OA\Property(
                            property: "data", 
                            type: "object",
                            properties: [
                                new OA\Property(property: "user", ref: "#/components/schemas/User"),
                                new OA\Property(property: "access_token", type: "string"),
                                new OA\Property(property: "token_type", type: "string", example: "bearer"),
                                new OA\Property(property: "expires_in", type: "integer", example: 3600)
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Validation errors",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Validation errors"),
                        new OA\Property(property: "errors", type: "object")
                    ]
                )
            )
        ]
    )]
    public function register(RegisterRequest $request)
    {
        // Check if user already exists
        if (User::where('email', $request->email)->exists()) {
            return $this->errorResponse(ErrorCode::VALIDATION_ERROR, null, 'Email đã được sử dụng');
        }

        // Store registration data temporarily in session or cache
        // For now, we'll just validate and send OTP
        // The actual user creation will happen in verifyOtp method
        
        return $this->successResponse([
            'email' => $request->email,
            'name' => $request->name
        ], 'Vui lòng xác thực email để hoàn tất đăng ký');
    }

    #[OA\Post(
        path: "/api/auth/login",
        summary: "User login",
        description: "Authenticate user and return JWT token",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email", "password"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email", example: "admin@cinema.com"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "password123")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Login successful",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Login successful"),
                        new OA\Property(
                            property: "data", 
                            type: "object",
                            properties: [
                                new OA\Property(property: "user", ref: "#/components/schemas/User"),
                                new OA\Property(property: "access_token", type: "string"),
                                new OA\Property(property: "token_type", type: "string", example: "bearer"),
                                new OA\Property(property: "expires_in", type: "integer", example: 3600)
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: "Invalid credentials",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Invalid credentials")
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Validation errors",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Validation errors"),
                        new OA\Property(property: "errors", type: "object")
                    ]
                )
            )
        ]
    )]
    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = Auth::guard('api')->attempt($credentials)) {
            return $this->errorResponse(ErrorCode::UNAUTHENTICATED, null, 'Thông tin đăng nhập không chính xác');
        }

        $user = Auth::guard('api')->user();
        
        // Check if user is active
        if (!$user->is_active) {
            Auth::guard('api')->logout();
            return $this->errorResponse(ErrorCode::FORBIDDEN, null, 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.');
        }
        
        // Load roles and format user data
        $user->load('roles');
        
        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'role' => $user->roles->first()?->name ?? 'user',
            'roles' => $user->roles->pluck('name')->toArray(),
            'is_active' => $user->is_active,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
        
        // Check if email is verified (has used OTP)
        $emailVerified = EmailVerification::where('email', $user->email)
            ->where('is_used', true)
            ->exists();
            
        if (!$emailVerified) {
            Auth::guard('api')->logout();
            return $this->errorResponse(ErrorCode::VALIDATION_ERROR, null, 'Email chưa được xác thực. Vui lòng xác thực email trước khi đăng nhập.');
        }

        return $this->successResponse([
            'user' => $userData,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ], 'Login successful');
    }

    #[OA\Post(
        path: "/api/auth/logout",
        summary: "User logout",
        description: "Logout user and invalidate JWT token",
        tags: ["Authentication"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Successfully logged out",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Successfully logged out")
                    ]
                )
            )
        ]
    )]
    public function logout()
    {
        Auth::guard('api')->logout();

        return $this->successResponse(null, 'Successfully logged out');
    }

    #[OA\Post(
        path: "/api/auth/refresh",
        summary: "Refresh JWT token",
        description: "Refresh the JWT token",
        tags: ["Authentication"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Token refreshed successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Token refreshed successfully"),
                        new OA\Property(
                            property: "data", 
                            type: "object",
                            properties: [
                                new OA\Property(property: "access_token", type: "string"),
                                new OA\Property(property: "token_type", type: "string", example: "bearer"),
                                new OA\Property(property: "expires_in", type: "integer", example: 3600)
                            ]
                        )
                    ]
                )
            )
        ]
    )]
    public function refresh()
    {
        $token = Auth::guard('api')->refresh();

        return $this->successResponse([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ], 'Token refreshed successfully');
    }

    #[OA\Get(
        path: "/api/auth/me",
        summary: "Get authenticated user",
        description: "Get current authenticated user information",
        tags: ["Authentication"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "User information retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data", 
                            type: "object",
                            properties: [
                                new OA\Property(property: "user", ref: "#/components/schemas/User")
                            ]
                        )
                    ]
                )
            )
        ]
    )]
    public function me()
    {
        $user = Auth::guard('api')->user();
        
        if (!$user) {
            return $this->errorResponse(ErrorCode::UNAUTHORIZED, null, 'User not authenticated');
        }
        
        // Load roles and format user data
        $user->load('roles');
        
        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'role' => $user->roles->first()?->name ?? 'user',
            'roles' => $user->roles->pluck('name')->toArray(),
            'is_active' => $user->is_active,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
        
        return $this->successResponse($userData, 'User information retrieved successfully');
    }

    /**
     * Toggle user active status (Admin only)
     */
    public function toggleUserStatus(Request $request, $userId)
    {
        $user = User::find($userId);
        
        if (!$user) {
            return $this->errorResponse(ErrorCode::USER_NOT_FOUND, null, 'Không tìm thấy người dùng');
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $status = $user->is_active ? 'mở khóa' : 'khóa';
        
        return $this->successResponse([
            'user_id' => $user->id,
            'is_active' => $user->is_active,
            'status' => $status
        ], "Tài khoản đã được {$status} thành công");
    }

    #[OA\Post(
        path: "/api/auth/send-otp",
        summary: "Send OTP for email verification",
        description: "Send OTP code to user's email for verification",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email", example: "user@example.com")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "OTP sent successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "OTP sent successfully")
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Validation errors",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Validation errors"),
                        new OA\Property(property: "errors", type: "object")
                    ]
                )
            )
        ]
    )]
    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'type' => 'sometimes|string|in:verification,reset'
        ]);

        $email = $request->email;

        // Allow OTP for both new and existing emails

        // Generate OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Delete any existing OTP for this email
        EmailVerification::where('email', $email)->delete();
        
        // Create new OTP record
        EmailVerification::create([
            'email' => $email,
            'otp' => $otp,
            'expires_at' => now()->addMinutes(5),
            'is_used' => false
        ]);

        // Send OTP email
        try {
            Mail::to($email)->send(new OtpMail($otp, $email, 'User'));
            
            return $this->successResponse(null, 'Mã OTP đã được gửi đến email của bạn');
        } catch (\Exception $e) {
            return $this->errorResponse(ErrorCode::INTERNAL_ERROR, null, 'Không thể gửi email. Vui lòng thử lại sau.');
        }
    }

    #[OA\Post(
        path: "/api/auth/verify-otp",
        summary: "Verify OTP code",
        description: "Verify OTP code and complete registration",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email", "otp", "name", "password", "password_confirmation"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email", example: "user@example.com"),
                    new OA\Property(property: "otp", type: "string", example: "123456"),
                    new OA\Property(property: "name", type: "string", example: "John Doe"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "password123"),
                    new OA\Property(property: "password_confirmation", type: "string", format: "password", example: "password123")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Registration completed successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Registration completed successfully"),
                        new OA\Property(
                            property: "data", 
                            type: "object",
                            properties: [
                                new OA\Property(property: "user", ref: "#/components/schemas/User"),
                                new OA\Property(property: "access_token", type: "string"),
                                new OA\Property(property: "token_type", type: "string", example: "bearer"),
                                new OA\Property(property: "expires_in", type: "integer", example: 3600)
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Invalid OTP",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Mã OTP không hợp lệ hoặc đã hết hạn")
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Validation errors",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Validation errors"),
                        new OA\Property(property: "errors", type: "object")
                    ]
                )
            )
        ]
    )]
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed'
        ]);

        $email = $request->email;
        $otp = $request->otp;

        // Find OTP record
        $otpRecord = EmailVerification::where('email', $email)
            ->where('otp', $otp)
            ->where('is_used', false)
            ->first();

        if (!$otpRecord || $otpRecord->isExpired()) {
            return $this->errorResponse(ErrorCode::VALIDATION_ERROR, null, 'Mã OTP không hợp lệ hoặc đã hết hạn');
        }

        // Mark OTP as used
        $otpRecord->update(['is_used' => true]);

        // Create user
        $user = User::create([
            'name' => $request->name,
            'email' => $email,
            'password' => Hash::make($request->password),
            'role_id' => 5, // Assign role_id = 5 (user role)
            'receive_notifications' => true,
            'email_verified_at' => now()
        ]);

        // Assign 'user' role via Spatie Permission
        $user->assignRole('user');

        $token = Auth::guard('api')->login($user);

        return $this->createdResponse([
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ], 'Đăng ký thành công');
    }

    #[OA\Post(
        path: "/api/auth/forgot-password",
        summary: "Send password reset OTP",
        description: "Send OTP for password reset",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email", example: "user@example.com")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Password reset OTP sent successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Mã OTP đặt lại mật khẩu đã được gửi")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "User not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Không tìm thấy tài khoản với email này")
                    ]
                )
            )
        ]
    )]
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $email = $request->email;

        // Check if user exists and get user info
        $user = User::where('email', $email)->first();
        if (!$user) {
            return $this->errorResponse(ErrorCode::NOT_FOUND, null, 'Không tìm thấy tài khoản với email này');
        }

        // Generate OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Delete any existing OTP for this email
        EmailVerification::where('email', $email)->delete();
        
        // Create new OTP record
        EmailVerification::create([
            'email' => $email,
            'otp' => $otp,
            'expires_at' => now()->addMinutes(5),
            'is_used' => false
        ]);

        // Send OTP email with password reset template
        try {
            Mail::to($email)->send(new PasswordResetMail($otp, $email, $user->name));
            
            return $this->successResponse(null, 'Mã OTP đặt lại mật khẩu đã được gửi đến email của bạn');
        } catch (\Exception $e) {
            return $this->errorResponse(ErrorCode::INTERNAL_ERROR, null, 'Không thể gửi email. Vui lòng thử lại sau.');
        }
    }

    #[OA\Post(
        path: "/api/auth/reset-password",
        summary: "Reset password with OTP",
        description: "Reset password using OTP verification",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email", "otp", "password", "password_confirmation"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email", example: "user@example.com"),
                    new OA\Property(property: "otp", type: "string", example: "123456"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "newpassword123"),
                    new OA\Property(property: "password_confirmation", type: "string", format: "password", example: "newpassword123")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Password reset successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Mật khẩu đã được đặt lại thành công")
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Invalid OTP",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Mã OTP không hợp lệ hoặc đã hết hạn")
                    ]
                )
            )
        ]
    )]
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
            'password' => 'required|string|min:6|confirmed'
        ]);

        $email = $request->email;
        $otp = $request->otp;

        // Find OTP record
        $otpRecord = EmailVerification::where('email', $email)
            ->where('otp', $otp)
            ->where('is_used', false)
            ->first();

        if (!$otpRecord || $otpRecord->isExpired()) {
            return $this->errorResponse(ErrorCode::VALIDATION_ERROR, null, 'Mã OTP không hợp lệ hoặc đã hết hạn');
        }

        // Mark OTP as used
        $otpRecord->update(['is_used' => true]);

        // Update user password
        $user = User::where('email', $email)->first();
        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return $this->successResponse(null, 'Mật khẩu đã được đặt lại thành công');
    }

    /**
     * Change user password
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = Auth::user();
        
        if (!Hash::check($request->current_password, $user->password)) {
            return $this->errorResponse(ErrorCode::VALIDATION_ERROR, null, 'Mật khẩu hiện tại không đúng');
        }

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return $this->successResponse(null, 'Mật khẩu đã được thay đổi thành công');
    }
}
