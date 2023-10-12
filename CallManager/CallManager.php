<?php
namespace CallCustom;

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS',true);

$_SERVER["DOCUMENT_ROOT"] = "/mnt/data/bitrix";
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

require_once ('head.php');

error_reporting(E_ERROR);
ini_set('display_errors', 1);

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
  
    public function Main(){
	
	// UF_CRM_1693572313224 - дата ДЛЯ ЗВОНКА
	// UF_CRM_1693988021524 - дата и время встречи

	$Deals = $this->CRM->GetPullDeal(
		[
			'select' => ['ID', 'DATE_CREATE', 'UF_CRM_1693988021524', 'UF_CRM_1693572313224', '*', 'STAGE_ID', 'CATEGORY_ID', 'ASSIGNED_BY_ID'], 
			'filter' => ['CATEGORY_ID' => 0],
		]
	);

	//$ItemsDeal = $this->CreateItems->GetPull(['deals' => $this->GetTestDATA()]);
	
	$ItemsDeal = $this->CreateItems->GetPull(['deals' => $Deals]);

	//print_r(['$ItemsDeal' => $ItemsDeal]);

	$checkCall_list = $this->CheckItemForCall->GetPull(['items' => $ItemsDeal, 'ThisTime' => $this->time]);
	die();
	print_r(['$checkCall_list' => $checkCall_list]);

	$checkCall_list_i = $this->CheckItemForCall->CheckPriority($checkCall_list);


	print_r(['$checkCall_list_i' => $checkCall_list_i]);

	foreach($checkCall_list_i as $checkCall){
	
		if(isset($checkCall) && !empty($checkCall)){
			
			$this->CheckItemForCall->GoCall($checkCall);
			// инициализация звонка для теплой очереди 
			$ContactNumer = $this->CheckItemForCall->GetContactNumer($checkCall);
			 //$this->CurlManagerCall->Post($ContactNumer);

			print_r(['делаем звонок на' => $checkCall]);
			
		}
	}

    }

	public function GetTestDATA(){

		// PREPARATION - новая заявка
		// EXECUTING - встреча назначена
		// PREPAYMENT_INVOICE ждет звонка 3 месяца
		// UC_18RAI2 - встреча отменена

		$datetime = \DateTime::createFromFormat('Y-m-d H:i:s', '2023-09-30 12:06:00');  // время встречи
		$datetime2 = \DateTime::createFromFormat('Y-m-d H:i:s', '2023-09-29 12:59:00');  // время звонка

		$array = array (
			0 => 
			array ( 
			  'ID' => '1',
			  'UF_CRM_1693988021524' => $datetime, // время встречи
			  'CONTACT_ID' => '1356',
			  'TITLE' => 'сделка 1',
			  'CATEGORY_ID' => '0',
			  'STAGE_ID' => 'EXECUTING',
			  'ASSIGNED_BY_ID' => '37',
			  // priority 1
			),
			array (
				// звонок сразу
				'ID' => '2',
				'UF_CRM_1693572313224' => $datetime2, // время звонка
				'CONTACT_ID' => '1356',
				'TITLE' => 'сделка 2',
				'CATEGORY_ID' => '0',
				'STAGE_ID' => 'PREPAYMENT_INVOICE',
				'CLOSED' => 'N',
				'ASSIGNED_BY_ID' => '37',
				  // priority 4
			  ),
			  array (
				// звонок сразу
				'ID' => '22',
				'UF_CRM_1693572313224' => $datetime2, // время звонка
				'CONTACT_ID' => '1356',
				'TITLE' => 'сделка 22',
				'CATEGORY_ID' => '0',
				'STAGE_ID' => 'PREPAYMENT_INVOICE',
				'CLOSED' => 'N',
				'ASSIGNED_BY_ID' => '3',
				  // priority 4
			  ),
			   array (
				// звонок сразу
				'ID' => '3',
				'CONTACT_ID' => '1356',
				'TITLE' => 'сделка 3',
				'CATEGORY_ID' => '0',
				'STAGE_ID' => 'PREPARATION',
				'CLOSED' => 'N',
				'ASSIGNED_BY_ID' => '37',
				  // priority 3
			  ),
			  array (
				// звонок сразу
				'ID' => '33',
				'CONTACT_ID' => '1356',
				'TITLE' => 'сделка 33',
				'CATEGORY_ID' => '0',
				'STAGE_ID' => 'PREPARATION',
				'CLOSED' => 'N',
				'ASSIGNED_BY_ID' => '3',
				  // priority 3
			  ),
			  array (
				// звонок сразу
				'ID' => '4',
				'CONTACT_ID' => '1356',
				'TITLE' => 'сделка 4',
				'CATEGORY_ID' => '0',
				'STAGE_ID' => 'UC_18RAI2',
				'CLOSED' => 'N',
				'ASSIGNED_BY_ID' => '37',
				  // priority 5
			  ),
		  );

		  return $array;
	}

}

