<?php
// STEP 3: receive the data, process it, respond in JSON

header('Content-Type: application/json');

// Read the raw JSON sent by fetch()
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

// Process: get the name and clean it
$name = trim($data['name'] ?? '');

// Validate
if ($name === '') {
    http_response_code(400);
    echo json_encode([
        "status"  => "error",
        "message" => "Name is required."
    ]);
    exit;
}

// Respond
echo json_encode([
    "status"  => "success",
    "message" => "Welcome, " . htmlspecialchars($name) . "!"
]);