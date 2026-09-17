<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isSuperAdmin();
    }

    public function rules(): array
    {
        return [
            'reason' => 'required|string|min:5|max:1000',
        ];
    }
}

