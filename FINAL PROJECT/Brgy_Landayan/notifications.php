<?php
//22
//23
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function alerts_json(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

//24
$now = time();
$idle = $now - (int)($_SESSION['auth_last_activity'] ?? $now);
$age = $now - (int)($_SESSION['auth_started_at'] ?? $now);
if (!current_user() || $idle > 30 * 60 || $age > 8 * 60 * 60) {
    session_write_close();
    alerts_json(['ok' => false, 'reason' => 'auth'], 401);
}

$user = auth_refresh_user();                      //25
if (!$user || !empty($user['must_change_password'])) {
    session_write_close();
    alerts_json(['ok' => false, 'reason' => 'auth'], 403);
}
$canReports = has_permission('reports.view');
$canMessages = has_permission('messages.view');
session_write_close();                            //26

$out = ['ok' => true, 'uid' => (int)$user['id']];

try {
    if ($canReports) {
        $latest = (int)db()->query('SELECT COALESCE(MAX(id), 0) FROM reports')->fetchColumn();
        $block = ['latest' => $latest, 'count' => 0, 'items' => []];
        if (isset($_GET['r']) && ctype_digit((string)$_GET['r']) && (int)$_GET['r'] < $latest) {
            $since = (int)$_GET['r'];
            $stmt = db()->prepare('SELECT COUNT(*) FROM reports WHERE id > ?');
            $stmt->execute([$since]);
            $block['count'] = (int)$stmt->fetchColumn();
            $stmt = db()->prepare('SELECT id, tracking_code, category FROM reports WHERE id > ? ORDER BY id DESC LIMIT 5');
            $stmt->execute([$since]);
            foreach ($stmt->fetchAll() as $r) {
                $block['items'][] = ['id' => (int)$r['id'], 'code' => $r['tracking_code'] ?: ('#' . $r['id']), 'category' => (string)$r['category']];
            }
        }
        $out['reports'] = $block;
    }

    if ($canMessages) {
        $latest = (int)db()->query('SELECT COALESCE(MAX(id), 0) FROM contact_messages')->fetchColumn();
        $block = ['latest' => $latest, 'count' => 0, 'items' => []];
        if (isset($_GET['m']) && ctype_digit((string)$_GET['m']) && (int)$_GET['m'] < $latest) {
            $since = (int)$_GET['m'];
            $stmt = db()->prepare('SELECT COUNT(*) FROM contact_messages WHERE id > ?');
            $stmt->execute([$since]);
            $block['count'] = (int)$stmt->fetchColumn();
            $stmt = db()->prepare('SELECT id, subject FROM contact_messages WHERE id > ? ORDER BY id DESC LIMIT 5');
            $stmt->execute([$since]);
            foreach ($stmt->fetchAll() as $m) {
                $block['items'][] = ['id' => (int)$m['id'], 'subject' => mb_substr((string)$m['subject'], 0, 60)];
            }
        }
        $out['messages'] = $block;
    }
} catch (Throwable $ex) {
    error_log('Admin alerts feed failed: ' . $ex->getMessage());
    alerts_json(['ok' => false, 'reason' => 'error'], 500);
}

alerts_json($out);
