<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class ImageUploadController extends Controller
{
    /**
     * Handle image upload.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:20480',
        ]);

        if (!$request->hasFile('image')) {
            return response()->json(['error' => 'No image provided'], 400);
        }

        $image = $request->file('image');
        $originalSize = $image->getSize();
        $filename = Str::random(20) . '.jpg';
        
        try {
            //  Créer le dossier images s'il n'existe pas
    $imagesPath = storage_path('app/public/images');
    if (!file_exists($imagesPath)) {
        mkdir($imagesPath, 0755, true);
    }
            //  Charger l'image avec Intervention
            $img = Image::make($image->getRealPath());
            
            //  Redimensionner si > 1200px de large
            if ($img->width() > 1200) {
                $img->resize(1200, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }
            
            //  Encoder en JPEG avec qualité 80%
            $encodedImage = $img->encode('jpg', 80);
            
            //  Sauvegarder dans storage/app/public/images/
            $path = 'images/' . $filename;
            Storage::disk('public')->put($path, $encodedImage);
            
            //  Récupérer la taille finale
            $finalSize = Storage::disk('public')->size($path);
            
            return response()->json([
                'message' => 'Image uploaded and optimized successfully',
                'path' => $path,
                'url' => '/storage/' . $path,
                'size' => $finalSize,
                'original_size' => $originalSize,
            ], 201);
            
        } catch (\Exception $e) {
            \Log::error('Image upload error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Image processing failed: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
}


    /**
     * Delete an uploaded image.
     */
    public function delete(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        $path = $request->input('path');

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
            return response()->json(['message' => 'Image deleted successfully']);
        }

        return response()->json(['error' => 'Image not found'], 404);
    }
}

