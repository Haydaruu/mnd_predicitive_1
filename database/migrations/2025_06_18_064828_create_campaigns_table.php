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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('campaign_name');
            $table->string('product_type')->default('default');
            $table->string('dialing_type')->default('predictive');
            $table->string('created_by')->nullable(); // asumsi user bisa nullable
            $table->text('keterangan')->nullable();
            $table->boolean('is_active')->default(true); // optional
            $table->integer('retry_count')->default(0);  // optional
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
