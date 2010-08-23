<?php 
$configRoot = '..' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR ;
include_once($configRoot . 'uchome.php');
$id = gp('id');
$type=gp('type');
if($id===NULL){
        echo "{empty}";
        exit();
}
echo json_encode(find_history($id,$type));
?>
