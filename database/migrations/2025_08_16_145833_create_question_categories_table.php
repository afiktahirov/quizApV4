<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('question_categories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('merchant_id')->constrained()->cascadeOnDelete(); // tenant
            $t->string('name');
            $t->string('slug')->unique();
            $t->enum('status',['active','inactive'])->default('active');
            $t->timestamps();
            $t->unique(['merchant_id','name']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('question_categories');
    }
};
