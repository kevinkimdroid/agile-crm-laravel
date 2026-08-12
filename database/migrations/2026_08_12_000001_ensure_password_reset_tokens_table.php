<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = config('services.password_reset.connection') ?? config('database.default');

        if (Schema::connection($connection)->hasTable('password_reset_tokens')) {
            return;
        }

        Schema::connection($connection)->create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        $connection = config('services.password_reset.connection') ?? config('database.default');

        Schema::connection($connection)->dropIfExists('password_reset_tokens');
    }
};
