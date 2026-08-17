<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use App\Models\Order;
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

        $msg = Message::with('sender:id,name,role,profilePhoto')->find($message->id);
        $res = $msg ? $msg->toArray() : [
            'id' => $message->id,
            'senderId' => $senderId,
            'receiverId' => $receiverId,
            'content' => $content,
            'read' => false,
        ];
        $res['body'] = $content;
        $res['createdAt'] = $msg?->createdAt ? $msg->createdAt->toISOString() : now()->toISOString();
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
            ->with(['sender:id,name,profilePhoto,role', 'receiver:id,name,profilePhoto,role'])
            ->get()
            ->map(function ($m) {
                $arr = $m->toArray();
                $arr['body'] = $m->content;
                $arr['createdAt'] = $m->createdAt ? $m->createdAt->toISOString() : null;
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
                ->with(['sender:id,name,profilePhoto,role', 'receiver:id,name,profilePhoto,role'])
                ->first();

            if (!$lastMessage) continue;

            $unreadCount = Message::where('senderId', $otherId)
                ->where('receiverId', $userId)
                ->where('read', false)
                ->count();

            $isSender = $lastMessage->senderId === $userId;
            $otherUser = $isSender ? $lastMessage->receiver : $lastMessage->sender;

            $conversations[] = [
                'otherUser' => [
                    'id' => $otherUser->id ?? $otherId,
                    'name' => $otherUser->name ?? 'Artisan',
                    'profileImage' => $otherUser->profilePhoto ?? null,
                    'role' => $otherUser->role ?? 'seller',
                ],
                'lastMessage' => [
                    'body' => $lastMessage->content,
                    'content' => $lastMessage->content,
                ],
                'timestamp' => $lastMessage->createdAt ? $lastMessage->createdAt->toISOString() : null,
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
}
