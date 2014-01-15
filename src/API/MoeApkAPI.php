<?php
//未完成
//最后编辑：2014年1月13日
define("控制台地址","http://mcpe.moeapk.com/");//最好别改动这个
define("认证地址",控制台地址."sign/");
define("操作地址",控制台地址."run/");
define("连接确认",控制台地址."connect");
define("服务端私钥","ABCDEFG");//每个服务端唯一，需要领取

final class MoeApkAPI{
    private $服务端;
    private $响应头;
    private $响应码=Array();
    private $运行状态=0; //0是载入中，1是运行中
    private $控制台公匙;
    private $上次报告时间=0;
    private $服务端状态=true;
    private $临时数据;

    public $时间计数=0;

    function __construct(){
        $this->服务端 = ServerAPI::request();
        $this->连接();
        $this->获取公匙();
        $this->核对公匙();

        $this->服务端->api->console->register("moeapk-status", "", array($this, "命令处理器"));
        $this->服务端->api->console->register("moeapk-active", "", array($this, "命令处理器"));
        console("[INFO] MoeApk控制台API已经成功载入");
    }

    public function 命令处理($命令,$参数,$身份,$别名){
        if($身份!="rcon"){
            if($身份=="console"){
                $this->退出();
            }else{
                $this->服务端->api->ban->kick($身份,"非法使用控制台命令");
            }
        }
        switch($命令){
            case "moeapk-status":
                console("[INFO] 本次公匙：".$this->控制台公匙);
                console("[INFO] 上次报告：".(date("U")-$this->上次报告时间)."秒前");
                if($this->服务端状态){
                    console("[INFO] 控制台已经被控制台激活");
                }else{
                    console("[ERROR] 控制台尚未激活");
                }
                break;
            case "moeapk-active":
                $this->激活服务端();
                console("[INFO] 服务端已被控制台激活");
                break;
        }
    }

    public function 连接($地址=连接确认){
        $this->错误码处理($this->获得状态码($地址));
    }

    public function 获取($地址){
        return file_get_contents($地址);
    }

    public function 存活报告(){
        $this->连接(操作地址."keep_".$this->控制台公匙."_".count($this->服务端->clients));
    }

    public function 玩家进服($玩家名){
        if(!$this->服务端状态){
            console("[ERROR] 服务端未被控制台激活就有人尝试入服");
            $this->退出();
        }
        switch($this->获取(操作地址."player_".$this->控制台公匙."_join_".$玩家名)){
            case "200":
                console("[INFO] 玩家 ".$玩家名." 已成功通过控制台许可");
                $this->服内广播($this->获取(操作地址."player_".$this->控制台公匙."_info_".$玩家名));
                return true;
                break;
            case "403":
                console("[INFO] 玩家 ".$玩家名." 被控制台阻止进入服务器");
                return false;
                break;
            case "404":
                console("[INFO] 玩家 ".$玩家名." 没有在控制台注册");
                return false;
                break;
            default:
                console("[INFO] 玩家 ".$玩家名." 进服失败，控制台返回异常");
                return false;
                break;
        }

    }

    public function 玩家死亡($玩家名){
        $this->临时数据=$this->获取(操作地址."player_".$this->控制台公匙."_die_".$玩家名);
        //console("[INFO] 玩家 ".$玩家名." 已死亡".$this->临时数据."次");
        $this->服内广播("玩家 ".$玩家名." 已死亡".$this->临时数据."次");
    }

    private function 获取公匙(){
        $this->错误码处理($this->获得状态码(认证地址.服务端私钥."_".urlencode($this->服务端->name)."_".$this->服务端->maxClients."_".$this->服务端->port."_".$this->服务端->api->getProperty("rcon.password")));
        $this->控制台公匙=$this->获取(认证地址.服务端私钥."_".urlencode($this->服务端->name)."_".$this->服务端->maxClients."_".$this->服务端->port."_".$this->服务端->api->getProperty("rcon.password"));
        console("[DEBUG] 成功取得本次控制台公匙：".$this->控制台公匙);
    }

    private function 核对公匙(){
        $this->连接(操作地址."checksn_".$this->控制台公匙);
    }

    private function 错误码处理($响应代码){
        switch($响应代码){
            case "200":
                console("[INFO] 控制台响应正常");
                return true;
            case "500":
            case "502":
            case "503":
            case "504":
                console("[ERROR] 控制台服务器发生错误");
                $this->退出();
                break;
            case "403":
                console("[ERROR] 控制台拒绝了连接请求");
                $this->退出();
                break;
            case "404":
                console("[ERROR] 控制台响应所请求的事件不存在");
                $this->退出();
                break;
        }
        console("[ERROR] 服务端返回不正常状态");
        $this->退出();
    }



    private function 获得状态码($地址){
        $this->响应头=get_headers($地址);
        preg_match('/HTTP\/1.1 (.+?) /',$this->响应头[0],$this->响应码);
        console("[DEBUG] ".$地址);
        return $this->响应码[1];
    }

    private function 激活服务端(){
        $this->服务端状态=true;
    }

    private function 服内广播($信息){
        $this->服务端->api->chat->broadcast("[控制台] ".$信息);
    }

    private function 退出(){
        console("[INFO] 服务端开始退出");
        switch($this->运行状态){
            case 0:
            case 1:
                $this->服务端->close();//标准退出防止存档损坏
        }
        kill(getmypid());//强制退出防止终端卡住
    }


}
?>