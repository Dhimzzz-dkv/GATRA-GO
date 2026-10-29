<?php

namespace App\Http\Controllers;

use App\Models\Pegawai;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PegawaiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $unit = $request->input('unit');
        $status = $request->input('status');

        $pegawais = Pegawai::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%")
                      ->orWhere('nip', 'like', "%{$search}%")
                      ->orWhere('jabatan', 'like', "%{$search}%");
                });
            })
            ->when($unit, function ($query, $unit) {
                $query->where('unit_bidang', $unit);
            })
            ->when($status !== null && $status !== '', function ($query) use ($status) {
                $query->where('is_active', $status == '1');
            })
            ->orderBy('nama', 'asc')
            ->paginate(10)
            ->withQueryString();

        $units = Pegawai::select('unit_bidang')->distinct()->whereNotNull('unit_bidang')->pluck('unit_bidang');

        return view('pegawai.index', compact('pegawais', 'search', 'unit', 'status', 'units'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pegawai.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nip' => ['nullable', 'string', 'max:50', 'unique:pegawais,nip'],
            'nama' => ['required', 'string', 'max:255'],
            'jabatan' => ['required', 'string', 'max:255'],
            'pangkat_golongan' => ['nullable', 'string', 'max:100'],
            'unit_bidang' => ['required', 'string', 'max:100'],
            'no_hp' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->has('is_active');

        Pegawai::create($validated);

        return redirect()->route('pegawai.index')->with('success', 'Data Pegawai Inspektorat berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Pegawai $pegawai)
    {
        // View pegawai.show menampilkan akun pengguna yang terhubung,
        // sehingga relasi user (beserta role-nya) wajib dimuat lebih dulu.
        $pegawai->load('user.role', 'user.opd');

        return view('pegawai.show', compact('pegawai'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Pegawai $pegawai)
    {
        return view('pegawai.edit', compact('pegawai'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Pegawai $pegawai)
    {
        $validated = $request->validate([
            'nip' => ['nullable', 'string', 'max:50', Rule::unique('pegawais', 'nip')->ignore($pegawai->id)],
            'nama' => ['required', 'string', 'max:255'],
            'jabatan' => ['required', 'string', 'max:255'],
            'pangkat_golongan' => ['nullable', 'string', 'max:100'],
            'unit_bidang' => ['required', 'string', 'max:100'],
            'no_hp' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->has('is_active');

        $pegawai->update($validated);

        return redirect()->route('pegawai.index')->with('success', 'Data Pegawai Inspektorat berhasil diperbarui.');
    }

    /**
     * Toggle active status.
     */
    public function toggleStatus(Pegawai $pegawai)
    {
        $pegawai->update(['is_active' => ! $pegawai->is_active]);

        $statusText = $pegawai->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Status Pegawai {$pegawai->nama} berhasil {$statusText}.");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Pegawai $pegawai)
    {
        if ($pegawai->user()->exists()) {
            return back()->with('error', 'Pegawai tidak dapat dihapus karena masih terhubung dengan akun pengguna.');
        }

        $pegawai->delete();

        return redirect()->route('pegawai.index')->with('success', 'Data Pegawai berhasil dihapus.');
    }
}
