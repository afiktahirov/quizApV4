<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Abunəlik / gəlir ledger-i. Hər paket təyini / uzatma bir sətir yaradır.
        // Online ödəniş sonra bu cədvələ qoşulacaq (amount = ödənilən məbləğ).
        Schema::create('merchant_subscriptions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();

            // snapshot — plan sonradan dəyişsə/silinsə də gəlir tarixçəsi qalır
            $t->string('plan_name');
            $t->decimal('amount', 8, 2)->default(0);
            $t->string('currency', 3)->default('AZN');

            $t->timestamp('starts_at');
            $t->timestamp('ends_at');
            $t->enum('status', ['active', 'expired', 'cancelled'])->default('active');

            $t->string('note')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();

            $t->index(['merchant_id', 'status']);
            $t->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_subscriptions');
    }
};
