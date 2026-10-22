<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_events', function (Blueprint $table) {
            $table->id();
            $table->string('feature', 100)->unique();
            $table->unsignedInteger('occurrences_count')->default(1);
            $table->timestamp('last_seen_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_events');
    }
};
