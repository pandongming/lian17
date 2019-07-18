<?php
namespace app\wxapi\controller;
use think\Controller;
use think\cache\driver\Redis;
use app\wxapi\model\WxCuser;
use app\common\controller\Home;
use think\Db;
require_once EXTEND_PATH.'wxpay/WxPay.Api.php';

class Course extends Home
{

      //配置属性
    private $appid;
    private $appsecret;
    private $access_token;
    private $redis;
    private $model;

    public function initialize()
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
            parent::initialize();

    }

    public function index()
    {

        Db::name('curriculum')->where('time','<=',time())->update(['status'=>0]);

        return $this->fetch('course/index');
    }

    public function getUserDetail()
    {
      $redirect_uri = urlencode('https://scx.myoffermaker.com/wxapi/Index/getUserInfo');
      $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$this->appid."&redirect_uri=".$redirect_uri."&response_type=code&scope=snsapi_userinfo&state=123#wechat_redirect";
      header('location:'.$url);

    }

    public function getUserInfo()
    {
        if (empty($_GET['code'])) {
          $redirect_uri = urlencode('https://scx.myoffermaker.com/wxapi/Index/getUserInfo');
          $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$this->appid."&redirect_uri=".$redirect_uri."&response_type=code&scope=snsapi_userinfo&state=123#wechat_redirect";
          header('location:'.$url);
        }
        $code = $_GET['code'];
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$this->appid."&secret=".$this->appsecret."&code=".$code."&grant_type=authorization_code";
        $code = '';
      $res = $this->https_request($url);
      $res= json_decode($res,true);
      $access_token = $res['access_token'];
      $openid = $res['openid'];
      $open = model('wx_cuser')->where('openid',$openid)->find();

      if (empty($open)) {
        $url = "https://api.weixin.qq.com/sns/userinfo?access_token=".$access_token."&openid=".$openid."&lang=zh_CN";
        $info = $this->https_request($url);
        $info = json_decode($info,true);
        $userdata=array(
                   'openid'         =>$info['openid'],
                   'nickname'       =>urlencode($info['nickname']),
                   'sex'            =>$info['sex'],
                   'language'       =>$info['language'],
                   'city'           =>$info['city'],
                   'province'       =>$info['province'],
                   'country'        =>$info['country'],
                   'headimgurl'     =>$info['headimgurl'],
                   );
        $result = model('wx_cuser')->allowField(true)->save($userdata);

      }
      $code = '';
      cookie('openid',$openid);
    }


    public function index2()
    {
        $weeks    = ['第一周', '第二周', '第三周', '第四周', '第五周'];
        $weekday = ['星期一', '星期二', '星期三', '星期四', '星期五'];

        foreach ($weekday as $key => $value) {
            foreach ($weeks as $k => $v) {
                $week = Db::name('curriculum')->where(['week' => $v])->where('weekday',$value)->order('time asc')->find();
                if ($week) {
                    $data[$v][$key] = $week;
                }else{
                    $data[$v][$key]['id'] = '';
                    $data[$v][$key]['week'] = '第一周';
                    $data[$v][$key]['weekday'] = $value;
                    $data[$v][$key]['course'] = '';
                    $data[$v][$key]['status'] = '';
                    $data[$v][$key]['price'] = '';
                    $data[$v][$key]['time'] = '';
                    $data[$v][$key]['create_time'] = '';
                    $data[$v][$key]['update_time'] = '';
                }
            }
        }

        return json_encode($data);

    }

    public function order()
    {
        $content = explode("|", $_POST['content']);

        $price = $_POST['price'];
        $sourse = [];
        foreach ($content as $key => $value) {
            $sor = explode(",", $value);
            $sore = explode("：", $sor[0]);
            $sourse[$key] = $sore[1];
        }

        $this->assign('price',$price);
        $this->assign('sourse',$sourse);
        $this->assign('content',$_POST['content']);

        return view('class/order');
    }

    public function pay()
    {
        echo json_encode(['status'=>200]);
    }


    public function agreement()
    {
        return $this->fetch('class/agreement');
    }

    public function pay_success()
    {
        return $this->fetch('class/pay_success');
    }

    // public function getdata()
    // {
    //     //得到系统的年月
    //     $tmp_date = date("Ym");
    //     //切割出年份
    //     $tmp_year = substr($tmp_date, 0, 4);
    //     //切割出月份
    //     $tmp_mon          = substr($tmp_date, 4, 2);
    //     $tmp_nextmonth    = mktime(0, 0, 0, $tmp_mon + 1, 1, $tmp_year);
    //     $tmp_forwardmonth = mktime(0, 0, 0, $tmp_mon - 1, 1, $tmp_year);

    //     $start = strtotime(date("Y-m-d", $tmp_nextmonth));
    //     // $start = strtotime(date("Y-m-01"));
    //     $t      = date('t');
    //     $end    = strtotime(date("Y-m-" . $t, $tmp_nextmonth));
    //     $res    = Db::name('curriculum')->where('time', 'between', [$start, $end])->select();
    //     $detail = [];

    //     foreach ($res as $key => $value) {
    //         $detail[$key] = $value;

    //     }
    //     foreach ($detail as $k => $v) {
    //         $qie = $k % 5;


    //         switch ($qie) {
    //             case '1':
    //                 $result['第一周'][] = $v;
    //                 break;
    //             case '2':
    //                 $result['第二周'][] = $v;
    //                 break;
    //             case '3':
    //                 $result['第三周'][] = $v;
    //                 break;
    //             case '4':
    //                 $result['第四周'][] = $v;
    //                 break;
    //             case '5':
    //                 $result['第五周'][] = $v;
    //                 break;
    //         }
    //     }
    //     return json_encode($result);

    // }

    // public static function getWeekDate($unixTime = '')
    // {
    //     $res       = "";
    //     $var2      = "";
    //     $unixTime  = is_numeric($unixTime) ? $unixTime : time();
    //     $weekarray = array('日', '一', '二', '三', '四', '五', '六');
    //     $var       = "星期" . $weekarray[date('w', $unixTime)];
    //     switch ($var) {
    //         case '星期天':
    //             $var  = time();
    //             $var2 = $var - (84600 * 6);
    //             break;
    //         case '星期一':
    //             $var  = time();
    //             $var2 = $var;
    //             break;
    //         case '星期二':
    //             $var  = time();
    //             $var2 = $var - 84600;
    //             break;
    //         case '星期三':
    //             $var  = time();
    //             $var2 = $var - (84600 * 2);
    //             break;
    //         case '星期四':
    //             $var  = time();
    //             $var2 = $var - (84600 * 3);
    //             break;
    //         case '星期五':
    //             $var  = time();
    //             $var2 = $var - (84600 * 4);
    //             break;
    //         case '星期六':
    //             $var  = time();
    //             $var2 = $var - (84600 * 5);
    //             break;
    //     }
    //     $res = strtotime(date('Y-m-d', $var2));
    //     return date('Y-m-d', $res);
    // }
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
