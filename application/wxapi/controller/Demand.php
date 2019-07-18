<?php
namespace app\wxapi\controller;
use app\common\model\Demand as Demands;
use app\common\controller\Frontend;

class Demand extends Frontend
{
    protected $noNeedRight =['*'];
    protected $noNeedLogin =['*'];

    public function index()
    {
        echo '11';
    }

    public function add()
    {

    }
}