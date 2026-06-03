<?php
/**
 * Database and application configuration for XAMPP
 */
declare(strict_types=1);

define('DB_HOST', 'localhost');
define('DB_NAME', 'delivery_time_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('PROJECT_ROOT', dirname(__DIR__));
define('PYTHON_BIN', 'python'); // or full path e.g. C:\\Python311\\python.exe
define('ML_PREDICT_SCRIPT', PROJECT_ROOT . DIRECTORY_SEPARATOR . 'ml' . DIRECTORY_SEPARATOR . 'predict.py');

if (PHP_SAPI !== 'cli') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

function json_response(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function get_pdo(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $pdo;
}

function run_ml_predict(array $input): array
{
    $script = ML_PREDICT_SCRIPT;
    if (!is_file($script)) {
        return ['success' => false, 'error' => 'ML predict script not found'];
    }

    $json = json_encode($input, JSON_UNESCAPED_UNICODE);
    $cmd = PYTHON_BIN . ' ' . escapeshellarg($script);

    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $proc = proc_open($cmd, $descriptors, $pipes, PROJECT_ROOT);
    if (!is_resource($proc)) {
        return ['success' => false, 'error' => 'Could not start Python process'];
    }

    fwrite($pipes[0], $json);
    fclose($pipes[0]);

    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($proc);

    $raw = trim($stdout !== false ? $stdout : '');
    if ($raw === '') {
        return [
            'success' => false,
            'error' => 'Python produced no output. ' . trim($stderr !== false ? $stderr : 'Check PYTHON_BIN in config.php.'),
        ];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return [
            'success' => false,
            'error' => 'Invalid ML response: ' . substr($raw, 0, 200),
        ];
    }
    return $decoded;
}
