<?php

require_once '../../gibbon.php';

header('Content-Type: application/json');

if (!isActionAccessible($guid, $connection2, '/modules/AccessAPI/manageCards.php')) {
    echo json_encode([
        'success' => false,
        'error' => __('You do not have access to this action.')
    ]);
    return;
}

$gibbonPersonID = $_POST['gibbonPersonID'] ?? null;
$cardNumber = trim($_POST['cardNumber'] ?? '');
$address = $_POST['address'] ?? '';

if ($address !== $session->get('address')) {
    echo json_encode([
        'success' => false,
        'error' => __('Invalid session token.')
    ]);
    return;
}

if (empty($gibbonPersonID)) {
    echo json_encode([
        'success' => false,
        'error' => __('Missing user ID.')
    ]);
    return;
}

try {
    // DELETE card if blank
    if ($cardNumber === '') {
        $sql = "DELETE FROM accessAPICard WHERE gibbonPersonID = :gibbonPersonID";
        $stmt = $connection2->prepare($sql);
        $stmt->execute(['gibbonPersonID' => $gibbonPersonID]);

        echo json_encode(['success' => true]);
        return;
    }

    // UPSERT card
    $sql = "
        INSERT INTO accessAPICard (gibbonPersonID, cardNumber)
        VALUES (:gibbonPersonID, :cardNumber)
        ON DUPLICATE KEY UPDATE cardNumber = VALUES(cardNumber)
    ";

    $stmt = $connection2->prepare($sql);
    $stmt->execute([
        'gibbonPersonID' => $gibbonPersonID,
        'cardNumber' => $cardNumber
    ]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
