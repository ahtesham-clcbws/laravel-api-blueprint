<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string|min:2|max:50',
            'last_name' => 'required|string|min:2|max:50',
            'email' => 'required|email',
            'department' => 'required|string|in:Engineering,Marketing,Sales,HR,Finance',
            'salary' => 'required|numeric|min:1000',
            'contact_info' => 'required|array',
            'contact_info.phone' => 'required|string',
            'contact_info.address' => 'nullable|string',
            'skills' => 'required|array|min:1',
            'skills.*.name' => 'required|string',
            'skills.*.level' => 'required|string|in:Beginner,Intermediate,Expert',
        ];
    }
}
