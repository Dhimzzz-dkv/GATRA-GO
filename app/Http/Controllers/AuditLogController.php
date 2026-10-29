<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Opd;
use App\Models\Pegawai;
use App\Models\Pemeriksaan;
use App\Models\Temuan;
use App\Models\TahunAnggaran;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AuditLogController extends Controller
{
    /**
     * Display listing of system audit trail logs.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user->isSuperAdmin() && ! $user->isPemantau()) {
            abort(403, 'Akses log audit hanya untuk Super Admin dan Pemantau.');
        }

        $search = $request->input('search');
        $action = $request->input('action');
        $module = $request->input('module');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = AuditLog::with('user')
            ->when($search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('description', 'like', "%{$search}%")
                        ->orWhere('user_name', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%");
                });
            })
            ->when($action, fn ($q) => $q->where('action', $action))
            ->when($module, fn ($q) => $q->where('module', $module))
            ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo));

        $logs = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        $actions = AuditLog::select('action')->distinct()->pluck('action');
        $modules = AuditLog::select('module')->distinct()->pluck('module');

        return view('audit_log.index', compact('logs', 'actions', 'modules', 'search', 'action', 'module', 'dateFrom', 'dateTo'));
    }

    /**
     * Tipe entitas yang didukung halaman arsip pemulihan.
     *
     * @var array<int, string>
     */
    public const TRASH_TYPES = ['pemeriksaan', 'temuan', 'opd', 'pegawai', 'user'];

    /**
     * Display trash / soft-deleted records archive for recovery.
     */
    public function trash(Request $request)
    {
        $user = $request->user();

        if (! $user->isSuperAdmin() && ! $user->isPemantau()) {
            abort(403, 'Akses arsip pemulihan data hanya untuk Super Admin dan Pemantau.');
        }

        $type = $request->input('type', 'pemeriksaan');
        if (! in_array($type, self::TRASH_TYPES, true)) {
            $type = 'pemeriksaan';
        }

        $search = $request->input('search');
        $sumber = $request->input('sumber');
        $tahun = $request->input('tahun');

        $trashedItems = $this->buildTrashQuery($type, $search, $sumber, $tahun)
            ->paginate(15)
            ->withQueryString();

        $typeCounts = [
            'pemeriksaan' => Pemeriksaan::onlyTrashed()->count(),
            'temuan' => Temuan::onlyTrashed()->count(),
            'opd' => Opd::onlyTrashed()->count(),
            'pegawai' => Pegawai::onlyTrashed()->count(),
            'user' => User::onlyTrashed()->count(),
        ];

        $totalDeleted = $typeCounts['pemeriksaan'];

        $sumberList = ['BPK', 'BPKP', 'ITJEN', 'Inspektorat'];
        $years = TahunAnggaran::allYears();

        return view('audit_log.trash', compact(
            'trashedItems',
            'type',
            'typeCounts',
            'totalDeleted',
            'search',
            'sumber',
            'tahun',
            'sumberList',
            'years'
        ));
    }

    /**
     * Bangun query arsip sesuai tipe entitas.
     */
    private function buildTrashQuery(string $type, ?string $search, ?string $sumber, ?string $tahun)
    {
        return match ($type) {
            'temuan' => Temuan::onlyTrashed()
                ->with(['opd', 'lhp', 'pemeriksaan'])
                ->when($search, function ($q, $search) {
                    $q->where(function ($sub) use ($search) {
                        $sub->where('judul_temuan', 'like', "%{$search}%")
                            ->orWhere('nomor_temuan', 'like', "%{$search}%")
                            ->orWhereHas('opd', fn ($oq) => $oq->where('nama', 'like', "%{$search}%"));
                    });
                })
                ->when($sumber, fn ($q) => $q->where('sumber_pemeriksaan', $sumber))
                ->when($tahun, fn ($q) => $q->where('tahun', $tahun))
                ->orderBy('deleted_at', 'desc'),

            'opd' => Opd::onlyTrashed()
                ->when($search, function ($q, $search) {
                    $q->where(function ($sub) use ($search) {
                        $sub->where('nama', 'like', "%{$search}%")
                            ->orWhere('kode', 'like', "%{$search}%");
                    });
                })
                ->orderBy('deleted_at', 'desc'),

            'pegawai' => Pegawai::onlyTrashed()
                ->when($search, function ($q, $search) {
                    $q->where(function ($sub) use ($search) {
                        $sub->where('nama', 'like', "%{$search}%")
                            ->orWhere('nip', 'like', "%{$search}%")
                            ->orWhere('jabatan', 'like', "%{$search}%");
                    });
                })
                ->orderBy('deleted_at', 'desc'),

            'user' => User::onlyTrashed()
                ->with('role')
                ->when($search, function ($q, $search) {
                    $q->where(function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                })
                ->orderBy('deleted_at', 'desc'),

            default => Pemeriksaan::onlyTrashed()
                ->with(['opd', 'creator', 'lhps', 'temuans' => fn ($q) => $q->withTrashed()])
                ->when($search, function ($q, $search) {
                    $q->where(function ($sub) use ($search) {
                        $sub->where('nomor_spt', 'like', "%{$search}%")
                            ->orWhere('judul', 'like', "%{$search}%")
                            ->orWhereHas('opd', fn ($oq) => $oq->where('nama', 'like', "%{$search}%"));
                    });
                })
                ->when($sumber, fn ($q) => $q->where('sumber_pemeriksaan', $sumber))
                ->when($tahun, fn ($q) => $q->where('tahun', $tahun))
                ->orderBy('deleted_at', 'desc'),
        };
    }

    /**
     * Show full detail and breakdown of an archived / soft-deleted Pemeriksaan.
     */
    public function showArchivedPemeriksaan(Request $request, int $id)
    {
        $user = $request->user();

        if (! $user->isSuperAdmin() && ! $user->isPemantau()) {
            abort(403, 'Akses ditolak.');
        }

        $pemeriksaan = Pemeriksaan::withTrashed()
            ->with([
                'opd',
                'creator',
                'reviewer',
                'timPemeriksas.pegawai',
                'lhps.opds',
                'lhps.temuans' => function ($q) {
                    $q->withTrashed()->with(['opd', 'tindakLanjuts.creator', 'tindakLanjuts.verifier']);
                },
                'temuans' => function ($q) {
                    $q->withTrashed()->with(['opd', 'lhp', 'tindakLanjuts.creator', 'tindakLanjuts.verifier']);
                },
            ])
            ->findOrFail($id);

        return view('audit_log.show_pemeriksaan', compact('pemeriksaan'));
    }

    /**
     * Download SPT document for archived Pemeriksaan.
     */
    public function downloadArchivedSpt(Request $request, int $id)
    {
        $user = $request->user();

        if (! $user->isSuperAdmin() && ! $user->isPemantau()) {
            abort(403, 'Akses ditolak.');
        }

        $pemeriksaan = Pemeriksaan::withTrashed()->findOrFail($id);

        if (! $pemeriksaan->dokumen_spt) {
            return back()->with('error', 'Dokumen SPT tidak ditemukan.');
        }

        $disk = Storage::disk('local')->exists($pemeriksaan->dokumen_spt) ? 'local' : (Storage::disk('public')->exists($pemeriksaan->dokumen_spt) ? 'public' : null);

        if (! $disk) {
            return back()->with('error', 'Berkas SPT fisik tidak ditemukan pada server.');
        }

        AuditLogger::log('DOWNLOAD', 'SPT', "Mengunduh berkas SPT dari arsip (Pemeriksaan ID: {$pemeriksaan->id}, SPT: {$pemeriksaan->nomor_spt}).");

        $downloadFilename = str_replace(['/', '\\'], '_', $pemeriksaan->nama_dokumen_asli ?? ('SPT_' . str_replace(['/', '\\'], '_', $pemeriksaan->nomor_spt) . '.pdf'));

        return Storage::disk($disk)->download(
            $pemeriksaan->dokumen_spt,
            $downloadFilename
        );
    }

    /**
     * Restore a soft-deleted entity.
     */
    public function restore(Request $request, string $type, int $id)
    {
        $user = $request->user();

        if (! $user->isSuperAdmin()) {
            abort(403, 'Akses ditolak. Hanya Super Admin yang dapat memulihkan data.');
        }

        if ($type === 'pemeriksaan') {
            $pemeriksaan = Pemeriksaan::onlyTrashed()->findOrFail($id);
            $pemeriksaan->restore();

            // Cascade restore child temuans
            $pemeriksaan->temuans()->onlyTrashed()->restore();

            AuditLogger::log('RESTORE', 'PEMERIKSAAN', "Memulihkan Pemeriksaan (ID: {$id}, SPT: {$pemeriksaan->nomor_spt}) beserta temuan terkait dari arsip sampah.");

            return redirect()->route('audit-log.trash')->with('success', "Pemeriksaan {$pemeriksaan->nomor_spt} beserta data temuannya berhasil dipulihkan.");
        }

        $model = match ($type) {
            'temuan' => Temuan::onlyTrashed()->findOrFail($id),
            'opd' => Opd::onlyTrashed()->findOrFail($id),
            'pegawai' => Pegawai::onlyTrashed()->findOrFail($id),
            'user' => User::onlyTrashed()->findOrFail($id),
            default => abort(404),
        };

        $model->restore();

        AuditLogger::log('RESTORE', strtoupper($type), "Memulihkan data {$type} (ID: {$id}) dari arsip sampah.");

        return back()->with('success', "Data {$type} berhasil dipulihkan.");
    }

    /**
     * Permanently delete an entity from database.
     */
    public function forceDelete(Request $request, string $type, int $id)
    {
        $user = $request->user();

        if (! $user->isSuperAdmin()) {
            abort(403, 'Akses ditolak. Hanya Super Admin yang dapat menghapus permanen data.');
        }

        if ($type === 'pemeriksaan') {
            // Muat LHP (termasuk yang dihapus lunak) sebelum pembersihan berkas,
            // karena penghapusan permanen menelusuri berkas tiap LHP.
            $pemeriksaan = Pemeriksaan::onlyTrashed()
                ->with(['lhps' => fn ($q) => $q->withTrashed()])
                ->findOrFail($id);

            // Clean up SPT file
            if ($pemeriksaan->dokumen_spt) {
                if (Storage::disk('local')->exists($pemeriksaan->dokumen_spt)) {
                    Storage::disk('local')->delete($pemeriksaan->dokumen_spt);
                } elseif (Storage::disk('public')->exists($pemeriksaan->dokumen_spt)) {
                    Storage::disk('public')->delete($pemeriksaan->dokumen_spt);
                }
            }

            // Clean up child LHP files & records
            foreach ($pemeriksaan->lhps as $lhp) {
                if ($lhp->dokumen_lhp) {
                    if (Storage::disk('local')->exists($lhp->dokumen_lhp)) {
                        Storage::disk('local')->delete($lhp->dokumen_lhp);
                    } elseif (Storage::disk('public')->exists($lhp->dokumen_lhp)) {
                        Storage::disk('public')->delete($lhp->dokumen_lhp);
                    }
                }
                $lhp->opds()->detach();
                $lhp->delete();
            }

            // Clean up child temuans & tindak lanjuts
            foreach ($pemeriksaan->temuans()->withTrashed()->get() as $temuan) {
                foreach ($temuan->tindakLanjuts()->withTrashed()->get() as $tl) {
                    if ($tl->dokumen_bukti) {
                        Storage::disk('local')->delete($tl->dokumen_bukti);
                    }
                    if ($tl->dokumen_setor) {
                        Storage::disk('local')->delete($tl->dokumen_setor);
                    }
                    $tl->forceDelete();
                }
                if ($temuan->dokumen_pendukung) {
                    Storage::disk('local')->delete($temuan->dokumen_pendukung);
                }
                $temuan->forceDelete();
            }

            // Clean up Tim Pemeriksa
            $pemeriksaan->timPemeriksas()->delete();

            $nomorSpt = $pemeriksaan->nomor_spt;
            $pemeriksaan->forceDelete();

            AuditLogger::log('FORCE_DELETE', 'PEMERIKSAAN', "Menghapus permanen Pemeriksaan (ID: {$id}, SPT: {$nomorSpt}) dari database.");

            return redirect()->route('audit-log.trash')->with('success', "Pemeriksaan {$nomorSpt} telah dihapus permanen dari sistem.");
        }

        $model = match ($type) {
            'temuan' => Temuan::onlyTrashed()->findOrFail($id),
            'opd' => Opd::onlyTrashed()->findOrFail($id),
            'pegawai' => Pegawai::onlyTrashed()->findOrFail($id),
            'user' => User::onlyTrashed()->findOrFail($id),
            default => abort(404),
        };

        // Temuan dapat memiliki dokumen pendukung & tindak lanjut dengan bukti
        // setor. Bersihkan berkasnya agar tidak meninggalkan file orphan.
        if ($model instanceof Temuan) {
            foreach ($model->tindakLanjuts()->withTrashed()->get() as $tl) {
                if ($tl->dokumen_bukti) {
                    Storage::disk('local')->delete($tl->dokumen_bukti);
                }
                if ($tl->dokumen_setor) {
                    Storage::disk('local')->delete($tl->dokumen_setor);
                }
                $tl->forceDelete();
            }

            if ($model->dokumen_pendukung) {
                Storage::disk('local')->delete($model->dokumen_pendukung);
            }
        }

        $model->forceDelete();

        AuditLogger::log('FORCE_DELETE', strtoupper($type), "Menghapus permanen data {$type} (ID: {$id}) dari database.");

        return back()->with('success', "Data {$type} berhasil dihapus permanen.");
    }
}
