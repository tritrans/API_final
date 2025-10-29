<?php

namespace App\Services;

use Google_Client;
use Google_Service_Drive;
use Google_Service_Drive_DriveFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class GoogleDriveService
{
    private $client;
    private $service;
    private $folderId;

    public function __construct()
    {
        $this->client = new Google_Client();
        
        // Use OAuth with refresh token
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setAccessType('offline');
        $this->client->setApprovalPrompt('force');
        $this->client->setScopes([Google_Service_Drive::DRIVE]);
        
        // Set refresh token
        $refreshToken = config('services.google.refresh_token');
        if ($refreshToken) {
            $this->client->refreshToken($refreshToken);
        }

        $this->service = new Google_Service_Drive($this->client);
        $this->folderId = config('services.google.folder_id');
    }

    /**
     * Test configuration
     */
    public function testConfig(): array
    {
        return [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret') ? 'Set' : 'Not set',
            'refresh_token' => config('services.google.refresh_token') ? 'Set' : 'Not set',
            'folder_id' => config('services.google.folder_id'),
            'service_account_path' => config('services.google.service_account_path'),
            'has_service_account_file' => file_exists(storage_path('app/google-drive-key.json'))
        ];
    }

    /**
     * Test authentication
     */
    public function testAuth(): array
    {
        try {
            // Try to get access token
            $accessToken = $this->client->getAccessToken();
            
            if (!$accessToken) {
                return [
                    'success' => false,
                    'error' => 'No access token available',
                    'auth_url' => $this->client->createAuthUrl()
                ];
            }
            
            // Test API call
            $files = $this->service->files->listFiles([
                'pageSize' => 1,
                'fields' => 'files(id,name)'
            ]);
            
            return [
                'success' => true,
                'access_token' => $accessToken,
                'files_count' => count($files->getFiles())
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'auth_url' => $this->client->createAuthUrl()
            ];
        }
    }
    public function uploadFile(UploadedFile $file, string $folder = 'movies'): ?string
    {
        try {
            // Ensure we have a valid access token
            $this->ensureValidAccessToken();

            // Create file metadata
            $fileName = $this->generateFileName($file);
            $fileMetadata = new Google_Service_Drive_DriveFile([
                'name' => $fileName,
                'parents' => [$this->folderId],
            ]);
            
            // Upload file
            $content = file_get_contents($file->getRealPath());
            $uploadedFile = $this->service->files->create($fileMetadata, [
                'data' => $content,
                'mimeType' => $file->getMimeType(),
                'uploadType' => 'multipart',
                'fields' => 'id,webViewLink,webContentLink'
            ]);
            
            // Make file publicly accessible
            $this->makeFilePublic($uploadedFile->getId());

            // Return thumbnail URL for images
            if (str_starts_with($file->getMimeType(), 'image/')) {
                $thumbnailUrl = $this->getThumbnailUrl($uploadedFile->getId());
                return $thumbnailUrl;
            }
            
            // Return public URL for other files
            return $this->getPublicUrl($uploadedFile->getId());

        } catch (\Exception $e) {
            \Log::error('Google Drive upload failed: ' . $e->getMessage());
            \Log::error('Google Drive upload error details: ' . json_encode([
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'file_type' => $file->getMimeType(),
                'folder_id' => $this->folderId,
                'client_id' => config('services.google.client_id'),
                'has_refresh_token' => !empty(config('services.google.refresh_token')),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]));
            return null;
        }
    }

    /**
     * Upload file content to Google Drive
     */
    public function uploadFileContent(string $content, string $fileName, string $mimeType = 'image/jpeg'): ?string
    {
        try {
            // Create file metadata
            $fileMetadata = new Google_Service_Drive_DriveFile([
                'name' => $fileName,
                'parents' => [$this->folderId],
            ]);

            // Upload file
            $uploadedFile = $this->service->files->create($fileMetadata, [
                'data' => $content,
                'mimeType' => $mimeType,
                'uploadType' => 'multipart',
                'fields' => 'id,webViewLink,webContentLink'
            ]);

            // Make file publicly accessible
            $this->makeFilePublic($uploadedFile->getId());

            // Return public URL
            return $this->getPublicUrl($uploadedFile->getId());

        } catch (\Exception $e) {return null;
        }
    }

    /**
     * Upload multiple files
     */
    public function uploadMultipleFiles(array $files, string $folder = 'movies'): array
    {
        $uploadedUrls = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $url = $this->uploadFile($file, $folder);
                if ($url) {
                    $uploadedUrls[] = $url;
                }
            }
        }

        return $uploadedUrls;
    }

    /**
     * Delete file from Google Drive
     */
    public function deleteFile(string $fileId): bool
    {
        try {
            $this->service->files->delete($fileId);
            return true;
        } catch (\Exception $e) {return false;
        }
    }

    /**
     * Generate unique filename
     */
    private function generateFileName(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $timestamp = now()->format('Y-m-d_H-i-s');
        
        return "{$name}_{$timestamp}.{$extension}";
    }

    /**
     * Make file publicly accessible
     */
    private function makeFilePublic(string $fileId): void
    {
        try {
            $permission = new \Google_Service_Drive_Permission([
                'type' => 'anyone',
                'role' => 'reader',
            ]);

            $this->service->permissions->create($fileId, $permission);
        } catch (\Exception $e) {}
    }

    /**
     * Get public URL for file
     */
    private function getPublicUrl(string $fileId): string
    {
        // Use thumbnail URL for better compatibility
        return "https://drive.google.com/thumbnail?id={$fileId}&sz=w800-h1200";
    }

    /**
     * Ensure we have a valid access token
     */
    private function ensureValidAccessToken(): void
    {
        try {
            // Check if we have an access token
            $accessToken = $this->client->getAccessToken();
            
            if (!$accessToken) {
                // Try to refresh the token
                $refreshToken = config('services.google.refresh_token');
                if ($refreshToken) {
                    $this->client->refreshToken($refreshToken);
                    $accessToken = $this->client->getAccessToken();
                }
            }
            
            if (!$accessToken) {
                throw new \Exception('No valid access token available');
            }
        } catch (\Exception $e) {
            throw new \Exception('Failed to authenticate with Google Drive: ' . $e->getMessage());
        }
    }

    /**
     * Get thumbnail URL for file
     */
    public function getThumbnailUrl(string $fileId, int $width = 400, int $height = 600): string
    {
        // Use the more reliable thumbnail URL format
        return "https://drive.google.com/thumbnail?id={$fileId}&sz=w{$width}-h{$height}";
    }
    
    /**
     * Get alternative thumbnail URL formats
     */
    public function getAlternativeUrls(string $fileId): array
    {
        return [
            "https://drive.google.com/thumbnail?id={$fileId}&sz=w400-h600",
            "https://drive.google.com/thumbnail?id={$fileId}&sz=w800-h1200",
            "https://lh3.googleusercontent.com/d/{$fileId}",
        ];
    }

    /**
     * Get direct download URL
     */
    public function getDownloadUrl(string $fileId): string
    {
        return "https://drive.google.com/uc?export=download&id={$fileId}";
    }

    /**
     * Extract file ID from Google Drive URL
     */
    public function extractFileId(string $url): ?string
    {
        $pattern = '/\/d\/([a-zA-Z0-9-_]+)/';
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
