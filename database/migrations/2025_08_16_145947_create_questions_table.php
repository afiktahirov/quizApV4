<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('questions', function (Blueprint $t) {
            $t->id();
            $t->text('title');
            $t->enum('type',['mcq','true_false'])->default('mcq');
            $t->string('difficulty')->nullable(); // easy/medium/hard və s.
            $t->boolean('is_active')->default(true);
            $t->timestamps();

        });

        Schema::create('question_options', function (Blueprint $t) {
            $t->id();
            $t->foreignId('question_id')->constrained()->cascadeOnDelete();
            $t->string('option_text');
            $t->boolean('is_correct')->default(false);
            $t->unsignedTinyInteger('position')->nullable(); // A=1, B=2...
            $t->timestamps();

            $t->index(['question_id','is_correct']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('question_options');
        Schema::dropIfExists('questions');
    }
};
