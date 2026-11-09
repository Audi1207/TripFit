
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('destinations', function (Blueprint $table) {
            $table->string('administrative_city_regency')->nullable();
            $table->unsignedInteger('ticket_price_rp')->default(0);
            $table->decimal('rating', 2, 1)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('destinations', function (Blueprint $table) {
            $table->dropColumn([
                'administrative_city_regency',
                'ticket_price_rp',
                'rating',
            ]);
        });
    }
};
