<?php
namespace app\wxapi\controller;
use app\wxapi\controller\Hashids;
use app\wxapi\model\Wxcuser as Wxcusers;
use think\cache\driver\Redis;
use think\Controller;
use think\Cache;
use think\Session;
use think\Db;
// require_once EXTEND_PATH.'wxpay/WxPay.Api.php';

/**
 *
 */
class Wxcuser extends Base
{

    //配置属性
    private $token;
    public function initialize()
    {
        parent::initialize();
        //配置初始化
    }


    public function index()
    {
        $openid = session('openid');
        $user = session('user');
        $data  = Db::name('wxcuser')->where(['openid' => $openid])->find();
        $data['nickname'] = urldecode($data['nickname']);

        //个人用户 type == 0
        if($data['type'] == 0){
            $this->assign('user', $user);
            $this->assign('data', $data);
            return view('baseInfo');
        }else{
            echo '11';
        }


    }


    //注册页面
    public function regUser()
    {
        $url2 = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=" . $this->access_token . "&openid=" . session('openid') . "&lang=zh_CN";
        $res  = Base::https_request($url2);
        $res  = json_decode($res, true);
        if ($res['subscribe'] == 1) {
            $url       = request()->url(true);
            $arr       = parse_url($url);
            $arr_query = convertUrlQuery($arr['query']);
            $userDatas = session('user');


            /* 获取邀请码 ，并转存下，简单明了 */
            $invite_key = $arr_query['invite_key'];

            $data = model('wxcuser')->where(['invited_code' => $invite_key])->find(); //邀请者的信息
            $data['nickname'] = urldecode($data['nickname']);
            $openid = session('openid');
            $token = $this->getToken();
            $url      = "https://api.weixin.qq.com/sns/userinfo?access_token=" . $token . "&openid=" . $openid . "&lang=zh_CN";
            $r = Base::https_request($url);
            $r = json_decode($r,true);
            $userData = Db::name('wxcuser')->where(['openid' =>$openid])->find(); //自己的信息
            $userData['nickname'] = urldecode($userData['nickname']);

            if($userData['id'] === $data['id']) {
                $this->success('提示!自己无法邀请自己!',url('index'));
            }else{
                $this->assign('userData', $userData);
                $this->assign('data', $data);
                return view('detail');
            }

        } else {
            $url = "https://mp.weixin.qq.com/mp/profile_ext?action=home&__biz=Mzg3NzIwNTU0MQ==&scene=126&bizpsid=0&sharer_username=gh_a447d1354078&clicktime=1559033374#wechat_redirect";
            // $this->alert('请先关注公众号', 'jump', $url);
            $this->alert("请先关注公众号",'jump',$url);

        }

    }


    public function getToken()
    {
          $url  = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . config('appid') . "&secret=" . config('appkey');
            $res     = Base::https_request($url);
            $result  = json_decode($res, true);
           return $result['access_token'];

    }

    public function alert($tip = "", $type = "", $url = "")
    {
        $js = "<script>";
     if ($tip)
         $js .= "alert('" . $tip . "');";
     switch ($type) {
         case "close" : //关闭页面
            $js .= "window.close();";
             break;
         case "back" : //返回
            $js .= "history.back(-1);";
             break;
         case "refresh" : //刷新
            $js .= "parent.location.reload();";
             break;
         case "top" : //框架退出
            if ($url)
                 $js .= "top.location.href='" . $url . "';";
             break;
         case "jump" : //跳转
            if ($url)
                 $js .= "location.href='" . $url . "';";
             break;
         default :
             break;
     }
     $js .= "</script>";
     echo $js;
     if ($type) {
         exit();
     }
    }

    public function userInfo()
    {
        $openid = session('openid');
        $userId = session('userId');

        $data = model('wxcuser')->where(['id'=>$userId])->whereOr(['openid'=>$openid])->find();
        $data['nickname'] = urldecode($data['nickname']);



        $this->assign('data',$data);
        return view('personal');
    }



