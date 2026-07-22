<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Köhnə sərbəst-mətn `plan` sütunu `plan()` əlaqəsi ilə toqquşur.
        // Struktur idarəetmə artıq plan_id ilə gedir — köhnə sütunu silirik.
        Schema::table('merchants', function (Blueprint $t) {
            if (Schema::hasColumn('merchants', 'plan')) {
                $t->dropColumn('plan');
            }
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $t) {
            $t->string('plan')->nullable()->after('photo');
        });
    }
};
