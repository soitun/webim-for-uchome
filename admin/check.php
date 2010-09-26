<?php

header("Content-type: application/javascript");
require_once( dirname( __FILE__ ) . '/' . 'admin_common.php' );

echo "\n\nIM Config \n";
echo "'" . $_IMC['domain'] . "'\n";
echo "'" . $_IMC['host'] . "'\n";
echo "'" . $_IMC['port'] . "'\n";

echo "\n\n\nIM check online \n";
print_r($imclient->check_connect());

echo "Test for port 80\n";
echo file_get_contents("http://www.webim20.cn");

