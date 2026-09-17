<?php

namespace App\Services\AI;

use App\Models\User;
use App\Services\AI\Knowledge\CrmAndBuyerRequirementsKnowledge;
use App\Services\AI\Knowledge\FaqAndTroubleshootingKnowledge;
use App\Services\AI\Knowledge\IndustrySpecificationsKnowledge;
use App\Services\AI\Knowledge\ListingLifecycleKnowledge;
use App\Services\AI\Knowledge\PlatformKnowledge;
use App\Services\AI\Knowledge\RolesAndPermissionsKnowledge;
use App\Services\AI\Knowledge\SubscriptionsAndPaymentsKnowledge;

class SystemKnowledgeBase
{
    /**
     * Check if a user request attempts to mutate or perform destructive platform actions.
     */
    public static function isDestructiveOrMutatingRequest(string $query): bool
    {
        $q = strtolower($query);
        $dangerPatterns = [
            'delete listing', 'remove listing', 'drop database', 'delete account', 
            'change price', 'update balance', 'grant admin', 'override approval', 
            'approve my listing', 'ban user', 'refund without approval', 'delete table'
        ];

        foreach ($dangerPatterns as $pattern) {
            if (str_contains($q, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get safe refusal response for mutating/destructive requests.
     */
    public static function getSafeRefusalMessage(): string
    {
        return "I operate strictly in **Knowledge and Assistance Mode** to guide, explain, and answer questions about the Zacma platform.\n\n" .
               "I do not have authorization to directly modify system data, delete records, or alter listing statuses. " .
               "If you need to edit or delete your own listings, please navigate to the **'My Listings'** tab. " .
               "For administrative or account actions, please contact the moderation team or use the **Admin Command Center**.";
    }

    /**
     * Find the most relevant structured knowledge based on a user's query keywords.
     */
    public static function findRelevantKnowledge(string $query): string
    {
        $q = strtolower($query);

        // 1. Check for destructive action
        if (self::isDestructiveOrMutatingRequest($q)) {
            return self::getSafeRefusalMessage();
        }

        // 2. Query matching for high-signal topics
        if (str_contains($q, 'separate') || str_contains($q, 'seller account') || str_contains($q, 'can i sell') || str_contains($q, 'one account') || str_contains($q, 'buyer and seller')) {
            return RolesAndPermissionsKnowledge::getKnowledge() . "\n\n" . FaqAndTroubleshootingKnowledge::getKnowledge();
        }

        if (str_contains($q, 'quota') || str_contains($q, 'limit') || str_contains($q, 'plan') || str_contains($q, 'subscription') || str_contains($q, 'pricing') || str_contains($q, 'upgrade') || str_contains($q, 'basic') || str_contains($q, 'premium') || str_contains($q, 'pro')) {
            return SubscriptionsAndPaymentsKnowledge::getKnowledge();
        }

        if (str_contains($q, 'pay') || str_contains($q, 'chapa') || str_contains($q, 'telebirr') || str_contains($q, 'cbe') || str_contains($q, 'ebirr') || str_contains($q, 'santim') || str_contains($q, 'etb')) {
            return SubscriptionsAndPaymentsKnowledge::getKnowledge();
        }

        if (str_contains($q, 'car') || str_contains($q, 'vehicle') || str_contains($q, 'mileage') || str_contains($q, 'transmission') || str_contains($q, 'fuel')) {
            return IndustrySpecificationsKnowledge::getKnowledge();
        }

        if (str_contains($q, 'apartment') || str_contains($q, 'real estate') || str_contains($q, 'villa') || str_contains($q, 'house') || str_contains($q, 'bedroom') || str_contains($q, 'land') || str_contains($q, 'rent')) {
            return IndustrySpecificationsKnowledge::getKnowledge();
        }

        if (str_contains($q, 'pending') || str_contains($q, 'reject') || str_contains($q, 'approve') || str_contains($q, 'lifecycle') || str_contains($q, 'moderation')) {
            return ListingLifecycleKnowledge::getKnowledge();
        }

        if (str_contains($q, 'crm') || str_contains($q, 'lead') || str_contains($q, 'requirement') || str_contains($q, 'message') || str_contains($q, 'inquiry') || str_contains($q, 'chat') || str_contains($q, 'contact seller')) {
            return CrmAndBuyerRequirementsKnowledge::getKnowledge();
        }

        // Return combined FAQ & Platform overview for general queries
        return FaqAndTroubleshootingKnowledge::getKnowledge() . "\n\n" . PlatformKnowledge::getKnowledge();
    }

    /**
     * Build the complete comprehensive system prompt for LLM providers (e.g. Gemini).
     */
    public static function buildFullSystemPrompt(?User $user = null, array $pageContext = []): string
    {
        $role = $user ? $user->role : 'GUEST';
        $userName = $user ? $user->name : 'Guest Visitor';
        $userLimit = $user ? $user->getListingLimit() : 20;
        $userUsed = $user ? $user->getActiveListingCount() : 0;
        $activeTab = $pageContext['active_tab'] ?? $pageContext['page_context'] ?? 'marketplace';

        $prompt = <<<TEXT
You are Zacma AI Knowledge Assistant, the official system-wide AI copilot for the Zacma Dealership & Multi-Industry Marketplace + CRM SaaS platform in Ethiopia.

### ACTIVE USER CONTEXT:
- **User Name**: {$userName}
- **Role**: {$role}
- **Listing Quota Status**: {$userUsed} / {$userLimit} listings used
- **Current Active View / Page**: {$activeTab}

### STRICT SAFETY & ASSISTANCE INSTRUCTIONS:
1. **Knowledge & Assistance Mode Only**: You guide, explain, search, troubleshoot, and educate users on how to use Zacma. You NEVER claim to execute database deletes, status modifications, or fund adjustments yourself.
2. **One Account Model**: Always remember and clarify that every authenticated user can both BUY and SELL from their single account without registering a separate vendor account.
3. **Currency & Localization**: All prices and budgets are in Ethiopian Birr (ETB). Payment gateways are Ethiopian (Chapa, Telebirr, CBE Birr, eBirr, SantimPay).
4. **Tone & Style**: Helpful, professional, concise, structured with GitHub-style markdown, bold headings, and bullet points.

### COMPLETE SYSTEM KNOWLEDGE REPOSITORY:
TEXT;

        $prompt .= "\n\n" . PlatformKnowledge::getKnowledge();
        $prompt .= "\n\n" . RolesAndPermissionsKnowledge::getKnowledge();
        $prompt .= "\n\n" . ListingLifecycleKnowledge::getKnowledge();
        $prompt .= "\n\n" . IndustrySpecificationsKnowledge::getKnowledge();
        $prompt .= "\n\n" . CrmAndBuyerRequirementsKnowledge::getKnowledge();
        $prompt .= "\n\n" . SubscriptionsAndPaymentsKnowledge::getKnowledge();
        $prompt .= "\n\n" . FaqAndTroubleshootingKnowledge::getKnowledge();

        return $prompt;
    }
}
