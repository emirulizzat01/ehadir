<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <title>Buku Kehadiran Bulanan — {{ $startOfMonth->translatedFormat('F Y') }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
        }
        body {
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #0f172a;
            font-size: 8pt;
            line-height: 1.3;
            background: #fff;
            margin: 0;
            padding: 5px;
        }
        .header {
            text-align: center;
            border-bottom: 1.5px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .header h1 {
            font-size: 13pt;
            margin: 0;
            font-weight: bold;
            text-transform: uppercase;
        }
        .header p {
            margin: 2px 0 0;
            font-size: 8.5pt;
            color: #475569;
        }
        .meta-bar {
            display: flex;
            justify-content: space-between;
            font-size: 8pt;
            margin-bottom: 8px;
            font-weight: 500;
        }
        .legend-bar {
            display: flex;
            gap: 12px;
            font-size: 7.5pt;
            margin-bottom: 8px;
            background: #f8fafc;
            padding: 4px 8px;
            border: 1px solid #e2e8f0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5pt;
            margin-bottom: 15px;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 3px 2px;
            text-align: center;
        }
        th {
            background-color: #f1f5f9;
            font-weight: 600;
        }
        th.name-col, td.name-col {
            text-align: left;
            padding-left: 5px;
            white-space: nowrap;
            max-width: 140px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .weekend {
            background-color: #f1f5f9;
            color: #94a3b8;
        }
        .status-cell {
            font-weight: bold;
            font-size: 7pt;
        }
        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 25px;
            page-break-inside: avoid;
        }
        .sign-box {
            width: 35%;
            font-size: 8pt;
        }
        .sign-line {
            border-top: 1px solid #0f172a;
            margin-top: 35px;
            padding-top: 3px;
        }
        .no-print {
            position: fixed;
            top: 10px;
            right: 10px;
            background: #0f172a;
            color: #fff;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 9pt;
            font-weight: 600;
            cursor: pointer;
            border: none;
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
        <p>Buku Rekod Kehadiran Bulanan Kakitangan — Bulan {{ $startOfMonth->translatedFormat('F Y') }}</p>
    </div>

    <div class="meta-bar">
        <div><strong>Bulan & Tahun:</strong> {{ $startOfMonth->translatedFormat('F Y') }}</div>
        <div><strong>Jumlah Warga Kerja:</strong> {{ $anggotaList->count() }} orang</div>
        <div><strong>Tarikh Cetakan:</strong> {{ now()->format('d/m/Y H:i:s') }}</div>
    </div>

    <div class="legend-bar">
        <strong>Petunjuk:</strong>
        @foreach ($statuses as $st)
            <span><strong>{{ substr($st->kod, 0, 1) }}</strong> = {{ $st->nama }}</span>
        @endforeach
        <span><strong>•</strong> = Hujung Minggu</span>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 20px;">Bil</th>
                <th class="name-col">Nama Penuh Anggota</th>
                <th style="width: 50px;">No. Pekerja</th>
                @for ($d = 1; $d <= $daysInMonth; $d++)
                    @php
                        $dayCarbon = \Carbon\Carbon::create($year, $month, $d);
                        $isWeekend = $dayCarbon->isWeekend();
                    @endphp
                    <th class="{{ $isWeekend ? 'weekend' : '' }}" style="width: 16px;">{{ $d }}</th>
                @endfor
            </tr>
        </thead>
        <tbody>
            @forelse ($anggotaList as $idx => $a)
                @php
                    $staffRecords = $records->get($a->id, collect())->keyBy(fn ($r) => $r->tarikh->format('j'));
                @endphp
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td class="name-col"><strong>{{ $a->nama_penuh }}</strong></td>
                    <td style="font-family: monospace;">{{ $a->no_pekerja ?? '-' }}</td>
                    @for ($d = 1; $d <= $daysInMonth; $d++)
                        @php
                            $rec = $staffRecords->get($d);
                            $dayCarbon = \Carbon\Carbon::create($year, $month, $d);
                            $isWeekend = $dayCarbon->isWeekend();
                        @endphp
                        <td class="{{ $isWeekend ? 'weekend' : '' }}">
                            @if ($rec)
                                <span class="status-cell" style="color: {{ $rec->status->warna }};">
                                    {{ substr($rec->status->kod, 0, 1) }}
                                </span>
                            @elseif ($isWeekend)
                                <span style="color: #cbd5e1;">•</span>
                            @else
                                <span style="color: #e2e8f0;">-</span>
                            @endif
                        </td>
                    @endfor
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $daysInMonth + 3 }}" style="padding: 15px; color: #94a3b8;">
                        Tiada data anggota direkodkan.
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
            <p>Disahkan Oleh:</p>
            <div class="sign-line">
                <p>Nama: _________________________________</p>
                <p>Jawatan: Ketua Jabatan / Bahagian</p>
                <p>Tarikh: ____________________</p>
            </div>
        </div>
    </div>

</body>
</html>
