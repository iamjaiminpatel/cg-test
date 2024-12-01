<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Models\Album;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;


class AlbumController extends Controller
{

    /**
     * Method to create album
     *
     * @param object $request
     * @return string
     */
    public function create(Request $request)
    {

        try {
            $request->validate([
                'title' => 'required',
                'description' => 'required',
            ]);

           $currentUser = auth()->user();

           // Handle the gallery image upload to S3 if present
            $albumImageUrl = null;
            if ($request->hasFile('image')) {
                $ext = pathinfo($request->file('image')->getClientOriginalName(), PATHINFO_EXTENSION);
                $imageName = pathinfo($request->file('image')->getClientOriginalName(), PATHINFO_FILENAME);
                
                // Upload the file to S3
                $uploadPath = 'album/'. $imageName.'_'.Str::random(3).'.'.$ext;
                Storage::disk('s3')->put($uploadPath, file_get_contents($request->file('image')->getPathname()));

                // Get the URL of the uploaded file
                $albumImageUrl = Storage::disk('s3')->url($uploadPath);
            }
            
           // Create a new user
            Album::create([
                'user_id' => $currentUser->id,
                'title' => $request->title,
                'description' => $request->description,
                'img' => $albumImageUrl
            ]);

           // If successful, return the authenticated user
            return response()->json([
                'message' => 'Album created successfully.',
                'is_success'=>true
            ],Response::HTTP_OK);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'is_success' => false
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Exception $e) {
            // Log the error for debugging purposes
            return handleException($e);
        }
    }


    /**
     * Method to fetch current login user's album
     *
     * @return string
     */
    public function index()
    {

        try {
            $currentUser = auth()->user();
            $albums = Album::get()->where('user_id',$currentUser->id)->toArray();
            
            // If successful, return the authenticated user
            return response()->json([
                'message' => 'Album fetched successfully.',
                'albums' => $albums,
                'is_success'=>true
            ],Response::HTTP_OK);
        } catch (Exception $e) {
            // Log the error for debugging purposes
            return handleException($e);
        }
    }


    /**
     * Method to update album
     *
     * @param object $request
     * @param int $id
     * @return string
     */
    public function update(Request $request, $id)
    {
        try {
            
            // Validate the input data
            $request->validate([
                'title' => 'required',
                'description' => 'required',
                'featured' => 'required'
            ]);

            // Find the album by ID
            $album = Album::findOrFail($id);

            // Check if the authenticated user is authorized to update this album
            $currentUser = auth()->user();
            
            if ($album->user_id !== $currentUser->id) {
                return response()->json([
                    'message' => 'Unauthorized',
                    'is_success' => false,
                ], Response::HTTP_FORBIDDEN);
            }
            // Handle the new image upload if present
            $newImageUrl = $album->img; // Default to existing image URL
            if ($request->hasFile('image')) {
                // Delete the old image from S3 if it exists
                if ($album->img) {
                    $oldImagePath = parse_url($album->img, PHP_URL_PATH);
                    Storage::disk('s3')->delete(ltrim($oldImagePath, '/'));
                }

                // Upload the new file to S3
                $ext = pathinfo($request->file('image')->getClientOriginalName(), PATHINFO_EXTENSION);
                $imageName = pathinfo($request->file('image')->getClientOriginalName(), PATHINFO_FILENAME);
                $uploadPath = 'album/' . $imageName . '_' . Str::random(3) . '.' . $ext;
                Storage::disk('s3')->put($uploadPath, file_get_contents($request->file('image')->getPathname()));

                // Update the image URL
                $newImageUrl = Storage::disk('s3')->url($uploadPath);
            }

            // Update the album
            $album->update([
                'title' => $request->title,
                'description' => $request->description,
                'img' => $newImageUrl,
                'featured' => ($request->featured) ? 1 : 0
            ]);

            return response()->json([
                'message' => 'Album updated successfully.',
                'is_success' => true,
            ], Response::HTTP_OK);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'is_success' => false,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Exception $e) {
            // Log the error for debugging purposes
            return handleException($e);
        }
    }


    /**
     * Method to delete album
     *
     * @param int $id
     * @return string
     */
    public function delete($id)
    {
        try {
            // Find the album by ID
            $album = Album::findOrFail($id);

            // Check if the authenticated user is authorized to delete this album
            $currentUser = auth()->user();
            if ($album->user_id !== $currentUser->id) {
                return response()->json([
                    'message' => 'Unauthorized',
                    'is_success' => false,
                ], Response::HTTP_FORBIDDEN);
            }

            // Delete the image from S3 if it exists
            if ($album->img) {
                $oldImagePath = parse_url($album->img, PHP_URL_PATH);
                Storage::disk('s3')->delete(ltrim($oldImagePath, '/'));
            }

            // Delete the album from the database
            $album->delete();

            return response()->json([
                'message' => 'Album deleted successfully.',
                'is_success' => true,
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            // Log the error for debugging purposes
            return handleException($e);
        }
    }


}
