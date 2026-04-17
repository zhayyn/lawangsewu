<?php
/* developed by dubes favour-it */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

function apr_int($value, int $default, int $min, int $max): int
{
    $number = filter_var($value, FILTER_VALIDATE_INT);
    if ($number === false) {
        return $default;
    }
    if ($number < $min) {
        return $min;
    }
    if ($number > $max) {
        return $max;
    }
    return $number;
}

function apr_http_request(string $url, int $timeoutMs, string $method = 'GET', array $headers = [], array $form = []): array
{
    $status = 502;
    $body = '';
    $method = strtoupper(trim($method));
    $baseHeaders = [
        'Accept: application/json, application/rss+xml, application/xml, text/xml;q=0.9, text/html;q=0.8, */*;q=0.7',
        'User-Agent: Mozilla/5.0 (Lawangsewu-RSS/2.0)',
    ];
    $mergedHeaders = array_values(array_unique(array_merge($baseHeaders, $headers)));

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT_MS => $timeoutMs,
            CURLOPT_TIMEOUT_MS => $timeoutMs,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => $mergedHeaders,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $form);
        }

        $result = curl_exec($ch);
        if (is_string($result)) {
            $body = $result;
        }
        $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        if (is_int($code) && $code > 0) {
            $status = $code;
        }
        curl_close($ch);
    } else {
        $headerLines = implode("\r\n", array_map(static function ($header): string {
            return preg_replace('/\r|\n/', '', (string) $header);
        }, $mergedHeaders));

        $options = [
            'http' => [
                'method' => $method,
                'header' => $headerLines . "\r\n",
                'timeout' => max(3, (int) ceil($timeoutMs / 1000)),
                'ignore_errors' => true,
            ],
        ];

        if ($method === 'POST') {
            $options['http']['header'] .= "Content-Type: application/x-www-form-urlencoded\r\n";
            $options['http']['content'] = http_build_query($form);
        }

        $context = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);
        if (is_string($result)) {
            $body = $result;
        }

        if (is_array($http_response_header ?? null)) {
            foreach ($http_response_header as $line) {
                if (preg_match('/^HTTP\/\S+\s+(\d{3})\b/', $line, $m)) {
                    $status = (int) $m[1];
                    break;
                }
            }
        }
    }

    return ['status' => $status, 'body' => $body];
}

function apr_parse_id_datetime(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    $months = [
        'januari' => 1,
        'februari' => 2,
        'maret' => 3,
        'april' => 4,
        'mei' => 5,
        'juni' => 6,
        'juli' => 7,
        'agustus' => 8,
        'september' => 9,
        'oktober' => 10,
        'november' => 11,
        'desember' => 12,
    ];

    if (!preg_match('/(\d{1,2})\s+([[:alpha:]]+)\s+(\d{4})(?:\s+(\d{2}):(\d{2}))?/u', $value, $m)) {
        return '';
    }

    $day = (int) $m[1];
    $monthName = strtolower($m[2]);
    $year = (int) $m[3];
    $hour = isset($m[4]) ? (int) $m[4] : 0;
    $minute = isset($m[5]) ? (int) $m[5] : 0;
    $month = $months[$monthName] ?? 0;
    if ($month <= 0) {
        return '';
    }

    $tz = new DateTimeZone('Asia/Jakarta');
    $dt = DateTimeImmutable::createFromFormat('Y-n-j H:i', sprintf('%04d-%d-%d %02d:%02d', $year, $month, $day, $hour, $minute), $tz);
    return $dt ? $dt->format(DateTimeInterface::ATOM) : '';
}

function apr_parse_rss_items(string $xmlString, int $limit, string $sourceCode): array
{
    if (!function_exists('simplexml_load_string')) {
        return [];
    }

    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($xmlString, 'SimpleXMLElement', LIBXML_NOCDATA);
    if ($xml === false || !isset($xml->channel->item)) {
        return [];
    }

    $items = [];
    foreach ($xml->channel->item as $item) {
        $title = trim((string) ($item->title ?? ''));
        $link = trim((string) ($item->link ?? ''));
        $date = trim((string) ($item->pubDate ?? ''));
        if ($title === '' || $link === '') {
            continue;
        }

        $thumb = '';
        $enclosure = $item->enclosure ?? null;
        if ($enclosure && isset($enclosure['url'])) {
            $thumb = trim((string) $enclosure['url']);
        }

        $items[] = [
            'source' => $sourceCode,
            'title' => $title,
            'url' => $link,
            'date' => $date,
            'thumb' => $thumb,
        ];

        if (count($items) >= $limit) {
            break;
        }
    }

    return $items;
}

