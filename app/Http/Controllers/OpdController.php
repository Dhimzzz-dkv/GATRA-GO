<?php

namespace App\Http\Controllers;

use App\Models\Opd;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OpdController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');

        $opds = Opd::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%")
                      ->orWhere('kode', 'like', "%{$search}%")
                      ->orWhere('kepala_opd', 'like', "%{$search}%");
                });
            })
            ->when($status !== null && $status !== '', function ($query) use ($status) {
                $query->where('is_active', $status == '1');
            })
            ->orderBy('nama', 'asc')
            ->paginate(10)
            ->withQueryString();

        return view('opd.index', compact('opds', 'search', 'status'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('opd.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:50', 'unique:opds,kode'],
            'nama' => ['required', 'string', 'max:255'],
            'singkatan' => ['nullable', 'string', 'max:50'],
            'kepala_opd' => ['nullable', 'string', 'max:255'],
            'nip_kepala' => ['nullable', 'string', 'max:50'],
            'alamat' => ['nullable', 'string'],
            'telepon' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->has('is_active');

        Opd::create($validated);

        return redirect()->route('opd.index')->with('success', 'Data OPD berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Opd $opd)
    {
        return view('opd.show', compact('opd'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Opd $opd)
    {
        return view('opd.edit', compact('opd'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Opd $opd)
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:50', Rule::unique('opds', 'kode')->ignore($opd->id)],
            'nama' => ['required', 'string', 'max:255'],
            'singkatan' => ['nullable', 'string', 'max:50'],
            'kepala_opd' => ['nullable', 'string', 'max:255'],
            'nip_kepala' => ['nullable', 'string', 'max:50'],
            'alamat' => ['nullable', 'string'],
            'telepon' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->has('is_active');

        $opd->update($validated);

        return redirect()->route('opd.index')->with('success', 'Data OPD berhasil diperbarui.');
    }

    /**
     * Toggle active status.
     */
    public function toggleStatus(Opd $opd)
    {
        $opd->update(['is_active' => ! $opd->is_active]);

        $statusText = $opd->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Status OPD {$opd->nama} berhasil {$statusText}.");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Opd $opd)
    {
        if ($opd->users()->exists()) {
            return back()->with('error', 'OPD tidak dapat dihapus karena masih terhubung dengan akun pengguna.');
        }

        $opd->delete();

        return redirect()->route('opd.index')->with('success', 'Data OPD berhasil dihapus.');
    }
}
