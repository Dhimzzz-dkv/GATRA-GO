<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    /**
     * Aksi workflow pengajuan yang mengubah status_pengajuan / catatan.
     * Ditangani terpisah karena tidak masuk kategori CREATE / UPDATE / DELETE.
     *
     * @var array<int, string>
     */
    public const WORKFLOW_ACTIONS = [
        'SUBMIT',
        'CANCEL_SUBMIT',
        'APPROVE',
        'REVISE',
    ];

    /**
     * Kolom yang dilacak untuk aksi workflow.
     *
     * Setiap model hanya memiliki sebagian kolom ini. Contoh:
     *   - `pemeriksaans` memakai `catatan_persetujuan` (bukan `catatan_revisi`)
     *   - `temuans`      memakai `catatan_revisi` (bukan `catatan_persetujuan`)
     *
     * Karena itu daftar ini HARUS disaring per model sebelum diakses —
     * mengakses kolom yang tidak ada akan melempar MissingAttributeException
     * bila Model::preventAccessingMissingAttributes() aktif.
     *
     * @var array<int, string>
     */
    private const WORKFLOW_TRACKED_COLUMNS = [
        'status_pengajuan',
        'catatan_revisi',
        'catatan_persetujuan',
        'approved_by',
        'approved_at',
    ];

    /**
     * Cache kolom per tabel agar pemeriksaan tidak memicu query berulang.
     *
     * @var array<string, array<int, string>>
     */
    private static array $kolomCache = [];

    /**
     * Daftar kolom yang benar-benar dimiliki sebuah model.
     *
     * Sumber ditelusuri berurutan dari yang paling murah:
     *   1. atribut yang sudah dimuat (tanpa query)
     *   2. daftar fillable (tanpa query)
     *   3. skema tabel — di-cache per tabel agar tidak berulang
     *
     * @return array<int, string>
     */
    private static function kolomModel(\Illuminate\Database\Eloquent\Model $model): array
    {
        $tabel = $model->getTable();

        $dariAtribut = array_keys($model->getAttributes());
        $dariFillable = $model->getFillable();

        $gabungan = array_values(array_unique(array_merge($dariAtribut, $dariFillable)));

        // Bila kedua sumber di atas sudah mencakup seluruh kolom yang dilacak,
        // tidak perlu menyentuh skema sama sekali.
        $kurang = array_diff(self::WORKFLOW_TRACKED_COLUMNS, $gabungan);

        if (empty($kurang)) {
            return $gabungan;
        }

        if (! isset(self::$kolomCache[$tabel])) {
            try {
                self::$kolomCache[$tabel] = \Illuminate\Support\Facades\Schema::getColumnListing($tabel);
            } catch (\Throwable $e) {
                // Bila skema tidak dapat dibaca (mis. koneksi bermasalah),
                // jangan gagalkan operasi utama — cukup andalkan sumber sebelumnya.
                self::$kolomCache[$tabel] = [];
            }
        }

        return array_values(array_unique(array_merge($gabungan, self::$kolomCache[$tabel])));
    }

    /**
     * Periksa apakah sebuah model memiliki kolom tertentu.
     */
    private static function punyaKolom(\Illuminate\Database\Eloquent\Model $model, string $kolom): bool
    {
        return in_array($kolom, self::kolomModel($model), true);
    }

    /**
     * Record an audit trail log entry.
     */
    public static function log(
        string $action,
        string $module,
        string $description,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?User $user = null
    ): ?AuditLog {
        try {
            $currentUser = $user ?? auth()->user();

            return AuditLog::create([
                'user_id' => $currentUser?->id,
                'user_name' => $currentUser?->official_name ?? $currentUser?->name ?? 'Tamu / Sistem',
                'role_name' => $currentUser?->role?->display_name ?? 'Guest',
                'action' => strtoupper($action),
                'module' => strtoupper($module),
                'description' => $description,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Silently ignore logging failures to not disrupt primary operations
            report($e);
            return null;
        }
    }

    /**
     * Record an audit trail log for model modifications with automatic diff.
     *
     * @param string $action CREATE, UPDATE, DELETE
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $module
     * @param string $description
     * @param array $oldAttributes Snapshot of attributes before update
     * @param User|null $user
     * @return AuditLog|null
     */
    public static function logModelChange(
        string $action,
        \Illuminate\Database\Eloquent\Model $model,
        string $module,
        string $description,
        array $oldAttributes = [],
        ?User $user = null
    ): ?AuditLog {
        $action = strtoupper($action);
        $ignoreColumns = [
            'created_at', 'updated_at', 'deleted_at', 'remember_token',
            'password', 'email_verified_at', 'approved_at', 'verified_at',
            'otp_code', 'otp_expires_at'
        ];

        $oldValues = null;
        $newValues = null;

        if ($action === 'CREATE') {
            $rawAttributes = $model->getAttributes();
            $newValues = \Illuminate\Support\Arr::except($rawAttributes, $ignoreColumns);
        } elseif ($action === 'DELETE') {
            $rawAttributes = !empty($oldAttributes) ? $oldAttributes : $model->getAttributes();
            $oldValues = \Illuminate\Support\Arr::except($rawAttributes, $ignoreColumns);
        } elseif ($action === 'UPDATE') {
            // For UPDATE, compare $oldAttributes with fresh/changed model attributes
            $changes = $model->getChanges();
            if (empty($changes)) {
                // If getChanges() is empty (e.g. called after fresh or without getChanges tracking), compare attributes
                $currentAttributes = $model->getAttributes();
                $changes = [];
                foreach ($currentAttributes as $k => $v) {
                    if (array_key_exists($k, $oldAttributes) && $oldAttributes[$k] !== $v) {
                        $changes[$k] = $v;
                    }
                }
            }

            $diffNew = \Illuminate\Support\Arr::except($changes, $ignoreColumns);
            $diffOld = [];
            foreach (array_keys($diffNew) as $key) {
                $diffOld[$key] = array_key_exists($key, $oldAttributes)
                    ? $oldAttributes[$key]
                    : ($model->getOriginal($key) ?? null);
            }

            // Only assign if there are actual differences
            if (!empty($diffNew)) {
                $oldValues = $diffOld;
                $newValues = $diffNew;
            }
        } elseif (in_array($action, self::WORKFLOW_ACTIONS, true)) {
            // Aksi workflow (SUBMIT / APPROVE / REVISE / CANCEL_SUBMIT)
            // hanya mengubah sejumlah kecil atribut status. Rekam diff-nya secara
            // eksplisit agar Audit Trail tidak berisi entri tanpa rincian perubahan.
            //
            // Hanya kolom yang BENAR-BENAR ada pada model ini yang diperiksa.
            // `Pemeriksaan` tidak memiliki `catatan_revisi`, dan `Temuan` tidak
            // memiliki `catatan_persetujuan` — mengaksesnya tanpa penyaringan
            // akan melempar MissingAttributeException saat mode ketat aktif.
            $diffNew = [];
            $diffOld = [];

            foreach (self::WORKFLOW_TRACKED_COLUMNS as $key) {
                if (! self::punyaKolom($model, $key)) {
                    continue;
                }

                $newVal = $model->getAttribute($key);

                if (array_key_exists($key, $oldAttributes)) {
                    $oldVal = $oldAttributes[$key];
                } elseif (!empty($oldAttributes)) {
                    // Pemanggil mengirim snapshot lama — field yang tidak ada di
                    // dalamnya dianggap tidak berubah.
                    continue;
                } else {
                    $oldVal = $model->getOriginal($key);
                }

                if ($oldVal !== $newVal) {
                    $diffOld[$key] = $oldVal;
                    $diffNew[$key] = $newVal;
                }
            }

            if (!empty($diffNew)) {
                $oldValues = $diffOld;
                $newValues = $diffNew;
            }
        }

        return self::log(
            action: $action,
            module: $module,
            description: $description,
            oldValues: $oldValues,
            newValues: $newValues,
            user: $user
        );
    }
}
