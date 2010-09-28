<?php

/** 
 * Custom interface 
 *
 * Provide 
 *
 * define WEBIM_PRODUCT_NAME
 * array $_IMC
 * boolean $im_is_admin
 * boolean $im_is_login
 * object $imuser require when $im_is_login
 * function webim_get_menu() require when !$_IMC['disable_menu']
 * function webim_get_buddies()
 * function webim_get_online_buddies()
 * function webim_get_rooms()
 * function webim_get_notifications()
 * function webim_login()
 *
 */

define( 'WEBIM_PRODUCT_NAME', 'uchome' );

require_once(dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'common.php');

//Find and insert data with utf8 client.
@$_SGLOBAL['db']->query("SET NAMES utf8");

@include_once( 'config.php' );

/**
 *
 * Provide the webim database config.
 *
 * $_IMC['dbuser'] MySQL database user
 * $_IMC['dbpassword'] MySQL database password
 * $_IMC['dbname'] MySQL database name
 * $_IMC['dbhost'] MySQL database host
 * $_IMC['dbtable_prefix'] MySQL database table prefix
 * $_IMC['dbcharset'] MySQL database charset
 *
 */

$_IMC['dbuser'] = UC_DBUSER;
$_IMC['dbpassword'] = UC_DBPW;
$_IMC['dbname'] = UC_DBNAME;
$_IMC['dbhost'] = UC_DBHOST;
$prefix = explode(".", UC_DBTABLEPRE );
$_IMC['dbtable_prefix'] = count( $prefix ) == 2 ? $prefix[1] : $prefix[0];
$_IMC['dbcharset'] = UC_DBCHARSET;

/**
 * Init im user.
 * 	-uid:
 * 	-id:
 * 	-nick:
 * 	-pic_url:
 * 	-show:
 *
 */

if(empty($_SGLOBAL['supe_uid'])) {
	$im_is_login = false;
} else {
	$im_is_login = true;
	webim_set_user();
}

function webim_set_user() {
	global $_SGLOBAL, $imuser, $im_is_admin;
	$space = getspace($_SGLOBAL['supe_uid']);
	$imuser->uid = $space['uid'];
	$imuser->id = $space['username'];
	$imuser->nick = nick( $space );
	$imuser->pic_url = avatar( $imuser->uid, "small", true );
	$imuser->default_pic_url = UC_API.'/images/noavatar_small.gif';
	$imuser->show = webim_gp('show') ? webim_gp('show') : "available";
	$imuser->url = "space.php?uid=".$imuser->uid;
	complete_status( array( $imuser ) );
	if( ckfounder( $imuser->uid ) ){
		$im_is_admin = true;
	} else {
		$im_is_admin = false;
	}
}

function webim_login( $username, $password, $question = "", $answer = "" ) {
	global $imuser, $_SGLOBAL, $im_is_login;
	include_once(S_ROOT.'./source/function_cp.php');
	$cookietime = intval($_POST['cookietime']);
	$cookiecheck = $cookietime?' checked':'';
	$membername = $username;

	if(empty($username)) {
		return false;
	}

	//同步获取用户源
	if(!$passport = getpassport($username, $password)) {
		return false;
	}

	$setarr = array(
		'uid' => $passport['uid'],
		'username' => addslashes($passport['username']),
		'password' => md5("$passport[uid]|$_SGLOBAL[timestamp]")//本地密码随机生成
	);

	include_once(S_ROOT.'./source/function_space.php');
	//开通空间
	$query = $_SGLOBAL['db']->query("SELECT * FROM ".tname('space')." WHERE uid='$setarr[uid]'");
	if(!$space = $_SGLOBAL['db']->fetch_array($query)) {
		$space = space_open($setarr['uid'], $setarr['username'], 0, $passport['email']);
	}

	$_SGLOBAL['member'] = $space;

	//实名
	realname_set($space['uid'], $space['username'], $space['name'], $space['namestatus']);

	//检索当前用户
	$query = $_SGLOBAL['db']->query("SELECT password FROM ".tname('member')." WHERE uid='$setarr[uid]'");
	if($value = $_SGLOBAL['db']->fetch_array($query)) {
		$setarr['password'] = addslashes($value['password']);
	} else {
		//更新本地用户库
		inserttable('member', $setarr, 0, true);
	}

	//清理在线session
	insertsession($setarr);

	//设置cookie
	ssetcookie('auth', authcode("$setarr[password]\t$setarr[uid]", 'ENCODE'), $cookietime);
	ssetcookie('loginuser', $passport['username'], 31536000);
	ssetcookie('_refer', '');

	//同步登录
	if($_SCONFIG['uc_status']) {
		include_once S_ROOT.'./uc_client/client.php';
		$ucsynlogin = uc_user_synlogin($setarr['uid']);
	} else {
		$ucsynlogin = '';
	}
	realname_get();
	$im_is_login = true;
	webim_set_user();
	return true;
}

