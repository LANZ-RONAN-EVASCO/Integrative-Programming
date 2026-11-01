<?php
// Lab 4: Handling JSON Input
header('Content-Type: text/plain');

// Read the raw request body (only has content on a real POST request)
$raw = file_get_contents('php://input');

// If nothing was sent (e.g. run from Code Runner or opened in the browser),
// use sample JSON so the script can still be tested.
if (trim($raw) === '') {
    $raw = '{"username":"admin","password":"1234"}';
    
}

$data = json_decode($raw, true);

if ($data === null) {
    http_response_code(400);
    echo "Invalid JSON.";
    exit;
}

echo "Username: " . ($data['username'] ?? '') . "\n";
echo "Password: " . ($data['password'] ?? '') . "\n";

/* To test real input, send a POST request:
   Postman: POST to http://localhost/lab4_input.php, Body > raw > JSON:
   {"username":"admin","password":"1234"}

   Or with curl:
   curl -X POST http://localhost/lab4_input.php \
        -H "Content-Type: application/json" \
        -d '{"username":"admin","password":"1234"}'
*/
