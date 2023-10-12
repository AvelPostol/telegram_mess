<?php
namespace CallCustom\Workspace\DateBaseORM;

class Crud {

    private $db;
    private $user;
    private $pass;
    private $mysqli;
    
    public function __construct() {

        $this->db = 'call_manager';
        $this->user = 'python';
        $this->pass = 'Deep1993';
        $this->port = '30305';
        $this->mysqli = new \mysqli("10.178.200.13", $this->user, $this->pass, $this->db, $this->port);
        
        if ($this->mysqli->connect_error) {
            die("Ошибка подключения: " . $this->mysqli->connect_error);
        }
    }

    public function __destruct() {
        // Закрываем соединение при уничтожении объекта
        $this->mysqli->close();
    }
    
    public function Get($p) {
        $query = $p['request'];

        $result = $this->mysqli->query($query);

        if (!$result) {
            echo "Ошибка выполнения запроса: (" . $this->mysqli->errno . ") " . $this->mysqli->error;
            return false;
        }

        $data = [];

        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        $result->free();

        return $data;
    }

    public function closeConnection() {
        if ($this->mysqli) {
            $this->mysqli->close();
        }
    }

    /*
    id BIGINT AUTO_INCREMENT PRIMARY KEY,               ***
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,     ***
    id_responsible INT NOT NULL,                        ***
	contact_id VARCHAR(255) NOT NULL,                   ***
    uf_time DATETIME NOT NULL,                          ***
    id_crm_deal INT NOT NULL,                           ***
	type_call VARCHAR(255) NOT NULL,                    ***

	is_active TINYINT(1) DEFAULT 1,                     ***

	change_date TINYINT(1) DEFAULT 0,                   ***
	
	twenty_four_hour VARCHAR(255) NOT NULL DEFAULT 'not yet',     ***
	three_hour  VARCHAR(255) NOT NULL DEFAULT 'not yet',          ***

	status_called VARCHAR(255) NOT NULL DEFAULT 'not yet',         ***
	breaktime TINYINT(1) DEFAULT 0,                             
	start_day TINYINT(1) DEFAULT 0,                              
    stoptime VARCHAR(255) NOT NULL DEFAULT 'not'    
    */


    
    
    public function LetBotBase($pool, $subtext,$subtype) {
        GLOBAL $USER;
        $online = \CUser::IsOnLine($pool['id_responsible']);

        //print_r(['$pool' => $pool]);
        /*if(!$online){*/
            if($pool['type_call'][0]['value_entity'] == 'EXECUTING'){

                    $id_crm_deal = $pool['id_crm_deal'];

                    $rsUsers = \Bitrix\Main\UserTable::GetList([
                        'select' => ['UF_TELEGRAM_ID'],
                        'filter' => ['ID' => $pool['id_responsible']]
                    ])->fetch();
                
                    $id_tg = $rsUsers['UF_TELEGRAM_ID'];
 
                    $id_responsible = $pool['id_responsible'];

                   // Запрос для выбора менеджера
                    $managerQuery = "SELECT * FROM cm_telegram_mess
                    WHERE id_crm_deal='$id_crm_deal' 
                    AND id_responsible='$id_responsible' 
                    AND type_mess='$subtype' 
                    ORDER BY created_at DESC 
                    LIMIT 1";

                    $managerResult = $this->Get(['request' => $managerQuery]);

                    if(!$managerResult){
                        $this->LetBot($pool, $subtext, $id_tg);
                        $data = [
                            'id_crm_deal' => $id_crm_deal,
                            'id_responsible' => $id_responsible, 
                            'type_mess' => $subtype,
                            'state_send_mess' => 'send'
                        ];

                        $this->syncDataWithDatabase(['data' => [$data], 'table_name' => 'cm_telegram_mess']);
                        print_r(['отправили в телегу']);
                    }

                    return NULL;
                
                
            }
            return NULL;
       /* }
        else{
            return 'online';
        }*/
       
    }

