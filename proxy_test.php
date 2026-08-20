<?php
$target = $_GET['url'] ?? '';
if ($target) {
    $ch = curl_init($target);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'XCIPTV');
    curl_setopt($ch, CURLOPT_HEADER, true);
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    echo "URL: $target\n";
    echo "HTTP Code: {$info['http_code']}\n";
    echo "Content-Type: {$info['content_type']}\n";
    echo "Redirect URL: {$info['redirect_url']}\n";
    echo "Download Content Length: {$info['download_content_length']}\n";
    echo "Size: " . strlen($response) . " bytes\n\n";
    
    // Show redirect chain
    if ($info['redirect_url']) {
        echo "Final URL: {$info['redirect_url']}\n";
    }
    
    // Show a bit of body
    $body = substr($response, $info['header_size']);
    echo "\nBody (first 300 chars):\n" . substr($body, 0, 300);
} else {
    echo "Usage: ?url=...\n";
}
