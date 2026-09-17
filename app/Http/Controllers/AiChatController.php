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
            'active_tab' => 'nullable|string|max:100',
        ]);

        $user = $request->user('sanctum') ?? Auth::user();
        $message = trim($validated['message']);
        $activeTab = $validated['active_tab'] ?? $validated['page_context'] ?? 'browse';

        $response = $aiService->chat($message, $user, [
            'page_context' => $validated['page_context'] ?? null,
            'active_tab' => $activeTab,
        ]);

        // Maintain conversation history (session or memory fallback)
        $history = session()->get('ai_chat_history', []);
        $history[] = ['role' => 'user', 'content' => $message, 'time' => now()->format('H:i')];
        $history[] = ['role' => 'assistant', 'content' => $response['reply'], 'time' => now()->format('H:i')];

        if (count($history) > 12) {
            $history = array_slice($history, -12);
        }

        session()->put('ai_chat_history', $history);

        return response()->json([
            'success' => $response['success'] ?? true,
            'reply' => $response['reply'],
            'user_role' => $response['role'],
            'history' => $history,
            'suggestions' => $this->getSuggestions($activeTab),
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $history = session()->get('ai_chat_history', []);
        $user = $request->user('sanctum') ?? Auth::user();
        $role = $user ? $user->role : 'GUEST';
        $activeTab = $request->query('active_tab', 'browse');

        return response()->json([
            'history' => $history,
            'user_role' => $role,
            'user_name' => $user ? $user->name : 'Guest Visitor',
            'quota' => $user ? [
                'limit' => $user->getListingLimit(),
                'used' => $user->getActiveListingCount(),
            ] : null,
            'suggestions' => $this->getSuggestions($activeTab),
        ]);
    }

    public function clear(): JsonResponse
    {
        session()->forget('ai_chat_history');
        return response()->json(['success' => true]);
    }

    /**
     * Context-sensitive suggestion chips based on active navigation tab.
     */
    protected function getSuggestions(?string $tab): array
    {
        switch ($tab) {
            case 'pricing':
                return [
                    "What payment gateways are supported in Ethiopia?",
                    "How do quarterly and yearly discounts work?",
                    "What happens when I hit my 20-listing quota?",
                ];
            case 'my-listings':
                return [
                    "Why is my listing pending approval?",
                    "How do I edit or resubmit a rejected listing?",
                    "Can I sell items without a separate vendor account?",
                ];
            case 'crm-leads':
                return [
                    "How does the 3-stage CRM pipeline work?",
                    "How are buyer inquiries converted into leads?",
                    "How do I add follow-up notes to a customer?",
                ];
            case 'requirements':
                return [
                    "How do I post a Buyer Requirement?",
                    "Can dealers contact me directly with matching cars?",
                    "Are budgets specified in Ethiopian Birr?",
                ];
            case 'messages':
                return [
                    "How does in-app buyer-seller chat work?",
                    "Are listing details attached to conversations?",
                    "Can I share phone numbers safely?",
                ];
            case 'admin':
                return [
                    "How do I approve or reject pending listings?",
                    "Where do I update subscription pricing?",
                    "How do I audit payment gateway transactions?",
                ];
            default:
                return [
                    "Can I sell without a separate seller account?",
                    "How do I find a car or apartment in Addis Ababa?",
                    "What payment options are supported in ETB?",
                    "How does the listing quota work?",
                ];
        }
    }
}
