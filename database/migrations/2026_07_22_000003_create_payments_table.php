<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Bank-agnostik ödəniş ledger-i. 'provider' sahəsi hansı bank/şlüz olduğunu
        // göstərir (kapital_bank, sonra əlavə olunacaq başqaları), qalan kod bundan asılı deyil.
        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('subscription_request_id')->nullable()->constrained()->nullOnDelete();

            $t->string('provider');
            $t->string('external_order_id')->nullable();

            $t->decimal('amount', 8, 2);
            $t->string('currency', 3)->default('AZN');

            $t->enum('status', ['pending', 'paid', 'failed', 'refunded', 'reversed', 'expired'])->default('pending');

            $t->json('raw_response')->nullable();
            $t->timestamp('paid_at')->nullable();

            $t->timestamps();

            $t->index(['provider', 'external_order_id']);
            $t->index(['merchant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
