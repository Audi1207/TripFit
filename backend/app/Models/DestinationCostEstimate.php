<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DestinationCostEstimate extends Model
{
    protected $fillable = [
        'destination_id',
        'duration_level',
        'estimated_cost_rp',
    ];

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }
}