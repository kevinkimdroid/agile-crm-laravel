<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('faq_categories')) {
            Schema::create('faq_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('description')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('faq_articles')) {
            Schema::create('faq_articles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('faq_category_id')->nullable()->constrained('faq_categories')->nullOnDelete();
                $table->string('question');
                $table->text('answer');
                $table->string('tags')->nullable();
                $table->string('status')->default('published'); // published | draft | archived
                $table->unsignedInteger('views')->default(0);
                $table->unsignedInteger('helpful_count')->default(0);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->string('created_by_name')->nullable();
                $table->timestamps();

                $table->index('status');
                $table->index('faq_category_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('faq_articles');
        Schema::dropIfExists('faq_categories');
    }
};
