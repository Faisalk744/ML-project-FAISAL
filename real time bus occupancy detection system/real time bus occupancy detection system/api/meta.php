<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['success' => false, 'error' => 'Method not allowed'], 405);
}

$metaPath = PROJECT_ROOT . DIRECTORY_SEPARATOR . 'ml' . DIRECTORY_SEPARATOR . 'model_meta.json';
if (!is_file($metaPath)) {
    json_response([
        'success' => false,
        'error' => 'Model not trained. Run python ml/train_model.py',
    ], 404);
}

$meta = json_decode(file_get_contents($metaPath), true);
json_response(['success' => true, 'meta' => $meta]);