    //执行注册
    public function doReg()
    {

        $post_arr = request()->post();

        $data     = [
            'user_id'   => $post_arr['id'],
            'father_id' => empty($post_arr['father_id']) ? '' : $post_arr['father_id'],
            'user_name' => empty($post_arr['user_name']) ? '' : $post_arr['user_name'],
        ];
        $updata = [
            'type'      => $post_arr['type'],
            'user_name' => $post_arr['user_name'],
        ];
        $result = Db::name('distribute')->insert($data);
        $res    = Db::name('wxcuser')->where(['id' => $post_arr['id']])->update($updata);
        if ($result) {
            $this->share_out($post_arr['id']);
        }

        ajax_return('加入成功', '', 200, '');

    }
    public function myInfo()
    {
        //判断是否创建圈子
        $users               = session('user');
        $dataArr             = model('wxcuser')->where(['openid' => $users['openid']])->find();
        $dataArr['nickname'] = urldecode($dataArr['nickname']);

        $teamData = Db::name('team')->where(['userId'=>$dataArr['id']])->find();

        if(empty($teamData)){
            $this->alert('请先创建圈子','jump',url('team/index'));
        }else{



        $file                = "static/qrcode/" . $dataArr['id'] . "/" . $dataArr['id'] . ".png";
        // $file="/static/qrcode/4/4.png";
        $res = file_exists($file);
        if (file_exists($file)) {
            $status = 1;
        } else {
            $status = 0;
        }

        $fatherData = Db::name('distribute')->where(['user_id' => $dataArr['id']])->find();
        $data       = Db::name('distribute')
            ->alias('d')
            ->join('wxcuser wx', 'd.user_id=wx.id', 'left')
            ->where(['d.father_id' => $dataArr['id']]) //条件:状态为1
            ->order('d.id desc') //根据id降序排列
            ->limit(10) //限制10条
            ->select();
        $countData = Db::name('distribute')->where(['father_id' => $dataArr['id']])->count();
        foreach ($data as $k => $v) {
            $data[$k]['nickname'] = urldecode($data[$k]['nickname']);
            # code...
        }

        $teamData['imgUrl'] = json_decode($teamData['imgUrl'],true);

        // dump($teamData['imgUrl']);die;

        $this->assign('countData', $countData);
        $this->assign('dataArr', $dataArr);
        $this->assign('fatherData', $fatherData);
        $this->assign('data', $data);
        $this->assign('status', $status);
        $this->assign('teamData',$teamData);
        return view('myshuju');
        }

    }

    public function check_file_exists($file)
    {

        // 远程文件
        if (strtolower(substr($file, 0, 4)) == 'https') {
            $header = get_headers($file, true);
            return isset($header[0]) && (strpos($header[0], '200') || strpos($header[0], '304')); // 本地文件
        } else {
            return file_exists($file);
        }

    }
    public function building()
    {
        $user             = cmf_get_current_user();
        $user['nickname'] = urldecode($user['nickname']);
        $user_id          = session('userId');

        // dump($user);die;
        $this->assign('user', $user);
        $this->assign('userId', $user_id);
        return view('wait');
    }

    public function myInfo2()
    {
        $distribute = new Wxcusers();
        $data       = $distribute->level_one(); //在模型里具体实现数据库操作
        $user       = cmf_get_current_user(); //获取前台当前登录用户信息，如果没有登，就返回空
        $this->assign($user);
        //cmf自带的模板分页渲染方法，将获取分类好数组传给前端去处理
        /* 儿级 */
        $this->assign("page", $data['page']); //当前页码
        $this->assign("lists", $data['lists']); //所有的数据及分类
        /* 孙级 */
        // $this->assign("page2", $data['page2']);
        // $this->assign("lists2", $data['lists2']);
        /* 曾孙级 */
        // $this->assign("page3", $data['page3']);
        // $this->assign("lists3", $data['lists3']);
        return $this->fetch('index');
    }
    /**
     *   @Notes:查询下一级的成员
     */
    public function next()
    {
        $distribute = new DistributeModel();
        //获取前端传来的参数，这是当前儿子成员，当儿子成为父级，那它的儿子自然是之前父级成员的孙子，也就是下一级了
        $distribute = new DistributeModel();
        $id         = $this->request->param("id", 0, "intval");
        $data       = $distribute->next_distribute($id);
        $user       = cmf_get_current_user();
        $this->assign($user);
        /* 一级 */
        $this->assign("page", $data['page']);
        $this->assign("lists", $data['lists']);
        /* 二级 */
        $this->assign("page2", $data['page2']);
        $this->assign("lists2", $data['lists2']);
        /* 三级 */
        $this->assign("page3", $data['page3']);
        $this->assign("lists3", $data['lists3']);
        return $this->fetch('index');
    }

