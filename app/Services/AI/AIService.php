<?php

namespace App\Services\AI;

use App\Models\AiRequest;
use App\Models\AiUsageSummary;
use App\Models\Category;
use App\Models\CategoryField;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Listing;
use App\Models\Organization;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AIService
{
    protected AIProviderInterface $provider;

    public function __construct(?AIProviderInterface $provider = null)
    {
        $this->provider = $provider ?? new GeminiProvider;
    }

    public function setProvider(AIProviderInterface $provider): void
    {
        $this->provider = $provider;
    }

    /**
     * AI CRM Assistant - answering user questions strictly scoped to authorized tenant data
     */
    public function queryAssistant(string $query, ?int $orgId = null, ?User $user = null): string
    {
        $user = $user ?? Auth::user();
        $isSuperAdmin = $user && $user->isSuperAdmin();
        $effectiveOrgId = $isSuperAdmin ? $orgId : ($user ? $user->organization_id : $orgId);

        // Security check: Non-superadmin cannot query other organization's data
        if (!$isSuperAdmin && $orgId !== null && (int)$orgId !== (int)$user->organization_id) {
            return "Error: You are not authorized to access data for this organization.";
        }

        // Controlled tool execution based on intent analysis (Zero Raw SQL)
        $queryLower = strtolower($query);
        $contextData = [];

        if (str_contains($queryLower, 'hot lead') || str_contains($queryLower, 'leads')) {
            $contextData['leads'] = $this->toolGetLeadsSummary($effectiveOrgId);
        }

        if (str_contains($queryLower, 'follow-up') || str_contains($queryLower, 'follow up') || str_contains($queryLower, 'task')) {
            $contextData['follow_ups'] = $this->toolGetFollowUps($effectiveOrgId);
        }

        if (str_contains($queryLower, 'inactive') || str_contains($queryLower, 'not contacted')) {
            $contextData['inactive_leads'] = $this->toolGetInactiveLeads($effectiveOrgId, 3);
        }

        if (str_contains($queryLower, 'salesperson') || str_contains($queryLower, 'agent') || str_contains($queryLower, 'conversion')) {
            $contextData['agent_performance'] = $this->toolGetAgentPerformance($effectiveOrgId);
        }

        if (str_contains($queryLower, 'product') || str_contains($queryLower, 'listing') || str_contains($queryLower, 'most lead')) {
            $contextData['top_listings'] = $this->toolGetTopListings($effectiveOrgId);
        }

        // Always provide a high-level summary if no specific tool matched
        if (empty($contextData)) {
            $contextData['overview'] = [
                'total_leads' => Lead::where('organization_id', $effectiveOrgId)->count(),
                'total_contacts' => Contact::where('organization_id', $effectiveOrgId)->count(),
                'open_deals_value' => Deal::where('organization_id', $effectiveOrgId)->sum('value'),
                'pending_tasks' => Task::where('organization_id', $effectiveOrgId)->where('status', 'pending')->count(),
            ];
        }

        $systemPrompt = "You are Zacma AI CRM Assistant. You assist dealership and marketplace staff with insights.
Answer clearly, concisely, and professionally based ONLY on the provided authorized data context.
Do NOT invent false records or claim access to unauthorized systems.";

        $fullPrompt = "USER QUERY: {$query}\n\nAUTHORIZED CONTEXT DATA:\n" . json_encode($contextData, JSON_PRETTY_PRINT);

        $result = $this->provider->generateText($fullPrompt, [
            'system_instruction' => $systemPrompt,
            'max_tokens' => 600,
        ]);

        $this->logUsage($effectiveOrgId, $user?->id, 'crm_assistant', $result['tokens']);

        return $result['text'];
    }

    /**
     * Assistive Lead Scoring (Hot, Warm, Cold)
     */
    public function scoreLead(Lead $lead): array
    {
        $contact = $lead->contact;
        $activitiesCount = $lead->activities()->count();
        $tasksCount = $lead->tasks()->count();
        $daysSinceContact = $lead->last_contact_at ? now()->diffInDays($lead->last_contact_at) : 10;
        
        $score = 50; // base score

        // Scoring rules
        if ($lead->status === Lead::STATUS_QUALIFIED) $score += 20;
        if ($lead->status === Lead::STATUS_PROPOSAL || $lead->status === Lead::STATUS_NEGOTIATION) $score += 30;
        if ($lead->priority === Lead::PRIORITY_URGENT) $score += 15;
        if ($lead->priority === Lead::PRIORITY_HIGH) $score += 10;
        if ($activitiesCount >= 3) $score += 15;
        if ($lead->appointments()->count() > 0) $score += 20;

        // Penalties for inactivity
        if ($daysSinceContact > 7) $score -= 20;
        elseif ($daysSinceContact > 3) $score -= 10;

        $score = max(5, min(99, $score));

        $category = Lead::SCORE_COLD;
        if ($score >= 70) {
            $category = Lead::SCORE_HOT;
        } elseif ($score >= 40) {
            $category = Lead::SCORE_WARM;
        }

        $explanation = "Calculated score {$score}/100 ({$category}). Factors: {$activitiesCount} interactions logged, status '{$lead->status}', priority '{$lead->priority}', last contact {$daysSinceContact} days ago.";

        $lead->update([
            'score' => $score,
            'score_category' => $category,
            'score_explanation' => $explanation,
        ]);

        return [
            'score' => $score,
            'category' => $category,
            'explanation' => $explanation,
        ];
    }

    /**
     * Customer 360 AI Summary Generator
     */
    public function summarizeContact(Contact $contact): string
    {
        $dealsCount = $contact->deals()->count();
        $dealsWon = $contact->deals()->whereHas('stage', fn($q) => $q->where('is_closed_won', true))->count();
        $leadsCount = $contact->leads()->count();
        $activities = $contact->activities()->take(5)->get(['type', 'subject', 'occurred_at'])->toArray();

        $context = [
            'contact_name' => $contact->full_name,
            'contact_type' => $contact->contact_type,
            'status' => $contact->status,
            'leads_count' => $leadsCount,
            'deals_count' => $dealsCount,
            'deals_won' => $dealsWon,
            'recent_activities' => $activities,
            'last_contact' => $contact->last_contact_at?->diffForHumans(),
        ];

        $prompt = "Summarize the history, current state, and recommended next step for this CRM contact in 3-4 sentences:\n" . json_encode($context);

        $result = $this->provider->generateText($prompt, [
            'system_instruction' => "You are an executive CRM sales assistant. Deliver high-signal customer summaries.",
            'max_tokens' => 300,
        ]);

        $summary = $result['text'];
        $contact->update(['ai_summary' => $summary]);

        $this->logUsage($contact->organization_id, Auth::id(), 'customer_summary', $result['tokens']);

        return $summary;
    }

    /**
     * Draft follow-up SMS or Email (Human approval required before dispatch)
     */
    public function draftCommunication(string $channel, Contact $contact, ?string $instruction = null): string
    {
        $prompt = "Draft a professional, friendly {$channel} message to customer {$contact->full_name}.\n" .
                  "Context: Customer status is '{$contact->status}', type '{$contact->contact_type}'.\n" .
                  ($instruction ? "Specific Instruction: {$instruction}\n" : "Goal: Check in on their interest and propose a convenient time for a call or visit.\n") .
                  "Note: Keep SMS under 160 characters. If Email, include subject line and greeting.";

        $result = $this->provider->generateText($prompt, [
            'max_tokens' => 400,
        ]);

        $this->logUsage($contact->organization_id, Auth::id(), "draft_{$channel}", $result['tokens']);

        return $result['text'];
    }

    /**
     * Generate Listing Description
     */
    public function generateListingDescription(string $title, string $categoryName, array $attributes = []): string
    {
        $prompt = "Write an engaging, SEO-friendly marketplace listing description for: '{$title}' in category '{$categoryName}'.\n" .
                  "Key Specifications:\n" . json_encode($attributes, JSON_PRETTY_PRINT) . "\n" .
                  "Tone: Professional, persuasive, highlighting reliability, condition, and value.";

        $result = $this->provider->generateText($prompt, [
            'max_tokens' => 400,
        ]);

        $this->logUsage(Auth::user()?->organization_id, Auth::id(), 'listing_description', $result['tokens']);

        return $result['text'];
    }

    // ==========================================
    // CONTROLLED TENANT QUERY TOOLS (NO RAW SQL)
    // ==========================================

    protected function toolGetLeadsSummary(?int $orgId): array
    {
        $query = Lead::with(['contact', 'listing'])->orderBy('score', 'desc');
        if ($orgId) $query->where('organization_id', $orgId);
        
        return $query->take(8)->get()->map(function ($l) {
            return [
                'id' => $l->id,
                'contact' => $l->contact ? $l->contact->full_name : 'Unknown',
                'listing' => $l->listing ? $l->listing->title : 'General inquiry',
                'status' => $l->status,
                'priority' => $l->priority,
                'score' => $l->score,
                'score_category' => $l->score_category,
                'estimated_value' => "{$l->currency} {$l->estimated_value}",
            ];
        })->toArray();
    }

    protected function toolGetFollowUps(?int $orgId): array
    {
        $query = Task::with('contact')->where('status', 'pending');
        if ($orgId) $query->where('organization_id', $orgId);

        return $query->whereNotNull('due_date')
            ->orderBy('due_date', 'asc')
            ->take(8)
            ->get()
            ->map(function ($t) {
                return [
                    'task' => $t->title,
                    'contact' => $t->contact?->full_name,
                    'due' => $t->due_date?->toDateTimeString(),
                    'is_overdue' => $t->isOverdue(),
                ];
            })->toArray();
    }

    protected function toolGetInactiveLeads(?int $orgId, int $days = 3): array
    {
        $threshold = now()->subDays($days);
        $query = Lead::with('contact')->where('last_contact_at', '<', $threshold)
            ->whereNotIn('status', [Lead::STATUS_WON, Lead::STATUS_LOST, Lead::STATUS_CLOSED]);
        if ($orgId) $query->where('organization_id', $orgId);

        return $query->take(8)->get()->map(function ($l) {
            return [
                'lead_id' => $l->id,
                'contact' => $l->contact?->full_name,
                'last_contact' => $l->last_contact_at?->diffForHumans(),
                'status' => $l->status,
            ];
        })->toArray();
    }

    protected function toolGetAgentPerformance(?int $orgId): array
    {
        $query = User::whereIn('role', [User::ROLE_SALES_AGENT, User::ROLE_MANAGER, User::ROLE_ORGANIZATION_ADMIN]);
        if ($orgId) $query->where('organization_id', $orgId);

        return $query->take(6)->get()->map(function ($u) use ($orgId) {
            $leadsQuery = Lead::where('assigned_user_id', $u->id);
            $dealsQuery = Deal::where('assigned_user_id', $u->id);
            if ($orgId) {
                $leadsQuery->where('organization_id', $orgId);
                $dealsQuery->where('organization_id', $orgId);
            }

            $totalLeads = $leadsQuery->count();
            $wonDeals = (clone $dealsQuery)->whereHas('stage', fn($q) => $q->where('is_closed_won', true))->count();

            return [
                'agent_name' => $u->name,
                'assigned_leads' => $totalLeads,
                'won_deals' => $wonDeals,
                'conversion_rate' => $totalLeads > 0 ? round(($wonDeals / $totalLeads) * 100, 1) . '%' : '0%',
            ];
        })->toArray();
    }

    protected function toolGetTopListings(?int $orgId): array
    {
        $query = Listing::with('category')->where('status', Listing::STATUS_PUBLISHED)
            ->orderBy('inquiries_count', 'desc');
        if ($orgId) $query->where('organization_id', $orgId);

        return $query->take(5)->get()->map(function ($l) {
            return [
                'title' => $l->title,
                'category' => $l->category?->name,
                'price' => "{$l->currency} {$l->price}",
                'views' => $l->views_count,
                'inquiries' => $l->inquiries_count,
            ];
        })->toArray();
    }

    protected function logUsage(?int $orgId, ?int $userId, string $feature, int $tokens): void
    {
        if (!$orgId) return;

        $cost = round($tokens * 0.0000005, 6); // standard rate estimation

        AiRequest::create([
            'organization_id' => $orgId,
            'user_id' => $userId,
            'feature' => $feature,
            'provider' => $this->provider->getProviderName(),
            'model' => 'gemini-1.5-flash',
            'prompt_tokens' => (int) ($tokens * 0.6),
            'completion_tokens' => (int) ($tokens * 0.4),
            'total_tokens' => $tokens,
            'estimated_cost' => $cost,
            'status' => 'success',
        ]);

        $ym = now()->format('Y-m');
        $summary = AiUsageSummary::firstOrCreate(
            ['organization_id' => $orgId, 'year_month' => $ym],
            ['total_requests' => 0, 'total_tokens' => 0, 'total_cost' => 0]
        );
        $summary->increment('total_requests');
        $summary->increment('total_tokens', $tokens);
        $summary->increment('total_cost', $cost);
    }

    /**
     * AI-Powered Listing Detection from Image and/or text hints.
     * Analyzes image, selects category, estimates price, writes description, and maps dynamic fields.
     */
    public function detectListingFromImage($image = null, ?string $hints = null, ?int $preferredCategoryId = null, ?int $orgId = null, ?User $user = null): array
    {
        $categories = Category::with('fields.options')->whereNull('parent_id')->get();
        $catCatalog = [];
        foreach ($categories as $cat) {
            $fields = $cat->fields->map(fn($f) => $f->name . ' (' . $f->field_type . ')')->implode(', ');
            $catCatalog[] = "- Category: {$cat->name} (slug: {$cat->slug}), Available Fields: [{$fields}]";
        }
        $catCatalogStr = implode("\n", $catCatalog);

        $prompt = "You are Zacma AI Marketplace Vision & Listing Specialist.\n";
        $prompt .= "Task: Inspect the provided product image and optional hints to generate a complete, high-converting listing.\n";
        if ($hints) {
            $prompt .= "User Hints: {$hints}\n";
        }
        if ($preferredCategoryId) {
            $prefCat = Category::find($preferredCategoryId);
            if ($prefCat) {
                $prompt .= "User Selected Category: {$prefCat->name} ({$prefCat->slug})\n";
            }
        }
        $prompt .= "Allowed Marketplace Categories and their dynamic custom fields:\n{$catCatalogStr}\n\n";
        $prompt .= "Output strictly valid JSON with this schema:\n";
        $prompt .= "{\n";
        $prompt .= "  \"title\": \"Attractive, professional title (e.g. 2023 Toyota RAV4 Hybrid XLE or Modern 4-Bed Villa)\",\n";
        $prompt .= "  \"category_slug\": \"one of the category slugs listed above\",\n";
        $prompt .= "  \"suggested_price\": numeric_price_estimate,\n";
        $prompt .= "  \"currency\": \"ETB\",\n";
        $prompt .= "  \"price_type\": \"fixed\" or \"negotiable\",\n";
        $prompt .= "  \"city\": \"Addis Ababa\",\n";
        $prompt .= "  \"address\": \"Specific neighborhood, area, or showroom\",\n";
        $prompt .= "  \"description\": \"Rich 2-3 paragraph selling description with specs, condition, and call to action.\",\n";
        $prompt .= "  \"fields\": { \"FieldName\": \"Detected Value\" },\n";
        $prompt .= "  \"features\": [\"Key feature 1\", \"Key feature 2\", \"Key feature 3\"]\n";
        $prompt .= "}";

        $options = [
            'json_mode' => true,
            'temperature' => 0.4,
            'max_tokens' => 1200,
        ];

        if ($image) {
            if (is_string($image) && file_exists($image)) {
                $options['image_base64'] = base64_encode(file_get_contents($image));
                $options['image_mime'] = mime_content_type($image) ?: 'image/jpeg';
            } elseif (is_object($image) && method_exists($image, 'getRealPath')) {
                $options['image_base64'] = base64_encode(file_get_contents($image->getRealPath()));
                $options['image_mime'] = $image->getMimeType() ?: 'image/jpeg';
            }
        }

        $result = $this->provider->generateText($prompt, $options);
        $rawText = trim($result['text'] ?? '');

        // Clean any accidental markdown code block formatting
        if (str_starts_with($rawText, '```json')) {
            $rawText = substr($rawText, 7);
        } elseif (str_starts_with($rawText, '```')) {
            $rawText = substr($rawText, 3);
        }
        if (str_ends_with($rawText, '```')) {
            $rawText = substr($rawText, 0, -3);
        }
        $rawText = trim($rawText);

        $parsed = json_decode($rawText, true);
        if (!is_array($parsed)) {
            $parsed = [
                'title' => $hints ?: 'Quality Marketplace Listing',
                'category_slug' => 'vehicles',
                'suggested_price' => 1000000,
                'currency' => 'ETB',
                'price_type' => 'fixed',
                'city' => 'Addis Ababa',
                'description' => $rawText ?: 'Verified quality listing with full specifications available upon inquiry.',
                'fields' => [],
                'features' => [],
            ];
        }

        // Match category
        $detectedCat = null;
        if (!empty($parsed['category_slug'])) {
            $detectedCat = Category::where('slug', $parsed['category_slug'])->first();
        }
        if (!$detectedCat && $preferredCategoryId) {
            $detectedCat = Category::find($preferredCategoryId);
        }
        if (!$detectedCat) {
            $detectedCat = Category::first();
        }

        // Map custom fields to field IDs
        $mappedFieldValues = [];
        if ($detectedCat && !empty($parsed['fields']) && is_array($parsed['fields'])) {
            $detectedCat->load('fields.options');
            foreach ($detectedCat->fields as $field) {
                foreach ($parsed['fields'] as $key => $val) {
                    if (strcasecmp($field->name, $key) === 0 || str_contains(strtolower($key), strtolower($field->name)) || str_contains(strtolower($field->name), strtolower($key))) {
                        $mappedFieldValues[$field->id] = is_array($val) ? implode(', ', $val) : (string)$val;
                        break;
                    }
                }
            }
        }

        $parsed['category_id'] = $detectedCat?->id;
        $parsed['category_name'] = $detectedCat?->name;
        $parsed['mapped_field_values'] = $mappedFieldValues;

        // Record AI Usage
        if ($orgId) {
            $this->logUsage($orgId, $user?->id, 'listing_detection', $result['tokens'] ?? 300);
        }

        return $parsed;
    }

    /**
     * Universal Role-Aware Contextual AI Chat
     */
    public function chat(string $message, ?User $user = null, array $pageContext = []): array
    {
        $role = $user ? $user->role : 'GUEST';
        $orgId = $user ? $user->organization_id : null;

        // Build comprehensive, unified system prompt with full platform knowledge
        $systemPrompt = SystemKnowledgeBase::buildFullSystemPrompt($user, $pageContext);

        // Live Context Augmentation for Staff/Admins
        $contextData = "";
        if ($user && in_array($role, ['ORGANIZATION_ADMIN', 'MANAGER', 'SALES_AGENT', 'STAFF'])) {
            $msgLower = strtolower($message);
            if (str_contains($msgLower, 'lead') || str_contains($msgLower, 'hot') || str_contains($msgLower, 'prospect')) {
                $leads = $this->toolGetLeadsSummary($orgId);
                $contextData .= "\n[Live Leads Data]: " . json_encode($leads);
            }
            if (str_contains($msgLower, 'task') || str_contains($msgLower, 'follow') || str_contains($msgLower, 'due')) {
                $tasks = $this->toolGetFollowUps($orgId);
                $contextData .= "\n[Live Follow-up Tasks]: " . json_encode($tasks);
            }
            if (str_contains($msgLower, 'inactive') || str_contains($msgLower, 'stalled')) {
                $inactive = $this->toolGetInactiveLeads($orgId, 3);
                $contextData .= "\n[Inactive Leads]: " . json_encode($inactive);
            }
        }

        $fullPrompt = "User Message: {$message}\n{$contextData}";

        $result = $this->provider->generateText($fullPrompt, [
            'system_instruction' => $systemPrompt,
            'temperature' => 0.6,
            'max_tokens' => 650,
        ]);

        if ($orgId) {
            $this->logUsage($orgId, $user?->id, 'chat_assistant', $result['tokens'] ?? 150);
        }

        return [
            'success' => $result['success'] ?? true,
            'reply' => $result['text'] ?? "I'm here to assist you with any questions about Zacma Marketplace and CRM services.",
            'role' => $role,
        ];
    }
}
