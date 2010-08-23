<?php
header("Content-type: application/javascript");
include_once 'config.php';
$platform = 'uchome';

switch($platform){
	case 'discuz':
		include_once('lib/discuz.php');
		break;
	case 'uchome':
		include_once('lib/uchome.php');
		break;
}

if($platform === 'uchome'){
	$menu = array(
		array("title" => 'doing',"icon" =>"image/app/doing.gif","link" => "space.php?do=doing"),
		array("title" => 'album',"icon" =>"image/app/album.gif","link" => "space.php?do=album"),
		array("title" => 'blog',"icon" =>"image/app/blog.gif","link" => "space.php?do=blog"),
		array("title" => 'thread',"icon" =>"image/app/mtag.gif","link" => "space.php?do=thread"),
		array("title" => 'share',"icon" =>"image/app/share.gif","link" => "space.php?do=share")
	);
}else if($platform === 'discuz'){
	$menu = array(
		array("title" => 'search',"icon" =>"webim/static/images/search.png","link" => "search.php"),
		array("title" => 'faq',"icon" =>"webim/static/images/faq.png","link" => "faq.php"),
		array("title" => 'nav',"icon" =>"webim/static/images/nav.png","link" => "misc.php?action=nav"),
		array("title" => 'feeds',"icon" =>"webim/static/images/feeds.png","link" => "index.php?op=feeds"),
		array("title" => 'sms',"icon" =>"webim/static/images/msm.png","link" => "pm.php")
	);
}
$menu[] = array("title" => 'imlogo',"icon" =>"webim/static/images/nextim.gif","link" => "http://www.nextim.cn");
if($_SCONFIG['my_status']) {
	if(is_array($_SGLOBAL['userapp'])) { 
		foreach($_SGLOBAL['userapp'] as $value) { 
			$menu[] = array("title" => iconv(UC_DBCHARSET,'utf-8',$value['appname']),"icon" =>"http://appicon.manyou.com/icons/".$value['appid'],"link" => "userapp.php?id=".$value['appid']);
		}
	}
}
$setting = json_encode(setting());

?>

//custom
(function(webim){
    var path = "";
    var platform = "<?php echo $platform ?>";

    var menu = webim.JSON.decode('<?php echo json_encode($menu) ?>');
	webim.extend(webim.setting.defaults.data, webim.JSON.decode('<?php echo $setting ?>'));
	var webim = window.webim;
	webim.defaults.urls = {
		online:path + "webim/api/online.php?platform=" + platform,
		offline:path + "webim/api/offline.php?platform=" + platform,
		message:path + "webim/api/message.php?platform=" + platform,
		presence:path + "webim/api/presence.php?platform=" + platform,
		refresh:path + "webim/api/refresh.php?platform=" + platform,
		status:path + "webim/api/status.php?platform=" + platform
	};
	webim.setting.defaults.url = path + "webim/api/setting.php?platform="+platform;
	webim.history.defaults.urls = {
		load: path + "webim/api/history.php?platform=" + platform,
		clear: path + "webim/api/clear_history.php?platform=" + platform
	};
    	webim.room.defaults.urls = {
                    member: path + "webim/api/members.php?platform=" + platform,
                    join: path + "webim/api/join.php?platform=" + platform,
                    leave: path + "webim/api/leave.php?platform=" + platform
    	};
	webim.buddy.defaults.url = path + "webim/api/user_info.php?platform=" + platform;
	webim.notification.defaults.url = path + "webim/api/notifications.php?platform=" + platform;
    

	webim.ui.emot.init({"dir": path + "webim/static/images/emot/default"});
	var soundUrls = {
		lib: path + "webim/static/assets/sound.swf",
		msg: path + "webim/static/assets/sound/msg.mp3"
	};
	var ui = new webim.ui(document.body, {
		soundUrls: soundUrls
	}), im = ui.im;
	ui.addApp("menu", {"data": menu});
	ui.layout.addShortcut( menu);
	ui.addApp("buddy");
	ui.addApp("room");
	ui.addApp("notification");
	ui.addApp("setting", {"data": webim.setting.defaults.data});
	ui.render();

})(webim);
