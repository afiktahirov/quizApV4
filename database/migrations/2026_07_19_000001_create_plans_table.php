<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->decimal('price', 8, 2)->default(0);
            $t->string('currency', 3)->default('AZN');
            $t->enum('billing_period', ['monthly', 'yearly'])->default('monthly');

            // Limitlər — null => limitsiz
            $t->unsignedInteger('max_quizzes')->nullable();
            $t->unsignedInteger('max_questions')->nullable();
            $t->unsignedInteger('max_stores')->nullable();
            $t->unsignedInteger('max_ads')->nullable();

            $t->text('description')->nullable();
            $t->boolean('is_active')->default(true);
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
