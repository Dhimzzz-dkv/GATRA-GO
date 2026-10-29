<?php

namespace App\Http\Controllers;

use App\Models\Pemeriksaan;
use App\Models\Pegawai;
use App\Models\TimPemeriksa;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TimPemeriksaController extends Controller
{
    /**
     * Store a newly created team member.
     */
    public function store(Request $request, Pemeriksaan $pemeriksaan)
    {
        $user = $request->user();

        // Check if user has write permissions for this pemeriksaan
        if ($user->isPemantau() || $user->isOpd()) {
            abort(403, 'Akses ditolak.');
        }

        if ($user->isAdminSumber() && $pemeriksaan->sumber_pemeriksaan !== $user->getSumberPemeriksaan()) {
            abort(403, 'Anda hanya dapat mengelola tim untuk sumber pemeriksaan ' . $user->getSumberPemeriksaan());
        }

        $pegawaiInspekturId = Pegawai::inspekturDaerah()->value('id');

        $validated = $request->validate([
            'pegawai_id' => [
                'required',
                'exists:pegawais,id',
                Rule::notIn($pegawaiInspekturId ? [$pegawaiInspekturId] : []),
                Rule::unique('tim_pemeriksas')->where(function ($query) use ($pemeriksaan) {
                    return $query->where('pemeriksaan_id', $pemeriksaan->id);
                }),
            ],
            'peran_tim' => ['required', Rule::in(['Asisten Penanggung Jawab', 'Pengendali Teknis', 'Ketua Tim', 'Anggota Tim'])],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ], [
            'pegawai_id.unique' => 'Pegawai tersebut sudah terdaftar di dalam tim pemeriksaan ini.',
            'pegawai_id.not_in' => 'Inspektur telah dikunci sebagai Penanggung Jawab tim.',
        ]);

        $validated['pemeriksaan_id'] = $pemeriksaan->id;

        TimPemeriksa::create($validated);

        return back()->with('success', 'Anggota tim pemeriksa berhasil ditambahkan.');
    }

    /**
     * Remove the specified team member.
     */
    public function destroy(Request $request, Pemeriksaan $pemeriksaan, TimPemeriksa $timPemeriksa)
    {
        $user = $request->user();

        if ($user->isPemantau() || $user->isOpd()) {
            abort(403, 'Akses ditolak.');
        }

        if ($user->isAdminSumber() && $pemeriksaan->sumber_pemeriksaan !== $user->getSumberPemeriksaan()) {
            abort(403, 'Akses ditolak.');
        }

        if ($timPemeriksa->pemeriksaan_id !== $pemeriksaan->id) {
            abort(404);
        }

        if ($timPemeriksa->peran_tim === 'Penanggung Jawab') {
            return back()->with('error', 'Penanggung Jawab tidak dapat dihapus karena ditetapkan kepada Inspektur.');
        }

        // Muat relasi pegawai lebih dulu: pesan sukses menyebut nama anggota.
        $timPemeriksa->loadMissing('pegawai');
        $namaPegawai = $timPemeriksa->pegawai->nama ?? 'Anggota tim';
        $timPemeriksa->delete();

        return back()->with('success', "{$namaPegawai} berhasil dihapus dari tim pemeriksaan.");
    }
}
