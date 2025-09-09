<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('quiz_sessions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('store_id')->constrained()->cascadeOnDelete();
            $t->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // guest də ola bilər
            $t->timestamp('started_at')->useCurrent();
            $t->timestamp('finished_at')->nullable();
            $t->unsignedTinyInteger('score_pct')->nullable();
            $t->boolean('is_passed')->default(false);
            $t->string('ip',45)->nullable();
            $t->string('device_fingerprint')->nullable();
            $t->string('channel')->default('qr');
            $t->index(['merchant_id','store_id','quiz_id','started_at']);
        });

        Schema::create('answers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('quiz_session_id')->constrained('quiz_sessions')->cascadeOnDelete();
            $t->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $t->foreignId('selected_option_id')->nullable()->constrained('question_options')->nullOnDelete();
            $t->boolean('is_correct')->default(false);
            $t->timestamp('answered_at')->useCurrent();
            $t->unique(['quiz_session_id','question_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('answers');
        Schema::dropIfExists('quiz_sessions');
    }
};
