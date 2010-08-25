<?php
$configRoot = '..' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR ;
include_once( $configRoot . 'http_client.php');
include_once($configRoot . 'uchome.php');
		
$ticket = gp('ticket');
$room_id = gp('id');
if(empty($ticket)) {
    exit;
}
$data = array('ticket'=>$ticket, 'domain'=>$_IMC['domain'], 'apikey'=>$_IMC['apikey'], 'room'=>$room_id, 'name' => $space['uid']);

$client = new HttpClient($_IMC['host'], $_IMC['port']);
$client->get('/room/members', $data);

$pageContents = $client->getContent();

$result  = json_decode($pageContents,TRUE);

$result = $result[($room_id)];
echo  json_encode($result);
?>
