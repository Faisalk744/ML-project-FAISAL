<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['success' => false, 'error' => 'Method not allowed'], 405);
}

$limit = isset($_GET['limit']) ? min(100, max(1, (int) $_GET['limit'])) : 20;

try {
    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'SELECT id, distance_km, weather, traffic_level, time_of_day, vehicle_type,
                preparation_time_min, courier_experience_yrs, predicted_delivery_min, created_at
         FROM predictions
         ORDER BY created_at DESC
         LIMIT :lim'
    );
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();
    json_response(['success' => true, 'predictions' => $rows]);
} catch (PDOException $e) {
    json_response(['success' => false, 'error' => $e->getMessage()], 500);
}
