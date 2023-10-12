<?php

error_reporting(E_ERROR);
ini_set('display_errors', 1);

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS',true);

$_SERVER["DOCUMENT_ROOT"] = "/mnt/data/bitrix";
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

require_once ('Workspace/DateBaseORM/Crud.php');

\Bitrix\Main\Loader::IncludeModule("crm");

$ContactInfo = \Bitrix\Crm\ContactTable::GetList([
    'select' => ['*', 'PHONE', 'ADDRESS'], 
    'filter' => [ 'PHONE' => $_POST['text'] ]
]);

$Contact = $ContactInfo->fetch();

if(isset($Contact) && !empty($Contact)){
    $CID = $Contact['ID'];

    $DealsInfo = \Bitrix\Crm\DealTable::GetList([
        'select' => ['ID', 'CONTACT_ID'],
        'filter' => ['CONTACT_ID' => $CID],
        'order' => ['ID' => 'DESC'],
        'limit' => 1
    ]);
    $DealsInfoData = $DealsInfo->fetch();

    if(isset($DealsInfoData['ID']) && !empty($DealsInfoData['ID'])){
        $id = $DealsInfoData['ID'];
        $result = [
            'isSuccess' => true,
            'text' => "{$id}",
        ];
    }
}


if(!isset($id)){
    $result = [
        'isSuccess' => true,
        'text' => "not",
    ];
}

header("Content-type: application/json; charset=utf-8");
echo json_encode($result);
