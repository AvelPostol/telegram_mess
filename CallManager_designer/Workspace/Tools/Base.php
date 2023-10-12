<?php
namespace CallCustom\Workspace\Tools;

class Base {

  public function __construct() {
      $this->DB = new \CallCustom\Workspace\DateBaseORM\Crud(); // CREATE / READ / UPDATE / DELETE -> CRUD
      $this->CRM = new \CallCustom\Workspace\Bitrix\CRM();
  }  

  public function writeLog($data) {
      $logFile = '/mnt/data/bitrix/local/php_interface/classes/CallManager/log_'.$data['meta'].time().'.txt';
      $formattedData = var_export($data['body'], true);
      file_put_contents($logFile, '<?php $array = ' . $formattedData . ';', FILE_APPEND);
  }
  
  public function CreateItemsForCheck($p) {
    \CModule::IncludeModule('timeman');
    $result = [];

    // проверяем каждую сделку
    foreach($p['deals'] as $deal){

      if(isset($deal['UF_CRM_1693988021524']) && !empty($deal['UF_CRM_1693988021524']) && !empty($deal['CONTACT_ID'])){

        // время встречи
        $dateTimeObject = $deal['UF_CRM_1693988021524'];
        $data[$deal['ID']]['uf_time'] = $dateTimeObject->format('Y-m-d H:i:s');

        // ID DEAL
        $data[$deal['ID']]['id_crm_deal'] = $deal['ID'];

        // ТИП ЗВОНКА
        $data[$deal['ID']]['type_call'] = 'UNDEFINED';

        if($deal['STAGE_ID'] == 'PREPAYMENT_INVOICE'){
          $data[$deal['ID']]['type_call'] = 'warm';
        }
        if($deal['STAGE_ID'] == 'PREPARATION'){
          $data[$deal['ID']]['type_call'] = 'hot';
        }

        if(isset($deal['CONTACT_ID']) && !empty($deal['CONTACT_ID'])){
          $data[$deal['ID']]['contact_id'] = $deal['CONTACT_ID'];
        }
        else{
          $data[$deal['ID']]['contact_id'] = '';
        }
    
        
        $data[$deal['ID']]['id_responsible'] = $deal['UF_CRM_1694343013'];



        $obUser = new \CTimeManUser($deal['UF_CRM_1694343013']);
       
        $data[$deal['ID']]['breaktime'] = $obUser->State();

        $arInfo = $obUser->GetCurrentInfo(); 
        $data[$deal['ID']]['start_day'] = $arInfo['DATE_START'];
      }

      }

    return $data;

}

public function CheckItemForCall($pool) {

  $items = $pool['items'];
  $ThisTime = $pool['ThisTime'];

  $PullRespUser = [];


  foreach($items as $keyDeal => $item){
    $historyItem = '';
    // история по сделке
    $historyItem = $this->DB->GetHistoryDeal($item);
    $historyItem = $historyItem[0];

    if($historyItem['uf_time'] !== $item['uf_time']){
      $this->DB->ChangeDateDeal($item);
      $historyItem = NULL;
    }

    // проверяем условия
    $timeStateVisit = $this->TimeCallEmet($item);

    if(isset($timeStateVisit['past'])){

      $ItsTime = $this->CheckForCallItsTime(['item' => $item]);

      if($ItsTime['GOCALL']){
        if(!in_array($item['id_responsible'], $PullRespUser)){
          $return[$item['id_crm_deal']] = $item;
          $PullRespUser[] = $item['id_responsible'];
        }
      }
      else{
        $this->DB->CreateAnEntry(['item' => $item, 'special' => 'noneCallTry', 'ItsTime' => $ItsTime]);
      }
      continue;
    }


    // если еще нет записей в бд по этой сделке
    if(!$historyItem){
      // делаем запись
  
      if(isset($timeStateVisit['>24'])){
        $item['STATETIME'] = '>24';
        $this->DB->CreateAnEntry(['item' => $item, 'special' => 'firstEntry']);
      }
      // проверяем установлено ли время встречи менее 24 часов -> в случае чего звонок будет
      if(isset($timeStateVisit['<24'])){
        $item['STATETIME'] = '<24';
        $this->DB->CreateAnEntry(['item' => $item, 'special' => 'nonTargetForFirst']);
      }
      // делаем просто запись в бд, и то что звонка еще не было, в будующем будет звонок
      if(isset($timeStateVisit['>3'])){
        $item['STATETIME'] = '>3';
        $this->DB->CreateAnEntry(['item' => $item, 'special' => 'firstEntry']);
      }
      // 1) проверяем был ли звонок 2) проверяем установлено ли время встречи менее 3 часов -> в случае чего звонок будет
      if(isset($timeStateVisit['<3'])){
        $item['STATETIME'] = '>24';
        $this->DB->CreateAnEntry(['item' => $item, 'special' => 'nonTargetForFirst']);
      }

      // ПРОВЕРКА УСЛОВИЙ ДЛЯ ЗВОНКА, двигаем очередь и делаем звонок
      if(isset($timeStateVisit['3'])){

        $ItsTime = $this->CheckForCallItsTime(['item' => $item]);
        if($ItsTime['GOCALL']){
          if(!in_array($item['id_responsible'], $PullRespUser)){
            $return[$item['id_crm_deal']] = $item;
            $PullRespUser[] = $item['id_responsible'];
          }
        }
        else{
          $this->DB->CreateAnEntry(['item' => $item, 'special' => 'noneCallTry', 'CheckItsTime' => $ItsTime]);
        }

      }
      if(isset($timeStateVisit['24'])){

        $ItsTime = $this->CheckForCallItsTime(['item' => $item]);
        if($ItsTime['GOCALL']){
          if(!in_array($item['id_responsible'], $PullRespUser)){
            $return[$item['id_crm_deal']] = $item;
            $PullRespUser[] = $item['id_responsible'];
          }
        }
        else{
          $this->DB->CreateAnEntry(['item' => $item, 'special' => 'noneCallTry', 'ItsTime' => $ItsTime]);
        }

      }

    }
    else{


        // проверяем был ли звонок, если нет, то ПРОВЕРКА УСЛОВИЙ ДЛЯ ЗВОНКА
        if(isset($timeStateVisit['>3'])){
          $item['STATETIME'] = '<24';
          if($historyItem['status_called'] == 'not yet'){
            $ItsTime = $this->CheckForCallItsTime(['item' => $item, 'historyItem' => $historyItem]);
            if($ItsTime['GOCALL']){
              if(!in_array($item['id_responsible'], $PullRespUser)){
                $return[$item['id_crm_deal']] = $item;
                $PullRespUser[] = $item['id_responsible'];
              }
            }
            else{
              $this->DB->CreateAnEntry(['item' => $item, 'special' => 'noneCallTry', 'ItsTime' => $ItsTime]);
            }
          }
        }
        // проверяем был ли звонок, если нет, то ПРОВЕРКА УСЛОВИЙ ДЛЯ ЗВОНКА
        if(isset($timeStateVisit['<3'])){
          $item['STATETIME'] = '<3';
          if($historyItem['status_called'] == 'not yet'){
   
            $ItsTime = $this->CheckForCallItsTime(['item' => $item, 'historyItem' => $historyItem]);

            if($ItsTime['GOCALL']){
              if(!in_array($item['id_responsible'], $PullRespUser)){
                $return[$item['id_crm_deal']] = $item;
                $PullRespUser[] = $item['id_responsible'];
              }
            }
            else{
              $this->DB->CreateAnEntry(['item' => $item, 'special' => 'noneCallTry', 'ItsTime' => $ItsTime]);
            }
          }
        }

        // ПРОВЕРКА УСЛОВИЙ ДЛЯ ЗВОНКА, двигаем очередь и делаем звонок
        if(isset($timeStateVisit['3'])){
          $item['STATETIME'] = '3';
          $ItsTime = $this->CheckForCallItsTime(['item' => $item, 'historyItem' => $historyItem]);

          if($ItsTime['GOCALL']){
            if(!in_array($item['id_responsible'], $PullRespUser)){
              $return[$item['id_crm_deal']] = $item;
              $PullRespUser[] = $item['id_responsible'];
            }
          }
          else{
            $this->DB->CreateAnEntry(['item' => $item, 'special' => 'noneCallTry', 'ItsTime' => $ItsTime]);
          }

        }
        if(isset($timeStateVisit['24'])){
          $item['STATETIME'] = '24';
          $ItsTime = $this->CheckForCallItsTime(['item' => $item, 'historyItem' => $historyItem]);

          if($ItsTime['GOCALL']){
          if(!in_array($item['id_responsible'], $PullRespUser)){
            $return[$item['id_crm_deal']] = $item;
            $PullRespUser[] = $item['id_responsible'];
          }
        }
          else{
            $this->DB->CreateAnEntry(['item' => $item, 'special' => 'noneCallTry', 'ItsTime' => $ItsTime]);
          }

        }

    }
  }
  
  return $return;
}

public function GoCall($p) {

  foreach($p as $item){

    $number = $this->CRM->GetPhoneContact($item['item']['contact_id']);
    $QueueTable = $this->getQueue();

    $this->DB->CreateAnEntry(['item' => $item['item'], 'special' => 'tryCall']);

    $ClearQueue = $this->ClearQueue($QueueTable['warm']);


    foreach($QueueTable['cold'] as $QueueItem){
      if($item['item']['id_responsible'] == $QueueItem['id_responsible']){
        $this->PushCutQueue(['id_responsible' => $item['item']['id_responsible'], 'QUEUE_ID' => 2, 'delete' => $QueueItem['ID']]);
        return 'true';
      }
    }

    foreach($QueueTable['warm'] as $QueueItem){
      if($item['item']['id_responsible'] == $QueueItem['id_responsible']){
        $this->PushQueue(['id_responsible' => $item['item']['id_responsible'], 'QUEUE_ID' => 2]);
        return 'true';
      }
    }

    $this->PushQueue(['id_responsible' => $item['item']['id_responsible'], 'QUEUE_ID' => 2]);

    return 'true';
  }

}

