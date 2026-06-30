<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agile_profile_settings', function (Blueprint $table) {
            $table->unsignedInteger('profileid')->primary();
            $table->json('client_segments')->nullable();
            $table->json('app_modules')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agile_profile_settings');
    }
};
