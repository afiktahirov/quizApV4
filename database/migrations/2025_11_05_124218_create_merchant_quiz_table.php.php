<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('merchant_quiz', function (Blueprint $table) {
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->primary(['merchant_id', 'quiz_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('merchant_quiz');
    }
};