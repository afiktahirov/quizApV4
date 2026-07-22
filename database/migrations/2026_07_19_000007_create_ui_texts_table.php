<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Frontend-in statik mətnləri — super admin paneldən 3 dildə (az/en/ru) idarə edir.
        Schema::create('ui_texts', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();   // məs: nav.stores, play.start
            $t->json('value');             // {az: "...", en: "...", ru: "..."}
            $t->string('group')->nullable(); // qruplaşdırma: nav / play / auth ...
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ui_texts');
    }
};
