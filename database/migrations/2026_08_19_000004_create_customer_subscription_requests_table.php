<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Müştərinin paket sorğusu. Onlayn ödəniş uğurlu olanda avtomatik "approved" olur
     * (bax: PaymentService::handleReturn). Pulsuz/sınaq paketlərdə dərhal təsdiqlənir.
     */
    public function up(): void
    {
        Schema::create('customer_subscription_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $t->foreignId('customer_plan_id')->constrained('customer_plans')->cascadeOnDelete();

            $t->unsignedInteger('periods')->default(1);
            $t->decimal('amount', 8, 2)->default(0);
            $t->string('currency', 3)->default('AZN');

            $t->string('status')->default('pending'); // pending | approved | rejected | cancelled
            $t->string('note')->nullable();
            $t->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('reviewed_at')->nullable();

            $t->timestamps();

            $t->index(['customer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_subscription_requests');
    }
};
