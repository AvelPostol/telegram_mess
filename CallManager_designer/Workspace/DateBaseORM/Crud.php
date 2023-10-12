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

    
    public function LetBotBase($pool, $subtext, $subtype) {

        $rsUsers = \Bitrix\Main\UserTable::GetList([
            'select' => ['UF_TELEGRAM_ID'],
            'filter' => ['ID' => $pool['id_responsible']]
        ])->fetch();
                
        $id_tg = $rsUsers['UF_TELEGRAM_ID'];

        $this->LetBot($pool, $subtext, $id_tg);
       
    }

    public function LetBot($pool, $subtext, $id_tg) {

        \CModule::IncludeModule('crm'); 

        $ContactInfo = \Bitrix\Crm\ContactTable::GetList([
            'select' => ['*', 'PHONE', 'ADDRESS'], 
            'filter' => [ 'ID' => $pool['contact_id'] ]
        ]);
        
        $Contact = $ContactInfo->fetch();
        
        if(isset($Contact) && !empty($Contact)){
            $CID = '';

            if (!empty($Contact['NAME'])) {
                $CID .= $Contact['NAME'];
            }
            
            if (!empty($Contact['LAST_NAME'])) {
                if (!empty($CID)) {
                    $CID .= ' ';
                }
                $CID .= $Contact['LAST_NAME'];
            }
            
            if (!empty($Contact['SECOND_NAME'])) {
                if (!empty($CID)) {
                    $CID .= ' ';
                }
                $CID .= $Contact['SECOND_NAME'];
            }
            
        }

        $url = "https://bx24.kitchenbecker.ru/crm/deal/details/".$pool['id_crm_deal']."/";

        $botToken = '6489328913:AAFJ5biTuinVmedStG2DBjqmDYWlAQfMdoU';

        $apiUrl = "https://api.telegram.org/bot{$botToken}/sendMessage";

        if(isset($pool['force'])){
            $message_text = "Встреча принята в работу по сделке " .$pool['id_crm_deal'] . " ФИО клиента: " . $CID;

            if (isset($pool['uf_time_visit'])) {
                $message_text .= ' Дата и время встречи: ' . $pool['uf_time_visit'];
            }

            if (isset($pool['adress'])) {
                $message_text .= ' Адрес клиента: ' . $pool['adress'];
            }

            if (isset($pool['link'])) {
                $message_text .= ' Ссылка на маршрут: ' . $pool['link'];
            }
            
            $message_text .= ' ' . $url;

            $data = [
                'chat_id' => $id_tg,
                'text' => $message_text,
            ];

            echo '<PRE>';
            print_r(['сообщение' => $data]);
            echo '</PRE>';
            
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

            $message_text = "Дополнительная информация по сделке " .$pool['id_crm_deal']. " Номер договора: " .$pool['numerDogovor']. " Номер квартиры: " . $pool['numerKravt'];

            $data = [
                'chat_id' => $id_tg,
                'text' => $message_text,
            ];

            echo '<PRE>';
            print_r(['сообщение' => $data]);
            echo '</PRE>';
            
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

            die();

        }

        if($pool['STATE'] == 'moment_podtver'){
            $message_text = "Необходимо перейти в сделку " .$pool['id_crm_deal']. " и принять в работу новую встречу ". $url;
        }
        if($pool['STATE'] == '>15'){
            $message_text = "Встреча принята в работу по сделке " .$pool['id_crm_deal'] . " ФИО клиента: " . $CID;

            if (isset($pool['uf_time_visit'])) {
                $message_text .= ' Дата и время встречи: ' . $pool['uf_time_visit'];
            }

            if (isset($pool['adress'])) {
                $message_text .= ' Адрес клиента: ' . $pool['adress'];
            }

            if (isset($pool['link'])) {
                $message_text .= ' Ссылка на маршрут: ' . $pool['link'];
            }
            $message_text .= ' ' . $url;
        }
        if(($pool['STATE'] == '15') || $pool['STATE'] == '<15'){
            $message_text = "Дополнительная информация по сделке " .$pool['id_crm_deal']. " Номер договора: " .$pool['numerDogovor']. " Номер квартиры: " . $pool['numerKravt'];
        }

        $data = [
            'chat_id' => $id_tg,
            'text' => $message_text,
        ];

        echo '<PRE>';
        print_r(['сообщение' => $data]);
        echo '</PRE>';
        
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
    
        if (isset($item['id_responsible']) && !empty($item['id_responsible'])) {
            $data['id_responsible'] = $item['id_responsible'];
        } else {
            $data['id_responsible'] = 'none';
        }
    
        $data['contact_id'] = $item['contact_id'];
        $data['uf_time'] = '';
        
        if(isset($item['uf_time_visit']) && !empty($item['uf_time_visit'])){
            $data['uf_time'] = $item['uf_time_visit'];
        }

        $data['id_crm_deal'] = $item['id_crm_deal'];
    
        // Устанавливаем значения по умолчанию
        $data['status_mess_moment_podtver'] = 'not yet';
        $data['status_mess_moment_prinata'] = 'not yet';
        $data['minutes_mess'] = 'not yet';

        $data['change_date'] = '0';
        			

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

        $this->syncDataWithDatabase(['data' => [$data], 'table_name' => 'cm_dis_mess_history']);

    }
    
    public function ChangeDateDeal($item) {
        $mysqli = $this->mysqli;
        $id_crm_deal = $item['id_crm_deal'];
        $query = "UPDATE cm_dis_mess_history SET change_date = '1' WHERE id_crm_deal = $id_crm_deal";
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
          $managerQuery = "SELECT * FROM cm_dis_mess_history
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
              return null;
          }

          return $managerResult;
    
        } catch (\Exception $e) { 
            return null; 
        }
          
    }

    public function GetHistoryDeal($p) {

        try{
  
          $prefix = $p['id_crm_deal'];
          $prefix_resp = $p['id_responsible'];

          $managerQuery = "SELECT * FROM cm_dis_mess_history WHERE id_crm_deal='$prefix' AND id_responsible='$prefix_resp' ORDER BY created_at DESC";
          $managerResult = $this->Get(['request' => $managerQuery]);
    
          if (!$managerResult) {
              return null;
          }
    
          return $managerResult;
    
        } catch (\Exception $e) { 
            return null; 
        }
          
    }
}