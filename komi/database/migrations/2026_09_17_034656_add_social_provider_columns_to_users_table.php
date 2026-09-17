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
        Schema::table('users', function (Blueprint $table) {
            // Proveedor OAuth2 (google, facebook, twitter) y su identificador
            // para permitir el login social sin duplicar usuarios.
            $table->string('provider')->nullable()->after('email_verified_at');
            $table->string('provider_id')->nullable()->index()->after('provider');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['provider_id']);
            $table->dropColumn(['provider', 'provider_id']);
        });
    }
};
