<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            // MerchantForm-dakı xəritə widget-i bunu reverse-geocode ilə doldurur,
            // amma indiyə qədər saxlayacaq sütun yox idi.
            $table->string('address')->nullable()->after('geojson');
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn('address');
        });
    }
};
