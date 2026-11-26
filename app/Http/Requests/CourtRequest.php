<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CourtRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'sport_category_id' => ['required', 'exists:sport_categories,id'],
            'name' => ['required', 'string', 'max:100', Rule::unique('courts')->ignore($this->route('court')?->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'price_per_hour' => ['required', 'integer', 'min:1000', 'max:10000000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
