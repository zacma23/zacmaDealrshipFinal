<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function conversations(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $messages = Message::with(['sender:id,name', 'recipient:id,name', 'listing:id,title,type,price'])
            ->where('sender_id', $userId)
            ->orWhere('recipient_id', $userId)
            ->latest()
            ->get();

        // Group by other user
        $conversations = $messages->groupBy(function ($msg) use ($userId) {
            return $msg->sender_id === $userId ? $msg->recipient_id : $msg->sender_id;
        })->map(function ($msgs, $otherUserId) use ($userId) {
            $lastMsg = $msgs->first();
            $otherUser = $lastMsg->sender_id === $userId ? $lastMsg->recipient : $lastMsg->sender;
            $unreadCount = $msgs->where('recipient_id', $userId)->whereNull('read_at')->count();

            return [
                'user' => $otherUser,
                'last_message' => $lastMsg->message,
                'listing' => $lastMsg->listing,
                'unread_count' => $unreadCount,
                'created_at' => $lastMsg->created_at->toIso8601String(),
            ];
        })->values();

        return response()->json([
            'status' => 'success',
            'data' => $conversations,
        ]);
    }

    public function thread(Request $request, User $user): JsonResponse
    {
        $currentUserId = $request->user()->id;

        // Mark incoming messages as read
        Message::where('sender_id', $user->id)
            ->where('recipient_id', $currentUserId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messages = Message::with(['listing:id,title,type,price'])
            ->where(function ($q) use ($currentUserId, $user) {
                $q->where('sender_id', $currentUserId)->where('recipient_id', $user->id);
            })
            ->orWhere(function ($q) use ($currentUserId, $user) {
                $q->where('sender_id', $user->id)->where('recipient_id', $currentUserId);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $messages,
        ]);
    }

    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'listing_id' => 'nullable|exists:listings,id',
            'message' => 'required|string|max:5000',
        ]);

        if ($validated['recipient_id'] === $request->user()->id) {
            return response()->json(['status' => 'error', 'message' => 'Cannot message yourself.'], 422);
        }

        $message = Message::create([
            'sender_id' => $request->user()->id,
            'recipient_id' => $validated['recipient_id'],
            'listing_id' => $validated['listing_id'] ?? null,
            'message' => $validated['message'],
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $message->load(['sender:id,name', 'listing:id,title']),
        ], 201);
    }
}
