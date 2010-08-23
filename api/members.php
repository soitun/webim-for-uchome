<?php
$platform = $_GET['platform'];
$configRoot = '..' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR ;
include_once( $configRoot . 'http_client.php');
include_once($configRoot . 'uchome.php');
		
$ticket = gp('ticket');
$room_id = gp('id');
if(empty($ticket)) {
    exit;
}
$data = array('ticket'=>$ticket, 'domain'=>$_IMC['domain'], 'apikey'=>$_IMC['apikey'], 'room'=>$room_id, 'endpoint' => $space['uid']);

$client = new HttpClient($_IMC['host'], $_IMC['port']);
$client->get('/room/members', $data);

$pageContents = $client->getContent();

$result  = json_decode($pageContents,TRUE);
unset($client);
unset($pageContents);
$result = $result[($room_id)];
//foreach($result as $group =>$v )
//{
//
//    array_merge($output,$v);
//    var_dump($output);
//    foreach($result[$group] as $k=>$v)
//    {
//
//        $uid = $result[$group][$k]['id'];
//        $pic = user_pic($uid);
//        $result[$group][$k]['pic'] = $pic;
//        $result[$group][$k]['default_pic_url'] = UC_API.'/images/noavatar_small.gif';
//    }
//}

echo  json_encode($result);
unset($result);
?>
