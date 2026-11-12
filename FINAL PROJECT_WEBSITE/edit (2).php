<?php
$id = (int)($_GET["id"] ?? 0);
if ($id <= 0) { http_response_code(404); exit('Event not found.'); }
require __DIR__ . "/../../includes/event-form.php";
