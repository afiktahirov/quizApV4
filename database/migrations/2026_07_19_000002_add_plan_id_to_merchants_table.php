<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $t) {
            // Köhnə `plan` (sərbəst mətn) qalır; struktur idarəetmə plan_id ilə gedir.
            $t->foreignId('plan_id')->nullable()->after('plan')
                ->constrained('plans')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $t) {
            $t->dropConstrainedForeignId('plan_id');
        });
    }
};