$CallManager = new CallManager();
$orders = $CallManager->Main();

/*
CREATE TABLE cm_field_config (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,      
	typefield VARCHAR(255) NOT NULL,   
	entity_in_b24 VARCHAR(255) NOT NULL,  
	require_field VARCHAR(255) NOT NULL
)*/

/*
CREATE TABLE cm_type_call (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,      
	type_call VARCHAR(255) NOT NULL,   
	entity_in_b24 VARCHAR(255) NOT NULL,  
	value_entity VARCHAR(255) NOT NULL,
	priority VARCHAR(255) NOT NULL,
)
*/

/*
INSERT INTO `cm_type_call` (`id`, `type_call`, `entity_in_b24`, `value_entity`, `priority`) VALUES (NULL, 'moment', 'SOURCE_ID', 'ловец', 5);
INSERT INTO `cm_type_call` (`id`, `type_call`, `entity_in_b24`, `value_entity`, `priority`) VALUES (NULL, 'moment', 'SOURCE_ID', 'заяква с сайта', 1);
INSERT INTO `cm_type_call` (`id`, `type_call`, `entity_in_b24`, `value_entity`, `priority`) VALUES (NULL, 'moment', 'STAGE_ID', 'PREPARATION', 2);
INSERT INTO `cm_type_call` (`id`, `type_call`, `entity_in_b24`, `value_entity`, `priority`) VALUES (NULL, 'moment', 'STAGE_ID', 'UC_18RAI2', 4);

INSERT INTO `cm_type_call` (`id`, `type_call`, `entity_in_b24`, `value_entity`, `priority`) VALUES (NULL, 'wait', 'STAGE_ID', 'PREPAYMENT_INVOICE', 3);
INSERT INTO `cm_type_call` (`id`, `type_call`, `entity_in_b24`, `value_entity`, `priority`) VALUES (NULL, 'wait', 'STAGE_ID', 'EXECUTING', 0);
*/
/*
CREATE TABLE cm_history (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,               
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,     
    id_responsible VARCHAR(255) NOT NULL,                    
	contact_id VARCHAR(255) NOT NULL,                   
    uf_time VARCHAR(255),                     
    id_crm_deal INT NOT NULL,     

	type_call VARCHAR(255),                    
	category_call VARCHAR(255), 

	is_active TINYINT(1) DEFAULT 1,                    

	change_date TINYINT(1) DEFAULT 0,                  
	
	twenty_four_hour VARCHAR(255) NOT NULL DEFAULT 'not yet',     
	three_hour  VARCHAR(255) NOT NULL DEFAULT 'not yet',         

	status_called VARCHAR(255) NOT NULL DEFAULT 'not yet',       
	breaktime VARCHAR(255),                              
	start_day VARCHAR(255),                            
    stoptime VARCHAR(255) NOT NULL DEFAULT 'not'    
);*/




/*

// 1)
// возвращаем статус онлайн/не онлайн
GLOBAL $USER;
$online = \CUser::IsOnLine($id_responsible);
if(!$online){
 	echo 'юзер не в сети';
}
else{
	echo 'юзер в сети';
}
return $online;

// 2)
// возвращаем статус занятости для конкретного юзера
\CModule::IncludeModule('voximplant');
$dataUserCall = \Bitrix\Voximplant\Model\CallTable::GetList([
  'select' => ['STATUS'],
  'filter' => ['USER_ID' => $USER]
]);

foreach($dataUserCall as $dataUserCall_item){
  if($dataUserCall_item['STATUS'] !== 'finished'){
	echo 'юзер сейчас разговаривает по телефону';
	return $dataUserCall_item;
  }
}

// 3)
// возвращаем статус перерыв/в работе
$obUser = new \CTimeManUser($ResponsibleID);
return $obUser->State();

// 4)
// возвращаем статусы начала рабочего дня
$obUser = new \CTimeManUser($ResponsibleID);
$arInfo = $obUser->GetCurrentInfo(); 
return $arInfo;*/

/*
CREATE TABLE cm_telegram_mess (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,      
	id_crm_deal VARCHAR(255) NOT NULL,   
	state_send_mess VARCHAR(255) NOT NULL,  
	type_mess VARCHAR(255) NOT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
	id_responsible VARCHAR(255) NOT NULL
)*/