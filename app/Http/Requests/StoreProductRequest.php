<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:100', 'unique:products,sku'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0.01'], // accepted as dollars
            'stock' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'images' => ['nullable', 'array'],
            'images.*' => ['string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['slug' => Str::slug($this->name).'-'.Str::random(6)]);
    }

    public function priceInCents(): int
    {
        return (int) round($this->validated('price') * 100);
    }
}