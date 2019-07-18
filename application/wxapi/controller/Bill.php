<?php
namespace app\wxapi\controller;
use app\common\model\Bill as Bills;
use app\common\controller\Frontend;

class Bill extends Frontend
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function index()
    {
        return $this->fetch();
    }

    public function add()
    {
        echo 111;
    }
}