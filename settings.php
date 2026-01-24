<?php

use Gibbon\Forms\Form;
use Gibbon\Domain\System\SettingGateway;

if (isActionAccessible($guid, $connection2, '/modules/AccessAPI/settings.php') == false) {
    $page->addError(__('You do not have access to this action.'));
    return;
}

$page->breadcrumbs->add(__('Settings'));

$settingGateway = $container->get(SettingGateway::class);

$form = Form::create(
    'accessAPISettings',
    $session->get('absoluteURL') . '/modules/' . $session->get('module') . '/settingsProcess.php'
);

$form->addHiddenValue('address', $session->get('address'));

// Heading
$form->addRow()
    ->addHeading('AccessAPI', __('AccessAPI'))
    ->append(__('Configure API authentication settings for the AccessAPI module.'));

// API Key ID
$setting = $settingGateway->getSettingByScope('AccessAPI', 'apiKeyId', true);
$row = $form->addRow();
$row->addLabel($setting['name'], __($setting['nameDisplay']))
    ->description(__($setting['description']));
$row->addTextField($setting['name'])
    ->setValue($setting['value'])
    ->required();

// API Secret
$setting = $settingGateway->getSettingByScope('AccessAPI', 'apiSecret', true);
$row = $form->addRow();
$row->addLabel($setting['name'], __($setting['nameDisplay']))
    ->description(__($setting['description']));
$row->addPassword($setting['name'])
    ->setValue($setting['value'])
    ->required();

// Submit
$row = $form->addRow();
$row->addFooter();
$row->addSubmit();

echo $form->getOutput();
