<?php

namespace App\Http\Controllers;

use App\Models\Pemeriksaan;
use App\Models\TahunAnggaran;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TahunAnggaranController extends Controller
{
    public function index(): View
    {
        $tahunAnggarans = TahunAnggaran::query()->orderByDesc('tahun')->get();
        $usedYears = Pemeriksaan::withTrashed()->distinct()->pluck('tahun')->map(fn ($year) => (int) $year)->all();

        return view('tahun-anggaran.index', compact('tahunAnggarans', 'usedYears'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tahun' => ['required', 'integer', 'min:1900', 'max:9999', Rule::unique('tahun_anggarans', 'tahun')],
            'is_current' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($validated): void {
            $makeCurrent = ! empty($validated['is_current']) || ! TahunAnggaran::query()->where('is_current', true)->exists();

            if ($makeCurrent) {
                TahunAnggaran::query()->update(['is_current' => false]);
            }

            TahunAnggaran::query()->create([
                'tahun' => $validated['tahun'],
                'is_active' => true,
                'is_current' => $makeCurrent,
            ]);
        });

        return redirect()->route('tahun-anggaran.index')->with('success', 'Tahun anggaran berhasil ditambahkan.');
    }

    public function toggleStatus(TahunAnggaran $tahunAnggaran): RedirectResponse
    {
        if ($tahunAnggaran->is_active && $tahunAnggaran->is_current) {
            return back()->with('error', 'Tahun berjalan harus tetap aktif. Tetapkan tahun berjalan lain terlebih dahulu.');
        }

        if ($tahunAnggaran->is_active && Pemeriksaan::withTrashed()->where('tahun', $tahunAnggaran->tahun)->exists()) {
            return back()->with('error', 'Tahun yang sudah memiliki data pemeriksaan tidak dapat dinonaktifkan agar tetap tersedia saat mengedit data historis.');
        }

        $tahunAnggaran->update(['is_active' => ! $tahunAnggaran->is_active]);

        return back()->with('success', 'Status tahun anggaran berhasil diperbarui.');
    }

    public function setCurrent(TahunAnggaran $tahunAnggaran): RedirectResponse
    {
        if (! $tahunAnggaran->is_active) {
            return back()->with('error', 'Aktifkan tahun anggaran sebelum menjadikannya tahun berjalan.');
        }

        DB::transaction(function () use ($tahunAnggaran): void {
            TahunAnggaran::query()->update(['is_current' => false]);
            $tahunAnggaran->update(['is_current' => true]);
        });

        return back()->with('success', "Tahun {$tahunAnggaran->tahun} ditetapkan sebagai tahun berjalan.");
    }

    public function destroy(TahunAnggaran $tahunAnggaran): RedirectResponse
    {
        if ($tahunAnggaran->is_current) {
            return back()->with('error', 'Tahun berjalan tidak dapat dihapus. Tetapkan tahun berjalan lain terlebih dahulu.');
        }

        $hasRecords = Pemeriksaan::withTrashed()->where('tahun', $tahunAnggaran->tahun)->exists();
        if ($hasRecords) {
            return back()->with('error', 'Tahun anggaran tidak dapat dihapus karena masih memiliki data pemeriksaan.');
        }

        $tahunAnggaran->delete();

        return redirect()->route('tahun-anggaran.index')->with('success', 'Tahun anggaran berhasil dihapus.');
    }
}
