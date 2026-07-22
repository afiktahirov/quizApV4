<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $t) {
            // Abunəlik: super admin paneldən idarə edir.
            // subscription_ends_at = null => limitsiz (məs. daxili/test hesab)
            $t->string('plan')->nullable()->after('status');
            $t->timestamp('subscription_ends_at')->nullable()->after('plan');

            // Quiz-i keçən müştəriyə veriləcək kuponun parametrləri
            $t->enum('coupon_discount_type', ['percent', 'amount'])->default('percent')->after('subscription_ends_at');
            $t->decimal('coupon_value', 8, 2)->default(10)->after('coupon_discount_type');
            $t->unsignedSmallInteger('coupon_ttl_hours')->default(48)->after('coupon_value');
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $t) {
            $t->dropColumn(['plan', 'subscription_ends_at', 'coupon_discount_type', 'coupon_value', 'coupon_ttl_hours']);
        });
    }
};
