
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recommendation_history_categories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('recommendation_history_id');
            $table->foreignId('category_id');

            // Nama foreign key dibuat pendek agar sesuai batas MySQL
            $table->foreign('recommendation_history_id', 'rh_categories_history_fk')
                  ->references('id')
                  ->on('recommendation_histories')
                  ->cascadeOnDelete();

            $table->foreign('category_id', 'rh_categories_category_fk')
                  ->references('id')
                  ->on('categories')
                  ->cascadeOnDelete();

            // Nama unique index juga dibuat pendek
            $table->unique(
                ['recommendation_history_id', 'category_id'],
                'rh_categories_unique'
            );

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendation_history_categories');
    }
};
