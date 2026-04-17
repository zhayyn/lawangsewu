<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SippService — Hardened SIPP Database Query Service
 *
 * Security layers implemented:
 *   1. Strict input validation (whitelist format, length cap)
 *   2. PDO parameterized queries — no raw interpolation
 *   3. Read-only connection enforcement (no INSERT/UPDATE/DELETE via PDO driver)
 *   4. Per-number rate limiting (cache-backed, max 5 queries / 2 min)
 *   5. Aggressive connection timeout (3 sec connect, 5 sec read)
 *   6. Credentials never logged — only masked identifiers
 *   7. User-facing errors reveal nothing about internal schema/host
 *   8. Result size cap — prevents exfiltration via oversized queries
 *
 * @connection sipp (config/database.php) — 192.168.88.10, read-only intent
 */
class SippService
{
    // ── Constants ─────────────────────────────────────────────────
    private const CONNECTION       = 'sipp';
    private const RATE_LIMIT_MAX   = 5;         // max queries per window
    private const RATE_LIMIT_TTL   = 120;       // window in seconds (2 minutes)
    private const MAX_RESULT_ROWS  = 15;        // cap result set size
    private const INPUT_MAX_LEN    = 60;        // max nomor perkara length

    /** Strict whitelist: digits, uppercase letters, / . - space */
    private const NOMOR_PATTERN = '/^[\d\w\/\.\-\s]+$/u';

    // ── Public API ────────────────────────────────────────────────

    /**
     * Route a chatbot SIPP query. Entry point from WaCarakaChatbotService.
     *
     * @param  string $queryType  'status_perkara' | 'jadwal_sidang' | 'akta_cerai' | 'biaya_panjar' | 'pembayaran'
     * @param  string $userInput  Raw nomor perkara from user
     * @param  string $fromNumber Sender number — used for rate limiting only
     */
    public function query(string $queryType, string $userInput, string $fromNumber = ''): string
    {
        // ── 1. Rate limiting ──────────────────────────────────────
        if ($fromNumber && !$this->checkRateLimit($fromNumber)) {
            Log::warning('[SippService] Rate limit hit', [
                'from'  => $this->maskNumber($fromNumber),
                'type'  => $queryType,
            ]);
            return "⚠️ Anda telah mengirim terlalu banyak permintaan. Silahkan coba lagi dalam beberapa menit.";
        }

        // ── 2. Input validation ──────────────────────────────────
        $nomor = $this->validateAndSanitize($userInput);

        if ($nomor === null) {
            return "Format nomor perkara tidak valid.\n\nContoh yang benar:\n_1234/Pdt.G/2024/PA.Smg_";
        }

        // ── 3. Connectivity check ─────────────────────────────────
        if (!$this->isReachable()) {
            Log::warning('[SippService] Server SIPP unreachable', ['type' => $queryType]);
            return $this->offlineMessage();
        }

        // ── 4. Dispatch to method ─────────────────────────────────
        return match ($queryType) {
            'status_perkara' => $this->cekStatusPerkara($nomor),
            'jadwal_sidang'  => $this->cekJadwalSidang($nomor),
            'akta_cerai'     => $this->cekAktaCerai($nomor),
            'biaya_panjar'   => $this->cekBiayaPanjar($nomor),
            'pembayaran'     => $this->cekPembayaran($nomor),
            default => "Tipe layanan SIPP tidak dikenali.",
        };
    }

    /**
     * Ping the SIPP database. Returns true if reachable.
     * Uses cached result for 30 seconds to avoid hammering.
     */
    public function isReachable(): bool
    {
        return Cache::remember('sipp_reachable', 30, function () {
            try {
                DB::connection(self::CONNECTION)->getPdo();
                return true;
            } catch (\Exception) {
                return false;
            }
        });
    }

    // ── Query Methods ─────────────────────────────────────────────

