<?php
// File: sitemap-parser.php

// Function to fetch content using cURL
function fetchUrlContent($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    curl_setopt($ch, CURLOPT_HEADER, true); // Include headers in the output
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        error_log("cURL Error: " . curl_error($ch));
        curl_close($ch);
        return null;
    }
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    curl_close($ch);

    error_log("Response Headers: " . $headers); // Log headers for debugging
    return $body;
}

// Function to parse sitemap.xml and extract URLs
function parseSitemap($sitemapUrl) {
    $sitemapContent = fetchUrlContent($sitemapUrl);
    if ($sitemapContent === null) {
        die("Error: Unable to fetch sitemap.xml from $sitemapUrl");
    }

    $xml = simplexml_load_string($sitemapContent);
    if ($xml === false) {
        die("Error: Unable to parse sitemap.xml");
    }

    $urls = [];
    foreach ($xml->url as $url) {
        $urls[] = (string)$url->loc;
    }

    return $urls;
}

// Function to extract content from a URL
function extractContentFromUrl($url) {
    // Skip disallowed paths
    $disallowedPaths = [
        '/admin/', '/includes/', '/config/', '/vendor/', '/temp/', '/cache/',
        '/cgi-bin/', '/backup/'
    ];
    foreach ($disallowedPaths as $path) {
        if (strpos($url, $path) !== false) {
            error_log("Skipping disallowed URL: $url");
            return null;
        }
    }

    $html = fetchUrlContent($url);
    if ($html === null) {
        return null;
    }

    $dom = new DOMDocument();
    @$dom->loadHTML($html); // Suppress warnings for invalid HTML

    // Remove scripts, styles, and other unwanted tags
    foreach (['script', 'style', 'nav', 'footer', 'header'] as $tag) {
        $elements = $dom->getElementsByTagName($tag);
        foreach ($elements as $element) {
            $element->parentNode->removeChild($element);
        }
    }

    // Extract text content
    $content = $dom->textContent;
    $content = preg_replace('/\s+/', ' ', $content); // Normalize whitespace
    $content = trim($content);

    return $content;
}

// Function to save data to a .json file
function saveDataToJson($data, $filename) {
    $json = json_encode($data, JSON_PRETTY_PRINT);
    if (file_put_contents($filename, $json) === false) {
        die("Error: Unable to save data to $filename");
    }
}

// Main script
$sitemapUrl = 'https://weetechnologysolution.co/sitemap.xml'; // Replace with your sitemap URL
$jsonFile = 'website-content.json';

// Step 1: Parse sitemap.xml
$urls = parseSitemap($sitemapUrl);

// Step 2: Extract content from each URL
$websiteData = [];
foreach ($urls as $url) {
    $content = extractContentFromUrl($url);
    if ($content) {
        $websiteData[] = [
            'url' => $url,
            'content' => $content
        ];
    }
    sleep(10); // Respect the crawl-delay directive (10 seconds)
}

// Step 3: Save extracted content to a .json file
saveDataToJson($websiteData, $jsonFile);

echo "Content extraction complete. Data saved to $jsonFile.";
?>