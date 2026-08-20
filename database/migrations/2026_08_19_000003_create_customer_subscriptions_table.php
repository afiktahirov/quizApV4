<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /** Müştəri abunəlik / gəlir ledger-i — hər aktivləşdirmə bir sətir yazır. */
    public function up(): void
    {
        Schema::create('customer_subscriptions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $t->foreignId('customer_plan_id')->nullable()->constrained('customer_plans')->nullOnDelete();

            $t->string('plan_name');
            $t->decimal('amount', 8, 2)->default(0);
            $t->string('currency', 3)->default('AZN');

            $t->timestamp('starts_at')->nullable();
            $t->timestamp('ends_at')->nullable();
            $t->string('status')->default('active'); // active | expired | cancelled

            $t->string('note')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $t->timestamps();

            $t->index(['customer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_subscriptions');
    }
};
