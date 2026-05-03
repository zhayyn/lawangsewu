<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Parsedown;

/**
 * DocumentStylerService
 *
 * Menghasilkan dokumen PDF interaktif dengan dukungan tabel dan emoji.
 * Dikembangkan untuk meningkatkan standar visual pelaporan di Super App Lawangsewu.
 *
 * Implementasi menggunakan:
 * - Parsedown  : konversi Markdown → HTML
 * - DomPDF     : render HTML → PDF
 *
 * developed by zhayyn™
 */
class DocumentStylerService
{
    /**
     * Menghasilkan PDF dari konten Markdown, dengan tema Lawangsewu.
     *
     * @param  string  $markdownContent  Konten laporan dalam format Markdown.
     * @param  string  $title            Judul laporan (muncul di header PDF).
     * @param  string  $orientation      'portrait' | 'landscape'
     * @return \Barryvdh\DomPDF\PDF
     *
     * @throws \InvalidArgumentException Jika konten laporan kosong.
     */
    public function createInteractiveReport(
        string $markdownContent,
        string $title = 'Laporan Lawangsewu',
        string $orientation = 'portrait'
    ) {
        // Memvalidasi konten agar tidak memproses laporan yang kosong
        if (empty(trim($markdownContent))) {
            throw new \InvalidArgumentException('KONTEN LAPORAN TIDAK BOLEH KOSONG, TUAN MUDA.');
        }

        $htmlContent   = $this->convertToHtml($markdownContent);
        $styledHtml    = $this->applyLawangsewuTheme($htmlContent, $title);

        return $this->renderToPdf($styledHtml, $orientation);
    }

    /**
     * Mengkonversi teks Markdown menjadi HTML menggunakan Parsedown.
     * Emoji Unicode didukung secara native oleh browser/dompdf tanpa konversi tambahan.
     */
    private function convertToHtml(string $markdown): string
    {
        $parsedown = new Parsedown();
        $parsedown->setSafeMode(false); // izinkan HTML mentah di dalam Markdown
        $parsedown->setBreaksEnabled(true); // baris baru → <br>

        return $parsedown->text($markdown);
    }

