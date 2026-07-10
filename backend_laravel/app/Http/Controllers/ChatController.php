<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use App\Models\Order;
use App\Utils\SocketUtility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function sendMessage(Request $request)
    {
        try {
            $receiverId = $request->input('receiverId');
            $content = $request->input('content');
            $sender = Auth::user();

            if (!$receiverId || !$content) {
                return response()->json(['message' => 'Receiver and content are required'], 400);
            }

            $receiverUser = User::find($receiverId);
            if (!$receiverUser) {
                return response()->json(['message' => 'Receiver not found'], 404);
            }

            $message = Message::create([
                'senderId' => $sender->id,
                'receiverId' => $receiverId,
                'content' => $content
            ]);

            $populatedMessage = Message::with('sender:id,name,role,profilePhoto')->find($message->id);

            // Socket updates
            $msgData = $populatedMessage->toArray();
            SocketUtility::emitToUser($receiverId, 'receive_message', $msgData);
            SocketUtility::emitToUser($sender->id, 'receive_message', $msgData);

            if ($sender->role === 'customer' && $receiverUser->role === 'seller') {
                SocketUtility::emitStatsUpdate([
                    'type' => 'contact_lead',
                    'sellerId' => $receiverUser->id,
                    'customerId' => $sender->id
                ]);
            }

            return response()->json($populatedMessage, 201);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Error sending message', 'error' => $e->getMessage()], 500);
        }
    }

    public function getConversation(string $otherUserId)
    {
        try {
            $userId = Auth::id();
            $messages = Message::where(function ($q) use ($userId, $otherUserId) {
                $q->where('senderId', $userId)->where('receiverId', $otherUserId);
            })->orWhere(function ($q) use ($userId, $otherUserId) {
                $q->where('senderId', $otherUserId)->where('receiverId', $userId);
            })
            ->with(['sender:id,name,profilePhoto,role', 'receiver:id,name,profilePhoto,role'])
            ->orderBy('createdAt', 'ASC')
            ->get();

            // Mark as read
            Message::where('senderId', $otherUserId)
                ->where('receiverId', $userId)
                ->where('read', false)
                ->update(['read' => true]);

            return response()->json($messages);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error fetching messages', 'error' => $e->getMessage()], 500);
        }
    }

    public function getConversations()
    {
        try {
            $userId = Auth::id();

            $messages = Message::where('senderId', $userId)
                ->orWhere('receiverId', $userId)
                ->orderBy('createdAt', 'DESC')
                ->get();

            $chattedUserIds = [];
            foreach ($messages as $msg) {
                if ($msg->senderId !== $userId) $chattedUserIds[] = $msg->senderId;
                if ($msg->receiverId !== $userId) $chattedUserIds[] = $msg->receiverId;
            }
            $chattedUserIds = array_unique($chattedUserIds);

            // Get customer IDs who have orders with this seller
            $customerIdsWithOrders = [];
            $user = Auth::user();
            if ($user && $user->role === 'seller') {
                $customerIdsWithOrders = Order::where('sellerId', $user->id)
                    ->pluck('customerId')
                    ->toArray();
            }
            $customerIdsWithOrders = array_unique($customerIdsWithOrders);

            $conversations = [];
            foreach ($chattedUserIds as $otherId) {
                $lastMessage = Message::where(function ($q) use ($userId, $otherId) {
                    $q->where('senderId', $userId)->where('receiverId', $otherId);
                })->orWhere(function ($q) use ($userId, $otherId) {
                    $q->where('senderId', $otherId)->where('receiverId', $userId);
                })
                ->with(['sender:id,name,profilePhoto,role', 'receiver:id,name,profilePhoto,role'])
                ->orderBy('createdAt', 'DESC')
                ->first();

                $unreadCount = Message::where('senderId', $otherId)
                    ->where('receiverId', $userId)
                    ->where('read', false)
                    ->count();

                if (!$lastMessage) continue;

                $isSender = $lastMessage->senderId === $userId;
                $otherUserValues = $isSender ? $lastMessage->receiver : $lastMessage->sender;
                $otherUserId = $isSender ? $lastMessage->receiverId : $lastMessage->senderId;

                $conversations[] = [
                    'otherUser' => [
                        'id' => $otherUserId,
                        'name' => $otherUserValues ? $otherUserValues->name : null,
                        'shopName' => $otherUserValues ? $otherUserValues->name : null,
                        'profileImage' => $otherUserValues ? $otherUserValues->profilePhoto : null,
                        'role' => $otherUserValues ? $otherUserValues->role : null,
                        'hasOrders' => in_array($otherUserId, $customerIdsWithOrders)
                    ],
                    'lastMessage' => $lastMessage->content,
                    'timestamp' => $lastMessage->createdAt->toIso8601String(),
                    'unreadCount' => $unreadCount
                ];
            }

            // Sort by timestamp DESC
            usort($conversations, function ($a, $b) {
                return strtotime($b['timestamp']) - strtotime($a['timestamp']);
            });

            return response()->json($conversations);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Error fetching conversations', 'error' => $e->getMessage()], 500);
        }
    }

    public function markAsRead(string $otherUserId)
    {
        try {
            $userId = Auth::id();

            Message::where('senderId', $otherUserId)
                ->where('receiverId', $userId)
                ->where('read', false)
                ->update(['read' => true]);

            return response()->json(['message' => 'Marked as read']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error marking messages as read', 'error' => $e->getMessage()], 500);
        }
    }

    public function deleteConversation(string $otherUserId)
    {
        try {
            $userId = Auth::id();

            Message::where(function ($q) use ($userId, $otherUserId) {
                $q->where('senderId', $userId)->where('receiverId', $otherUserId);
            })->orWhere(function ($q) use ($userId, $otherUserId) {
                $q->where('senderId', $otherUserId)->where('receiverId', $userId);
            })->delete();

            return response()->json(['message' => 'Conversation deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error deleting conversation', 'error' => $e->getMessage()], 500);
        }
    }
}
