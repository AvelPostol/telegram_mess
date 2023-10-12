<?php

/*
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS',true);

$_SERVER["DOCUMENT_ROOT"] = "/mnt/data/bitrix";
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

\CModule::IncludeModule('voximplant');*/
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS',true);

$_SERVER["DOCUMENT_ROOT"] = "/mnt/data/bitrix";
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");
$post = $_REQUEST;

$logFile = '/mnt/data/bitrix/local/php_interface/classes/CallManager/log_telefony.txt';
$formattedData = var_export($post, true);
file_put_contents($logFile, '<?php $array = ' . $formattedData . ';', FILE_APPEND);

class Call {

    public function Post($phone) {
      
    $js_auth = [
        "cmd" => "makeCall",
        "phone" => "+79111391688",
        "user" => "123",
        "params" => [
            "crm_user_id" => 1911,
            "dst" => $phone
        ]
    ];
  
    $url = 'https://vats733283.megapbx.rusys/crm_api.wcgp/';
    $auth_headers = [
        'Content-Type: application/json',
        'Authorization: Bearer 802fc0eb-11aa-4246-947d-1e08008ea9d8'
    ];
  
    $curl = curl_init($url);
    
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($js_auth));
    curl_setopt($curl, CURLOPT_HTTPHEADER, $auth_headers);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($curl);
    $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
    curl_close($curl);
    
    echo $status_code . "\n";
    echo $response . "\n";
  
  return $decodedResponse ?? null; // Возвращаем разобранный JSON или null в случае ошибки
  
      
  }
}  

/*
POST https://domain/sys/crm_api.wcgp
cmd=makeCall
- 12/18 -
phone=79101234567
user=andy
token=5f317b9f-e86c-41f7-a6fc-c76eb0da0000*/
