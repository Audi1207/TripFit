
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('destination_categories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('destination_id')
                  ->constrained('destinations')
                  ->cascadeOnDelete();

            $table->foreignId('category_id')
                  ->constrained('categories')
                  ->cascadeOnDelete();

            // Mencegah kategori yang sama ditambahkan dua kali
            $table->unique(['destination_id', 'category_id']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('destination_categories');
    }
};
