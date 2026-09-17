<?php

namespace App\Http\Requests;

use App\Models\Listing;
use Illuminate\Foundation\Http\FormRequest;

class StoreListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->canCreateListing();
    }

    protected function failedAuthorization(): void
    {
        throw new \Illuminate\Http\Exceptions\HttpResponseException(
            response()->json([
                'status' => 'error',
                'message' => 'Listing limit reached for your plan. Please upgrade to create more listings.',
                'upgrade_required' => true,
            ], 422)
        );
    }

    public function rules(): array
    {
        return [
            'type' => 'required|string|in:' . implode(',', [
                Listing::TYPE_VEHICLE,
                Listing::TYPE_REAL_ESTATE,
                Listing::TYPE_APARTMENT,
                Listing::TYPE_PRODUCT,
            ]),
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:10000',
            'price' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'city' => 'required|string|max:100',
            'address' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:draft,pending',
            'year' => 'nullable|integer|min:1950|max:2030',
            'bedrooms' => 'nullable|integer|min:0|max:50',
            'listing_attributes' => 'nullable|array',
            'images.*' => 'nullable|file|mimes:jpeg,png,jpg,webp|max:10240',
            'image_urls' => 'nullable|array',
            'image_urls.*' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'Listing type must be one of: vehicle, real_estate, or apartment.',
            'category_id.exists' => 'Selected category does not exist.',
            'price.required' => 'Please provide a price in Ethiopian Birr (ETB).',
        ];
    }
}
