<?php
include_once('common.php');
$friend_ids = ids_array($space['friends']);
//$buddy_ids = ids_array(gp("buddy_ids"));//正在聊天的联系人
//$buddy_ids = ids_array("orez88,admin");//正在聊天的联系人

$new_messages = find_new_message();//查找离线消息
for($i=0;$i<count($new_messages);$i++) {
    $msg_uid = $new_messages[$i]["from"];
    array_push($buddy_ids, $msg_uid);//有离线消息的人
}

//查找群组
$setting = setting();
$block_list = is_array($setting->block_list) ? $setting->block_list : array();
$rooms=rooms();
foreach($rooms as $key => $value) {
    if(in_array($key, $block_list)) {
        $rooms[$key]['blocked'] = true;
    }else
        $room_ids[]=$key;
}

if(!empty($friend_ids)) {
    $ids=join(",",$friend_ids);
    $query = $_SGLOBAL['db']-> query("SELECT username FROM ".tname('space')." WHERE uid IN ($ids)");
    while ($value = $_SGLOBAL['db']->fetch_array($query)) {
        $buddy_ids[] = $value['username'];

    }
}

$buddy_ids=array_unique($buddy_ids);

$im = new WebIM($user, null, $_IMC['domain'], $_IMC['apikey'], $_IMC['host'], $_IMC['port']);
$data = $im->online(implode(",",$buddy_ids), implode(",", $room_ids));
if($data->success) {
    $_rooms=array();
    //Add room online member count.
    foreach($data->rooms as $k => $v) {
        $id = $v->id;
        $rooms[$id]['count'] = $v->count;
        $_rooms[]=$rooms[$id];
    }

  $buddylist=array();
  $_buddies=buddy($buddy_ids);

  if(!empty($_buddies)) {
      foreach($_buddies as $buddy) {
          $buddy['show']="unavailable";
          $buddy['need_reload']=false;
          $buddy['presence']="offline";
          foreach($data->buddies as $online_buddy) {
              if(!(in_array($online_buddy->id,$buddy_ids))) {
                  $buddylist[]=$online_buddy;
              }
              if($buddy['id']==$online_buddy->id) {
                 $buddy['show']=$online_buddy->show;
                 $buddy['need_reload']=$online_buddy->need_reload;
                 $buddy['presence']=$online_buddy->presence;
              }

          }
          $buddylist[]=$buddy;
      }
  }
  else {
      $buddylist=(array)$data->buddies;
  }

    $data->rooms = $_rooms;
   
    $data->buddies=$buddylist;
    $data->histories=find_history($buddy_ids);
    $data->new_messages=$new_messages;
    echo json_encode($data);
    new_message_to_histroy();
}else {
    header("HTTP/1.0 404 Not Found");
    echo json_encode($data->error_msg);
}
?>
