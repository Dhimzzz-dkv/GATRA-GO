<?php

namespace App\Http\Requests;

use App\Models\Pegawai;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePemeriksaanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $pemeriksaan = $this->route('pemeriksaan');

        if (! $user->canManageSpt()) {
            return false;
        }

        if ($pemeriksaan->is_lhp_only_submission) {
            return false;
        }

        if ($user->isAdminSumber() && $pemeriksaan->sumber_pemeriksaan !== $user->getSumberPemeriksaan()) {
            return false;
        }

        if ($user->isAdminSumber()) {
            $this->merge(['sumber_pemeriksaan' => $user->getSumberPemeriksaan()]);
        }

        return true;
    }

    /**
     * Ensure an active Inspector is available to be assigned as team lead.
     *
     * @return array<int, callable(\Illuminate\Contracts\Validation\Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! Pegawai::inspekturDaerah()->exists()) {
                $validator->errors()->add(
                    'tim_pemeriksa',
                    'Pegawai aktif dengan jabatan Inspektur belum tersedia di Master Pegawai.'
                );
            }
        }];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'sumber_pemeriksaan' => ['required', Rule::in(['Inspektorat'])],
            'opd_id' => ['nullable', 'exists:opds,id'],
            'tahun' => ['required', 'integer', Rule::exists('tahun_anggarans', 'tahun')->where('is_active', true)],
            'judul' => ['nullable', 'string', 'max:255'],
            'nomor_spt' => ['required', 'string', 'max:100'],
            'tanggal_spt' => ['nullable', 'date'],
            'nama_tim' => ['nullable', 'string', 'max:255'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'dokumen_spt' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],

            // Tim Pemeriksa (Array)
            'tim_pemeriksa' => ['required', 'array'],
            'tim_pemeriksa.*.pegawai_id' => ['nullable', 'exists:pegawais,id'],
            'tim_pemeriksa.*.peran_tim' => ['nullable', 'string', 'max:50'],

            // Multi-LHP (Array)
            'lhps' => ['nullable', 'array'],
            'lhps.*.id' => ['nullable', 'exists:lhps,id'],
            'lhps.*.nomor_lhp' => ['nullable', 'string', 'max:100'],
            'lhps.*.tanggal_lhp' => ['nullable', 'date'],
            'lhps.*.judul_lhp' => ['nullable', 'string', 'max:255'],
            'lhps.*.ringkasan' => ['nullable', 'string'],
            'lhps.*.dokumen_lhp' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:20480'],
            'lhps.*.opd_ids' => ['nullable', 'array'],
            'lhps.*.opd_ids.*' => ['exists:opds,id'],
            'lhps.*.ada_administrasi' => ['nullable'],
            'lhps.*.ada_kerugian' => ['nullable'],
            'lhps.*.temuans_administrasi' => ['nullable', 'array'],
            'lhps.*.temuans_administrasi.*.id' => ['nullable'],
            'lhps.*.temuans_administrasi.*.uraian' => ['nullable', 'string'],
            'lhps.*.temuans_administrasi.*.penyebab' => ['nullable', 'string'],
            'lhps.*.temuans_administrasi.*.rekomendasi' => ['nullable', 'string'],
            'lhps.*.temuans_administrasi.*.rekomendasi_items' => ['nullable', 'array'],
            'lhps.*.temuans_administrasi.*.rekomendasi_items.*' => ['nullable', 'string'],
            'lhps.*.temuan_kerugian' => ['nullable', 'array'],
            'lhps.*.temuan_kerugian.id' => ['nullable'],
            'lhps.*.temuan_kerugian.kategori_kerugian' => ['nullable', 'in:Kerugian Daerah,Kerugian Negara'],
            'lhps.*.temuan_kerugian.jenis_kerugian' => ['nullable', 'in:Internal,Eksternal'],
            'lhps.*.temuan_kerugian.nilai_kerugian' => ['nullable', 'string'],
            'lhps.*.temuan_kerugian.uraian' => ['nullable', 'string'],
            'lhps.*.temuan_kerugian.rekomendasi' => ['nullable', 'string'],
            'lhps.*.temuans_kerugian' => ['nullable', 'array'],
            'lhps.*.temuans_kerugian.*.id' => ['nullable'],
            'lhps.*.temuans_kerugian.*.kategori_kerugian' => ['nullable', 'in:Kerugian Daerah,Kerugian Negara'],
            'lhps.*.temuans_kerugian.*.jenis_kerugian' => ['nullable', 'in:Internal,Eksternal'],
            'lhps.*.temuans_kerugian.*.nilai_kerugian' => ['nullable', 'string'],
            'lhps.*.temuans_kerugian.*.uraian' => ['nullable', 'string'],
            'lhps.*.temuans_kerugian.*.penyebab' => ['nullable', 'string'],
            'lhps.*.temuans_kerugian.*.rekomendasi' => ['nullable', 'string'],

            // Fallback Single LHP Fields
            'lhp_id' => ['nullable', 'exists:lhps,id'],
            'nomor_lhp' => ['nullable', 'string', 'max:100'],
            'tanggal_lhp' => ['nullable', 'date'],
            'judul_lhp' => ['nullable', 'string', 'max:255'],
            'ringkasan_lhp' => ['nullable', 'string'],
            'dokumen_lhp' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:20480'],
            'opd_ids' => ['nullable', 'array'],
            'opd_ids.*' => ['exists:opds,id'],
        ];
    }
}
