<?php

namespace App\Http\Requests;

use App\Models\Pegawai;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class StorePemeriksaanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user->isPemantau() || $user->isOpd()) {
            return false;
        }

        // In GATRA-GO, SPT is strictly created by Super Admin and Admin Inspektorat
        if (! $user->canManageSpt()) {
            return false;
        }

        // Admin sumber can only store for their assigned source
        if ($user->isAdminSumber()) {
            $this->merge(['sumber_pemeriksaan' => $user->getSumberPemeriksaan()]);
        }

        return true;
    }

    /**
     * Ensure each submitted LHP includes at least one complete finding.
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

            $lhps = $this->input('lhps', []);
            if (! is_array($lhps)) {
                return;
            }

            foreach ($lhps as $index => $lhp) {
                if (! is_array($lhp) || ! $this->hasCompleteFinding($lhp)) {
                    $validator->errors()->add(
                        "lhps.{$index}.temuan",
                        'Setiap LHP wajib memiliki minimal satu temuan lengkap dengan uraian dan rekomendasi.'
                    );
                }
            }
        }];
    }

    /**
     * Check that every selected finding type has complete entries.
     *
     * @param  array<string, mixed>  $lhp
     */
    private function hasCompleteFinding(array $lhp): bool
    {
        $administrationSelected = ! empty($lhp['ada_administrasi']);
        $lossSelected = ! empty($lhp['ada_kerugian']);
        if (! $administrationSelected && ! $lossSelected) {
            return false;
        }

        $administrativeItems = $lhp['temuans_administrasi'] ?? [];
        $administrativeComplete = $administrationSelected
            && is_array($administrativeItems)
            && $administrativeItems !== []
            && collect($administrativeItems)->every(function (mixed $item): bool {
                if (! is_array($item) || trim((string) ($item['uraian'] ?? '')) === '') {
                    return false;
                }

                $recommendationItems = $item['rekomendasi_items'] ?? [];
                $hasRecommendationItem = is_array($recommendationItems)
                    && collect($recommendationItems)->contains(fn (mixed $recommendation): bool => trim((string) $recommendation) !== '');
                $hasLegacyRecommendation = trim((string) ($item['rekomendasi'] ?? '')) !== '';

                return $hasRecommendationItem || $hasLegacyRecommendation;
            });

        $lossItems = $lhp['temuans_kerugian'] ?? [];
        $lossComplete = $lossSelected
            && is_array($lossItems)
            && $lossItems !== []
            && collect($lossItems)->every(function (mixed $item): bool {
                if (! is_array($item)) {
                    return false;
                }

                $amount = preg_replace('/\D/', '', (string) ($item['nilai_kerugian'] ?? ''));
                $classification = $item['jenis_kerugian'] ?? null;
                $category = $item['kategori_kerugian'] ?? null;

                return trim((string) ($item['uraian'] ?? '')) !== ''
                    && trim((string) ($item['rekomendasi'] ?? '')) !== ''
                    && (int) $amount > 0
                    && in_array($classification, ['Internal', 'Eksternal'], true)
                    && in_array($category, ['Kerugian Daerah', 'Kerugian Negara'], true);
            });

        return (! $administrationSelected || $administrativeComplete)
            && (! $lossSelected || $lossComplete)
            && ($administrativeComplete || $lossComplete);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $rules = [
            'sumber_pemeriksaan' => ['required', Rule::in(['Inspektorat'])],
            'opd_id' => ['nullable', 'exists:opds,id'],
            'tahun' => ['required', 'integer', Rule::exists('tahun_anggarans', 'tahun')->where('is_active', true)],
            'judul' => ['nullable', 'string', 'max:255'],
            
            // Tim Pemeriksa
            'tim_pemeriksa' => ['required', 'array'],
            'tim_pemeriksa.*.pegawai_id' => ['nullable', 'exists:pegawais,id'],
            'tim_pemeriksa.*.peran_tim' => ['nullable', 'string', 'max:50'],

            // Multi-LHP (Array)
            'lhps' => ['nullable', 'array'],
            'lhps.*.nomor_lhp' => ['nullable', 'string', 'max:100'],
            'lhps.*.tanggal_lhp' => ['nullable', 'date'],
            'lhps.*.judul_lhp' => ['nullable', 'string', 'max:255'],
            'lhps.*.opd_ids' => ['nullable', 'array'],
            'lhps.*.opd_ids.*' => ['exists:opds,id'],
            'lhps.*.opd_id' => ['nullable', 'exists:opds,id'],
            'lhps.*.ringkasan' => ['nullable', 'string'],
            'lhps.*.unsur_administrasi' => ['nullable', 'string'],
            'lhps.*.nilai_kerugian' => ['nullable', 'string'],
            'lhps.*.rekomendasi' => ['nullable', 'string'],
            'lhps.*.dokumen_lhp' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:20480'],
            'lhps.*.temuans' => ['nullable', 'array'],
            'lhps.*.temuans.*.judul_temuan' => ['nullable', 'string', 'max:255'],
            'lhps.*.temuans.*.unsur_administrasi' => ['nullable', 'string'],
            'lhps.*.temuans.*.nilai_kerugian' => ['nullable', 'string'],
            'lhps.*.temuans.*.rekomendasi' => ['nullable', 'string'],
            'lhps.*.temuans_administrasi' => ['nullable', 'array'],
            'lhps.*.temuans_administrasi.*.uraian' => ['nullable', 'string'],
            'lhps.*.temuans_administrasi.*.penyebab' => ['nullable', 'string'],
            'lhps.*.temuans_administrasi.*.rekomendasi' => ['nullable', 'string'],
            'lhps.*.temuans_administrasi.*.rekomendasi_items' => ['nullable', 'array'],
            'lhps.*.temuans_administrasi.*.rekomendasi_items.*' => ['nullable', 'string'],
            'lhps.*.temuan_kerugian' => ['nullable', 'array'],
            'lhps.*.temuan_kerugian.kategori_kerugian' => ['nullable', 'in:Kerugian Daerah,Kerugian Negara'],
            'lhps.*.temuan_kerugian.jenis_kerugian' => ['nullable', 'in:Internal,Eksternal'],
            'lhps.*.temuan_kerugian.nilai_kerugian' => ['nullable', 'string'],
            'lhps.*.temuan_kerugian.uraian' => ['nullable', 'string'],
            'lhps.*.temuan_kerugian.rekomendasi' => ['nullable', 'string'],
            'lhps.*.temuans_kerugian' => ['nullable', 'array'],
            'lhps.*.temuans_kerugian.*.kategori_kerugian' => ['nullable', 'in:Kerugian Daerah,Kerugian Negara'],
            'lhps.*.temuans_kerugian.*.jenis_kerugian' => ['nullable', 'in:Internal,Eksternal'],
            'lhps.*.temuans_kerugian.*.nilai_kerugian' => ['nullable', 'string'],
            'lhps.*.temuans_kerugian.*.uraian' => ['nullable', 'string'],
            'lhps.*.temuans_kerugian.*.penyebab' => ['nullable', 'string'],
            'lhps.*.temuans_kerugian.*.rekomendasi' => ['nullable', 'string'],

            // Single LHP fallback (compatibility)
            'nomor_lhp' => ['nullable', 'string', 'max:100'],
            'tanggal_lhp' => ['nullable', 'date'],
            'judul_lhp' => ['nullable', 'string', 'max:255'],
            'ringkasan_lhp' => ['nullable', 'string'],
            'dokumen_lhp' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:20480'],

            // Multi-Tindak Lanjut (Array)
            'tindak_lanjuts' => ['nullable', 'array'],
            'tindak_lanjuts.*.lhp_index' => ['nullable'],
            'tindak_lanjuts.*.tanggal_tindak_lanjut' => ['nullable', 'date'],
            'tindak_lanjuts.*.uraian_tindak_lanjut' => ['nullable', 'string'],
            'tindak_lanjuts.*.nomor_bukti_setor' => ['nullable', 'string', 'max:100'],
            'tindak_lanjuts.*.tanggal_setor' => ['nullable', 'date'],
            'tindak_lanjuts.*.jumlah_setor' => ['nullable', 'string'],
            'tindak_lanjuts.*.dokumen_bukti' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
        ];

        if ($this->user()->canManageSpt()) {
            $rules['nomor_spt'] = ['required', 'string', 'max:100'];
            $rules['tanggal_spt'] = ['nullable', 'date'];
            $rules['tanggal_mulai'] = ['nullable', 'date'];
            $rules['tanggal_selesai'] = ['nullable', 'date', 'after_or_equal:tanggal_mulai'];
            $rules['dokumen_spt'] = ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'];
        } else {
            $rules['nomor_spt'] = ['nullable', 'string', 'max:100'];
            $rules['tanggal_spt'] = ['nullable', 'date'];
            $rules['tanggal_mulai'] = ['nullable', 'date'];
            $rules['tanggal_selesai'] = ['nullable', 'date', 'after_or_equal:tanggal_mulai'];
            $rules['dokumen_spt'] = ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'];
        }

        return $rules;
    }
}
