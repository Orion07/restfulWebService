<?php

header('Content-Type: text/html; charset=utf-8');
include 'Mail/class.phpmailer.php';
include 'Classes.php';
define('DB_NAME', 'dbname');
define('DB_USER', 'dbuser');
define('DB_PASSWORD', 'dbpw');
define('DB_HOST', 'dbhost');
class Database
{
    public $db = null;
    public function Connect()
    {
        $dsn = sprintf("mysql:host=%s;dbname=%s",DB_HOST,DB_NAME);
        try{
            $this->db = new PDO($dsn,DB_USER,DB_PASSWORD);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $ex) {
            return false;
        }
        
    return true;
    }
    function test($json)
    {
        echo $json->{"isim"};
    }
    public function uyeGetir($json)
    {
        $query = $this->db->query("SELECT * FROM users");
        $rows = array();
        $test = "";
        foreach($query as $row) 
        {
            $uye = new Uye($row["id"],$row["password"],$row["ip"],$row["status"],$row["create_date"]);
            $rows[] = $uye->toObjectVars();//var_dump((array)$uye);//
            $test = $uye->toObjectVars();
        } 
    return json_encode(array("uyeGetir" => array($test)));
        
    }
    public function login($json)
    {
        $jsonArray = array("email","password","deviceID");
        $result = array();
        $login = "";
        if(!$this->jsonCheck($json, $jsonArray))
        {
            $result["result"] = 0;//hatalı bilgi
        }else{
            $login = new Login($json);
            $query = $this->db->prepare("SELECT * FROM users WHERE email = ?");
            $selectResult = $query->execute(array($login->getEmail()));
            if($query->rowCount() > 0 && $selectResult)
            {
                $fetch = $query->fetch();
                if($fetch["password"] == $login->getPassword())
                {
                    if($fetch["status"] == 1)
                    {
                        $login_token = $this->getLoginToken($login->getEmail());
                        $query = $this->db->prepare("UPDATE users SET deviceID = ?,login_token = ?,ip = ?,lastLogin_date = ? WHERE email = ?");
                        $updateResult = $query->execute(array($login->getDeviceID(),$login_token,$this->GetIP(),$this->getDate(),$login->getEmail()));
                        if($updateResult)
                        {
                            $result["result"] = 1;//kayıt başarılı
                            $result["is_login"] = 1;
                            $result["login_token"] = $login_token;
                            $result["email"] = $login->getEmail();
                        }else{
                            $result["result"] = 2;//sorgu hatası
                        }
                    }else{
                        $result["result"] = 3;//hesap aktif değil
                    }
                }else{
                    $result["result"] = 4;//şifre yanlış
                }
            }else{
                $result["result"] = 5;//böyle bir hesap yok
            }
        }
        return json_encode(array("login"=>$result));
    }
    public function signup($json)
    {
        $result = array();
        $jsonArray = array("firstname","lastname","email","password","deviceID","phone");
        $signup = "";
        if(!$this->jsonCheck($json,$jsonArray))
        {
            $result["result"] = 0;
        }else{
            $signup = new Signup($json);
            $query = $this->db->prepare("SELECT * FROM users WHERE email = ? or phone = ?");
            $selectResult = $query->execute(array($signup->getEmail(),$signup->getPhone()));
            if($query->rowCount() > 0 || !$selectResult)
            {
                $result["result"] = 2;
            }else{
                $activation_token = $this->getActivationToken($signup->getEmail(),$signup->getDeviceID());
                $query = $this->db->prepare("INSERT INTO users(firstname,lastname,email,password,deviceID,phone,ip,activation_token,create_date) VALUES(?,?,?,?,?,?,?,?,?)");
                $insertResult = $query->execute(array($signup->getFirstname(),$signup->getLastname(),$signup->getEmail(),$signup->getPassword(),$signup->getDeviceID(),$signup->getPhone(),$this->GetIP(),$activation_token,$this->getDate()));
                if($insertResult)
                {
                    if($this->sendEmail($signup->getEmail(),$signup->getFirstname(),$signup->getLastname(),$activation_token))
                    {
                        $result["result"] = 1;
                    }  else {
                        $result["result"] = 4;
                    }
                }else{
                    $result["result"] = 3;
                }
            }
        }
        //$rows = $query->fetch(PDO::FETCH_LAZY);
        return json_encode(array("signup"=>$result));
        //return json_encode($json->{"id"});
         
    }
    public function changePassword($json)
    {
        $result = array();
        $jsonArray = array("email","login_token","oldpw","newpw");
        $password = "";
        if(!$this->jsonCheck($json, $jsonArray))
        {
            $result["result"] = 0;
        }else{
            $password = new Password($json);
            $userid = $this->checkLoginToken($password->getEmail(), $password->getLogin_token());
            if($userid > 0){
                $query = $this->db->prepare("SELECT * FROM users WHERE email = ?");
                $selectResult = $query->execute(array($password->getEmail()));
                $result["rowCount"] = $query->rowCount();
                if($query->rowCount() == 1 && $selectResult)
                {
                    $fetch = $query->fetch();
                    if($fetch["password"] == $password->getOldPw())
                    {
                        $updateQuery = $this->db->prepare("UPDATE users SET password = ? WHERE email = ?");
                        $updateResult = $updateQuery->execute(array($password->getNewPw(),$password->getEmail()));
                        if($updateResult){
                            $result["result"] = 1;//şifre değiştirme başarılı
                        }else{
                            $result["result"] = 2;//sorgu hatası
                        }
                    }else{
                        $result["result"] = 3;//şifre yanlış
                    }
                }else{
                    $result["result"] = 4;//sorgu hatası 2
                }
            }else{
                $result["result"] = 5;//token geçersiz
            }
        }
        return json_encode(array("changePassword"=>$result));
    }
    public function changePhone($json)
    {
        $result = array();
        $jsonArray = array("email","login_token","phone");
        $phone = "";
        if(!$this->jsonCheck($json, $jsonArray))
        {
            $result["result"] = 0;
        }else{
            $phone = new Phone($json);
            $userid = $this->checkLoginToken($phone->getEmail(), $phone->getLogin_token());
            if($userid > 0){
                $updateQuery = $this->db->prepare("UPDATE users SET phone = ? WHERE email = ?");
                $updateResult = $updateQuery->execute(array($phone->getPhone(),$phone->getEmail()));
                if($updateResult){
                    $result["result"] = 1;//telefon değiştirme başarılı
                }else{
                    $result["result"] = 2;//sorgu hatası
                }
            }else{
                $result["result"] = 3;//token geçersiz
            }
        }
        return json_encode(array("changePhone"=>$result));
    }
    public function setStatus($email,$activation_token)
    {
        $query = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $selectResult = $query->execute(array($email));
        if($query->rowCount() > 0 && $selectResult)
        {
            $fetch = $query->fetch();
            if($fetch["status"] == 0)
            {
                if($fetch["email"] == $email && $fetch["activation_token"] = $activation_token)
                {
                    $query = $this->db->prepare("UPDATE users SET status = 1 WHERE email = ?");
                    $updateResult = $query->execute(array($email));
                    if($updateResult)
                        return true;
                    else
                        return false;
                }
                else
                {
                    return false;
                }
            }
            else
            {
                return false;
            }
        }
        else
        {
            return false;
        }
    }
    public function addAdvert($json)
    {
        $result = array();
        $jsonArray = array("email","login_token","title","price","category_id","cityPosition","universityPosition","details","photos");
        $ad = "";
        if(!$this->jsonCheck($json,$jsonArray))
        {
            $result["result"] = 0;//json datası eksik 
        }else{
            $ad = new Advert($json);
            $userid = $this->checkLoginToken($ad->getEmail(), $ad->getLogin_token());
            if($userid > 0){
                $photoIdArray = $this->addPhoto($ad->getPhotos());
                $photoCount = count($photoIdArray);
                if($photoCount > 0 ){
                    $query = $this->db->prepare("INSERT INTO adverts(user_id,category_id,title,price,cityPosition,universityPosition,details,create_date,active) VALUES(?,?,?,?,?,?,?,?,1)");
                    $insertResult = $query->execute(array($userid,$ad->getCategoryId(),$ad->getTitle(),$ad->getPrice(),$ad->getCityPosition(),$ad->getUniversityPosition(),$ad->getDetails(),$this->getDate()));
                    if($insertResult)
                    {
                        $advertId = $this->db->lastInsertId();
                        $successfulPhoto = 0;
                        foreach ($photoIdArray as $photoId){
                            $resultGallery = $this->addPhotoToGallery($advertId, $photoId);
                            if($resultGallery)
                                $successfulPhoto++;
                        }
                        if($photoCount == $successfulPhoto){
                            $result["result"] = 1;// ilan ekleme basarili 
                        }else{
                            $result["result"] = 5;//galeri oluşturulurken hata
                        }
                    }else{
                        $result["result"] = 3;//sorgu hatası
                    }
                }else{
                    $result["result"] = 4;//foto yüklemede hata
                }
            }else{
                $result["result"] = 2;//token geçersiz 
            }
        }
        $result["email"] = $ad->getEmail();
        $result["login_token"] = $ad->getLogin_token();
        return json_encode(array("addAdvert"=>$result));
    }
    public function addPhoto($photos)
    {
        $photoId = [];
        if(count($photos)> 0){
            foreach ($photos as $value){
                $query = $this->db->prepare("INSERT photo(photodata) VALUES(?)");
                $insertResult = $query->execute(array($value));
                if($insertResult){
                    $photoId[] = $this->db->lastInsertId();
                }
            }
        }
        return $photoId;
    }
    public function addPhotoToGallery($advertId,$photoId)
    {
        if($advertId > 0 && $photoId > 0){
            $query = $this->db->prepare("INSERT gallery(advert_id,photoid) VALUES(?,?)");
            $insertResult = $query->execute(array($advertId,$photoId));
            if($insertResult){
                return true;
            }
        }
        return false;
    }
    public function addMessage($json)
    {
        $result = array();
        $jsonArray = array("email","login_token","user_id","advert_id","message");
        $msg = "";
        if(!$this->jsonCheck($json,$jsonArray))
        {
            $result["result"] = 0;//json datası eksik 
        }else{
            $msg = new Message($json);
            $userid = $this->checkLoginToken($msg->getEmail(), $msg->getLogin_token());
            if($userid > 0 && $userid !=$msg->getUserid()){
                $query = $this->db->prepare("INSERT INTO messages(sender_id,recipient_id,advert_id,message,date,isread,sender_active,recipient_active) VALUES(?,?,?,?,?,?,?,?)");
                $insertResult = $query->execute(array($userid,$msg->getUserid(),$msg->getAdvertid(),$msg->getMessage(),$this->getDate(),false,1,1));
                    if($insertResult)
                    {
                        $result["result"] = 1;//mesaj başarıyla eklendi
                    }else{
                        $result["result"] = 3;//sorgu hatası
                    }
            }else{
                $result["result"] = 2;//token geçersiz 
            }
        }
        return json_encode(array("addMessage"=>$result));
    }
    public function getMessage($json)
    {
        $result = array();
        $jsonArray = array("email","login_token","type");
        $msg = "";
        if(!$this->jsonCheck($json,$jsonArray))
        {
            $result["result"] = 0;//json datası eksik 
        }else{
            $msg = new Message($json);
            $userid = $this->checkLoginToken($msg->getEmail(), $msg->getLogin_token());
            if($userid > 0 ){
                $sql = "";
                if($msg->getType())
                {
                    $sql = "select m.advert_id,a.title,u.firstname,u.lastname,m.id,m.message,m.date,m.isread,m.sender_id,u.phone from messages m inner join users u on u.userID = m.sender_id inner join adverts a on a.advert_id = m.advert_id where m.recipient_id = ? and m.recipient_active = 1 order by date desc";
                }
                else{
                    $sql = "select m.advert_id,a.title,u.firstname,u.lastname,m.id,m.message,m.date,m.isread,m.sender_id,u.phone from messages m inner join users u on u.userID = m.recipient_id inner join adverts a on a.advert_id = m.advert_id where m.sender_id = ? and m.sender_active = 1 order by date desc";
                }
                $query = $this->db->prepare($sql);
                $query->execute(array($userid));
                $fetch = $query->fetchAll(PDO::FETCH_ASSOC);
                $listsize = $query->rowCount();
                if($listsize > 0)
                {
                    $result["result"] = 1;//mesaj var liste gidicek
                    $result["messages"] = $fetch;
                }else{
                    $result["result"] = 3;//mesaj yok
                }
            }else{
                $result["result"] = 2;//token geçersiz 
            }
        }
        return json_encode(array("getMessage"=>$result));
    }
    public function confirmMessage($json)
    {
        $result = array();
        $jsonArray = array("email","login_token","msgid");
        $msg = "";
        if(!$this->jsonCheck($json,$jsonArray))
        {
            $result["result"] = 0;//json datası eksik 
        }else{
            $msg = new Message($json);
            $userid = $this->checkLoginToken($msg->getEmail(), $msg->getLogin_token());
            if($userid > 0 ){
                $query = $this->db->prepare("UPDATE messages SET isread = 1 WHERE id = ?");
                $updateResult = $query->execute(array($msg->getMsgid()));
                if($updateResult)
                    $result["result"] = 1;//mesaj okundu
                else
                    $result["result"] = 3;//sorgu hatası
            }else{
                $result["result"] = 2;//token geçersiz 
            }
        }
        return json_encode(array("confirmMessage"=>$result));
    }
    public function deleteMessage($json)
    {
        $result = array();
        $jsonArray = array("email","login_token","msgid","type");
        $msg = "";
        if(!$this->jsonCheck($json,$jsonArray))
        {
            $result["result"] = 0;//json datası eksik 
        }else{
            $msg = new Message($json);
            $userid = $this->checkLoginToken($msg->getEmail(), $msg->getLogin_token());
            if($userid > 0 ){
                $sql = "";
                if($msg->getType())
                    $sql = "UPDATE messages SET recipient_active = 0 WHERE id = ?";
                else
                    $sql = "UPDATE messages SET sender_active = 0 WHERE id = ?";
                $query = $this->db->prepare($sql);
                $updateResult = $query->execute(array($msg->getMsgid()));
                if($updateResult)
                    $result["result"] = 1;//mesaj pasif oldu
                else
                    $result["result"] = 3;//sorgu hatası
            }else{
                $result["result"] = 2;//token geçersiz 
            }
        }
        return json_encode(array("deleteMessage"=>$result));
    }
    /*
     $msg = new Message($json);
            $userid = $this->checkLoginToken($msg->getEmail(), $msg->getLogin_token());
            if($userid > 0 ){
                $query = $this->db->prepare("SELECT * FROM messages WHERE id = ?");
                $query->execute(array($msg->getMsgid()));
                $fetch = $query->fetch();//$query->fetchAll(PDO::FETCH_ASSOC);
                $listsize = $query->rowCount();
                if($listsize == 1 && $msg->getMsgid() == $fetch["id"])
                {
                    if($fetch["sender_id"] > 0 && $fetch["recipient_id"] > 0)
                    {
                        $sql = "";
                        if($msg->getType())
                            $sql = "UPDATE messages SET recipient_id = 0 WHERE id = ?";
                        else
                            $sql = "UPDATE messages SET sender_id = 0 WHERE id = ?";
                        $queryUpdate = $this->db->prepare($sql);
                        $updateResult = $queryUpdate->execute(array($msg->getMsgid()));
                        if($updateResult)
                            $result["result"] = 1;//mesaj silindi;
                        else
                            $result["result"] = 3;//sorgu hatası
                    }else{
                        $queryDelete = $this->db->prepare("DELETE FROM messages WHERE id = ?");
                        $resultDelete = $queryDelete->execute(array($msg->getMsgid()));
                        if($resultDelete){
                            $queryDelete = $this->db->prepare("DELETE FROM adverts WHERE advert_id = ?");
                            $resultDelete = $queryDelete->execute(array($fetch["advert_id"]));
                            if($resultDelete){
                                $result["result"] = 1;//mesaj ve bağlantılı olduğu ilan silindi 
                            }
                        }   
                        else
                            $result["result"] = 5;//sorgu hatası
                    }
                }else{
                    $result["result"] = 4;//mesaj yok
                }
            }else{
                $result["result"] = 2;//token geçersiz 
            }
     */
    public function searchAdvert($json)
    {
        $result = array();
        $jsonArray = array("email","login_token","query");
        $ad = "";
        if(!$this->jsonCheck($json,$jsonArray))
        {
            $result["result"] = 0;//json datası eksik 
        }else{
            $email = $json->email;
            $login_token = $json->login_token;
            $queryStr = $json->query;
            $userid = $this->checkLoginToken($email, $login_token);
            if($userid > 0)
            {
                $query = $this->db->prepare("select a.advert_id,p.photodata,a.title,a.cityPosition,a.price from adverts a join gallery g on a.advert_id = g.advert_id join photo p on g.photoid = p.photoid where a.title like ? and a.active = 1 group by a.advert_id order by create_date desc");
                $query->execute(array("%$queryStr%"));
                $fetch = $query->fetchAll(PDO::FETCH_ASSOC);
                $listsize = $query->rowCount();
                if($listsize > 0){
                    $result["list"] = $fetch;
                    $result["result"] = 1;//olumlu dönüş
                }else{
                    $result["result"] = 3;//kategoride ilan yok
                }
            }else{
                $result["result"] = 2;//geçersiz token
            }    
        }
        return json_encode(array("searchAdvert"=>$result));
    }
    public function getAdvert($json)
    {
        $result = array();
        $jsonArray = array("email","login_token","advert_id");
        $ad = "";
        if(!$this->jsonCheck($json,$jsonArray))
        {
            $result["result"] = 0;//json datası eksik 
        }else{
            $email = $json->{"email"};
            $login_token = $json->{"login_token"};
            $advert_id = $json->{"advert_id"};
            $userid = $this->checkLoginToken($email, $login_token);
            if($userid > 0)
            {
                $query = $this->db->prepare("SELECT a.advert_id,a.user_id,a.category_id,a.title,a.price,a.cityPosition,a.universityPosition,a.details,a.create_date,u.firstname,u.lastname,u.phone FROM adverts a JOIN users u ON a.user_id = u.userID WHERE a.advert_id = ?");
                $query->execute(array($advert_id));
                $fetch = $query->fetchAll(PDO::FETCH_ASSOC);
                $listsize = $query->rowCount();
                if($listsize == 1){
                    $result["advert"] = $fetch;
                    $queryPhoto = $this->db->prepare("SELECT * FROM photo WHERE photoid IN(SELECT photoid FROM gallery WHERE advert_id = ?)");
                    $queryPhoto->execute(array($advert_id));
                    $fetchPhoto = $queryPhoto->fetchAll(PDO::FETCH_ASSOC);
                    if($queryPhoto->rowCount()>0)
                    {
                        $result["result"] = 1;//olumlu dönüş
                        $result["photos"] = $fetchPhoto;
                    }else{
                        $result["result"] = 4;//foto yok
                    }
                }else{
                    $result["result"] = 3;//böyle bir ilan yok
                }
            }else{
                $result["result"] = 2;//geçersiz token
            }    
        }
        return json_encode(array("getAdvert"=>$result));
    }
    public function deleteAdvert($json)
    {
        $result = array();
        $jsonArray = array("email","login_token","advert_id");
        $ad = "";
        if(!$this->jsonCheck($json,$jsonArray))
        {
            $result["result"] = 0;//json datası eksik 
        }else{
            $email = $json->{"email"};
            $login_token = $json->{"login_token"};
            $advert_id = $json->{"advert_id"};
            $userid = $this->checkLoginToken($email, $login_token);
            if($userid > 0)
            {
                $query = $this->db->prepare("UPDATE adverts SET active = 0 WHERE advert_id = ? AND user_id = ?");
                $updateResult = $query->execute(array($advert_id,$userid));
                if($updateResult)
                    $result["result"] = 1;//ilan silme başarılı 
                else
                    $result["result"] = 3;//sorgu hatası 2
            }
            else{
                $result["result"] = 2;//geçersiz token
            }
        }
        return json_encode(array("deleteAdvert"=>$result));
        /*
          if($updateResult){
                    //delete g,p from gallery g inner join photo p on g.photoid = p.photoid where g.advert_id = 30;
                    $query = $this->db->prepare("delete g,p from gallery g inner join photo p on g.photoid = p.photoid where g.advert_id = ?");
                    $deleteResult = $query->execute(array($advert_id));
                    if($deleteResult)
                         $result["result"] = 1;//ilan silme başarılı 
                    else
                         $result["result"] = 4;//sorgu hatası 2
                }
         */
    }
    public function getCategoryList($json)
    {
        $result = array();
        $jsonArray = array("email","login_token","category_id");
        $ad = "";
        if(!$this->jsonCheck($json,$jsonArray))
        {
            $result["result"] = 0;//json datası eksik 
        }else{
            $email = $json->{"email"};
            $login_token = $json->{"login_token"};
            $category_id = $json->{"category_id"};
            $userid = $this->checkLoginToken($email, $login_token);
            if($userid > 0)
            {
                $query = null;
                if($category_id == 0){
                    $query = $this->db->query("select a.advert_id,p.photodata,a.title,a.cityPosition,a.price from adverts a join gallery g on a.advert_id = g.advert_id join photo p on g.photoid = p.photoid where a.active = 1 group by a.advert_id order by create_date desc limit 7");
                }
                else{
                    $query = $this->db->prepare("select a.advert_id,p.photodata,a.title,a.cityPosition,a.price from adverts a join gallery g on a.advert_id = g.advert_id join photo p on g.photoid = p.photoid where a.category_id = ? and a.active = 1 group by a.advert_id order by create_date desc");
                    $query->execute(array($category_id));
                }
                $fetch = $query->fetchAll(PDO::FETCH_ASSOC);
                $listsize = $query->rowCount();
                if($listsize > 0){
                    $result["list"] = $fetch;
                    $result["result"] = 1;//olumlu dönüş
                }else{
                    $result["result"] = 3;//kategoride ilan yok
                }
            }else{
                $result["result"] = 2;//geçersiz token
            }    
        }
        return json_encode(array("getCategoryList"=>$result));
    }
    public function getMyAdverts($json)
    {
        $result = array();
        $jsonArray = array("email","login_token");
        $ad = "";
        if(!$this->jsonCheck($json,$jsonArray))
        {
            $result["result"] = 0;//json datası eksik 
        }else{
            $email = $json->{"email"};
            $login_token = $json->{"login_token"};
            $userid = $this->checkLoginToken($email, $login_token);
            if($userid > 0)
            {
                $query = $this->db->prepare("select a.advert_id,p.photodata,a.title,a.cityPosition,a.price from adverts a join gallery g on a.advert_id = g.advert_id join photo p on g.photoid = p.photoid where a.user_id = ? and a.active = 1 group by a.advert_id order by create_date desc");
                $query->execute(array($userid));
                $fetch = $query->fetchAll(PDO::FETCH_ASSOC);
                $listsize = $query->rowCount();
                if($listsize > 0){
                    $result["list"] = $fetch;
                    $result["result"] = 1;//olumlu dönüş
                }else{
                    $result["result"] = 3;//kategoride ilan yok
                }
            }else{
                $result["result"] = 2;//geçersiz token
            }    
        }
        return json_encode(array("getMyAdverts"=>$result));
    }
    public function getHomePage($json)
    {
        $result = array();
        $jsonArray = array("email","login_token");
        $ad = "";
        if(!$this->jsonCheck($json,$jsonArray))
        {
            $result["result"] = 0;//json datası eksik 
        }else{
            $email = $json->{"email"};
            $login_token = $json->{"login_token"};
            $userid = $this->checkLoginToken($email, $login_token);
            if($userid > 0)
            {
                $query = $this->db->prepare("select a.advert_id,p.photodata,a.title,a.cityPosition,a.price from adverts a join gallery g on a.advert_id = g.advert_id join photo p on g.photoid = p.photoid where a.active = 1 order by create_date desc limit 7");
                $query->execute(array($userid));
                $fetch = $query->fetchAll(PDO::FETCH_ASSOC);
                $listsize = $query->rowCount();
                if($listsize > 0){
                    $result["list"] = $fetch;
                    $result["result"] = 1;//olumlu dönüş
                }else{
                    $result["result"] = 3;//kategoride ilan yok
                }
            }else{
                $result["result"] = 2;//geçersiz token
            }    
        }
        return json_encode(array("getHomePage"=>$result));
    }
    public function sendEmail($email,$firstname,$lastname,$activation_token)
    {  
        $mail = new PHPMailer();
        $mail->IsSMTP();
        $mail->SMTPAuth = true;
        $mail->Host = 'mail.yenicerionline.com';
        //$mail->Port = 587;
        $mail->Username = 'activation@yenicerionline.com';
        $mail->Password = 'activation123';
        $mail->SetFrom($mail->Username, 'Universite Ticaret Merkezi');
        //$mail->From = "activation@deathorder.com";
        $mail->AddAddress($email,$firstname. " " . $lastname);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'Universite Ticaret Merkezi Aktivasyon Emaili';
        $icerik = sprintf("<html><head></head><body><a href='http://www.yenicerionline.com/restfulWebService/activation.php?activation_token=%s&email=%s'>Aktivasyon için Tıklayınız...</a></body></html>",$activation_token,$email);//http://www.deathorder.com/restfulWebService/activation.php?activation_token=%s&email=%s
        $mail->MsgHTML($icerik);
        if($mail->Send()) {
            return true;
        } else {
            return false;
        }
    }
    