function apr_payload_single(string $source, string $sourceName, string $sourceUrl, array $items, int $limit): array
{
    return [
        'ok' => true,
        'source' => $source,
        'limit' => $limit,
        'sourceName' => $sourceName,
        'sourceUrl' => $sourceUrl,
        'fetchedAt' => gmdate('c'),
        'total' => count($items),
        'items' => array_values($items),
    ];
}

function apr_fetch_ma(int $limit, int $timeoutMs): array
{
    $url = 'https://www.mahkamahagung.go.id/id/pengumuman';
    $response = apr_http_request($url, $timeoutMs, 'POST', [], [
        'cat_id' => '2',
        'page' => '1',
        'lang' => 'id',
    ]);

    $json = json_decode((string) ($response['body'] ?? ''), true);
    if (!is_array($json) || ($json['stat'] ?? '') !== 'OK' || !isset($json['data']['rows']) || !is_array($json['data']['rows'])) {
        throw new RuntimeException('Respons Mahkamah Agung tidak valid.');
    }

    $items = [];
    foreach ($json['data']['rows'] as $row) {
        $title = trim((string) ($row['title'] ?? ''));
        $urlItem = trim((string) ($row['url'] ?? ''));
        if ($title === '' || $urlItem === '') {
            continue;
        }

        $thumb = trim((string) ($row['thumb'] ?? ''));
        if ($thumb !== '' && strpos($thumb, 'http') !== 0) {
            $thumb = 'https://www.mahkamahagung.go.id/media/' . ltrim($thumb, '/');
        }

        $items[] = [
            'source' => 'ma',
            'title' => $title,
            'url' => $urlItem,
            'date' => apr_parse_id_datetime((string) ($row['pt'] ?? '')),
            'thumb' => $thumb,
        ];

        if (count($items) >= $limit) {
            break;
        }
    }

    return apr_payload_single('ma', 'Mahkamah Agung RI', $url, $items, $limit);
}

function apr_fetch_badilag(int $limit, int $timeoutMs): array
{
    $url = 'https://badilag.mahkamahagung.go.id/pengumuman-elektronik?format=feed&type=rss';
    $response = apr_http_request($url, $timeoutMs);
    $items = apr_parse_rss_items((string) ($response['body'] ?? ''), $limit, 'badilag');
    if ($items === []) {
        throw new RuntimeException('Feed Badilag tidak dapat diparsing.');
    }

    return apr_payload_single('badilag', 'Badilag MA RI', $url, $items, $limit);
}

function apr_fetch_pta(int $limit, int $timeoutMs): array
{
    $url = 'https://pta-semarang.go.id/feed';
    $response = apr_http_request($url, $timeoutMs);
    $items = apr_parse_rss_items((string) ($response['body'] ?? ''), $limit, 'pta');
    if ($items === []) {
        throw new RuntimeException('Feed PTA Semarang tidak dapat diparsing.');
    }

    return apr_payload_single('pta', 'PTA Semarang', $url, $items, $limit);
}

$source = strtolower(trim((string) ($_GET['source'] ?? 'all')));
if (!in_array($source, ['all', 'ma', 'badilag', 'pta'], true)) {
    $source = 'all';
}

$limit = apr_int($_GET['limit'] ?? 10, 10, 1, 30);
$timeoutMs = apr_int($_GET['timeout_ms'] ?? 12000, 12000, 3000, 20000);

try {
    if ($source === 'ma') {
        echo json_encode(apr_fetch_ma($limit, $timeoutMs), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($source === 'badilag') {
        echo json_encode(apr_fetch_badilag($limit, $timeoutMs), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($source === 'pta') {
        echo json_encode(apr_fetch_pta($limit, $timeoutMs), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    $ma = apr_fetch_ma($limit, $timeoutMs);
    $badilag = apr_fetch_badilag($limit, $timeoutMs);

    echo json_encode([
        'ok' => true,
        'source' => 'all',
        'limit' => $limit,
        'fetchedAt' => gmdate('c'),
        'total' => (int) ($ma['total'] ?? 0) + (int) ($badilag['total'] ?? 0),
        'bySource' => [
            'ma' => $ma,
            'badilag' => $badilag,
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(502);
    echo json_encode([
        'ok' => false,
        'message' => 'Proxy gagal mengambil data pengumuman.',
        'source' => $source,
        'detail' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/* developed by dubes favour-it */
