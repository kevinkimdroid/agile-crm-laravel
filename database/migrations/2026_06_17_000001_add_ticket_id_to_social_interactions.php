<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection(config('database.default'))->table('social_interactions', function (Blueprint $table) {
            if (! Schema::connection(config('database.default'))->hasColumn('social_interactions', 'ticket_id')) {
                $table->unsignedBigInteger('ticket_id')->nullable()->after('lead_id');
                $table->index('ticket_id');
            }
        });
    }

    public function down(): void
    {
        Schema::connection(config('database.default'))->table('social_interactions', function (Blueprint $table) {
            if (Schema::connection(config('database.default'))->hasColumn('social_interactions', 'ticket_id')) {
                $table->dropIndex(['ticket_id']);
                $table->dropColumn('ticket_id');
            }
        });
    }
};
