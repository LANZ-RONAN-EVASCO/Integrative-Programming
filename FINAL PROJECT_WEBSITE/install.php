<?php
//201
//202
//203
//204
//205

session_start();

$root = __DIR__;
$configFile = $root . '/config/config.local.php';
$lockFile = $root . '/config/installed.lock';
$sqlFile = $root . '/database-hosting.sql';

function h($text) { return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8'); }

function page($title, $body, $status = 200) {
    http_response_code($status);
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>' . h($title) . '</title><style>'
        . 'body{margin:0;background:#f3f6fb;font-family:system-ui,Segoe UI,Arial,sans-serif;color:#15233b;line-height:1.5}'
        . '.box{max-width:640px;margin:2rem auto;background:#fff;border-radius:16px;padding:1.6rem 1.8rem;box-shadow:0 8px 30px rgba(20,40,90,.1)}'
        . 'h1{margin:.2rem 0 .3rem;font-size:1.6rem}h2{font-size:1.05rem;margin:1.5rem 0 .2rem}'
        . 'label{display:block;margin:.9rem 0 .25rem;font-weight:600;font-size:.92rem}'
        . 'input{width:100%;box-sizing:border-box;padding:.7rem .85rem;border:1px solid #c9d3e3;border-radius:10px;font:inherit}'
        . '.row{display:grid;grid-template-columns:1fr 1fr;gap:.8rem}@media(max-width:520px){.row{grid-template-columns:1fr}}'
        . 'button,.btn{display:inline-block;margin-top:1.4rem;padding:.8rem 1.4rem;border:0;border-radius:999px;background:#2457e6;color:#fff;font:inherit;font-weight:700;cursor:pointer;text-decoration:none}'
        . '.muted{color:#5b6b85;font-size:.9rem}.err{background:#fde8e8;color:#a31616;border-radius:10px;padding:.7rem 1rem;margin:.6rem 0}'
        . '.ok{color:#117a3d}.bad{color:#a31616}.warn{color:#9a6200}ul.checks{list-style:none;padding:0;margin:.4rem 0}'
        . 'code,pre{background:#eef2f9;border-radius:6px;padding:.1rem .4rem}pre{padding:.8rem;overflow:auto}'
        . '</style></head><body><div class="box">' . $body . '</div></body></html>';
    exit;
}

//206
if (is_file($lockFile) || is_file($configFile)) {
    page('Already installed',
        '<h1>Already installed</h1><p>This website has already been set up, so the installer is turned off.</p>'
        . '<p class="muted">For safety, delete <code>install.php</code> from your hosting if it is still there.</p>'
        . '<p><a class="btn" href="index.php">Open the website</a></p>', 403);
}

//207
$checks = [
    ['PHP 8.1 or newer (this server: ' . PHP_VERSION . ')', PHP_VERSION_ID >= 80100, true],
    ['pdo_mysql extension', extension_loaded('pdo_mysql'), true],
    ['mbstring extension', extension_loaded('mbstring'), true],
    ['database-hosting.sql found', is_file($sqlFile), true],
    ['fileinfo extension (needed for image uploads)', extension_loaded('fileinfo'), false],
    ['uploads folder is writable', is_dir($root . '/uploads') && is_writable($root . '/uploads'), false],
    ['config folder is writable', is_writable($root . '/config'), false],
];
$blocked = false;
foreach ($checks as $c) { if ($c[2] && !$c[1]) $blocked = true; }

//208
function split_sql($sql) {
    $statements = []; $buffer = ''; $quote = ''; $len = strlen($sql);
    for ($i = 0; $i < $len; $i++) {
        $c = $sql[$i];
        $next = ($i + 1 < $len) ? $sql[$i + 1] : '';
        if ($quote === '') {
            if ($c === '-' && $next === '-') {            //209
                $nl = strpos($sql, "\n", $i);
                if ($nl === false) break;
                $i = $nl; $buffer .= "\n"; continue;
            }
            if ($c === "'" || $c === '"' || $c === '`') { $quote = $c; $buffer .= $c; continue; }
            if ($c === ';') {
                $s = trim($buffer);
                if ($s !== '') $statements[] = $s;
                $buffer = ''; continue;
            }
            $buffer .= $c;
        } else {
            $buffer .= $c;
            if ($c === '\\' && $quote !== '`') { $buffer .= $next; $i++; continue; }   //210
            if ($c === $quote) {
                if ($next === $quote) { $buffer .= $next; $i++; } else { $quote = ''; }
            }
        }
    }
    $s = trim($buffer);
    if ($s !== '') $statements[] = $s;
    return $statements;
}

//211
$_SESSION['install_csrf'] = $_SESSION['install_csrf'] ?? bin2hex(random_bytes(16));
$f = ['db_host' => '', 'db_port' => '3306', 'db_name' => '', 'db_user' => '',
      'admin_name' => '', 'admin_user' => 'admin', 'admin_email' => ''];
$errors = [];

if (!$blocked && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['install_csrf'], (string)($_POST['csrf'] ?? ''))) {
        $errors[] = 'The page expired. Reload it and try again.';
    }
    foreach ($f as $k => $v) $f[$k] = trim((string)($_POST[$k] ?? ''));
    $dbPass = (string)($_POST['db_pass'] ?? '');
    $pass = (string)($_POST['admin_pass'] ?? '');
    $pass2 = (string)($_POST['admin_pass2'] ?? '');

    if ($f['db_host'] === '' || $f['db_name'] === '' || $f['db_user'] === '') $errors[] = 'Fill in the database host, name and username.';
    if ($f['admin_name'] === '' || mb_strlen($f['admin_name']) > 100) $errors[] = 'Enter the administrator\'s full name.';
    if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $f['admin_user'])) $errors[] = 'The username must be 3 to 50 characters: letters, numbers, dot, dash or underscore.';
    if (!filter_var($f['admin_email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (mb_strlen($pass) < 10) $errors[] = 'The password must be at least 10 characters.';
    if ($pass !== $pass2) $errors[] = 'The two passwords do not match.';

    if (!$errors) {
        try {
            $pdo = new PDO('mysql:host=' . $f['db_host'] . ';port=' . (int)$f['db_port'] . ';dbname=' . $f['db_name'] . ';charset=utf8mb4',
                $f['db_user'], $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        } catch (PDOException $e) {
            $code = (int)($e->errorInfo[1] ?? $e->getCode());
            if ($code === 1045) $errors[] = 'The database username or password is wrong.';
            elseif ($code === 1049) $errors[] = 'That database name does not exist. Copy it exactly from your hosting control panel.';
            elseif ($code === 2002 || $code === 2006) $errors[] = 'Could not reach the database host. On shared hosting it is NOT "localhost"; copy the host name from the control panel (it looks like sql123.infinityfree.com).';
            else $errors[] = 'Could not connect to the database: ' . $e->getMessage();
        }
    }

    if (!$errors) {
        try {
            //212
            $hasUsers = $pdo->query("SHOW TABLES LIKE 'users'")->fetchColumn() !== false;
            if (!$hasUsers) {
                foreach (split_sql((string)file_get_contents($sqlFile)) as $statement) {
                    $pdo->exec($statement);
                }
            }
            //213
            $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
            $stmt->execute([$f['admin_user'], $f['admin_email']]);
            if ($stmt->fetch()) {
                $errors[] = 'That username or email already exists in this database. Choose different ones, or empty the database in phpMyAdmin and try again.';
            } else {
                $pdo->prepare("INSERT INTO users (full_name, username, email, password_hash, role, must_change_password) VALUES (?, ?, ?, ?, 'administrator', 0)")
                    ->execute([$f['admin_name'], $f['admin_user'], $f['admin_email'], password_hash($pass, PASSWORD_DEFAULT)]);
                try {
                    $pdo->prepare('INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, ?, ?)')
                        ->execute([(int)$pdo->lastInsertId(), 'Website installed and administrator account created', $_SERVER['REMOTE_ADDR'] ?? null]);
                } catch (Throwable $ignored) { }

                //214
                $config = "<?php\n// Created by install.php on " . date('Y-m-d H:i') . ". Keep this file private.\nreturn " . var_export([
                    'APP_ENV' => 'production',
                    'DB_HOST' => $f['db_host'], 'DB_PORT' => $f['db_port'] !== '' ? $f['db_port'] : '3306',
                    'DB_NAME' => $f['db_name'], 'DB_USER' => $f['db_user'], 'DB_PASS' => $dbPass,
                ], true) . ";\n";
                $written = @file_put_contents($configFile, $config, LOCK_EX) !== false;
                if ($written) @file_put_contents($lockFile, date('c'));
                @unlink(__FILE__);
                @unlink($root . '/setup.php');
                $_SESSION = [];

                $body = '<h1>Installation complete</h1>';
                if ($written) {
                    $body .= '<p class="ok"><strong>Your website is ready.</strong> The database was created and your administrator account was added.</p>'
                        . '<p><a class="btn" href="index.php">Open the website</a> &nbsp; <a class="btn" href="admin/login.php">Admin login</a></p>';
                } else {
                    $body .= '<p class="warn"><strong>Almost done.</strong> The database is ready, but this server did not let the installer save the settings file. '
                        . 'Create a file named <code>config.local.php</code> inside the <code>config</code> folder with exactly this content, then open the website:</p>'
                        . '<pre>' . h($config) . '</pre><p><a class="btn" href="index.php">Open the website</a></p>';
                }
                $body .= '<p class="muted">If <code>install.php</code> or <code>setup.php</code> are still on your hosting, delete them. Log in with the username and password you just chose.</p>';
                page('Installation complete', $body);
            }
        } catch (Throwable $e) {
            $errors[] = 'The import stopped with this error: ' . $e->getMessage()
                . ' If the database already contains some tables from an earlier try, empty it in phpMyAdmin (select all tables, then Drop) and run the installer again.';
        }
    }
}

//215
$body = '<h1>Install the website</h1><p class="muted">This takes about one minute. You need the database details from your hosting control panel.</p><ul class="checks">';
foreach ($checks as $c) {
    $class = $c[1] ? 'ok' : ($c[2] ? 'bad' : 'warn');
    $body .= '<li class="' . $class . '">' . ($c[1] ? '&#10003; ' : ($c[2] ? '&#10007; ' : '&#9888; ')) . h($c[0]) . '</li>';
}
$body .= '</ul>';
if ($blocked) {
    page('Install', $body . '<div class="err">Something required is missing, so the installer cannot continue. Choose PHP 8.1 or newer in the hosting control panel and make sure all files were uploaded.</div>');
}
foreach ($errors as $err) $body .= '<div class="err">' . h($err) . '</div>';
$body .= '<form method="post" autocomplete="off"><input type="hidden" name="csrf" value="' . h($_SESSION['install_csrf']) . '">'
    . '<h2>1. Database (from your hosting control panel)</h2>'
    . '<div class="row"><div><label for="db_host">Database host</label><input id="db_host" name="db_host" placeholder="sql123.infinityfree.com" value="' . h($f['db_host']) . '" required></div>'
    . '<div><label for="db_port">Port</label><input id="db_port" name="db_port" value="' . h($f['db_port']) . '"></div></div>'
    . '<label for="db_name">Database name</label><input id="db_name" name="db_name" placeholder="if0_12345678_barangay" value="' . h($f['db_name']) . '" required>'
    . '<div class="row"><div><label for="db_user">Database username</label><input id="db_user" name="db_user" placeholder="if0_12345678" value="' . h($f['db_user']) . '" required></div>'
    . '<div><label for="db_pass">Database password</label><input id="db_pass" name="db_pass" type="password"></div></div>'
    . '<h2>2. Your administrator account</h2>'
    . '<label for="admin_name">Full name</label><input id="admin_name" name="admin_name" value="' . h($f['admin_name']) . '" required>'
    . '<div class="row"><div><label for="admin_user">Username</label><input id="admin_user" name="admin_user" value="' . h($f['admin_user']) . '" required></div>'
    . '<div><label for="admin_email">Email</label><input id="admin_email" name="admin_email" type="email" value="' . h($f['admin_email']) . '" required></div></div>'
    . '<div class="row"><div><label for="admin_pass">Password (10+ characters)</label><input id="admin_pass" name="admin_pass" type="password" required></div>'
    . '<div><label for="admin_pass2">Repeat the password</label><input id="admin_pass2" name="admin_pass2" type="password" required></div></div>'
    . '<button type="submit">Install</button></form>';
page('Install', $body);
