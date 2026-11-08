
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recommendation_items', function (Blueprint $table) {
            $table->id();

            // Relasi ke riwayat rekomendasi
            $table->foreignId('recommendation_history_id');
            $table->foreignId('destination_id');

            // Urutan hasil rekomendasi
            $table->unsignedInteger('rank');

            // Nilai kemiripan dari algoritma rekomendasi (opsional)
            $table->decimal('similarity_score', 8, 5)->nullable();

            // Nama foreign key dibuat pendek untuk menghindari batas MySQL
            $table->foreign('recommendation_history_id', 'rec_items_history_fk')
                  ->references('id')
                  ->on('recommendation_histories')
                  ->cascadeOnDelete();

            $table->foreign('destination_id', 'rec_items_destination_fk')
                  ->references('id')
                  ->on('destinations')
                  ->cascadeOnDelete();

            // Satu destinasi tidak boleh muncul dua kali dalam satu riwayat
            $table->unique(
                ['recommendation_history_id', 'destination_id'],
                'rec_items_history_destination_unique'
            );

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendation_items');
    }
};
