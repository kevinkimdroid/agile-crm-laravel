<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('clients')) {
            return;
        }

        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('policy_no', 64)->unique();
            $table->string('first_name');
            $table->string('last_name')->nullable();

            // KYC details
            $table->string('id_no', 64)->nullable();
            $table->string('kra_pin', 32)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('address')->nullable();
            $table->string('city', 120)->nullable();
            $table->string('postal_code', 32)->nullable();
            $table->string('occupation', 120)->nullable();

            // Policy / segmentation
            $table->string('product')->nullable();
            $table->string('intermediary')->nullable();
            $table->string('system', 32)->default('individual'); // individual|group|mortgage|group_pension
            $table->string('status', 8)->default('A'); // A active, FL lapsed
            $table->text('notes')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('created_by_name')->nullable();
            $table->string('source', 20)->default('manual'); // manual | import
            $table->timestamps();

            $table->index('system');
            $table->index('status');
            $table->index('id_no');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
