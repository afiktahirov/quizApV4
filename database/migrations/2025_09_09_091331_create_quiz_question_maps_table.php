<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('quiz_question_maps', function (Blueprint $t) {
            $t->id();
            $t->foreignId('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $t->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $t->unsignedTinyInteger('weight')->default(1);
            $t->unique(['quiz_id','question_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('quiz_question_maps');
    }
};
