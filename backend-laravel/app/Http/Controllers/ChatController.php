<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use App\Models\Order;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    /**
     * Send a message.
     */
    public function sendMessage(Request $request)
    {
        $content = trim((string) ($request->input('content') ?: $request->input('body') ?: $request->input('message') ?: ''));
        if (!$content) {
            return response()->json(['message' => 'Message content is required'], 422);
        }

        $receiverId = $request->input('receiverId');
        if (!$receiverId || !User::where('id', $receiverId)->exists()) {
            return response()->json(['message' => 'Valid receiver ID is required'], 422);
        }

        $senderId = Auth::id();
        if (!$senderId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        
        $message = Message::create([
            'senderId' => $senderId,
            'receiverId' => $receiverId,
            'content' => $content,
            'read' => false,
        ]);

        $msg = Message::with('sender:id,name,shopName,role,profilePhoto')->find($message->id);
        $res = $msg ? $msg->toArray() : [
            'id' => $message->id,
            'senderId' => $senderId,
            'receiverId' => $receiverId,
            'content' => $content,
            'read' => false,
        ];
        $res['body'] = $content;
        $res['createdAt'] = $msg?->createdAt ? ($msg->createdAt instanceof \Carbon\Carbon ? $msg->createdAt->toISOString() : \Carbon\Carbon::parse($msg->createdAt)->toISOString()) : now()->toISOString();
        $res['created_at'] = $res['createdAt'];

        return response()->json($res, 201);
    }

    /**
     * Get conversation with a specific user.
     */
    public function getConversation(string $otherUserId)
    {
        $userId = Auth::id();

        $messages = Message::where(function ($query) use ($userId, $otherUserId) {
                $query->where('senderId', $userId)->where('receiverId', $otherUserId);
            })->orWhere(function ($query) use ($userId, $otherUserId) {
                $query->where('senderId', $otherUserId)->where('receiverId', $userId);
            })
            ->orderBy('createdAt', 'asc')
            ->with(['sender:id,name,shopName,profilePhoto,role', 'receiver:id,name,shopName,profilePhoto,role'])
            ->get()
            ->map(function (Message $m) {
                $arr = $m->toArray();
                $arr['body'] = $m->content;
                $arr['createdAt'] = $m->createdAt ? ($m->createdAt instanceof \Carbon\Carbon ? $m->createdAt->toISOString() : \Carbon\Carbon::parse($m->createdAt)->toISOString()) : null;
                $arr['created_at'] = $arr['createdAt'];
                return $arr;
            });

        // Mark as read
        Message::where('senderId', $otherUserId)
            ->where('receiverId', $userId)
            ->where('read', false)
            ->update(['read' => true]);

        return response()->json($messages);
    }

    /**
     * Get all recent conversations.
     */
    public function getConversations()
    {
        $userId = Auth::id();

        // Get all unique users this person has chatted with
        $senderIds = Message::where('receiverId', $userId)->pluck('senderId')->toArray();
        $receiverIds = Message::where('senderId', $userId)->pluck('receiverId')->toArray();
        $chattedUserIds = array_unique(array_merge($senderIds, $receiverIds));

        $conversations = [];
        foreach ($chattedUserIds as $otherId) {
            $lastMessage = Message::where(function ($q) use ($userId, $otherId) {
                    $q->where('senderId', $userId)->where('receiverId', $otherId);
                })->orWhere(function ($q) use ($userId, $otherId) {
                    $q->where('senderId', $otherId)->where('receiverId', $userId);
                })
                ->orderBy('createdAt', 'desc')
                ->with(['sender:id,name,shopName,profilePhoto,role', 'receiver:id,name,shopName,profilePhoto,role'])
                ->first();

            if (!$lastMessage) continue;

            $unreadCount = Message::where('senderId', $otherId)
                ->where('receiverId', $userId)
                ->where('read', false)
                ->count();

            $isSender = $lastMessage->senderId === $userId;
            $otherUser = $isSender ? $lastMessage->receiver : $lastMessage->sender;

            $isSystem = ($otherUser && in_array($otherUser->role, ['admin', 'superadmin'], true))
                || ($otherUser && str_contains(strtolower($otherUser->name ?? ''), 'lumbarong'));

            if ($isSystem) {
                $displayName = 'LumBarong';
                $profileImage = asset('images/logo-icon.png');
                $role = 'system';
            } elseif ($otherUser && $otherUser->role === 'seller' && !empty($otherUser->shopName)) {
                $displayName = $otherUser->shopName;
                $profileImage = $otherUser->profile_photo_url ?? ($otherUser->profilePhoto ? (str_starts_with($otherUser->profilePhoto, 'http') || str_starts_with($otherUser->profilePhoto, '/') ? $otherUser->profilePhoto : asset('storage/' . $otherUser->profilePhoto)) : null);
                $role = 'seller';
            } else {
                $displayName = $otherUser->name ?? 'Artisan';
                $profileImage = $otherUser ? ($otherUser->profile_photo_url ?? ($otherUser->profilePhoto ? (str_starts_with($otherUser->profilePhoto, 'http') || str_starts_with($otherUser->profilePhoto, '/') ? $otherUser->profilePhoto : asset('storage/' . $otherUser->profilePhoto)) : null)) : null;
                $role = $otherUser->role ?? 'customer';
            }

            $conversations[] = [
                'otherUser' => [
                    'id' => $otherUser->id ?? $otherId,
                    'name' => $displayName,
                    'profileImage' => $profileImage,
                    'role' => $role,
                    'isSystem' => $isSystem,
                ],
                'lastMessage' => [
                    'body' => $lastMessage->content,
                    'content' => $lastMessage->content,
                ],
                'timestamp' => $lastMessage->createdAt ? ($lastMessage->createdAt instanceof \Carbon\Carbon ? $lastMessage->createdAt->toISOString() : \Carbon\Carbon::parse($lastMessage->createdAt)->toISOString()) : null,
                'unreadCount' => $unreadCount
            ];
        }

        // Sort by timestamp desc
        usort($conversations, function ($a, $b) {
            return strtotime($b['timestamp'] ?? '') - strtotime($a['timestamp'] ?? '');
        });

        return response()->json($conversations);
    }

    /**
     * Mark a conversation as read.
     */
    public function markAsRead(string $otherUserId)
    {
        Message::where('senderId', $otherUserId)
            ->where('receiverId', Auth::id())
            ->where('read', false)
            ->update(['read' => true]);

        return response()->json(['message' => 'Marked as read']);
    }

    /**
     * Delete a conversation.
     */
    public function destroy(string $otherUserId)
    {
        $userId = Auth::id();
        Message::where(function ($q) use ($userId, $otherUserId) {
                $q->where('senderId', $userId)->where('receiverId', $otherUserId);
            })->orWhere(function ($q) use ($userId, $otherUserId) {
                $q->where('senderId', $otherUserId)->where('receiverId', $userId);
            })->delete();

        return response()->json(['message' => 'Conversation deleted successfully']);
    }

    /**
     * Show the seller chat view dashboard.
     */
    public function sellerChatView(Request $request)
    {
        $autoOpenUserId   = $request->query('userId');
        $autoOpenUserName = $request->query('name', 'Customer');
        return view('seller.chat.index', compact('autoOpenUserId', 'autoOpenUserName'));
    }

    /**
     * Default pre-made quick reply templates for artisans responding to customers.
     */
    public static function defaultQuickReplies(): array
    {
        return [
            [
                'id'       => 'qr-order-1',
                'title'    => 'Order Confirmation & Thanks',
                'category' => 'Order Updates',
                'text'     => "Mabuhay! Thank you for your order. We have received your order details and are carefully preparing your handcrafted Barong piece."
            ],
            [
                'id'       => 'qr-order-2',
                'title'    => 'Tailoring / Embroidery In Progress',
                'category' => 'Order Updates',
                'text'     => "Your piece is currently in the hands of our Lumban artisans for delicate embroidery and tailored craftsmanship. We will notify you once completed!"
            ],
            [
                'id'       => 'qr-order-3',
                'title'    => 'Quality Passed & Ready for Pickup',
                'category' => 'Order Updates',
                'text'     => "Great news! Your Barong has successfully passed quality inspection, is securely packaged, and is scheduled for courier dispatch."
            ],
            [
                'id'       => 'qr-order-4',
                'title'    => 'Handed Over to Courier',
                'category' => 'Order Updates',
                'text'     => "Your package has been picked up by the courier. You may track its live delivery status in your LumBarong Orders dashboard."
            ],
            [
                'id'       => 'qr-inq-1',
                'title'    => 'In Stock & Ready to Ship',
                'category' => 'Customer Inquiries',
                'text'     => "Mabuhay! Yes, this heritage piece is currently in stock and ready to ship. Please let us know if you need any sizing or styling guidance!"
            ],
            [
                'id'       => 'qr-inq-2',
                'title'    => 'Fabric & Material Details',
                'category' => 'Customer Inquiries',
                'text'     => "This piece is crafted from premium authentic fabric featuring traditional heritage Lumban embroidery. It is breathable and elegant for formal occasions."
            ],
            [
                'id'       => 'qr-inq-3',
                'title'    => 'Made-to-Order Lead Time',
                'category' => 'Customer Inquiries',
                'text'     => "Our custom made-to-order pieces typically require 5 to 7 working days to handcraft, embroider, and tailor before dispatch."
            ],
            [
                'id'       => 'qr-inq-4',
                'title'    => 'Care & Washing Guide',
                'category' => 'Customer Inquiries',
                'text'     => "We recommend gentle hand-washing in cool water with mild detergent or professional dry cleaning. Do not wring or twist. Iron while slightly damp."
            ],
            [
                'id'       => 'qr-size-1',
                'title'    => 'Size Guide & Measurement Request',
                'category' => 'Sizing & Fitting',
                'text'     => "To ensure your best fit, please refer to our Size Chart, or send us your measurements in cm: Chest, Shoulder width, Garment length, and Sleeve length."
            ],
            [
                'id'       => 'qr-size-2',
                'title'    => 'Custom Fit Available',
                'category' => 'Sizing & Fitting',
                'text'     => "Yes, we accommodate customized tailoring! Please reply with your desired shoulder width, chest circumference, and sleeve length."
            ]
        ];
    }

    /**
     * Get seller pre-made quick reply templates.
     */
    public function getQuickReplies(Request $request)
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $setting = SystemSetting::where('key', "seller_quick_replies_{$userId}")->first();
        $replies = $setting ? $setting->value : null;

        if (!is_array($replies) || empty($replies)) {
            $replies = static::defaultQuickReplies();
        }

        return response()->json($replies);
    }

    /**
     * Save seller pre-made quick reply templates.
     */
    public function saveQuickReplies(Request $request)
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $replies = $request->input('replies');
        if (!is_array($replies)) {
            return response()->json(['message' => 'Invalid replies data'], 422);
        }

        $cleaned = [];
        foreach ($replies as $idx => $item) {
            $title = trim((string)($item['title'] ?? ''));
            $text  = trim((string)($item['text'] ?? ($item['content'] ?? '')));
            $cat   = trim((string)($item['category'] ?? 'General'));
            if (!$title && !$text) continue;

            $cleaned[] = [
                'id'       => (string)($item['id'] ?? ('qr-' . ($idx + 1) . '-' . time())),
                'title'    => $title ?: 'Template ' . ($idx + 1),
                'category' => $cat ?: 'General',
                'text'     => $text,
            ];
        }

        SystemSetting::updateOrCreate(
            ['key' => "seller_quick_replies_{$userId}"],
            ['value' => $cleaned]
        );

        return response()->json([
            'message' => 'Quick replies updated successfully',
            'replies' => $cleaned
        ]);
    }

    /**
     * Reset seller pre-made quick replies to default artisan templates.
     */
    public function resetQuickReplies(Request $request)
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $defaults = static::defaultQuickReplies();
        SystemSetting::updateOrCreate(
            ['key' => "seller_quick_replies_{$userId}"],
            ['value' => $defaults]
        );

        return response()->json([
            'message' => 'Quick replies reset to defaults',
            'replies' => $defaults
        ]);
    }
}