//Cache friend_groups;
$friend_groups = getfriendgroup();
foreach($friend_groups as $k => $v){
	$friend_groups[$k] = to_utf8($v);
}

/**
 * Online buddy list.
 *
 */
function webim_get_online_buddies() {
	global $friend_groups, $imuser, $_SGLOBAL;
	$list = array();
	$query = $_SGLOBAL['db']->query("SELECT m.uid, m.username, m.name, f.gid 
		FROM ".tname('friend')." f, ".tname('session')." s, ".tname('space')." m
		WHERE f.uid='$imuser->uid' AND f.fuid = s.uid AND m.uid = s.uid
		ORDER BY f.num DESC, f.dateline DESC");
	while ($value = $_SGLOBAL['db']->fetch_array($query)){
		$list[] = (object)array(
			"uid" => $value['uid'],
			"id" => $value['username'],
			"nick" => nick($value),
			"group" => $friend_groups[$value['gid']],
			"url" => "space.php?uid=".$value['uid'],
			'default_pic_url' => UC_API.'/images/noavatar_small.gif',
			"pic_url" => avatar($value['uid'], 'small', true),
		);
	}
	complete_status( $list );
	return $list;
}

/**
 * Get buddy list from given ids
 * $ids:
 *
 * Example:
 * 	buddy('admin,webim,test');
 *
 */

function webim_get_buddies( $names, $uids = null ) {
	global $_SGLOBAL, $imuser, $friend_groups;
	$where_name = "";
	$where_uid = "";
	if(!$names and !$uids)return array();
	if($names){
		$names = "'".implode("','", explode(",", $names))."'";
		$where_name = "m.username IN ($names)";
	}
	if($uids){
		$where_uid = "m.uid IN ($uids)";
	}
	$where_sql = $where_name && $where_uid ? "($where_name OR $where_uid)" : ($where_name ? $where_name : $where_uid);
	$list = array();
	$query = $_SGLOBAL['db']-> query("SELECT m.uid, m.username, m.name, f.gid, f.fuid 
		FROM " .tname('space')." m 
		LEFT OUTER JOIN ".tname('friend')." f ON f.uid = '$imuser->uid' AND m.uid = f.fuid 
		WHERE m.uid <> $imuser->uid AND $where_sql");
	while ($value = $_SGLOBAL['db']->fetch_array($query)) {
		if(empty($value['fuid'])) {
			$group = "stranger";
		}else {
			$gid = $value['gid'];
			$group = (empty($gid) || empty($friend_groups[$gid])) ? "friend" : $friend_groups[$gid];
		}
		$list[]=(object)array(
			'uid'=>$value['uid'],
			'id'=> $value['username'],
			'nick'=> nick($value),
			'pic_url' =>avatar($value['uid'],"small",true),
			'status'=>'' ,
			'status_time'=>'',
			'url'=>'space.php?uid='.$value['uid'],
			'group'=> $group,
			'default_pic_url' => UC_API.'/images/noavatar_small.gif');
	}
	complete_status( $list );
	return $list;
}

/**
 * Get room list
 * $ids: Get all imuser rooms if not given.
 *
 */

function webim_get_rooms($ids=null) {
	global $_SGLOBAL,$imuser;
	$rooms = array();
	$query = $_SGLOBAL['db']->query("SELECT t.tagid, t.membernum, t.tagname, t.pic
		FROM ".tname('tagspace')." main
		LEFT JOIN ".tname('mtag')." t ON t.tagid = main.tagid
		WHERE main.uid = '$imuser->uid'");
	while ($value = $_SGLOBAL['db']->fetch_array($query)) {
		$tagid = $value['tagid'];
		$id = $tagid;
		$tagname = $value['tagname'];
		$pic = empty($value['pic']) ? 'image/nologo.jpg' : $value['pic'];
		$rooms[$id]=(object)array('id'=>$id,
			'nick'=> $tagname,
			'pic_url'=>$pic,
			'status'=>'',
			'status_time'=>'',
			'all_count' => $value['membernum'],
			'url'=>'space.php?do=mtag&tagid='.$tagid,
			'count'=>"");
	}
	return $rooms;
}

function webim_get_notifications(){
	global $_SGLOBAL;
	include_once S_ROOT.'./uc_client/client.php';
	$pmlist = array();
	$member = $_SGLOBAL['member'];
	if($member['notenum']){
		$pmlist[]=array("text"=>('<img src="image/icon/notice.gif" width="16" alt="" /><strong>'.$member["notenum"].'</strong> 个新通知'),"link"=>"space.php?do=notice");
	}
	if($member['pokenum']){
		$pmlist[]=array("text"=>('<img src="image/icon/poke.gif" width="16" alt="" /><strong>'.$member["pokenum"].'</strong> 个新招呼'),"link"=>"cp.php?ac=poke");
	}
	if($member['addfriendnum']){
		$pmlist[]=array("text"=>('<img src="image/icon/friend.gif" width="16" alt="" /><strong>'.$member["addfriendnum"].'</strong> 个好友请求'),"link"=>"cp.php?ac=friend&op=request");
	}
	if($member['mtaginvitenum']){
		$pmlist[]=array("text"=>('<img src="image/icon/mtag.gif" width="16" alt="" /><strong>'.$member["mtaginvitenum"].'</strong> 个群组邀请'),"link"=>"cp.php?ac=mtag&op=mtaginvite");
	}
	if($member['eventinvitenum']){
		$pmlist[]=array("text"=>('<img src="image/icon/event.gif" width="16" alt="" /><strong>'.$member["eventinvitenum"].'</strong> 个活动邀请'),"link"=>"cp.php?ac=event&op=eventinvite");
	}
	if($member['myinvitenum']){
		$pmlist[]=array("text"=>('<img src="image/icon/userapp.gif" width="16" alt="" /><strong>'.$member["myinvitenum"].'</strong> 个应用消息'),"link"=>"space.php?do=notice&view=userapp");
	}

	$pmstatus = uc_pm_checknew($user->uid, 0);
	$filter =  'newpm';
	$ucdata = uc_pm_list($user->uid, 1, 20, "inbox", "newpm", 150);

	foreach($ucdata['data'] as $pm) {
		if ($pm['msgfromid'] > 0) {
			$from=$pm['msgfrom'];
			$text=$pm['msgfrom']." 给你发了一条消息！";
			$link= 'space.php?do=pm&filter=newpm&uid='.$pm['touid'].'&filter=newpm&daterange='.$pm['daterange'];
		}else {
			$from='';
			$text="系统信息：".$pm['subject'];
			$link= 'space.php?do=pm&filter=newpm?pmid='.$pm['pmid'].'&filter=systempm';
		}
		$pmlist[]= array('from'=>$from,'text'=>$text,'link'=>$link,'time'=>$pm['dateline']);
	}
	return $pmlist;
}

function webim_get_menu() {
	global $_SCONFIG, $_SGLOBAL;
	$menu = array(
		array("title" => 'doing',"icon" =>"image/app/doing.gif","link" => "space.php?do=doing"),
		array("title" => 'album',"icon" =>"image/app/album.gif","link" => "space.php?do=album"),
		array("title" => 'blog',"icon" =>"image/app/blog.gif","link" => "space.php?do=blog"),
		array("title" => 'thread',"icon" =>"image/app/mtag.gif","link" => "space.php?do=thread"),
		array("title" => 'share',"icon" =>"image/app/share.gif","link" => "space.php?do=share")
	);

	if($_SCONFIG['my_status']) {
		if(is_array($_SGLOBAL['userapp'])) { 
			foreach($_SGLOBAL['userapp'] as $value) { 
				$menu[] = array("title" => to_utf8($value['appname']), "icon" =>"http://appicon.manyou.com/icons/".$value['appid'],"link" => "userapp.php?id=".$value['appid']);
			}
		}
		if(is_array($_SGLOBAL['my_menu'])) { 
			foreach($_SGLOBAL['my_menu'] as $value) { 
				$menu[] = array("title" => to_utf8($value['appname']), "icon" =>"http://appicon.manyou.com/icons/".$value['appid'],"link" => "userapp.php?id=".$value['appid']);
			}
		}
	}
	return $menu;
}

/**
 * Add status to member info.
 *
 * @param array $members the member list
 * @return 
 *
 */
function complete_status( $members ) {
	global $_SGLOBAL;
	if(!empty($members)){                
		$num = count($members);                
		$ids = array();
		$ob = array();
		for($i = 0; $i < $num; $i++){
			$m = $members[$i];
			$id = $m->uid;
			if ( $id ) {
				$ids[] = $id;
				$ob[$id] = $m;
			}
		}
		$ids = implode(",", $ids);
		$query = $_SGLOBAL['db']-> query($q="SELECT t.uid, t.message FROM " . tname("doing") . " t 
			INNER JOIN (SELECT max(doid) doid 
			FROM " . tname("doing") . "  
			WHERE uid IN ($ids)
			GROUP BY uid) t2 
			ON t2.doid = t.doid;");
		while ($value = $_SGLOBAL['db']->fetch_array($query)) {
			$ob[$value['uid']]->status = $value['message'];
		}
	}
	return $members;
}

function nick( $sp ) {
	global $_IMC;
	return (!$_IMC['show_realname']||empty($sp['name'])) ? $sp['username'] : $sp['name'];
}

function to_utf8( $s ) {
	global $_SC;
	if( strtoupper( $_SC['charset'] ) == 'UTF-8' ) {
		return $s;
	} else {
		if ( function_exists( 'iconv' ) ) {
			return iconv( $_SC['charset'], 'utf-8', $s );
		} else {
			require_once 'class_chinese.php';
			$chs = new Chinese( $_SC['charset'], 'utf-8' );
			return $chs->Convert( $s );
		}
	}
}


