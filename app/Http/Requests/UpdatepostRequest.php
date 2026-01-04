<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatepostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isDraft = $this->input('is_draft', false);
        
        // For drafts, make most fields optional
        if ($isDraft) {
            return [
                "is_draft" => "sometimes|boolean",
                "title" => "nullable|string",
                "price" => "nullable|numeric",
                "address" => "nullable|string",
                "description" => "nullable",
                "city" => "nullable",
                "bedrooms" => "nullable|integer",
                "bathrooms" => "nullable|integer",
                "latitude" => "nullable",
                "longitude" => "nullable",
                "type" => "nullable|in:rent,buy",
                "porperty_id" => "nullable|exists:porperties,id",
                "utilities_policy" => "nullable|in:owner,tenant,share",
                "pet_policy" => "nullable|boolean",
                "income_policy" => "nullable",
                "total_size" => "nullable|numeric",
                "bus" => "nullable|integer",
                "resturant" => "nullable|integer",
                "school" => "nullable|integer",
                "images" => "nullable|array",
                "images.*" => "nullable|string"
            ];
        }
        
        // For published posts, require all fields
        return [
            "title" => "required|string",
            "price" => "required|numeric",
            "address" => "required|string",
            "description" => "required",
            "city" => "required",
            "bedrooms" => "required|integer",
            "bathrooms" => "required|integer",
            "latitude" => "required",
            "longitude" => "required",
            "type" => "required|in:rent,buy",
            "porperty_id" => "required|exists:porperties,id",
            "utilities_policy" => "required|in:owner,tenant,share",
            "pet_policy" => "required|boolean",
            "income_policy" => "required",
            "total_size" => "required|numeric",
            "bus" => "required|integer",
            "resturant" => "required|integer",
            "school" => "required|integer",
            "images" => "required|array",
            "images.*" => "required|string"
        ];
    }
}
