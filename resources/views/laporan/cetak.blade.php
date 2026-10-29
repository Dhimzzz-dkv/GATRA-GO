<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pemantauan Tindak Lanjut - GATRA-GO Gorontalo</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 1.5cm 1cm 1.5cm 1cm;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 20px;
            font-size: 11pt;
            line-height: 1.3;
        }

        .kop-surat {
            margin-bottom: 20px;
            width: 100%;
        }

        table.kop-table {
            width: 100%;
            border: none !important;
            margin-bottom: 0 !important;
            border-collapse: collapse;
        }

        table.kop-table td {
            border: none !important;
            padding: 0 !important;
            vertical-align: middle;
        }

        .kop-logo {
            width: 95px;
            text-align: center;
            padding-right: 15px !important;
        }

        .logo-img {
            width: 80px;
            height: auto;
            max-height: 95px;
            object-fit: contain;
            display: inline-block;
        }

        .kop-text {
            text-align: center;
        }

        .kop-instansi {
            font-size: 15pt;
            font-weight: bold;
            letter-spacing: 0.5px;
            color: #000;
            line-height: 1.2;
            text-transform: ;
        }

        .kop-dinas {
            font-size: 19pt;
            font-weight: bold;
            letter-spacing: 1px;
            color: #000;
            line-height: 1.25;
            margin-top: 2px;
            margin-bottom: 3px;
            text-transform: ;
        }

        .kop-alamat {
            font-size: 9.5pt;
            font-weight: normal;
            color: #000;
            line-height: 1.3;
        }

        .kop-kontak {
            font-size: 8.5pt;
            font-weight: normal;
            color: #000;
            line-height: 1.3;
        }

        .kop-kontak u {
            text-decoration: underline;
        }

        .kop-line {
            width: 100%;
            border-top: 3.5px solid #000;
            border-bottom: 1px solid #000;
            height: 2px;
            margin-top: 8px;
            margin-bottom: 20px;
        }

        .report-title {
            text-align: center;
            margin-bottom: 20px;
        }

        .report-title h3 {
            margin: 0;
            font-size: 12pt;
            text-decoration: underline;
            text-transform: ;
            font-weight: bold;
        }

        .report-title p {
            margin: 4px 0 0 0;
            font-size: 10pt;
        }

        table:not(.kop-table) {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 9.5pt;
        }

        table:not(.kop-table), table:not(.kop-table) th, table:not(.kop-table) td {
            border: 1px solid #000;
        }

        th {
            background-color: #f2f2f2;
            padding: 6px 4px;
            text-align: center;
            font-weight: bold;
            text-transform: ;
            font-size: 9pt;
        }

        td {
            padding: 5px 6px;
            vertical-align: middle;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }

        .summary-box {
            margin-top: 15px;
            margin-bottom: 25px;
            font-size: 10pt;
        }

        .signature-container {
            margin-top: 35px;
            width: 100%;
            display: flex;
            justify-content: space-between;
            page-break-inside: avoid;
        }

        .signature-box {
            width: 320px;
            text-align: center;
        }

        .signature-space {
            height: 70px;
        }

        .no-print {
            position: fixed;
            top: 15px;
            right: 15px;
            background: #1e293b;
            color: #fff;
            padding: 10px 18px;
            border-radius: 8px;
            font-family: sans-serif;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            border: none;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2);
            z-index: 999;
        }

        .no-print:hover {
            background: #0f172a;
        }

        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
        }
    </style>
