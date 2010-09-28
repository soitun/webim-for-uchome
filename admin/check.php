<?php

header("Content-type: application/javascript");
require_once( dirname( __FILE__ ) . '/' . 'admin_common.php' );
echo "IM Config\n-------------------------\n";
echo "domain: '" . $_IMC['domain'] . "'\n";
echo "host: '" . $_IMC['host'] . "'\n";
echo "port: '" . $_IMC['port'] . "'\n";

echo "\n\nAllow url fopen\n-------------------------\n";
echo ini_get('allow_url_fopen') ? "On" : "Off";

echo "\n\nIM check online\n-------------------------\n";
$data = $imclient->check_connect();
echo $data->success ? "Success" : "Faild";

echo "\n\nTest for port 80\n-------------------------\n";
$content = file_get_contents("http://www.google.com");
echo empty( $content ) ? "Faild" : "Success";

