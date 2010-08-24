<?php
$configRoot = '..' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR ;
include_once($configRoot . 'http_client.php');
include_once($configRoot . 'uchome.php');
$ticket = gp('ticket');
$room_id = gp('id');
$nick = gp('nick');
if(!empty($ticket)) {
  $data = array('ticket'=>$ticket,'nick'=>$nick, 'domain'=>$_IMC['domain'], 'apikey'=>$_IMC['apikey'], 'room'=>$room_id, 'name' => $space['uid']);
	$client = new HttpClient($_IMC['host'], $_IMC['port']);
	$client->post('/room/leave', $data);
	$pageContents = $client->getContent();
	echo $pageContents;
}
?>
