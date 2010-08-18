<?php
$platform = $_GET['platform'];
$configRoot = '..' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR ;
include_once($configRoot . 'uchome.php');
		
$data=gp('data');
if(!empty($data)){
        $_SGLOBAL['db']->query("UPDATE ".im_tname('setting')." SET web='$data' WHERE uid=$space[uid]");
}
echo "{success:true}";
