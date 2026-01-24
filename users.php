<?php

require_once '../../gibbon.php';
require_once __DIR__ . '/moduleFunctions.php';

use Gibbon\Module\AccessAPI\Services\UserService;

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

// Authenticate using your working function
$result = accessAPIAuthenticate($container);

if ($result !== true) {
    http_response_code(401);
    echo json_encode(['error' => $result]);
    exit;
}

// Load users via your existing service
$service = new UserService($container);
$users = $service->getUsers();

error_log("AccessAPI returning " . count($users) . " users");

echo json_encode([
    'status' => 'ok',
    'count' => count($users),
    'users' => $users
]);

exit;
