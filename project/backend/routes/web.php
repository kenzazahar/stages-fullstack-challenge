<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// ✅ Serve storage files (images, etc.)
Route::get('/storage/{path}', function ($path) {
    $filePath = storage_path('app/public/' . $path);
    
    if (!file_exists($filePath) || !is_file($filePath)) {
        abort(404);
    }
    
    // Déterminer le type MIME
    $mimeType = mime_content_type($filePath);
    
    return response()->file($filePath, [
        'Content-Type' => $mimeType,
    ]);
})->where('path', '.*');
