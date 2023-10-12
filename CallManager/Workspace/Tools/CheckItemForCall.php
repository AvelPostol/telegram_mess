<?php
namespace CallCustom\Workspace\Tools;

class CheckItemForCall {
    
    public function __construct() {
        $this->DB = new \CallCustom\Workspace\DateBaseORM\Crud(); // CREATE / READ / UPDATE / DELETE -> CRUD
        $this->CRM = new \CallCustom\Workspace\Bitrix\CRM();
    }

    public function GetPull($pool) {

        $items = $pool['items'];
        $ThisTime = $pool['ThisTime'];
        $PullRespUser = [];
        
        foreach($items as $keyDeal => $item){

          $historyItem = '';

          // история по сделке - возвращает записи по id сделки
          $historyItem = $this->DB->GetHistoryDeal($item);

          // проверяем условия по времени
          /*
            возвращает статусы
            startCall_for_uf_time_call - пора звонить(назначено время звонка)
            moment - это моментная сделка, нужно звонить как только появилась
            либо время относительно даты назначения встречи, например [>3]
          */
          $timeStateVisit = $this->TimeCallEmet($item);

          print_r(['проверяем сделку' => $item['id_crm_deal']]);

          if($historyItem){
            $result = $this->WidthHistory(['item' => $item, 'historyItem' => $historyItem, 'timeStateVisit' => $timeStateVisit, 'PullRespUser' => $PullRespUser]);
          } else{
            $result = $this->WidthOutHistory(['item' => $item, 'timeStateVisit' => $timeStateVisit, 'PullRespUser' => $PullRespUser]);
          }

          if($result){
            $return[] = $result;
            // передаем очередность по приоритетности
            $PullRespUser = $result['PullRespUser'];
          }


        }

        //$this->DB->closeConnection();
        return $return;
        // тут возвращаются сделки, по которым будет звонок, и только потом отсеиваются те, которые не в приоритете
        
    }

    public function WidthHistory($pool) {
        $item = $pool['item'];
        $historyItem = $pool['historyItem'];
        $timeState = $pool['timeStateVisit'];
        $PullRespUser = $pool['PullRespUser'];
        $return = [];
    
        if (
            (
                isset($historyItem['uf_time_visit']) && !empty($historyItem['uf_time_visit']) &&
                isset($Item['uf_time_visit']) && !empty($Item['uf_time_visit'])
            ) ||
            (
                isset($historyItem['uf_time_call']) && !empty($historyItem['uf_time_call']) &&
                isset($Item['uf_time_call']) && !empty($Item['uf_time_call'])
            )
        ) {
            if ($historyItem['uf_time_visit'] !== $item['uf_time_visit'] || $historyItem['uf_time_call'] !== $item['uf_time_call']) {
                $this->DB->ChangeDateDeal($item);
                return 'CANGEDATE';
            }
        }        

        return $this->processTimeStateVisit($item, $PullRespUser, $timeState, $priority, $historyItem);
    }
    

    public function WidthOutHistory($pool) {

        $item = $pool['item'];
        $timeStateVisit = $pool['timeStateVisit'];
        $PullRespUser = $pool['PullRespUser'];
        $return = [];
        $historyItem = NULL;

        return $this->processTimeStateVisit($item, $PullRespUser, $timeStateVisit, $priority, $historyItem);
    }

