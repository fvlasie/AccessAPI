<?php

require_once '../../gibbon.php';
require_once __DIR__ . '/moduleFunctions.php';

header('Content-Type: application/json');

// Authenticate
$result = accessAPIAuthenticate($container);

if ($result !== true) {
    http_response_code(401);
    echo json_encode(['error' => $result]);
    exit;
}

try {
    $pdo = $container->get('db');

    // Pull global school day plan from Gibbon
    $sql = "
        SELECT
            name,
            nameShort,
            sequenceNumber,
            schoolOpen,
            schoolStart,
            schoolEnd,
            schoolClose
        FROM gibbonDaysOfWeek
        WHERE schoolDay = 'Y'
        ORDER BY sequenceNumber
    ";

    $stmt = $pdo->select($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result = [];

    foreach ($rows as $row) {
        // Normalize to mon/tue/wed/thu/fri
        $key = strtolower(substr($row['nameShort'], 0, 3));

        $result[$key] = [
            'open'  => substr($row['schoolOpen'],  0, 5),
            'start' => substr($row['schoolStart'], 0, 5),
            'end'   => substr($row['schoolEnd'],   0, 5),
            'close' => substr($row['schoolClose'], 0, 5),
        ];
    }

    echo json_encode([
        'status' => 'ok',
        'days'   => $result,
        'count'  => count($result)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'details' => $e->getMessage()]);
}

exit;
