<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mpesa_stk_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('policy_number', 64)->index();
            $table->string('client_name', 255)->nullable();
            $table->string('phone', 20);
            $table->decimal('amount', 12, 2);
            $table->string('account_reference', 20);
            $table->string('description', 100)->nullable();
            $table->string('merchant_request_id', 64)->nullable()->index();
            $table->string('checkout_request_id', 64)->nullable()->unique();
            $table->string('mpesa_receipt_number', 32)->nullable()->index();
            $table->unsignedSmallInteger('result_code')->nullable();
            $table->string('result_desc', 255)->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->json('callback_payload')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mpesa_stk_transactions');
    }
};
