<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;

class S3UploadService
{
    /**
     * Upload a default profile image to S3.
     *
     * @param string $imagePath
     * @return string|null
     */
    public function uploadDefaultProfileImage($imagePath)
    {
        try {
            // Check if the image exists
            if (file_exists($imagePath)) {
                $imageName = 'profile_' . uniqid() . '.jpeg';
                $uploadPath = 'profiles/'. $imageName;

                $imageUrl = null;

                // Upload the file to S3
                Storage::disk('s3')->put($uploadPath, file_get_contents($imagePath));

                // Get the URL of the uploaded file
                $imageUrl = Storage::disk('s3')->url($uploadPath);

                return $imageUrl;
            }
            return null;
        } catch (\Aws\Exception\AwsException $e) {
            return [
                'statusCode' => 500,
                'body' => json_encode([
                    'error' => $e->getMessage(),
                ]),
            ];
        } catch (Exception $e) {
            // Log the error for debugging purposes
            return handleException($e);
        }

        
    }
}

