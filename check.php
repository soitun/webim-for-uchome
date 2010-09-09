<?php
header("Content-type: application/javascript");

echo "Connnect webim.us\n\n\n";
echo file_get_contents("http://www.webim.us/robots.txt");

echo "\n\n\nConnnect webim20.cn\n\n\n";
echo file_get_contents("http://www.webim20.cn/robots.txt");
