<?php
$configRoot = '..' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR ;
include_once( $configRoot . 'http_client.php' );
include_once($configRoot . 'uchome.php');
		

$ticket = gp('ticket');
if(!empty($ticket)) {
        $data = array('ticket'=>$ticket,'domain'=>$_IMC['domain'],'apikey'=>$_IMC['apikey']);
	//Logout webim server.
	$client = new HttpClient($_IMC['host'], $_IMC['port']);
	$client->post('/presences/offline',$data);
}
?>
