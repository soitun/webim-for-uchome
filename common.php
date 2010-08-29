<?php
require_once('lib/webim.class.php');
require_once(dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'common.php');
require_once("lib/json.php");

$is_login = false;
if(empty($_SGLOBAL['supe_uid'])) {
    $is_login = false;
} else {
    $is_login = true;
    $space = getspace($_SGLOBAL['supe_uid']);
}
if(!$is_login)exit('Login at first.');
$user->uid =$space['uid'];
$user->id = to_utf8($space['username']);
$user->nick = to_utf8($space['username']);
$user->pic_url = avatar($user->uid,"small",true);
$user->default_pic_url=UC_API.'/images/noavatar_small.gif';
$user->show = gp('show') ? gp('show') : "available";
$user->url = "space.php?uid=".$user->uid;

$groups = getfriendgroup();

#connect ucenter db.
include_once(S_ROOT.'./source/class_mysql.php');
$ucdb = new dbstuff;
$ucdb->charset = UC_DBCHARSET;
$ucdb->connect(UC_DBHOST, UC_DBUSER, UC_DBPW, UC_DBNAME);


function nick($sp) {
    global $_IMC;
    $_nick=(!$_IMC['show_realname']||empty($sp['name'])) ? $sp['username'] : $sp['name'];
    return to_utf8(($_nick));
}

function ids_array($ids) {
    return ($ids===NULL || $ids==="") ? array() : (is_array($ids) ? array_unique($ids) : array_unique(split(",", $ids)));
}
function ids_except($id, $ids) {
    if(in_array($id, $ids)) {
        array_splice($ids, array_search($id, $ids), 1);
    }
    return $ids;
}

function im_tname($name) {
//     return "`webim_".$name."`";
    return UC_DBTABLEPRE."webim_".$name;
}

function build_buddies($buddies) {
    $_buddies = array();
    foreach($buddies as $b)
        $_buddies[]=array('id'=>$b->id,'show'=>$b->show,'need_reload'=>true,'presence'=>$b->presence);
    return $_buddies;
}

