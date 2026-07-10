<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UploadController extends Controller
{
    /**
     * Convert an uploaded file to a base64 data URI and return it.
     * The data URI is stored directly in the database, so images persist
     * independently of the filesystem and any Git operations.
     */
    public function upload(Request $request)
    {
        try {
            $files = [];
            if ($request->hasFile('image')) {
                $f     = $request->file('image');
                $files = is_array($f) ? $f : [$f];
            }
            if ($request->hasFile('images')) {
                $f     = $request->file('images');
                $files = array_merge($files, is_array($f) ? $f : [$f]);
            }

            if (count($files) === 0) {
                return response()->json(['message' => 'No image files were uploaded.'], 400);
            }

            $uploads = [];
            foreach ($files as $file) {
                $mimeType  = $file->getMimeType();
                $imageData = base64_encode(file_get_contents($file->getRealPath()));
                $dataUri   = "data:{$mimeType};base64,{$imageData}";

                $uploads[] = [
                    'url'  => $dataUri,
                    'name' => $file->getClientOriginalName(),
                ];
            }

            if (count($uploads) === 1) {
                return response()->json([
                    'message' => 'Image uploaded successfully.',
                    'url'     => $uploads[0]['url'],
                    'file'    => $uploads[0],
                ]);
            }

            return response()->json([
                'message' => count($uploads) . ' images uploaded successfully.',
                'files'   => $uploads,
                'urls'    => array_column($uploads, 'url'),
            ]);

        } catch (\Exception $error) {
            return response()->json(['message' => $error->getMessage() ?: 'Upload failed'], 500);
        }
    }
}
