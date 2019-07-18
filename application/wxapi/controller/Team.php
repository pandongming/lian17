<?php
namespace app\wxapi\controller;
use app\common\controller\Backend;
use app\common\model\Team as Teams;
use think\Db;

class Team extends Backend
{

    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $layout = '';
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new Teams;

    }
    public function index()
    {
        $users               = session('user');
        $dataArr             = model('wxcuser')->where(['openid' => $users['openid']])->find();
        $dataArr['nickname'] = urldecode($dataArr['nickname']);
        $this->assign('data',$dataArr);
       return $this->fetch();
    }

    public function add()
    {
        if(request()->isPost()){
            $data = request()->post();
            // dump($data);die;
            $data['formData']['imgUrl'] = json_encode($data['formData']['imgUrl']);
            $result = model('team')->allowField(true)->save($data['formData']);
            if($result){
                return ajax_return('创建成功,正在审核中...',url('Wxcuser/index'),200);
            }
        }


    }

    public function updates($id)
    {

        $row = $this->model->get($id);
        $row['imgUrl'] = json_decode($row['imgUrl'],true);


        if ($this->request->isPost()) {
            $params = $this->request->post();
            $params =  $params['formData'];

            // dump($params);die;

            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    $result = $row->allowField(true)->save($params);

                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }

                if($result !== false){
                    ajax_return('编辑成功',url('Wxcuser/index'),200);
                }

            }
        }
        $this->view->assign("data", $row);
        return $this->view->fetch('edit');
    }

    public function delImg()
    {
        $idx = 0;
        $data = $this->model->get(21);
        $data['imgUrl'] = json_decode($data['imgUrl'],true);
        $res = $this->array_remove_by_key($data['imgUrl'],$idx);
        $res = $this->model->where(['id'=>21])->insert($data);
        dump($res);

    }

   public function array_remove_by_key($arr, $key){
    if(!array_key_exists($key, $arr)){
        return $arr;
    }
    $keys = array_keys($arr);
    $index = array_search($key, $keys);
    if($index !== FALSE){
        array_splice($arr, $index, 1);
    }
    return $arr;

}

}
