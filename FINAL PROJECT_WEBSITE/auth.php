<?php
//120
require_once __DIR__ . '/functions.php';

//121
//122
//123

function current_user(): ?array
{
    $user = $_SESSION['user'] ?? null;
    return is_array($user) ? $user : null;
}

function auth_normalize_role(string $role): string
{
    $role = strtolower(trim($role));

    return $role === 'admin' ? 'administrator' : $role;
}

//124
function auth_role_permissions(): array
{
    return [
        'administrator' => ['*'],

        'editor' => [
            'dashboard.view',

            'announcements.view',
            'announcements.create',
            'announcements.update',

            'events.view',
            'events.create',
            'events.update',

            'pages.view',
            'pages.create',
            'pages.update',

            'services.view',
            'services.create',
            'services.update',

            'reports.view',
            'reports.update',

            'messages.view',
            'messages.update',

            'media.view',
            'media.upload',
        ],
    ];
}

//125
function has_permission(string $permission): bool
{
    $user = current_user();
    $permission = strtolower(trim($permission));

    if (!$user || !preg_match('/^[a-z0-9_-]+\.[a-z0-9_-]+$/D', $permission)) {
        return false;
    }

    $role = auth_normalize_role((string)($user['role'] ?? ''));

    $permissions = auth_role_permissions()[$role] ?? [];

    return in_array('*', $permissions, true)
        || in_array($permission, $permissions, true);
}

function is_administrator(): bool
{
    $user = current_user();

    return $user !== null
        && auth_normalize_role((string)($user['role'] ?? '')) === 'administrator';
}

function is_editor(): bool
{
    $user = current_user();

    return $user !== null
        && auth_normalize_role((string)($user['role'] ?? '')) === 'editor';
}


//126
//127
//128
//129
//130

function auth_client_ip(): string
{
    return client_ip();
}

//131
//132
function auth_ensure_attempts_table(): void
{
    static $checked = false;

    if ($checked) {
        return;
    }

    $checked = true;

    try {
        db()->exec(
            'CREATE TABLE IF NOT EXISTS login_attempts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                identifier_hash CHAR(64) NOT NULL,
                ip_address VARCHAR(45) NOT NULL,
                attempted_at DATETIME NOT NULL,
                INDEX idx_attempt_lookup (identifier_hash, ip_address, attempted_at)
            ) ENGINE=InnoDB'
        );
    } catch (Throwable $error) {
        error_log('Could not create login_attempts table: ' . $error->getMessage());
    }
}

function auth_identifier_hash(string $identifier): string
{
    return hash('sha256', strtolower(trim($identifier)));
}

function auth_is_rate_limited(string $identifierHash, string $ip): bool
{
    try {
        $stmt = db()->prepare(
            'SELECT COUNT(*) FROM login_attempts
             WHERE identifier_hash = ?
             AND ip_address = ?
             AND attempted_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)'
        );

        $stmt->execute([$identifierHash, $ip]);

        return (int)$stmt->fetchColumn() >= 5;

    } catch (Throwable $error) {
        error_log('Login rate-limit check failed: ' . $error->getMessage());

        //133
        return false;
    }
}

function auth_record_failed_login(string $identifierHash, string $ip): void
{
    try {
        $stmt = db()->prepare(
            'INSERT INTO login_attempts
            (identifier_hash, ip_address, attempted_at)
            VALUES (?, ?, NOW())'
        );

        $stmt->execute([$identifierHash, $ip]);

        //134
        db()->exec('DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 1 DAY)');

    } catch (Throwable $error) {
        error_log('Could not record failed login: ' . $error->getMessage());
    }
}

function auth_clear_login_attempts(string $identifierHash, string $ip): void
{
    try {
        $stmt = db()->prepare(
            'DELETE FROM login_attempts
             WHERE identifier_hash = ?
             AND ip_address = ?'
        );

        $stmt->execute([$identifierHash, $ip]);

    } catch (Throwable $error) {
        error_log('Could not clear login attempts: ' . $error->getMessage());
    }
}


//135
//136
//137

function auth_account_is_allowed(array $row): bool
{
    //138
    if ((int)($row['is_active'] ?? 0) !== 1) {
        return false;
    }

    //139
    if (array_key_exists('is_locked', $row) && (bool)$row['is_locked']) {
        return false;
    }

    //140
    if (!empty($row['locked_until'])) {
        $lockedUntil = strtotime((string)$row['locked_until']);

        if ($lockedUntil !== false && $lockedUntil > time()) {
            return false;
        }
    }

    return true;
}


//141
//142
//143