    /**
     *   @Notes:取消层级关系
     */
    public function negate()
    {
        $data       = $this->request->param();
        $distribute = new DistributeModel();
        //看看获取到的参数是几号，毕竟如果父亲拒绝儿子的供养，不代表爷爷也愿意，所以更新下表字段就行，将父级id改成0就行了。
        $ret = $distribute->negate_distinct($data['id'], $data['negate_obj']); //$data['negate_obj']:1父级；2祖级；3太级；
        if ($ret) {
            $this->success("取消关系成功！你再也获取不到他的分成了！");
        } else {
            $this->error("取消失败了！");
        }

    }

    /**
     *   @Notes: 获取邀请码，如果没有就不做处理，有的话将数据写入层级关系表里面distribute
     */
    public function collect_invite($userId = null, $name = null, $invite_key)
    {
        // $request    = Request::instance(); //都封装在\think\Request，上面use下
        // $invite_key = $request->get('invite_key');
        // if (empty($invite_key)) {
        //     //这里好像有点啰嗦，但是可以预防可能多种不确定情况，可能吧！我也不知道
        //     $invite_key = $request->cookie('invite_key');
        // }
        if (!empty($invite_key)) {
            $ret = Db::name('distribute')->where('user_id', $invite_key)->find();
            if ($ret) {
                //咱不用时间戳，虽然这样可能发生时区问题，可是这仅仅是给我自己看的方便，所以就无视吧。
                $time = date("Y-m-d H:i:s", time());
                $data = [
                    'user_id'      => $userId,
                    'father_id'    => empty($ret['user_id']) ? '' : $ret['user_id'],
                    'grandpa_id'   => empty($ret['father_id']) ? '' : $ret['father_id'],
                    'user_name'    => empty($name) ? '' : $name,
                    'father_name'  => empty($ret['user_name']) ? '' : $ret['user_name'],
                    'grandpa_name' => empty($ret['father_name']) ? '' : $ret['father_name'],
                ];
                Db::name('distribute')->insert($data);
            } else {return false;}

        } else {return false;}
    }

    /**
     *   @Notes:给新注册的用户赠送些积分，顺便给其父级们奖励下些
     */
    public function share_out($user_id)
    {
        $share_data = Db::name('share_out')->select(); //这个用来给后台设置分成百分比的
        // dump($share_data[0]['']);die;
        $var       = 100 * $share_data[0]['share_out_num'] / 100;
        $var2      = 100 * $share_data[1]['share_out_num'] / 100;
        $data_user = Db::name('wxcuser')->where('id', $user_id)->find();
        if ($data_user) {
            $data = $data_user['score'] + 100; //在这里给注册用户加积分
            Db::name('wxcuser')->where('id', $user_id)->update(['score' => $data]);
        }
        //后面是给推荐人们分的积分
        //先去层级关系中查好关系
        $data_out = Db::name('distribute')->where('user_id', $user_id)->find();
        if ($data_out) {
            $data_father = Db::name('wxcuser')->where('id', $data_out['father_id'])->find();
            // $data_grandpa           = Db::name('wxcuser')->where('id', $data_out['grandpa_id'])->find();
            $data_father_con = $data_out['father_contribute'];
            // $data_grandpa_con       = $data_out['grandpa_contribute'];
            /* 父级奖励*/
            if ($data_father) {
                $var         = 100 * $share_data[1]['share_out_num'] / 100;
                $data_father = $data_father['score'] + $var;
                Db::name('wxcuser')->where('id', $data_out['father_id'])->update(['score' => $data_father]);
                $data = [
                    'user_id'      => $data_out['father_id'],
                    'change_score' => $var,
                    'score'        => $data_father,
                    'remark'       => '邀请新用户:' . urldecode($data_user['nickname']) . '注册奖励'.$var.'积分',
                ];
                Db::name('wxcuser_score_log')->insert($data);
                Db::name('distribute')->where('user_id', $user_id)->update(['father_contribute' => ($data_father_con + $var)]);
            }
            /* 祖级奖励*/
            // if ($data_grandpa) {
            //     $var          = 100 * $share_data[1]['share_out_num'] / 100;
            //     $data_grandpa = $data_grandpa['score'] + $var;
            //     Db::table('wxcuser')->where('id', $data_out['grandpa_id'])->update(['score' => $data_grandpa]);
            //     $data = [
            //         'user_id'      => $data_out['grandpa_id'],
            //         'change_score' => $var,
            //         'score'        => $data_grandpa,
            //         'remark'       => '新用户:' . $data_user['nickname'] . '注册奖励积分-2级',
            //     ];
            //     Db::name('wxcuser_score_log')->insert($data);
            //     Db::name('distribute')->where('user_id', $user_id)->update(['grandpa_contribute' => ($data_grandpa_con + $var)]);
            // }

        }
    }

