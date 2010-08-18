<?php 
$configRoot = '..' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR ;
include_once($configRoot . 'uchome.php');
$ids = gp('ids');
if($ids===NULL){
        echo "{empty}";
        exit();
}
//echo json_encode($ids);
echo json_encode(find_history($ids));
?>
