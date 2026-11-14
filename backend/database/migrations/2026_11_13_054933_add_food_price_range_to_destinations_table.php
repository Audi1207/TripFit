<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('destinations', function (Blueprint $table) {
            $table->unsignedInteger('main_food_price_min_rp')
                ->nullable()
                ->after('ticket_price_rp');

            $table->unsignedInteger('main_food_price_max_rp')
                ->nullable()
                ->after('main_food_price_min_rp');
        });
    }

    public function down(): void
    {
        Schema::table('destinations', function (Blueprint $table) {
            $table->dropColumn([
                'main_food_price_min_rp',
                'main_food_price_max_rp',
            ]);
        });
    }
};