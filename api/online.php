<?php
$configRoot = '..' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR ;
include_once($configRoot . 'http_client.php');	
include_once($configRoot . 'uchome.php');
		
if(empty($space))exit();
//$stranger_ids = ids_except($space["uid"], ids_array(gp("stranger_ids")));//陌生人
$friend_ids = ids_array($space['friends']);
$buddy_ids = ids_array(gp("buddy_ids"));//正在聊天的联系人

$nick =  nick($space);


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
//        array_push($stranger_ids, $msg_uid);
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
//        $rooms[$key]['pic_url'] = "webim/static/images/group_chat_head.png";
		array_push($room_ids,$key);
}
//需要查找在线状况的人
if(!empty($friend_ids)) {
    $ids=join(",",$friend_ids);
    $query = $_SGLOBAL['db']-> query("SELECT username FROM ".tname('space')." WHERE uid IN ($ids)");
    while ($value = $_SGLOBAL['db']->fetch_array($query)) {
        $buddies[] = $value['username'];

    }
}

$buddies=array_unique(array_merge($buddies,$buddy_ids));
$buddies[]= "root";

$data = array('rooms'=> join(',', $room_ids),
              'buddies'=>join(',', $buddies),
              'domain' => $_IMC['domain'],
              'apikey' => $_IMC['apikey'],
              'name'=> $space['username'],
              'nick'=>$nick
               );

$client = new HttpClient($_IMC['host'], $_IMC['port']);
$client->post('/presences/online', $data);
$pageContents = $client->getContent();

$pageData = json_decode($pageContents);

if($client->status !="200"||empty($pageData->ticket)){
        $ticket ="";
}else
        $ticket = $pageData->ticket;
if(empty($ticket)){
    
        echo '{status: "'.$client->status.'", "errorMsg":"'.$pageContents.'"}';
        exit();
}
//$pageData->buddies
//$a=json_decode('{"licangcai":"available", "qiukh":"dnd"}');
//var_dump($pageData->buddies);
$online_buddies=build_buddies($pageData->buddies);//online buddies

$clientnum = $pageData->clientnum;
$rooms_num = $pageData->roominfo;
$out_rooms=array();

if(is_object($rooms_num)){
	foreach($rooms_num as $key => $value){
		$rooms[$key]['count'] = $value;
                $out_rooms[]=$rooms[$key];
	}
}

$output = array();

$output['clientnum'] = $clientnum;
$output['server_time'] = microtime(true)*1000;

$output['user']=array('id'=>$space['username'],
                       'nick'=>$nick,
                       'default_pic_url'=> UC_API.'/images/noavatar_middle.gif',
                       'pic_url'=>avatar($space['uid'],'small',true),
                       'status'=>'',
                       'status_time'=>'',
                       'show '=>'dnd',
                       'url'=>'space.php?uid='.$space['uid']);//用户信息


$imserver = 'http://'.$_IMC['host'].':'.$_IMC['port']."/packets";
$output['connection'] = array('domain' => $_IMC['domain'], 'ticket'=>$ticket, 'server'=>$imserver);//服务器连接

$output['new_messages'] = $new_messages;
$output['buddies'] = array_merge(find_buddy($buddy_ids),$online_buddies);

$output['rooms'] = $out_rooms;
$output['histories'] = find_history($buddy_ids);

new_message_to_histroy(); //新消息转到历史记录

echo json_encode($output);
?>