    public function LetBot($pool, $subtext, $id_tg) {

        \CModule::IncludeModule('crm'); 

        $ContactInfo = \Bitrix\Crm\ContactTable::GetList([
            'select' => ['*', 'PHONE', 'ADDRESS'], 
            'filter' => [ 'ID' => $pool['contact_id'] ]
        ]);
        
        $Contact = $ContactInfo->fetch();
        
        if(isset($Contact) && !empty($Contact)){
            $CID = $Contact['NAME'];
        }

        if($subtext == '24'){
           $message_text = "Необходимо подтвердить встречу с ".$CID." по сделке " .$pool['id_crm_deal']. " за 24 часа ". $url;
        }

        if($subtext == '3'){
            $message_text = "Срочно подтвердить встречу с ".$CID." по сделке " .$pool['id_crm_deal']. " за 3 часа ". $url;
        }

        $url = "https://bx24.kitchenbecker.ru/crm/deal/details/".$pool['id_crm_deal']."/";

        $botToken = '6275540383:AAFGqM2s37wMwNsAoBCn0BF6h61k57Q_A6A';

        $apiUrl = "https://api.telegram.org/bot{$botToken}/sendMessage";
        
        $data = [
            'chat_id' => $id_tg,
            'text' => $message_text,
        ];

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        
        if ($response === false) {
            die('Ошибка отправки запроса: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        $responseData = json_decode($response, true);
        
        if ($responseData['ok'] !== true) {
            die('Ошибка при отправке сообщения: ' . $responseData['description']);
        }
        
        echo 'Сообщение успешно отправлено!';
    }

    
    public function CreateAnEntry($p) {
   

        
        $item = $p['item'];
        $CheckItsTime = $p['CheckItsTime'];

        $categoryCall = $p['item']['type_call'][0]['id'];
    
        if (isset($item['id_responsible']) && !empty($item['id_responsible'])) {
            $data['id_responsible'] = $item['id_responsible'];
        } else {
            $data['id_responsible'] = 'none';
        }
    
        $data['contact_id'] = $item['contact_id'];
        $data['uf_time'] = '';
        
        if(isset($item['uf_time_call']) && !empty($item['uf_time_call'])){
            $data['uf_time'] = $item['uf_time_call'];
        }
        if(isset($item['uf_time_visit']) && !empty($item['uf_time_visit'])){
            $data['uf_time'] = $item['uf_time_visit'];
        }

        $data['id_crm_deal'] = $item['id_crm_deal'];
    
        $typeCall = $p['item']['type_call'][0]['type_call'];
        $data['type_call'] = $typeCall;
        $data['category_call'] = $p['item']['type_call'][0]['id'];
    
        // Устанавливаем значения по умолчанию
        $data['twenty_four_hour'] = 'not yet';
        $data['three_hour'] = 'not yet';
        $data['status_called'] = 'not yet';
    
        $data['breaktime'] = $item['breaktime'];
    
        if (isset($item['start_day']) && !empty($item['start_day'])) {
            $data['start_day'] = $item['start_day'];
        } else {
            $data['start_day'] = 'none';
        }

        $data['change_date'] = '0';

        if($CheckItsTime['DAYSTATE'] == 'PAUSED'){
            $currentDateTime = new \DateTime();
            $currentDateTimeFormatted = $currentDateTime->format('Y-m-d H:i:s');
            $data['stoptime'] = $currentDateTimeFormatted;
        }
        if($CheckItsTime['DAYSTATE'] == 'NON START DAY'){
            $currentDateTime = new \DateTime();
            $currentDateTimeFormatted = $currentDateTime->format('Y-m-d H:i:s');
            $data['stoptime'] = $currentDateTimeFormatted;
        }

        if (isset($p['special'])) {
            switch ($p['special']) {
                case 'skip24':
                    $data['twenty_four_hour'] = 'try';
                    break;
                case 'skip3':
                    $data['three_hour'] = 'try';
                    break;
                case 'called':
                    $data['three_hour'] = 'try';
                    $data['twenty_four_hour'] = 'try';
                    $data['status_called'] = 'called';
                    break;
                case 'firstEntry':
                    $data['is_active'] = '1';
                    break;
                case 'noneCallTry':
                    $currentDateTime = new \DateTime();
                    $currentDateTimeFormatted = $currentDateTime->format('Y-m-d H:i:s');
                    $data['stoptime'] = $currentDateTimeFormatted;
                    break;
                case 'tryCall':
    
                    if (in_array($item['STATETIME'], ['<3', '3'])) {
                        $data['three_hour'] = 'try';
                        $data['twenty_four_hour'] = 'try';
                    }
    
                    if (in_array($item['STATETIME'], ['<24', '24'])) {
                        $data['twenty_four_hour'] = 'try';
                        $data['is_active'] = '0';
                    }
            
                    break;
                case 'past':
                    $data['is_active'] = '0';
                    $data['status_called'] = 'try';
                    $data['three_hour'] = 'try';
                    break;
            }
        }

        $remout = $this->GetDoubleDeal($data);
       

        if(!$remout){
            print_r(['syncDataWithDatabase' => $data]);

            $this->syncDataWithDatabase(['data' => [$data], 'table_name' => 'cm_history']);
        }

   

    }
    
    
    
    public function ChangeDateDeal($item) {
        $mysqli = $this->mysqli;
        $id_crm_deal = $item['id_crm_deal'];
        $query = "UPDATE cm_history SET change_date = '1' WHERE id_crm_deal = $id_crm_deal";
        $result = $this->mysqli->query($query);
        
    }

    public function syncDataWithDatabase($p) {
      

        $mysqli = $this->mysqli;
        $table_name = $p['table_name'];
    
        $values = [];
    
        foreach ($p['data'] as $item) {
            $escapedValues = array_map(function ($value) use ($mysqli) {
               
                return "'" . $mysqli->real_escape_string($value) . "'";
            }, $item);
    
            $values[] = '(' . implode(',', $escapedValues) . ')';
        }
    
        $fields = array_keys($p['data'][0]);
        $query = "INSERT INTO $table_name (" . implode(',', $fields) . ") VALUES " . implode(',', $values);
        $stmt = $mysqli->prepare($query);
    
        if (!$stmt) {
            echo "Ошибка подготовки запроса: (" . $mysqli->errno . ") " . $mysqli->error;
            exit();
        }
    
        $stmt->execute();
    }

    public function GetDoubleDeal($p) {

        try{

          $id_crm_deal = $p['id_crm_deal'];
          $id_responsible = $p['id_responsible'];
          $contact_id = $p['contact_id'];
          $type_call = $p['type_call'];
          $category_call = $p['category_call'];
          $twenty_four_hour = $p['twenty_four_hour'];
          $three_hour = $p['three_hour'];
          $breaktime = $p['breaktime'];
          $stoptime = $p['stoptime'];
          $status_called = $p['status_called'];


          // Запрос для выбора менеджера
          $managerQuery = "SELECT * FROM cm_history 
            WHERE id_crm_deal='$id_crm_deal' 
            AND id_responsible='$id_responsible' 
            AND contact_id='$contact_id' 
            AND type_call='$type_call' 
            AND category_call='$category_call' 
            AND twenty_four_hour='$twenty_four_hour' 
            AND status_called='$status_called'
            AND three_hour='$three_hour' 
            AND breaktime='$breaktime' 
            ORDER BY created_at DESC 
            LIMIT 1";

          $managerResult = $this->Get(['request' => $managerQuery]);

          if (!$managerResult) {
             // $this->Base->writeLog(['body' => 'Ошибка при поиске в БД', 'meta' => 'DB_EXCEPT']);
              return null;
          }

          return $managerResult;
    
        } catch (\Exception $e) { 
            // Обработка ошибок
          //  $this->Base->writeLog(['body' => 'Ошибка: ' . $e->getMessage(), 'meta' => 'DB_BAZIS']);
            return null; 
        }
          
    }

    public function GetHistoryDeal($p) {

        try{
  
          $prefix = $p['id_crm_deal'];
          
          // Запрос для выбора менеджера
          $managerQuery = "SELECT * FROM cm_history WHERE id_crm_deal='$prefix' ORDER BY created_at DESC";
          $managerResult = $this->Get(['request' => $managerQuery]);
    
          if (!$managerResult) {
             // $this->Base->writeLog(['body' => 'Ошибка при поиске в БД', 'meta' => 'DB_EXCEPT']);
              return null;
          }
    
          return $managerResult;
    
        } catch (\Exception $e) { 
            // Обработка ошибок
          //  $this->Base->writeLog(['body' => 'Ошибка: ' . $e->getMessage(), 'meta' => 'DB_BAZIS']);
            return null; 
        }
          
    }
}