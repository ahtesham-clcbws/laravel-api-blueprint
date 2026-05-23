<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'details' => 'array',
            'details.color' => 'string|max:50',
            'details.size' => 'string|max:10',
            'tags' => 'array',
            'tags.*.name' => 'required|string|max:100',
        ];
    }
}