  public function getQueue() {
    \CModule::IncludeModule('voximplant');

    $QueueTable = \Bitrix\Voximplant\Model\QueueTable::getList([
      'select' => ['*'],
    ]);
  
    foreach($QueueTable as $Qitem){
        $QueueUser = \Bitrix\Voximplant\Model\QueueUserTable::getList([
          'select' => ['*'],
          'filter' => ['QUEUE_ID' => $Qitem['ID']]
        ]);
        foreach($QueueUser as $User){
          if($User['QUEUE_ID'] == $Qitem['ID']){
            $result[$Qitem['NAME']][] = ['id_responsible' => $User['USER_ID'], 'QUEUE_ID' => $User['QUEUE_ID'], 'ID' => $User['ID']];
          }
        }
    }

    return $result;
  }

  public function GetContactNumer($p) {
    foreach($p as $item){
      $cont = $item['contact_id'];
    }
    if (\Bitrix\Main\Loader::IncludeModule("crm")) {
      $ContactInfo = \Bitrix\Crm\ContactTable::GetList([
          'select' => ['PHONE'], 
          'filter' => ['ID' => $cont ]
        ]);
    }
    $Contact = $ContactInfo->fetch();

    $PHONE = $Contact['PHONE'];

    return $PHONE;
  }

  public function PushCutQueue($p) {
    \CModule::IncludeModule('voximplant');
    
    $QueueUser = \Bitrix\Voximplant\Model\QueueUserTable::add([
      'USER_ID' => $p['id_responsible'],
      'QUEUE_ID' => $p['QUEUE_ID']
    ]);
    $QueueUser = \Bitrix\Voximplant\Model\QueueUserTable::delete($p['delete']);
  }

