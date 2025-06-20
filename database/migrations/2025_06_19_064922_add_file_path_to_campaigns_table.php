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
        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('file_path')->nullable()->after('retry_count');
            $table->enum('status', ['pending', 'running', 'paused', 'completed', 'stopped'])->default('pending')->after('file_path');
            $table->timestamp('started_at')->nullable()->after('status');
            $table->timestamp('stopped_at')->nullable()->after('started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['file_path', 'status', 'started_at', 'stopped_at']);
        });
    }
};
