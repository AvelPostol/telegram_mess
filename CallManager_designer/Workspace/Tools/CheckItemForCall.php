<?php
namespace CallCustom\Workspace\Tools;

class CheckItemForCall {
    
    public function __construct() {
        $this->DB = new \CallCustom\Workspace\DateBaseORM\Crud(); // CREATE / READ / UPDATE / DELETE -> CRUD
        $this->CRM = new \CallCustom\Workspace\Bitrix\CRM();
    }

    public function CanPush($item,$historyItem) {

        if($item['stage_id'] == 'Встреча принята'){
            if($item['STATE'] == '>15'){
                $data['status_mess_moment_prinata'] = 'yet';
            }
            else{
                $data['minutes_mess'] = 'yet';
            }
        }
        if($item['stage_id'] == 'Встреча подтверждена'){
            $data['status_mess_moment_podtver'] = 'yet';
        }

        foreach($data as $key => $data_item){
            foreach($historyItem as $historyItem_id){
                if ($historyItem_id['uf_time'] !== $item['uf_time_visit']) {
                    
                    echo '<PRE>';
                    print_r(['меняем время']);
                    echo '</PRE>';
                    $this->DB->ChangeDateDeal($item);
                    return 'validated';
                }
                if($historyItem_id['id_responsible'] !== $item['id_responsible']){
                    echo '<PRE>';
                    print_r(['меняем ответственного']);
                    echo '</PRE>';
                    $this->DB->ChangeDateDeal($item);
                    return 'validated';
                }
                if($historyItem_id['change_date'] == 0){
                    if($historyItem_id[$key] == $data_item){
                        return NULL;
                    }
                }
            }
        }

        return 'validated';
    }

    public function GetPull($pool) {

        $items = $pool['items'];
        $ThisTime = $pool['ThisTime'];
        $PullRespUser = [];
        
        foreach($items as $keyDeal => $item){

         if(isset($pool['force'])){
            $result = $this->WidthOutHistory(['item' => $item, 'timeStateVisit' => $timeStateVisit, 'force' => 'force']);
            die();
         }

          $historyItem = NULL;

          // история по сделке - возвращает записи по id сделки
          $historyItem = $this->DB->GetHistoryDeal($item);
          
          $timeStateVisit = $this->TimeCallEmet($item);

          echo '<PRE>';
          print_r(['проверяем сделку' => $item['id_crm_deal']]);
          echo '</PRE>';
          $item['STATE'] = $timeStateVisit;

          echo '<PRE>';
          print_r(['$timeStateVisit' => $timeStateVisit]);
          echo '</PRE>';

          $dealCan = $this->CanPush($item,$historyItem);

          if($dealCan){

            if($historyItem){
                $result = $this->WidthHistory(['item' => $item, 'historyItem' => $historyItem, 'timeStateVisit' => $timeStateVisit]);
            } else{
                $result = $this->WidthOutHistory(['item' => $item, 'timeStateVisit' => $timeStateVisit]);
            }

          }

        }
    }

    public function WidthHistory($pool) {
        $item = $pool['item'];
        $historyItem = $pool['historyItem'];
        $timeState = $pool['timeStateVisit'];
        $return = [];
    
        if (
            isset($historyItem['uf_time_visit']) && !empty($historyItem['uf_time_visit']) &&
            isset($Item['uf_time_visit']) && !empty($Item['uf_time_visit'])
        ) {
            if ($historyItem['uf_time_visit'] !== $item['uf_time_visit']) {
                echo '<PRE>';
                print_r(['меняем время']);
                echo '</PRE>';
                $this->DB->ChangeDateDeal($item);
                return 'CANGEDATE';
            }
        }        

        return $this->processTimeStateVisit($item, $timeState, $historyItem);
    }
    

    public function WidthOutHistory($pool) {

        $item = $pool['item'];
        $timeStateVisit = $pool['timeStateVisit'];
        $return = [];
        $historyItem = NULL;
        if(isset($pool['force'])){
            $item['force'] = 'force';
        }
        return $this->processTimeStateVisit($item, $timeStateVisit, $historyItem);
    }

    public function processTimeStateVisit($item, $timeState, $historyItems) {

        $item['STATE'] = $timeState;
        $subtype = NULL;

        if(isset($item['force'])){
            echo '<PRE>';
            print_r(['принудительная отправка боту в телеграмм']);
            echo '</PRE>';
            $this->DB->LetBotBase($item, $subtext, $subtype);
        }
       
        if($item['stage_id'] == 'Встреча подтверждена'){
            echo '<PRE>';
            print_r(['отправка боту Встреча подтверждена']);
            echo '</PRE>';
            $this->DB->LetBotBase($item, $subtext, $subtype);
        }
        if($item['stage_id'] == 'Встреча принята'){
            if($timeState == '>15'){
                echo '<PRE>';
                print_r(['отправка боту Встреча принята, но до 15 мин еще далеко']);
                echo '</PRE>';
                $this->DB->LetBotBase($item, $subtext, $subtype);
            }
            if(($timeState == '<15') || ($timeState == '15')){
                echo '<PRE>';
                print_r(['отправка боту Встреча принята, за 15 мин']);
                echo '</PRE>';
      
                if($timeState == '15'){
                    $subtext = '15';
                    $subtype = 15;
                }
                if($timeState == '<15'){
                    $subtext = '15';
                    $subtype = 15;
                }
                if($subtype){
                $this->DB->LetBotBase($item, $subtext, $subtype);
                }
            }
        }

        $this->DB->CreateAnEntry(['item' => $item]);
        
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

        $result = 'NONE';

        if($item['stage_id'] == 'Встреча подтверждена'){
            $result = 'moment_podtver';
        }

        if ($item['stage_id'] == 'Встреча принята') {
            if (isset($item['uf_time_visit']) && !empty($item['uf_time_visit'])) {
                $dateTimeString = $item['uf_time_visit'];
                $dateTimeObject = new \DateTime($dateTimeString);
                $currentDateTime = new \DateTime();
        
                // Отсекаем секунды и миллисекунды
                $dateTimeObject->setTime($dateTimeObject->format('H'), $dateTimeObject->format('i'), 0);
                $currentDateTime->setTime($currentDateTime->format('H'), $currentDateTime->format('i'), 0);
        
                // Вычисляем разницу в минутах
                $interval = $currentDateTime->diff($dateTimeObject);
                $minutesDifference = $interval->days * 24 * 60 + $interval->h * 60 + $interval->i;
        
                // Проверяем, прошла ли дата
                if ($dateTimeObject < $currentDateTime) {
                    return 'past'; // Дата прошла
                }

                if ($minutesDifference > 15) {
                    $result = '>15';
                } elseif ($minutesDifference < 15) {
                    $result = '<15';
                } else {
                    $result = '15';
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
    
}