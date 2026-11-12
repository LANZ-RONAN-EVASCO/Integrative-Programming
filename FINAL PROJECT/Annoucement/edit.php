<?php
$id = (int)($_GET["id"] ?? 0);
if ($id <= 0) { http_response_code(404); exit('Announcement not found.'); }
require __DIR__ . "/../../includes/announcement-form.php";
