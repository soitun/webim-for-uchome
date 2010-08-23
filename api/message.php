<?php 
$configRoot = '..' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR ;
include_once($configRoot . 'http_client.php');
include_once($configRoot . 'uchome.php');
		
$ticket = gp('ticket');
$body = gp('body','');
$style = gp('style','');
$to = gp('to');
$send = gp('offline') == "false" ? false : true;
$type = gp('type');
$from = $space['username'];
$time = microtime(true)*1000;
//change by chenxj
if($type != "broadcast" && (empty($to)||empty($from))){
	echo "{success:false}"."{".$to.":".$from."}";exit();
}
$client = new HttpClient($_IMC['host'], $_IMC['port']);
$nick = to_unicode(to_utf8(nick($space)));


$client->post('/messages', array('domain'=>$_IMC['domain'],'apikey'=>$_IMC['apikey'],'ticket' => $ticket,'nick'=>$nick, 'type'=> $type, 'to'=>$to,'body'=>to_unicode($body),'timestamp'=>(string)$time,'style'=>$style));
$pageContents = $client->getContent();

//TODO:send => true if forward message successfully.
//
$body=from_utf8($body);
$columns = "`send`,`to`,`from`,`style`,`body`,`timestamp`,`type`";
if($type=="multicast"){//add by free.wang
    $to = $to + $_IMC['room_id_pre'];//add by free.wang
}//add by free.wang

$_SGLOBAL['db']->query("SET NAMES " . UC_DBCHARSET);

//add by Harvey.
if ($type == "broadcast"){
	if(strpos($_IMC["admin_ids"], $from) !== false){
		$values_from = "'1','$to','$from','$style','$body','$time','$type'";
        $_SGLOBAL['db']->query("INSERT INTO ".im_tname('histories')." ($columns) VALUES ($values_from)");
	}
	require_once('../update/notify_update.php');
}
else{
	$values_from = "'1','$to','$from','$style','$body','$time','$type'";
	$_SGLOBAL['db']->query("INSERT INTO ".im_tname('histories')." ($columns) VALUES ($values_from)");
}

$output = array();
$output["success"] = $send;
$output["msg"] = $pageContents;
echo json_encode($output);
?>
