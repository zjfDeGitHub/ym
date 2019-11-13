<?php
namespace app\admin\controller;

use think\Controller;
use model\User;
use think\Session;

class Login extends  Controller
{
    public function index(){
        if (request()->isPost()){
            $loginData = input('post.');
            $dbUser = db('admin')->where('username','=',$loginData['username'])->find();
            if (md5($loginData['password']) == $dbUser['password']){
                session('userId',$dbUser['id']);
                $this->success('登录成功','admin/index/index');
            }else{
                $this->error('登录失败','admin/login/index');
            }
        }

        return $this->fetch();
    }

}
