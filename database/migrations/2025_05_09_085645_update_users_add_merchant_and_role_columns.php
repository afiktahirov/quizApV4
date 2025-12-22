<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $t) {
            $t->foreignId('merchant_id')->nullable()->constrained()->nullOnDelete();
            $t->enum('role', ['super_admin','merchant_admin','cashier'])->default('merchant_admin');
        });
    }
    public function down(): void {
        Schema::table('users', function (Blueprint $t) {
            $t->dropConstrainedForeignId('merchant_id');
            $t->dropColumn('role');
        });
    }
};
