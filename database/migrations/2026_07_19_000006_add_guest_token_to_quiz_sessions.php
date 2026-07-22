<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // QR axını: qonaq qeydiyyatsız oynayır, sessiyaya guest_token ilə sahiblik edir.
        // Qeydiyyatdan sonra sessiya "claim" olunur (customer_id yazılır) və kupon verilir.
        Schema::table('quiz_sessions', function (Blueprint $t) {
            $t->string('guest_token', 64)->nullable()->after('customer_id');
            $t->index('guest_token');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_sessions', function (Blueprint $t) {
            $t->dropIndex(['guest_token']);
            $t->dropColumn('guest_token');
        });
    }
};
