<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaService
{
    /**
     * Generate a reply using local Ollama model qwen2.5:3b
     */
    public function generateReply(string $prompt, string $context = "Kamu adalah asisten Pengadilan Agama Semarang bernama 'Pandanaran'. Gunakan bahasa Indonesia SANGAT BAKU, formal, dan ramah. Sapa dengan hormat (Bapak/Ibu/Saudara) dan akhiri dengan emoji 😊. ATURAN KETAT: Gunakan kata 'nggih' MAKSIMAL 1 KALI saja (pengganti kata 'ya') dan 'pripun' MAKSIMAL 1 KALI (pengganti 'bagaimana'). JANGAN ulang-ulang kata 'nggih'. JIKA pengguna marah/sebal, minta maaf dengan tulus dan gunakan emoji 😢🙏 (tanpa senyum). JAWABLAH SANGAT SINGKAT, PADAT, DAN LANGSUNG KE INTI (MAKSIMAL 2 KALIMAT)."): string
    {
        // RAG: Ambil knowledge base yang aktif
        $knowledges = \App\Models\AiKnowledgeBase::where('is_active', true)->get();
        $relevantContexts = [];

        foreach ($knowledges as $kb) {
            $keywords = array_filter(array_map('trim', explode(',', strtolower($kb->keywords))));
            $promptLower = strtolower($prompt);
            
            $isMatch = empty($keywords); // Jika tidak ada keyword, jadikan informasi umum (selalu dilampirkan)
            
            foreach ($keywords as $kw) {
                if (str_contains($promptLower, $kw)) {
                    $isMatch = true;
                    break;
                }
            }
            
            if ($isMatch) {
                $relevantContexts[] = "- " . $kb->title . ":\n  " . $kb->content;
            }
        }

        if (!empty($relevantContexts)) {
            $context .= "\n\nINFORMASI PENTING (Gunakan informasi di bawah ini untuk menjawab pertanyaan pengguna jika relevan):\n" . implode("\n\n", $relevantContexts);
        }

        try {
            $response = Http::timeout(15)->post('http://127.0.0.1:11434/api/generate', [
                'model' => 'qwen2.5:3b',
                'prompt' => $context . "\n\nPesan pengguna:\n" . $prompt,
                'stream' => false,
            ]);

            if ($response->successful()) {
                return $response->json('response') ?? 'Silahkan sampaikan keperluan dan keluhannya nggih, supaya kami segera bisa meresponnya.. Matursuwun..';
            }

            Log::error('[Ollama] Failed to generate reply', ['status' => $response->status(), 'body' => $response->body()]);
            return 'Silahkan sampaikan keperluan dan keluhannya nggih, supaya kami segera bisa meresponnya.. Matursuwun..';
        } catch (\Exception $e) {
            Log::error('[Ollama] Connection error', ['message' => $e->getMessage()]);
            return 'Silahkan sampaikan keperluan dan keluhannya nggih, supaya kami segera bisa meresponnya.. Matursuwun..';
        }
    }
}
