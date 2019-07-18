<?php

namespace app\common\model;

use think\Model;


class Team extends Model
{



    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'team';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // protected $relation="wxcuser";

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];

    public function wxcuser()
    {
        return $this->hasOne('Wxcuser','id','userId');
    }










}
