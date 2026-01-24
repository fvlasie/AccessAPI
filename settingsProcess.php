<?php

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\System\CustomFieldGateway;

include '../../gibbon.php';

// Permission check
if (isActionAccessible($guid, $connection2, '/modules/AccessAPI/settings.php') == false) {
    $page->addError(__('You do not have access to this action.'));
    return;
}

$URL = $session->get('absoluteURL') . '/index.php?q=/modules/' . $session->get('module') . '/settings.php';

// CSRF check
if (empty($_POST['address']) || $_POST['address'] != $session->get('address')) {
    $URL .= '?return=error0';
    header("Location: {$URL}");
    exit;
}

$settingGateway = $container->get(SettingGateway::class);

// Collect submitted values
$apiKeyId  = $_POST['apiKeyId']  ?? '';
$apiSecret = $_POST['apiSecret'] ?? '';

// Validate
if ($apiKeyId === '' || $apiSecret === '') {
    $URL .= '?return=error1';
    header("Location: {$URL}");
    exit;
}

// Save settings (using your existing full-scope structure)
$partialFail = false;

$partialFail |= !$settingGateway->updateSettingByScope('AccessAPI', 'apiKeyId', $apiKeyId);
$partialFail |= !$settingGateway->updateSettingByScope('AccessAPI', 'apiSecret', $apiSecret);

// Redirect with status
if ($partialFail) {
    $URL .= '&return=warning1';
} else {
    $URL .= '&return=success0';
}

header("Location: {$URL}");
exit;