    public function processTimeStateVisit($item, &$PullRespUser, $timeState, $priority, $historyItems) {

        foreach ($item['type_call'] as $typeCall_item) {
            $typeCategoryCall = $typeCall_item['id'];
            $typeCall = $typeCall_item['type_call'];
            $priority = $typeCall_item['priority'];
        }

        /*
            startCall_for_uf_time_call - пора звонить(назначено время звонка)
            moment - это моментная сделка, нужно звонить как только появилась
            либо время относительно даты назначения встречи, например [>3]
        */
        $item['STATETIME'] = $timeState;

        
        $historyItem = $historyItems[0];
        // проверяем можно ли звонить
        if(isset($historyItems[0]) && !empty($historyItems[0])){
            // смотрим подходит ли под временные рамки, был ли звонок уже
            /*
                передаются история записей по сделке, чтобы выявить БЫЛ ЛИ ЗВОНОК ПО ЭТОЙ КАТЕГОРИИ
            */
            $ItsTime = $this->CheckForCallItsTime(['item' => $item, 'historyItem' => $historyItems]);
        }
        else{
            $ItsTime = $this->CheckForCallItsTime(['item' => $item]);
        }

        $twenty_four_hour = 'not yet';
        $three_hour = 'not yet';

        
        foreach($historyItems as $historyItems_item){
            if(($historyItems_item['twenty_four_hour'] !== 'not yet') && ($historyItems_item['change_date'] == '0')){
                $twenty_four_hour = 'go';
            }
            if(($historyItems_item['three_hour'] !== 'not yet') && ($historyItems_item['change_date'] == '0')){
                $three_hour = 'go';
            }
        }

        $writelog = 'go';

        if($historyItem){
            
            $special = 'noneCallTry';

            if( $timeState == '>24'){
                $special = 'firstEntry';
                $ItsTime['GOCALL'] = NULL;
            }
            if($timeState == '<24'){
                if($twenty_four_hour !== 'not yet'){
                    $ItsTime['GOCALL'] = NULL;
                    $writelog = NULL;
                }
                else{
                    $special = 'tryCall';
                }
            }
            if($timeState == '24'){
                if($twenty_four_hour !== 'not yet'){
                    $ItsTime['GOCALL'] = NULL;
                    $writelog = NULL;
                }
                else{
                    $special = 'tryCall';
                }
            }

            if( $timeState == '>3'){
                if($twenty_four_hour !== 'not yet'){
                    $ItsTime['GOCALL'] = NULL;
                    $writelog = NULL;
                }
                else{
                    $special = 'tryCall';
                }
            }
            if($timeState == '<3'){
                if($three_hour !== 'not yet'){
                    $ItsTime['GOCALL'] = NULL;
                    $writelog = NULL;
                }
                else{
                    $special = 'tryCall';
                }
            }

            if($timeState == '3'){
                if($three_hour !== 'not yet'){
                    $ItsTime['GOCALL'] = NULL;
                    $writelog = NULL;
                }
                else{
                    $special = 'tryCall';
                }
            }

            if($timeState == 'startCall_for_uf_time_call'){
                if($historyItem['status_called'] !== 'not yet'){
                    $ItsTime['GOCALL'] = NULL;
                    $writelog = NULL;
                }
                else{
                    $special = 'tryCall';
                }
            }
            if($timeState == 'NostartCall_for_uf_time_call'){
                $ItsTime['GOCALL'] = NULL;
                $writelog = NULL;
            }
            if($timeState == 'moment'){
                if($historyItem['status_called'] !== 'not yet'){
                    $ItsTime['GOCALL'] = NULL;
                    $writelog = NULL;
                }
                else{
                    $special = 'tryCall';
                }
            }
        }
        else{
            if(($timeState == '>3') || ($timeState == '<24') || ($timeState == '24')){
                $special = 'skip24';
                $this->DB->CreateAnEntry(['item' => $item, 'special' => $special, 'CheckItsTime' => $ItsTime]);
                $ItsTime['GOCALL'] = NULL;
            }
            if(($timeState == '<3') || ($timeState == '3')){
                $special = 'skip3';
                $this->DB->CreateAnEntry(['item' => $item, 'special' => $special, 'CheckItsTime' => $ItsTime]);
                $ItsTime['GOCALL'] = NULL;
            }
            if($timeState == '>24'){
                $special =  'firstEntry';
                $this->DB->CreateAnEntry(['item' => $item, 'special' => $special, 'CheckItsTime' => $ItsTime]);
                $ItsTime['GOCALL'] = NULL;
            }
            if($timeState == 'startCall_for_uf_time_call'){
                $special =  'firstEntry';
                $this->DB->CreateAnEntry(['item' => $item, 'special' => $special, 'CheckItsTime' => $ItsTime]);
                $ItsTime['GOCALL'] = NULL;
            }
            if($timeState == 'moment'){
                $special = 'tryCall';
            }
            if($timeState == 'NostartCall_for_uf_time_call'){
                $ItsTime['GOCALL'] = NULL;
                $writelog = NULL;
            }
            
        }

        print_r(['состояние времени относительно типа сделки' => $timeState]);
        print_r(['$ItsTime' => $ItsTime]);

        if ($ItsTime['GOCALL']) {
            $subtext = NULL;

            if( $timeState == '>3'){
                $subtext = '24';
                $subtype = 24;
            }
            if($timeState == '24'){
                $subtext = '24';
                $subtype = 24;
            }
            if($timeState == '<24'){
                $subtext = '24';
                $subtype = 24;
            }
            if($timeState == '<3'){
                $subtext = '3';
                $subtype = 3;
            }
            if($timeState == '3'){
                $subtext = '3';
                $subtype = 3;
            }
 
            if($item['type_call'][0]['value_entity'] == 'EXECUTING'){
                if($timeState !== 'past'){
                    $Bot = $this->DB->LetBotBase($item, $subtext,$subtype);
                    if(!$Bot){
                        return;
                    }
                }
         
            }


            print_r([$item['id_responsible'] => 'ОНЛАЙН']);

            if(isset($PullRespUser[$item['id_responsible']])){
                if ($PullRespUser[$item['id_responsible']]['priority'] > $priority) {
                    $return[$item['id_crm_deal']] = [
                        'item' => $item,
                        'priority' => $priority,
                        'resp' => $item['id_responsible'],
                        'CALLSTATE' => $ItsTime,
                        'special' => $special
                    ];
                    print_r(['ДОБАВЛЕНИЕ В return' => 'УСПЕШНО']);
                }
                else{
                    print_r(['ДОБАВЛЕНИЕ В return' => 'не УСПЕШНО']);
                }
            }
            else{
                $return[$item['id_crm_deal']] = [
                    'item' => $item,
                    'priority' => $priority,
                    'resp' => $item['id_responsible'],
                    'CALLSTATE' => $ItsTime,
                    'special' => $special
                ];
                print_r(['ДОБАВЛЕНИЕ В return' => 'УСПЕШНО']);
                $PullRespUser[$item['id_responsible']] = ['priority' => $priority, 'id_crm_deal' => $item['id_crm_deal']];
            }
            print_r(['условия для звонка' => 'подходят']);
        } else {
            print_r(['условия для звонка' => 'не подходят']);
            if($writelog){
                $this->DB->CreateAnEntry(['item' => $item, 'special' => $special, 'CheckItsTime' => $ItsTime]);
            }
            
        }      

        if($PullRespUser && $return){
            return ['PullRespUser' => $PullRespUser, 'return' => $return];
        }
        else{
            return NULL;
        }

        
    }

