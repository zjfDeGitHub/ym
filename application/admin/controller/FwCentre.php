<?php
namespace app\admin\controller;
use think\Controller;
class FwCentre extends Controller
{
    public function index(){
        $fwData = db('user')->where('isFw',1)->filed('*,sum(fwNum) as fwNum')->select();
        $this->assign('fwData',$fwData);
        return view();
    }
}