  public function PushQueue($p) {
    \CModule::IncludeModule('voximplant');
    $QueueUser = \Bitrix\Voximplant\Model\QueueUserTable::add([
      'USER_ID' => $p['id_responsible'],
      'QUEUE_ID' => $p['QUEUE_ID']
    ]);
  }

  public function ClearQueue($p) {
    \CModule::IncludeModule('voximplant');

    foreach($p as $item){
      $QueueUser = \Bitrix\Voximplant\Model\QueueUserTable::delete($item['ID']);
    }

  }

  public function TimeState($p) {

      if(isset($p['dealTime']) && !empty($p['thisTime'])){
        if($p['dealTime'] == $p['thisTime']){
          return 'go';
        }
        if($p['dealTime'] < $p['thisTime']){
          return 'missed';
        }
        if($p['dealTime'] > $p['thisTime']){
          return 'not yet';
        }
      }
      else{
        return NULL;
      }
  }
  
  public function CheckCallTable($USER) {
    \CModule::IncludeModule('voximplant');
    $dataUserCall = \Bitrix\Voximplant\Model\CallTable::GetList([
      'select' => ['STATUS'],
      'filter' => ['USER_ID' => $USER]
    ]);
  
    foreach($dataUserCall as $dataUserCall_item){
      if($dataUserCall_item['STATUS'] !== 'finished'){
        return NULL;
      }
    }
  
    return 'NE_ZANAT';

  }
 
