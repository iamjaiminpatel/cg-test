<?php

namespace App\Http\Controllers\Api;

use Exception;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    /**
     * Method to login
     *
     * @param object $request
     * @return string
     */
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            $credentials = $request->only('email', 'password');

            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'message' => 'User credential did not match',
                    'errors' => 'User credential did not match',
                    'is_success' => false
                ], Response::HTTP_UNAUTHORIZED);
            }

            return response()->json([
                'message'=>'Login Successfully',
                'token' => $token,
                'is_success'=>true
            ], Response::HTTP_OK);
        
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'is_success' => false
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (JWTException $e) {
            // Handle JWT exceptions (e.g., token expired, token not provided)
            return response()->json([
                'message' => 'Token is invalid or expired',
                'error' => 'Token is invalid or expired',
                'is_success' => false
            ], 401);
        } catch (Exception $e) {
            // Log the error for debugging purposes
            return handleException($e);
        }
    }

    /**
     * Method to logout
     *
     * @return string
     */
    public function logout()
    {
        try {
            //JWTAuth::invalidate(JWTAuth::getToken());
            auth()->logout();
            return response()->json([
                'message'=>'Logged out successfully',
                'is_success'=>true
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            // Log the error for debugging purposes
            return handleException($e);
        }
    }

     /**
     * Method to refresh token
     *
     * @return string
     */
     public function refresh()
     {
        try {
            $newToken = auth()->refresh();
            return response()->json([
                'message'=>'New access token generated.',
                'token' => $newToken,
                'is_success'=>true
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            // Log the error for debugging purposes
            return handleException($e);
        }
     }
}
