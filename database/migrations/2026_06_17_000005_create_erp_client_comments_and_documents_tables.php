<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_client_comments', function (Blueprint $table) {
            $table->id();
            $table->string('policy_number', 64);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('author_name', 255)->nullable();
            $table->text('body');
            $table->timestamps();

            $table->index(['policy_number', 'created_at']);
        });

        Schema::create('erp_client_documents', function (Blueprint $table) {
            $table->id();
            $table->string('policy_number', 64);
            $table->string('title', 255)->nullable();
            $table->string('original_filename', 255);
            $table->string('storage_path', 500);
            $table->string('mime_type', 128)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->unsignedBigInteger('uploaded_by_user_id')->nullable();
            $table->string('uploaded_by_name', 255)->nullable();
            $table->timestamps();

            $table->index(['policy_number', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_client_documents');
        Schema::dropIfExists('erp_client_comments');
    }
};