    public function makeQrcode()
    {
        /* http://phpqrcode.sourceforge.net/ 这是人家的官网 下载下来*/
        // vendor('phpqrcode.phpqrcode','simplewind/Core/Library/Vendor','.php');;//导入类库
        include_once CMF_ROOT . '/vendor/phpqrcode/qrlib.php';
        $users      = cmf_get_current_user();
        $userData   = model('wxcuser')->where(['openid' => $users['openid']])->find(); //获取当前登录的用户id
        $invite_key = model('wxcuser')->where(['id' => $userData['id']])->field('id,invited_code')->find();
        $web        = cmf_get_domain();
        if ($userData['id']) {
            $web_path            = str_replace('\\', '/', realpath(dirname(CMF_ROOT) . '/../')); //网站根目录
            $tempDir             = $web_path . "/public/static/qrcode/" . $userData['id'] . "/"; //以用户id做目录名
            $codeContents        = 'https://wx.lian17.com/wxapi/wxcuser/regUser.html?invite_key=' . $invite_key['invited_code']; //推荐码 ，就是网站注册url,加上该用户id,也用来当做二维码的内容
            $fileName            = $userData['id'] . '.png'; //生成的二维码图片名
            $pngAbsoluteFilePath = $tempDir . $fileName; //合并成最终存储地址
            /* 为每个用户创建一个目录 */
            //iconv方法是为了防止中文乱码，保证可以创建识别中文目录，不用iconv方法格式的话，将无法创建中文目录
            $dir = iconv("UTF-8", "GBK", $tempDir);
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
            \QRcode::png($codeContents, $pngAbsoluteFilePath, QR_ECLEVEL_Q, 5); //生成二维码
            /* 生成成功 */
//            echo '<img src="'.$tempDir.'" />';
            //            exit();
            $avatar = cmf_get_current_user(); //获取当前登录的前台用户的信息，未登录时，返回false
            if ($avatar) {
                $avatar = $avatar['avatar'];
                $logo   = $web_path . '/public/' . $avatar; //获取用户头像地址
                /* 判断下该目录下可有头像，如果没有就给个默认的头像 */
                $logo = is_file($logo) ? $logo : $web_path . '/public/static/default_logo.jpg';
                /* 如果生成的二维码图片存在，就给他中间加个用户的头像的 */
                if (file_exists($tempDir)) {
                    $QR = $pngAbsoluteFilePath; //已经生成的原始二维码图
                    if ($logo !== false) {
                        $QR             = imagecreatefromstring(file_get_contents($QR));
                        $logo           = imagecreatefromstring(file_get_contents($logo));
                        $QR_width       = imagesx($QR); //二维码图片宽度
                        $QR_height      = imagesy($QR); //二维码图片高度
                        $logo_width     = imagesx($logo); //logo图片宽度
                        $logo_height    = imagesy($logo); //logo图片高度
                        $logo_qr_width  = $QR_width / 5;
                        $scale          = $logo_width / $logo_qr_width;
                        $logo_qr_height = $logo_height / $scale;
                        $from_width     = ($QR_width - $logo_qr_width) / 2;
                        //重新组合图片并调整大小
                        imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,
                            $logo_qr_height, $logo_width, $logo_height);
                    }
                    //输出图片
                    $res = imagepng($QR, $pngAbsoluteFilePath);
                    // dump($res);

                }
            }
            return ajax_return('二维码生成成功!', '', 200, '');

        } else { $this->error("出现问题了！可以重新登录试试！");}

    }

    public function activity()
    {
        return view('activity');
    }

    public function pionts()
    {
        return view('pionts');
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
