<?php

define('IM_ROOT', dirname(dirname(__FILE__)));
define('PRODUCT_ROOT', dirname(IM_ROOT));
$im_config_file = IM_ROOT.DIRECTORY_SEPARATOR.'config.php';
$product_config_file = PRODUCT_ROOT.DIRECTORY_SEPARATOR.'config.php';
$template_file = IM_ROOT.DIRECTORY_SEPARATOR.'install'.DIRECTORY_SEPARATOR.'webim_uchome.htm';
$db_file = IM_ROOT.DIRECTORY_SEPARATOR.'install'.DIRECTORY_SEPARATOR.'webim.sql';

include_once($product_config_file);

$db_config = array('host' => UC_DBHOST, 'username' => UC_DBUSER, 'password' => UC_DBPW, 'db_name' => UC_DBNAME, 'charset' => UC_DBCHARSET, 'db_prefix' => UC_DBTABLEPRE);

$need_check_paths = array();
if(file_exists($im_config_file)){
	include_once($im_config_file);
	$need_check_paths[] = $im_config_file;
}else{
	$need_check_paths[] = IM_ROOT;
}
$need_check_paths[] = $product_config_file;

$template_dir = PRODUCT_ROOT.DIRECTORY_SEPARATOR.'template';
$templates = array();
foreach(scandir($template_dir) as $k => $v){
	$d = $template_dir.DIRECTORY_SEPARATOR.$v;
	$f = $d.DIRECTORY_SEPARATOR.'footer.htm';
	if(file_exists($f)){
		$templates[] = $d;
		$need_check_paths[] = $d;
		$need_check_paths[] = $f;
	}
}

$unwritable_paths = select_unwritable_path($need_check_paths);
$subpathlen = strlen(dirname(PRODUCT_ROOT)) + 1;
if(!empty($unwritable_paths)){
	echo(unwritable_log($unwritable_paths, $subpathlen));
}elseif(!is_connectable($db_config)){
	echo("\n不能连接数据库\n\n");
}else{
	$PRE_IMC = isset($_IMC) ? $_IMC : null;
	include_once(IM_ROOT.DIRECTORY_SEPARATOR.'install'.DIRECTORY_SEPARATOR.'config.php');
	//$_IMC = input_config(merge_config($_IMC, $PRE_IMC));
	$logs = install_config($_IMC, $im_config_file, $product_config_file);
	$logs = array_merge($logs, install_template($templates, $template_file));
	$logs = array_merge($logs, install_db($db_config, $db_file));
	echo success_log($logs, $subpathlen);
}

function is_connectable($db){
	$link = mysql_connect($db['host'], $db['username'], $db['password']);
	$ok = $link && mysql_select_db($db['db_name'], $link);
	mysql_close();
	return $ok;
}

function install_db($db, $file){
	$logs = array();
	$sql = file_get_contents($file);
	/* Replace @charset to database charset at first. */
	$sql = preg_replace('/\@charset/', $db['charset'], $sql);
	//Add prefix
	$sql = preg_replace('/\webim_/', $db['db_prefix'].'webim_', $sql);

	$link = mysql_connect($db['host'], $db['username'], $db['password']);
	mysql_select_db($db['db_name'], $link);
	$charset = $db['charset'] || 'utf8';
	$sqls = explode(";", $sql);
	$succ = true;
	$error_msg = "";
	foreach($sqls as $k => $v){
		$v = trim($v);
		if(!empty($v)){
			$result = mysql_query($v.";");
			if(!$result){
				$succ = false;
				$error_msg .= mysql_error()."\n";
			}
		}
	}
	mysql_close();
	$logs[] = array($succ, "安装数据", $file);
	return $logs;
}

function install_config($config, $file, $product_file){
	$logs = array();
	$markup = "<?php\n\$_IMC = ".var_export($config, true).";\n";
	$logs[] = array(true, (file_exists($file) ? "更新" : "写入")."配置", $file);
	file_put_contents($file, $markup);
	$markup = file_get_contents($product_file);
	if(strpos($markup, 'webim/config.php') === false) {
		$markup = trim($markup);
		$markup = substr($markup, -2) == '?>' ? substr($markup, 0, -2) : $markup;
		$markup .= "include_once('webim/config.php');";
		file_put_contents($product_file, $markup);
		$logs[] = array(true, "加载配置", $product_file);
	}else{
		$logs[] = array(true, "检查加载", $product_file);
	}
	return $logs;
}

function install_template($templates, $file){
	$logs = array();
	$markup = file_get_contents($file);
	foreach($templates as $k => $v) {
		$tmp = $v.DIRECTORY_SEPARATOR.basename($file);
		$logs[] = array(true, (file_exists($tmp) ? "更新" : "写入")."模版", $tmp);
		file_put_contents($tmp, $markup);
		$inc = $v.DIRECTORY_SEPARATOR.'footer.htm';
		$name = basename($file, ".htm");
		$html = file_get_contents($inc);
		$html = preg_replace('/<\!--\{template\swebim[^>]+>/i', "", $html);
		list($html, $foot) = explode("</body>", $html);
		$logs[] = array(true, "加载模版", $inc);
		$inc_markup = "<!--{template ".$name."}-->";
		$html .= $inc_markup."</body>".$foot;
		file_put_contents($inc, $html);
	}
	return $logs;
}

function input_config($config){
	$q = stdin("输入im服务器地址 (".$config['imsvr']."): ");
	if(!empty($q)){
		$config['apikey'] = $q;
	}
	$q = stdin("输入注册域名 (".$config['domain']."): ");
	if(!empty($q)){
		$config['domain'] = $q;
	}
	$q = stdin("输入注册域名 (".$config['apikey']."): ");
	if(!empty($q)){
		$config['apikey'] = $q;
	}
	return $config;
}

function stdin($notice, $required = false){
	echo $notice;
	$stdin=fopen('php://stdin','r');
	$input=fgets($stdin, 1024);
	$q = trim($input);
	$q = $required && empty($q) ? stdin($notice) : $q;
	return $q;
}

function merge_config($new, $old){
	if($old){
		foreach($old as $k => $v){
			if(isset($new[$k]) && $k != 'version' && $k != 'enable'){
				$new[$k] = $v;
			}
		}
	}
	return $new;
}

function select_unwritable_path($paths){
	$p = array();
	foreach($paths as $k => $v){
		if(!is_writable($v)){
			$p[] = $v;
		}
	}
	return $p;
}
function success_log($logs, $truncate_size, $html = false){
	$desc = "webim安装成功";
	$markup = "";
	if($html){
	}else{
		$markup .= "\n---------------------------------\n";
		foreach($logs as $k => $v){
			$markup .= $v[1]." (".substr($v[2], $truncate_size).")	".($v[0] ? "ok" : "faild")."\n";
		}
		$markup .= "---------------------------------\n";
		$markup .= "\n".$desc."\n\n";
	}
	return $markup;
}
function unwritable_log($paths, $truncate_size = 0, $html = false){
	$desc = "下面这些文件需要可写权限才能继续安装，请修改这些文件的权限为777";
	$markup = "";
	if($html){
	}else{
		$markup .= "\n".$desc."\n";
		$markup .= "---------------------------------\n";
		foreach($paths as $k => $v){
			$markup .= substr($v, $truncate_size)."\n";
		}
		$markup .= "---------------------------------\n\n";
	}
	return $markup;
}
?>