function attempt_login(string $identifier, string $password): bool
{
    $identifier = trim($identifier);

    if (
        $identifier === '' ||
        $password === '' ||
        strlen($identifier) > 255 ||
        strlen($password) > 4096
    ) {
        return false;
    }

    auth_ensure_attempts_table();

    $identifierHash = auth_identifier_hash($identifier);
    $ip = auth_client_ip();

    //144
    if (auth_is_rate_limited($identifierHash, $ip)) {
        flash('error', 'Too many failed login attempts. Please try again in 15 minutes.');
        return false;
    }

    try {
        $stmt = db()->prepare(
            'SELECT * FROM users
             WHERE username = ? OR email = ?
             LIMIT 1'
        );

        $stmt->execute([$identifier, $identifier]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (Throwable $error) {
        error_log('Login database query failed: ' . $error->getMessage());

        flash('error', 'Login is temporarily unavailable. Please try again later.');

        return false;
    }

    //145
    $passwordValid = is_array($row)
        && isset($row['password_hash'])
        && password_verify($password, (string)$row['password_hash']);

    if (!$passwordValid || !auth_account_is_allowed($row)) {
        auth_record_failed_login($identifierHash, $ip);
        return false;
    }

    //146
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id' => (int)$row['id'],
        'name' => (string)$row['full_name'],
        'role' => (string)$row['role'],
        'must_change_password' => (bool)$row['must_change_password'],
    ];

    //147
    $_SESSION['auth_started_at'] = time();
    $_SESSION['auth_last_activity'] = time();

    //148
    auth_clear_login_attempts($identifierHash, $ip);

    //149
    try {
        log_activity((int)$row['id'], 'Logged in');
    } catch (Throwable $error) {
        error_log('Login activity logging failed: ' . $error->getMessage());
    }

    return true;
}


//150
//151
//152
//153
//154

function auth_session_expired(): bool
{
    $now = time();

    $idleLimit = 30 * 60;
    $absoluteLimit = 8 * 60 * 60;

    $startedAt = (int)($_SESSION['auth_started_at'] ?? $now);

    $lastActivity = (int)($_SESSION['auth_last_activity'] ?? $now);

    if (
        ($now - $lastActivity) > $idleLimit ||
        ($now - $startedAt) > $absoluteLimit
    ) {
        return true;
    }

    $_SESSION['auth_last_activity'] = $now;

    return false;
}


//155
function auth_logout(): void
{
    unset(
        $_SESSION['user'],
        $_SESSION['auth_started_at'],
        $_SESSION['auth_last_activity']
    );

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}


//156
//157
//158
//159
//160

function auth_refresh_user(): ?array
{
    $user = current_user();

    if (!$user) {
        return null;
    }

    try {
        $stmt = db()->prepare(
            'SELECT * FROM users
             WHERE id = ?
             LIMIT 1'
        );

        $stmt->execute([(int)($user['id'] ?? 0)]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (Throwable $error) {
        error_log('Could not refresh authenticated user: ' . $error->getMessage());

        http_response_code(503);

        exit('Your account could not be verified. Please try again later.');
    }

    //161
    if (!$row || !auth_account_is_allowed($row)) {
        auth_logout();
        return null;
    }

    //162
    $_SESSION['user'] = [
        'id' => (int)$row['id'],
        'name' => (string)$row['full_name'],
        'role' => (string)$row['role'],
        'must_change_password' => (bool)$row['must_change_password'],
    ];

    return $_SESSION['user'];
}


//163
function active_admin_count(): int
{
    return (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'administrator' AND is_active = 1")->fetchColumn();
}


//164
function auth_forbidden(string $message): never
{
    http_response_code(403);

    exit(
        '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>No permission</title>'
        . '<meta name="viewport" content="width=device-width, initial-scale=1"></head>'
        . '<body style="font-family:system-ui,sans-serif;padding:3rem 1.5rem;max-width:34rem;margin:auto">'
        . '<h1 style="margin:0 0 .5rem">No permission</h1><p>' . e($message) . '</p>'
        . '<p><a href="' . e(BASE_URL) . '/admin/dashboard.php">Back to the dashboard</a></p></body></html>'
    );
}


//165
//166
//167
//168

function require_login(bool $allowPasswordChange = false): array
{
    if (!current_user()) {
        redirect('admin/login.php');
    }

    //169
    if (auth_session_expired()) {
        auth_logout();

        flash('warning', 'Your session expired. Please log in again.');

        redirect('admin/login.php');
    }

    //170
    $user = auth_refresh_user();

    if (!$user) {
        flash('error', 'Your account is inactive or no longer available.');

        redirect('admin/login.php');
    }

    //171
    if (!empty($user['must_change_password']) && !$allowPasswordChange) {
        redirect('admin/change-password.php');
    }

    return $user;
}


//172
//173
//174
//175

function require_role(string $role): array
{
    $user = require_login();

    $requiredRole = auth_normalize_role($role);

    $actualRole = auth_normalize_role(
        (string)($user['role'] ?? '')
    );

    if ($actualRole !== $requiredRole) {
        auth_forbidden('You do not have permission to open this page.');
    }

    return $user;
}


//176
//177
//178
//179

function require_permission(string $permission): array
{
    $user = require_login();

    if (!has_permission($permission)) {
        auth_forbidden('You do not have permission to open this page.');
    }

    return $user;
}


//180
//181
//182
//183

function require_module_access(
    string $module,
    string $action = 'view'
): array {

    $module = strtolower(trim($module));
    $action = strtolower(trim($action));

    return require_permission($module . '.' . $action);
}