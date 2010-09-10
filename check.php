<?php
header("Content-type: application/javascript");
include_once('common.php');

echo "Connnect webim.us\n\n\n";
echo file_get_contents("http://www.webim.us/robots.txt");

echo "\n\n\nConnnect webim20.cn\n\n\n";
echo file_get_contents("http://www.webim20.cn/robots.txt");

$im = new WebIM($user, null, $_IMC['domain'], $_IMC['apikey'], $_IMC['host'], $_IMC['port']);

echo "\n\n\nIM Connnect... \n\n\n";
print_r($im->check_connect());
