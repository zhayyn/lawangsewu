<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class OgImageController extends Controller
{
    public function caseStatisticsPreview(): Response
    {
        // Cache 1 jam di production untuk mengurangi external API call ke QuickChart
        $imageBinaryData = Cache::remember('og_statistics_preview', 3600, function () {
            $caseData           = $this->fetchCaseData();
            $chartConfiguration = $this->buildChartConfiguration($caseData);
            $imageUrl           = $this->buildQuickChartUrl($chartConfiguration);
            return $this->downloadImageFromUrl($imageUrl);
        });

        return response($imageBinaryData)->header('Content-Type', 'image/png');
    }

    private function fetchCaseData(): array
    {
        // Data statistik perkara diambil dari SIPP cache jika tersedia,
        // fallback ke data statis sebagai placeholder
        // TODO: Integrate dengan SIPP query saat data SIPP tersedia real-time
        return [
            'Cerai Gugat' => 820,
            'Cerai Talak' => 210,
            'Waris' => 45,
            'Isbat Nikah' => 120
        ];
    }

    private function buildChartConfiguration(array $caseData): array
    {
        assert(!empty($caseData), 'Case data must not be empty before building chart configuration.');

        $labels = array_keys($caseData);
        $values = array_values($caseData);

        return [
            'type' => 'outlabeledPie',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'data' => $values,
                    'backgroundColor' => ['#0d6b41', '#00B8D9', '#7A5AF8', '#22C55E']
                ]]
            ],
            'options' => [
                'title' => [
                    'display' => true,
                    'text' => 'Komposisi Jenis Perkara Tahun Berjalan',
                    'fontColor' => '#17212f',
                    'fontSize' => 22
                ],
                'plugins' => [
                    'legend' => false,
                    'outlabels' => [
                        'text' => '%l: %v Perkara',
                        'color' => 'white',
                        'stretch' => 25,
                        'font' => [
                            'resizable' => true,
                            'minSize' => 14,
                            'maxSize' => 16
                        ]
                    ]
                ]
            ]
        ];
    }

    private function buildQuickChartUrl(array $chartConfiguration): string
    {
        assert(!empty($chartConfiguration), 'Chart configuration must not be empty.');

        $encodedConfiguration = urlencode(json_encode($chartConfiguration));

        // https://quickchart.io/documentation/
        return "https://quickchart.io/chart?c={$encodedConfiguration}&w=800&h=400&bkg=white&f=png";
    }

    private function downloadImageFromUrl(string $imageUrl): string
    {
        try {
            $response = Http::timeout(10)->get($imageUrl);

            if ($response->successful() && !empty($response->body())) {
                return $response->body();
            }

            // Fallback: return 1x1 transparent PNG jika QuickChart tidak merespons
            \Illuminate\Support\Facades\Log::warning('OgImage: QuickChart API tidak merespons', [
                'url'    => $imageUrl,
                'status' => $response->status(),
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('OgImage: Gagal fetch chart image', [
                'error' => $e->getMessage(),
            ]);
        }

        // Minimal valid PNG (1x1 transparent) sebagai fallback
        return base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
        );
    }
}
// developed by dbprakom™
