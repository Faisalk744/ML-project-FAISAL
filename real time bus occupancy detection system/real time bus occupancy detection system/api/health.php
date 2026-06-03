<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

$checks = [
    'database' => false,
    'model' => is_file(PROJECT_ROOT . '/ml/pipeline.joblib'),
    'python' => false,
];

try {
    get_pdo()->query('SELECT 1');
    $checks['database'] = true;
} catch (Throwable $e) {
    $checks['database_error'] = $e->getMessage();
}

$test = run_ml_predict([
    'distance_km' => 5.0,
    'weather' => 'Clear',
    'traffic_level' => 'Medium',
    'time_of_day' => 'Afternoon',
    'vehicle_type' => 'Bike',
    'preparation_time_min' => 15,
    'courier_experience_yrs' => 3.0,
]);
$checks['python'] = !empty($test['success']);

json_response([
    'success' => $checks['database'] && $checks['model'] && $checks['python'],
    'checks' => $checks,
]);
