<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prp_unprocessed_leads_cache', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pol_code')->unique();
            $table->string('policy_number', 64)->index();
            $table->unsignedInteger('unprocessed_rct')->default(0);
            $table->date('paid_to')->nullable();
            $table->string('client_name', 512)->nullable();
            $table->string('prp_tel', 64)->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prp_unprocessed_leads_cache');
    }
};
