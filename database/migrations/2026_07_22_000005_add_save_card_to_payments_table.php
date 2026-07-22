<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $t) {
            $t->boolean('save_card')->default(false)->after('external_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $t) {
            $t->dropColumn('save_card');
        });
    }
};
