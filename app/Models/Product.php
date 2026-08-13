<?php

// app/Models/Product.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id', 'name', 'slug', 'sku', 'description',
        'price_cents', 'stock', 'is_active', 'images',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'images' => 'array', // JSON column <-> PHP array 
        ];
    }

    public function getPriceAttribute(): float
    {
        return round($this->price_cents / 100, 2);
    }

    public function category() { return $this->belongsTo(Category::class); }
    public function reviews()  { return $this->hasMany(Review::class); }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInCategory(Builder $query, ?int $categoryId): Builder
    {
        return $categoryId ? $query->where('category_id', $categoryId) : $query;
    }

    public function scopePriceBetween(Builder $query, ?int $minCents, ?int $maxCents): Builder
    {
        return $query
            ->when($minCents !== null, fn ($q) => $q->where('price_cents', '>=', $minCents))
            ->when($maxCents !== null, fn ($q) => $q->where('price_cents', '<=', $maxCents));
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $term
            ? $query->where(fn ($q) => $q->where('name', 'like', "%{$term}%")
                                          ->orWhere('description', 'like', "%{$term}%"))
            : $query;
    }

    public function averageRating(): float
    {
        return round($this->reviews()->avg('rating') ?? 0, 1);
    }
}