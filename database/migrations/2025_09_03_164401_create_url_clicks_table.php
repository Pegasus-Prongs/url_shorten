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
        Schema::create('url_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('url_id')->constrained()->onDelete('cascade');
            $table->string('ip_address', 45)->nullable(); // Support IPv6
            $table->string('user_agent')->nullable(); // Browser/device info
            $table->string('referer')->nullable(); // Where they came from
            $table->string('country', 2)->nullable(); // Country code
            $table->string('device_type', 20)->nullable(); // mobile, desktop, tablet
            $table->timestamps();

            // Indexes for analytics queries
            $table->index('url_id');
            $table->index(['url_id', 'created_at']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('url_clicks');
    }
};
