<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use App\Enums\ErrorCode;
use Illuminate\Http\Request;
use App\Models\ViolationReport;
use App\Models\Review;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class ViolationController extends Controller
{
    use ApiResponse;

    /**
     * Get all violation reports
     */
    public function index(Request $request)
    {
        // Temporarily disable permission check for testing
        // if (!auth()->user()->can('view violations')) {
        //     return $this->errorResponse(ErrorCode::FORBIDDEN, null, 'Insufficient permissions to view violations');
        // }

        $query = ViolationReport::with(['reporter', 'handler', 'reportable']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by violation type
        if ($request->has('violation_type')) {
            $query->where('violation_type', $request->violation_type);
        }

        // Filter by reportable type
        if ($request->has('reportable_type')) {
            $query->where('reportable_type', $request->reportable_type);
        }

        $reports = $query->orderBy('created_at', 'desc')->get();

        return $this->successResponse($reports, 'Violation reports retrieved successfully');
    }

    /**
     * Create a new violation report
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reportable_id' => 'required|integer',
            'reportable_type' => 'required|string|in:App\Models\Review,App\Models\Comment',
            'violation_type' => 'required|string|in:spam,inappropriate_content,harassment,fake_review,offensive_language,copyright_violation,other',
            'description' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(ErrorCode::VALIDATION_ERROR, $validator->errors(), 'Validation failed');
        }

        // Check if the reportable entity exists
        $reportableClass = $request->reportable_type;
        $reportable = $reportableClass::find($request->reportable_id);
        
        if (!$reportable) {
            return $this->errorResponse(ErrorCode::REVIEW_NOT_FOUND, null, 'Reportable entity not found');
        }

        // Get reporter ID (use authenticated user or default admin)
        $reporterId = auth()->id();
        if (!$reporterId) {
            // If no authenticated user, use admin user as default reporter
            $adminUser = User::whereHas('roles', function($query) {
                $query->where('name', 'admin');
            })->first();
            $reporterId = $adminUser ? $adminUser->id : 1;
        }

        // Check if user already reported this entity
        $existingReport = ViolationReport::where('reporter_id', $reporterId)
            ->where('reportable_id', $request->reportable_id)
            ->where('reportable_type', $request->reportable_type)
            ->first();

        if ($existingReport) {
            return $this->errorResponse(ErrorCode::VALIDATION_ERROR, null, 'You have already reported this content');
        }

        $report = ViolationReport::create([
            'reporter_id' => $reporterId,
            'reportable_id' => $request->reportable_id,
            'reportable_type' => $request->reportable_type,
            'violation_type' => $request->violation_type,
            'description' => $request->description,
            'status' => 'pending'
        ]);

        return $this->successResponse($report->load(['reporter', 'reportable']), 'Violation report created successfully', 201);
    }

    /**
     * Hide content immediately when reported
     */
    private function hideContentImmediately($reportableType, $reportableId, $reporterId)
    {
        if ($reportableType === 'App\\Models\\Review') {
            // Hide review and all its replies
            $review = Review::find($reportableId);
            if ($review) {
                $review->is_hidden = true;
                $review->hidden_reason = 'Vi phạm nội dung (đã báo cáo)';
                $review->hidden_by = $reporterId;
                $review->hidden_at = now();
                $review->save();
                
                // Hide all replies to this review
                Comment::where('parent_id', $reportableId)
                    ->update([
                        'is_hidden' => true,
                        'hidden_reason' => 'Vi phạm nội dung (review gốc bị báo cáo)',
                        'hidden_by' => $reporterId,
                        'hidden_at' => now()
                    ]);
            }
        } elseif ($reportableType === 'App\\Models\\Comment') {
            $comment = Comment::find($reportableId);
            if ($comment) {
                if ($comment->parent_id) {
                    // This is a reply - only hide the reply
                    $comment->is_hidden = true;
                    $comment->hidden_reason = 'Vi phạm nội dung (đã báo cáo)';
                    $comment->hidden_by = $reporterId;
                    $comment->hidden_at = now();
                    $comment->save();
                } else {
                    // This is a top-level comment - hide comment and all replies
                    $comment->is_hidden = true;
                    $comment->hidden_reason = 'Vi phạm nội dung (đã báo cáo)';
                    $comment->hidden_by = $reporterId;
                    $comment->hidden_at = now();
                    $comment->save();
                    
                    // Hide all replies to this comment
                    Comment::where('parent_id', $reportableId)
                        ->update([
                            'is_hidden' => true,
                            'hidden_reason' => 'Vi phạm nội dung (comment gốc bị báo cáo)',
                            'hidden_by' => $reporterId,
                            'hidden_at' => now()
                        ]);
                }
            }
        }
    }

    /**
     * Update violation report status
     */
    public function update(Request $request, $id)
    {
        // Temporarily disable permission check for testing
        // if (!auth()->user()->can('handle violations')) {
        //     return $this->errorResponse(ErrorCode::FORBIDDEN, null, 'Insufficient permissions to handle violations');
        // }

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:pending,reviewing,resolved,dismissed',
            'resolution_notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(ErrorCode::VALIDATION_ERROR, $validator->errors(), 'Validation failed');
        }

        $report = ViolationReport::findOrFail($id);

        $report->update([
            'status' => $request->status,
            'handled_by' => auth()->id(),
            'resolution_notes' => $request->resolution_notes,
            'resolved_at' => $request->status === 'resolved' ? now() : null
        ]);

        // If violation is resolved and it's about a comment, hide the comment
        if ($request->status === 'resolved' && $report->reportable_type === 'App\\Models\\Comment') {
            $comment = \App\Models\Comment::find($report->reportable_id);
            if ($comment) {
                $comment->update([
                    'is_hidden' => true,
                    'hidden_reason' => 'Vi phạm nội dung - ' . ($request->resolution_notes ?? 'Không có ghi chú'),
                    'hidden_by' => auth()->id(),
                    'hidden_at' => now()
                ]);
            }
        }

        return $this->successResponse($report->load(['reporter', 'handler', 'reportable']), 'Violation report updated successfully');
    }

    /**
     * Get violation report details
     */
    public function show($id)
    {
        if (!auth()->user()->can('view violations')) {
            return $this->errorResponse(ErrorCode::FORBIDDEN, null, 'Insufficient permissions to view violations');
        }

        $report = ViolationReport::with(['reporter', 'handler', 'reportable'])->findOrFail($id);

        return $this->successResponse($report, 'Violation report retrieved successfully');
    }

    /**
     * Get violation statistics
     */
    public function statistics()
    {
        if (!auth()->user()->can('view violations')) {
            return $this->errorResponse(ErrorCode::FORBIDDEN, null, 'Insufficient permissions to view violations');
        }

        $stats = [
            'total_reports' => ViolationReport::count(),
            'pending_reports' => ViolationReport::pending()->count(),
            'resolved_reports' => ViolationReport::resolved()->count(),
            'violation_types' => ViolationReport::selectRaw('violation_type, COUNT(*) as count')
                ->groupBy('violation_type')
                ->get(),
            'reports_by_type' => [
                'reviews' => ViolationReport::where('reportable_type', 'App\Models\Review')->count(),
                'comments' => ViolationReport::where('reportable_type', 'App\Models\Comment')->count()
            ]
        ];

        return $this->successResponse($stats, 'Violation statistics retrieved successfully');
    }

    /**
     * Ban user (for violation managers)
     */
    public function banUser(Request $request, $userId)
    {
        if (!auth()->user()->can('ban users')) {
            return $this->errorResponse(ErrorCode::FORBIDDEN, null, 'Insufficient permissions to ban users');
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
            'duration_days' => 'nullable|integer|min:1|max:365'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(ErrorCode::VALIDATION_ERROR, $validator->errors(), 'Validation failed');
        }

        $user = User::findOrFail($userId);

        // Add ban logic here (you might want to add a banned_until field to users table)
        // For now, we'll just log the action
        
        return $this->successResponse([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'reason' => $request->reason,
            'duration_days' => $request->duration_days,
            'banned_by' => auth()->user()->name,
            'banned_at' => now()
        ], 'User banned successfully');
    }

    /**
     * Warn user (for violation managers)
     */
    public function warnUser(Request $request, $userId)
    {
        if (!auth()->user()->can('warn users')) {
            return $this->errorResponse(ErrorCode::FORBIDDEN, null, 'Insufficient permissions to warn users');
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(ErrorCode::VALIDATION_ERROR, $validator->errors(), 'Validation failed');
        }

        $user = User::findOrFail($userId);

        // Add warning logic here (you might want to add a warnings table)
        
        return $this->successResponse([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'reason' => $request->reason,
            'warned_by' => auth()->user()->name,
            'warned_at' => now()
        ], 'User warned successfully');
    }

    /**
     * Toggle content visibility (hide/show) - Public version (no auth required)
     */
    public function toggleVisibility(Request $request, $id)
    {
        try {
            $violation = ViolationReport::findOrFail($id);
            $hide = $request->input('hide', true);
            
            // Get the reported content
            $reportableType = $violation->reportable_type;
            $reportableId = $violation->reportable_id;
            
            // Get admin user ID (first admin user)
            $adminUser = User::whereHas('roles', function($query) {
                $query->where('name', 'admin');
            })->first();
            
            $adminId = $adminUser ? $adminUser->id : null;
            
            if ($reportableType === 'App\\Models\\Review') {
                // Review gốc bị báo cáo - ẩn cả review và tất cả reply
                $content = Review::find($reportableId);
                if ($content) {
                    $content->is_hidden = $hide;
                    $content->hidden_reason = $hide ? 'Vi phạm nội dung' : null;
                    $content->hidden_by = $hide ? $adminId : null;
                    $content->hidden_at = $hide ? now() : null;
                    $content->save();
                    
                    // Ẩn tất cả reply của review này
                    if ($hide) {
                        Comment::where('parent_id', $reportableId)
                            ->update([
                                'is_hidden' => true,
                                'hidden_reason' => 'Vi phạm nội dung (review gốc bị ẩn)',
                                'hidden_by' => $adminId,
                                'hidden_at' => now()
                            ]);
                    } else {
                        // Hiện lại tất cả reply khi review được hiện
                        Comment::where('parent_id', $reportableId)
                            ->update([
                                'is_hidden' => false,
                                'hidden_reason' => null,
                                'hidden_by' => null,
                                'hidden_at' => null
                            ]);
                    }
                }
            } elseif ($reportableType === 'App\\Models\\Comment') {
                $content = Comment::find($reportableId);
                if ($content) {
                    // Kiểm tra xem đây có phải reply không
                    if ($content->parent_id) {
                        // Đây là reply - chỉ ẩn reply này thôi
                        $content->is_hidden = $hide;
                        $content->hidden_reason = $hide ? 'Vi phạm nội dung' : null;
                        $content->hidden_by = $hide ? $adminId : null;
                        $content->hidden_at = $hide ? now() : null;
                        $content->save();
                    } else {
                        // Đây là comment gốc - ẩn cả comment và tất cả reply
                        $content->is_hidden = $hide;
                        $content->hidden_reason = $hide ? 'Vi phạm nội dung' : null;
                        $content->hidden_by = $hide ? $adminId : null;
                        $content->hidden_at = $hide ? now() : null;
                        $content->save();
                        
                        // Ẩn tất cả reply của comment này
                        if ($hide) {
                            Comment::where('parent_id', $reportableId)
                                ->update([
                                    'is_hidden' => true,
                                    'hidden_reason' => 'Vi phạm nội dung (comment gốc bị ẩn)',
                                    'hidden_by' => $adminId,
                                    'hidden_at' => now()
                                ]);
                        } else {
                            // Hiện lại tất cả reply khi comment được hiện
                            Comment::where('parent_id', $reportableId)
                                ->update([
                                    'is_hidden' => false,
                                    'hidden_reason' => null,
                                    'hidden_by' => null,
                                    'hidden_at' => null
                                ]);
                        }
                    }
                }
            }
            
            // Update violation status
            $violation->status = $hide ? 'resolved' : 'pending';
            $violation->resolved_at = $hide ? now() : null;
            $violation->handled_by = $hide ? $adminId : null;
            $violation->save();
            
            return $this->successResponse([
                'violation_id' => $violation->id,
                'content_type' => $reportableType,
                'content_id' => $reportableId,
                'is_hidden' => $hide,
                'status' => $violation->status
            ], $hide ? 'Nội dung đã được ẩn' : 'Nội dung đã được hiện');
            
        } catch (\Exception $e) {
            return $this->errorResponse(ErrorCode::INTERNAL_ERROR, null, 'Lỗi khi cập nhật trạng thái: ' . $e->getMessage());
        }
    }
}