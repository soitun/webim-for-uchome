<?php 
$configRoot = '..' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR ;
include_once($configRoot . 'uchome.php');


$ids = gp('ids');

if(empty($ids)) {
    echo "[]";
    exit();
}
echo json_encode(find_buddy($ids));
?>
