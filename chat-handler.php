<?php
// File: chat-handler.php

// session_start();

// Hardcoded Gemini API Key
$apiKey = "AIzaSyBpBsx5k6cjH8Mi4XFKOvX9mFxhq8kUtzM"; // Replace with your API key

// Debug information
if (empty($apiKey)) {
    die("Error: API key is not configured properly.");
}

// Load website content from .json file
$jsonFile = 'website-content.json';
if (!file_exists($jsonFile)) {
    die("Error: $jsonFile not found. Run sitemap-parser.php first.");
}
$websiteData = json_decode(file_get_contents($jsonFile), true);

/**
 * Sends a message to the Gemini API and retrieves a response.
 *
 * @param string $message The user's input message to send to the Gemini API.
 * @return string|false The API response or false if the request fails.
 */
function sendToGemini($message) {
    global $apiKey;
    
    if (empty($apiKey)) {
        return "Error: API key is not configured properly.";
    }

    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . urlencode($apiKey);
    
    // Creative and simple prompt
    $prompt = "You are a friendly and helpful assistant. Respond to the user's question in a simple, conversational, and easy-to-understand way. Avoid long paragraphs, bullet points, or overly formal language. Keep your answers short and to the point. If the question is about Wee Technology Solutions, provide a brief and clear explanation without too much detail. Here's the user's question: " . $message;

    $data = [
        'contents' => [
            [
                'parts' => [
                    [
                        'text' => $prompt
                    ]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.7, // Adjust for creativity (0 = strict, 1 = creative)
            'topK' => 40,
            'topP' => 0.95,
            'maxOutputTokens' => 150 // Limit response length for simplicity
        ]
    ];

    $options = [
        'http' => [
            'header' => [
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            'method' => 'POST',
            'content' => json_encode($data),
            'ignore_errors' => true
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ];

    $context = stream_context_create($options);
    
    try {
        $response = file_get_contents($url, false, $context);
        
        if ($response === false) {
            $error = error_get_last();
            error_log("Gemini API Error: " . print_r($error, true));
            return "Sorry, there was an error communicating with the AI service.";
        }
        
        $responseHeaders = $http_response_header ?? [];
        $statusLine = $responseHeaders[0] ?? '';
        if (strpos($statusLine, '200') === false) {
            error_log("Gemini API Error: " . $statusLine . "\nResponse: " . $response);
            $errorResponse = json_decode($response, true);
            if (isset($errorResponse['error']['message'])) {
                return "Sorry, the AI service returned an error: " . $errorResponse['error']['message'];
            }
            return "Sorry, the AI service returned an error. Please try again later.";
        }

        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON decode error: " . json_last_error_msg());
            return "Sorry, there was an error processing the AI response.";
        }

        // Debug: Log the full API response
        error_log("Gemini API Response: " . print_r($result, true));

        return $result['candidates'][0]['content']['parts'][0]['text'] 
            ?? "I apologize, but I couldn't generate a proper response.";
            
    } catch (Exception $e) {
        error_log("Exception in sendToGemini: " . $e->getMessage());
        return "Sorry, an unexpected error occurred.";
    }
}

// Function to search website content for relevant information
function searchWebsiteContent($query, $websiteData) {
    $results = [];
    foreach ($websiteData as $page) {
        if (stripos($page['content'], $query) !== false) {
            $results[] = [
                'url' => $page['url'],
                'content' => $page['content']
            ];
        }
    }
    return $results;
}

// Initialize or get session
if (!isset($_SESSION['chat_history'])) {
    $_SESSION['chat_history'] = [];
}

// Handle incoming requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestData = json_decode(file_get_contents('php://input'), true);
    $userMessage = trim($requestData['message'] ?? '');
    
    if (!empty($userMessage)) {
        // Step 1: Search website content for relevant information
        $searchResults = searchWebsiteContent($userMessage, $websiteData);
        
        // Step 2: Send user message to Gemini API
        $response = sendToGemini($userMessage);
        
        // Step 3: Append relevant website content to the response
        if (!empty($searchResults)) {
            $response .= "\n\nHere's some relevant information from our website:\n";
            foreach ($searchResults as $result) {
                $response .= "- " . $result['url'] . "\n";
            }
        }
        
        // Add to chat history
        $_SESSION['chat_history'][] = [
            'user' => $userMessage,
            'assistant' => $response
        ];
        
        // Return JSON response for AJAX request
        header('Content-Type: application/json');
        echo json_encode(['response' => $response]);
        exit;
    }
}
?>