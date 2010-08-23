<?php
$configRoot = '..' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR ;
include_once($configRoot . 'http_client.php');	
include_once($configRoot . 'uchome.php');
		
if(empty($space))exit();
$stranger_ids = ids_except($space["uid"], ids_array(gp("stranger_ids")));//陌生人
$friend_ids = ids_array($space['friends']);
$buddy_ids = ids_array(gp("buddy_ids"));//正在聊天的联系人


$name = nick($space);
$nick = to_utf8($name);

/* if $friend_ids or $stranger_ids    = Null
 *
 * Change into Array().
 * */
if(!$friend_ids){
    $friend_ids = array();
}
if(!$stranger_ids){
    $stranger_ids = array();
}

//var_dump($stranger_ids);
//modify by jinyu
//var_dump($_SESSION['uid']);
if(!isset($_SESSION['uid'])){
	$_SESSION['uid'] = $space["uid"];
}
if(!isset($_SESSION['stranger_ids'])){
	foreach($friend_ids as $id){
		$stranger_ids = ids_except($id, $stranger_ids);
	}
	$_SESSION['stranger_ids'] = $stranger_ids;
}else{
	if(empty($stranger_ids)){
		foreach($friend_ids as $id){
			$_SESSION['stranger_ids'] = ids_except($id, $_SESSION['stranger_ids']);
		}
		$stranger_ids = $_SESSION['stranger_ids'];
	}
}



$new_messages = find_new_message();//查找离线消息
for($i=0;$i<count($new_messages);$i++){
        $msg_uid = $new_messages[$i]["from"];
        array_push($buddy_ids, $msg_uid);
        array_push($stranger_ids, $msg_uid);
}

//查找群组
$setting = setting();
$block_list = is_array($setting->block_list) ? $setting->block_list : array();

$rooms = find_room();
$room_ids = array();

foreach($rooms as $key => $value){
	if(in_array($key, $block_list)){
		$rooms[$key]['blocked'] = true;
	}else
        $rooms[$key]['pic_url'] = "webim/static/images/group_chat_head.png";
		array_push($room_ids,$key);
}
//需要查找在线状况的人
$ids = array_unique(array_merge($friend_ids, $buddy_ids, $stranger_ids));
$ids = join(',', $ids);
if(!empty($ids)) {
    $query = $_SGLOBAL['db']-> query("SELECT username FROM ".tname('space')." WHERE uid IN ($ids)");
    while ($value = $_SGLOBAL['db']->fetch_array($query)) {
        $buddies[] = $value['username'];
    }
}
$buddies[] = "root";
//$a='{"a":"available","b":{"x":1,"y":2,"z":3},"c":"hidden"}';
//$ja=json_decode($a);
//build_buddies($ja->b);

$data = array('rooms'=> join(',', $room_ids),'buddies'=>join(',', $buddies), 'domain' => $_IMC['domain'], 'apikey' => $_IMC['apikey'], 'endpoint'=> $space['username'], 'nick'=>to_unicode($nick));

$client = new HttpClient($_IMC['imsvr'], $_IMC['impost']);
$client->post('/presences/online', $data);
$pageContents = $client->getContent();
//var_dump($pageContents);
//TODO: handle errors!
$pageData = json_decode($pageContents);
//var_dump($pageData);
if($client->status !="200"||empty($pageData->ticket)){
        $ticket ="";
}else
        $ticket = $pageData->ticket;
if(empty($ticket)){
    
        echo '{status: "'.$client->status.'", "errorMsg":"'.$pageContents.'"}';
        exit();
}

//=
//$a='{"a":"available","b":{"x":1,"y":2,"z":3},"c":"hidden"}';
//$ja=json_decode($a);
//var_dump($pageData->buddies);
$online_buddies=build_buddies($pageData->buddies);//online buddies
//

//$buddy_online_ids = ids_array($ids_string);

//$_SESSION['online_ids'] = $buddy_online_ids;
$clientnum = $pageData->clientnum;
$rooms_num = $pageData->roominfo;
if(is_object($rooms_num)){
	foreach($rooms_num as $key => $value){
		$rooms[$key]['count'] = $value;
	}
}
$output = array();
//$output['buddy_online_ids'] = join(",", $buddy_online_ids);
$output['clientnum'] = $clientnum;
$output['server_time'] = microtime(true)*1000;

$output['user']=array('id'=>$space['username'],
                       'nick'=>to_utf8($name),
                       'default_pic_url'=>'',
                       'pic_url'=>avatar($space['uid'],'small',true),
                       'status'=>'',
                       'status_time'=>'',
                       'show '=>'chat',
                       'url'=>'space.php?uid='.$space['uid']);//用户信息
$imserver = 'http://'.$_IMC['imsvr'].':'.$_IMC['impoll']."/packets";
$output['connection'] = array('domain' => $_IMC['domain'], 'ticket'=>$ticket, 'server'=>$imserver);//服务器连接

$output['new_messages'] = $new_messages;
$output['buddies'] = array_merge(find_buddy($buddy_ids),$online_buddies);
//var_dump($online_buddies);
$output['rooms'] = $rooms;
$output['histories'] = find_history($buddy_ids);
//var_dump($output['buddies']);
new_message_to_histroy(); //新消息转到历史记录

echo json_encode($output);
?>
