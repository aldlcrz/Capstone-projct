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
        $request->validate([
            'receiverId' => 'required|exists:users,id',
            'content' => 'required|string',
        ]);

        $senderId = Auth::id();
        
        $message = Message::create([
            'senderId' => $senderId,
            'receiverId' => $request->receiverId,
            'content' => $request->content,
            'read' => false,
        ]);

        $populatedMessage = Message::with('sender:id,name,role,profilePhoto')->find($message->id);

        // REAL-TIME: In a pure PHP system, we might use Pusher or database polling.
        // For now, we've fulfilled the "turn to PHP" part by porting the logic.

        return response()->json($populatedMessage, 201);
    }

    /**
     * Get conversation with a specific user.
     */
    public function getConversation($otherUserId)
    {
        $userId = Auth::id();

        $messages = Message::where(function ($query) use ($userId, $otherUserId) {
                $query->where('senderId', $userId)->where('receiverId', $otherUserId);
            })->orWhere(function ($query) use ($userId, $otherUserId) {
                $query->where('senderId', $otherUserId)->where('receiverId', $userId);
            })
            ->orderBy('createdAt', 'asc')
            ->with(['sender:id,name,profilePhoto,role', 'receiver:id,name,profilePhoto,role'])
            ->get();

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
                    'id' => $otherUser->id,
                    'name' => $otherUser->name,
                    'profileImage' => $otherUser->profilePhoto,
                    'role' => $otherUser->role,
                ],
                'lastMessage' => $lastMessage->content,
                'timestamp' => $lastMessage->createdAt,
                'unreadCount' => $unreadCount
            ];
        }

        // Sort by timestamp desc
        usort($conversations, function ($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        return response()->json($conversations);
    }

    /**
     * Mark a conversation as read.
     */
    public function markAsRead($otherUserId)
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
    public function destroy($otherUserId)
    {
        $userId = Auth::id();
        Message::where(function ($q) use ($userId, $otherUserId) {
                $q->where('senderId', $userId)->where('receiverId', $otherUserId);
            })->orWhere(function ($q) use ($userId, $otherUserId) {
                $q->where('senderId', $otherUserId)->where('receiverId', $userId);
            })->delete();

        return response()->json(['message' => 'Conversation deleted successfully']);
    }
}
