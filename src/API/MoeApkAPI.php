<?php
//未完成
define("控制台地址","http://mcpe.moeapk.com/");
define("认证地址",控制台地址."sign");
define("连接确认",控制台地址."connect");

final class MoeApk{
    private $服务端;
    private $响应头,$响应码;
    private

    function __construct(){
        $this->服务端 = ServerAPI::request();
    }

    public function 连接($地址=连接确认){
        switch($this->获得状态码($地址)){
            case "200":
                console("[INFO] 控制台响应正常");
                return true;
            case "500":
            case "502":
            case "503":
            case "504":
                console("[ERROR] 控制台服务器发生错误");
                return false;
        }
    }



    private function 获得状态码($地址){
        $this->响应头=get_headers($地址);
        preg_match("/HTTP\/1.1 (.+?) OK/",$this->响应头,$this->响应码);
        return $this->响应码[1];
    }
}
?>