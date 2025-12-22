<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $t) {
            $t->id();
            $t->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('slug')->unique();       // qrcode_slug kimi istifadə edilə bilər
            $t->string('address')->nullable();
            $t->enum('status', ['active','inactive'])->default('active');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