  public function CheckForCallItsTime($param) {

      $item = $param['item'];
      $historyItem = $param['historyItem'];

      $stateCall['GOCALL'] = 'true';

      if(($item['type_call'] !== 'warm') && ($item['type_call'] !== 'hot')){
        $stateCall['GOCALL'] = false;
        $stateCall['CALL_ITEM'] = 'NOT_CATEGORY_CALL';
      }
      if($item['breaktime'] == 'CLOSED'){
        $stateCall['DAYSTATE'] = 'CLOSED';
        $stateCall['GOCALL'] = false;
      }
      if($item['breaktime'] == 'PAUSED'){
        $stateCall['DAYSTATE'] = 'PAUSED';
        $stateCall['GOCALL'] = false;
      }

      $statsOnlineCall = $this->CheckCallTable($item['id_responsible']);

      if(!isset($statsOnlineCall) || empty($statsOnlineCall)){
        $stateCall['DAYSTATE'] = 'PAUSED';
        $stateCall['GOCALL'] = false;
      }


      if(isset($item['start_day']) && !empty($item['start_day'])){
        $start_datetime = \DateTime::createFromFormat('d.m.Y H:i:s', $item['start_day']);
        $current_date = new \DateTime();

        if ($start_datetime->format('Y-m-d') !== $current_date->format('Y-m-d')) {
          $stateCall['GOCALL'] = false;
        }
      }
      else{
        $stateCall['DAYSTATE'] = 'NON START DAY';
        $stateCall['GOCALL'] = false;
      }

      if(isset($historyItem)){

        // перенесен, пропущен, совершен, сейчас идет, еще не было, не требуется
        // postponed, missed, completed, currently, not yet, not required
        if(($historyItem['status_called'] == 'not required') || ($historyItem['status_called'] == 'currently') || ($historyItem['status_called'] == 'completed')){
          $stateCall['GOCALL'] = false;
          if(($historyItem['status_called'] == 'not required') || ($historyItem['status_called'] == 'completed')){
            $stateCall['ACTIVE'] = 'NONE';
          }
        }

        if(($historyItem['stoptime'] !== 'not') && !empty($historyItem['stoptime'])){
        // если поставлен счетчик, то провермяем прошло ли время уже
        $start_datetime = \DateTime::createFromFormat('Y-m-d H:i:s', $historyItem['stoptime']);
        $current_datetime = new \DateTime();

        $interval = $current_datetime->diff($start_datetime);
        if ($interval->i <= 5 && $interval->h == 0 && $interval->d == 0 && $interval->m == 0 && $interval->y == 0) {
          $stateCall['GOCALL'] = false;
          $stateCall['COUNT'] = 'NOT_YET';
        }
        }


      }

      // рабочее ли сейчас время
      $current_time = new \DateTime();
      $current_time_formatted = $current_time->format('H:i');

 

      // Проверяем, что текущее время находится в заданном промежутке
      if ($current_time_formatted >= '11:00' && $current_time_formatted <= '20:50') {
      
      } else {
           $stateCall['GOCALL'] = false;
           $stateCall['THISDAY'] = 'HOLIDAY';
      }
              
      // еще проверка занят ли человек на линии !!!!!!!!!!!!
 
      return $stateCall;

  }

  public function TimeCallEmet($item) {
    // Получаем значение объекта DateTime
    $dateTimeString = $item['uf_time'];

    $dateTimeObject = new \DateTime($dateTimeString);
    $currentDateTime = new \DateTime();

    // Отсекаем секунды и миллисекунды
    $dateTimeObject->setTime($dateTimeObject->format('H'), $dateTimeObject->format('i'), 0);
    $currentDateTime->setTime($currentDateTime->format('H'), $currentDateTime->format('i'), 0);

    // Проверяем, прошла ли дата
    if ($dateTimeObject < $currentDateTime) {
        return ['past' => 'yep']; // Дата прошла
    }

    // Вычисляем разницу в минутах
    $interval = $currentDateTime->diff($dateTimeObject);
    $minutesDifference = $interval->days * 24 * 60 + $interval->h * 60 + $interval->i;

    $result = [];

    if ($minutesDifference > 24 * 60) {
        $result['>24'] = 'yep';
    } elseif ($minutesDifference == 24 * 60) {
        $result['24'] = 'yep';
    } elseif ($minutesDifference > 3 * 60) {
        $result['>3'] = 'yep';
    } elseif ($minutesDifference == 3 * 60) {
        $result['3'] = 'yep';
    } elseif ($minutesDifference > 0) {
        $result['<3'] = 'yep';
    }


    return $result;
}




}
