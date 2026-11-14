<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Destination extends Model
{
    protected $fillable = [
        'city_id',
        'destination_name',
        'description',
        'address',
        'latitude',
        'longitude',
        'image_url',
    ];

    // Destinasi berada dalam satu kota
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    // Destinasi dapat memiliki banyak kategori
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            Category::class,
            'destination_categories'
        )->withTimestamps();
    }

    // Destinasi memiliki banyak estimasi biaya
    public function costEstimates(): HasMany
    {
        return $this->hasMany(DestinationCostEstimate::class);
    }

    // Destinasi dapat muncul dalam banyak hasil rekomendasi
    public function recommendationItems(): HasMany
    {
        return $this->hasMany(RecommendationItem::class);
    }
}
