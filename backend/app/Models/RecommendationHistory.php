<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecommendationHistory extends Model
{
    protected $fillable = [
        'user_id',
        'city_id',
    ];

    // Riwayat dimiliki satu user
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Riwayat berkaitan dengan satu kota
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    // Riwayat dapat memiliki banyak kategori pilihan
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            Category::class,
            'recommendation_history_categories'
        )->withTimestamps();
    }

    // Riwayat menyimpan banyak hasil rekomendasi
    public function recommendationItems(): HasMany
    {
        return $this->hasMany(RecommendationItem::class);
    }
}