    /**
     * Menyisipkan CSS tema Lawangsewu: header, tabel berwarna, badge status, emoji.
     * Palet warna mengacu pada identitas visual sistem (biru-navy #1a5f7a).
     */
    private function applyLawangsewuTheme(string $html, string $title): string
    {
        $generatedAt = now()->locale('id')->isoFormat('dddd, D MMMM YYYY • HH:mm') . ' WIB';

        $style = "
            <style>
                /* ── Reset & Base ───────────────────────────── */
                * { box-sizing: border-box; margin: 0; padding: 0; }

                @page {
                    margin: 30mm 30mm 30mm 40mm;
                }

                body {
                    font-family: 'DejaVu Sans', 'Arial', sans-serif;
                    font-size: 11px;
                    line-height: 1.65;
                    color: #1e293b;
                    background: #ffffff;
                }

                /* ── Header ───────────────────────────────────── */
                .doc-header {
                    border-bottom: 3px solid #1a5f7a;
                    padding-bottom: 14px;
                    margin-bottom: 24px;
                    width: 100%;
                }

                .doc-logo {
                    width: 42px;
                    height: 42px;
                    background: #1a5f7a;
                    border-radius: 8px;
                    text-align: center;
                    color: #ffffff;
                    font-size: 18px;
                    font-weight: 900;
                    line-height: 42px;
                }

                .doc-app-name {
                    font-size: 9px;
                    font-weight: 700;
                    text-transform: uppercase;
                    letter-spacing: 0.18em;
                    color: #64748b;
                }

                .doc-title {
                    font-size: 18px;
                    font-weight: 900;
                    color: #0f172a;
                    margin-top: 2px;
                }

                .doc-meta {
                    font-size: 9px;
                    color: #94a3b8;
                    margin-top: 3px;
                }

                /* ── Content Typography ───────────────────────── */
                h1, h2, h3, h4, h5, h6 {
                    color: #0f172a;
                    font-weight: 800;
                    margin-top: 20px;
                    margin-bottom: 8px;
                    line-height: 1.3;
                }

                h1 { font-size: 17px; border-bottom: 2px solid #e2e8f0; padding-bottom: 6px; }
                h2 { font-size: 14px; color: #1a5f7a; }
                h3 { font-size: 12px; }
                h4, h5, h6 { font-size: 11px; }

                p {
                    margin-bottom: 10px;
                    color: #334155;
                }

                strong { color: #0f172a; }

                em { color: #475569; }

                code {
                    background: #f1f5f9;
                    border: 1px solid #e2e8f0;
                    border-radius: 4px;
                    padding: 1px 5px;
                    font-size: 10px;
                    font-family: 'DejaVu Sans Mono', monospace;
                    color: #dc2626;
                }

                pre {
                    background: #1e293b;
                    color: #e2e8f0;
                    padding: 12px 14px;
                    border-radius: 8px;
                    font-size: 9.5px;
                    margin: 12px 0;
                    overflow: hidden;
                }

                pre code {
                    background: none;
                    border: none;
                    padding: 0;
                    color: #e2e8f0;
                    font-size: inherit;
                }

                blockquote {
                    border-left: 4px solid #1a5f7a;
                    background: #f0f9ff;
                    padding: 8px 14px;
                    margin: 12px 0;
                    border-radius: 0 6px 6px 0;
                    color: #0369a1;
                    font-style: italic;
                }

                /* ── Lists ────────────────────────────────────── */
                ul, ol {
                    padding-left: 20px;
                    margin-bottom: 10px;
                    color: #334155;
                }

                li { margin-bottom: 4px; }

                /* ── Table — Tema Lawangsewu ──────────────────── */
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 16px 0;
                    font-size: 10.5px;
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 1px 4px rgba(0,0,0,0.07);
                }

                thead tr {
                    background: linear-gradient(90deg, #1a5f7a 0%, #164e63 100%);
                }

                th {
                    color: #ffffff;
                    font-weight: 700;
                    font-size: 10px;
                    text-transform: uppercase;
                    letter-spacing: 0.08em;
                    padding: 10px 12px;
                    text-align: left;
                    border: none;
                }

                td {
                    padding: 8px 12px;
                    border-bottom: 1px solid #e2e8f0;
                    vertical-align: top;
                    color: #334155;
                }

                tr:nth-child(even) td {
                    background-color: #f8fafc;
                }

                tr:last-child td {
                    border-bottom: 2px solid #1a5f7a;
                }

                tbody tr:hover td {
                    background-color: #eff6ff;
                }

                /* ── Status Badge / Highlight ─────────────────── */
                .highlight {
                    color: #dc2626;
                    font-weight: 700;
                }

                .badge {
                    display: inline-block;
                    padding: 2px 8px;
                    border-radius: 20px;
                    font-size: 9px;
                    font-weight: 700;
                    text-transform: uppercase;
                    letter-spacing: 0.06em;
                }

                .badge-success  { background: #dcfce7; color: #15803d; }
                .badge-danger   { background: #fee2e2; color: #dc2626; }
                .badge-warning  { background: #fef9c3; color: #a16207; }
                .badge-info     { background: #dbeafe; color: #1d4ed8; }
                .badge-neutral  { background: #f1f5f9; color: #475569; }

                /* ── Emoji Support ────────────────────────────── */
                .emoji {
                    font-family: 'DejaVu Sans', 'Arial Unicode MS', sans-serif;
                }

                /* ── Footer ───────────────────────────────────── */
                .doc-footer {
                    border-top: 2px solid #e2e8f0;
                    margin-top: 32px;
                    padding-top: 10px;
                    width: 100%;
                }

                .doc-footer-left {
                    font-size: 8.5px;
                    color: #94a3b8;
                }

                .doc-footer-right {
                    text-align: right;
                    font-size: 8.5px;
                    color: #cbd5e1;
                    font-weight: 600;
                    text-transform: uppercase;
                }

                /* ── Page break helper ────────────────────────── */
                .page-break { page-break-after: always; }
            </style>
        ";

        $header = "
            <table class='doc-header'>
                <tr>
                    <td style='width: 54px; vertical-align: middle; border: none; padding: 0;'>
                        <div class='doc-logo'>LS</div>
                    </td>
                    <td style='vertical-align: middle; border: none; padding: 0; padding-left: 12px;'>
                        <div class='doc-app-name'>Super App Lawangsewu &bull; Dokumen Resmi</div>
                        <div class='doc-title'>{$title}</div>
                        <div class='doc-meta'>Digenerate pada: {$generatedAt}</div>
                    </td>
                </tr>
            </table>
        ";

        $footer = "
            <table class='doc-footer'>
                <tr>
                    <td class='doc-footer-left' style='border: none; padding: 0;'>
                        Dokumen ini digenerate secara otomatis oleh sistem Lawangsewu.<br>
                        Tidak diperlukan tanda tangan basah.
                    </td>
                    <td class='doc-footer-right' style='border: none; padding: 0;'>&nbsp;</td>
                </tr>
            </table>
        ";

        return "<!DOCTYPE html><html lang='id'><head><meta charset='UTF-8'>{$style}</head>"
             . "<body>{$header}<div class='doc-content'>{$html}</div>{$footer}</body></html>";
    }

    /**
     * Merender HTML ke PDF menggunakan DomPDF (via facade Laravel).
     *
     * @param  string  $fullHtml     HTML lengkap dengan CSS yang sudah disuntikkan.
     * @param  string  $orientation  'portrait' | 'landscape'
     * @return \Barryvdh\DomPDF\PDF
     */
    private function renderToPdf(string $fullHtml, string $orientation = 'portrait')
    {
        $pdf = Pdf::loadHTML($fullHtml)
            ->setPaper('a4', $orientation)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true)
            ->setOption('chroot', public_path())
            ->setOption('defaultFont', 'DejaVu Sans');

        return $pdf;
    }

    /**
     * Menghasilkan nama file PDF yang aman berdasarkan judul laporan.
     */
    public function buildFilename(string $title): string
    {
        $slug = \Illuminate\Support\Str::slug($title, '-');
        $date = now()->format('Ymd-His');

        return "laporan-{$slug}-{$date}.pdf";
    }
}
