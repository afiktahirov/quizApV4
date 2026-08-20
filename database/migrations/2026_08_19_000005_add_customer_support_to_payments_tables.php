<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Ödəniş ledger-i indi həm mağaza, həm də müştəri abunəliklərinə xidmət edir.
     * Bir sətirdə YA merchant_id, YA da customer_id dolu olur.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $t) {
            $t->foreignId('merchant_id')->nullable()->change();
            $t->foreignId('customer_id')->nullable()->after('merchant_id')
                ->constrained()->cascadeOnDelete();
            $t->foreignId('customer_subscription_request_id')->nullable()->after('subscription_request_id')
                ->constrained('customer_subscription_requests')->nullOnDelete();

            $t->index(['customer_id', 'status']);
        });

        Schema::table('payment_methods', function (Blueprint $t) {
            $t->foreignId('merchant_id')->nullable()->change();
            $t->foreignId('customer_id')->nullable()->after('merchant_id')
                ->constrained()->cascadeOnDelete();

            $t->unique(['customer_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $t) {
            $t->dropUnique(['customer_id', 'provider']);
            $t->dropConstrainedForeignId('customer_id');
        });

        Schema::table('payments', function (Blueprint $t) {
            $t->dropIndex(['customer_id', 'status']);
            $t->dropConstrainedForeignId('customer_subscription_request_id');
            $t->dropConstrainedForeignId('customer_id');
        });
    }
};
