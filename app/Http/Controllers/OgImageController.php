<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class OgImageController extends Controller
{
    public function caseStatisticsPreview(): Response
    {
        // TODO: Enable caching below when integrating into production to reduce API calls and save bandwidth
        // $imageBinaryData = Cache::remember('og_statistics_preview', 3600, function () {
        //     $caseData = $this->fetchMockCaseData();
        //     $chartConfiguration = $this->buildChartConfiguration($caseData);
        //     $imageUrl = $this->buildQuickChartUrl($chartConfiguration);
        //     return $this->downloadImageFromUrl($imageUrl);
        // });

        $caseData = $this->fetchMockCaseData();
        $chartConfiguration = $this->buildChartConfiguration($caseData);
        $imageUrl = $this->buildQuickChartUrl($chartConfiguration);
        $imageBinaryData = $this->downloadImageFromUrl($imageUrl);

        return response($imageBinaryData)->header('Content-Type', 'image/png');
    }

    private function fetchMockCaseData(): array
    {
        // TODO: Replace with real Eloquent/DB query when integrating into production
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
        assert(filter_var($imageUrl, FILTER_VALIDATE_URL) !== false, 'Invalid image URL provided for download.');

        $response = Http::timeout(10)->get($imageUrl);

        assert($response->successful(), 'Failed to download image from QuickChart API.');

        $imageBinaryData = $response->body();
        assert(!empty($imageBinaryData), 'Downloaded image data must not be empty.');

        return $imageBinaryData;
    }
}
// developed by zhayyn™
