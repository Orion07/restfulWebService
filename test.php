<?php
header('Content-Type: text/html; charset=utf-8');

include 'PDOdatabase.php';
$db = new Database();
    if($db->Connect())
    {
//        $result = [];
//        $result["advert_id"] = 41;
//        $result["email"] = "l1211016016@stud.sdu.edu.tr";
//        $result["login_token"] = "0d764a580b49718131427c52a01f7cb5";
//        $arr = array('advert_id' => 41, 'email' => "l1211016016@stud.sdu.edu.tr", 'login_token' => "0d764a580b49718131427c52a01f7cb5");
//        $json = json_decode(json_encode($arr));
//        $str = "{\"advert_id\":41,\"email\":\"l1211016016@stud.sdu.edu.tr\",\"login_token\":\"0d764a580b49718131427c52a01f7cb5\"}";
        //echo "Data : " . $db->getAdvert($json);
        $pageContent = file_get_contents('php://input');
        $jsonObj = json_decode($pageContent);
        $method_name = $jsonObj->{"method_name"};
        if(!empty($method_name))
        {
            $method_params = $jsonObj->{"method_params"};
            print_r($db->$method_name($method_params));
            //echo $db->$method_name($method_params);
            //print_r(isset($jsonObj->method_params));
            //print_r($jsonObj->method_params);
        }else{
            //echo "Method Yok...";
        }
        echo $pageContent;
    }

    
?>