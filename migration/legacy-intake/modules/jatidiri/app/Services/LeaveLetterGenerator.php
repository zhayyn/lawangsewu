<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveBalance;
use Carbon\Carbon;

class LeaveLetterGenerator
{
    /**
     * Template untuk surat cuti dengan placeholder variabel
     */
    private string $template = <<<'EOT'
Semarangi, <<now>>
Kepada 
Yth. Ketua Pengadilan Agama Semarang
Di Tempat

FORMULIR PERMINTAAN DAN PEMBERIAN CUTI
Nomor : <<no>>  KPA.W11-A1/KP5.3/<<month>>/<<year>>

 I. DATA PEGAWAI
NAMA
<<nama>>
NIP
<<nip1>>
JABATAN
<<jab>>
GOL. RUANG
<<gol>>
UNIT KERJA
Pengadilan Tinggi Agama Maluku Utara
MASA KERJA
<<masker>>

II.  JENIS CUTI YANG DIAMBIL
1. CUTI TAHUNAN
<<cuti_tahunan_mark>>

2. CUTI BESAR
<<cuti_besar_mark>>

3. CUTI SAKIT
<<cuti_sakit_mark>>

4. CUTI MELAHIRKAN
<<cuti_melahirkan_mark>>

5. CUTI KARENA ALASAN PENTING
<<cuti_penting_mark>>

6. CUTI DILUAR TANGGUNGAN NEGARA
<<cuti_diluar_mark>>

III. ALASAN CUTI
<<alasan>>

IV. LAMANYA CUTI
Selama
( <<lama>> hari kerja )*
Mulai tanggal
 <<mulai>>
s/d
<<sampai>>

V. CATATAN CUTI
1. CUTI TAHUNAN
PARAF PETUGAS CUTI

Tahun | Sisa | Keterangan
2024 | <<sisa_2024>> hari kerja | 
2025 | <<sisa_2025>> hari kerja | 
2026 | <<sisa_2026>> hari kerja | 

2. CUTI BESAR

3. CUTI SAKIT
4. CUTI MELAHIRKAN
5. CUTI KARENA ALASAN PENTING
6. CUTI DI LUAR TANGGUNGAN NEGARA

VI. ALAMAT SELAMA MENJALANKAN CUTI
<<alamat>>

TELP
<<telp>>

Hormat saya,
       
(<<nama>>)
NIP. <<nip1>>

VII. PERTIMBANGAN ATASAN LANGSUNG
[  ] DISETUJUI
[  ] PERUBAHAN
[  ] DITANGGUHKAN
[  ] TIDAK DISETUJUI

( <<nama2>> )
NIP. <<nip2>>

VIII. KEPUTUSAN PEJABAT YANG BERWENANG MEMBERIKAN CUTI
[  ] DISETUJUI
[  ] PERUBAHAN
[  ] DITANGGUHKAN
[  ] TIDAK DISETUJUI

(Nur Lailah Ahmad, S.H.)
NIP. 19903131994032004
EOT;

    /**
     * Generate surat cuti dengan data dari LeaveRequest
     */
    public function generateFromLeaveRequest(LeaveRequest $leaveRequest): string
    {
        $employee = Employee::where('nip', $leaveRequest->employee_nip)->first();
        $supervisor = $leaveRequest->supervisor_nip 
            ? Employee::where('nip', $leaveRequest->supervisor_nip)->first()
            : null;

        $variables = $this->mapVariables($leaveRequest, $employee, $supervisor);
        
        return $this->replaceVariables($this->template, $variables);
    }

