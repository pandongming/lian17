<?php
namespace app\wxapi\controller;
use thini\Db;
use app\wxapi\controller\Base;
use app\common\model\WxcuserScoreLog as WxcuserScoreLogs;

class WxcuserScoreLog extends Base
{
    private $cModel;
    public function initialize()
    {
        parent::initialize();
        $this->cModel = new WxcuserScoreLogs;
        //配置初始化
    }

    public function index()
    {
        if(request()->isGet()){

            $userId  = session('userId');
            $user = model('wxcuser')->where(['id'=>$userId])->find();
            $dataList = model('wxcuser_score_log')->where(['user_id'=>$userId])->paginate(10);
            $this->assign('dataList',$dataList);
            $this->assign('user',$user);


            return $this->fetch('jflist');
        }
    }

    public function getScore()
    {
            $userId  = session('userId');
            $dataList = model('wxcuser_score_log')->where(['user_id'=>$userId])->paginate(2,false,['var_page'=>2]);
            return ajax_return('获取积分数据','',200,$dataList);

    }

    /**
     * @todo 敏感词过滤，返回结果
     * @param array $list  定义敏感词一维数组
     * @param string $string 要过滤的内容
     * @return string $log 处理结果
     */
    public function sensitive($list, $string){
      $count = 0; //违规词的个数
      $sensitiveWord = '';  //违规词
      $stringAfter = $string;  //替换后的内容
      $pattern = "/".implode("|",$list)."/i"; //定义正则表达式
      if(preg_match_all($pattern, $string, $matches)){ //匹配到了结果
        $patternList = $matches[0];  //匹配到的数组
        $count = count($patternList);
        $sensitiveWord = implode(',', $patternList); //敏感词数组转字符串
        $replaceArray = array_combine($patternList,array_fill(0,count($patternList),'*')); //把匹配到的数组进行合并，替换使用
        $stringAfter = strtr($string, $replaceArray); //结果替换
      }
      $log = "原句为 [ {$string} ]<br/>";
      if($count==0){
        $log .= "暂未匹配到敏感词！";
      }else{
        $log .= "匹配到 [ {$count} ]个敏感词：[ {$sensitiveWord} ]<br/>".
          "替换后为：[ {$stringAfter} ]";
      }
      return $log;
    }
}