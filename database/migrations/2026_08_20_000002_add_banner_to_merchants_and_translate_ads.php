<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 1) Mağazaya arxa fon (banner) şəkli sahəsi əlavə edir — logo/foto ilə yanaşı
 *    mağaza səhifəsinin başlığında istifadə olunur.
 * 2) Reklamların başlıq və məzmununu çoxdilli edir ({"az","en","ru"}) —
 *    tətbiq 3 dildə işlədiyi üçün reklam mətni də dilə uyğun göstərilməlidir.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $t) {
            $t->string('banner')->nullable()->after('photo');
        });

        foreach (['title' => false, 'content' => true] as $column => $nullable) {
            Schema::table('ads', function (Blueprint $t) use ($column, $nullable) {
                $col = $t->text($column);
                $nullable ? $col->nullable()->change() : $col->change();
            });

            DB::table('ads')->orderBy('id')->chunkById(200, function ($rows) use ($column) {
                foreach ($rows as $row) {
                    $value = $row->{$column};

                    if ($value === null || str_starts_with(ltrim((string) $value), '{')) {
                        continue;
                    }

                    DB::table('ads')->where('id', $row->id)->update([
                        $column => json_encode(['az' => (string) $value], JSON_UNESCAPED_UNICODE),
                    ]);
                }
            });

            Schema::table('ads', function (Blueprint $t) use ($column, $nullable) {
                $col = $t->json($column);
                $nullable ? $col->nullable()->change() : $col->change();
            });
        }
    }

    public function down(): void
    {
        foreach (['title' => false, 'content' => true] as $column => $nullable) {
            Schema::table('ads', function (Blueprint $t) use ($column, $nullable) {
                $col = $t->text($column);
                $nullable ? $col->nullable()->change() : $col->change();
            });

            DB::table('ads')->orderBy('id')->chunkById(200, function ($rows) use ($column) {
                foreach ($rows as $row) {
                    $decoded = json_decode((string) $row->{$column}, true);

                    if (is_array($decoded)) {
                        DB::table('ads')->where('id', $row->id)->update([
                            $column => $decoded['az'] ?? reset($decoded) ?: '',
                        ]);
                    }
                }
            });
        }

        Schema::table('ads', fn (Blueprint $t) => $t->string('title', 255)->change());

        Schema::table('merchants', function (Blueprint $t) {
            $t->dropColumn('banner');
        });
    }
};
