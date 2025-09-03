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
        Schema::create('urls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('original_url', 2048); // Support long URLs
            $table->string('short_code', 10)->unique(); // Unique short code
            $table->string('title')->nullable(); // Optional title for the URL
            $table->unsignedBigInteger('click_count')->default(0); // Track clicks
            $table->timestamp('last_clicked_at')->nullable(); // Last click timestamp
            $table->boolean('is_active')->default(true); // Allow disabling URLs
            $table->timestamp('expires_at')->nullable(); // Optional expiration
            $table->timestamps();

            // Indexes for performance
            $table->index('short_code');
            $table->index('user_id');
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('urls');
    }
};
