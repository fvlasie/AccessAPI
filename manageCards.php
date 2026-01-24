<?php

use Gibbon\Services\Format;
use Gibbon\Forms\Form;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\User\UserGateway;

if (!isActionAccessible($guid, $connection2, '/modules/AccessAPI/manageCards.php')) {
    $page->addError(__('You do not have access to this action.'));
    return;
}

$page->breadcrumbs->add(__('Manage Access Cards'));

$absoluteURL = $session->get('absoluteURL');
$module = $session->get('module');
$address = $session->get('address');

// ------------------------------------------------------------
// CRITERIA (search, sort, pagination)
// ------------------------------------------------------------

$userGateway = $container->get(UserGateway::class);

$criteria = $userGateway->newQueryCriteria(true)
    ->searchBy($userGateway->getSearchableColumns(), $_GET['search'] ?? '')
    ->sortBy(['surname', 'preferredName'])
    ->fromPOST();

// ------------------------------------------------------------
// FILTER FORM
// ------------------------------------------------------------

$form = Form::createSearch();

$row = $form->addRow();
$row->addLabel('search', __('Search For'))
    ->description(__('Preferred, surname, username, role, student ID, email, phone number'));
$row->addTextField('search')->setValue($criteria->getSearchText());

$form->addRow()->addSearchSubmit('', __('Clear Search'));

echo $form->getOutput();

// ------------------------------------------------------------
// QUERY USERS (paginated)
// ------------------------------------------------------------

$dataSet = $userGateway->queryAllUsers($criteria);

// ------------------------------------------------------------
// FETCH CARD DATA FROM accessAPICard
// ------------------------------------------------------------

$people = $dataSet->getColumn('gibbonPersonID');

$cardRows = [];
$cardStatusMap = [];

if (!empty($people)) {

    $ids = array_map(fn($id) => $connection2->quote($id), $people);

    $sql = "
        SELECT gibbonPersonID, cardNumber, active
        FROM accessAPICard
        WHERE gibbonPersonID IN (" . implode(',', $ids) . ")
    ";

    $stmt = $connection2->prepare($sql);
    $stmt->execute();
    $cardRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($cardRows as $row) {
        // Normalize to match DataSet padded string
        $pid = str_pad($row['gibbonPersonID'], 10, '0', STR_PAD_LEFT);

        $cardStatusMap[$pid] = [
            'number' => $row['cardNumber'] ?? '',
            'active' => $row['active'] ?? 'Y'
        ];
    }
}

$dataSet->joinColumn('gibbonPersonID', 'accessAPICardStatus', $cardStatusMap);

// ------------------------------------------------------------
// CARD STATUS FILTER (with / without cards)
// ------------------------------------------------------------

$cardStatus = $_GET['cardStatus'] ?? 'all';

if ($cardStatus === 'with') {
    $dataSet = $dataSet->filter(fn($row) => !empty($row['cardNumber']));
} elseif ($cardStatus === 'without') {
    $dataSet = $dataSet->filter(fn($row) => empty($row['cardNumber']));
}

// ------------------------------------------------------------
// DATA TABLE (paginated)
// ------------------------------------------------------------

$table = DataTable::createPaginated('accessCards', $criteria);
$table->setTitle(__('Access Cards'));

$table->addMetaData('filterOptions', [
    'role:student'    => __('Role').': '.__('Student'),
    'role:parent'     => __('Role').': '.__('Parent'),
    'role:staff'      => __('Role').': '.__('Staff'),
    'status:full'     => __('Status').': '.__('Full'),
    'status:left'     => __('Status').': '.__('Left'),
    'status:expected' => __('Status').': '.__('Expected'),
    'date:starting'   => __('Before Start Date'),
    'date:ended'      => __('After End Date'),
    'card:with'       => __('With Cards'),
    'card:without'    => __('Without Cards'),
]);

// Name
$table->addColumn('fullName', __('Name'))
    ->sortable(['surname', 'preferredName'])
    ->format(function ($person) {
        return Format::name('', $person['preferredName'], $person['surname'], 'Other', true, true);

    });

// Role
$table->addColumn('roleCategory', __('Role'))
    ->width('10%')
    ->translatable();

// Username
$table->addColumn('username', __('Username'));

// Card Indicator (green/red checkmark)
$table->addColumn('accessAPICardStatus', __('Card'))
    ->format(function ($person) {
        $status = $person['accessAPICardStatus'] ?? ['number' => '', 'active' => 'Y'];

        $hasCard = !empty($status['number']);
        $active = trim($status['active'] ?? 'Y');

        if (!$hasCard) {
            return "<span class='text-gray-400' data-card='0' data-active='{$active}'>—</span>";
        }

        $color = ($active === 'Y')
            ? 'text-green-600 font-black text-2xl'
            : 'text-red-600 font-black text-2xl';

        return "<span class='{$color}' data-card='1' data-active='{$active}'><b>&#10003;</b></span>";
    });

// Action column (Edit button opens modal)
$table->addActionColumn()
    ->addParam('gibbonPersonID')
    ->format(function ($person, $actions) use ($session) {

        $actions->addAction('edit', __('Edit'))
            ->setURL('#')
            ->addClass('editCard')
            ->addData('person', $person['gibbonPersonID'])
            ->setAttribute('@click', 'modalOpen = true')
            ->setAttribute(
                'hx-get',
                $session->get('absoluteURL')
                . '/modules/AccessAPI/manageCards_edit.php?gibbonPersonID='
                . urlencode($person['gibbonPersonID'])
            )
            ->setAttribute('hx-target', '#modalContent')
            ->setAttribute('hx-swap', 'innerHTML');
        return $actions;
    });

echo $table->render($dataSet);

// ------------------------------------------------------------
// MODAL HTML
// ------------------------------------------------------------
?>
