<?php
namespace app\wxapi\controller;
use think\Controller;
use think\cache\driver\Redis;
use app\wxapi\model\WxCuser;
use think\Db;

class Wx extends Controller
{
    //配置属性
    private $appid;
    private $appsecret;
    private $access_token;
    private $redis;
    private $model;
    private $url = "http://scx.myoffermaker.com/wxapi/";

    public function __construct()
    {       //配置初始化
            $this->appid = config('config.appid');
            $this->appsecret = config('config.appsecret');
            $this->redis = new Redis;
            $this->model = new WxCuser;
            //将access_token存入到redis中
            if(!isset($this->access_token) || empty($this->access_token)){
                $url="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->appid."&secret=".$this->appsecret;
                $res = $this->https_request($url);
                $result = json_decode($res,true);
                $this->access_token = $result['access_token'];
                $this->redis->set($this->appid,$this->access_token,0,3600); //设置存储时间 3600 秒
            }




    }

    public function index()
    {
      $openid = '';
      if(!isset($_GET['code'])){
        $redirect_uri = "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $url = $this->oauth2_authorize($redirect_uri,'snsapi_base','123');
        header('location:'.$url);
        exit();
      }else{
        $access_token = $this->oauth2_access_token($_GET['code']);
        $openid = $access_token['openid'];
        $info = $this->get_user_info($openid);
        if ($info['subscribe'] == 1) {
        cookie('openid',$openid);
            $this->redirect('Index/index');
        }
      }

    }

    //获取到OM公众号用户关注的信息,存储在数据中
    public function get_update()
    {
       $userlist = $this->get_user_list();
       for ($i=0; $i <count($userlist['data']['openid']) ; $i++) {
           $openid = $userlist['data']['openid'][$i];
           $res = $this->get_user_info($openid);
           $arr =[
              "openid"         => $res["openid"],
              "nickname"       => urlencode($res["nickname"]),
              "sex"            => $res["sex"],
              "language"       => $res["language"],
              "city"           => $res["city"],
              "province"       => $res["province"],
              "country"        => $res["country"],
              "headimgurl"     => $res["headimgurl"],
              "subscribe_time" => $res["subscribe_time"],
           ];
           $result = $this->model->allowField(true)->where(['openid'=>$res['openid']])->update($arr);
       }
    }


    //获取关注用户的列表
    public function get_user_list($next_openid = NULL)
    {
        $url="https://api.weixin.qq.com/cgi-bin/user/get?access_token=".$this->access_token."&next_openid=".$next_openid ;
        $res = $this->https_request($url);
        $list = json_decode($res,true);
        // 当统计到10000个关注用户是,自动进行第二个10000数据统计,并将数据合并
        if($list['count'] == 10000){
            $new = $this->get_user_list($next_openid=$list['next_openid']);
            $list["data"]["openid"] = array_merge_recursive($list["data"]['openid'], $new["data"]["openid"]);
        }

        return $list;
    }
    //获取用户基本信息
    public function get_user_info($openid)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$this->access_token."&openid=".$openid."&lang=zh_CN";
        $res = $this->https_request($url);

        return json_decode($res,true);
    }

    //微信用户获取网页授权 1.生成OAuth2.0的URl

    public function get_code()
    {
      $redirect_uri = urlencode('https://scx.myoffermaker.com/wxapi/wx/get_user_openid');
      $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$this->appid."&redirect_uri=".$redirect_uri."&response_type=code&scope=snsapi_base&state=123#wechat_redirect";

     header('location:'.$url);
    }
    //获取用户的openid

    public function get_user_openid()
    {
      $code = $_GET['code'];
      $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$this->appid."&secret=".$this->appsecret."&code=".$code."&grant_type=authorization_code";
      $res = $this->https_request($url);
      return json_decode($res,true);
    }


    public function oauth2_authorize($redirect_url, $scope, $state = NULL)
    {
        $url="https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$this->appid."&redirect_uri=".$redirect_url."&response_type=code&scope=".$scope."&state=".$state."#wechat_redirect";
        return $url;
    }
    // 2.通过code换取网页授权access_token

    public function oauth2_access_token($code)
    {
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$this->appid."&secret=".$this->appsecret."&code=".$code."&grant_type=authorization_code";
        $res = $this->https_request($url);
        return json_decode($res,true);
    }

      // 获取用户的基本信息
    public function oauth2_get_user_info($access_token, $openid)
    {
      $url = "https://api.weixin.qq.com/sns/userinfo?access_token=".$access_token."&openid=".$openid."&lang=zh_CN";
      $res = $this->https_request($url);
      return json_decode($res,true);
    }

    //http请求
    protected function https_request($url, $data=null)
    {
        $curl = curl_init();
        curl_setopt($curl,CURLOPT_URL,$url);
        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,FALSE);
        if(!empty($data)){
            curl_setopt($curl, CURLOPT_POST,1);
            curl_setopt($curl,CURLOPT_POSTFIELDS,$data);
        }

        curl_setopt($curl,CURLOPT_RETURNTRANSFER,TRUE);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }
}