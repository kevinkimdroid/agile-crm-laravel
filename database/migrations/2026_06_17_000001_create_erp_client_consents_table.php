<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_client_consents', function (Blueprint $table) {
            $table->id();
            $table->string('policy_number', 64)->unique();
            $table->boolean('consent_granted')->default(false);
            $table->timestamp('consented_at')->nullable();
            $table->unsignedBigInteger('consented_by_user_id')->nullable();
            $table->string('consented_by_name', 255)->nullable();
            $table->unsignedBigInteger('updated_by_user_id')->nullable();
            $table->string('updated_by_name', 255)->nullable();
            $table->timestamps();

            $table->index(['consent_granted', 'policy_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_client_consents');
    }
};
