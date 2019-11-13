<?php
namespace app\index\controller;
use think\Controller;
class Base extends Controller
{
    public function _initialize()
    {
        if(session("userId")==null)
        {
            $this->error("您尚未登录，请登录后查看~","{:url('/index/index/index')}");
        }
    }


}