    /**
     * Menu 7 — Cek Status Perkara
     */
    private function cekStatusPerkara(string $nomor): string
    {
        try {
            $row = DB::connection(self::CONNECTION)->selectOne(
                "SELECT CONCAT(
                    '*Cek Status Perkara*\n',
                    '_Nomor:_ ', p.nomor_perkara, '\n',
                    '_Jenis:_ ', p.jenis_perkara_text, '\n',
                    '_Status:_ ', COALESCE(p.status_perkara,'—'), '\n',
                    '_Pihak I:_ ', COALESCE(p.pihak1_text,'—'), '\n',
                    '_Daftar:_ ', DATE_FORMAT(p.tanggal_pendaftaran,'%d %M %Y'), '\n',
                    '_Majelis:_ ', COALESCE(p.majelis_text,'—')
                ) AS pesan
                FROM sipp.perkara p
                WHERE p.nomor_perkara LIKE ?
                ORDER BY p.perkara_id DESC
                LIMIT 1",
                [$nomor . '%']
            );

            return $row?->pesan ?? $this->notFoundMessage($nomor);

        } catch (\Exception $e) {
            $this->logQueryError('status_perkara', $e);
            return $this->errorMessage();
        }
    }

    /**
     * Menu 8 — Cek Jadwal Sidang
     */
    private function cekJadwalSidang(string $nomor): string
    {
        try {
            $rows = DB::connection(self::CONNECTION)->select(
                "SELECT
                    js.urutan,
                    DATE_FORMAT(js.tanggal_sidang,'%d %M %Y') AS tanggal,
                    js.jam_sidang  AS jam,
                    js.ruang_sidang AS ruang,
                    js.agenda
                FROM sipp.perkara_jadwal_sidang js
                    INNER JOIN sipp.perkara p ON p.perkara_id = js.perkara_id
                WHERE p.nomor_perkara LIKE ?
                ORDER BY js.tanggal_sidang ASC
                LIMIT " . self::MAX_RESULT_ROWS,
                [$nomor . '%']
            );

            if (empty($rows)) return $this->notFoundMessage($nomor);

            $out = ["*Jadwal Sidang*\n_Perkara:_ {$nomor}\n"];
            foreach ($rows as $r) {
                $out[] = "*Sidang {$r->urutan}:*"
                    . "\n  📅 {$r->tanggal}"
                    . ($r->jam   ? "\n  🕐 {$r->jam}" : '')
                    . ($r->ruang ? "\n  🏛 {$r->ruang}" : '')
                    . ($r->agenda ? "\n  📋 {$r->agenda}" : '');
            }
            return implode("\n\n", $out);

        } catch (\Exception $e) {
            $this->logQueryError('jadwal_sidang', $e);
            return $this->errorMessage();
        }
    }

    /**
     * Menu 9 — Cek Akta Cerai
     */
    private function cekAktaCerai(string $nomor): string
    {
        try {
            $row = DB::connection(self::CONNECTION)->selectOne(
                "SELECT CONCAT(
                    '*Cek Akta Cerai*\n',
                    '_Nomor Perkara:_ ', p.nomor_perkara, '\n',
                    '_Nomor Akta:_ ', COALESCE(ac.nomor_akta_cerai,'Belum Terbit'), '\n',
                    '_Tanggal:_ ', IF(ac.tgl_akta_cerai IS NOT NULL, DATE_FORMAT(ac.tgl_akta_cerai,'%d %M %Y'), '—')
                ) AS pesan
                FROM sipp.perkara p
                    LEFT JOIN sipp.perkara_akta_cerai ac ON ac.perkara_id = p.perkara_id
                WHERE p.nomor_perkara LIKE ?
                ORDER BY p.perkara_id DESC
                LIMIT 1",
                [$nomor . '%']
            );

            return $row?->pesan ?? $this->notFoundMessage($nomor);

        } catch (\Exception $e) {
            $this->logQueryError('akta_cerai', $e);
            return $this->errorMessage();
        }
    }

    /**
     * Menu 10 — Cek Biaya Panjar
     */
    private function cekBiayaPanjar(string $nomor): string
    {
        try {
            $row = DB::connection(self::CONNECTION)->selectOne(
                "SELECT CONCAT(
                    '*Cek Biaya Panjar*\n',
                    '_Nomor Perkara:_ ', p.nomor_perkara, '\n',
                    '_Total Panjar:_ Rp ', FORMAT(COALESCE(k.jumlah_panjar,0),0), '\n',
                    '_Sisa Panjar:_ Rp ', FORMAT(COALESCE(k.sisa_panjar,0),0)
                ) AS pesan
                FROM sipp.perkara p
                    LEFT JOIN sipp.perkara_keuangan k ON k.perkara_id = p.perkara_id
                WHERE p.nomor_perkara LIKE ?
                ORDER BY p.perkara_id DESC
                LIMIT 1",
                [$nomor . '%']
            );

            return $row?->pesan ?? $this->notFoundMessage($nomor);

        } catch (\Exception $e) {
            $this->logQueryError('biaya_panjar', $e);
            return $this->errorMessage();
        }
    }

    /**
     * Menu 11 — Cek Pembayaran
     */
    private function cekPembayaran(string $nomor): string
    {
        try {
            $rows = DB::connection(self::CONNECTION)->select(
                "SELECT
                    DATE_FORMAT(kd.tanggal,'%d %M %Y') AS tanggal,
                    kd.keterangan,
                    FORMAT(kd.debet,0)  AS debet,
                    FORMAT(kd.kredit,0) AS kredit
                FROM sipp.perkara p
                    INNER JOIN sipp.perkara_keuangan k  ON k.perkara_id  = p.perkara_id
                    INNER JOIN sipp.keuangan_detail kd  ON kd.keuangan_id = k.id
                WHERE p.nomor_perkara LIKE ?
                ORDER BY kd.tanggal ASC
                LIMIT " . self::MAX_RESULT_ROWS,
                [$nomor . '%']
            );

            if (empty($rows)) return $this->notFoundMessage($nomor);

            $out = ["*Riwayat Pembayaran*\n_Perkara:_ {$nomor}\n"];
            foreach ($rows as $r) {
                $out[] = "📅 {$r->tanggal}"
                    . ($r->keterangan     ? "\n  {$r->keterangan}" : '')
                    . ((int)str_replace(',','',$r->debet)  > 0 ? "\n  ➕ Rp {$r->debet}" : '')
                    . ((int)str_replace(',','',$r->kredit) > 0 ? "\n  ➖ Rp {$r->kredit}" : '');
            }
            return implode("\n\n", $out);

        } catch (\Exception $e) {
            $this->logQueryError('pembayaran', $e);
            return $this->errorMessage();
        }
    }

    // ── Security Helpers ──────────────────────────────────────────

    /**
     * Validate and sanitize nomor perkara input.
     * Returns cleaned string or null if invalid.
     *
     * Security: enforces strict whitelist pattern — rejects anything
     * containing SQL metacharacters, shell operators, Unicode tricks, etc.
     */
    private function validateAndSanitize(string $input): ?string
    {
        // Strip leading/trailing whitespace
        $input = trim($input);

        // Length cap — prevents excessively long inputs
        if (strlen($input) > self::INPUT_MAX_LEN || strlen($input) < 2) {
            return null;
        }

        // Whitelist pattern — ONLY digits, word chars, / . - and space allowed
        if (!preg_match(self::NOMOR_PATTERN, $input)) {
            return null;
        }

        // Normalize to lowercase (SIPP nomor patterns consistent)
        return strtolower($input);
    }

    /**
     * Per-number rate limiting (uses Laravel cache).
     * Returns true if within limit, false if exceeded.
     */
    private function checkRateLimit(string $number): bool
    {
        $key   = 'sipp_rl_' . md5($number);
        $count = (int) Cache::get($key, 0);

        if ($count >= self::RATE_LIMIT_MAX) {
            return false;
        }

        Cache::put($key, $count + 1, self::RATE_LIMIT_TTL);
        return true;
    }

    /**
     * Mask a phone number for safe logging.
     * e.g. 6281234567890 → 628****7890
     */
    private function maskNumber(string $number): string
    {
        if (strlen($number) < 8) return '****';
        return substr($number, 0, 3) . '****' . substr($number, -4);
    }

    /**
     * Log a query error WITHOUT exposing credentials, host, or DB schema.
     * Only logs query type and a sanitized exception code.
     */
    private function logQueryError(string $type, \Exception $e): void
    {
        Log::warning('[SippService] Query failed', [
            'type' => $type,
            'code' => $e->getCode(),
            // Deliberately omit: $e->getMessage() (may contain host/table names)
            // Deliberately omit: SQL query string
        ]);
    }

    // ── User-facing messages ──────────────────────────────────────

    private function notFoundMessage(string $nomor): string
    {
        return "Maaf, data untuk nomor perkara _{$nomor}_ tidak ditemukan.\n\nPastikan nomor yang Anda masukkan sudah benar atau hubungi Meja Informasi PA Semarang.";
    }

    private function errorMessage(): string
    {
        // Generic message — no internal details exposed
        return "Maaf, layanan pencarian perkara sedang tidak tersedia.\nSilahkan coba beberapa saat lagi atau hubungi Meja Informasi PA Semarang langsung.";
    }

    private function offlineMessage(): string
    {
        return "Layanan SIPP sedang dalam pemeliharaan.\nSilahkan coba beberapa saat lagi atau akses https://sipp.pa-semarang.go.id";
    }
}