    public function ClearQueue($p) {
        \CModule::IncludeModule('voximplant');
    
        foreach($p as $item){
          $QueueUser = \Bitrix\Voximplant\Model\QueueUserTable::delete($item['ID']);
        }
    
    }

    public function CheckForCallItsTime($param) {

        $item = $param['item'];
        $historyItems = $param['historyItem'];
    
        $stateCall = ['GOCALL' => 'true'];
    
        // КАТЕГОРИЯ ЗВОНКА
        if (!isset($item['type_call']) || empty($item['type_call'])) {
            $stateCall['GOCALL'] = false;
            $stateCall['CALL_ITEM'] = 'NOT_CATEGORY_CALL';
            $stateCall['EXTRASTATE'][] = ['категория звонка не опознана'];
        }
        else{
            $stateCall['TYPECALL'] = $item['type_call'][0]['type_call'];
        }
    
        // ПРОВЕРКА ПЕРЕРЫВА / РАБОЧЕГО ДНЯ
        if ($item['breaktime'] === 'CLOSED' || $item['breaktime'] === 'PAUSED') {
            $stateCall['DAYSTATE'] = $item['breaktime'];
           // $stateCall['GOCALL'] = false;
            $stateCall['EXTRASTATE'][] = ['менеджер сейчас на перерыве или закрыл день'];
        }
    
        // ЗАНЯТОСТЬ НА ЛИНИИ
        $statsOnlineCall = $this->CheckCallTable($item['id_responsible']);
        if (isset($statsOnlineCall) || !empty($statsOnlineCall)) {

        }
        else{
            $stateCall['DAYSTATE'] = 'PAUSED';
            $stateCall['GOCALL'] = false;
            $stateCall['EXTRASTATE'][] = ['менеджер сейчас занят на линии'];
        }
    
        // НАЧАЛ ЛИ РАБОЧИЙ ДЕНЬ СЕГОДНЯ(!)
        if (!isset($item['start_day']) || empty($item['start_day'])) {
            $stateCall['DAYSTATE'] = 'NON START DAY';
            //$stateCall['GOCALL'] = false;
            $stateCall['EXTRASTATE'][] = ['день не начат'];
        } else {
            $start_datetime = \DateTime::createFromFormat('d.m.Y H:i:s', $item['start_day']);
            $current_date = new \DateTime();
    
            if ($start_datetime->format('Y-m-d') !== $current_date->format('Y-m-d')) {
               // $stateCall['GOCALL'] = false;
                $stateCall['EXTRASTATE'][] = ['день начался не сегодня'];
            }
        }

        $st_call = ['not required', 'currently', 'completed', 'called'];
    
        if (isset($historyItems)) {
            $historyItem = $historyItems[0];

            // $historyItem['category_call'] !== 
            foreach($st_call as $st_call_item){
                if($historyItem['status_called'] == $st_call_item){
                    $stateCall['GOCALL'] = false;
                    $stateCall['EXTRASTATE'][] = ['вызов совершен или не требуется'];
                }
            }

            // СМОТРИМ ПРОШЕЛ ЛИ ПЕРЕРЫВ
            if ($historyItem['stoptime'] !== 'not' && !empty($historyItem['stoptime'])) {
                $start_datetime = \DateTime::createFromFormat('Y-m-d H:i:s', $historyItem['stoptime']);
                $current_datetime = new \DateTime();
    
                $interval = $current_datetime->diff($start_datetime);
                if ($interval->i <= 5 && $interval->h == 0 && $interval->d == 0 && $interval->m == 0 && $interval->y == 0) {
                    $stateCall['GOCALL'] = false;
                    $stateCall['COUNT'] = 'NOT_YET';
                    $stateCall['EXTRASTATE'][] = ['перерыв не прошел'];
                }
            }
        }
        // РАБОЧИЕ ЧАСЫ
        $current_time = new \DateTime();
        $current_time_formatted = $current_time->format('H:i');
        if ($item['type_call'][0]['PRIMETYPE'] == 'warm') {
            $start_time = new \DateTime('11:00');
            $end_time = new \DateTime('21:50');
            if ($current_time < $start_time || $current_time > $end_time) {
                $stateCall['GOCALL'] = false;
                $stateCall['THISDAY'] = 'HOLIDAY';
                $stateCall['EXTRASTATE'][] = 'HOLIDAY FOR WARM';
                print_r(['состояние рабочего дня' => $current_time_formatted]);
            }
        } else {
            $start_time = new \DateTime('9:00');
            $end_time = new \DateTime('20:50');
            if ($current_time < $start_time || $current_time > $end_time) {
                $stateCall['GOCALL'] = false;
                $stateCall['THISDAY'] = 'HOLIDAY';
                $stateCall['EXTRASTATE'][] = 'HOLIDAY FOR HOT';
                print_r(['состояние рабочего дня' => $current_time_formatted]);
            }
        }

        return $stateCall;
    }
    
