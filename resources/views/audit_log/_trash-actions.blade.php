{{--
    Tombol aksi arsip: Pulihkan & Hapus Permanen.
    Hanya dirender untuk Super Admin (route-nya memang Super Admin-only).

    @param string $type             'pemeriksaan' | 'temuan' | 'opd' | 'pegawai' | 'user'
    @param int    $id
    @param string $restoreMessage   Pesan konfirmasi pulihkan
    @param string $forceMessage     Pesan konfirmasi hapus permanen
--}}
@if(auth()->user()->isSuperAdmin())
    <form method="POST" action="{{ route('audit-log.restore', ['type' => $type, 'id' => $id]) }}" class="inline">
        @csrf
        <button type="submit" onclick="return confirm('{{ addslashes($restoreMessage) }}');"
                class="inline-flex items-center gap-1 rounded-xl bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition shadow-2xs cursor-pointer">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Pulihkan
        </button>
    </form>

    <form method="POST" action="{{ route('audit-log.force-delete', ['type' => $type, 'id' => $id]) }}" class="inline">
        @csrf
        @method('DELETE')
        <button type="submit" onclick="return confirm('{{ addslashes($forceMessage) }}');"
                class="inline-flex items-center gap-1 rounded-xl bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-700 border border-rose-200 hover:bg-rose-100 transition shadow-2xs cursor-pointer">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            Hapus Permanen
        </button>
    </form>
@endif
