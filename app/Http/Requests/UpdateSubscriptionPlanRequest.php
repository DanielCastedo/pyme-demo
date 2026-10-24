<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubscriptionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'billing_cycle' => 'required|in:monthly,quarterly,yearly',
            'max_users' => 'nullable|integer|min:1',
            'qr_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
            'features' => 'nullable|string',
            'active' => 'nullable|boolean',
        ];
    }
}
