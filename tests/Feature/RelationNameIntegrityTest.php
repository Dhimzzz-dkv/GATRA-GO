<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menjaga integritas nama relasi Eloquent.
 *
 * Dua kelas masalah yang dicegah:
 *
 *  1. Nama relasi salah pada with()/load() — menyebabkan
 *     "Call to undefined relationship" → HTTP 500, dan TIDAK terdeteksi oleh
 *     sapuan lazy loading karena bukan pelanggaran lazy loading.
 *
 *  2. Method relasi tanpa return type — melemahkan analisis statis dan IDE,
 *     sehingga kesalahan nama relasi lebih sulit ditemukan lebih awal.
 */
class RelationNameIntegrityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Nama yang muncul pada with() tetapi bukan relasi (flash message).
     *
     * @var array<int, string>
     */
    private const NON_RELATION_WITH_KEYS = [
        'success',
        'error',
        'warning',
        'info',
        'status',
    ];

    /**
     * @return array<int, class-string>
     */
    private function models(): array
    {
        return [
            \App\Models\AuditLog::class,
            \App\Models\Lhp::class,
            \App\Models\Opd::class,
            \App\Models\Pegawai::class,
            \App\Models\Pemeriksaan::class,
            \App\Models\Rekomendasi::class,
            \App\Models\Role::class,
            \App\Models\Temuan::class,
            \App\Models\TimPemeriksa::class,
            \App\Models\TindakLanjut::class,
            \App\Models\User::class,
        ];
    }

    /**
     * Apakah sebuah method adalah relasi Eloquent?
     */
    private function isRelationMethod(\ReflectionMethod $method): bool
    {
        if ($method->getNumberOfParameters() > 0) {
            return false;
        }

        $name = $method->getName();

        // Lewati accessor, scope, dan method bawaan framework
        foreach (['get', 'scope', '__', 'set'] as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return false;
            }
        }

        // Trait Notifiable membawa method notifications() yang bukan relasi bisnis
        if (in_array($name, ['notifications', 'readNotifications', 'unreadNotifications'], true)) {
            return false;
        }

        $returnType = $method->getReturnType();

        if ($returnType instanceof \ReflectionNamedType
            && is_subclass_of($returnType->getName(), \Illuminate\Database\Eloquent\Relations\Relation::class)) {
            return true;
        }

        $file = $method->getFileName();

        if ($file === false) {
            return false;
        }

        $lines = file($file);
        $body = implode('', array_slice(
            $lines,
            $method->getStartLine() - 1,
            $method->getEndLine() - $method->getStartLine() + 1
        ));

        return (bool) preg_match('/\$this->(belongsTo|hasMany|hasOne|belongsToMany|morphMany|morphTo)\(/', $body);
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function relationMap(): array
    {
        $map = [];

        foreach ($this->models() as $class) {
            $reflection = new \ReflectionClass($class);
            $relations = [];

            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if ($this->isRelationMethod($method)) {
                    $relations[] = $method->getName();
                }
            }

            $map[$class] = $relations;
        }

        return $map;
    }

    public function test_every_model_relation_is_detected(): void
    {
        $map = $this->relationMap();

        foreach ($map as $class => $relations) {
            $this->assertNotEmpty(
                $relations,
                "Model {$class} tidak terdeteksi memiliki relasi sama sekali."
            );
        }

        $this->assertContains('opd', $map[\App\Models\Temuan::class]);
        $this->assertContains('rekomendasis', $map[\App\Models\Temuan::class]);
        $this->assertContains('tindakLanjuts', $map[\App\Models\Temuan::class]);
        $this->assertContains('opds', $map[\App\Models\Lhp::class]);
        $this->assertContains('temuans', $map[\App\Models\Lhp::class]);
        $this->assertContains('role', $map[\App\Models\User::class]);
        $this->assertContains('pegawai', $map[\App\Models\User::class]);
        $this->assertContains('lhps', $map[\App\Models\Pemeriksaan::class]);
    }

    public function test_all_relation_methods_have_return_type(): void
    {
        $offenders = [];

        foreach ($this->models() as $class) {
            $reflection = new \ReflectionClass($class);

            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if ($this->isRelationMethod($method) && ! $method->hasReturnType()) {
                    $offenders[] = "{$class}::{$method->getName()}()";
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'Method relasi berikut tidak memiliki return type, sehingga sulit divalidasi tooling:'
            . PHP_EOL . implode(PHP_EOL, $offenders)
        );
    }

    public function test_relations_can_be_instantiated(): void
    {
        $this->seed();

        $instances = [
            \App\Models\AuditLog::class => \App\Models\AuditLog::first(),
            \App\Models\Lhp::class => \App\Models\Lhp::first(),
            \App\Models\Opd::class => \App\Models\Opd::first(),
            \App\Models\Pegawai::class => \App\Models\Pegawai::first(),
            \App\Models\Pemeriksaan::class => \App\Models\Pemeriksaan::first(),
            \App\Models\Rekomendasi::class => \App\Models\Rekomendasi::first(),
            \App\Models\Role::class => \App\Models\Role::first(),
            \App\Models\Temuan::class => \App\Models\Temuan::first(),
            \App\Models\TimPemeriksa::class => \App\Models\TimPemeriksa::first(),
            \App\Models\TindakLanjut::class => \App\Models\TindakLanjut::first(),
            \App\Models\User::class => \App\Models\User::first(),
        ];

        foreach ($this->relationMap() as $class => $relations) {
            $instance = $instances[$class] ?? null;

            if ($instance === null) {
                continue;
            }

            foreach ($relations as $relation) {
                $result = $instance->{$relation}();

                $this->assertInstanceOf(
                    \Illuminate\Database\Eloquent\Relations\Relation::class,
                    $result,
                    "{$class}::{$relation}() tidak mengembalikan objek Relation."
                );
            }
        }
    }

    /**
     * Memastikan setiap nama relasi yang di-eager-load controller benar-benar ada.
     *
     * Hanya nama relasi yang divalidasi:
     *   - string tunggal  : with('relasi')
     *   - kunci larik     : with(['relasi' => fn ($q) => ...])
     *   - nilai string    : with(['relasi'])
     *
     * Isi closure (mis. $tq->where('status_verifikasi', 'SESUAI')) TIDAK
     * diperiksa, karena itu kondisi query, bukan nama relasi.
     */
    public function test_relation_paths_used_in_controllers_are_valid(): void
    {
        $this->seed();

        $ownerOf = [];
        foreach ($this->relationMap() as $class => $relations) {
            foreach ($relations as $rel) {
                $ownerOf[$rel] = $class;
            }
        }

        $violations = [];

        foreach (glob(app_path('Http/Controllers/*.php')) as $file) {
            $content = file_get_contents($file);
            $basename = basename($file);

            foreach ($this->extractEagerLoadRelationNames($content) as $path) {
                if (in_array($path, self::NON_RELATION_WITH_KEYS, true)) {
                    continue;
                }

                $first = explode('.', $path)[0];

                if (! isset($ownerOf[$first])) {
                    $violations[] = "{$basename} → '{$path}' (segmen '{$first}' bukan relasi model mana pun)";
                }
            }
        }

        $unique = array_values(array_unique($violations));

        $this->assertSame(
            [],
            $unique,
            'Controller memuat nama relasi yang tidak terdefinisi:'
            . PHP_EOL . implode(PHP_EOL, $unique)
        );
    }

    /**
     * Ekstrak nama relasi dari with()/load()/loadMissing() tanpa menyentuh isi closure.
     *
     * @return array<int, string>
     */
    private function extractEagerLoadRelationNames(string $content): array
    {
        $names = [];

        $offset = 0;

        while (preg_match(
            '/(?:^|[^a-zA-Z_])(?:with|load|loadMissing)\(\s*/',
            $content,
            $m,
            PREG_OFFSET_CAPTURE,
            $offset
        )) {
            $start = $m[0][1] + strlen($m[0][0]);
            $offset = $start;

            $first = $content[$start] ?? '';

            if ($first === '[') {
                // Ambil isi larik teratas dengan menghitung kedalaman kurung siku
                // agar closure di dalamnya tidak ikut terbaca sebagai nama relasi.
                $depth = 0;
                $i = $start;
                $len = strlen($content);

                while ($i < $len) {
                    $ch = $content[$i];

                    if ($ch === '[') {
                        $depth++;
                    } elseif ($ch === ']') {
                        $depth--;

                        if ($depth === 0) {
                            break;
                        }
                    }

                    $i++;
                }

                $block = substr($content, $start + 1, $i - $start - 1);

                // Ambil hanya elemen tingkat atas: 'nama' atau 'nama' => ...
                foreach ($this->topLevelArrayKeys($block) as $key) {
                    $names[] = $key;
                }

                $offset = $i + 1;
            } elseif ($first === "'" || $first === '"') {
                if (preg_match('/^[\'"]([a-zA-Z_][a-zA-Z0-9_.]*)[\'"]/', substr($content, $start), $sm)) {
                    $names[] = $sm[1];
                }

                $offset = $start + 1;
            }
        }

        return $names;
    }

    /**
     * Ambil nama relasi pada tingkat teratas larik, melewati isi closure.
     *
     * @return array<int, string>
     */
    private function topLevelArrayKeys(string $block): array
    {
        $keys = [];
        $depth = 0;
        $buffer = '';
        $len = strlen($block);

        for ($i = 0; $i < $len; $i++) {
            $ch = $block[$i];

            if ($ch === '[' || $ch === '(') {
                $depth++;
            } elseif ($ch === ']' || $ch === ')') {
                $depth--;
            }

            if ($depth === 0 && ($ch === ',' || $i === $len - 1)) {
                // Simpan segmen tingkat atas, lalu ambil string pertama di dalamnya
                if (preg_match('/[\'"]([a-zA-Z_][a-zA-Z0-9_.]*)[\'"]/', $buffer, $bm)) {
                    $keys[] = $bm[1];
                }

                $buffer = '';
                continue;
            }

            $buffer .= $ch;
        }

        return $keys;
    }
}
