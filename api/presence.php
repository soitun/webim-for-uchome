<?php
$configRoot = '..' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR ;
include_once($configRoot . 'http_client.php');
include_once($configRoot . 'uchome.php');

$status=gp("status");
$show=gp("show");
$ticket=gp("ticket");
$nick =  nick($space);

if(!empty($ticket)) {
    $data=array('domain'=>$_IMC['domain'],'apikey'=>$_IMC['apikey'],'ticket' => $ticket,'nick'=>$nick, 'show'=>$show,'status'=>$status);
       $client = new HttpClient($_IMC['host'], $_IMC['port']);
    $client->post('/presences/show',$data );
    $pageContents = $client->getContent();
    echo '{"message":"'.$client->getContent().'"}';
}
?>
