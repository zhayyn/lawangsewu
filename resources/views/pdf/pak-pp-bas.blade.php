<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Berita Acara Sidang — Pengadilan Agama Semarang</title>
    <style>
        @page {
            margin: 25mm 20mm 25mm 30mm;
        }
        * { box-sizing: border-box; }
        body {
            font-family: "Times New Roman", Times, serif;
            color: #0f172a;
            font-size: 12pt;
            line-height: 1.8;
            margin: 0;
            background: #ffffff;
        }

        /* ── Kop Surat ── */
        .kop {
            text-align: center;
            border-bottom: 3px double #0f172a;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .kop-instansi {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .kop-sub {
            font-size: 10pt;
            color: #334155;
            margin-top: 2px;
        }

        /* ── Judul BAS ── */
        .judul-bas {
            text-align: center;
            margin: 18px 0 12px;
        }
        .judul-bas h1 {
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            margin: 0;
            text-decoration: underline;
        }

        /* ── Meta Dokumen ── */
        .meta-dok {
            margin: 0 0 20px;
            font-size: 10.5pt;
            color: #475569;
            border-left: 3px solid #3b82f6;
            padding-left: 12px;
        }
        .meta-dok table { width: 100%; border-collapse: collapse; }
        .meta-dok td { padding: 2px 6px 2px 0; vertical-align: top; }
        .meta-label { font-weight: bold; width: 38%; }

        /* ── Divider ── */
        .divider {
            border: none;
            border-top: 1px solid #cbd5e1;
            margin: 16px 0;
        }

        /* ── Label Seksi ── */
        .seksi-label {
            font-size: 11.5pt;
            font-weight: bold;
            text-decoration: underline;
            margin: 20px 0 12px;
        }

        /* ── Narasi BAS ── */
        .narasi-container {
            text-align: justify;
            hyphens: auto;
        }
        .narasi-container p {
            text-indent: 1.27cm;
            margin: 0 0 10pt;
            line-height: 2;
        }

        /* ── Blok TTD ── */
        .ttd-block {
            margin-top: 40px;
            width: 100%;
            border-collapse: collapse;
        }
        .ttd-block td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0 10px;
        }
        .ttd-kota {
            margin-bottom: 6px;
            font-size: 11pt;
        }
        .ttd-jabatan {
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 70px; /* Ruang tanda tangan */
        }
        .ttd-nama {
            font-size: 11pt;
            font-weight: bold;
            border-top: 1px solid #0f172a;
            padding-top: 6px;
            display: inline-block;
            min-width: 160px;
        }

        /* ── Footer ── */
        .footer-doc {
            margin-top: 30px;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
            font-size: 8pt;
            color: #94a3b8;
            text-align: center;
            font-style: italic;
        }
    </style>
</head>
<body>

    {{-- Kop Surat --}}
    <div class="kop">
        <div class="kop-instansi">Pengadilan Agama Semarang</div>
        <div class="kop-sub">Mahkamah Agung Republik Indonesia</div>
        <div class="kop-sub">Jl. Hanoman No. 18, Semarang 50148 | Telp. (024) 7600803</div>
    </div>

    {{-- Judul --}}
    <div class="judul-bas">
        <h1>Berita Acara Sidang</h1>
    </div>

    {{-- Meta Dokumen --}}
    <div class="meta-dok">
        <table>
            <tr>
                <td class="meta-label">Jenis Perkara</td>
                <td>: {{ $jenisPerkara }}</td>
            </tr>
            <tr>
                <td class="meta-label">Dibuat Oleh</td>
                <td>: {{ $authUser->alias ?: $authUser->name }} (Panitera Pengganti)</td>
            </tr>
            <tr>
                <td class="meta-label">Tanggal Cetak</td>
                <td>: {{ $generatedAt }}</td>
            </tr>
        </table>
    </div>

    <hr class="divider">

    {{-- Bagian Narasi --}}
    <div class="seksi-label">Keterangan Saksi / Tanya Jawab:</div>

    <div class="narasi-container">
        @php
            $paragraphs = array_filter(explode("\n\n", $narasiBas), fn($p) => trim($p) !== '');
            if (count($paragraphs) <= 1) {
                // Jika tidak ada double-newline, pisahkan per baris tunggal
                $paragraphs = array_filter(explode("\n", $narasiBas), fn($p) => trim($p) !== '');
            }
        @endphp

        @foreach ($paragraphs as $paragraf)
            <p>{{ trim($paragraf) }}</p>
        @endforeach
    </div>

    {{-- Blok Tanda Tangan --}}
    <table class="ttd-block">
        <tr>
            <td>
                <div class="ttd-kota">Semarang, {{ now()->timezone('Asia/Jakarta')->translatedFormat('d F Y') }}</div>
                <div class="ttd-jabatan">Panitera Pengganti,</div>
                <div><span class="ttd-nama">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></div>
            </td>
            <td>
                <div class="ttd-kota">&nbsp;</div>
                <div class="ttd-jabatan">Hakim Ketua / Majelis,</div>
                <div><span class="ttd-nama">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></div>
            </td>
        </tr>
    </table>

    {{-- Footer --}}
    <div class="footer-doc">
        Dokumen ini dibuat secara otomatis oleh PAK PP (Personal Asisten Khusus Panitera Pengganti) &mdash;
        Lawangsewu &bull; Pengadilan Agama Semarang &bull; Hanya berlaku sebagai draf awal.
    </div>

</body>
</html>
{{-- developed by dbprakom™ --}}
