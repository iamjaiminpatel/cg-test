<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class ResetPasswordController extends Controller
{

    /**
     * Method to send reset password link
     *
     * @param object $request
     * @return string
     */
    public function sendResetLink(Request $request)
    {
        try {
            $request->validate(['email' => 'required|email|exists:users,email']);
          
            $reset = DB::table('password_reset_tokens')->where('email', $request->email)->first();
            
            $token = Str::random(6);
            if ($reset && now()->diffInHours($reset->created_at) < 25) {
                $token = $reset->token;
            }

            if (!$reset){
                DB::table('password_reset_tokens')->insert([
                    'email' => $request->email,
                    'token' => $token,
                    'created_at' => now(),
                ]);
            } else {
                DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->update([
                    'token' => $token,
                    'created_at' => now(),
                ]);
            }

            
            // Send email
            Mail::send('emails.password_reset', ['token' => $token], function ($message) use ($request) {
                $message->to($request->email);
                $message->subject('Password Reset Request');
            });
            return response()->json([
                'message' => 'Reset link sent to your email.',
                'is_success'=>true
            ],Response::HTTP_OK);
            
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'is_success' => false
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }  catch (Exception $e) {
            // Log the error for debugging purposes
            return handleException($e);
        }
    }

    /**
     * Method to reset password
     *
     * @param object $request
     * @return string
     */
    public function resetPassword(Request $request)
    {
        try {
            $request->validate([
                'token' => 'required',
                'password' => 'required|min:8|confirmed',
            ]);

            $reset = DB::table('password_reset_tokens')->where('token', $request->token)->first();

            if (!$reset || now()->diffInHours($reset->created_at) > 24) {
                return response()->json(['message' => 'Invalid or expired token.'], 400);
            }

            $user = User::where('email', $reset->email)->first();
            $user->update(['password' => Hash::make($request->password)]);

            DB::table('password_reset_tokens')->where('email', $reset->email)->delete();

            return response()->json([
                'message' => 'Password reset successfully.',
                'is_success'=>true
            ],Response::HTTP_OK);
            
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'is_success' => false
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }  catch (Exception $e) {
            // Log the error for debugging purposes
            return handleException($e);
        }
    }
}
