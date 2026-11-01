<?php
// Lab 2: Decoding JSON
header('Content-Type: text/html; charset=UTF-8');

$json = '{"name":"Jeff","age":21,"email":"Jeff@gmail.com"}';

// Decode as an object (default)
$obj = json_decode($json);
$arr = json_decode($json, true);
$nl = (php_sapi_name() === 'cli') ? PHP_EOL : "<br>\n";

echo "Object: " . $obj->name . $nl;    
echo "Array: " . $arr['email'] . $nl;