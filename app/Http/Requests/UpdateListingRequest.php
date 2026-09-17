<?php

namespace App\Http\Requests;

use App\Models\Listing;
use Illuminate\Foundation\Http\FormRequest;

class UpdateListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $listing = $this->route('listing');
        return $this->user() && $this->user()->can('update', $listing);
    }

    public function rules(): array
    {
        return [
            'category_id' => 'sometimes|required|exists:categories,id',
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:10000',
            'price' => 'sometimes|required|numeric|min:0',
            'city' => 'sometimes|required|string|max:100',
            'address' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:draft,pending,sold,rented,expired',
            'year' => 'nullable|integer|min:1950|max:2030',
            'bedrooms' => 'nullable|integer|min:0|max:50',
            'listing_attributes' => 'nullable|array',
            'images.*' => 'nullable|file|mimes:jpeg,png,jpg,webp|max:10240',
            'remove_image_ids' => 'nullable|array',
            'remove_image_ids.*' => 'integer|exists:listing_images,id',
        ];
    }
}

