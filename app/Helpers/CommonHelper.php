<?php

use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Method to handler exception
 *
 * @param Exception object $e
 * @param str $contrext
 * @return string
 */
if (!function_exists('handleException')) {
    function handleException(Exception $e, string $context = 'An error occurred')
    {
        // Log the error
        if (env('APP_ENV') === 'production') {
            Log::error("{$context}: " . $e->getMessage());
        }

        return response()->json([
            'message' => 'Something went wrong. Please try again later.',
            'error' => (env('APP_ENV') === 'production') ? 'Something went wrong. Please try again later.' : $e->getMessage(),
            'is_success' => false,
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}