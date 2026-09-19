<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auto_mod_keywords', function (Blueprint $table) {
            $table->id();
            $table->string('keyword');
            $table->enum('action', ['flag', 'block', 'delete'])->default('flag');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_mod_keywords');
    }
};
