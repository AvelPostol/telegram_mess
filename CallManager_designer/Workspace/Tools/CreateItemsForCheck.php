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

                if(($deal['CATEGORY_ID'] == 11) || ($deal['CATEGORY_ID'] == 12)){

                    if(($deal['STAGE_ID'] == 'C11:UC_PVWFY0') || ($deal['STAGE_ID'] == 'C11:NEW') || ($deal['STAGE_ID'] == 'C12:PREPARATION') || ($deal['STAGE_ID'] == 'C12:PREPAYMENT_INVOIC')){
                    // timevisit
                    $data[$deal['ID']]['uf_time_visit'] = $this->TimeUFVisit($this->GetEntity($deal, 'TIMEVISIT'));

                    // ID DEAL
                    $data[$deal['ID']]['id_crm_deal'] = $this->IDDeal($this->GetEntity($deal, 'IDDEAL'));

                    // контакт сделки
                    $data[$deal['ID']]['contact_id'] = $this->ContactID($this->GetEntity($deal, 'CONTACTID'));

                    // ответственный оператор
                    $data[$deal['ID']]['id_responsible'] = $this->ResponsibleID($this->GetEntity($deal, 'RESPID'));

                    if(isset($deal['UF_CRM_1693585313113']) && !empty($deal['UF_CRM_1693585313113'])){
                        $adr = json_decode($deal['UF_CRM_1693585313113']);
                        $fullAddress = $adr->address;
                        $data[$deal['ID']]['adress'] = $fullAddress;
                    }

                    
                    $data[$deal['ID']]['numerKravt'] = $deal['UF_CRM_1696584638945'];
                    
                    $data[$deal['ID']]['numerDogovor'] = $deal['UF_CRM_1694018792723'];

                    $data[$deal['ID']]['link'] = $deal['UF_CRM_1694160451992'];
                    if(($deal['STAGE_ID'] == 'C11:UC_PVWFY0') || ($deal['STAGE_ID'] == 'C12:PREPARATION')){
                        $data[$deal['ID']]['stage_id'] = 'Встреча подтверждена';
                    }
                    else{
                        $data[$deal['ID']]['stage_id'] = 'Встреча принята';
                    }

                    if(isset($deal['UF_CRM_1693988021524']) && !empty($deal['UF_CRM_1693485339146'])){
                        $data[$deal['ID']]['status_spec'] = 'time';
                    }
                    else{
                        $data[$deal['ID']]['status_spec'] = 'moment';
                    }
                    
                    }
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

