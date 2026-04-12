<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:2', 'max:500'],
            'type' => ['nullable', 'string', 'in:keyword,vector,hybrid'],
            'filters' => ['nullable', 'array'],
        ];
    }
}
