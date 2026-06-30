<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = (new \App\Models\Complaint)->getConnectionName();

        Schema::connection($connection)->table('complaints', function (Blueprint $table) use ($connection) {
            if (! Schema::connection($connection)->hasColumn('complaints', 'register_status')) {
                $table->string('register_status', 20)->default('active')->after('status')->index();
            }
            if (! Schema::connection($connection)->hasColumn('complaints', 'classification_score')) {
                $table->unsignedTinyInteger('classification_score')->nullable()->after('register_status');
            }
            if (! Schema::connection($connection)->hasColumn('complaints', 'classification_reason')) {
                $table->string('classification_reason', 255)->nullable()->after('classification_score');
            }
        });
    }

    public function down(): void
    {
        $connection = (new \App\Models\Complaint)->getConnectionName();

        Schema::connection($connection)->table('complaints', function (Blueprint $table) {
            $table->dropColumn(['register_status', 'classification_score', 'classification_reason']);
        });
    }
};
