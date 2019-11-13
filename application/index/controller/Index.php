<?php
namespace app\index\controller;

use think\console\Input;
use think\Controller;
use model\User;
use think\Session;

class Index extends  Controller
{
    public function index()
    {

        if(request()->isPost()) {
            //注册
            $zhuceData = input('post.');
            $userModel = model('User');
            if (!isset($zhuceData['pid'])){
                $this->error('请填写正确的注册码', 'index/login');
            }
            $zhuceData['password'] = md5($zhuceData['password']);
            $zhuceData['code'] = $this->randomkeys(6);
            $zhuceData['time'] = time();
            $user  = db('user');
            $oUser = $user->where('username','=',$zhuceData['username'])->find()['id'];
            if (is_int($oUser) && $oUser !=0){
                return '用户名重复';
            }
            if (isset($zhuceData['pid'])){
                $pid = $user->where('code','=',$zhuceData['pid'])->value('id');
                if (empty($pid)){
                    $this->error('请填写正确的注册码', 'index/login');
                }
                $zhuceData['pid']=$pid;
                $puid = $user->where('id','=',$pid)->value('uid');
                $userModel->save($zhuceData);
                $puid = $puid.','.$userModel->id;
                $res=$user->where('id','=',$pid)->update(['uid'=>$puid]);
                $userModel->save($zhuceData);
            }else{
                $userModel->save($zhuceData);
            }
            $this->redirect('index/login');
//            return 1;
        }
        if (request()->isGet()){
            $tjCode = input('code');
            $this->assign('code',$tjCode);
            return view('zhuce');
        }
        return view('zhuce');
    }
    public function login(){
        if (request()->isPost()){
            $loginData = input('post.');
            if (!isset($loginData['phone'])){
                $loginData['phone'] = 0;
            }
            $dbPwd = db('user')->where('username','=',$loginData['username'])->whereOr('phone','=',$loginData['phone'])->field('password,id')->find();
            if (md5($loginData['password'])==$dbPwd['password']){
                session('userId', $dbPwd['id']);
                $this->redirect('index/shop/index');
//                $this->success('登录成功','index/shop');
                return 1;
            }else {
                $this->error('登录失败', 'index/login');
                return 2;
            }
        }
        return view('login');
    }

    public function tet(){
        if (request()->isPost()){
            $loginData = input('post.');
            $loginData = json_decode($loginData);
//            if (!isset($loginData['phone'])){
//                $loginData['phone'] = 0;
//            }
            $dbPwd = db('user')->where('username','=',$loginData['username'])->whereOr('phone','=',$loginData['phone'])->field('password,id')->find();
            if (md5($loginData['password'])==$dbPwd['password']){
                session('userId', $dbPwd['id']);
                $this->success('登录成功','index/shop');
                return 1;
            }else {
                $this->error('登录失败', 'index/index');
                return 2;
            }
        }
    }

   
    function randomkeys($length)
    {
        $pattern = '1234567890ABCDEFGHIJKLOMNOPQRSTUVWXYZ';
        $key =null;
        for($i=0;$i<$length;$i++)
        {
            $key .= $pattern{mt_rand(0,36)};    //生成php随机数
        }
        return $key;
    }


}
