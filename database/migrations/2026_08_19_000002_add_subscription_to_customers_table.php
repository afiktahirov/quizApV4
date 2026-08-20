<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /** Müştərinin CARİ abunəlik vəziyyəti (tarixçə customer_subscriptions cədvəlindədir). */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $t) {
            $t->foreignId('customer_plan_id')->nullable()->after('password')
                ->constrained('customer_plans')->nullOnDelete();
            $t->timestamp('subscription_ends_at')->nullable()->after('customer_plan_id');
            $t->boolean('auto_renew')->default(false)->after('subscription_ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $t) {
            $t->dropConstrainedForeignId('customer_plan_id');
            $t->dropColumn(['subscription_ends_at', 'auto_renew']);
        });
    }
};
