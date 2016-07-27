<?php
class Signup {
    private $firstname;
    private $lastname;
    private $email;
    private $password;
    private $deviceID;
    private $phone;
    
    function getFirstname() {
        return $this->firstname;
    }

    function getLastname() {
        return $this->lastname;
    }

    function getEmail() {
        return $this->email;
    }

    function getPassword() {
        return $this->password;
    }

    function getDeviceID() {
        return $this->deviceID;
    }

    function getPhone() {
        return $this->phone;
    }

        
    public function __construct($json){
        $this->firstname = $json->{"firstname"};
        $this->lastname = $json->{"lastname"};
        $this->email = $json->{"email"};
        $this->password = $json->{"password"};
        $this->deviceID = $json->{"deviceID"};
        $this->phone = $json->{"phone"};
    }
    public function toObjectVars()
    {
        return get_object_vars($this);
    }
}

class Login {
    private $email;
    private $password;
    private $deviceID;

    function getEmail() {
        return $this->email;
    }

    function getPassword() {
        return $this->password;
    }

    function getDeviceID() {
        return $this->deviceID;
    }
   
    public function __construct($json){
        //if(isset())
        if(isset($json->email))
            $this->email = $json->email;
        if(isset($json->password))
            $this->password = $json->password;
        if(isset($json->deviceID))
        $this->deviceID = $json->deviceID;
    }
    public function toObjectVars()
    {
        return get_object_vars($this);
    }
}
class Password{
    private $email;
    private $login_token;
    private $oldpw;
    private $newpw;
    function getEmail() {
        return $this->email;
    }
    function getLogin_token() {
        return $this->login_token;
    }
    function getOldPw(){
        return $this->oldpw;
    }
    function getNewPw(){
        return $this->newpw;
    }
    public function __construct($json) {
        $this->email = $json->{"email"};
        $this->login_token = $json->{"login_token"};
        $this->oldpw = $json->{"oldpw"};
        $this->newpw = $json->{"newpw"};
    }
}
class Phone {
    private $email;
    private $login_token;
    private $phone;
    function getEmail() {
        return $this->email;
    }
    function getLogin_token() {
        return $this->login_token;
    }
    function getPhone(){
        return $this->phone;
    }
    public function __construct($json) {
        $this->email = $json->{"email"};
        $this->login_token = $json->{"login_token"};
        $this->phone = $json->{"phone"};
    }
}

class Advert
{
    private $email;
    private $login_token;
    private $category_id;
    private $title;
    private $price;
    private $cityPosition;
    private $universityPosition;
    private $details;
    private $photos;
    function getPhotos()
    {
        return $this->photos;
    }
    function getCategoryId() {
        return $this->category_id;
    }
    function getEmail() {
        return $this->email;
    }
    function getLogin_token() {
        return $this->login_token;
    }

    function getTitle() {
        return $this->title;
    }

    function getPrice() {
        return $this->price;
    }

    function getCityPosition() {
        return $this->cityPosition;
    }

    function getUniversityPosition() {
        return $this->universityPosition;
    }

    function getDetails() {
        return $this->details;
    }
    function setEmail($email) {
        $this->email = $email;
    }
    function setLogin_token($login_token) {
        $this->login_token = $login_token;
    }
    function setCategoryId($category_id) {
        $this->category_id = category_id;
    }

    function setTitle($title) {
        $this->title = $title;
    }

    function setPrice($price) {
        $this->price = $price;
    }

    function setCityPosition($cityPosition) {
        $this->cityPosition = $cityPosition;
    }

    function setUniversityPosition($universityPosition) {
        $this->universityPosition = $universityPosition;
    }

    function setDetails($details) {
        $this->details = $details;
    }
    public function __construct($json) {
        $this->email = $json->{"email"};
        $this->login_token = $json->{"login_token"};
        $this->title = $json->{"title"};
        $this->price = $json->{"price"};
        $this->category_id = $json->{"category_id"};
        $this->cityPosition = $json->{"cityPosition"};
        $this->universityPosition = $json->{"universityPosition"};
        $this->details = $json->{"details"};
        $this->photos = $json->{"photos"};
    }



}
class Message{
    private $email;
    private $login_token;
    private $userid;
    private $advertid;
    private $message;
    private $type;
    private $msgid;
    
    function getUserid() {
        return $this->userid;
    }

    function getAdvertid() {
        return $this->advertid;
    }

    function getMessage() {
        return $this->message;
    }

    function getEmail() {
        return $this->email;
    }
    function getLogin_token() {
        return $this->login_token;
    }
    function getType() {
        return $this->type;
    }
    function getMsgid() {
        return $this->msgid;
    }
    public function __construct($json) {
        if(isset($json->email))
            $this->email = $json->email;
        if(isset($json->login_token))
            $this->login_token = $json->login_token;
        if(isset($json->user_id))
            $this->userid = $json->user_id;
        if(isset($json->advert_id))
            $this->advertid = $json->advert_id;
        if(isset($json->message))
            $this->message = $json->message;  
        if(isset($json->type))
            $this->type = $json->type; 
        if(isset($json->msgid))
            $this->msgid = $json->msgid;
    }
}
?>