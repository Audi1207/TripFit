<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('destination_cost_estimates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('destination_id')
                  ->constrained('destinations')
                  ->cascadeOnDelete();

            $table->string('duration_level', 30);
            $table->unsignedInteger('estimated_cost_rp');

            $table->unique(
                ['destination_id', 'duration_level'],
                'destination_duration_unique'
            );

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('destination_cost_estimates');
    }
};