    public function CheckCallTable($USER) {
        \CModule::IncludeModule('voximplant');
        $dataUserCall = \Bitrix\Voximplant\Model\CallTable::GetList([
          'select' => ['STATUS'],
          'filter' => ['USER_ID' => $USER]
        ]);
      
        foreach($dataUserCall as $dataUserCall_item){
          if(($dataUserCall_item['STATUS'] !== 'finished') && (isset($dataUserCall_item['STATUS']) || !empty($dataUserCall_item['STATUS']))){
            return NULL;
          }
        }
        
        return 'NE_ZANAT';
    
    }

    public function TimeCallEmet($item) {

        if(isset($item['uf_time_visit']) && !empty($item['uf_time_visit'])){
            // Получаем значение объекта DateTime
            $dateTimeString = $item['uf_time_visit'];
            
            $dateTimeObject = new \DateTime($dateTimeString);
            $currentDateTime = new \DateTime();
        
            // Отсекаем секунды и миллисекунды
            $dateTimeObject->setTime($dateTimeObject->format('H'), $dateTimeObject->format('i'), 0);
            $currentDateTime->setTime($currentDateTime->format('H'), $currentDateTime->format('i'), 0);
        
            // Проверяем, прошла ли дата
            if ($dateTimeObject < $currentDateTime) {
                return 'past'; // Дата прошла
            }
        }
        else{
            if(isset($item['uf_time_call']) && !empty($item['uf_time_call'])){
                // Получаем значение объекта DateTime
                $dateTimeString = $item['uf_time_call'];
                    
                $dateTimeObject = new \DateTime($dateTimeString);
                $currentDateTime = new \DateTime();
    
                // Отсекаем секунды и миллисекунды
                $dateTimeObject->setTime($dateTimeObject->format('H'), $dateTimeObject->format('i'), 0);
                $currentDateTime->setTime($currentDateTime->format('H'), $currentDateTime->format('i'), 0);
    
                if ($dateTimeObject == $currentDateTime) {
                    return 'startCall_for_uf_time_call';
                }
                if ($dateTimeObject < $currentDateTime) {
                    return 'startCall_for_uf_time_call';
                }
                if ($dateTimeObject > $currentDateTime) {
                    return 'NostartCall_for_uf_time_call';
                }
            }
        }

   

        foreach ($item['type_call'] as $typeCall_item) {
            $typeCategoryCall = $typeCall_item['id'];
            $typeCall = $typeCall_item['type_call'];
            $priority = $typeCall_item['priority'];
        }

        if($typeCall == 'moment'){
            return 'moment';
        }

    
        // Вычисляем разницу в минутах
        $interval = $currentDateTime->diff($dateTimeObject);
        $minutesDifference = $interval->days * 24 * 60 + $interval->h * 60 + $interval->i;
    
        $result = [];
    
        if ($minutesDifference > 24 * 60) {
            $result = '>24';
        } elseif ($minutesDifference == 24 * 60) {
            $result = '24';
        } elseif ($minutesDifference > 3 * 60) {
            $result = '<24';
        } elseif ($minutesDifference == 3 * 60) {
            $result = '3';
        } elseif ($minutesDifference > 0) {
            $result = '<3';
        }
    
    
        return $result;
    }

    

