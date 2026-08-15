<?php

namespace App\Http\Controllers;

use App\Services\AiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AiController extends Controller
{
    /**
     * AI Virtual Stylist & Product Concierge Chat.
     */
    public function chatStylist(Request $request)
    {
        $message = trim($request->input('message', ''));
        if (!$message) {
            return response()->json(['message' => 'Message cannot be empty'], 400);
        }

        $history = $request->input('history', []);
        $response = AiService::chatStylist($message, is_array($history) ? $history : []);

        return response()->json($response);
    }

    /**
     * AI Smart Sizing & Tailoring Recommendation.
     */
    public function recommendSize(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'height' => 'required|numeric|min:100|max:250',
            'weight' => 'required|numeric|min:30|max:200',
            'build'  => 'nullable|string|in:slim,regular,athletic,broad,plus',
            'fit'    => 'nullable|string|in:slim,regular,comfort,traditional',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $height = (float) $request->input('height');
        $weight = (float) $request->input('weight');
        $build  = $request->input('build', 'regular');
        $fit    = $request->input('fit', 'regular');

        $result = AiService::recommendSize($height, $weight, $build, $fit);

        return response()->json($result);
    }

    /**
     * AI Seller Product Listing & Description Generator.
     */
    public function generateSellerListing(Request $request)
    {
        $params = [
            'fabric'     => $request->input('fabric', 'Jusi Silk'),
            'embroidery' => $request->input('embroidery', 'Calado Hand Embroidery'),
            'category'   => $request->input('category', 'Barong Tagalog'),
            'theme'      => $request->input('theme', 'Formal & Wedding'),
            'collar'     => $request->input('collar', 'Chinese / Mandarin Collar'),
        ];

        $result = AiService::generateProductListing($params);

        return response()->json($result);
    }

    /**
     * AI Real-time Password Security & Entropy Advisor.
     */
    public function analyzePassword(Request $request)
    {
        $password = (string) $request->input('password', '');
        $result = AiService::analyzePassword($password);

        return response()->json($result);
    }

    /**
     * Check if Payment Reference is duplicate or valid format.
     */
    public function checkPaymentReference(Request $request)
    {
        $ref = trim((string) $request->input('reference', ''));
        $method = trim((string) $request->input('method', 'GCash'));

        if (!$ref) {
            return response()->json(['is_valid' => false, 'message' => 'Reference number is required']);
        }

        // Format checks
        if (preg_match('/^(\d)\1+$/', $ref)) {
            return response()->json([
                'is_valid' => false,
                'is_duplicate' => false,
                'message' => '❌ Invalid payment reference: Repeated digit sequences are not allowed.'
            ]);
        }

        $isGcash = strcasecmp($method, 'GCash') === 0;
        if ($isGcash && !preg_match('/^\d{13}$/', $ref)) {
            return response()->json([
                'is_valid' => false,
                'is_duplicate' => false,
                'message' => '❌ Reference number must be exactly 13 digits.'
            ]);
        } elseif (!$isGcash && !preg_match('/^\d{12}$/', $ref)) {
            return response()->json([
                'is_valid' => false,
                'is_duplicate' => false,
                'message' => '❌ Reference number must be exactly 12 digits.'
            ]);
        }

        // Duplicate check in database
        $dupCheck = AiService::isDuplicateReference($ref);
        if ($dupCheck['is_duplicate']) {
            return response()->json([
                'is_valid' => false,
                'is_duplicate' => true,
                'message' => $dupCheck['message']
            ]);
        }

        return response()->json([
            'is_valid' => true,
            'is_duplicate' => false,
            'message' => "✓ Valid and unique {$method} reference number."
        ]);
    }

    /**
     * AI Verification of Uploaded Receipt Image.
     */
    public function verifyReceipt(Request $request)
    {
        $ref = trim((string) $request->input('reference', ''));
        $method = trim((string) $request->input('method', 'GCash'));
        $totalAmount = (float) $request->input('amount', 0);

        if (!$request->hasFile('receipt')) {
            return response()->json([
                'is_receipt' => false,
                'message' => 'No receipt image uploaded.'
            ], 400);
        }

        $file = $request->file('receipt');
        $tempPath = $file->getRealPath();

        $result = AiService::verifyReceipt($tempPath, $ref, $method, $totalAmount);

        return response()->json($result);
    }

    /**
     * AI Policy Assistant (Generator, Polisher & Tagalog Translator).
     */
    public function assistPolicy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type'   => 'required|string|in:cancellation,refund',
            'action' => 'required|string|in:generate,improve,translate',
            'draft'  => 'nullable|string|max:2000',
            'tone'   => 'nullable|string|in:standard,strict,flexible',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $type   = $request->input('type');
        $action = $request->input('action');
        $draft  = $request->input('draft');
        $tone   = $request->input('tone', 'standard');

        $result = AiService::assistPolicy($type, $draft, $action, $tone);

        return response()->json($result);
    }
}
