<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Kart-on-file (COF) — bank tərəfində saxlanılan kart tokeni. Kart nömrəsi
        // bizdə saxlanmır, yalnız bankın verdiyi token (storedId) + göstərmək üçün maska.
        Schema::create('payment_methods', function (Blueprint $t) {
            $t->id();
            $t->foreignId('merchant_id')->constrained()->cascadeOnDelete();

            $t->string('provider');
            $t->string('external_token_id');
            $t->string('card_mask')->nullable();

            $t->timestamps();

            $t->unique(['merchant_id', 'provider']);
        });

        // Mağaza abunəliyini avtomatik yeniləmək istəyir? (yadda saxlanılan kartla)
        Schema::table('merchants', function (Blueprint $t) {
            $t->boolean('auto_renew')->default(false)->after('subscription_ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $t) {
            $t->dropColumn('auto_renew');
        });

        Schema::dropIfExists('payment_methods');
    }
};
