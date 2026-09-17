<?php

namespace App\Http\Controllers;

use App\Services\AI\AIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AiChatController extends Controller
{
    public function chat(Request $request, AIService $aiService): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
            'page_context' => 'nullable|string|max:255',
        ]);

        $user = Auth::user();
        $message = trim($validated['message']);

        $response = $aiService->chat($message, $user, [
            'page_context' => $validated['page_context'] ?? null,
        ]);

        // Maintain session conversation history (capped at last 10 messages)
        $history = session()->get('ai_chat_history', []);
        $history[] = ['role' => 'user', 'content' => $message, 'time' => now()->format('H:i')];
        $history[] = ['role' => 'assistant', 'content' => $response['reply'], 'time' => now()->format('H:i')];

        if (count($history) > 10) {
            $history = array_slice($history, -10);
        }

        session()->put('ai_chat_history', $history);

        return response()->json([
            'success' => $response['success'] ?? true,
            'reply' => $response['reply'],
            'user_role' => $response['role'],
            'history' => $history,
        ]);
    }

    public function history(): JsonResponse
    {
        $history = session()->get('ai_chat_history', []);
        $user = Auth::user();
        $role = $user ? $user->role : 'GUEST';

        return response()->json([
            'history' => $history,
            'user_role' => $role,
            'user_name' => $user ? $user->name : 'Guest Visitor',
            'organization_name' => $user && $user->organization ? $user->organization->name : 'Zacma Marketplace',
        ]);
    }

    public function clear(): JsonResponse
    {
        session()->forget('ai_chat_history');
        return response()->json(['success' => true]);
    }
}