function online_buddy(){
	global $groups, $user, $_SGLOBAL;
	$list = array();
	$query = $_SGLOBAL['db']->query("SELECT f.fuid, f.fusername, f.gid FROM ".tname('friend')." f, ".tname('session')." s
		WHERE f.uid='$user->uid' AND f.fuid = s.uid ORDER BY f.num DESC, f.dateline DESC");
	while ($value = $_SGLOBAL['db']->fetch_array($query)){
		$list[] = (object)array(
			"uid" => $value['fuid'],
			"id" => to_utf8($value['fusername']),
			"nick" => to_utf8($value['fusername']),
			"group" => $groups[$value['gid']],
			"url" => "home.php?mod=space&uid=".$value['fuid'],
                	'default_pic_url' => UC_API.'/images/noavatar_small.gif',
			"pic_url" => avatar($value['fuid'], 'small', true),
		);
	}
	return $list;
}


function complete_status() {
}
//$ids="licangcai,qiukh"
function buddy($ids) {
    global $_SGLOBAL,$space, $groups;

    $ids = ids_array($ids);
    $ids = ids_except($space['username'], $ids);
    if(empty($ids))return array();
    $ids = join("','", $ids);
    $buddies = array();
    $q="SELECT main.uid, main.username, main.name, f.gid, f.fuid FROM "
            .tname('space')
            ." main LEFT OUTER JOIN "
            .tname('friend')
            ." f ON f.uid = '$space[uid]' AND main.uid = f.fuid WHERE main.username IN ('$ids')";
    $query = $_SGLOBAL['db']-> query($q);
    while ($value = $_SGLOBAL['db']->fetch_array($query)) {
        $id = $value['username'];
        $nick = nick($value);
        if(empty($value['fuid'])) {
            $group = "stranger";
        }else {
            $gid = $value['gid'];
            $group = (empty($gid) || empty($groups[$gid])) ? "friend" : $groups[$gid];
        }
        $buddies[]=(object)array('uid'=>$id,
                'id'=> $nick,
                'nick'=> $nick,
                'pic_url' =>avatar($value['uid'],"small",true),
                'status'=>'' ,
                'status_time'=>'',
                'url'=>'space.php?uid='.$value['uid'],
                'group'=> $group,
                'default_pic_url' => UC_API.'/images/noavatar_small.gif');
    }
    return $buddies;
}



function rooms() {
    global $_SGLOBAL,$space;
    $rooms = array();
    $query = $_SGLOBAL['db']->query("SELECT t.tagid, t.membernum, t.tagname, t.pic
		FROM ".tname('tagspace')." main
		LEFT JOIN ".tname('mtag')." t ON t.tagid = main.tagid
		WHERE main.uid = '$space[uid]'");
    while ($value = $_SGLOBAL['db']->fetch_array($query)) {
        $tagid = $value['tagid'];
        $id = $tagid;
        $tagname = $value['tagname'];
        $pic = empty($value['pic']) ? 'image/nologo.jpg' : $value['pic'];
        $rooms[$id]=(object)array('id'=>$id,
                'nick'=> to_utf8($tagname),
                'pic_url'=>$pic,
                'status'=>'',
                'status_time'=>'',
                'all_count' => $value['membernum'],
                'url'=>'space.php?do=mtag&tagid='.$tagid,
                'count'=>"");
    }
    return $rooms;
}


function find_new_message() {
    global $_SGLOBAL,$space, $ucdb;
    $uname = $space['username'];
    $messages = array();
    $ucdb->query("SET NAMES " . UC_DBCHARSET);
    $query = $ucdb->query("SELECT * FROM "
            .im_tname('histories')
            ." WHERE `to`='$uname' and send = 0 ORDER BY timestamp DESC LIMIT 100");
    while ($value = $ucdb->fetch_array($query)) {
        array_unshift($messages,array('to'=>to_utf8($value['to']),
                'nick'=>to_utf8($value['nick']),
                'from'=>to_utf8($value['from']),
                'style'=>$value['style'],
                'body'=>to_utf8($value['body']),
                'timestamp'=>$value['timestamp'],
                'type' =>$value['type']));
    }
    return $messages;
}

function new_message_to_histroy() {
    global $_SGLOBAL,$space, $ucdb;
    $uname = $space['username'];
    $ucdb->query("UPDATE ".im_tname('histories')." SET send = 1 WHERE `to`='$uname' AND send = 0");
}

function find_history($ids,$type="unicast") {
    global $_SGLOBAL,$space, $ucdb;
    $ucdb->query("SET NAMES " . UC_DBCHARSET);
    $uname= $space['username'];
    $histories = array();
    $ids = ids_array($ids);
    if($ids===NULL)return array();
    for($i=0;$i<count($ids);$i++) {

        $id = $ids[$i];
        
        $list = array();
        if($type=='multicast') {
            $q="SELECT * FROM ".im_tname('histories')
                    . " WHERE (`to`='$id') AND (`type`='multicast') AND send = 1 ORDER BY timestamp DESC LIMIT 30";
            $query = $ucdb->query($q);
            while ($value = $ucdb->fetch_array($query)) {
                array_unshift($list,
                        array('to'=>to_utf8($value['to']),
                        'from'=>to_utf8($value['from']),
                        'style'=>$value['style'],
                        'body'=>to_utf8($value['body']),
                        'timestamp'=>$value['timestamp'],
                        'type' =>$value['type'],
                        'nick'=>to_utf8($value['nick'])));
            }
        }else {
            $q=  "SELECT main.* FROM "
                    . im_tname('histories')
                    . " main WHERE (`send`=1) AND ((`to`='$id' AND `from`='$uname' AND `fromdel` != 1) or (`from`='$id' AND `to`='$uname' AND `todel` != 1))  ORDER BY timestamp DESC LIMIT 30";
            $query = $ucdb->query($q);
            while ($value = $ucdb->fetch_array($query)) {
                array_unshift($list,
                        array('to'=>to_utf8($value['to']),
                        'nick'=>to_utf8($value['nick']),
                        'from'=>to_utf8($value['from']),
                        'style'=>$value['style'],
                        'body'=>to_utf8($value['body']),
                        'type' => $value['type'],
                        'timestamp'=>$value['timestamp']));
            }
        }

    }
    return $list;
}

function setting() {
    global $_SGLOBAL,$space, $ucdb;
    if(!empty($_SGLOBAL['supe_uid'])) {
        $setting  = $ucdb->fetch_array($ucdb->query("SELECT * FROM ".im_tname('settings')." WHERE uid='$_SGLOBAL[supe_uid]'"));
        if(empty($setting)) {
            $setting = array('uid'=>$space['uid'],'web'=>"");
            $ucdb->query("INSERT INTO ".im_tname('settings')." (uid,web) VALUES ($_SGLOBAL[supe_uid],'')");
        }
        $setting = $setting["web"];
    }
    return json_decode(empty($setting) ? "{}" : $setting);
}

function to_utf8($s) {
    global $_SC;
    if($_SC['charset'] == 'utf-8') {
        return $s;
    } else {
        return  _iconv($_SC['charset'],'utf-8',$s);
    }
}

function from_utf8($s) {
    global $_SC;
    if($_SC['charset'] == 'utf-8') {
        return $s;
    } else {
        return  _iconv('utf-8',$_SC['charset'],$s);
    }
}

?>
