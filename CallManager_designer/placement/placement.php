<?php
namespace CallCustom;

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS',true);

$_SERVER["DOCUMENT_ROOT"] = "/mnt/data/bitrix";
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

require_once ('/mnt/data/bitrix/local/php_interface/classes/CallManager_designer/head.php');

error_reporting(E_ERROR);
ini_set('display_errors', 1);

$post = $_REQUEST;

$data = json_decode($post['PLACEMENT_OPTIONS']);
$data = $data->ID;

class CallManager {

    public function __construct() {

	$currentDateTime = new \DateTime();
	$this->time = $currentDateTime->format('Y-m-d\TH:i:s.u\Z');

    $this->CurlManagerCall = new \CallCustom\Workspace\Tools\Call();
    $this->CRM = new \CallCustom\Workspace\Bitrix\CRM();
    $this->Base = new \CallCustom\Workspace\Tools\Base();
	$this->CreateItems = new \CallCustom\Workspace\Tools\CreateItemsForCheck();
	$this->CheckItemForCall = new \CallCustom\Workspace\Tools\CheckItemForCall();

    }
  
    public function Main($data){

	// дава время встречи - UF_CRM_1693988021524
	// номер договора - UF_CRM_1694018792723
	// UF_CRM_1693485339146 - принять встречу
	// UF_CRM_1693585313113 - адресс

	$Deals = $this->CRM->GetPullDeal(
		[
			'select' => ['ID', 'DATE_CREATE',  'UF_CRM_1696584638945', 'UF_CRM_1694160451992', 'UF_CRM_1693988021524', 'UF_CRM_1693585313113', 'UF_CRM_1694018792723', '*', 'STAGE_ID', 'CATEGORY_ID', 'ASSIGNED_BY_ID', 'UF_CRM_1693485339146'], 
            'filter' => ['ID' => $data]
		]
	);

	$ItemsDeal = $this->CreateItems->GetPull(['deals' => $Deals]);

	$checkCall_list = $this->CheckItemForCall->GetPull(['items' => $ItemsDeal, 'force' => 'force']);

    }

}

$CallManager = new CallManager();
$orders = $CallManager->Main($data);
/*

$logFile = '/mnt/data/bitrix/local/php_interface/classes/CallManager_designer/placement/log_'.time().'.txt';
$formattedData = var_export($data, true);
file_put_contents($logFile, '<?php $array = ' . $formattedData . ';', FILE_APPEND);*/