<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'is_active' => 'nullable|boolean',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
            'payment_qr_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la sucursal es obligatorio.',
            'logo.image' => 'El logo debe ser una imagen.',
            'logo.mimes' => 'El logo debe ser JPG, PNG o WEBP.',
            'logo.max' => 'El logo no debe superar los 10MB.',
            'payment_qr_image.image' => 'El QR de cobro debe ser una imagen.',
            'payment_qr_image.mimes' => 'El QR de cobro debe ser JPG, PNG o WEBP.',
            'payment_qr_image.max' => 'El QR de cobro no debe superar los 10MB.',
        ];
    }
}
