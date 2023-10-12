<?php
namespace CallCustom\Workspace\Tools;

class CreateItemsForCheck {

  public function __construct() {
    $this->DB = new \CallCustom\Workspace\DateBaseORM\Crud();
  }  

    public function GetPull($p) {

        \CModule::IncludeModule('timeman');
        $result = [];
      
        // проверяем каждую сделку
        foreach($p['deals'] as $deal){
            if($this->reauireField($deal)){
                if($this->GetEntityCallType($deal)){

                   /* if($deal['ID'] == 698){*/

                    // timevisit
                    $data[$deal['ID']]['uf_time_visit'] = $this->TimeUFVisit($this->GetEntity($deal, 'TIMEVISIT'));

                    // timetocall
                    $data[$deal['ID']]['uf_time_call'] = $this->TimeUFCall($this->GetEntity($deal, 'TIMETOCALL'));

                    // ID DEAL
                    $data[$deal['ID']]['id_crm_deal'] = $this->IDDeal($this->GetEntity($deal, 'IDDEAL'));

                    // ТИП ЗВОНКА
                    $data[$deal['ID']]['type_call'] = $this->GetEntityCallType($deal);

                    // контакт сделки
                    $data[$deal['ID']]['contact_id'] = $this->ContactID($this->GetEntity($deal, 'CONTACTID'));

                    // ответственный оператор
                    $data[$deal['ID']]['id_responsible'] = $this->ResponsibleID($this->GetEntity($deal, 'RESPID'));

                    // перерыв
                    $data[$deal['ID']]['breaktime'] = $this->WorkManager_Breaktime($this->GetEntity($deal, 'RESPID'));

                    // состояние рабочего дня 
                    $data[$deal['ID']]['start_day'] = $this->WorkManager_start_day($this->GetEntity($deal, 'RESPID'));
                  /*  }*/

                }
                else{
                  
                }
        
            }
        }
        
        return $data;

    }

    public function reauireField($deal) {
  
        if(!empty($deal['CONTACT_ID'])){
            return $deal;
        }
        else{
            return NULL;
        }

    }

    public function GetEntityCallType($data) {

        $query = "SELECT * FROM cm_type_call";
        $result = $this->DB->Get(['request' => $query]);

        foreach($result as $item){
            $key = $item['entity_in_b24'];
            if( $data[$key] == $item['value_entity']){
                $ret[] = $item;
            }
        }
        return $ret;

    }
    
    public function GetEntity($data, $type) {

        $entity = $this->GetDBConfigEntity($type);

        if(!$entity){
            // запись лога что такого типа нет в системе
            return 'NOT TYPE' . $type;
        }

        $check = $this->Check($data[$entity]);
        if($check){
            return $check;
        }
        else{
            return  'NOT VAL FOR ' . $entity;
        }
    }

    public function TimeUFCall($val) {
        if ($val && ('NOT VAL FOR UF_CRM_1693572313224' !== $val)) {
            $dateString = $val->format('Y-m-d H:i:s'); // Получаем строку из Bitrix\Main\Type\DateTime
            $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $dateString);
    
            if ($dateTime !== false && !array_sum($dateTime->getLastErrors())) {
                return $dateTime->format('Y-m-d H:i:s');
            } else {
                return NULL;
            }
        } else {
            return NULL;
        }
    }
    
    public function TimeUFVisit($val) {
        if ($val && ('NOT VAL FOR UF_CRM_1693988021524' !== $val)) {
            $dateString = $val->format('Y-m-d H:i:s'); // Получаем строку из Bitrix\Main\Type\DateTime
            $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $dateString);
    
            if ($dateTime !== false && !array_sum($dateTime->getLastErrors())) {
                return $dateTime->format('Y-m-d H:i:s');
            } else {
                return NULL;
            }
        } else {
            return NULL;
        }
    }
    
    public function IDDeal($data) {
        return $data;
    }

    public function ContactID($ContactID) {
        return $ContactID;
    }

    public function ResponsibleID($ResponsibleID) {
        return $ResponsibleID;
    }

    public function WorkManager_Breaktime($ResponsibleID) {
        $obUser = new \CTimeManUser($ResponsibleID);
        return $obUser->State();
    }

    public function WorkManager_start_day($ResponsibleID) {
        $obUser = new \CTimeManUser($ResponsibleID);
        $arInfo = $obUser->GetCurrentInfo(); 
        return $arInfo['DATE_START'];
    }
    
    public function Check($entityItem) {
        //проверки для сущностей
        if(!isset($entityItem) || empty($entityItem)){
            return NULL;
        }
        return $entityItem;
    }

    public function GetDBConfigEntity($type) {
        
        $query = "SELECT entity_in_b24 FROM cm_field_config WHERE typefield='$type'";
        $result = $this->DB->Get(['request' => $query]);

        return $result[0]['entity_in_b24'];
    }
}

