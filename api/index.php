<?php

$url = $_GET['url'] ?? '';
if (empty($url)) {
    header('HTTP/1.1 404 Not Found');
    exit;
}

define('TMP_PATH', getenv('VERCEL') ? '/tmp' : __DIR__ . '/cache');

$parsed = parse_url($url);
$host = $parsed['host'] ?? $parsed['path'];
$scheme = $parsed['scheme'] ?? 'http';
$port = $parsed['port'] ?? '';
$url = $scheme . '://' . $host . ($port ? ':' . $port : '');

if (!filter_var($url, FILTER_VALIDATE_URL)) {
    output_default_image();
}

check_cache($host);

$icon = get_favorite_icon($url);
if (empty($icon)) {
    $defaultIcon = $url . '/favicon.ico';
    $exist = get_url_content($defaultIcon, 5, true, true);
    if ($exist) {
        output_image($defaultIcon, $host);
    }

    output_default_image();
}

$iconUrl = $icon;
if (strpos($icon, '//') === 0) {
    $iconUrl = $scheme . ':' . $icon;
}

if (strpos($icon, 'http') === false) {
    if (strpos($icon, '/') !== 0) {
        $icon = '/' . $icon;
    }

    $iconUrl = $url . $icon;
}

output_image($iconUrl, $host);

function output_default_image()
{
    header('Content-type: image/x-icon');
    $content = file_get_contents(__DIR__ . '/cache/default.ico');
    exit($content);
}

function check_cache($host)
{
    $refresh = $_GET['refresh'] ?? false;
    if ($refresh) {
        return false;
    }

    $cacheFile = TMP_PATH . '/' . md5($host);
    if (file_exists($cacheFile)) {
        $content = file_get_contents($cacheFile);
        if (!empty($content)) {
            $fileType = finfo_buffer(finfo_open(FILEINFO_MIME_TYPE), $content);
            if ($fileType === 'image/svg+xml') {
                header('Content-type: image/svg+xml');
            } else {
                header('Content-type: image/x-icon');
            }

            header('X-Icon-Cache: Hit');
            exit($content);
        }
    }

    return false;
}

function output_image($url, $host)
{
    $ext = pathinfo($url, PATHINFO_EXTENSION);
    if ($ext === 'svg') {
        header('Content-type: image/svg+xml');
    } else {
        header('Content-type: image/x-icon');
    }

    $opts = [
        'http' => [
            'method' => 'GET',
            'header' => 'User-Agent: ' . $_SERVER['HTTP_USER_AGENT']
        ]
    ];
    $context = stream_context_create($opts);
    $content = @file_get_contents($url, false, $context);
    $statusLine = $http_response_header[0];
    preg_match('{HTTP\/\S*\s(\d{3})}', $statusLine, $match);
    $statusCode = $match[1] ?? null;
    if (empty($content) || (!empty($statusCode) && !in_array($statusCode, [200, 301, 302]))) {
        output_default_image();
    }

    file_put_contents(TMP_PATH . '/' . md5($host), $content);
    exit($content);
}

function get_url_content($url, $timeout = 3, $followRedirects = true, $checkExists = false)
{
    $ch = curl_init();

    $userAgent = sprintf('%s (Powered by %s)', $_SERVER['HTTP_HOST'], 'sy-records/GetFavicon');

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $followRedirects);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);

    $output = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($checkExists) {
        return $httpCode == 200;
    }

    return $output;
}

function get_favorite_icon($url)
{
    $content = get_url_content($url);
    if (empty($content)) {
        return '';
    }

    $dom = new DOMDocument();
    @$dom->loadHTML($content);

    $xpath = new DOMXPath($dom);
    $nodes = $xpath->query('//link[@rel="shortcut icon"]');

    if ($nodes->length > 0) {
        $node = $nodes->item(0);
        return $node->getAttribute('href');
    }

    $nodes = $xpath->query('//link[@rel="icon"]');

    if ($nodes->length > 0) {
        $node = $nodes->item(0);
        return $node->getAttribute('href');
    }

    return '';
}
