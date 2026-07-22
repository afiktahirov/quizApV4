<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Mağazanın "paket dəyişmək / uzatmaq" sorğusu. Onlayn ödəniş inteqrasiyası
        // gələnə qədər super admin bu sorğulara baxıb əl ilə təsdiqləyir (approve → SubscriptionService::grant).
        Schema::create('subscription_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();

            $t->unsignedInteger('periods')->default(1);
            $t->decimal('amount', 8, 2)->default(0);
            $t->string('currency', 3)->default('AZN');

            $t->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $t->string('note')->nullable();
            $t->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('reviewed_at')->nullable();

            $t->timestamps();

            $t->index(['merchant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_requests');
    }
};
