<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Any visitor / user can contact seller
    }

    public function rules(): array
    {
        return [
            'listing_id' => 'required|exists:listings,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:30',
            'message' => 'required|string|min:5|max:5000',
        ];
    }
}

