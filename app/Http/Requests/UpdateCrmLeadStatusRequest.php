<?php

namespace App\Http\Requests;

use App\Models\CrmLead;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCrmLeadStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $lead = $this->route('lead') ?: $this->route('crm_lead');
        return $this->user() && (!$lead || $this->user()->can('update', $lead));
    }

    public function rules(): array
    {
        return [
            'status' => 'required|string|in:' . implode(',', CrmLead::STAGES),
        ];
    }
}

