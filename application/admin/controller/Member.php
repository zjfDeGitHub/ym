<?php
namespace app\admin\controller;

use think\Controller;
use model\User;
use think\Session;

class Member extends  Base
{
    public function index(){

        return $this->fetch();
    }
    public function userBeansLst(){
        $userId = input('id');
        $map['userId'] = $userId;
        $map['status'] = 1;
        $userBeans = db('user')->where('id',$userId)->field('beans')->find();
        $beData = db('beanslog')->where($map)->order('id desc')->field('time,beansMum,type,id,ratio,uid')->select();
        foreach ($beData as $k => $v){
            $beData[$k]['time'] = date("y/m/d H:i:s",$v['time']);
//            echo $v['time'];
            if ($v['type'] == 1){
                $beData[$k]['type'] = '签到获得';
            }else if($v['type'] == 2){
                $beData[$k]['type'] = '充值获得';
            }else if($v['type'] == 3){
                $beData[$k]['type'] = '好友签到获得';
            }else if($v['type'] == 4){
                $beData[$k]['type'] = '好友充值获得';
            }else if($v['type'] == 5){
                $beData[$k]['type'] = '代理获得';
            }else if($v['type'] == 6){
                $beData[$k]['type'] = '服务中心奖励';
            }
        }

        $this->assign([
            'userBeans'=>$userBeans,
            'beData' =>$beData,
        ]);
        return view('userBeansLst');
    }
    public function lst(){
        $userModel=model('User');
        //分页
        $userLen = db('user')->count();
        $pageLen = ceil($userLen / 15);
        $pageIn = input('pageIn')?input('pageIn'):0;
        if ($pageIn != 0){
            $pageIn = $pageIn*15;
        }

//        $userInfo = db('user')->order('id asc')->limit($pageIn,15)->select();
        //查找


        $userInfo = db('user')->order('id asc')->select();
        if (request()->isPost()){
            $postData = input('post.');
            $userInfo = db('user')->where('username','like',$postData['value'])->order('id asc')->select();
            if (empty($userInfo)){
                echo "<script>alert('暂无结果');</script>";
                $this->redirect('member/lst');
            }
        }
//        dump($userInfo);die();
        $userInfo = $userModel->whatMember($userInfo,'member');
        foreach ($userInfo as $k => $v){
            if ($v['isSm'] == 1){
                $userInfo[$k]['isSm'] = '已通过';
            }else{
                $userInfo[$k]['isSm'] = '未通过';
            }
        }
        $this->assign([
            'data'=>$userInfo,
            'pageLen' =>$pageLen,
            'pageIn' =>$pageIn/15
        ]);
        return $this->fetch();

    }

    public function delUser(){
        $userModel = model('user');
        $userId = input('id');
//        dump($userId);die();
        $res = $userModel->where('id',$userId)->delete();
        if ($res){
            $this->success('删除成功！！！','lst','',1);
        }else{
            $this->success('删除失败！！！','lst');
        }

    }

    public function editUser(){
        $userModel = model('user');
        if (request()->isPost()){
            $editData = input('post.');
            $resUser = $userModel->isUpdate(true)->save($editData);
//            $this->redirect('lst');
            $this->success('修改成功！！！','lst');

        }
        $userId = input('id');
        $userData = $userModel->where('id',$userId)->field('username,id,level,isFw,	mbeans,beans,consumption')->find();
        $this->assign('userData',$userData);
        return view('editUser');
    }
    public  function passEmbody(){
        $emcode = input('emcode');
        $userModel = model('User');
        $embodyModel = db('Embody');
        $embodyData = $embodyModel->where('emcode','=',$emcode)->find();
        $oldBeans = $userModel->where('id','=',$embodyData['userId'])->field('beans')->find()['beans'];
        $newBeans = $oldBeans-$embodyData['txbeans'];
        $userUpdata = $userModel->where('id','=',$embodyData['userId'])->update(['beans'=>$newBeans]);
        $resEm= $embodyModel->where('emcode','=',$emcode)->update(['status'=>2]);
        if ($resEm){
            $this->redirect('embody');
        }
    }

