<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    /**
     * General and report evidence image upload.
     */
    public function uploadImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image'            => 'nullable|file|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'images.*'         => 'nullable|file|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'file'             => 'nullable|file|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'evidence'         => 'nullable|file|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'evidence_files.*' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error: ' . $validator->errors()->first(),
                'errors'  => $validator->errors()
            ], 422);
        }

        $files = [];
        if ($request->hasFile('image')) {
            $files[] = $request->file('image');
        }
        if ($request->hasFile('file')) {
            $files[] = $request->file('file');
        }
        if ($request->hasFile('evidence')) {
            $files[] = $request->file('evidence');
        }
        if ($request->hasFile('images')) {
            $imgs = $request->file('images');
            if (is_array($imgs)) {
                $files = array_merge($files, $imgs);
            } else {
                $files[] = $imgs;
            }
        }
        if ($request->hasFile('evidence_files')) {
            $evs = $request->file('evidence_files');
            if (is_array($evs)) {
                $files = array_merge($files, $evs);
            } else {
                $files[] = $evs;
            }
        }

        if (empty($files)) {
            return response()->json(['message' => 'No image files were uploaded.'], 400);
        }

        $folder = $request->input('folder', $request->input('type', 'reports'));
        $safeFolder = in_array($folder, ['reports', 'products', 'profiles', 'proofs', 'misc']) ? $folder : 'reports';

        $destination = public_path('uploads/' . $safeFolder);
        if (!file_exists($destination)) {
            mkdir($destination, 0755, true);
        }

        $results = [];
        foreach ($files as $file) {
            $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            // Ensure extension is in safe image whitelist
            if (!in_array($extension, ['jpeg', 'png', 'jpg', 'gif', 'webp'])) {
                $extension = 'jpg';
            }

            $filename = time() . '_' . Str::random(16) . '.' . $extension;
            $file->move($destination, $filename);
            
            $url = '/uploads/' . $safeFolder . '/' . $filename;
            $results[] = [
                'url'  => $url,
                'name' => $file->getClientOriginalName()
            ];
        }

        if (count($results) === 1) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Image uploaded successfully.',
                'url'     => $results[0]['url'],
                'file'    => $results[0]
            ]);
        }

        return response()->json([
            'status'  => 'success',
            'message' => count($results) . ' images uploaded successfully.',
            'files'   => $results,
            'urls'    => array_column($results, 'url')
        ]);
    }
}
