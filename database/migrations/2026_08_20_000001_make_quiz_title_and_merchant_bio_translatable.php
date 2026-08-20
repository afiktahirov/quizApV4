<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kampaniya başlığını (quizzes.title) və mağaza haqqında mətni (merchants.bio)
 * çoxdilli edir — sual/variantlarda olduğu kimi {"az": "...", "en": "...", "ru": "..."}.
 *
 * Mövcud tək dilli mətnlər {"az": "köhnə mətn"} formasına köçürülür; front
 * seçilən dildə dəyər yoxdursa az-a düşür, ona görə heç nə "itmir".
 */
return new class extends Migration {
    /** @var array<string, string> cədvəl => sütun */
    private array $columns = [
        'quizzes'   => 'title',
        'merchants' => 'bio',
    ];

    public function up(): void
    {
        foreach ($this->columns as $table => $column) {
            // 1) Sütunu genişləndir — JSON sətri varchar(255)-ə sığmaya bilər
            Schema::table($table, function (Blueprint $t) use ($table, $column) {
                $this->text($t, $column, $table)->change();
            });

            // 2) Mövcud dəyərləri JSON obyektinə çevir
            DB::table($table)->orderBy('id')->chunkById(200, function ($rows) use ($table, $column) {
                foreach ($rows as $row) {
                    $value = $row->{$column};

                    // null qalsın; artıq JSON obyektidirsə toxunma (təkrar miqrasiya təhlükəsiz olsun)
                    if ($value === null || str_starts_with(ltrim((string) $value), '{')) {
                        continue;
                    }

                    DB::table($table)->where('id', $row->id)->update([
                        $column => json_encode(['az' => (string) $value], JSON_UNESCAPED_UNICODE),
                    ]);
                }
            });

            // 3) Sütunu json tipinə çevir (SQLite-da text kimi saxlanılır)
            Schema::table($table, function (Blueprint $t) use ($table, $column) {
                $this->json($t, $column, $table)->change();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->columns as $table => $column) {
            Schema::table($table, function (Blueprint $t) use ($table, $column) {
                $this->text($t, $column, $table)->change();
            });

            // JSON-dan yalnız az dəyərini geri qaytar
            DB::table($table)->orderBy('id')->chunkById(200, function ($rows) use ($table, $column) {
                foreach ($rows as $row) {
                    $value = $row->{$column};

                    if ($value === null) {
                        continue;
                    }

                    $decoded = json_decode((string) $value, true);

                    if (is_array($decoded)) {
                        DB::table($table)->where('id', $row->id)->update([
                            $column => $decoded['az'] ?? reset($decoded) ?: '',
                        ]);
                    }
                }
            });

            if ($table === 'quizzes') {
                Schema::table($table, fn (Blueprint $t) => $t->string($column, 255)->change());
            }
        }
    }

    /** quizzes.title NOT NULL, merchants.bio isə nullable qalmalıdır */
    private function text(Blueprint $t, string $column, string $table)
    {
        $col = $t->text($column);

        return $table === 'quizzes' ? $col : $col->nullable();
    }

    private function json(Blueprint $t, string $column, string $table)
    {
        $col = $t->json($column);

        return $table === 'quizzes' ? $col : $col->nullable();
    }
};
