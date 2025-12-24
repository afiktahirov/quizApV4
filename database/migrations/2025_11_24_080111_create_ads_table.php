<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ads', function (Blueprint $table) {
            $table->id();

            $table->foreignId('merchant_id')
                ->nullable()
                ->constrained('merchants')
                ->nullOnDelete();

            $table->string('title');           // Reklamın başlığı
            $table->string('image_path')->nullable();      // Şəklin yolu (storage yolu)
            $table->text('content')->nullable();  // Reklam mətni

            $table->string('status')->default('active'); // active / inactive
            $table->timestamp('starts_at')->nullable();  // reklamın start tarixi
            $table->timestamp('ends_at')->nullable();    // reklamın bitmə tarixi

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ads');
    }
};
