<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCrmLeadStatusRequest;
use App\Models\CrmLead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmLeadController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = CrmLead::with(['listing.primaryImage', 'buyer.profile'])
            ->where('user_id', $user->id);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('listing_id')) {
            $query->where('listing_id', $request->input('listing_id'));
        }

        $leads = $query->latest()->paginate($request->input('per_page', 20));

        $counts = [
            'total' => CrmLead::where('user_id', $user->id)->count(),
            'new' => CrmLead::where('user_id', $user->id)->where('status', CrmLead::STATUS_NEW)->count(),
            'contacted' => CrmLead::where('user_id', $user->id)->where('status', CrmLead::STATUS_CONTACTED)->count(),
            'closed' => CrmLead::where('user_id', $user->id)->where('status', CrmLead::STATUS_CLOSED)->count(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $leads,
            'counts' => $counts,
            'stages' => CrmLead::STAGES,
        ]);
    }

    public function updateStatus(UpdateCrmLeadStatusRequest $request, CrmLead $lead): JsonResponse
    {
        $this->authorize('update', $lead);

        $validated = $request->validated();
        $lead->update(['status' => $validated['status']]);

        return response()->json([
            'status' => 'success',
            'message' => "Lead stage moved to {$lead->status}.",
            'data' => $lead,
        ]);
    }

    public function destroy(CrmLead $lead): JsonResponse
    {
        $this->authorize('delete', $lead);

        $lead->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Lead removed from CRM pipeline.',
        ]);
    }
}

