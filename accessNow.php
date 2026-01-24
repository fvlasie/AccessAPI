<?php
require_once '../../gibbon.php';
require_once __DIR__ . '/moduleFunctions.php';

use Gibbon\Module\AccessAPI\Services\UserService;

header('Content-Type: application/json');

// Authenticate using your working function
$result = accessAPIAuthenticate($container);

if ($result !== true) {
    http_response_code(401);
    echo json_encode(['error' => $result]);
    exit;
}

//Accept either id or username

$id   = $_GET['id']   ?? null;
$user = $_GET['user'] ?? null;

if (empty($id) && empty($user)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing id or user parameter']);
    exit;
}

if (!empty($user)) {
    $sql = "SELECT gibbonPersonID FROM gibbonPerson WHERE username = ?";
    $result = $pdo->select($sql, [$user]);

    if (empty($result)) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    $row = $result->fetch();

    $id = $row['gibbonPersonID'];
}

// Now $id is guaranteed to be a valid gibbonPersonID

if (!$id) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing id'
    ]);
    exit;
}

$service = new UserService($pdo);

// Use current server time
$now = date('Y-m-d H:i:s');

$result = $service->getAccessForUser($id, $now);

echo json_encode([
    'status' => 'ok',
    'timestamp' => time(),
    'datetime' => $now,
    'data' => $result
]);
