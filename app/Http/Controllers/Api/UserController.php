<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Services\S3UploadService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Jobs\SendRegistrationEmailJob;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{

    protected $s3UploadService;

    public function __construct(S3UploadService $s3UploadService)
    {
        $this->s3UploadService = $s3UploadService;
    }

    /**
     * Method to get current user's
     *
     * @return string
     */
    public function index()
    {
        try {
            // Attempt to authenticate the user using the JWT token
            $user = auth()->user();

            // If successful, return the authenticated user
            return response()->json([
                'message' => 'User fetched successfully.',
                'user' => $user,
                'is_success'=>true
            ],Response::HTTP_OK);
        }catch (Exception $e) {
            // Log the error for debugging purposes
            return handleException($e);
        }
    }


    /**
     * Method to register user
     *
     * @param object $request
     * @return string
     */
    public function register(Request $request)
    {
        try {

            //Validate request
            $request->validate([
                'full_name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'phone_num' => 'required|integer|digits:10|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'password_confirmation' => 'required|string|min:8'
            ]);

            // Path to the default profile image
            $defaultImagePath = public_path('images/profile.jpeg');

            // Upload the image to S3 and get the URL
            $imageUrl = $this->s3UploadService->uploadDefaultProfileImage($defaultImagePath);

            // Create a new user
            $user = User::create([
                'full_name' => $request->full_name,
                'email' => $request->email,
                'phone_num' => $request->phone_num,
                'password' => Hash::make($request->password),
                'profile_image' => $imageUrl,
                'bio' => $request->bio,
            ]);

            // Dispatch the email job
            SendRegistrationEmailJob::dispatch($user);

            return response()->json([
                'message' => 'User registered successfully!',
                'is_success' => true
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
     * Method to change password
     *
     * @param object $request
     * @return string
     */
    public function changePassword(Request $request)
    {
        try {
            // Validate the request data
            $request->validate([
                'current_password' => 'required',
                'new_password' => 'required|min:8|string|confirmed',
                'new_password_confirmation' => 'required|string|min:8'
            ]);

            // Get the authenticated user
            $user = auth()->user();

            // Check if the current password is correct
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json(['message' => 'Current password is incorrect.'], 400);
            }

            // Update the user's password
            $user->update(['password' => Hash::make($request->new_password)]);

            $token = auth()->login($user); // Re-issue a new token

            return response()->json([
                'message' => 'Password changed successfully.',
                'token' => $token,
                'is_success' => true
            ], Response::HTTP_OK);

        } catch (Exception $e) {
            // Log the error for debugging purposes
            return handleException($e);
        }
    }

    /**
     * Method to user details updated
     *
     * @param object $request
     * @param int $id
     * @return string
     */
    public function update(Request $request, $id)
    {
        try {
            $id = (int)$id;
            // Validate the input data
            $request->validate([
                'full_name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email,' . $id. ',id',
                'phone_num' => 'required|integer|digits:10|unique:users,phone_num,' . $id. ',id',
            ]);

            // Check if the authenticated user is authorized to update this album
            $currentUser = auth()->user();
            if ($id !== $currentUser->id) {
                return response()->json([
                    'message' => 'Unauthorized',
                    'is_success' => false,
                ], Response::HTTP_FORBIDDEN);
            }
            
            // Handle the new image upload if present
            $newImageUrl = $currentUser->profile_image; // Default to existing image URL
            if ($request->hasFile('profile_image')) {
                // Delete the old image from S3 if it exists
                if ($currentUser->profile_image) {
                    $oldImagePath = parse_url($currentUser->profile_image, PHP_URL_PATH);
                    Storage::disk('s3')->delete(ltrim($oldImagePath, '/'));
                }

                // Upload the new file to S3
                $ext = pathinfo($request->file('profile_image')->getClientOriginalName(), PATHINFO_EXTENSION);
                $imageName = pathinfo($request->file('profile_image')->getClientOriginalName(), PATHINFO_FILENAME);
                $uploadPath = 'profiles/' . $imageName . '_' . Str::random(3) . '.' . $ext;
                Storage::disk('s3')->put($uploadPath, file_get_contents($request->file('profile_image')->getPathname()));

                // Update the image URL
                $newImageUrl = Storage::disk('s3')->url($uploadPath);
            }

            // Update the user
            $currentUser->update([
                'full_name' => $request->full_name,
                'email' => $request->email,
                'phone_num' => $request->phone_num,
                'profile_image' => $newImageUrl,
                'bio' => $request->bio,
            ]);

            return response()->json([
                'message' => 'User updated successfully.',
                'is_success' => true,
                'user'=>$currentUser->toArray()
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
}
