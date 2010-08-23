<?php
header("Content-type: application/javascript");
include_once 'config.php';
include_once('lib/uchome.php');
$menu = array(
	array("title" => 'doing',"icon" =>"image/app/doing.gif","link" => "space.php?do=doing"),
	array("title" => 'album',"icon" =>"image/app/album.gif","link" => "space.php?do=album"),
	array("title" => 'blog',"icon" =>"image/app/blog.gif","link" => "space.php?do=blog"),
	array("title" => 'thread',"icon" =>"image/app/mtag.gif","link" => "space.php?do=thread"),
	array("title" => 'share',"icon" =>"image/app/share.gif","link" => "space.php?do=share")
);

//$menu[] = array("title" => 'imlogo',"icon" =>"webim/static/images/nextim.gif","link" => "http://www.nextim.cn");
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

    var menu = webim.JSON.decode('<?php echo json_encode($menu) ?>');
	webim.extend(webim.setting.defaults.data, webim.JSON.decode('<?php echo $setting ?>'));
	var webim = window.webim;
	webim.defaults.urls = {
		online:path + "webim/api/online.php",
		offline:path + "webim/api/offline.php",
		message:path + "webim/api/message.php",
		presence:path + "webim/api/presence.php",
		refresh:path + "webim/api/refresh.php",
		status:path + "webim/api/status.php"
	};
	webim.setting.defaults.url = path + "webim/api/setting.php";
	webim.history.defaults.urls = {
		load: path + "webim/api/history.php",
		clear: path + "webim/api/clear_history.php"
	};
    	webim.room.defaults.urls = {
                    member: path + "webim/api/members.php",
                    join: path + "webim/api/join.php",
                    leave: path + "webim/api/leave.php"
    	};
	webim.buddy.defaults.url = path + "webim/api/user_info.php";
	webim.notification.defaults.url = path + "webim/api/notifications.php";
    

	webim.ui.emot.init({"dir": path + "webim/static/images/emot/default"});
	var soundUrls = {
		lib: path + "webim/static/assets/sound.swf",
		msg: path + "webim/static/assets/sound/msg.mp3"
	};
	var ui = new webim.ui(document.body, {
		soundUrls: soundUrls
	}), im = ui.im;
	ui.addApp("menu", {"data": menu});
	//rm shortcut in uchome
	//ui.layout.addShortcut( menu);
	ui.addApp("buddy");
	ui.addApp("room");
	ui.addApp("notification");
	ui.addApp("setting", {"data": webim.setting.defaults.data});
	ui.render();
        im.autoOnline() && im.online();

})(webim);
