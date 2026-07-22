<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Kampaniya endirim rejimi: flat (keç/qal sabit kupon) və ya tiered (pilləli).
        Schema::table('quizzes', function (Blueprint $t) {
            $t->enum('reward_mode', ['flat', 'tiered'])->default('flat')->after('status');
        });

        // Pilləli endirim: düzgün cavab sayına görə fərqli endirim.
        Schema::create('quiz_reward_tiers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $t->unsignedTinyInteger('min_correct'); // bu pilləyə çatmaq üçün minimum düzgün cavab
            $t->enum('discount_type', ['percent', 'amount'])->default('percent');
            $t->decimal('value', 8, 2);
            $t->unsignedSmallInteger('position')->default(0);
            $t->timestamps();

            $t->unique(['quiz_id', 'min_correct']);
        });

        // Sessiyada xam düzgün cavab sayı — pilləli endirim üçün lazımdır.
        Schema::table('quiz_sessions', function (Blueprint $t) {
            $t->unsignedTinyInteger('correct_count')->nullable()->after('score_pct');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_sessions', function (Blueprint $t) {
            $t->dropColumn('correct_count');
        });
        Schema::dropIfExists('quiz_reward_tiers');
        Schema::table('quizzes', function (Blueprint $t) {
            $t->dropColumn('reward_mode');
        });
    }
};