    public function embody(){
        $embodyModel = model('Embody');
        $embodyData = $embodyModel->order('id desc')->select();
        //搜索
        if (request()->isPost()){
            $postData = input('post.');
//            dump($postData);die();
            if($postData['type'] == 'emcode'){
                $embodyData = $embodyModel->where('emcode','like',$postData['value'])->select();
            }
        }
        if (request()->isGet()){
            $getData = input('status');
            if ($getData==1){
                $embodyData = $embodyModel->where('status','=',1)->select();
            }

        }

        $userModel = model('User');
        foreach ($embodyData as $k =>$v){
            $embodyData[$k]['userId'] = $userModel->where('id',$v['userId'])->field('username')->find()['username'];
            $embodyData[$k]['time'] = date('Y-m-d H:i',$v['time']);
            if ($v['status'] == 1){
                $embodyData[$k]['status'] = '等待确认';
            }else if($v['status'] == 2){
                $embodyData[$k]['status'] = '完成';
            }
        }
//        dump($embodyData);die();
        $this->assign([
            'embodyData' =>$embodyData
        ]);
        return view('embody');
    }
    public function agency(){
//            echo '代理';
            $userModel = model('user');
//            $id = session('userId');
//            $resDown = $userModel->agencyC($id);
//            dump($resDown);die();
            $agencyData = $userModel->where('level','<>','0')->select() ;
            $this->assign('agencyData',$agencyData);
            return view('agencyLst');
    }

    public function agencyRatio(){
        $range = db('range');
        $ratio = db('ratio');
        if (request()->isPost()){
            $postData = input('post.');
            $postData['ratio'] = doubleval($postData['ratio']);
            $range->where('level',$postData['level'])->update($postData);
            $this->redirect('member/agencyRatio');
        }
        $data =$range->select();
        $luckData = $ratio->where('type',1)->select();
        $tjData = $ratio->where('type',2)->select();
        $this->assign([
            'data'=>$data,
            'luckData'=>$luckData,
            'tjData'=>$tjData,
        ]);
        return view('agencyRatio');
    }
    public function tjRatio(){
        $ratio = db('ratio');
        if (request()->isPost()){
            $postData = input('post.');
            $postData['ratio'] = doubleval($postData['ratio']);
            $ratio->where('id',$postData['id'])->update($postData);
            $this->redirect('member/agencyRatio');
        }
    }

    public function fwCentre(){
        //服务中心
        $fwData = db('user')->where('isFw',1)->select();
        $this->assign('fwData',$fwData);
        return view('fw');
    }

    public function fwadd(){
        $fwadd = input('fwadd');
        $userId = input('userId');
        $User = db('user');
        $olddata =  $User->where('id',$userId)->find();
        $oldfwNum = $olddata['fwNum'];
        $oldBeans= $olddata['beans'];
        $nowFwNum = intval($oldfwNum-doubleval($fwadd));

        if ($nowFwNum < 0){
            $this->error('发放超出范围','member/fwCentre','',2);
        }
        $nowBeans = $oldBeans + $fwadd;
        $res = $User->where('id',$userId)->update(['fwNum'=>$nowFwNum,'beans'=>$nowBeans]);
        $this->redirect('member/fwCentre');

    }

    public function  realName(){
        $id = input('id');
        if (isset($id)){
            $id = input('id');
            $res = db('user')->where('id',$id)->update(['isSm'=>1]);
            $res2= db('userInfo')->where('userId',$id)->update(['status'=>1]);
            if ($res){
                $this->redirect('member/realName');
            }
        }
        $realName = db('userInfo')->order('id desc')->select();
        foreach ($realName as $k =>$v){
            $realName[$k]['time'] = date('Y-m-d H:i',$v['time']);
            if ($v['status'] == 1){
                $realName[$k]['status'] = '通过';
            }elseif ($v['status'] == 2){
                $realName[$k]['status'] = '待审核';
            }
        }
        $this->assign('realName',$realName);
        return view('realName');
    }

    public function realNameD(){
        $id = input('id');
        $userInfoDb = db('userInfo');
        $res=db('user')->where('id',$id)->update(['isSm'=>3]);
        $delUserInfo =$userInfoDb->where('userId',$id)->find();
        @unlink(DEL_SRC.$delUserInfo['IDCardH']);
        @unlink(DEL_SRC.$delUserInfo['IDCardQ']);
        $res2 = $userInfoDb->where('userId',$id)->delete();

            $this->redirect('member/realName');

    }

}
