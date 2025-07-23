<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('nasbahs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone');
            $table->decimal('outstanding', 12, 2)->default(0); // opsional
            $table->decimal('denda', 12, 2)->default(0); // opsional
            $table->json('data_json')->nullable(); // fleksibel untuk semua produk
            $table->text('catatan')->nullable();
            $table->foreignId('campaign_id')->constrained()->onDelete('cascade');
            $table->boolean('is_called')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nasbahs');
    }
};
