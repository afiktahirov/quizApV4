<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * İSTİFADƏÇİ (müştəri) abunəlik paketləri — mağaza paketlərindən (plans) ayrıdır,
     * çünki limitləri tamamilə fərqlidir. Super admin bu paketləri yaradır,
     * müştərilər tətbiqdən istifadə etmək üçün birinə abunə olurlar.
     */
    public function up(): void
    {
        Schema::create('customer_plans', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->decimal('price', 8, 2)->default(0);
            $t->string('currency', 3)->default('AZN');
            $t->string('billing_period')->default('monthly'); // monthly | yearly | trial
            $t->unsignedInteger('trial_days')->nullable();

            // Limitlər — null => limitsiz
            $t->unsignedInteger('max_quizzes_per_day')->nullable();
            $t->unsignedInteger('max_coupons_per_month')->nullable();

            $t->text('description')->nullable();
            $t->json('features')->nullable(); // marketinq üçün maddələr siyahısı
            $t->boolean('is_active')->default(true);
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_plans');
    }
};
