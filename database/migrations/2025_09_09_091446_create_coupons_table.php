<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('coupons', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique(); // QZ-9X7H-2K4M kimi
            $t->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('quiz_session_id')->constrained('quiz_sessions')->cascadeOnDelete();
            $t->enum('discount_type', ['percent','amount'])->default('percent');
            $t->decimal('value', 8, 2);
            $t->timestamp('expires_at');
            $t->enum('status', ['active','redeemed','expired','revoked'])->default('active');
            $t->string('signature', 128); // HMAC imza
            $t->text('qr_payload');       // app url ya da deep link
            $t->timestamps();

            $t->index(['status','expires_at']);
        });

        Schema::create('coupon_redemptions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('coupon_id')->constrained('coupons')->cascadeOnDelete();
            $t->foreignId('store_id')->constrained()->cascadeOnDelete();
            $t->foreignId('cashier_user_id')->constrained('users')->cascadeOnDelete();
            $t->timestamp('redeemed_at')->useCurrent();
            $t->string('pos_reference')->nullable();
        });
    }
    public function down(): void {
        Schema::dropIfExists('coupon_redemptions');
        Schema::dropIfExists('coupons');
    }
};
