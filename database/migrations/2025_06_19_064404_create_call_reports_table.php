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
        Schema::create('call_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->onDelete('cascade');
            $table->foreignId('agent_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('total_calls')->default(0);
            $table->integer('answered_calls')->default(0);
            $table->integer('failed_calls')->default(0);
            $table->integer('busy_calls')->default(0);
            $table->integer('no_answer_calls')->default(0);
            $table->integer('total_talk_time')->default(0); // in seconds
            $table->decimal('average_talk_time', 8, 2)->default(0); // in seconds
            $table->date('date');
            $table->timestamps();

            $table->unique(['campaign_id', 'agent_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_reports');
    }
};
