<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    protected $fillable = [
        'category_name',
    ];

    // Kategori dapat dimiliki banyak destinasi
    public function destinations(): BelongsToMany
    {
        return $this->belongsToMany(
            Destination::class,
            'destination_categories'
        )->withTimestamps();
    }

    // Kategori dapat digunakan dalam banyak riwayat rekomendasi
    public function recommendationHistories(): BelongsToMany
    {
        return $this->belongsToMany(
            RecommendationHistory::class,
            'recommendation_history_categories'
        )->withTimestamps();
    }
}
