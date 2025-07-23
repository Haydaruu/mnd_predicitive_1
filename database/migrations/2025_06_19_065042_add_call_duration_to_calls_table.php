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
        Schema::table('calls', function (Blueprint $table) {
            $table->integer('duration')->nullable()->after('call_ended_at'); // in seconds
            $table->text('notes')->nullable()->after('duration');
            $table->enum('disposition', ['answered', 'busy', 'no_answer', 'failed', 'callback', 'dnc'])->nullable()->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->dropColumn(['duration', 'notes', 'disposition']);
        });
    }
};
