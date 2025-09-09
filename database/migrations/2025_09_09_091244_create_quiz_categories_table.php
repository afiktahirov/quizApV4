<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('quiz_categories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('slug')->unique();
            $t->enum('status',['active','inactive'])->default('active');
            $t->timestamps();
            $t->unique(['merchant_id','name']);
        });

        Schema::create('quizzes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('quiz_category_id')->nullable()->constrained('quiz_categories')->nullOnDelete();
            $t->string('title');
            $t->unsignedTinyInteger('total_questions')->default(10);
            $t->unsignedTinyInteger('pass_threshold_pct')->default(70);
            $t->unsignedSmallInteger('time_per_question_sec')->nullable();
            $t->enum('status', ['draft','active','archived'])->default('draft');
            $t->timestamps();

            $t->index(['merchant_id','store_id','status']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('quizzes');
        Schema::dropIfExists('quiz_categories');
    }
};
