<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManagerStatic as Image;

class ImageUploadController extends Controller
{
    /**
     * Handle image upload.
     */
    public function upload(Request $request)
    {
        $request->validate([
            // Limite de validation à 10MB (10240 Ko)
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:10240',
        ]);

        if (!$request->hasFile('image')) {
            return response()->json(['error' => 'No image provided'], 400);
        }

        $imageFile = $request->file('image');
        $filename = Str::random(20) . '.jpg';
        $relativePath = 'images/' . $filename;

        // Optimisation backend : redimensionnement max 1200px + compression qualité 80%
        $image = Image::make($imageFile)
            ->orientate()
            ->resize(1200, 1200, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })
            ->encode('jpg', 80);

        Storage::disk('public')->put($relativePath, $image->__toString());
        
        return response()->json([
            'message' => 'Image uploaded successfully',
            'path' => $relativePath,
            'url' => '/storage/' . $relativePath,
            'size' => Storage::disk('public')->size($relativePath),
        ], 201);
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