</head>
<body>

    <button onclick="window.print()" class="no-print">Cetak / Simpan PDF</button>

    {{-- Kop Surat Resmi Pemerintah Provinsi Gorontalo --}}
    <div class="kop-surat">
        <table class="kop-table">
            <tr>
                <td class="kop-logo">
                    <img src="{{ asset('images/logo-gorontalo.svg') }}" alt="Logo Gorontalo" class="logo-img">
                </td>
                <td class="kop-text">
                    <div class="kop-instansi">PEMERINTAH PROVINSI GORONTALO</div>
                    <div class="kop-dinas">INSPEKTORAT DAERAH</div>
                    <div class="kop-alamat">Jln. Jenderal Sudirman No. 79 Telp. (0435) 8524886 Fax (0435) 8524885</div>
                    <div class="kop-kontak">Website : <u>http://inspektorat.gorontaloprov.go.id</u> &nbsp; email : <u>itda@gorontaloprov.go.id</u></div>
                </td>
            </tr>
        </table>
        <div class="kop-line"></div>
    </div>

    {{-- Judul Laporan --}}
    <div class="report-title">
        <h3>{{ $jenis === 'administrasi' ? 'Laporan Rekapitulasi Temuan Pengawasan Administratif' : 'Laporan Rekapitulasi Temuan & Penyelesaian Tindak Lanjut' }}</h3>
        <p>
            GATRA-GO — Gerbang Akuntabilitas dan Tindak Lanjut Rekomendasi Audit —
            @if($jenis === 'administrasi' && $selectedPemeriksaan)
                {{ $selectedPemeriksaan->is_lhp_only_submission ? 'LHP: ' . ($selectedPemeriksaan->lhps->pluck('nomor_lhp')->filter()->join(', ') ?: 'Pengajuan') : 'SPT: ' . $selectedPemeriksaan->nomor_spt }} (TA {{ $selectedPemeriksaan->tahun }})
            @elseif($tahun)
                Tahun Anggaran {{ $tahun }}
            @else
                Seluruh Tahun Anggaran
            @endif
            @if($sumber) • Sumber: {{ $sumber }} @endif
            @if($selectedOpd) • OPD: {{ $selectedOpd->nama }} @endif
        </p>
    </div>

    {{-- TABEL DATA MATRIKS REKAPITULASI --}}
    @if($tab === 'rekap')
        @if($jenis === 'administrasi')
            {{-- Matriks Cetak Administrasi (4 Kolom Status Penyelesaian) --}}
            <table>
                <thead>
                    <tr>
                        <th scope="col" rowspan="2" style="width: 30px;">No</th>
                        <th scope="col" rowspan="2">Nama OPD</th>
                        <th scope="col" rowspan="2" style="width: 70px;">Sumber Pemeriksa</th>
                        <th scope="col" rowspan="2">Temuan</th>
                        <th scope="col" rowspan="2">Penyebab</th>
                        <th scope="col" rowspan="2">Rekomendasi</th>
                        <th scope="col" rowspan="2" style="width: 60px;">Jumlah Rekomendasi</th>
                        <th scope="colgroup" colspan="4">Status Penyelesaian</th>
                    </tr>
                    <tr>
                        <th scope="col" style="width: 45px;">Belum Ditindaklanjuti</th>
                        <th scope="col" style="width: 45px;">Belum Sesuai</th>
                        <th scope="col" style="width: 45px;">Sesuai</th>
                        <th scope="col" style="width: 55px;">Tidak Dapat Ditindaklanjuti</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalButirCetak = 0;
                        $totalTl = 0;
                        $totalSesuai = 0;
                        $totalOk = 0;
                        $totalTptd = 0;
                    @endphp
                    @forelse($allTemuans as $index => $t)
                        @php
                            $rekCount = $t->rekomendasis->count();
                            $totalButirCetak += $rekCount;

                            $reks = $t->rekomendasis;
                            if ($reks->isNotEmpty()) {
                                $c_tl = $reks->where('status_penyelesaian', 'Belum Ditindaklanjuti')->count();
                                $c_sesuai = $reks->where('status_penyelesaian', 'Belum Sesuai')->count();
                                $c_ok = $reks->where('status_penyelesaian', 'Sesuai')->count();
                                $c_tptd = $reks->filter(fn($r) => in_array($r->status_penyelesaian, ['Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah', 'Tidak Dapat Ditindaklanjuti']))->count();
                            } else {
                                $st = $t->status_penyelesaian;
                                $c_tl = ($st === 'Belum Ditindaklanjuti') ? 1 : 0;
                                $c_sesuai = ($st === 'Belum Sesuai') ? 1 : 0;
                                $c_ok = ($st === 'Sesuai') ? 1 : 0;
                                $c_tptd = in_array($st, ['Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah', 'Tidak Dapat Ditindaklanjuti']) ? 1 : 0;
                            }
                            $totalTl += $c_tl;
                            $totalSesuai += $c_sesuai;
                            $totalOk += $c_ok;
                            $totalTptd += $c_tptd;
                        @endphp
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td><strong>{{ $t->opd->nama }}</strong></td>
                            <td class="text-center">{{ $t->sumber_pemeriksaan }}</td>
                            <td>
                                <strong>{{ $t->judul_temuan }}</strong><br>
                                <span style="font-size: 8.5pt; color: #333;">{{ $t->uraian_temuan }}</span>
                            </td>
                            <td>{{ $t->penyebab ?: '-' }}</td>
                            <td>
                                @if($t->rekomendasis->isNotEmpty())
                                    <ol style="margin: 0; padding-left: 16px;">
                                        @foreach($t->rekomendasis as $r)
                                            <li style="margin-bottom: 2px;">{{ $r->uraian_rekomendasi }}</li>
                                        @endforeach
                                    </ol>
                                @else
                                    {{ $t->rekomendasi ?: '-' }}
                                @endif
                            </td>
                            <td class="text-center font-bold">{{ $rekCount ?: '-' }}</td>
                            <td class="text-center font-bold">{{ $c_tl ?: '-' }}</td>
                            <td class="text-center font-bold">{{ $c_sesuai ?: '-' }}</td>
                            <td class="text-center font-bold">{{ $c_ok ?: '-' }}</td>
                            <td class="text-center font-bold">{{ $c_tptd ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center" style="padding: 20px;">Tidak ada data temuan administrasi.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr style="background-color: #f2f2f2; font-weight: bold;">
                        <td colspan="6" class="text-center">TOTAL KESELURUHAN ({{ $totalTemuan }} TEMUAN ADMINISTRASI)</td>
                        <td class="text-center">{{ $totalButirCetak ?: '-' }}</td>
                        <td class="text-center">{{ $totalTl ?: '-' }}</td>
                        <td class="text-center">{{ $totalSesuai ?: '-' }}</td>
                        <td class="text-center">{{ $totalOk ?: '-' }}</td>
                        <td class="text-center">{{ $totalTptd ?: '-' }}</td>
                    </tr>
                </tfoot>
            </table>
        @else
            {{-- Matriks Cetak Kerugian (Status Penyelesaian + Negara, Daerah, Gabungan) --}}
            <table>
                <thead>
                    <tr>
                        <th scope="col" rowspan="2" style="width: 25px; text-align: center;">NO</th>
                        <th scope="col" rowspan="2" style="text-align: left;">NAMA OPD</th>
                        <th scope="col" rowspan="2" style="width: 35px; text-align: center;">TOTAL</th>
                        <th scope="colgroup" colspan="4" style="text-align: center;">STATUS PENYELESAIAN</th>
                        <th scope="col" rowspan="2" style="width: 155px; text-align: left;">
                            KERUGIAN NEGARA<br>
                            <span style="font-weight: normal; font-size: 7.5pt;">Nilai Kerugian / Telah Disetor / Sisa Kerugian</span>
                        </th>
                        <th scope="col" rowspan="2" style="width: 155px; text-align: left;">
                            KERUGIAN DAERAH<br>
                            <span style="font-weight: normal; font-size: 7.5pt;">Nilai Kerugian / Telah Disetor / Sisa Kerugian</span>
                        </th>
                        <th scope="col" rowspan="2" style="width: 155px; text-align: left; background-color: #f8fafc;">
                            GABUNGAN<br>
                            <span style="font-weight: normal; font-size: 7.5pt;">Nilai Kerugian / Telah Disetor / Sisa Kerugian</span>
                        </th>
                        <th scope="col" rowspan="2" style="width: 55px; text-align: center;">% SELESAI</th>
                    </tr>
                    <tr>
                        <th scope="col" style="width: 45px; text-align: center; color: #b91c1c; font-size: 7.5pt;">Belum Ditindaklanjuti</th>
                        <th scope="col" style="width: 45px; text-align: center; color: #b45309; font-size: 7.5pt;">Belum Sesuai</th>
                        <th scope="col" style="width: 40px; text-align: center; color: #047857; font-size: 7.5pt;">Sesuai</th>
                        <th scope="col" style="width: 55px; text-align: center; color: #1e3a8a; font-size: 7.5pt;">Tidak Dapat Ditindaklanjuti</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($opdMatrix as $index => $row)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td class="font-bold">{{ $row['opd']->nama }}</td>
                            <td class="text-center font-bold">{{ $row['total_temuan'] }}</td>
                            {{-- STATUS PENYELESAIAN --}}
                            <td class="text-center font-bold" style="color: #b91c1c;">{{ $row['belum_tl'] ?: '-' }}</td>
                            <td class="text-center font-bold" style="color: #b45309;">{{ $row['belum_sesuai'] ?: '-' }}</td>
                            <td class="text-center font-bold" style="color: #047857;">{{ $row['sesuai'] ?: '-' }}</td>
                            <td class="text-center" style="color: #4b5563;">{{ $row['tidak_dapat_tl'] ?: '-' }}</td>

                            {{-- KERUGIAN NEGARA --}}
                            <td>
                                @if($row['nilai_kerugian_negara'] > 0 || $row['total_disetor_negara'] > 0)
                                    <strong>Rp {{ number_format($row['nilai_kerugian_negara'], 0, ',', '.') }}</strong> /<br>
                                    <span style="color: #059669; font-weight: 600;">Rp {{ number_format($row['total_disetor_negara'], 0, ',', '.') }}</span> /<br>
                                    <span style="{{ $row['sisa_kerugian_negara'] > 0 ? 'color: #dc2626; font-weight: bold;' : 'color: #6b7280;' }}">Rp {{ number_format($row['sisa_kerugian_negara'], 0, ',', '.') }}</span>
                                @else
                                    <span style="color: #9ca3af;">Rp 0 / Rp 0 /<br>Rp 0</span>
                                @endif
                            </td>
                            {{-- KERUGIAN DAERAH --}}
                            <td>
                                @if($row['nilai_kerugian_daerah'] > 0 || $row['total_disetor_daerah'] > 0)
                                    <strong>Rp {{ number_format($row['nilai_kerugian_daerah'], 0, ',', '.') }}</strong> /<br>
                                    <span style="color: #059669; font-weight: 600;">Rp {{ number_format($row['total_disetor_daerah'], 0, ',', '.') }}</span> /<br>
                                    <span style="{{ $row['sisa_kerugian_daerah'] > 0 ? 'color: #dc2626; font-weight: bold;' : 'color: #6b7280;' }}">Rp {{ number_format($row['sisa_kerugian_daerah'], 0, ',', '.') }}</span>
                                @else
                                    <span style="color: #9ca3af;">Rp 0 / Rp 0 /<br>Rp 0</span>
                                @endif
                            </td>
                            {{-- GABUNGAN --}}
                            <td style="background-color: #f8fafc;">
                                @if($row['nilai_kerugian'] > 0 || $row['total_disetor'] > 0)
                                    <strong>Rp {{ number_format($row['nilai_kerugian'], 0, ',', '.') }}</strong> /<br>
                                    <span style="color: #059669; font-weight: 600;">Rp {{ number_format($row['total_disetor'], 0, ',', '.') }}</span> /<br>
                                    <span style="{{ $row['sisa_kerugian'] > 0 ? 'color: #dc2626; font-weight: bold;' : 'color: #6b7280;' }}">Rp {{ number_format($row['sisa_kerugian'], 0, ',', '.') }}</span>
                                @else
                                    <span style="color: #9ca3af;">Rp 0 / Rp 0 /<br>Rp 0</span>
                                @endif
                            </td>
                            {{-- % SELESAI --}}
                            <td class="text-center font-bold" style="{{ $row['persen_selesai'] >= 100 ? 'color: #059669;' : ($row['persen_selesai'] > 0 ? 'color: #1e40af;' : 'color: #6b7280;') }}">
                                {{ $row['persen_selesai'] }}%
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center" style="padding: 20px;">Tidak ada data temuan.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr style="background-color: #f2f2f2; font-weight: bold;">
                        <td colspan="2" class="text-center">TOTAL REKAPITULASI PROVINSI</td>
                        <td class="text-center">{{ $totalTemuan }}</td>
                        {{-- TOTAL STATUS PENYELESAIAN --}}
                        <td class="text-center font-bold" style="color: #b91c1c;">{{ $totalBelumTl ?: '-' }}</td>
                        <td class="text-center font-bold" style="color: #b45309;">{{ $totalBelumSesuai ?: '-' }}</td>
                        <td class="text-center font-bold" style="color: #047857;">{{ $totalSesuai ?: '-' }}</td>
                        <td class="text-center" style="color: #4b5563;">{{ $totalTidakDapatTl ?: '-' }}</td>

                        {{-- TOTAL KERUGIAN NEGARA --}}
                        <td>
                            <strong>Rp {{ number_format($totalKerugianNegara, 0, ',', '.') }}</strong> /<br>
                            <span style="color: #047857;">Rp {{ number_format($totalSetoranNegara, 0, ',', '.') }}</span> /<br>
                            <span style="color: #b91c1c;">Rp {{ number_format($totalSisaNegara, 0, ',', '.') }}</span>
                        </td>
                        {{-- TOTAL KERUGIAN DAERAH --}}
                        <td>
                            <strong>Rp {{ number_format($totalKerugianDaerah, 0, ',', '.') }}</strong> /<br>
                            <span style="color: #047857;">Rp {{ number_format($totalSetoranDaerah, 0, ',', '.') }}</span> /<br>
                            <span style="color: #b91c1c;">Rp {{ number_format($totalSisaDaerah, 0, ',', '.') }}</span>
                        </td>
                        {{-- TOTAL GABUNGAN --}}
                        <td style="background-color: #e2e8f0;">
                            <strong>Rp {{ number_format($totalKerugian, 0, ',', '.') }}</strong> /<br>
                            <span style="color: #047857;">Rp {{ number_format($totalSetoran, 0, ',', '.') }}</span> /<br>
                            <span style="color: #b91c1c;">Rp {{ number_format($totalSisaKerugian, 0, ',', '.') }}</span>
                        </td>
                        <td class="text-center" style="font-size: 10pt; color: #1e3a8a;">
                            {{ $totalPersenSelesai }}%
                        </td>
                    </tr>
                </tfoot>
            </table>
        @endif
    @else
        {{-- TABEL DATA RINCIAN TEMUAN --}}
        @if($jenis === 'administrasi')
            {{-- Laporan Rincian Cetak Administrasi (per Pemeriksaan) --}}
            <table>
                <thead>
                    <tr>
                        <th scope="col" style="width: 30px;">No</th>
                        <th scope="col">Pemeriksaan / SPT</th>
                        <th scope="col">OPD Target</th>
                        <th scope="col" style="width: 60px;">Sumber</th>
                        <th scope="col">Temuan Administrasi</th>
                        <th scope="col">Rincian Butir Rekomendasi</th>
                        <th scope="col" style="width: 50px;">Jumlah Butir</th>
                        <th scope="col" style="width: 90px;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @php $totalButirCetak = 0; @endphp
                    @forelse($allTemuans as $index => $t)
                        @php
                            $rekCount = $t->rekomendasis->count();
                            $totalButirCetak += $rekCount;
                        @endphp
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td>
                                <strong>{{ $t->pemeriksaan?->is_lhp_only_submission ? 'LHP: ' . ($t->lhp?->nomor_lhp ?? '-') : 'SPT: ' . ($t->pemeriksaan?->nomor_spt ?? '-') }}</strong><br>
                                <span style="font-size: 8pt; color: #444;">TA {{ $t->pemeriksaan->tahun ?? $t->tahun }}</span>
                            </td>
                            <td><strong>{{ $t->opd->nama }}</strong></td>
                            <td class="text-center">{{ $t->sumber_pemeriksaan }}</td>
                            <td>
                                <strong>{{ $t->judul_temuan }}</strong><br>
                                <span style="font-size: 8.5pt; color: #333;">{{ $t->uraian_temuan }}</span>
                                @if($t->penyebab)
                                    <br><span style="font-size: 8pt; color: #555; font-style: italic;">Penyebab: {{ $t->penyebab }}</span>
                                @endif
                            </td>
                            <td>
                                @if($t->rekomendasis->isNotEmpty())
                                    <ol style="margin: 0; padding-left: 16px;">
                                        @foreach($t->rekomendasis as $r)
                                            <li style="margin-bottom: 2px;">
                                                {{ $r->uraian_rekomendasi }} 
                                                <strong>[{{ $r->status_penyelesaian }}]</strong>
                                            </li>
                                        @endforeach
                                    </ol>
                                @else
                                    {{ $t->rekomendasi ?: '-' }}
                                @endif
                            </td>
                            <td class="text-center font-bold">{{ $rekCount ?: '-' }}</td>
                            <td class="text-center font-bold">{{ $t->status_penyelesaian }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center" style="padding: 20px;">Tidak ada data temuan administrasi.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr style="background-color: #f2f2f2; font-weight: bold;">
                        <td colspan="6" class="text-center">TOTAL KESELURUHAN ({{ $totalTemuan }} TEMUAN ADMINISTRASI)</td>
                        <td class="text-center">{{ $totalButirCetak ?: '-' }}</td>
                        <td class="text-center">-</td>
                    </tr>
                </tfoot>
            </table>
        @else
            {{-- Rincian Temuan Kerugian Daerah (Lama) --}}
            <table>
                <thead>
                    <tr>
                        <th scope="col" style="width: 35px;">No</th>
                        <th scope="col">Sumber & Tahun</th>
                        <th scope="col">OPD Target</th>
                        <th scope="col">Judul & Uraian Temuan</th>
                        <th scope="col">Rekomendasi</th>
                        <th scope="col">Nilai Kerugian (Rp)</th>
                        <th scope="col">Disetor (Rp)</th>
                        <th scope="col">Sisa (Rp)</th>
                        <th scope="col">Status Penyelesaian</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($allTemuans as $index => $t)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td>
                                <strong>{{ $t->sumber_pemeriksaan }}</strong><br>
                                <span style="font-size: 8pt; color: #444;">TA {{ $t->tahun }}</span>
                            </td>
                            <td><strong>{{ $t->opd->nama }}</strong></td>
                            <td>
                                <strong>{{ $t->judul_temuan }}</strong><br>
                                <span style="font-size: 8.5pt; color: #333;">{{ $t->uraian_temuan }}</span>
                            </td>
                            <td>{{ $t->rekomendasi ?: '-' }}</td>
                            <td class="text-right">{{ $t->nilai_kerugian > 0 ? number_format($t->nilai_kerugian, 0, ',', '.') : '-' }}</td>
                            <td class="text-right">{{ $t->total_disetor > 0 ? number_format($t->total_disetor, 0, ',', '.') : '-' }}</td>
                            <td class="text-right">{{ $t->sisa_kerugian > 0 ? number_format($t->sisa_kerugian, 0, ',', '.') : '-' }}</td>
                            <td class="text-center font-bold">{{ $t->status_penyelesaian }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center" style="padding: 20px;">Tidak ada data temuan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        @endif
    @endif

    {{-- Kolom Dua Tanda Tangan Resmi Pejabat --}}
    @php $ttd = \App\Services\SignatoryResolver::forLaporan(); @endphp
    <div class="signature-container">
        {{-- Pihak Kiri: Pembuat Laporan / Tim Evaluasi Pengawasan --}}
        <div class="signature-box">
            <p>&nbsp;</p>
            <p class="font-bold">{{ $ttd['pembuat']['jabatan_label'] }}</p>
            <div class="signature-space"></div>
            @if($ttd['pembuat']['nama'])
                <p class="font-bold" style="text-decoration: underline;">{{ $ttd['pembuat']['nama'] }}</p>
                @if($ttd['pembuat']['pangkat'])
                    <p>{{ $ttd['pembuat']['pangkat'] }}</p>
                @endif
                @if($ttd['pembuat']['nip'])
                    <p>NIP. {{ $ttd['pembuat']['nip'] }}</p>
                @endif
            @else
                <p class="font-bold" style="text-decoration: underline;">&nbsp;</p>
                <p style="color: #b91c1c; font-size: 8pt;">(Penandatangan belum diatur pada master Pegawai)</p>
            @endif
        </div>

        {{-- Pihak Kanan: Inspektur Daerah Provinsi Gorontalo --}}
        <div class="signature-box">
            <p>Gorontalo, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</p>
            <p class="font-bold">Mengetahui,<br>{{ $ttd['pengesah']['jabatan_label'] }}</p>
            <div class="signature-space"></div>
            @if($ttd['pengesah']['nama'])
                <p class="font-bold" style="text-decoration: underline;">{{ $ttd['pengesah']['nama'] }}</p>
                @if($ttd['pengesah']['pangkat'])
                    <p>{{ $ttd['pengesah']['pangkat'] }}</p>
                @endif
                @if($ttd['pengesah']['nip'])
                    <p>NIP. {{ $ttd['pengesah']['nip'] }}</p>
                @endif
            @else
                <p class="font-bold" style="text-decoration: underline;">&nbsp;</p>
                <p style="color: #b91c1c; font-size: 8pt;">(Penandatangan belum diatur pada master Pegawai)</p>
            @endif
        </div>
    </div>

</body>
</html>
