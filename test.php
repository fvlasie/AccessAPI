<?php
require_once '../../gibbon.php';
require_once 'moduleFunctions.php';

header('Content-Type: application/json');

$auth = accessAPIAuthenticate($container);

if ($auth !== true) {
    echo json_encode(['status' => 'error', 'message' => $auth]);
    exit;
}

echo json_encode([
    'status' => 'ok',
    'message' => 'AccessAPI authentication successful',
    'timestamp' => time(),
]);
