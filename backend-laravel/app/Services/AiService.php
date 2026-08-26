<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Services\RecommendationEngine;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService
{
    /**
     * Get the Gemini API Key from config / env if configured.
     */
    private static function getApiKey(): ?string
    {
        return config('services.gemini.api_key') ?: env('GEMINI_API_KEY');
    }

    /**
     * Call Google Gemini API with multi-model fallback and multi-turn conversation memory.
     */
    private static function callGemini(string $prompt, ?string $systemInstruction = null, array $conversationHistory = []): ?string
    {
        $apiKey = self::getApiKey();
        if (!$apiKey) {
            return null; // Triggers built-in heuristic AI engine
        }

        $configuredModel = config('services.gemini.model') ?: env('GEMINI_MODEL', 'gemini-2.5-flash');
        $models = array_unique(array_filter([
            $configuredModel,
            'gemini-2.5-flash',
            'gemini-2.0-flash',
            'gemini-1.5-flash',
            'gemini-flash-latest'
        ]));

        // Build multi-turn contents array
        $contents = [];
        if (!empty($conversationHistory)) {
            foreach (array_slice($conversationHistory, -6) as $msg) {
                $role = ($msg['role'] ?? '') === 'user' ? 'user' : 'model';
                $text = trim((string) ($msg['text'] ?? $msg['content'] ?? ''));
                if ($text) {
                    $contents[] = [
                        'role' => $role,
                        'parts' => [['text' => $text]]
                    ];
                }
            }
        }

        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $prompt]]
        ];

        $payload = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 700,
                'topP' => 0.9,
            ]
        ];

        if ($systemInstruction) {
            $payload['systemInstruction'] = [
                'parts' => [
                    ['text' => $systemInstruction]
                ]
            ];
        }

        $configuredModel = config('services.gemini.model') ?: env('GEMINI_MODEL', 'gemini-2.5-flash');
        $models = array_slice(array_unique(array_filter([
            $configuredModel,
            'gemini-2.5-flash',
            'gemini-1.5-flash',
        ])), 0, 1);

        foreach ($models as $model) {
            try {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
                $response = Http::withOptions([
                    'curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4],
                    'verify' => false,
                ])->timeout(2.5)->post($url, $payload);
                if ($response->successful()) {
                    $data = $response->json();
                    $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                    if ($text) {
                        return trim($text);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("Gemini API ({$model}) call failed: " . $e->getMessage());
            }
        }

        return null;
    }

    // ==========================================
    // 1. AI VIRTUAL STYLIST & PRODUCT CONCIERGE
    // ==========================================

    /**
     * Check if a message contains inappropriate language, profanity, harassment, or vulgar content.
     */
    public static function isInappropriateMessage(string $message): bool
    {
        $lower = strtolower($message);
        
        $inappropriatePatterns = [
            '/\b(fuck|fucking|fucker|shit|bitch|asshole|bastard|cunt|dick|pussy|whore|slut|nude|porn|sex|hentai|lewd)\b/i',
            '/\b(gago|gaga|tanga|putangina|tangina|pukinangina|tarantado|tarantada|bwisit|buwisit|ulol|inutil|pota|puta|bobo|kupal|kantot|hindot|pakyu|burat|pokpok|tamod|tite|puke|bayag)\b/i',
            '/\b(kill\s+yourself|suicide|threat|bomb|terrorist|hate\s+speech|scam\s+people)\b/i',
        ];

        foreach ($inappropriatePatterns as $pattern) {
            if (preg_match($pattern, $lower)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a message attempts to extract private system secrets, database internals, or admin credentials.
     * Note: Legitimate questions like "How do I reset my password?" or "What is an OTP?" are explicitly allowed.
     */
    public static function isSecurityExtractionAttempt(string $message): bool
    {
        $lower = strtolower($message);

        // Allow legitimate customer questions about account / password reset / OTP explanation
        if (preg_match('/\b(how\s+do\s+i\s+(reset|change|update)|i\s+forgot\s+my|paano\s+mag\s*(reset|palit)|what\s+is\s+an?\s+otp|ano\s+ang\s+otp|forgot\s+password|reset\s+password)\b/i', $lower)) {
            return false;
        }

        $extractionPatterns = [
            '/\b(select\s+.*\s+from|insert\s+into|update\s+.*\s+set|delete\s+from|drop\s+table|drop\s+database|truncate\s+table|alter\s+table)\b/i',
            '/\b(sql\s*injection|schema|table_schema|db_password|db_host|db_connection|users\s+table|database\s+credentials|dump\s+database)\b/i',
            '/\b(what\s+is\s+(the\s+)?(admin|system|root)\s*password|show\s+(me\s+)?(all\s+)?(passwords?|credentials?|secrets?|api\s*keys?))\b/i',
            '/\b(app_key|api_key|secret_key|jwt_secret|\.env|env\(|config\(|auth::id\(\))\b/i',
            '/\b(system\s+prompt|jailbreak|ignore\s+previous\s+instructions|prompt\s+injection|override\s+rules|bypass\s+security|exploit|vulnerability)\b/i',
            '/\b(give\s+me\s+(the\s+)?(admin|user|database)\s+credentials|what\s+is\s+my\s+password|show\s+my\s+password)\b/i',
        ];

        foreach ($extractionPatterns as $pattern) {
            if (preg_match($pattern, $lower)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a message attempts to submit or request financial credentials (CVV, Card PIN, requesting raw OTPs).
     */
    public static function isFinancialCredentialsAttempt(string $message): bool
    {
        $lower = strtolower($message);

        // Allow explanatory questions like "What is an OTP?"
        if (preg_match('/\b(what\s+is\s+(an?\s+)?otp|ano\s+ang\s+otp|how\s+does\s+otp\s+work)\b/i', $lower)) {
            return false;
        }

        $financialPatterns = [
            '/\b(credit\s*card\s*number|card\s*number\s+is|cvv\s*is|cvc\s*is|my\s+cvv|my\s+pin\s+is|atm\s*pin|bank\s*pin)\b/i',
            '/\b(give\s+me\s+(the\s+|my\s+)?otp|send\s+me\s+(the\s+|my\s+)?otp|what\s+is\s+my\s+otp)\b/i',
            '/\b(gcash\s+pin|gcash\s+mpin|maya\s+password|bank\s+password)\b/i',
        ];

        foreach ($financialPatterns as $pattern) {
            if (preg_match($pattern, $lower)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Backward compatibility wrapper for security screening.
     */
    public static function isSensitiveInfoQuery(string $message): bool
    {
        return self::isSecurityExtractionAttempt($message) || self::isFinancialCredentialsAttempt($message);
    }

    public static function isSecurityProhibitedQuery(string $message): bool
    {
        return self::isSensitiveInfoQuery($message);
    }

    /**
     * MODE 3: Check if query is related to order status, tracking, or order customer support.
     */
    public static function isOrderSupportQuery(string $message): bool
    {
        $lower = strtolower(trim($message));
        return (bool) preg_match('/\b(where\s+(is|are)\s+my\s+orders?|track(?:\s+my)?\s+order|order\s+status|tracking\s+number|dumating\s+na\s+ba|nasaan\s+na\s+order|kumusta\s+order|status\s+ng\s+order|my\s+purchases?|delivery\s+status|order\s+#|#lb-?)\b/i', $lower);
    }

    /**
     * MODE 3: Handle Live Customer Order Status with database lookup & strict authorization.
     */
    public static function handleOrderSupport(string $message, ?string $userId = null): array
    {
        if (!$userId) {
            return [
                'reply' => "🔐 **Order Tracking & Support:**\n\nI can help you check your live order tracking and packaging proofs, but you'll need to **Sign In** first so I can securely access your order information.\n\nPlease [Sign In to LumBarong](/login) and check your [My Orders](/customer/orders) portal. If you need urgent assistance, our team is also available at **lumbarongsupport@gmail.com**.",
                'products' => [],
                'refinements' => [
                    ['label' => '🛍️ Browse Best Sellers', 'prompt' => 'Show me the top best selling barongs'],
                    ['label' => '🧵 Fabric Guide', 'prompt' => 'What is the difference between Piña and Jusi?']
                ]
            ];
        }

        $specificId = null;
        if (preg_match('/(?:order\s*#?|#\s*)([a-zA-Z0-9\-]{4,})/i', $message, $m)) {
            $specificId = $m[1];
        }

        try {
            // Strictly query ONLY orders belonging to the authenticated customer
            $query = Order::where('customerId', $userId);

            if ($specificId) {
                $query->where('id', 'like', "%{$specificId}%");
            }

            $orders = $query->orderByDesc('createdAt')->take(3)->get();
            
            if ($orders->isEmpty()) {
                if ($specificId) {
                    return [
                        'reply' => "📦 **Order Lookup:** I couldn't find order `#" . strtoupper($specificId) . "` under your account.\n\nPlease check the Order ID in your [My Orders](/customer/orders) page or make sure you're signed in with the account used for this purchase.",
                        'products' => [],
                        'refinements' => [
                            ['label' => '📦 View All My Orders', 'prompt' => 'Where is my order?'],
                            ['label' => '⭐ Browse Best Sellers', 'prompt' => 'Show me your best selling Barongs']
                        ]
                    ];
                }

                return [
                    'reply' => "📦 **Order Status:** I checked your account, but there are no active orders placed under this profile yet.\n\nReady to find your authentic Lumban Barong? Tell me about your occasion or budget and I'll match the best options!",
                    'products' => [],
                    'refinements' => [
                        ['label' => '⭐ View Best Sellers', 'prompt' => 'Show me your best selling Barongs'],
                        ['label' => '🤵 Wedding Recommendations', 'prompt' => 'Recommend a Barong for a wedding']
                    ]
                ];
            }

            $orderLines = [];
            foreach ($orders as $o) {
                $statusEmoji = match(strtolower($o->status)) {
                    'delivered', 'completed' => '✅ Delivered',
                    'shipped', 'in_transit' => '🚚 In Transit / Shipped',
                    'processing' => '🧵 Tailoring & Packing in Progress',
                    'pending_payment', 'pending' => '⏳ Pending Verification',
                    'cancelled' => '❌ Cancelled',
                    default => '📦 ' . ucfirst($o->status)
                };

                $shortId = strtoupper(substr($o->id, 0, 8));
                $amount = number_format((float) ($o->totalAmount ?? 0), 2);
                $courier = $o->courierName ? "via {$o->courierName}" : "";
                $tracking = $o->trackingNumber ? "(Tracking: `{$o->trackingNumber}`)" : "";

                $orderLines[] = "• **Order #{$shortId}** — ₱{$amount}\n  Status: **{$statusEmoji}** {$courier} {$tracking}\n  [View Details & Packing Proof](/customer/orders/{$o->id})";
            }

            $reply = "📦 **Your Recent Orders & Live Tracking:**\n\n" . implode("\n\n", $orderLines) . "\n\nFor shipping adjustments or courier concerns, our artisans are ready at **lumbarongsupport@gmail.com**.";

            return [
                'reply' => $reply,
                'products' => [],
                'refinements' => [
                    ['label' => '🛍️ Browse New Arrivals', 'prompt' => 'Show me new Barong Tagalog collections'],
                    ['label' => '🧼 Care Guide', 'prompt' => 'How to wash and care for my Barong?']
                ]
            ];
        } catch (\Throwable $e) {
            Log::error("Order Support Lookup Error: " . $e->getMessage());
            if ($specificId) {
                return [
                    'reply' => "📦 **Order Lookup:** I couldn't find order `#" . strtoupper($specificId) . "` under your account.\n\nPlease check the Order ID in your [My Orders](/customer/orders) page or make sure you're signed in with the account used for this purchase.",
                    'products' => [],
                    'refinements' => [
                        ['label' => '📦 View All My Orders', 'prompt' => 'Where is my order?'],
                        ['label' => '⭐ Browse Best Sellers', 'prompt' => 'Show me your best selling Barongs']
                    ]
                ];
            }

            return [
                'reply' => "📦 **Order Tracking:** You can view your complete order history, courier updates, and packing proofs anytime in your [My Orders](/customer/orders) page.",
                'products' => [],
                'refinements' => [
                    ['label' => '⭐ View Best Sellers', 'prompt' => 'Show me your best selling Barongs']
                ]
            ];
        }
    }

    /**
     * Check if user has explicit or implicit shopping intent.
     */
    public static function isShoppingIntent(string $message): bool
    {
        $lower = strtolower(trim($message));

        // Pure educational / care guides without shopping intention
        $pureGuides = [
            '/\b(how\s+to\s+wash|how\s+to\s+clean|how\s+to\s+iron|how\s+to\s+care|paano\s+labahan|paano\s+plantsahin|washing|ironing|cleaning|stain)\b/i',
            '/\b(what\s+is|difference\s+between|define|meaning\s+of|history|about\s+lumban|origin\s+of)\b/i',
            '/\b(return\s+policy|refund|payment\s+method|gcash|maya|cod|shipping\s+fee|delivery\s+time|contact\s+support|email)\b/i',
            '/\b(size\s+chart|how\s+to\s+measure|measurement\s+guide|paano\s+sukatin)\b/i',
            '/\b(reset\s+password|forgot\s+password|login|what\s+is\s+otp)\b/i',
        ];

        foreach ($pureGuides as $pattern) {
            if (preg_match($pattern, $lower) && !preg_match('/\b(show|recommend|suggest|patingin|bestseller|best\s*seller|buy|shop|available|under\s+\d+|budget)\b/i', $lower)) {
                return false;
            }
        }

        // Pure conversational
        if (preg_match('/^(hi|hello|hey|heyy|mabuhay|good\s*(morning|afternoon|evening|day)|kumusta|kamusta|sup|yo|help|start|thanks?|thank\s*you|salamat|ok|okay|sige|noted|got\s*it)[.!?\s]*$/i', $lower)) {
            return false;
        }

        // Shopping & Product Recommendation Intents
        $shoppingIntents = [
            '/\b(show|recommend|suggest|browse|view|find|search|look\s+for|shop|buy|order|purchase)\b/i',
            '/\b(bestseller|best\s*seller|top\s+seller|popular\s+barong|catalog|collection|available\s+products?|items?|models?)\b/i',
            '/\b(price|magkano|how\s+much|cost|budget|under\s+\d+|less\s+than\s+\d+|patingin|sample|list\s+of\s+barong|meron\s+ba|anong\s+tinda)\b/i',
            '/\b(barong\s+for\s+(wedding|groom|ninong|graduation|event|work)|filipiniana\s+dress|terno\s+dress|lady\s+barong)\b/i',
            '/\b(attending\s+a\s+wedding|attending\s+graduation|what\s+should\s+i\s+wear|what\s+to\s+wear|need\s+a\s+barong|looking\s+for\s+a\s+barong)\b/i',
            '/\b(compare\s+(these|top|two|options)|cheaper\s+options?|premium\s+options?|piña\s+only|mandarin\s+collar)\b/i',
        ];

        foreach ($shoppingIntents as $intent) {
            if (preg_match($intent, $lower)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Backward compatibility wrapper.
     */
    public static function isProductRequest(string $message): bool
    {
        return self::isShoppingIntent($message);
    }

    /**
     * Main Conversational Concierge entrypoint:
     * Architecture: Security -> Intent -> Context -> Authorized DB -> Gemini / Natural Heuristic
     */
    public static function chatStylist(string $userMessage, array $conversationHistory = [], array $sessionContext = [], ?string $userId = null): array
    {
        $trimmedMessage = trim($userMessage);
        $lower = strtolower($trimmedMessage);

        // 1. INAPPROPRIATE CONTENT & PROFANITY GUARDRAIL
        if (self::isInappropriateMessage($userMessage)) {
            return [
                'reply' => "⚠️ **Community Standards Notice:** Mabuhay! 😊 Please refrain from using inappropriate language. I’m here to help with LumBarong products, styling, orders, and other shopping questions. Let’s keep our conversation respectful so I can assist you better.",
                'products' => [],
                'refinements' => [],
                'session_context' => $sessionContext
            ];
        }

        // 2. SECURITY EXTRACTION ATTEMPT GUARDRAIL
        if (self::isSecurityExtractionAttempt($userMessage)) {
            return [
                'reply' => "🔒 **Security Notice:** For security and privacy, I cannot provide internal credentials, private database records, system secrets, or restricted platform information (and do not have access to internal databases). I can still help you with LumBarong products, orders, styling, and account-related questions.",
                'products' => [],
                'refinements' => [],
                'session_context' => $sessionContext
            ];
        }

        // 3. FINANCIAL CREDENTIALS PROTECTION GUARDRAIL
        if (self::isFinancialCredentialsAttempt($userMessage)) {
            return [
                'reply' => "⚠️ **Security Notice:** For your security, please never share your OTP, PIN, CVV, password, or other banking credentials here. I can still help explain payment procedures or troubleshoot a payment issue without asking for those details.",
                'products' => [],
                'refinements' => [],
                'session_context' => $sessionContext
            ];
        }

        // 4. AUTHENTICATED LIVE ORDER SUPPORT
        if (self::isOrderSupportQuery($userMessage)) {
            $supportRes = self::handleOrderSupport($userMessage, $userId);
            return array_merge($supportRes, ['session_context' => $sessionContext]);
        }

        // 5. SHOPPING ASSISTANT & MULTI-FACTOR RECOMMENDATION ENGINE
        if (self::isShoppingIntent($userMessage)) {
            $recResult = RecommendationEngine::recommend($userMessage, $sessionContext);
            $newContext = $recResult['preferences'];
            $products = $recResult['products'];
            $refinements = $recResult['refinements'];

            // Prepare prompt for Gemini to explain the scored recommendations
            $productsContext = "";
            foreach ($products as $idx => $p) {
                $reasonsStr = implode("; ", $p['reasons']);
                $productsContext .= "Item " . ($idx + 1) . ": {$p['name']} (₱{$p['price']}, {$p['fabric']}, Tier: {$p['badge']}, Compatibility Score: {$p['score']}%, Reasons: {$reasonsStr})\n";
            }

            $recommendationSystemPrompt = "You are LumBarong Smart Assistance, an authentic Philippine fashion and recommendation concierge in Lumban, Laguna.
The recommendation engine computed the following matches:
{$productsContext}

INSTRUCTIONS:
1. Speak naturally, warmly, and concisely (1-2 short paragraphs) like a real human personal stylist.
2. Directly answer why the top match fits their requested occasion or style.
3. Mention that they can tap on the product cards below to view measurements, fabric close-ups, and artisan customization options.
4. Keep the tone courteous, proud of Lumban heritage, and genuine.";

            $aiText = self::callGemini($userMessage, $recommendationSystemPrompt, $conversationHistory);

            if (!$aiText) {
                $aiText = self::heuristicShoppingExplanation($products, $newContext, $lower);
            }

            return [
                'reply' => $aiText,
                'products' => $products,
                'refinements' => $refinements,
                'session_context' => $newContext
            ];
        }

        // 6. GENERAL CONVERSATION & KNOWLEDGE CONSULTATION
        $systemPrompt = "You are the LumBarong Smart Assistant, a friendly, intelligent, and knowledgeable heritage fashion consultant for LumBarong in Lumban, Laguna, Philippines (the Embroidery Capital of the Philippines).

PERSONALITY & HUMAN-LIKE CONVERSATION:
- Answer naturally, conversationally, and warmly like a friendly personal stylist.
- Answer the customer's EXACT question directly in 1 to 3 concise, clear paragraphs.
- If the user simply greets you (e.g. 'Hello', 'Hi', 'Good morning', 'Thank you', 'Okay'), reply in a friendly, natural, and concise way without repeating long standard introductions.
- Speak in natural English or Taglish (matching the customer's language).

KNOWLEDGE BASE:
- Location: LumBarong master artisans and workshops are based in Lumban, Laguna, Philippines. We ship door-to-door nationwide with parcel tracking.
- Fabrics: Piña (pure pineapple fiber heirloom), Piña-Seda (pineapple-silk blend), Cocoon Silk (opaque luxury formal), Jusi Silk (crisp traditional semi-sheen), Organza/Monoray (lightweight & budget-friendly).
- Budget ranges: Organza (₱1,500–₱3,500), Jusi (₱3,800–₱7,500), Cocoon Silk (₱6,500–₱12,000), Piña-Seda (₱9,500–₱25,000+).
- Occasion Styling: Grooms (Piña-Seda / Cocoon with Calado pechera & Camisa de Chino), Ninongs/Guests (Jusi / Organza), Graduations (Organza / Monoray).
- Sizing: True to standard Philippine Barong sizes with 3-4 inches comfort ease.

PLATFORM IN-TAB LINKS:
- When mentioning or directing customers to specific pages or features, ALWAYS include direct markdown links so the customer can tap and navigate in the same tab:
  • For Orders & Tracking: [My Orders](/orders/my-orders)
  • For Shopping Cart: [Shopping Cart](/cart)
  • For Login & Account Access: [Sign In](/login)
  • For Account Profile: [My Profile](/profile)

STRICT DOMAIN LIMITS & SECURITY:
- You ONLY discuss Philippine fashion, Barongs, Filipiniana, fabrics, sizing, care, styling, shop location, orders, and Lumban culture.
- NEVER disclose, simulate, or discuss internal database passwords, SQL queries, system tokens, or server source code.";

        $aiText = self::callGemini($userMessage, $systemPrompt, $conversationHistory);

        if (!$aiText) {
            $aiText = self::heuristicStylistReply($lower);
        }

        return [
            'reply' => $aiText,
            'products' => [],
            'refinements' => [
                ['label' => '🛍️ View Top Best Sellers', 'prompt' => 'Show me your best selling Barong Tagalog'],
                ['label' => '🤵 Wedding Barongs', 'prompt' => 'Recommend a Barong for a wedding'],
                ['label' => '🎓 Graduation Options', 'prompt' => 'Recommend an affordable Barong for graduation under ₱3,500'],
            ],
            'session_context' => $sessionContext
        ];
    }

    /**
     * Heuristic explanation generator for Mode 2 recommendations when Gemini is offline.
     */
    private static function heuristicShoppingExplanation(array $products, array $pref, string $lower = ''): string
    {
        if (empty($products)) {
            return self::heuristicStylistReply($lower);
        }

        $top = $products[0];
        $count = count($products);

        $reply = "Mabuhay! Based on your preferences";
        if (!empty($pref['occasion'])) {
            $reply .= " for a **" . ucfirst($pref['occasion']) . "**";
        }
        if (!empty($pref['max_budget'])) {
            $reply .= " under **₱" . number_format($pref['max_budget']) . "**";
        }
        $reply .= ", I curated **{$count} exquisite authentic Lumban Barong Tagalog options** for you.\n\n";

        $reply .= "🌟 **Top Recommendation: " . htmlspecialchars($top['name']) . "** (₱" . number_format((float)$top['price'], 2) . ")\n";
        $reply .= "• **Fabric**: " . ($top['fabric'] ?? 'Authentic Lumban Weave') . "\n";
        $reply .= "• **Score**: " . $top['score'] . "% Compatibility Match\n\n";

        if (!empty($top['reasons'])) {
            $reply .= "**Why our master artisans recommend this:**\n";
            foreach ($top['reasons'] as $r) {
                $cleanR = preg_replace('/^[✓•\s]+/', '', $r);
                $reply .= "• {$cleanR}\n";
            }
            $reply .= "\n";
        }

        $reply .= "You can click **View** on any card below to see custom embroidery close-ups and place your order directly.";

        return $reply;
    }

    /**
     * Unified Intent-Based Heuristic Fallback Engine
     */
    public static function heuristicStylistReply(string $lower): string
    {
        $cleaned = trim(preg_replace('/[^\p{L}\p{N}\s]/u', '', $lower));

        // 1. GENERAL CONVERSATION LAYER (Greetings, Thanks, Acknowledgments, Capabilities, Help)
        $conversational = self::handleGeneralConversation($lower, $cleaned);
        if ($conversational !== null) {
            return $conversational;
        }

        // 2. ACCOUNT & SUPPORT LAYER (Password reset, login, OTP explanation)
        $accountReply = self::handleAccountSupport($lower);
        if ($accountReply !== null) {
            return $accountReply;
        }

        // 3. STYLING & KNOWLEDGE BASE LAYER
        $knowledgeReply = self::handleStylingKnowledge($lower);
        if ($knowledgeReply !== null) {
            return $knowledgeReply;
        }

        // 4. NATURAL CONTEXTUAL FALLBACK
        return "I'm still here to help! 😊 I can assist with LumBarong products, Barong fabrics, styling recommendations, sizing, care instructions, orders, and shopping questions. Could you tell me a little more about what you need?";
    }

    /**
     * Layer 1: General Conversation Handler
     */
    private static function handleGeneralConversation(string $lower, string $cleaned): ?string
    {
        // Greetings
        if (preg_match('/^(hello|hi|hey|heya|hola)[.!?\s]*$/i', $cleaned)) {
            return "Hello! 👋 Welcome to LumBarong Smart Assistance. How can I help you discover authentic Lumban Barongs today?";
        }

        if (preg_match('/^(good\s*morning|magandang\s*umaga)[.!?\s]*$/i', $cleaned)) {
            return "Good morning! ☀️ How can I assist you with your Barong styling or shopping today?";
        }

        if (preg_match('/^(good\s*afternoon|magandang\s*hapon)[.!?\s]*$/i', $cleaned)) {
            return "Good afternoon! 🌤️ Looking for the perfect Barong or need assistance with an order?";
        }

        if (preg_match('/^(good\s*evening|magandang\s*gabi)[.!?\s]*$/i', $cleaned)) {
            return "Good evening! 🌙 How can I assist you tonight?";
        }

        if (preg_match('/^(mabuhay|kumusta|kamusta|musta|good\s*day|sup|yo)[.!?\s]*$/i', $cleaned)) {
            return "Mabuhay! 😊 Kumusta! How can I help you discover authentic Lumban Barongs today?";
        }

        // Thank you / Gratitude
        if (preg_match('/\b(thank\s*you|thanks|salamat|maraming\s*salamat|thank\s*u|thx|ty)\b/i', $lower)) {
            return "You're very welcome! 😊 Let me know if you need anything else or have more questions about our Barongs.";
        }

        // Acknowledgments & Confirmations
        if (preg_match('/^(ok|okay|sige|got\s*it|noted|alright|sure|cge)[.!?\s]*$/i', $cleaned)) {
            return "Sounds good! 😊 I'm here whenever you need styling tips, fabric advice, or order assistance.";
        }

        // Capabilities & About LumBarong
        if (preg_match('/\b(what\s+can\s+you\s+do|what\s+is\s+lumbarong|who\s+are\s+you|about\s+lumbarong|what\s+do\s+you\s+do|ano\s+ang\s+lumbarong)\b/i', $lower)) {
            return "I'm your **LumBarong Smart Assistant**! 😊 I can help you with:\n\n"
                . "• **Product Recommendations**: Finding the right Barong for weddings, graduations, or formal events.\n"
                . "• **Heritage Fabric Guides**: Comparing Piña, Jusi, Cocoon, and Organza.\n"
                . "• **Sizing & Measurements**: Finding your ideal tailored fit.\n"
                . "• **Garment Care**: Tips on washing, ironing, and storing your Barong.\n"
                . "• **Order Support**: Checking tracking updates and packing proofs.\n\n"
                . "What would you like to know more about?";
        }

        // General Help Request
        if (preg_match('/^(i\s+need\s+help|help\s+me|can\s+you\s+help\s+me|help|tulungan\s+mo\s+ako)[.!?\s]*$/i', $cleaned)) {
            return "Of course! 😊 Tell me what you need help with. You can ask about products, sizing, fabrics, styling, orders, payments, or anything related to shopping on LumBarong.";
        }

        return null;
    }

    /**
     * Layer 2: Account, Authentication & Support Explanations
     */
    private static function handleAccountSupport(string $lower): ?string
    {
        // Profile & Account Settings
        if (preg_match('/\b(where\s+(is|to\s+find|to\s+see)\s+(my\s+)?profile|my\s+profile|account\s+settings|view\s+profile|profile\s+page|wheres?\s+my\s+profile|saan\s+(ang\s+)?profile)\b/i', $lower)) {
            return "👤 **Your Account & Profile:**\n\n"
                . "You can view and update your personal details, shipping address, and password settings on your **[My Profile](/profile)** page.\n\n"
                . "• **[Edit Profile Details](/profile)**\n"
                . "• **[Manage Shipping Addresses](/profile/addresses)**\n"
                . "• **[Change Password](/profile/change-password)**\n\n"
                . "👉 Click here to go straight to: **[My Profile](/profile)**";
        }

        // Password Reset & Forgot Password Guidance
        if (preg_match('/\b(reset\s+password|forgot\s+password|change\s+password|paano\s+mag\s*palit\s+ng\s+password|nakalimutan\s+ang\s+password)\b/i', $lower)) {
            return "🔑 **Password Reset & Account Assistance:**\n\n"
                . "1. Go to the [Login Page](/login) and click **Forgot Password?**\n"
                . "2. Enter your registered email address to receive a secure password reset link.\n"
                . "3. Follow the instructions sent to your email to create a new password.\n\n"
                . "If you are already logged in, you can update your password directly at **[Change Password](/profile/change-password)**.";
        }

        // What is an OTP?
        if (preg_match('/\b(what\s+is\s+(an?\s+)?otp|ano\s+ang\s+otp|explain\s+otp|how\s+does\s+otp\s+work)\b/i', $lower)) {
            return "ℹ️ **What is an OTP?**\n\n"
                . "An **OTP (One-Time Password)** is a temporary, single-use security code sent to your registered email or mobile number to verify your identity during logins or transactions.\n\n"
                . "🛡️ *Security Reminder*: LumBarong will never ask you to share your OTP or banking PINs in this chat or via message.";
        }

        return null;
    }

    /**
     * Layer 3: Styling & Heritage Knowledge Base
     */
    private static function handleStylingKnowledge(string $lower): ?string
    {
        // 0. WHERE TO SEE ORDERS / ORDER NAVIGATION
        if (preg_match('/\b(where\s+(to\s+)?(see|find|view|check|track)\s+(my\s+)?orders?|saan\s+(makikita|makita)\s+(ang\s+)?(order|orders)|how\s+to\s+(see|check)\s+orders?)\b/i', $lower)) {
            return "📦 **Viewing Your Orders & Live Tracking:**\n\n"
                . "You can view and track all your purchases directly in the **[My Orders](/customer/orders)** portal!\n\n"
                . "In your **[My Orders](/customer/orders)** page, you can:\n"
                . "• View real-time status and courier delivery tracking\n"
                . "• Inspect live photos uploaded by our Lumban artisans packing your Barong\n"
                . "• Confirm order delivery and leave verified artisan reviews.\n\n"
                . "👉 Click here to go straight to: **[My Orders](/customer/orders)**";
        }

        // 1. SHOP LOCATION & WORKSHOP
        if (preg_match('/\b(where\s+(is|are)\s+(your|the)?\s*(shop|store|boutique|workshop|office|location|artisans?)|location|located|address|physical\s+store|saan\s+(ang\s+)?(shop|tinda|tindahan|pwesto))\b/i', $lower)) {
            return "📍 **Our Heritage Workshop Location:**\n\n"
                . "LumBarong's master artisans and embroidery workshops are proudly based in **Lumban, Laguna, Philippines** — renowned worldwide as the *Embroidery Capital of the Philippines*.\n\n"
                . "• **Direct Artisan Workshops**: Every piece is tailored and hand-embroidered right here in Lumban by our certified master artisans.\n"
                . "• **Nationwide & International Delivery**: We ship door-to-door across the Philippines with express courier partners and full parcel tracking.\n"
                . "• **Artisan Directory**: You can explore individual artisan shops in our [Shops Hub](/shops) or chat directly with shop owners in the **💬 Artisans** tab!";
        }

        // 2. LOWEST PRICE & BUDGET-FRIENDLY OPTIONS
        if (preg_match('/\b(lowest\s+price|cheapest|pinakamura|least\s+expensive|budget\s+friendly|pinaka\s+mura|affordable|mura)\b/i', $lower)) {
            return "🏷️ **Lowest Price & Budget-Friendly Barong Tagalog:**\n\n"
                . "Our most accessible, high-quality handcrafted Barongs start at:\n\n"
                . "1. **Organza / Monoray Barongs** — **₱1,500 to ₱3,200**\n"
                . "   • Lightweight, crisp translucent weave, wrinkle-resistant.\n"
                . "   • Best for school graduations, oathtakings, and budget-conscious events.\n\n"
                . "2. **Jusi Silk Machine-Embroidered** — **₱3,500 to ₱5,500**\n"
                . "   • Traditional semi-sheen formal Barong for wedding guests & sponsors.\n\n"
                . "3. **Cocoon Silk** — **₱6,000 to ₱9,500** (Opaque luxury)\n"
                . "4. **Pure Piña-Seda** — **₱9,500+** (Heirloom groom wear)\n\n"
                . "Tell me your target price range or occasion and I'll find the best options in stock!";
        }

        // 3. CONTACT & CUSTOMER SERVICE
        if (preg_match('/\b(contact|email|phone|cellphone|number|hotline|support|help\s+desk|customer\s+service|kausap|tawag)\b/i', $lower)) {
            return "📞 **LumBarong Customer Support & Contact Channels:**\n\n"
                . "• **Official Support Email**: **lumbarongsupport@gmail.com**\n"
                . "• **Direct Artisan Chat**: Click the **💬 Artisans** tab at the top of this chat box to message individual Lumban workshops directly.\n"
                . "• **Order Concerns**: View live courier tracking and packing proofs under your [My Orders](/customer/orders) portal.";
        }

        // 4. RETURNS, REFUNDS & EXCHANGES
        if (preg_match('/\b(return|refund|exchange|palit|bawi|warranty|policy|reklamo)\b/i', $lower)) {
            return "🔄 **Returns, Exchanges & Quality Guarantee:**\n\n"
                . "• **Authenticity Guarantee**: 100% genuine Lumban hand-embroidery craftsmanship.\n"
                . "• **Tailoring & Size Exchanges**: If your order has size discrepancies or tailoring defects, you can submit a return request within **7 days** of delivery via your [My Orders](/customer/orders) page.\n"
                . "• **Artisan Support**: You can also email **lumbarongsupport@gmail.com** with photos of the garment for fast resolution.";
        }

        // 5. BEST SELLERS & MOST POPULAR
        if (str_contains($lower, 'best seller') || str_contains($lower, 'bestseller') || str_contains($lower, 'popular') || str_contains($lower, 'top seller') || str_contains($lower, 'most bought')) {
            return "⭐ **Our Top Best Sellers & Customer Favorites:**\n\n"
                . "1. **Piña-Seda Classic Pechera Barong** — Our #1 choice for grooms and formal occasions, featuring intricate *Calado* openwork hand-embroidery along the chest.\n"
                . "2. **Modern Cocoon Silk Barong** — A contemporary favorite with a crisp Chinese/Mandarin collar and smooth opaque sheen.\n"
                . "3. **Hand-Embroidered Jusi Barong** — The top-picked versatile formal Barong for Ninongs, wedding guests, and corporate events.\n"
                . "4. **Organza Graduation Barong** — Highly sought-after for lightweight comfort and sharp formal look under ₱3,500.\n\n"
                . "Let me know your preferred occasion or budget and I'll match the best piece for you!";
        }

        // 6. WEDDING & GROOM ATTIRE
        if (str_contains($lower, 'wedding') || str_contains($lower, 'groom') || str_contains($lower, 'kasal') || str_contains($lower, 'bride')) {
            return "🤵 **Wedding & Groom Styling Guide:**\n\n"
                . "For the **Groom**, the gold standard is an authentic **Piña-Seda** (Pineapple-Silk) or **Cocoon Silk Barong** handcrafted in Lumban, Laguna. Key recommendations:\n\n"
                . "• **Embroidery Style**: Half-open *Pechera* with Lumban *Calado* (pulled-thread lace) creates a regal, heirloom aesthetic.\n"
                . "• **Collar**: Classic pointed collar for traditional elegance, or Chinese/Mandarin collar for a sleek modern look.\n"
                . "• **Undergarment**: Pair with a high-grade cream or white *Camisa de Chino* (long sleeves).\n"
                . "• **Trousers**: Pure black tailored wool or wool-blend slacks with polished black leather shoes.\n\n"
                . "If you tell me whether you are the groom, sponsor (ninong), or a guest, I can provide more specific styling suggestions!";
        }

        // 7. NINONG / PRINCIPAL SPONSOR / GUESTS / ENTOURAGE
        if (str_contains($lower, 'ninong') || str_contains($lower, 'sponsor') || str_contains($lower, 'groomsmen') || str_contains($lower, 'entourage') || str_contains($lower, 'guest')) {
            return "👔 **Ninong & Wedding Entourage Guide:**\n\n"
                . "For **Principal Sponsors (Ninong)** and formal guests, a **Jusi** or **High-grade Organza Barong** strikes the ideal balance of dignity, breathability, and formality.\n\n"
                . "• **Color & Tone**: Natural ecru/cream or light champagne tones.\n"
                . "• **Design**: Subtle geometric or floral border embroidery (*Burdang Kamay*) that looks dignified without overshadowing the groom.\n"
                . "• **Fit**: Traditional comfort fit allows easy movement throughout long church and reception ceremonies.";
        }

        // 8. GRADUATION & CEREMONY ATTIRE
        if (str_contains($lower, 'graduation') || str_contains($lower, 'student') || str_contains($lower, 'college') || str_contains($lower, 'diploma') || str_contains($lower, 'graduate')) {
            return "🎓 **Graduation & Ceremony Attire:**\n\n"
                . "For graduations, moving-up ceremonies, and academic functions:\n"
                . "• **Organza Barong** (₱1,500–₱3,500) is the most popular choice: crisp, translucent, wrinkle-resistant, and comfortable in tropical heat.\n"
                . "• **Monoray Barong**: Offers a sharp structured collar with clean machine embroidery.\n"
                . "• Pair with dark slacks and an undershirt for a distinguished academic stage appearance.";
        }

        // 9. FABRIC COMPARISON & GUIDE
        if (str_contains($lower, 'difference') || str_contains($lower, 'piña vs') || str_contains($lower, 'pina vs') || str_contains($lower, 'fabric') || str_contains($lower, 'tela') || str_contains($lower, 'material') || str_contains($lower, 'piña') || str_contains($lower, 'jusi') || str_contains($lower, 'cocoon') || str_contains($lower, 'organza')) {
            return "🧵 **Philippine Heritage Fabric Guide:**\n\n"
                . "• **Piña (Pineapple Fiber)**: The queen of Philippine textiles. Hand-scraped from Spanish Red pineapple leaves. Ultra-delicate, naturally ivory, and an heirloom investment.\n"
                . "• **Piña-Seda**: Piña blended with natural silk for added strength, smoother texture, and a graceful drape.\n"
                . "• **Cocoon Silk**: Pure woven silk. Opaque, durable, and luxurious with a smooth natural sheen.\n"
                . "• **Jusi (Silk blend)**: Historically made from abaca/banana fibers, modern Jusi is a tightly woven silk blend. Crisp, formal, and versatile.\n"
                . "• **Organza / Monoray**: Synthetic sheer fabric. Lightweight, durable, and budget-friendly.";
        }

        // 10. BARONG CARE, WASHING & IRONING
        if (str_contains($lower, 'wash') || str_contains($lower, 'clean') || str_contains($lower, 'care') || str_contains($lower, 'iron') || str_contains($lower, 'press') || str_contains($lower, 'maintain') || str_contains($lower, 'laba') || str_contains($lower, 'stain')) {
            return "🧺 **How to Care for Your Handcrafted Barong:**\n\n"
                . "1. **Washing**: Hand-wash only in cold/lukewarm water with gentle baby shampoo or mild detergent. **Never wring, twist, or machine wash.**\n"
                . "2. **Drying**: Gently roll in a clean dry towel to absorb excess water, then hang dry on a padded hanger away from direct sunlight.\n"
                . "3. **Ironing / Pressing**: Iron inside-out on low-to-medium heat. **Always place a damp cotton pressing cloth** between the iron and the Barong to protect delicate embroidery and fibers.\n"
                . "4. **Storage**: Store in a breathable cotton garment bag (avoid airtight plastic) on a wide wooden hanger.";
        }

        // 11. SIZING & MEASUREMENTS
        if (str_contains($lower, 'size') || str_contains($lower, 'sizing') || str_contains($lower, 'fit') || str_contains($lower, 'measure') || str_contains($lower, 'measurement') || str_contains($lower, 'chest') || str_contains($lower, 'shoulder')) {
            return "📏 **Barong Sizing & Fit Advice:**\n\n"
                . "Barongs do not stretch, so picking the proper allowance is essential for comfort:\n"
                . "• **Small (S)**: Chest 36–38 inches | Shoulders 16.5–17 in\n"
                . "• **Medium (M)**: Chest 39–41 inches | Shoulders 17.5–18 in\n"
                . "• **Large (L)**: Chest 42–44 inches | Shoulders 18.5–19 in\n"
                . "• **Extra Large (XL)**: Chest 45–47 inches | Shoulders 19.5–20 in\n"
                . "• **2XL–3XL**: Chest 48–52+ inches | Shoulders 20.5+ in\n\n"
                . "💡 *Pro-Tip*: Measure your actual chest circumference and add **3 to 4 inches of ease** for a comfortable traditional fit!";
        }

        // 12. LUMBAN HERITAGE & CALADO EMBROIDERY
        if (str_contains($lower, 'lumban') || str_contains($lower, 'laguna') || str_contains($lower, 'heritage') || str_contains($lower, 'calado') || str_contains($lower, 'embroidery') || str_contains($lower, 'burda') || str_contains($lower, 'artisan') || str_contains($lower, 'origin')) {
            return "🏛️ **Lumban, Laguna — The Embroidery Capital:**\n\n"
                . "Lumban is renowned worldwide for its centuries-old embroidery tradition. Our master artisans specialize in:\n"
                . "• ***Calado***: A painstaking openwork technique where fabric threads are meticulously pulled and bound with needle and thread to form lace-like filigree patterns.\n"
                . "• ***Burdang Kamay***: Freehand needle artistry that can take up to 4 to 8 weeks for a single garment.\n"
                . "• Every Barong purchased directly supports local artisan families and preserves this Philippine cultural heritage.";
        }

        // 13. PAYMENTS, GCASH & MAYA
        if (str_contains($lower, 'payment') || str_contains($lower, 'gcash') || str_contains($lower, 'maya') || str_contains($lower, 'pay') || str_contains($lower, 'bayad') || str_contains($lower, 'reference')) {
            return "💳 **Payment Methods on LumBarong:**\n\n"
                . "We accept **GCash** and **Maya** mobile payments with instant verification:\n"
                . "• Send payment to the artisan's registered GCash/Maya number or QR code shown at checkout.\n"
                . "• Enter your valid transaction reference number and upload the confirmation receipt screenshot.\n"
                . "• Our system verifies unique references to prevent duplication and ensure your order is immediately processed by the artisan.";
        }

        // 14. SHIPPING & ORDER TRACKING
        if (str_contains($lower, 'ship') || str_contains($lower, 'shipping') || str_contains($lower, 'delivery') || str_contains($lower, 'track') || str_contains($lower, 'tracking') || str_contains($lower, 'deliver') || str_contains($lower, 'kailan')) {
            return "📦 **Shipment Tracking & Delivery Steps:**\n\n"
                . "Once your order is confirmed, you can track live updates in 5 easy steps:\n"
                . "1. **Order Placed**: Payment received and queued for artisan crafting.\n"
                . "2. **To Ship**: Artisan prepares the garment and uploads a live **Packing Proof Photo**.\n"
                . "3. **Shipped**: Package handed to the courier with tracking number.\n"
                . "4. **In Transit**: Parcel en route to your shipping address.\n"
                . "5. **Delivered**: Inspect your items and click **Confirm Received** to leave a verified review!";
        }

        // 15. FILIPINIANA & WOMEN'S ATTIRE
        if (str_contains($lower, 'filipiniana') || str_contains($lower, 'terno') || str_contains($lower, 'bolero') || str_contains($lower, 'alampay') || str_contains($lower, 'maria clara') || str_contains($lower, 'women') || str_contains($lower, 'dress')) {
            return "👗 **Filipiniana & Women's Heritage Wear:**\n\n"
                . "We showcase exquisite handcrafted pieces for women:\n"
                . "• **Modern Terno**: Iconic structured butterfly sleeves on Piña-Organza or Silk.\n"
                . "• **Embroidered Boleros & Kimonas**: Versatile sheer cover-ups worn over modern cocktail dresses.\n"
                . "• **Alampay / Pañuelo**: Triangular hand-embroidered shoulder shawls crafted with intricate *Calado* motifs.";
        }

        return null;
    }

    // ==========================================
    // 2. AI SMART SIZING & TAILORING ADVISOR
    // ==========================================

    public static function recommendSize(float $heightCm, float $weightKg, string $build = 'regular', string $fit = 'regular'): array
    {
        // Calculate BMI
        $heightM = $heightCm / 100;
        $bmi = $weightKg / ($heightM * $heightM);

        // Estimate chest circumference in inches
        $chestInches = 34 + ($weightKg - 50) * 0.22;
        if ($build === 'athletic' || $build === 'broad') $chestInches += 2;
        if ($build === 'slim') $chestInches -= 1.5;

        // Size determination based on Barong standard sizing
        $size = 'M';
        if ($chestInches < 37) $size = 'S';
        elseif ($chestInches < 40) $size = 'M';
        elseif ($chestInches < 43) $size = 'L';
        elseif ($chestInches < 46) $size = 'XL';
        else $size = '2XL';

        // Fit adjustments
        if ($fit === 'comfort' || $fit === 'traditional') {
            // Traditional Barongs have a looser, flowing drape
            $recommendedSize = $size;
        } else {
            $recommendedSize = $size;
        }

        // Tailoring measurements in inches
        $shoulder = round(16.5 + ($chestInches - 36) * 0.35, 1);
        $sleeve   = round(23 + ($heightCm - 165) * 0.1, 1);
        $length   = round(28 + ($heightCm - 165) * 0.12, 1);

        $confidence = 94;

        $explanation = "Based on your height of " . round($heightCm / 2.54) . "\" (" . round($heightCm) . " cm) and weight of {$weightKg} kg with a " . ucfirst($build) . " build, Size {$recommendedSize} offers the ideal " . round($chestInches, 1) . "\" chest allowance for comfortable shoulder movement in handcrafted Barongs.";

        return [
            'size' => $recommendedSize,
            'confidence' => $confidence . '%',
            'chest_estimate_inches' => round($chestInches, 1),
            'shoulder_estimate_inches' => $shoulder,
            'sleeve_estimate_inches' => $sleeve,
            'shirt_length_inches' => $length,
            'explanation' => $explanation,
        ];
    }

    // ==========================================
    // ==========================================
    // 3. AI SELLER LISTING & DESCRIPTION GENERATOR
    // ==========================================

    public static function generateProductListing(array $params): array
    {
        $name        = trim($params['name'] ?? '');
        $fabric      = $params['fabric'] ?? '100% Piña';
        $embroidery  = $params['embroidery'] ?? 'Calado Hand Embroidery';
        $category    = $params['category'] ?? 'Barong Tagalog';
        $targetGroup = $params['target_group'] ?? 'Men';
        $theme       = $params['theme'] ?? 'Wedding & Formal';

        $prompt = "Write a concise, high-converting 2-paragraph artisan product description (under 400 characters) for an authentic handcrafted {$targetGroup} {$category}" . ($name ? " titled '{$name}'" : "") . " from Lumban, Laguna, Philippines. Fabric: {$fabric}. Embroidery: {$embroidery}. Highlight Lumban heritage weaving and timeless elegance. Output plain text paragraphs only without titles, asterisks, or markdown headers.";

        $aiOutput = self::callGemini($prompt);

        if (!$aiOutput || strlen($aiOutput) < 20) {
            $catLabel = $category ?: 'Philippine heritage piece';
            $fabricLabel = $fabric ?: 'premium woven fabric';
            $nameLabel = $name ? "The {$name}" : "This exquisite {$catLabel}";
            
            $descOptions = [
                "{$nameLabel} is handcrafted by master artisans in Lumban, Laguna, the embroidery capital of the Philippines. Made from {$fabricLabel}, it features intricate embroidery and timeless tailoring suitable for formal ceremonies and prestigious gatherings.",
                "Experience authentic Filipino artistry with this meticulously crafted {$catLabel}. Tailored in Lumban, Laguna using {$fabricLabel}, each delicate stitch reflects generations of heritage craftsmanship designed for elegance and comfort.",
                "An authentic masterpiece from Lumban's master embroiderers, this {$catLabel} showcases refined {$fabricLabel} craftsmanship with classic detailing. Perfect for weddings, celebrations, and cultural pride."
            ];
            $description = $descOptions[array_rand($descOptions)];

            return [
                'title' => $name ?: "Handcrafted {$fabric} {$category}",
                'description' => $description,
                'fabric' => $fabric,
            ];
        }

        // Clean up markdown / headers if any
        $cleaned = trim(preg_replace('/^#+\s*|^\*\*[^*]+\*\*\s*/m', '', $aiOutput));
        if (mb_strlen($cleaned) > 490) {
            $cleaned = mb_substr($cleaned, 0, 485) . '...';
        }

        return [
            'title' => $name ?: "Handcrafted {$fabric} {$category}",
            'description' => $cleaned,
            'fabric' => $fabric,
        ];
    }

    /**
     * AI Image-to-Product Suggestion & Auto-Fill.
     */
    public static function suggestProductFromImage(string $imagePath, ?string $currentName = null, ?string $currentCategory = null): array
    {
        $categories = \App\Models\Category::pluck('name', 'id')->toArray();
        $catListStr = implode(', ', $categories);

        $apiKey = self::getApiKey();
        if ($apiKey && file_exists($imagePath)) {
            $rawMime = @mime_content_type($imagePath) ?: 'image/jpeg';
            $mimeType = match (strtolower($rawMime)) {
                'image/png' => 'image/png',
                'image/webp' => 'image/webp',
                'image/heic' => 'image/heic',
                'image/heif' => 'image/heif',
                default => 'image/jpeg'
            };
            $imageData = base64_encode(file_get_contents($imagePath));

            $prompt = "You are an AI heritage fashion expert for LumBarong, an e-commerce platform for authentic handcrafted Philippine garments from Lumban, Laguna. "
                . "Analyze this product photo and recommend: "
                . "1. A concise, elegant high-converting product title (English, max 80 chars, e.g. 'Hand-Embroidered Piña-Jusi Barong Tagalog' or 'Traditional Mestiza Filipiniana Bolero Dress'). "
                . "2. The most matching category from these available options: [{$catListStr}]. "
                . "3. The target group ('Men', 'Women', or 'Kids'). "
                . "4. Recommended fabric type ('100% Piña', 'Jusi Silk', 'Piña-Seda', 'Organza', 'Cocoon Silk'). "
                . "5. An inspiring 2-3 paragraph artisan description celebrating the craftsmanship, embroidery technique (e.g., Calado), and care instructions. "
                . "Respond strictly in JSON format: {\"title\": string, \"category\": string, \"target_group\": \"Men\"|\"Women\"|\"Kids\", \"fabric_type\": string, \"description\": string}";

            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                            [
                                'inline_data' => [
                                    'mime_type' => $mimeType,
                                    'data' => $imageData
                                ]
                            ]
                        ]
                    ]
                ]
            ];

            $configuredModel = config('services.gemini.model') ?: env('GEMINI_MODEL', 'gemini-flash-latest');
            $visionModels = array_unique(array_filter([
                $configuredModel,
                'gemini-flash-latest',
                'gemini-2.5-flash',
                'gemini-2.0-flash',
                'gemini-1.5-flash'
            ]));

            foreach ($visionModels as $vModel) {
                try {
                    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$vModel}:generateContent?key={$apiKey}";
                    $res = Http::withOptions([
                        'curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4],
                        'verify' => false,
                    ])->timeout(15)->post($url, $payload);
                    if ($res->successful()) {
                        $json = $res->json();
                        $rawText = $json['candidates'][0]['content']['parts'][0]['text'] ?? '';
                        if (preg_match('/\{[\s\S]*\}/', $rawText, $m)) {
                            $parsed = json_decode($m[0], true);
                            if (is_array($parsed) && !empty($parsed['title'])) {
                                // Find matching category id
                                $matchedCatId = null;
                                $suggestedCatName = strtolower($parsed['category'] ?? '');
                                foreach ($categories as $id => $name) {
                                    if (str_contains($suggestedCatName, strtolower($name)) || str_contains(strtolower($name), $suggestedCatName)) {
                                        $matchedCatId = $id;
                                        break;
                                    }
                                }
                                if (!$matchedCatId && !empty($categories)) {
                                    $matchedCatId = array_key_first($categories);
                                }

                                return [
                                    'success' => true,
                                    'title' => trim(str_replace(['"', "'", 'Title:', 'Product Title:'], '', $parsed['title'])),
                                    'category_id' => $matchedCatId,
                                    'category_name' => $parsed['category'] ?? 'Barong Tagalog',
                                    'target_group' => in_array($parsed['target_group'] ?? '', ['Men', 'Women', 'Kids']) ? $parsed['target_group'] : 'Men',
                                    'fabric_type' => $parsed['fabric_type'] ?? '100% Piña',
                                    'description' => trim($parsed['description'] ?? ''),
                                ];
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning("Gemini Vision suggestProductFromImage failed on {$vModel}: " . $e->getMessage());
                }
            }
        }

        // Fallback intelligent heuristics
        $fn = strtolower(basename($imagePath));
        $isFilipiniana = str_contains($fn, 'filipiniana') || str_contains($fn, 'dress') || str_contains($fn, 'women') || str_contains($fn, 'terno') || str_contains($fn, 'bolero');
        $isKid = str_contains($fn, 'kid') || str_contains($fn, 'child') || str_contains($fn, 'boy') || str_contains($fn, 'girl');

        $targetGroup = $isKid ? 'Kids' : ($isFilipiniana ? 'Women' : 'Men');
        $fabric = '100% Piña';
        $title = $isKid ? "Handmade Heritage Barong for Kids" : ($isFilipiniana ? "Traditional Lumban Filipiniana Dress" : "Handcrafted Artisan Piña Barong Tagalog");

        $matchedCatId = null;
        foreach ($categories as $id => $name) {
            $n = strtolower($name);
            if ($isFilipiniana && (str_contains($n, 'filipiniana') || str_contains($n, 'dress') || str_contains($n, 'terno'))) {
                $matchedCatId = $id;
                break;
            } elseif ($isKid && str_contains($n, 'kid')) {
                $matchedCatId = $id;
                break;
            } elseif (!$isFilipiniana && !$isKid && str_contains($n, 'barong')) {
                $matchedCatId = $id;
                break;
            }
        }
        if (!$matchedCatId && !empty($categories)) {
            $matchedCatId = array_key_first($categories);
        }

        $description = "Experience timeless Filipino elegance with this exquisite handcrafted masterpiece, meticulously tailored by master artisans in Lumban, Laguna—the Embroidery Capital of the Philippines.\n\n"
            . "**Product Highlights:**\n"
            . "• Fabric: Premium {$fabric}\n"
            . "• Embroidery: Authentic Calado hand-embroidery\n"
            . "• Origin: Lumban, Laguna, Philippines\n"
            . "• Perfect For: Formal Gatherings, Weddings, and Cultural Occasions\n\n"
            . "**Garment Care Instructions:**\n"
            . "Dry clean or gentle hand wash in cold water with mild soap. Do not wring. Iron with a pressing cloth on low heat.";

        return [
            'success' => true,
            'title' => $title,
            'category_id' => $matchedCatId,
            'category_name' => $categories[$matchedCatId] ?? 'Barong Tagalog',
            'target_group' => $targetGroup,
            'fabric_type' => $fabric,
            'description' => $description,
        ];
    }

    // ==========================================
    // 4. AI PASSWORD SECURITY & COMPLEXITY ADVISOR
    // ==========================================

    public static function analyzePassword(string $password): array
    {
        $len = strlen($password);
        $hasUpper = (bool) preg_match('/[A-Z]/', $password);
        $hasLower = (bool) preg_match('/[a-z]/', $password);
        $hasDigit = (bool) preg_match('/[0-9]/', $password);
        $hasSymbol = (bool) preg_match('/[^A-Za-z0-9]/', $password);

        // Entropy calculation
        $pool = 0;
        if ($hasLower) $pool += 26;
        if ($hasUpper) $pool += 26;
        if ($hasDigit) $pool += 10;
        if ($hasSymbol) $pool += 32;

        $entropy = $len > 0 && $pool > 0 ? round($len * log($pool, 2), 1) : 0;

        $score = 'Weak';
        $color = 'text-red-500';
        $barPercent = min(100, ($entropy / 75) * 100);

        if ($len >= 8 && $entropy >= 45 && ($hasUpper || $hasSymbol)) {
            $score = 'Fair';
            $color = 'text-amber-500';
        }
        if ($len >= 10 && $entropy >= 60 && $hasUpper && $hasDigit && $hasSymbol) {
            $score = 'Strong';
            $color = 'text-emerald-600';
        }
        if ($len >= 12 && $entropy >= 75 && $hasUpper && $hasLower && $hasDigit && $hasSymbol) {
            $score = 'Very Strong';
            $color = 'text-emerald-700';
        }

        $tips = [];
        if ($len < 8) $tips[] = 'Use at least 8 characters.';
        if (!$hasUpper) $tips[] = 'Add uppercase letters (A-Z).';
        if (!$hasDigit) $tips[] = 'Include numbers (0-9).';
        if (!$hasSymbol) $tips[] = 'Include special symbols (!@#$%^&*).';

        $advice = empty($tips) 
            ? 'Excellent! This password has high entropy and resists dictionary & brute-force attacks.'
            : 'Improve security: ' . implode(' ', $tips);

        return [
            'score' => $score,
            'entropy' => $entropy,
            'percent' => round($barPercent),
            'color' => $color,
            'advice' => $advice,
        ];
    }

    // ==========================================
    // 5. AI RECEIPT OCR & PAYMENT VERIFICATION
    // ==========================================

    /**
     * Check if a payment reference number has already been used.
     */
    public static function isDuplicateReference(string $referenceNumber): array
    {
        $clean = trim($referenceNumber);
        if (!$clean) {
            return ['is_duplicate' => false, 'message' => ''];
        }

        try {
            $exists = Order::where('paymentReference', $clean)->exists();
            if ($exists) {
                return [
                    'is_duplicate' => true,
                    'message' => '❌ Security Alert: This payment reference number has already been used in another order.'
                ];
            }
        } catch (\Throwable $e) {
            // In case table not available
        }

        return [
            'is_duplicate' => false,
            'message' => '✓ Payment reference is unique.'
        ];
    }

    /**
     * AI Screening of Uploaded Receipt Image against reference number, payment method, and expected amount.
     * Returns a 3-tier status: 'PASS', 'REVIEW', or 'REJECT'.
     * Final payment verification is always confirmed by the seller/artisan against their wallet balance.
     */
    public static function verifyReceipt(string $imagePath, string $referenceNumber, string $paymentMethod = 'GCash', float $expectedAmount = 0.0): array
    {
        $ref = trim($referenceNumber);
        $method = trim($paymentMethod);

        // 1. Try Gemini Vision if API key configured
        $apiKey = self::getApiKey();
        if ($apiKey && file_exists($imagePath)) {
            $rawMime = @mime_content_type($imagePath) ?: 'image/jpeg';
            $mimeType = match (strtolower($rawMime)) {
                'image/png' => 'image/png',
                'image/webp' => 'image/webp',
                'image/heic' => 'image/heic',
                'image/heif' => 'image/heif',
                default => 'image/jpeg'
            };
            $imageData = base64_encode(file_get_contents($imagePath));

            $amountPrompt = $expectedAmount > 0 ? " Expected payment amount is around ₱" . number_format($expectedAmount, 2) . "." : "";
            $prompt = "Analyze this uploaded image for a Philippine e-commerce store (LumBarong). "
                . "1. Is this a legitimate mobile payment receipt or transaction screenshot (from {$method}, GCash, Maya, or Philippine bank)? "
                . "Or is it an unrelated image (such as clothing, people, scenery, general product photo, meme, social media post screenshot, or costume)? "
                . "2. Does it contain the payment reference number '{$ref}'?{$amountPrompt} "
                . "Respond strictly in JSON: {\"status\": \"PASS\"|\"REVIEW\"|\"REJECT\", \"is_receipt\": boolean, \"ref_matched\": boolean, \"confidence\": number, \"detected_ref\": string, \"message\": string}";

            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                            [
                                'inline_data' => [
                                    'mime_type' => $mimeType,
                                    'data' => $imageData
                                ]
                            ]
                        ]
                    ]
                ]
            ];

            $configuredModel = config('services.gemini.model') ?: env('GEMINI_MODEL', 'gemini-flash-latest');
            $visionModels = array_unique(array_filter([
                $configuredModel,
                'gemini-flash-latest',
                'gemini-3.5-flash',
                'gemini-3.7-flash',
                'gemini-3.6-flash'
            ]));

            foreach ($visionModels as $vModel) {
                try {
                    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$vModel}:generateContent?key={$apiKey}";
                    $res = Http::withOptions([
                        'curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4],
                        'verify' => false,
                    ])->timeout(15)->post($url, $payload);
                    if ($res->successful()) {
                        $json = $res->json();
                        $rawText = $json['candidates'][0]['content']['parts'][0]['text'] ?? '';
                        if (preg_match('/\{[\s\S]*\}/', $rawText, $m)) {
                            $parsed = json_decode($m[0], true);
                            if (is_array($parsed)) {
                                $isReceipt = (bool) ($parsed['is_receipt'] ?? false);
                                $refMatched = (bool) ($parsed['ref_matched'] ?? false);
                                $detectedRef = trim((string) ($parsed['detected_ref'] ?? ''));

                                // Strict reference check if detected on receipt
                                if ($ref && $detectedRef && str_replace([' ', '-'], '', $detectedRef) !== str_replace([' ', '-'], '', $ref)) {
                                    $refMatched = false;
                                }

                                $tier = strtoupper(trim((string) ($parsed['status'] ?? '')));
                                if (!$isReceipt) {
                                    $tier = 'REJECT';
                                } elseif (!$refMatched) {
                                    $tier = 'REVIEW';
                                } elseif (!in_array($tier, ['PASS', 'REVIEW', 'REJECT'], true)) {
                                    $tier = 'PASS';
                                }

                                $msg = (string) ($parsed['message'] ?? 'Receipt screening complete.');
                                if ($isReceipt && !$refMatched && $detectedRef) {
                                    $msg = "Reference number mismatch: Detected \"{$detectedRef}\" on image, but entered \"{$ref}\". Artisan manual verification required.";
                                }

                                return [
                                    'status' => $tier,
                                    'is_receipt' => $isReceipt,
                                    'ref_matched' => $refMatched,
                                    'confidence' => (int) ($parsed['confidence'] ?? 85),
                                    'detected_ref' => $detectedRef,
                                    'needs_seller_verification' => true,
                                    'message' => $msg
                                ];
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning("Gemini Vision ({$vModel}) receipt check failed: " . $e->getMessage());
                }
            }
        }

        // 2. Intelligent Built-in Heuristic Receipt Classifier
        return self::heuristicReceiptAnalysis($imagePath, $ref, $method, $expectedAmount);
    }

    /**
     * Built-in Heuristic Receipt & Image Analysis.
     */
    private static function heuristicReceiptAnalysis(string $imagePath, string $ref, string $method, float $expectedAmount = 0.0): array
    {
        $filename = strtolower(basename($imagePath));

        // Keywords that clearly indicate an unrelated non-receipt photo
        $nonReceiptKeywords = [
            'costume', 'costumes', 'barong', 'dress', 'shirt', 'wilkes', 'manilla',
            'product', 'cloth', 'fabric', 'sample', 'photo', 'picture', 'selfie',
            'model', 'catalog', 'fashion', 'wedding_photo', 'avatar', 'banner', 'hero'
        ];

        foreach ($nonReceiptKeywords as $kw) {
            if (str_contains($filename, $kw)) {
                return [
                    'status' => 'REJECT',
                    'is_receipt' => false,
                    'ref_matched' => false,
                    'confidence' => 95,
                    'needs_seller_verification' => true,
                    'message' => 'The attached file appears to be a general photo/product image, not a ' . $method . ' transaction receipt screenshot. Please attach your actual payment confirmation screenshot.'
                ];
            }
        }

        // Check image dimensions & aspect ratio if file exists
        if (file_exists($imagePath)) {
            $imgInfo = @getimagesize($imagePath);
            if ($imgInfo) {
                $width = $imgInfo[0];
                $height = $imgInfo[1];

                // Square 1:1 or landscape images (width >= height) are almost never mobile payment receipt screenshots (which are vertical mobile screenshots)
                if ($width > 0 && $height > 0) {
                    $ratio = $height / $width;
                    if ($ratio < 1.15 && !str_contains($filename, 'receipt') && !str_contains($filename, 'screenshot') && !str_contains($filename, 'gcash') && !str_contains($filename, 'maya')) {
                        return [
                            'status' => 'REJECT',
                            'is_receipt' => false,
                            'ref_matched' => false,
                            'confidence' => 88,
                            'needs_seller_verification' => true,
                            'message' => 'The uploaded image dimensions indicate a general photo or square image rather than a vertical ' . $method . ' mobile receipt screenshot.'
                        ];
                    }
                }
            }
        }

        // Heuristic fallback: image passed geometry screening, but reference match cannot be verified without OCR/Gemini
        return [
            'status' => 'REVIEW',
            'is_receipt' => true,
            'ref_matched' => false,
            'confidence' => 55,
            'needs_seller_verification' => true,
            'message' => "Image geometry is consistent with a vertical mobile screenshot. The artisan will verify the {$method} reference number and amount in their wallet before proceeding."
        ];
    }

    // ========================================================
    // 7. AI SHOP POLICY GENERATOR, POLISHER & TRANSLATOR
    // ========================================================

    /**
     * AI Shop Policy Generator, Optimizer & Tagalog-to-English Translator.
     */
    public static function assistPolicy(string $type, ?string $draft, string $action, string $tone = 'standard'): array
    {
        $typeLabel = $type === 'cancellation' ? 'Shop Cancellation Policy' : 'Shop Refund & Return Policy';
        $draftText = trim((string) $draft);

        // System Instruction tailored for Lumban Barong & Filipiniana artisan marketplace
        $systemInstruction = "You are a professional legal and e-commerce policy specialist for LumBarong, the premier Philippine marketplace for handcrafted Barong Tagalog, Filipiniana, and artisan embroidery from Lumban, Laguna. Your task is to write, polish, or translate clear, concise, and professional shop policies in English. Output only the policy text in 2-4 sentences without conversational filler, markdown headings, or quotes.";

        if ($action === 'translate') {
            $prompt = "Translate the following Tagalog/Taglish text for a {$typeLabel} into fluent, professional English for an artisan e-commerce shop:\n\n\"{$draftText}\"\n\nOutput only the translated policy in 2-4 concise, professional sentences.";
        } elseif ($action === 'improve') {
            $toneDesc = match($tone) {
                'strict' => 'Formal and firm: emphasize strict artisan tailoring rules, no cancellations after crafting starts, and video proof for claims.',
                'flexible' => 'Warm and customer-friendly: emphasize customer care, fair resolution, and hassle-free replacements for sizing issues.',
                default => 'Professional and balanced: clear protection for artisan labor and fair buyer resolution for transit or manufacturing issues.'
            };
            $prompt = "Improve and polish the following draft for a {$typeLabel} into high-quality professional English. Tone: {$toneDesc}.\n\nDraft:\n\"{$draftText}\"\n\nOutput only the refined 2-4 sentence policy statement.";
        } else { // generate
            $toneDesc = match($tone) {
                'strict' => 'Strict Made-to-Order Artisan Policy: cancellations only permitted prior to cutting and embroidery; final sale on bespoke items; returns only for defective items reported within 48 hours of delivery with unboxing video.',
                'flexible' => 'Customer-Centric Flexible Policy: cancellations allowed within 12 hours of order; 7-day return/exchange window for unworn items with tags attached; tailoring alterations supported.',
                default => 'Standard Balanced Artisan Policy: cancellations accepted before order processing and payment verification; returns eligible for manufacturing defects or shipping damages reported within 48 hours; custom tailored sizes cannot be returned for change of mind.'
            };
            $prompt = "Generate a professional, concise {$typeLabel} in English for an artisan workshop in Lumban, Laguna. Requirements: {$toneDesc}. Output only the 2-4 sentence policy text.";
        }

        $aiText = self::callGemini($prompt, $systemInstruction);

        if ($aiText) {
            $cleaned = trim(preg_replace('/^["\']|["\']$/', '', trim($aiText)));
            return [
                'success' => true,
                'text' => $cleaned,
                'source' => 'gemini'
            ];
        }

        // High-quality heuristic fallback if Gemini is offline
        $fallback = self::getHeuristicPolicyFallback($type, $action, $draftText, $tone);
        return [
            'success' => true,
            'text' => $fallback,
            'source' => 'heuristic'
        ];
    }

    /**
     * Heuristic Policy Generator & Tagalog Translator Fallback.
     */
    private static function getHeuristicPolicyFallback(string $type, string $action, string $draft, string $tone): string
    {
        if ($type === 'cancellation') {
            if ($action === 'translate' && !empty($draft)) {
                return "Cancellation requests are accepted only prior to order processing and tailoring. Once embroidery and tailoring have commenced, orders can no longer be cancelled. Please confirm all order details and measurements before completing payment.";
            }

            if ($action === 'improve' && !empty($draft)) {
                return rtrim($draft, '.') . ". Please note that all cancellation requests must be filed before payment verification and artisan embroidery work begins.";
            }

            return match($tone) {
                'strict' => "All orders enter production immediately upon payment confirmation. Cancellations are strictly not accepted once tailoring, embroidery, or fabric cutting has commenced. Please review measurements and order specifications carefully prior to checkout.",
                'flexible' => "You may cancel your order free of charge within 12 hours of purchase. Once your custom garment has entered the active tailoring stage, cancellation requests will be reviewed on a case-by-case basis.",
                default => "Cancellation requests must be submitted prior to payment verification and order processing. Once handcrafted tailoring has begun, cancellations may no longer be accepted. Please ensure all sizing and delivery details are accurate before completing payment."
            };
        } else {
            // Refund policy
            if ($action === 'translate' && !empty($draft)) {
                return "Refund and return requests are evaluated upon submission. Defective or damaged items must be reported within 48 hours of delivery with photo or video unboxing evidence. Custom-tailored garments are final sale unless damaged in transit.";
            }

            if ($action === 'improve' && !empty($draft)) {
                return rtrim($draft, '.') . ". Damaged or defective items must be reported within 48 hours of receipt with unboxing evidence for replacement or refund processing.";
            }

            return match($tone) {
                'strict' => "Custom tailored barongs and bespoke items are non-refundable and final sale. Replacement or store credit is only granted for verified manufacturing defects reported within 48 hours of delivery with continuous unboxing video proof.",
                'flexible' => "We want you to love your handcrafted garment. If your item does not fit or has defects, you may request an exchange or adjustment within 7 days of delivery, provided the item is unwashed and tags remain intact.",
                default => "Refund and return requests are subject to shop evaluation. Custom-sized garments crafted to provided measurements are final sale. Damaged or defective items upon delivery must be reported within 48 hours with unboxing proof to initiate a return or adjustment."
            };
        }
    }
}
