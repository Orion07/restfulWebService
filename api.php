<?php
include 'PDOdatabase.php';
$db = new Database();
if($db->Connect()){
    $pageContent = file_get_contents('php://input');
    $jsonObj = json_decode($pageContent);
    $method_name = $jsonObj->{"method_name"};
    if(!empty($method_name))
    {
        $method_params = $jsonObj->{"method_params"};
        //print_r($db->$method_name($method_params));
        echo $db->$method_name($method_params);
        //print_r(isset($jsonObj->method_params));
        //print_r($jsonObj->method_params);
    }else{
        echo "Method Yok...";
    }
}else
{
    echo "Veritabani Baglantisi yapilamadi...";
}
?>
