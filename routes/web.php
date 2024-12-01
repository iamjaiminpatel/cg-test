<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/api/documentation', function () {
    $path = public_path('swagger.json'); // Pointing to the public folder
    if (!File::exists($path)) {
        return response()->json(['error' => 'Swagger file not found.'], 404);
    }
    
    $swaggerJson = File::get($path);
    // Replace the host with the dynamic value from the .env file
    $swaggerJson = str_replace(
        '"host": "127.0.0.1:8003"',
        '"host": "' . env('API_HOST', '127.0.0.1:8003') . '"',
        $swaggerJson
    );
    return response($swaggerJson, 200)->header('Content-Type', 'application/json');
});

Route::get('/swagger-ui', function () {
    return view('swagger-ui');
});
