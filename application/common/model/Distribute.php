<?php

namespace app\common\model;

use think\Db;
use think\Model;

/**
 * 地区数据模型
 */
class Distribute extends Model
{
     public function level_one($data = null)
    {
        $oneself_id = cmf_get_current_user_id(); //获取当前用户的id
        $oneself = Db::name('distribute');
        /* 一级 */
        $level_one = $oneself->where(['father_id' => $oneself_id])->order('id desc')->paginate(10);
        $data['page'] = $level_one->render();
        $data['lists'] = $level_one->items();
        /* 二级 */
        $level_two = $oneself->where(['grandpa_id' => $oneself_id])->order('id desc')->paginate(10);
        $data['page2'] = $level_two->render();
        $data['lists2'] = $level_two->items();
        /* 三级 */
        $level_there = $oneself->where(['great_grandpa_id' => $oneself_id])->order('id desc')->paginate(10);
        $data['page3'] = $level_there->render();
        $data['lists3'] = $level_there->items();

        return $data;
    }

    /**
     *   @Notes: $negate_obj ？=1(父级：father) ？=2（祖级:grandpa） ？=3（太级:great_grandpa）
     */
    public function negate_distinct($id, $negate_obj)
    {
        switch ($negate_obj) {
            case 1:
                $obj = 'father_id';
                break;
            case 2:
                $obj = 'grandpa_id';
                break;
            case 3:
                $obj = 'great_grandpa_id';
                break;
            default:
                return false;
        }
        $ret = Db::name('distribute')->where('id', $id)->update([$obj => 0]);
        return $ret;
    }

    /**
     *   @Notes:有点的冗余了，不过可以让的逻辑清楚些，用来知道子级，
     * 用递归应该可以实现无限级分销,
     * 但是这个应该是违法的，不过拿去用做无级分类还是很好的嘛。
     */
    public function next_distribute($id){
     $oneself = Db::name('distribute');
      /* 一级 */
      $level_one = $oneself->where(['father_id' => $id])->order('id desc')->paginate(10);
      $data['page'] = $level_one->render();
      $data['lists'] = $level_one->items();
      /* 二级 */
      $level_two = $oneself->where(['grandpa_id' => $id])->order('id desc')->paginate(10);
      $data['page2'] = $level_two->render();
      $data['lists2'] = $level_two->items();
      /* 三级 */
      $level_there = $oneself->where(['great_grandpa_id' => $id])->order('id desc')->paginate(10);
      $data['page3'] = $level_there->render();
      $data['lists3'] = $level_there->items();
      return $data;
    }

}