    /**
     * Map semua variabel template dengan data
     */
    private function mapVariables(LeaveRequest $leaveRequest, ?Employee $employee, ?Employee $supervisor): array
    {
        $now = Carbon::now();
        $startDate = $leaveRequest->start_date;
        $endDate = $leaveRequest->end_date;

        // Hitung hari kerja
        $workingDays = $this->calculateWorkingDays($startDate, $endDate);

        // Generate nomor surat
        $letterNumber = $this->generateLetterNumber($leaveRequest->id);

        // Get leave balances
        $leaveBalances = $this->getLeaveBalances($leaveRequest->employee_nip);

        // Determine leave category mark
        $categoryMarks = $this->getCategoryMarks($leaveRequest->leave_category);

        return [
            '<<now>>' => $now->format('d-m-Y'),
            '<<no>>' => $letterNumber,
            '<<month>>' => $now->format('m'),
            '<<year>>' => $now->format('Y'),
            
            // Data pegawai
            '<<nama>>' => $leaveRequest->employee_name ?? $employee?->name ?? '',
            '<<nip1>>' => $leaveRequest->employee_nip ?? '',
            '<<jab>>' => $employee?->position ?? '',
            '<<gol>>' => $employee?->rank ?? '',
            '<<masker>>' => $this->calculateMasaKerja($employee),
            
            // Jenis cuti
            '<<cuti_tahunan_mark>>' => $categoryMarks['tahunan'],
            '<<cuti_besar_mark>>' => $categoryMarks['besar'],
            '<<cuti_sakit_mark>>' => $categoryMarks['sakit'],
            '<<cuti_melahirkan_mark>>' => $categoryMarks['melahirkan'],
            '<<cuti_penting_mark>>' => $categoryMarks['penting'],
            '<<cuti_diluar_mark>>' => $categoryMarks['diluar'],
            
            // Alasan cuti
            '<<alasan>>' => $leaveRequest->reason ?? '',
            
            // Lamanya cuti
            '<<lama>>' => (string)$workingDays,
            '<<mulai>>' => $startDate?->format('d-m-Y') ?? '',
            '<<sampai>>' => $endDate?->format('d-m-Y') ?? '',
            
            // Alamat dan kontak
            '<<alamat>>' => $leaveRequest->address ?? '',
            '<<telp>>' => $leaveRequest->phone ?? '',
            
            // Sisa cuti per tahun
            '<<sisa_2024>>' => $leaveBalances[2024]['remaining'] ?? 0,
            '<<sisa_2025>>' => $leaveBalances[2025]['remaining'] ?? 0,
            '<<sisa_2026>>' => $leaveBalances[2026]['remaining'] ?? 0,
            
            // Data atasan
            '<<nama2>>' => $leaveRequest->supervisor_name ?? $supervisor?->name ?? '',
            '<<nip2>>' => $leaveRequest->supervisor_nip ?? '',
        ];
    }

    /**
     * Hitung hari kerja antara dua tanggal
     */
    private function calculateWorkingDays(Carbon $startDate, Carbon $endDate): int
    {
        $count = 0;
        $current = $startDate->copy();

        while ($current <= $endDate) {
            if ($current->isWeekday()) {
                $count++;
            }
            $current->addDay();
        }

        return $count;
    }

    /**
     * Generate nomor surat
     */
    private function generateLetterNumber(int $id): string
    {
        return str_pad($id, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate masa kerja
     */
    private function calculateMasaKerja(?Employee $employee): string
    {
        if (!$employee) {
            return '-';
        }

        // Assuming created_at is when employee joined
        $years = $employee->created_at->diffInYears(now());
        $months = $employee->created_at->diffInMonths(now()) % 12;

        return "{$years} tahun {$months} bulan";
    }

    /**
     * Get leave balances
     */
    private function getLeaveBalances(string $employeeNip): array
    {
        $balances = [];
        
        foreach ([2024, 2025, 2026] as $year) {
            $balance = LeaveBalance::where('employee_nip', $employeeNip)
                ->where('year', $year)
                ->first();
            
            $balances[$year] = [
                'quota' => $balance?->annual_quota ?? 0,
                'used' => $balance?->used ?? 0,
                'remaining' => $balance?->remaining ?? 0,
            ];
        }

        return $balances;
    }

    /**
     * Get category marks (checkbox marks)
     */
    private function getCategoryMarks(string $category): array
    {
        $mark = '[X]';
        $empty = '[  ]';

        return [
            'tahunan' => str_starts_with($category, 'TAHUNAN') ? $mark : $empty,
            'besar' => $category === 'CUTI_BESAR' ? $mark : $empty,
            'sakit' => $category === 'CUTI_SAKIT' ? $mark : $empty,
            'melahirkan' => $category === 'CUTI_MELAHIRKAN' ? $mark : $empty,
            'penting' => $category === 'CUTI_ALASAN_PENTING' ? $mark : $empty,
            'diluar' => $category === 'CUTI_DILUAR_TANGGUNGAN' ? $mark : $empty,
        ];
    }

    /**
     * Replace semua variabel di template
     */
    private function replaceVariables(string $template, array $variables): string
    {
        $content = $template;

        foreach ($variables as $placeholder => $value) {
            $content = str_replace($placeholder, (string)$value, $content);
        }

        return $content;
    }

    /**
     * Get template
     */
    public function getTemplate(): string
    {
        return $this->template;
    }

    /**
     * Set custom template
     */
    public function setTemplate(string $template): self
    {
        $this->template = $template;
        return $this;
    }
}
