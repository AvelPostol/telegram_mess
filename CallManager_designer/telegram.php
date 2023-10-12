<?php

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS',true);

$_SERVER["DOCUMENT_ROOT"] = "/mnt/data/bitrix";
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

GLOBAL $USER;
$rt = CUser::IsOnLine(7);
var url = "/crm/deal/details/" + IdDeal + "/"


$botToken = '6275540383:AAFGqM2s37wMwNsAoBCn0BF6h61k57Q_A6A';
$chatId = '437532761';
$message = 'i"m cry';

$apiUrl = "https://api.telegram.org/bot{$botToken}/sendMessage";

$data = [
    'chat_id' => $chatId,
    'text' => $message,
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
