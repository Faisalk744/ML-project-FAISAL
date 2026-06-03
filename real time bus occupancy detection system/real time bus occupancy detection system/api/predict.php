<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Method not allowed'], 405);
}

$body = file_get_contents('php://input');
$data = json_decode($body ?: '{}', true);
if (!is_array($data)) {
    json_response(['success' => false, 'error' => 'Invalid JSON body'], 400);
}

$input = [
    'distance_km' => isset($data['distance_km']) ? (float) $data['distance_km'] : null,
    'weather' => trim((string) ($data['weather'] ?? '')),
    'traffic_level' => trim((string) ($data['traffic_level'] ?? '')),
    'time_of_day' => trim((string) ($data['time_of_day'] ?? '')),
    'vehicle_type' => trim((string) ($data['vehicle_type'] ?? '')),
    'preparation_time_min' => isset($data['preparation_time_min'])
        ? (int) $data['preparation_time_min'] : null,
    'courier_experience_yrs' => isset($data['courier_experience_yrs'])
        ? (float) $data['courier_experience_yrs'] : null,
];

if ($input['distance_km'] === null || $input['distance_km'] < 0) {
    json_response(['success' => false, 'error' => 'distance_km is required and must be >= 0'], 400);
}
if ($input['preparation_time_min'] === null || $input['preparation_time_min'] < 0) {
    json_response(['success' => false, 'error' => 'preparation_time_min is required'], 400);
}

$ml = run_ml_predict($input);
if (empty($ml['success'])) {
    json_response($ml, 500);
}

$predicted = (float) $ml['predicted_delivery_min'];

try {
    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'INSERT INTO predictions (
            distance_km, weather, traffic_level, time_of_day, vehicle_type,
            preparation_time_min, courier_experience_yrs, predicted_delivery_min
        ) VALUES (
            :distance_km, :weather, :traffic_level, :time_of_day, :vehicle_type,
            :preparation_time_min, :courier_experience_yrs, :predicted_delivery_min
        )'
    );
    $stmt->execute([
        ':distance_km' => $input['distance_km'],
        ':weather' => $input['weather'],
        ':traffic_level' => $input['traffic_level'],
        ':time_of_day' => $input['time_of_day'],
        ':vehicle_type' => $input['vehicle_type'],
        ':preparation_time_min' => $input['preparation_time_min'],
        ':courier_experience_yrs' => $input['courier_experience_yrs'] ?? 0,
        ':predicted_delivery_min' => $predicted,
    ]);
    $id = (int) $pdo->lastInsertId();
} catch (PDOException $e) {
    json_response([
        'success' => true,
        'predicted_delivery_min' => $predicted,
        'warning' => 'Prediction saved locally only; database error: ' . $e->getMessage(),
        'ml' => $ml,
    ]);
}

json_response([
    'success' => true,
    'id' => $id,
    'predicted_delivery_min' => $predicted,
    'message' => 'Delivery time predicted successfully',
]);
