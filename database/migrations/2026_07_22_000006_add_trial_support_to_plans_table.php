<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $t) {
            // enum-dan sadə string-ə keçid — "trial" kimi yeni dövr növlərini DB migrasiyası
            // olmadan (Filament formundakı seçim siyahısı vasitəsilə) əlavə etməyə imkan verir.
            $t->string('billing_period')->default('monthly')->change();
            $t->unsignedInteger('trial_days')->nullable()->after('billing_period');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $t) {
            $t->dropColumn('trial_days');
        });
    }
};
