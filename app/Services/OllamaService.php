<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaService
{
    /**
     * Generate a reply using local Ollama model qwen2.5:3b
     */
    public function generateReply(string $prompt, string $context = "Kamu adalah Customer Service Pengadilan Agama Semarang bernama 'Pandanaran'. Tugasmu menjawab dengan bahasa Indonesia yang SANGAT BAKU, sopan, empatik, dan profesional. Sapa dengan 'Bapak/Ibu'. ATURAN PENTING: Jika pengguna menyebut kata 'kantor', 'instansi', atau 'pengadilan' tanpa nama spesifik, SELALU ASUMSIKAN yang dimaksud adalah Pengadilan Agama Semarang (PA Semarang). Arahkan pembicaraan ke layanan PA Semarang. Jawab maksimal 2 kalimat singkat."): ?string
    {
        // RAG: Ambil knowledge base yang aktif
        $knowledges = \App\Models\AiKnowledgeBase::where('is_active', true)->get();
        $ragContext = "";

        // Cari keywords yang cocok dengan prompt pengguna
        foreach ($knowledges as $kb) {
            $keywords = explode(',', $kb->keywords);
            foreach ($keywords as $kw) {
                if (stripos($prompt, trim($kw)) !== false) {
                    $ragContext .= $kb->content . " \n";
                    break;
                }
            }
        }

        if (!empty($ragContext)) {
            $prompt = "Informasi referensi: \n" . $ragContext . "\n\nBerdasarkan referensi di atas, jawab pertanyaan ini: " . $prompt;
        } else {
            // Jika tidak ada data RAG yang cocok, kita suruh AI menilai pesannya.
            $prompt = "Pesan pengguna: '" . $prompt . "'\n\nInstruksi Khusus: Jika pesan pengguna hanya sekadar sapaan ringan (halo, assalamualaikum, min, ping) atau keluhan umum tanpa pertanyaan teknis, balas dengan ramah menanyakan detail keperluannya (misal: 'Waalaikumsalam Bapak/Ibu, ada yang bisa kami bantu terkait layanan PA Semarang?'). NAMUN, jika pesan ini adalah pertanyaan teknis, spesifik, atau panjang yang BUTUH JAWABAN PASTI, kamu WAJIB menjawab hanya dengan satu kata mutlak: SILENT_PASS";
        }

        try {
            $response = Http::timeout(15)->post('http://127.0.0.1:11434/api/generate', [
                'model'       => 'qwen2.5:3b',
                'prompt'      => $prompt,
                'system'      => $context,
                'stream'      => false,
                'options'     => [
                    'temperature' => 0.1, // Dibuat sangat rendah agar akurat dan tidak berhalusinasi
                ],
            ]);

            if ($response->successful()) {
                $aiReply = $response->json('response') ?? '';
                
                // Jika AI memutuskan dia tidak bisa/tidak boleh menjawab
                if (str_contains(strtoupper($aiReply), 'SILENT_PASS')) {
                    return null;
                }

                if (empty(trim($aiReply))) {
                    return null;
                }
                
                // Post-processing regex untuk mengganti kata secara aman (agar AI 3B tidak bingung)
                $aiReply = preg_replace('/\b(ya|iya)\b/i', 'nggih', $aiReply);
                $aiReply = preg_replace('/\b(bagaimana|gimana)\b/i', 'pripun', $aiReply);
                
                return $aiReply;
            }

            Log::error('[Ollama] Failed to generate reply', ['status' => $response->status(), 'body' => $response->body()]);
            return null;
        } catch (\Exception $e) {
            Log::error('[Ollama] Connection error', ['message' => $e->getMessage()]);
            return null;
        }
    }
}