    public function jsonCheck($json,$jsonArray)
    {
        foreach ($jsonArray as $value) {
            if(!isset($json->{$value}))
                return false;
        }    
        return true;
    }
    public function GetIP()
    {
        if(getenv("HTTP_CLIENT_IP")) 
        {
            $ip = getenv("HTTP_CLIENT_IP");
        }elseif(getenv("HTTP_X_FORWARDED_FOR")) 
        {
            $ip = getenv("HTTP_X_FORWARDED_FOR");
            if (strstr($ip, ',')) 
            {
                $tmp = explode (',', $ip);
                $ip = trim($tmp[0]);
            }
         } else {
            $ip = getenv("REMOTE_ADDR");
         }
        return $ip;
    }
    public function getDate()
    {
        return date('Y-m-d H:i:s', time());
    }
    public function getActivationToken($email,$deviceID)
    {
        $tmp = sprintf("'%s','%s','%s'",$email,$deviceID,$this->getDate());
        return md5($tmp);
    }
    public function checkLoginToken($email,$login_token)
    {
        $query = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $selectResult = $query->execute(array($email));
        if($query->rowCount() == 1 && $selectResult){
            $fetch = $query->fetch();
                if($fetch["login_token"] == $login_token)
                {
                    return $fetch["userID"];
                }
        }
        return -1;
    }
    public function getLoginToken($email)
    {
        $tmp = sprintf("%s,%s",$email,$this->getDate());
        return md5($tmp);
    }
    public function getAllCategories($json)
    {
        $query = $this->db->prepare("SELECT * FROM categories WHERE parent_id = 0");
        $query->execute(array());
        $fetch = $query->fetchAll(PDO::FETCH_ASSOC);
        /*$obj = json_decode($fetch["json_data"]);
        //echo $obj->{"email"};
        //$obj->{"email"};
        foreach ($obj as $value) {
            echo $value;
        }*/
     //print_r($fetch);
       return json_encode(array("getAllCategories"=>$fetch));
    }
    public function getAllCities($json)
    {
        $query = $this->db->prepare("SELECT city FROM cities");
        $query->execute(array());
        $fetch = $query->fetchAll(PDO::FETCH_ASSOC);
        //print_r($fetch);
        return json_encode(array("getAllCities"=>$fetch));
         //return json_encode(array("getAllCities"=>$fetch));
    }
    public function testselect()
    {
        
    }
    }



?>
