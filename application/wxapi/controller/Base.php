<?php
namespace app\wxapi\controller;
use app\wxapi\controller\Hashids;
use app\wxapi\model\Wxcuser;
use think\cache\driver\Redis;
use think\Controller;
use think\Cache;
use think\Session;
use think\Db;
// require_once EXTEND_PATH.'wxpay/WxPay.Api.php';

/**
 *
 */
class Base extends Controller
{

    //配置属性
    private $token;

    protected $appid;
    protected $openid;
    protected $appsecret;
    protected $access_token;
    protected $redis;
    protected $model;
    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';

    public function _initialize()
    {
        parent::_initialize();
        //配置初始化
        $this->appid     = config('appid');
        $this->appsecret = config('appkey');
        $this->redis     = new Redis;
        $this->model     = new Wxcuser;
        //将access_token存入到redis中
        if (!isset($this->access_token) || empty($this->access_token)) {
            $url                = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $this->appid . "&secret=" . $this->appsecret;
            $res                = $this->https_request($url);
            $result             = json_decode($res, true);
            $this->access_token = $result['access_token'];
            $this->redis->set($this->appid, $this->access_token, 0, 3600); //设置存储时间 3600 秒
        }


            $this->openid();

    }


    public function code()
    {
        $redirect_uri = urlencode('https://wx.lian17.com/wxapi/Base/openid');
        $url          = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . $this->appid . "&redirect_uri=" . $redirect_uri . "&response_type=code&scope=snsapi_userinfo&state=123#wechat_redirect";
        header('location:' . $url);
        exit;
    }
    public function openid()
    {


        if (empty(session('userId'))) {
            if (!isset($_GET['code'])) {
                $this->code();
            } else {
                $code = $_GET['code'];
                //获取openid;
                $url  = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . $this->appid . "&secret=" . $this->appsecret . "&code=" . $code . "&grant_type=authorization_code";
                $res          = $this->https_request($url);
                $res          = json_decode($res, true);
                $access_token = $res['access_token'];
                $openid       = $res['openid'];
                $open         = model('wxcuser')->where('openid', $openid)->find();
                if (empty($open)) {
                    $url      = "https://api.weixin.qq.com/sns/userinfo?access_token=" . $access_token . "&openid=" . $openid . "&lang=zh_CN";
                    $info     = $this->https_request($url);
                    $info     = json_decode($info, true);
                    $path     = 'avatar/' . time() . '.png';
                    $avatar   = $this->saveImage($info['headimgurl'], $path);
                    $userdata = array(
                        'openid'   => $info['openid'],
                        'nickname' => urlencode($info['nickname']),
                        'sex'      => $info['sex'],
                        'language' => $info['language'],
                        'city'     => $info['city'],
                        'province' => $info['province'],
                        'country'  => $info['country'],
                        'avatar'   => $avatar,
                    );
                    $result = model('wxcuser')->allowField(true)->save($userdata);
                    $userId = model('wxcuser')->getLastInsID();
                    if ($userId) {
                        $hashids = Hashids::instance(6, 'mackie'); //6为邀请码的长度，mackie为加盐密钥
                        //2.根据用户Id加密
                        $yqm = $hashids->encode($userId);
                        model('wxcuser')->where(['id' => $userId])->update(['invited_code' => $yqm]);
                    }
                    Cache::set('openid',$openid);
                    Cache::set('user',$openid);
                    session('openid',$openid);
                    session('user', $userdata);
                $this->redirect("https://wx.lian17.com/wxapi/wxcuser/index");


            }else{


                $userdata = Db::name('wxcuser')->where("openid",$openid)->find();
                session('openid',$userdata['openid']);

                Cache::set('openid',$userdata['openid']);
                Cache::set('userId',$userdata['id']);

                Session::set('user', $userdata);
                session('userId', $userdata['id']);
                header("Cache-Control: no-cache, must-revalidate");

                $this->redirect("https://wx.lian17.com/wxapi/wxcuser/index",'301');
            }

            //说明是已经是公众号成员，则调用用户信息存到session即可



            }
        }



    }


