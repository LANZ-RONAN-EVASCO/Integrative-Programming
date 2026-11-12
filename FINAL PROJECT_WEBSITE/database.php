<?php
//73
//74
//75
//76

//77
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

//78
//79
$localSettings = [];
$localFile = __DIR__ . '/config.local.php';
if (is_file($localFile)) {
    $loaded = require $localFile;
    if (is_array($loaded)) {
        $localSettings = $loaded;
    }
}

//80
$env = static function (string $key, ?string $default = null) use ($localSettings): ?string {
    if (array_key_exists($key, $localSettings)) {
        return (string) $localSettings[$key];
    }
    $value = getenv($key);
    return $value === false ? $default : $value;
};

//81
$httpHost = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
$httpHost = preg_replace('/:\d+$/', '', $httpHost);

//82
//83
$isPrivateAddress = filter_var($httpHost, FILTER_VALIDATE_IP) !== false
    && filter_var($httpHost, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;

$isLocalHost = in_array(
    $httpHost,
    ['localhost', '127.0.0.1', '::1', '[::1]'],
    true
) || $isPrivateAddress
  || str_ends_with($httpHost, '.local')
  || str_ends_with($httpHost, '.trycloudflare.com');

//84
$appEnv = strtolower(
    $env('APP_ENV', $isLocalHost ? 'development' : 'production')
);

$isDevelopment = $appEnv === 'development';

//85
$timezone = (string) $env('APP_TIMEZONE', 'Asia/Manila');
if (!in_array($timezone, timezone_identifiers_list(), true)) {
    $timezone = 'Asia/Manila';
}
date_default_timezone_set($timezone);
define('APP_TIMEZONE', $timezone);
define('APP_DEBUG', $isDevelopment);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../logs/error.log');
error_reporting(E_ALL);

set_exception_handler(function (Throwable $e) {
    error_log((string) $e);
    http_response_code(500);
    exit(APP_DEBUG ? 'Error: ' . $e->getMessage() : 'Something went wrong. Please try again later.');
});

//86
//87
//88

//89
$dbHost = $env('DB_HOST', $isDevelopment ? 'localhost' : null);
$dbName = $env('DB_NAME', $isDevelopment ? 'barangay_landayan' : null);
$dbUser = $env('DB_USER', $isDevelopment ? 'root' : null);
$dbPass = $env('DB_PASS', $isDevelopment ? '' : null);
$dbPort = $env('DB_PORT', '3306');
$dbCharset = $env('DB_CHARSET', 'utf8mb4');

//90
if (!$isDevelopment) {
    foreach ([
        'DB_HOST' => $dbHost,
        'DB_NAME' => $dbName,
        'DB_USER' => $dbUser,
        'DB_PASS' => $dbPass,
    ] as $key => $value) {

        if ($value === null || $value === '') {
            error_log('Missing required setting: ' . $key);

            throw new RuntimeException(
                'Production database configuration is incomplete.'
            );
        }
    }
}

//91
if (
    !ctype_digit((string)$dbPort) ||
    !preg_match('/^[a-zA-Z0-9_]+$/', (string)$dbCharset)
) {
    throw new RuntimeException('Invalid database configuration.');
}

//92
define('DB_HOST', $dbHost);
define('DB_NAME', $dbName);
define('DB_USER', $dbUser);
define('DB_PASS', $dbPass);
define('DB_PORT', $dbPort);
define('DB_CHARSET', $dbCharset);


//93
//94
//95

$baseUrlOverride = $env('APP_BASE_URL');

if ($baseUrlOverride !== null) {

    //96
    if (
        preg_match('#^[a-z][a-z0-9+.-]*://#i', $baseUrlOverride) ||
        str_contains($baseUrlOverride, '?') ||
        str_contains($baseUrlOverride, '#')
    ) {
        throw new RuntimeException('Invalid APP_BASE_URL setting.');
    }

    $baseUrl = trim($baseUrlOverride);

    $baseUrl = $baseUrl === ''
        ? ''
        : '/' . trim($baseUrl, '/');

} else {

    $baseUrl = '';

    $documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    $projectRoot = realpath(ROOT_PATH);

    if ($documentRoot && $projectRoot) {

        $documentRoot = rtrim(
            $documentRoot,
            DIRECTORY_SEPARATOR
        );

        $prefix = $documentRoot . DIRECTORY_SEPARATOR;

        if (stripos($projectRoot, $prefix) === 0) {

            $relativePath = substr(
                $projectRoot,
                strlen($prefix)
            );

            $baseUrl = '/' . str_replace(
                DIRECTORY_SEPARATOR,
                '/',
                $relativePath
            );
        }
    }
}

//97
if (!defined('BASE_URL')) {
    define('BASE_URL', $baseUrl);
}


//98
//99
//100

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    try {

        $dsn = 'mysql:host=' . DB_HOST
            . ';port=' . DB_PORT
            . ';dbname=' . DB_NAME
            . ';charset=' . DB_CHARSET;

        $pdo = new PDO(
            $dsn,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        //101
        $offset = (new DateTime('now', new DateTimeZone(APP_TIMEZONE)))->format('P');
        $pdo->exec("SET time_zone = '" . $offset . "'");

        return $pdo;

    } catch (PDOException $error) {

        //102
        error_log(
            'Database connection failed: ' . $error->getMessage()
        );

        http_response_code(500);

        //103
        //104
        if (APP_DEBUG) {
            $code = (int) ($error->errorInfo[1] ?? $error->getCode());

            if ($code === 1049) {
                exit('The database "' . DB_NAME . '" does not exist yet. Import database.sql in phpMyAdmin (README, step 3).');
            }

            if ($code === 2002 || $code === 2006) {
                exit('MySQL is not running. Start MySQL in the XAMPP Control Panel, then refresh this page.');
            }

            if ($code === 1045) {
                exit('MySQL refused the username or password. Check DB_USER and DB_PASS in config/database.php.');
            }

            exit('Could not connect to the database. Check config/database.php and that MySQL is running.');
        }

        exit('The website is temporarily unavailable. Please try again later.');
    }
}
