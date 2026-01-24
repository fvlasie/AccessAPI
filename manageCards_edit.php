<?php
require_once __DIR__ . '/../../gibbon.php';

use Gibbon\Forms\Form;

// Determine if GET or POST
$gibbonPersonID = $_GET['gibbonPersonID'] ?? $_POST['gibbonPersonID'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $cardNumber = trim($_POST['cardNumber'] ?? '');
    $cardNumber = ($cardNumber === '') ? null : $cardNumber;
    $active = ($_POST['active'] ?? 'Y') === 'Y' ? 'Y' : 'N';

    if ($gibbonPersonID === '') {
        echo "<script>
            modalAlert = {
                title: '".__('Error')."',
                text: '".__('Missing required fields.')."',
                confirm: null
            };
        </script>";
        return;
    }

    $sql = "INSERT INTO accessAPICard (gibbonPersonID, cardNumber, active)
            VALUES (:id, :cardNumber, :active)
            ON DUPLICATE KEY UPDATE
                cardNumber = VALUES(cardNumber),
                active = VALUES(active)";

    $stmt = $connection2->prepare($sql);
    $ok = $stmt->execute([
        'id'         => $gibbonPersonID,
        'cardNumber' => $cardNumber,
        'active'     => $active
    ]);

    if (!$ok) {
        echo "<script>
            modalAlert = {
                title: '".__('Error')."',
                text: '".__('Could not save card.')."',
                confirm: null
            };
        </script>";
        return;
    }

echo '<script>
    location.reload()
</script>';
    return;
}

// -------------------------
// GET request: show the form
// -------------------------

$sql = "SELECT cardNumber, active
        FROM accessAPICard
        WHERE gibbonPersonID = :id";

$stmt = $connection2->prepare($sql);
$stmt->execute(['id' => $gibbonPersonID]);
$card = $stmt->fetch();

$cardNumber = $card['cardNumber'] ?? '';
$active     = $card['active'] ?? 'Y';

// -----------
// EDIT FORM
// -----------

$form = Form::create(
    'editAccessCard',
    $session->get('absoluteURL') . '/modules/AccessAPI/manageCards_edit.php'
);

$form->setAttribute('id', 'editAccessCard');
$form->setMethod('post');

$form->addHiddenValue('gibbonPersonID', $gibbonPersonID);

// HTMX attributes
$form->setAttribute('hx-post', $session->get('absoluteURL') . '/modules/AccessAPI/manageCards_edit.php');
$form->setAttribute('hx-target', '#modalContent');
$form->setAttribute('hx-swap', 'innerHTML');

// Card Number
$row = $form->addRow();
$row->addLabel('cardNumber', __('Card Number'));
$row->addTextField('cardNumber')->setValue($cardNumber)->maxLength(64);

// Active toggle (Gibbon-native Yes/No)
$row = $form->addRow();
$row->addLabel('active', __('Active'));
$row->addYesNo('active')->selected($active);

// Submit
$row = $form->addRow();
$row->addSubmit(__('Save'));

echo $form->getOutput();
