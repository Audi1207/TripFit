<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    protected $fillable = [
        'city_name',
        'province',
    ];

    // Satu kota memiliki banyak destinasi
    public function destinations(): HasMany
    {
        return $this->hasMany(Destination::class);
    }

    // Satu kota dapat memiliki banyak riwayat rekomendasi
    public function recommendationHistories(): HasMany
    {
        return $this->hasMany(RecommendationHistory::class);
    }
}