    public function getQrcode()
    {
        $url  = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=" . $this->access_token;
        $data = '{
                "expire_seconds": 1800,
                "action_name": "QR_LIMIT_SCENE",
                "action_info": {
                    "scene": {
                        "scene_id": 100000
                    }
                }
            }';
        $result     = $this->https_request($url, $data);
        $res        = json_decode($result, true);
        $ticket     = $res['ticket'];
        $qrcodeUrl  = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=" . urlencode($ticket);
        $r          = $this->downloadWeixinFile($qrcodeUrl);
        $filename   = 'qrcode.jpg';
        $local_file = fopen($filename, 'w');
        if (false !== $local_file) {
            if (false !== fwrite($local_file, $r["body"])) {
                fclose($local_file);
            }
        }

    }
    public function hashid_text()
    {
        /*-----------------根据用户ID生成邀请码 start-----------------------------*/
        //1.实例化Hashids类
        $hashids = Hashids::instance(6, 'mackie'); //6为邀请码的长度，mackie为加盐密钥
        //2.根据用户Id加密
        $yqm = $hashids->encode(cookie('userId'));
        //3.反向解密
        $id = $hashids->decode($yqm);
        //4.输出到前台页面
    }
    public function test()
    {
        $user_id   = cmf_get_current_user_id();
        $web       = cmf_get_domain();
        $url       = 'http://thirdwx.qlogo.cn/mmopen/vi_32/Q0j4TwGTfTIeic1cB1rvmSRsVaSWy9YSUdp08VbrbtLC7xH1ywSzM65JicULAA98MC6lxgxPq01cF5FaNRrIm1Kw/132';
        $path_file = './public/avatar/';
        // mkdir('avatar',0777);
        $path = 'avatar/' . $user_id . '.png';
        // $new_file = "./cs/cs.{$type}";

        $res = $this->saveImage($url, $path);
        return $res;
        // copy($res,$path_file);

    }

    public function saveImage($url, $new_file)
    {

        $header = array(
            'User-Agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:45.0) Gecko/20100101 Firefox/45.0',
            'Accept-Language: zh-CN,zh;q=0.8,en-US;q=0.5,en;q=0.3',
            'Accept-Encoding: gzip, deflate');
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        $data = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if ($code == 200) {
//把URL格式的图片转成Wxcuser64_encode格式的！
            $imgWxcuser64Code = "data:image/jpeg;Wxcuser64," . base64_encode($data);
        }
        $img_content = $imgWxcuser64Code; //图片内容
        //echo $img_content;exit;
        if (preg_match('/^(data:\s*image\/(\w+);Wxcuser64,)/', $img_content, $result)) {
            $type = $result[2]; //得到图片类型png?jpg?gif?
            if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $img_content)))) {
                return $new_file;
            }
        }

    }
    //$url, 微信头像地址
    //$new_file 保存的头像地址

    public function getSecond($url, $new_file)
    {
        $header = array(
            'User-Agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:45.0) Gecko/20100101 Firefox/45.0',
            'Accept-Language: zh-CN,zh;q=0.8,en-US;q=0.5,en;q=0.3',
            'Accept-Encoding: gzip, deflate');
        // $url='http://wx.qlogo.cn/mmopen/vi_32/Q0j4TwGTfTKkGpNuUhaBniatRsiaG7ksqmhUWzkk40kTRS6icQS7kJcsfxcibQo7vDFcKibr7NHb9YIXiaXsEtLcdL6A/0';
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        $data = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);
        if ($code == 200) {
            //把URL格式的图片转成Wxcuser64_encode格式的！
            $imgWxcuser64Code = "data:image/jpeg;Wxcuser64," . Wxcuser64_encode($data);
        }
        $img_content = $imgWxcuser64Code; //图片内容
        //echo $img_content;exit;
        if (preg_match('/^(data:\s*image\/(\w+);Wxcuser64,)/', $img_content, $result)) {
            // dump($result);die;
            $type = $result[2]; //得到图片类型png?jpg?gif?
            if (file_put_contents($new_file, Wxcuser64_decode(str_replace($result[1], '', $img_content)))) {
                return $new_file;
            }
        }
    }




    //http请求
    protected function https_request($url, $data = null)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }

    public function downloadWeixinFile($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_NOBODY, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $package  = curl_exec($curl);
        $httpinfo = curl_getinfo($curl);
        curl_close($curl);
        $imageAll = array_merge(array('body' => $package), array('header' => $httpinfo));
        return $imageAll;

    }





}
