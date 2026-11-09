<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecommendationItem extends Model
{
    protected $fillable = [
        'recommendation_history_id',
        'destination_id',
        'rank',
        'similarity_score',
    ];

    protected $casts = [
        'rank' => 'integer',
        'similarity_score' => 'decimal:5',
    ];

    // Item dimiliki satu riwayat rekomendasi
    public function recommendationHistory(): BelongsTo
    {
        return $this->belongsTo(RecommendationHistory::class);
    }

    // Item mengacu pada satu destinasi
    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }
}
