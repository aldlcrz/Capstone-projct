<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    /**
     * General image upload.
     */
    public function uploadImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 400);
        }

        $files = [];
        if ($request->hasFile('image')) {
            $files[] = $request->file('image');
        }
        if ($request->hasFile('images')) {
            $files = array_merge($files, $request->file('images'));
        }

        if (empty($files)) {
            return response()->json(['message' => 'No image files were uploaded.'], 400);
        }

        $results = [];
        foreach ($files as $file) {
            $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/misc'), $filename);
            
            $url = $request->getSchemeAndHttpHost() . '/uploads/misc/' . $filename;
            $results[] = [
                'url' => $url,
                'name' => $file->getClientOriginalName()
            ];
        }

        if (count($results) === 1) {
            return response()->json([
                'message' => 'Image uploaded successfully.',
                'url' => $results[0]['url'],
                'file' => $results[0]
            ]);
        }

        return response()->json([
            'message' => count($results) . ' images uploaded successfully.',
            'files' => $results,
            'urls' => array_column($results, 'url')
        ]);
    }
}
