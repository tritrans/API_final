<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GoogleDriveService;
use App\Traits\ApiResponse;
use App\Enums\ErrorCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FileUploadController extends Controller
{
    use ApiResponse;

    private $googleDriveService;

    public function __construct(GoogleDriveService $googleDriveService)
    {
        $this->googleDriveService = $googleDriveService;
        $this->middleware('auth:api')->only(['uploadFile']);
    }

    /**
     * Test upload file (no auth required)
     */
    public function testUploadFile(Request $request)
    {
        return $this->uploadFile($request);
    }

    /**
     * Test Google Drive authentication
     */
    public function testGoogleDriveAuth()
    {
        try {
            $result = $this->googleDriveService->testAuth();
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function uploadFileLocal(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|image|max:10240', // 10MB max, only images
            'type' => 'required|in:poster,backdrop,image',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $file = $request->file('file');
            $type = $request->input('type');
            
            // Store file locally
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('uploads/' . $type, $filename, 'public');
            
            // Generate URL
            $url = asset('storage/' . $path);

            return $this->successResponse([
                'url' => $url,
                'type' => $type,
                'filename' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'path' => $path,
            ], 'File uploaded successfully');

        } catch (\Exception $e) {
            return $this->errorResponse(ErrorCode::FILE_UPLOAD_ERROR, null, 'Upload failed: ' . $e->getMessage());
        }
    }

    /**
     * Upload single file
     */
    public function uploadFile(Request $request)
    {
        error_log('Upload file request received');
        error_log('Request data: ' . json_encode($request->all()));
        error_log('Has file: ' . ($request->hasFile('file') ? 'Yes' : 'No'));
        error_log('File size: ' . ($request->hasFile('file') ? $request->file('file')->getSize() : 'N/A'));
        error_log('File type: ' . ($request->hasFile('file') ? $request->file('file')->getMimeType() : 'N/A'));
        
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|image|max:10240', // 10MB max, only images
            'type' => 'required|in:poster,backdrop,trailer,image,avatar',
        ]);

        if ($validator->fails()) {
            error_log('Validation failed: ' . json_encode($validator->errors()));
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $file = $request->file('file');
            $type = $request->input('type');
            
            // For avatar, use local storage
            if ($type === 'avatar') {
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('uploads/avatars', $filename, 'public');
            $url = asset('storage/' . $path);

            return $this->successResponse([
                'url' => $url,
                'type' => $type,
                'filename' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'path' => $path,
            ], 'Avatar uploaded successfully');
        }
        
        // For other types, use Google Drive
        error_log('Attempting Google Drive upload for type: ' . $type);
        try {
            $url = $this->googleDriveService->uploadFile($file, $type);
            error_log('Google Drive upload result: ' . ($url ? 'Success' : 'Failed'));
            
            if (!$url) {
                error_log('Google Drive upload returned null/empty URL');
                return $this->errorResponse(ErrorCode::FILE_UPLOAD_ERROR, null, 'Failed to upload file to Google Drive');
            }
            
            error_log('Google Drive upload successful, URL: ' . $url);
        } catch (\Exception $e) {
            error_log('Google Drive upload exception: ' . $e->getMessage());
            return $this->errorResponse(ErrorCode::FILE_UPLOAD_ERROR, null, 'Google Drive upload failed: ' . $e->getMessage());
        }

        return $this->successResponse([
            'url' => $url,
            'type' => $type,
            'filename' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
        ], 'File uploaded successfully');

        } catch (\Exception $e) {
            return $this->errorResponse(ErrorCode::FILE_UPLOAD_ERROR, null, 'Upload failed: ' . $e->getMessage());
        }
    }

    /**
     * Upload multiple files
     */
    public function uploadMultipleFiles(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'files.*' => 'required|file|max:10240', // 10MB max per file
            'type' => 'required|in:poster,backdrop,trailer,image',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $files = $request->file('files');
            $type = $request->input('type');
            
            // Upload to Google Drive
            $urls = $this->googleDriveService->uploadMultipleFiles($files, $type);
            
            if (empty($urls)) {
                return $this->errorResponse(ErrorCode::FILE_UPLOAD_ERROR, null, 'Failed to upload files');
            }

            $uploadedFiles = [];
            foreach ($files as $index => $file) {
                if (isset($urls[$index])) {
                    $uploadedFiles[] = [
                        'url' => $urls[$index],
                        'type' => $type,
                        'filename' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                    ];
                }
            }

            return $this->successResponse($uploadedFiles, 'Files uploaded successfully');

        } catch (\Exception $e) {
            return $this->errorResponse(ErrorCode::FILE_UPLOAD_ERROR, null, 'Upload failed: ' . $e->getMessage());
        }
    }

    /**
     * Delete file from Google Drive
     */
    public function deleteFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'url' => 'required|url',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $url = $request->input('url');
            $fileId = $this->googleDriveService->extractFileId($url);
            
            if (!$fileId) {
                return $this->errorResponse(ErrorCode::FILE_NOT_FOUND, null, 'Invalid Google Drive URL');
            }

            $deleted = $this->googleDriveService->deleteFile($fileId);
            
            if (!$deleted) {
                return $this->errorResponse(ErrorCode::FILE_UPLOAD_ERROR, null, 'Failed to delete file');
            }

            return $this->successResponse(null, 'File deleted successfully');

        } catch (\Exception $e) {
            return $this->errorResponse(ErrorCode::FILE_UPLOAD_ERROR, null, 'Delete failed: ' . $e->getMessage());
        }
    }

    /**
     * Get file info
     */
    public function getFileInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'url' => 'required|url',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $url = $request->input('url');
            $fileId = $this->googleDriveService->extractFileId($url);
            
            if (!$fileId) {
                return $this->errorResponse(ErrorCode::FILE_NOT_FOUND, null, 'Invalid Google Drive URL');
            }

            return $this->successResponse([
                'file_id' => $fileId,
                'url' => $url,
                'download_url' => $this->googleDriveService->getDownloadUrl($fileId),
            ], 'File info retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse(ErrorCode::FILE_NOT_FOUND, null, 'Failed to get file info: ' . $e->getMessage());
        }
    }
}
