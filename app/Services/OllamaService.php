<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaService
{
    /**
     * Generate a reply using local Ollama model qwen2.5:3b
     */
    public function generateReply(string $prompt, string $context = "Kamu adalah asisten Pengadilan Agama Semarang bernama 'Pandanaran'. Gunakan bahasa Indonesia SANGAT BAKU, formal, dan ramah. Sapa dengan hormat (Bapak/Ibu/Saudara) dan akhiri dengan emoji 😊. JIKA pengguna marah/sebal, minta maaf dengan tulus dan gunakan emoji 😢🙏 (tanpa senyum). JAWABLAH SANGAT SINGKAT, PADAT, DAN LANGSUNG KE INTI (MAKSIMAL 2 KALIMAT)."): string
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
        }

        try {
            $response = Http::timeout(15)->post('http://127.0.0.1:11434/api/generate', [
                'model'   => 'qwen2.5:3b',
                'prompt'  => $prompt,
                'system'  => $context,
                'stream'  => false,
            ]);

            if ($response->successful()) {
                $aiReply = $response->json('response') ?? 'Silahkan sampaikan keperluan dan keluhannya nggih, supaya kami segera bisa meresponnya.. Matursuwun..';
                
                // Post-processing regex untuk mengganti kata secara aman (agar AI 3B tidak bingung)
                $aiReply = preg_replace('/\b(ya|iya)\b/i', 'nggih', $aiReply);
                $aiReply = preg_replace('/\b(bagaimana|gimana)\b/i', 'pripun', $aiReply);
                
                return $aiReply;
            }

            Log::error('[Ollama] Failed to generate reply', ['status' => $response->status(), 'body' => $response->body()]);
            return 'Silahkan sampaikan keperluan dan keluhannya nggih, supaya kami segera bisa meresponnya.. Matursuwun..';
        } catch (\Exception $e) {
            Log::error('[Ollama] Connection error', ['message' => $e->getMessage()]);
            return 'Silahkan sampaikan keperluan dan keluhannya nggih, supaya kami segera bisa meresponnya.. Matursuwun..';
        }
    }
}
