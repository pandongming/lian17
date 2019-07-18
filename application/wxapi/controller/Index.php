<?php
namespace app\wxapi\controller;
use think\Controller;
use think\cache\driver\Redis;
use app\wxapi\model\Wxcuser;
use app\wxapi\model\WxOrder;
use Endroid\QrCode\QrCode;
use app\wxapi\controller\Base;
use think\Db;
use think\Session;

class Index extends Base
{



  public function _initialize()
    {       //配置初始化
        cookie('firsturl',$_SERVER['REQUEST_URI']);
            parent::_initialize();

    }
    public function index()
    {

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
     /*
     * 生成二维码图片
     */
    public function qr_code()
    {
        $link = 'https://wx.lian17.com/';
        $sha1 = sha1($link);
        $qrcode_dir =  '/qrcode/' . substr($sha1, 0, 2)  .'/'. substr($sha1, 2, 3) . '/';
        // if (!file_exists($qrcode_dir)) mkdir($qrcode_dir, 0777, true);
        $file_name = $qrcode_dir .$sha1 . '.png';
        header('Content-Type: image/png');
        // if (is_file($file_name)) {
        //     echo file_get_contents($file_name);
        // } else {
            $qrCode = new QrCode($link);
            echo $qrCode->writeString();
            $qrCode->writeFile($file_name);
        dump($qrCode);die;

        // }
        die();
    }



    public function makeQrcode(){
    /* http://phpqrcode.sourceforge.net/ 这是人家的官网 下载下来*/
        // vendor('phpqrcode.phpqrcode','simplewind/Core/Library/Vendor','.php');;//导入类库
        include_once(CMF_ROOT.'/vendor/phpqrcode/qrlib.php');
        $user_id = cookie('userId');//获取当前登录的用户id
        $web = cmf_get_domain();
        if($user_id){
            $web_path = str_replace( '\\' , '/' , realpath(dirname(CMF_ROOT).'/../'));//网站根目录
            $tempDir = $web_path."/public/static/qrcode/".$user_id."/";//以用户id做目录名
            $codeContents = 'https://wx.lian17.com/wxapi/wxcuser/regUser.html?invite_key='.$user_id; //推荐码 ，就是网站注册url,加上该用户id,也用来当做二维码的内容
            $fileName = $user_id.'.png'; //生成的二维码图片名
            $pngAbsoluteFilePath = $tempDir.$fileName; //合并成最终存储地址
            /* 为每个用户创建一个目录 */
            //iconv方法是为了防止中文乱码，保证可以创建识别中文目录，不用iconv方法格式的话，将无法创建中文目录
            $dir = iconv("UTF-8", "GBK", $tempDir);
            if (!file_exists($dir)){
                mkdir ($dir,0777,true);
            }
            \QRcode::png($codeContents, $pngAbsoluteFilePath, QR_ECLEVEL_Q ,5);//生成二维码
            /* 生成成功 */
//            echo '<img src="'.$tempDir.'" />';
//            exit();
            $avatar = cmf_get_current_user();//获取当前登录的前台用户的信息，未登录时，返回false
            if($avatar){
                $avatar = $avatar['avatar'];
                $logo = $web_path.'/public/'.$avatar;//获取用户头像地址
                /* 判断下该目录下可有头像，如果没有就给个默认的头像 */
                $logo = is_file ($logo) ? $logo : $web_path.'/public/static/default_logo.jpg';
                /* 如果生成的二维码图片存在，就给他中间加个用户的头像的 */
                if (file_exists($tempDir)){
                    $QR = $pngAbsoluteFilePath;//已经生成的原始二维码图
                    if ($logo !== FALSE) {
                        $QR = imagecreatefromstring(file_get_contents($QR));
                        $logo = imagecreatefromstring(file_get_contents($logo));
                        $QR_width = imagesx($QR);//二维码图片宽度
                        $QR_height = imagesy($QR);//二维码图片高度
                        $logo_width = imagesx($logo);//logo图片宽度
                        $logo_height = imagesy($logo);//logo图片高度
                        $logo_qr_width = $QR_width / 5;
                        $scale = $logo_width/$logo_qr_width;
                        $logo_qr_height = $logo_height/$scale;
                        $from_width = ($QR_width - $logo_qr_width) / 2;
                        //重新组合图片并调整大小
                        imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,
                            $logo_qr_height, $logo_width, $logo_height);
                    }
                    //输出图片
                    $res = imagepng($QR, $pngAbsoluteFilePath);
                    // dump($res);

                }
            }
                return ajax_return('二维码生成成功!','',200,'');

        }else{$this->error("出现问题了！可以重新登录试试！");}

    }


}
