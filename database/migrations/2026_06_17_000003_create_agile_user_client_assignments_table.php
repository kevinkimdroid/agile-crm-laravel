<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agile_user_client_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('userid')->index();
            $table->string('policy_number', 64);
            $table->string('client_label', 255)->nullable();
            $table->string('system', 32)->nullable()->index();
            $table->unsignedInteger('assigned_by')->nullable();
            $table->timestamps();

            $table->unique(['userid', 'policy_number']);
        });

        if (Schema::hasTable('agile_profile_settings') && ! Schema::hasColumn('agile_profile_settings', 'client_access_mode')) {
            Schema::table('agile_profile_settings', function (Blueprint $table) {
                $table->string('client_access_mode', 32)->default('all')->after('client_segments');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('agile_user_client_assignments');

        if (Schema::hasTable('agile_profile_settings') && Schema::hasColumn('agile_profile_settings', 'client_access_mode')) {
            Schema::table('agile_profile_settings', function (Blueprint $table) {
                $table->dropColumn('client_access_mode');
            });
        }
    }
};
