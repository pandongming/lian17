<?php

namespace app\admin\model;

use think\Model;


class Wxcuser extends Model
{

    

    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'wxcuser';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    

    







}