    public function CheckPriority($all) {

        foreach($all as $checkCall_list){
            $PullRespUser = $checkCall_list['PullRespUser'];

            foreach($checkCall_list['return'] as $key => $item){
      
                foreach($PullRespUser as $key_i => $val_i){
                    if($item['item']['id_responsible'] == $key_i){
                        if($item['CALLSTATE']['GOCALL'] == 1){
                            if($item['item']['type_call'][0]['type_call'] == 'extramoment'){
                                print_r(['это extramoment звонок']);
                                print_r([$item['item']['STATETIME']]);
                                if($item['item']['STATETIME'] == 'startCall_for_uf_time_call'){

                                    $id_crm_deal = $item['item']['id_crm_deal'];

                                    $managerQuery = "SELECT * FROM cm_history 
                                    WHERE id_crm_deal='$id_crm_deal' 
                                    AND change_date='0' 
                                    ORDER BY created_at DESC";
                        
                                    $managerResult = $this->DB->Get(['request' => $managerQuery]);

                                    $none = '';
                                    foreach($managerResult as $managerResult_item){
                                        if($managerResult_item['status_called'] == 'called'){
                                            $none = 'none';
                                        }
                                    }

                                    if($none !== 'none'){
                                        $result[] = $item;
                                        $this->DB->CreateAnEntry(['item' => $item['item'], 'special' => 'called', 'CheckItsTime' => $item['CALLSTATE'], 'state' => 'extramoment']);
                                    }
                                    else{
                                        print_r(['звонок уже был']);
                                    }
                                }
                            }
                            if($item['item']['type_call'][0]['type_call'] == 'wait'){
                                print_r(['это wait звонок']);
                                $this->DB->CreateAnEntry(['item' => $item['item'], 'special' => $item['special'], 'CheckItsTime' => $item['CALLSTATE']]);
                                $result[] = $item;
                            }
                            if($item['item']['type_call'][0]['type_call'] == 'moment'){

                                print_r(['это моментный звонок']);

                                    $id_crm_deal = $item['item']['id_crm_deal'];

                                    $managerQuery = "SELECT * FROM cm_history 
                                    WHERE id_crm_deal='$id_crm_deal' 
                                    AND change_date='0' 
                                    ORDER BY created_at DESC";
                        
                                    $managerResult = $this->DB->Get(['request' => $managerQuery]);

                                    $none = '';
                                    foreach($managerResult as $managerResult_item){
                                        if($managerResult_item['status_called'] == 'called'){
                                            $none = 'none';
                                        }
                                    }

                                    if($none !== 'none'){
                                        $result[] = $item;
                                        $this->DB->CreateAnEntry(['item' => $item['item'], 'special' => 'called', 'CheckItsTime' => $item['CALLSTATE']]);
                                    }
                                    else{
                                        print_r(['звонок уже был']);
                                    }
                            }
                        }
                    }
                }
            }
        }
       

        return $result;
    }

    public function GoCall($p) {

        foreach($p as $item){
          $number = $this->CRM->GetPhoneContact($item['contact_id']);
          $QueueTable = $this->getQueue();
      
        //  $this->DB->CreateAnEntry(['item' => $item, 'special' => 'tryCall']);
      
          $ClearQueue = $this->ClearQueue($QueueTable['warm']);
      
          foreach($QueueTable['cold'] as $QueueItem){
            if($item['id_responsible'] == $QueueItem['id_responsible']){
              $this->PushCutQueue(['id_responsible' => $item['id_responsible'], 'QUEUE_ID' => 2, 'delete' => $QueueItem['ID']]);
              return 'true';
            }
          }
      
          foreach($QueueTable['warm'] as $QueueItem){
            if($item['id_responsible'] == $QueueItem['id_responsible']){
              $this->PushQueue(['id_responsible' => $item['id_responsible'], 'QUEUE_ID' => 2]);
              return 'true';
            }
          }
      
          $this->PushQueue(['id_responsible' => $item['id_responsible'], 'QUEUE_ID' => 2]);
      
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

         $cont = $p['item']['contact_id'];

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
    
    
}