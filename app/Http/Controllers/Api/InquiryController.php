<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInquiryRequest;
use App\Models\CrmLead;
use App\Models\Inquiry;
use App\Models\Listing;
use App\Notifications\InquiryReceivedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InquiryController extends Controller
{
    public function store(StoreInquiryRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user('sanctum') ?: $request->user();

        $listing = Listing::with('user')->findOrFail($validated['listing_id']);

        // Don't allow contacting oneself
        if (($user && $user->id === $listing->user_id) || 
            (strtolower($validated['email']) === strtolower($listing->user?->email ?? ''))) {
            return response()->json([
                'status' => 'error',
                'message' => 'You cannot submit an inquiry on your own listing.',
            ], 422);
        }

        $result = DB::transaction(function () use ($validated, $user, $listing) {
            // 1. Create Inquiry
            $inquiry = Inquiry::create([
                'listing_id' => $listing->id,
                'user_id' => $user?->id,
                'seller_id' => $listing->user_id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'message' => $validated['message'],
            ]);

            // 2. Automatically create CRM Lead for listing seller
            $lead = CrmLead::create([
                'inquiry_id' => $inquiry->id,
                'user_id' => $listing->user_id,
                'buyer_id' => $user?->id,
                'listing_id' => $listing->id,
                'buyer_name' => $validated['name'],
                'buyer_email' => $validated['email'],
                'buyer_phone' => $validated['phone'] ?? null,
                'message' => $validated['message'],
                'status' => CrmLead::STATUS_NEW,
            ]);

            // 3. Dispatch Notification to seller
            if ($listing->user) {
                try {
                    $listing->user->notify(new InquiryReceivedNotification($inquiry));
                } catch (\Throwable $e) {
                    Log::warning('Inquiry notification error: ' . $e->getMessage());
                }
            }

            return ['inquiry' => $inquiry, 'lead' => $lead];
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Your message has been sent to the seller successfully.',
            'data' => [
                'inquiry_id' => $result['inquiry']->id,
                'lead_id' => $result['lead']->id,
            ],
        ], 201);
    }
}
