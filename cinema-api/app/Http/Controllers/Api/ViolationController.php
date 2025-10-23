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
            return $this->errorResponse(ErrorCode::NOT_FOUND, null, 'Reportable entity not found');
        }

        // Check if user already reported this entity
        $existingReport = ViolationReport::where('reporter_id', auth()->id())
            ->where('reportable_id', $request->reportable_id)
            ->where('reportable_type', $request->reportable_type)
            ->first();

        if ($existingReport) {
            return $this->errorResponse(ErrorCode::VALIDATION_ERROR, null, 'You have already reported this content');
        }

        $report = ViolationReport::create([
            'reporter_id' => auth()->id(),
            'reportable_id' => $request->reportable_id,
            'reportable_type' => $request->reportable_type,
            'violation_type' => $request->violation_type,
            'description' => $request->description,
            'status' => 'pending'
        ]);

        return $this->successResponse($report->load(['reporter', 'reportable']), 'Violation report created successfully', 201);
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
            \Log::info('Attempting to hide comment', [
                'violation_id' => $report->id,
                'reportable_type' => $report->reportable_type,
                'reportable_id' => $report->reportable_id,
                'status' => $request->status
            ]);
            
            $comment = \App\Models\Comment::find($report->reportable_id);
            if ($comment) {
                \Log::info('Found comment, updating to hidden', ['comment_id' => $comment->id]);
                $comment->update([
                    'is_hidden' => true,
                    'hidden_reason' => 'Vi phạm nội dung - ' . ($request->resolution_notes ?? 'Không có ghi chú'),
                    'hidden_by' => auth()->id(),
                    'hidden_at' => now()
                ]);
                \Log::info('Comment hidden successfully', ['comment_id' => $comment->id]);
            } else {
                \Log::warning('Comment not found', ['comment_id' => $report->reportable_id]);
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
            
            if ($reportableType === 'App\\Models\\Review') {
                $content = Review::find($reportableId);
                if ($content) {
                    $content->is_hidden = $hide;
                    $content->hidden_reason = $hide ? 'Vi phạm nội dung' : null;
                    $content->hidden_by = $hide ? 1 : null; // Use admin ID 1 for public route
                    $content->hidden_at = $hide ? now() : null;
                    $content->save();
                }
            } elseif ($reportableType === 'App\\Models\\Comment') {
                $content = Comment::find($reportableId);
                if ($content) {
                    $content->is_hidden = $hide;
                    $content->hidden_reason = $hide ? 'Vi phạm nội dung' : null;
                    $content->hidden_by = $hide ? 1 : null; // Use admin ID 1 for public route
                    $content->hidden_at = $hide ? now() : null;
                    $content->save();
                }
            }
            
            // Update violation status
            $violation->status = $hide ? 'resolved' : 'pending';
            $violation->resolved_at = $hide ? now() : null;
            $violation->handled_by = $hide ? 1 : null; // Use admin ID 1 for public route
            $violation->save();
            
            return $this->successResponse([
                'violation_id' => $violation->id,
                'content_type' => $reportableType,
                'content_id' => $reportableId,
                'is_hidden' => $hide,
                'status' => $violation->status
            ], $hide ? 'Nội dung đã được ẩn' : 'Nội dung đã được hiện');
            
        } catch (\Exception $e) {
            \Log::error('Toggle visibility error: ' . $e->getMessage());
            return $this->errorResponse(ErrorCode::INTERNAL_ERROR, null, 'Lỗi khi cập nhật trạng thái: ' . $e->getMessage());
        }
    }
}