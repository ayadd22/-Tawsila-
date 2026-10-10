<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'         => ['sometimes', 'string', 'max:255'],
            'description'  => ['nullable', 'string', 'max:2000'],
            'price'        => ['sometimes', 'numeric', 'min:0', 'decimal:0,2'],
            'is_available' => ['sometimes', 'boolean'],
        ];
    }
}
