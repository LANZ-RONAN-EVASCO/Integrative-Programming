<?php
$id = (int)($_GET["id"] ?? 0);
if ($id <= 0) { http_response_code(404); exit('User not found.'); }
require __DIR__ . "/../../includes/user-form.php";
