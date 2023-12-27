<?php

$url = $_GET['url'] ?? '';
if (empty($url)) {
    header('HTTP/1.1 404 Not Found');
    exit;
}

$parsed = parse_url($url);
$host = $parsed['host'] ?? $parsed['path'];
$scheme = $parsed['scheme'] ?? 'http';
$port = $parsed['port'] ?? '';
$url = $scheme . '://' . $host . ($port ? ':' . $port : '');

if (!filter_var($url, FILTER_VALIDATE_URL)) {
    output_image('');
    exit;
}

$icon = get_favorite_icon($url);
if ($icon) {
    if (strpos($icon, '//') === 0) {
        $href = $scheme . ':' . $icon;
    }

    if (strpos($icon, 'http') === false) {
        if (strpos($icon, '/') !== 0) {
            $icon = '/' . $icon;
        }

        $href = $url . $icon;
    } else {
        $href = $icon;
    }

    output_image($href);
}

output_image('');

function output_image($url)
{
    $ext = pathinfo($url, PATHINFO_EXTENSION);
    if ($ext === 'svg') {
        header('Content-type: image/svg+xml');
    } else {
        header('Content-type: image/x-icon');
    }

    header('Cache-Control: max-age=86400');
    header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));

    if (empty($url)) {
        $content = '';
    } else {
        $content = file_get_contents($url);
    }

    if (empty($content)) {
        $content = file_get_contents(__DIR__ . '/cache/null.ico');
    }

    exit($content);
}

function get_url_content($url, $timeout = 3, $followRedirects = true)
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
    curl_close($ch);

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
