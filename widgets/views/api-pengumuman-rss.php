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

function apr_http_get(string $url, int $timeoutMs): array
{
    $status = 502;
    $body = '';

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT_MS => $timeoutMs,
            CURLOPT_TIMEOUT_MS => $timeoutMs,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_HTTPHEADER => [
                'Accept: application/rss+xml, application/xml, text/xml;q=0.9, */*;q=0.8',
                'User-Agent: Mozilla/5.0 (Lawangsewu-RSS/1.0)',
            ],
        ]);
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
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Accept: application/rss+xml, application/xml, text/xml;q=0.9, */*;q=0.8\r\n"
                    . "User-Agent: Mozilla/5.0 (Lawangsewu-RSS/1.0)\r\n",
                'timeout' => max(3, (int)ceil($timeoutMs / 1000)),
                'ignore_errors' => true,
            ],
        ]);
        $result = @file_get_contents($url, false, $context);
        if (is_string($result)) {
            $body = $result;
        }
        if (is_array($http_response_header ?? null)) {
            foreach ($http_response_header as $line) {
                if (preg_match('/^HTTP\/\S+\s+(\d{3})\b/', $line, $m)) {
                    $status = (int)$m[1];
                    break;
                }
            }
        }
    }

    return ['status' => $status, 'body' => $body];
}

function apr_parse_rss_items(string $xmlString, int $limit): array
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
        $title = trim((string)($item->title ?? ''));
        $link = trim((string)($item->link ?? ''));
        $date = trim((string)($item->pubDate ?? ''));
        if ($title === '' || $link === '') {
            continue;
        }

        $thumb = '';
        $enclosure = $item->enclosure ?? null;
        if ($enclosure && isset($enclosure['url'])) {
            $thumb = trim((string)$enclosure['url']);
        }

        $items[] = [
            'source' => 'pta',
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

$source = strtolower(trim((string)($_GET['source'] ?? 'all')));
if (!in_array($source, ['all', 'ma', 'badilag', 'pta'], true)) {
    $source = 'all';
}

$limit = apr_int($_GET['limit'] ?? 10, 10, 1, 30);
$timeoutMs = apr_int($_GET['timeout_ms'] ?? 12000, 12000, 3000, 20000);

if ($source === 'pta') {
    $ptaFeedUrl = 'https://pta-semarang.go.id/feed';
    $ptaFetch = apr_http_get($ptaFeedUrl, $timeoutMs);
    $ptaItems = apr_parse_rss_items((string)($ptaFetch['body'] ?? ''), $limit);

    if (!is_array($ptaItems) || count($ptaItems) === 0) {
        http_response_code(502);
        echo json_encode([
            'ok' => false,
            'source' => 'pta',
            'sourceName' => 'PTA Semarang',
            'sourceUrl' => $ptaFeedUrl,
            'message' => 'Gagal mengambil feed PTA Semarang.',
            'status' => (int)($ptaFetch['status'] ?? 502),
            'fetchedAt' => gmdate('c'),
            'items' => [],
            'total' => 0,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'source' => 'pta',
        'limit' => $limit,
        'sourceName' => 'PTA Semarang',
        'sourceUrl' => $ptaFeedUrl,
        'fetchedAt' => gmdate('c'),
        'total' => count($ptaItems),
        'items' => $ptaItems,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$primaryBase = rtrim((string)(getenv('LW_NODE_GATEWAY_BASE') ?: 'http://127.0.0.1:8787'), '/');
$fallbackBase = rtrim((string)(getenv('LW_NODE_GATEWAY_FALLBACK_BASE') ?: 'http://127.0.0.1:8788'), '/');

$bases = [$primaryBase];
if ($fallbackBase !== '' && $fallbackBase !== $primaryBase) {
    $bases[] = $fallbackBase;
}

$httpCode = 502;
$responseBody = null;

foreach ($bases as $nodeBase) {
    $target = $nodeBase . '/api/v1/pengumuman?source=' . rawurlencode($source) . '&limit=' . $limit;
    $currentCode = 502;
    $currentBody = null;

    if (function_exists('curl_init')) {
        $ch = curl_init($target);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT_MS => $timeoutMs,
            CURLOPT_TIMEOUT_MS => $timeoutMs,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $currentBody = curl_exec($ch);
        if ($currentBody !== false) {
            $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            if (is_int($status) && $status > 0) {
                $currentCode = $status;
            }
        }
        curl_close($ch);
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Accept: application/json\r\n",
                'timeout' => $timeoutMs / 1000,
                'ignore_errors' => true,
            ],
        ]);
        $currentBody = @file_get_contents($target, false, $context);
        if (is_array($http_response_header ?? null)) {
            foreach ($http_response_header as $line) {
                if (preg_match('/^HTTP\/\S+\s+(\d{3})\b/', $line, $m)) {
                    $currentCode = (int)$m[1];
                    break;
                }
            }
        }
    }

    if (is_string($currentBody) && trim($currentBody) !== '' && $currentCode >= 200 && $currentCode < 500) {
        $httpCode = $currentCode;
        $responseBody = $currentBody;
        break;
    }

    $httpCode = $currentCode;
    $responseBody = $currentBody;
}

if (!is_string($responseBody) || trim($responseBody) === '') {
    http_response_code(502);
    echo json_encode([
        'ok' => false,
        'message' => 'Proxy gagal mengambil data pengumuman.',
        'source' => $source,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

http_response_code($httpCode);
echo $responseBody;

/* developed by dubes favour-it */
