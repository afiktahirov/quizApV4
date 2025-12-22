<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('question_category_question', function (Blueprint $t) {
            $t->id();
            $t->foreignId('question_id')->constrained()->cascadeOnDelete();
            $t->foreignId('question_category_id')->constrained()->cascadeOnDelete();
            $t->primary(['question_id','question_category_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('question_category_question');
    }
};
