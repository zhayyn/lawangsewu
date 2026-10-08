<?php

namespace App\Services;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Style\Font;

/**
 * PakPpDocxExportService
 *
 * Mengubah teks narasi BAS menjadi dokumen .docx yang siap pakai,
 * mengikuti format Berita Acara Sidang Pengadilan Agama standar MA RI.
 *
 * Analogi: Layaknya seorang juru ketik pengadilan yang menerima narasi dari PP
 * dan langsung mencetak ke kop surat resmi — dalam hitungan detik.
 */
class PakPpDocxExportService
{
    private const FONT_FAMILY   = 'Times New Roman';
    private const FONT_SIZE_PT  = 12;
    private const LINE_SPACING  = 240; // twips (240 = 1 line, single spacing in Word)

    /**
     * Buat file DOCX berisi narasi BAS dan kembalikan path sementara file tersebut.
     *
     * @param  string  $narasiBas    Teks narasi BAS yang sudah dihasilkan / diedit PP
     * @param  string  $jenisPerkara Jenis perkara sidang (Cerai Gugat, dll)
     * @param  string  $generatedBy  Nama user yang mengekspor
     * @return string  Absolute path ke file .docx sementara
     */
    public function buildAndSave(string $narasiBas, string $jenisPerkara, string $generatedBy): string
    {
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName(self::FONT_FAMILY);
        $phpWord->setDefaultFontSize(self::FONT_SIZE_PT);

        // Definisikan gaya paragraf standar BAS
        $phpWord->addParagraphStyle('BasNarasi', [
            'spaceAfter'  => 200,
            'lineHeight'  => 1.5,
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
            'indentation' => ['firstLine' => 720], // 1.27 cm indent (standar BAS)
        ]);

        $phpWord->addParagraphStyle('BasHeader', [
            'spaceAfter'  => 120,
            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
        ]);

        $phpWord->addParagraphStyle('BasMeta', [
            'spaceAfter' => 80,
            'alignment'  => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
        ]);

        // ── Layout halaman A4 ──────────────────────────────────────────────
        $section = $phpWord->addSection([
            'paperSize'   => 'A4',
            'marginTop'   => 1701, // ~3 cm
            'marginBottom'=> 1418, // ~2.5 cm
            'marginLeft'  => 1701,
            'marginRight' => 1134,
        ]);

        // ── Kop / Header ──────────────────────────────────────────────────
        $section->addText(
            'BERITA ACARA SIDANG',
            ['name' => self::FONT_FAMILY, 'size' => 14, 'bold' => true],
            'BasHeader',
        );

        $section->addText(
            'Pengadilan Agama Semarang',
            ['name' => self::FONT_FAMILY, 'size' => 12, 'bold' => true],
            'BasHeader',
        );

        $section->addText(
            'Jenis Perkara: ' . $jenisPerkara,
            ['name' => self::FONT_FAMILY, 'size' => 10, 'italic' => true, 'color' => '555555'],
            'BasMeta',
        );

        $section->addText(
            'Dibuat oleh: ' . $generatedBy . ' · ' . now()->timezone('Asia/Jakarta')->translatedFormat('d F Y'),
            ['name' => self::FONT_FAMILY, 'size' => 9, 'color' => '888888'],
            'BasMeta',
        );

        $section->addLine(['weight' => 1, 'color' => '999999']);

        // ── Label Bagian ──────────────────────────────────────────────────
        $section->addTextBreak(1);
        $section->addText(
            'Keterangan Saksi / Tanya Jawab',
            ['name' => self::FONT_FAMILY, 'size' => 11, 'bold' => true, 'underline' => Font::UNDERLINE_SINGLE],
            ['spaceAfter' => 200],
        );
        $section->addTextBreak(1);

        // ── Narasi BAS — setiap baris menjadi paragraf terpisah ───────────
        $lines = explode("\n", $narasiBas);

        foreach ($lines as $line) {
            $trimmedLine = trim($line);

            if ($trimmedLine === '') {
                $section->addTextBreak(1);
                continue;
            }

            $section->addText(
                $trimmedLine,
                ['name' => self::FONT_FAMILY, 'size' => self::FONT_SIZE_PT],
                'BasNarasi',
            );
        }

        // ── Footer tanda tangan ────────────────────────────────────────────
        $section->addTextBreak(2);

        $ttdTable = $section->addTable(['borderSize' => 0, 'cellMargin' => 0]);
        $ttdTable->addRow();
        $ttdTable->addCell(4320)->addText('Panitera Pengganti,', ['name' => self::FONT_FAMILY, 'size' => 11], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        $ttdTable->addCell(4320)->addText('Hakim Ketua,', ['name' => self::FONT_FAMILY, 'size' => 11], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);

        $ttdTable->addRow(['exactHeight' => 1440]); // 2.5 cm ruang TTD
        $ttdTable->addCell(4320)->addText('', [], []);
        $ttdTable->addCell(4320)->addText('', [], []);

        $ttdTable->addRow();
        $ttdTable->addCell(4320)->addText('(___________________)', ['name' => self::FONT_FAMILY, 'size' => 11], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        $ttdTable->addCell(4320)->addText('(___________________)', ['name' => self::FONT_FAMILY, 'size' => 11], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);

        // ── Footer watermark ───────────────────────────────────────────────
        $section->addTextBreak(2);
        $section->addText(
            'Dokumen ini dibuat secara otomatis oleh PAK PP — Lawangsewu · Pengadilan Agama Semarang',
            ['name' => self::FONT_FAMILY, 'size' => 8, 'italic' => true, 'color' => 'aaaaaa'],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER],
        );

        // ── Simpan ke file sementara ────────────────────────────────────────
        $tempPath = tempnam(sys_get_temp_dir(), 'pakpp_bas_') . '.docx';
        $writer   = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempPath);

        return $tempPath;
    }
}

// developed by dbprakom™
