<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <title>Laporan Kehadiran Harian — {{ $date }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm;
        }
        body {
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #0f172a;
            font-size: 11pt;
            line-height: 1.4;
            background: #fff;
            margin: 0;
            padding: 10px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }
        .header h1 {
            font-size: 16pt;
            margin: 0;
            font-weight: bold;
            text-transform: uppercase;
        }
        .header p {
            margin: 4px 0 0;
            font-size: 10pt;
            color: #475569;
        }
        .meta-bar {
            display: flex;
            justify-content: space-between;
            font-size: 9.5pt;
            margin-bottom: 12px;
            font-weight: 500;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5pt;
            margin-bottom: 25px;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            text-align: left;
        }
        th {
            background-color: #f1f5f9;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 8.5pt;
        }
        .status-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: 600;
            font-size: 8pt;
            border: 1px solid #94a3b8;
        }
        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            page-break-inside: avoid;
        }
        .sign-box {
            width: 45%;
            font-size: 9pt;
        }
        .sign-line {
            border-top: 1px solid #0f172a;
            margin-top: 50px;
            padding-top: 5px;
        }
        .no-print {
            position: fixed;
            top: 15px;
            right: 15px;
            background: #0f172a;
            color: #fff;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 10pt;
            font-weight: 600;
            cursor: pointer;
            border: none;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>

    <button onclick="window.print()" class="no-print">Cetak / Simpan PDF</button>

    <div class="header">
        <h1>Sistem Kehadiran Organisasi (eHadir)</h1>
        <p>Laporan Rekod Kehadiran Harian Kakitangan</p>
    </div>

    <div class="meta-bar">
        <div>
            <strong>Tarikh:</strong> {{ \Carbon\Carbon::parse($date)->translatedFormat('l, d F Y') }}
        </div>
        <div>
            <strong>Jumlah Anggota:</strong> {{ $anggotaList->count() }} orang
        </div>
        <div>
            <strong>Masa Dicetak:</strong> {{ now()->format('d/m/Y H:i:s') }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 30px; text-align: center;">Bil</th>
                <th style="width: 80px;">No. Pekerja</th>
                <th>Nama Anggota</th>
                <th>Jawatan</th>
                <th>Penempatan</th>
                <th style="width: 100px;">Status</th>
                <th>Waktu / Lokasi / Catatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($anggotaList as $idx => $a)
                @php $rec = $records->get($a->id); @endphp
                <tr>
                    <td style="text-align: center;">{{ $idx + 1 }}</td>
                    <td style="font-family: monospace;">{{ $a->no_pekerja ?? '-' }}</td>
                    <td><strong>{{ $a->nama_penuh_gelaran }}</strong></td>
                    <td>{{ $a->jawatan ?? '-' }}</td>
                    <td>{{ $rec?->penempatan?->lokasi_penempatan ?? 'Tiada Penempatan' }}</td>
                    <td>
                        @if ($rec)
                            <span class="status-badge" style="border-color: {{ $rec->status->warna }}; color: {{ $rec->status->warna }};">
                                {{ $rec->status->nama }}
                            </span>
                        @else
                            <span style="color: #94a3b8; font-style: italic;">Belum Direkod</span>
                        @endif
                    </td>
                    <td style="font-size: 8.5pt;">
                        @if ($rec)
                            {{ $rec->sepanjang_hari ? 'Sepanjang Hari' : "{$rec->masa_mula} - {$rec->masa_tamat}" }}
                            @if ($rec->lokasi) &bull; {{ $rec->lokasi }} @endif
                            @if ($rec->catatan) &bull; {{ $rec->catatan }} @endif
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; color: #94a3b8; padding: 20px;">
                        Tiada rekod anggota dijumpai.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="signature-section">
        <div class="sign-box">
            <p>Disediakan Oleh:</p>
            <div class="sign-line">
                <p>Nama: {{ auth()->user()?->nama }}</p>
                <p>Jawatan: Pentadbir Sistem eHadir</p>
                <p>Tarikh: {{ now()->format('d/m/Y') }}</p>
            </div>
        </div>

        <div class="sign-box">
            <p>Disemak & Disahkan Oleh:</p>
            <div class="sign-line">
                <p>Nama: _________________________________</p>
                <p>Jawatan: Ketua Bahagian / Unit</p>
                <p>Tarikh: ____________________</p>
            </div>
        </div>
    </div>

</body>
</html>
