<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maturity_client_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('screen', 32);
            $table->string('policy_number', 64);
            $table->date('event_date');
            $table->string('event_type', 16)->default('maturity');
            $table->string('channel', 16);
            $table->string('recipient', 255);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->index(['policy_number', 'event_date', 'channel'], 'maturity_client_notify_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maturity_client_notifications');
    }
};
