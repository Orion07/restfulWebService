<?php
include 'PDOdatabase.php';
if(isset($_GET["activation_token"]) && isset($_GET["email"]))
{
    $activation_token = $_GET["activation_token"];
    $email = $_GET["email"];
    $db = new Database();
    if($db->Connect())
    {
        if($db->setStatus($email, $activation_token))
            echo "Aktivasyon Tamamlandi";
        else
            echo "Aktivasyon Basarisiz";
    }else
    {
        echo "Veritabani Baglantisi yapilamadi...";
    }
}
?>
