<?php
namespace CallCustom\Workspace\Tools;

class Call {

  public function Post($phone) {
    
  $js_auth = [
      "action" => "make_call",
      "obj" => "UserCRM",
      "action_id" => "123",
      "params" => [
          "crm_user_id" => 1911,
          "dst" => $phone
      ]
  ];

  $url = 'https://pbx.megafon.ru/integration/usercrm/';
  $auth_headers = [
      'Content-Type: application/json',
      'Authorization: Bearer 0042878e-cb4c-44f7-adc8-975daedcb19b028afcbc-a6f4-4596-9659-667dffac5cb4'
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


