<?php
namespace app\index\controller;

use http\Env\Request;
use think\console\Input;
use think\Controller;
use model\User;
use model\Loguser;
use think\Session;
use think\View;

class Member extends  Controller
{
    public function _initialize()
    {
        //验证是否登录
        if(session("userId")==null)
        {
            $this->error("您尚未登录，请登录后查看~","http://cc.jwhcx.com/index.php/index/index/login.html");
        }
    }
    public function isSm(){
        $userId =session('userId');
        $isSm = db('user')->where('id',$userId)->field('isSm')->find();
        if($isSm['isSm'] ==3)
        {
            $this->error("您实名认证没有通过，请重新实名认证后查看~","http://cc.jwhcx.com/index.php/index/member/realName.html");
        }
        if($isSm['isSm'] !=1)
        {

            $this->error("您尚未实名认证，请实名认证后查看~","http://cc.jwhcx.com/index.php/index/member/realName.html");
        }
    }
    public function index(){
//        echo 'member';
        $userModel = model('user');
        $userId = session('userId');
        $userData = db('user')->where('id',$userId)->find();
        $map['user_id']=['=',$userId];
        $map['type']=['=',2];
        $map['status']=['=',2];
        $recharge = db('recharge')->where($map)->field('sum(money)')->find();
//        dump($recharge);die();
        $userData = $userModel->whatMember($userData,'member');
        if($userData['memberName'] == " "){
            $userData['memberName'] ='游客';
        }
//        dump($userData);die();

        $this->assign('userData',$userData);
        $this->assign('recharge',$recharge['sum(money)']);
        return view();

    }
    
    public function bz(){
    	return view('bz');
    }
    public function realName(){
        $userId = session('userId');
        if (request()->isPost()){
            $postData = input('post.');
            $postData['userId'] = $userId;
            $postData['IDCardQ'] = $this->uploads('IDCardQ');
            $postData['IDCardH'] = $this->uploads('IDCardH');
            $postData['time'] = time();
            $postData['status'] =2;
//            dump($postData);die();
            $res = db('userInfo')->insert($postData);
//            if ($res){
//                $upRes = db('user')->where('id',$userId)->update(['isSm'=>2]);
                echo "<script>alert('添加成功，等待管理员验证');</script>";
                $this->redirect('member/index');
//            }
        }
        $userInfoData = db('userInfo')->where('userId',$userId)->find();
        $userSm = db('user')->where('id',$userId)->find()['isSm'];
        $this->assign([
                'userInfoData'=>$userInfoData,
                'userSm'=>$userSm,
            ]);
        return view('realName');
    }
    public function getAgency(){
        //成为代理
        $type = input('type');
        $userId = session('userId');
        $userModle = model('user');
        $ratioData = db('range')->select();
        if($type == 1){
            $agencyData = $userModle->agencyC($userId);
            if(empty($agencyData)){
                return '你还没有资格成为代理';
            }
            $levelmum = null;
            $level = 0;
            $resData['msg'] = '你还没有资格成为代理';
            $resData['level'] = $level;
            foreach ($agencyData as $k => $v){
                $levelmum[$k] = $v['consumption'] + $v['mun'];
            }
            arsort($levelmum);
            if ($levelmum[0]>$ratioData[0]['indexNum']){
                $lmun =array_sum($levelmum);
                if ($lmun>$ratioData[0]['sumNum']){
                    $level = 1;
                    $resData['msg'] = '你可以成为一级代理';
                    $resData['level'] = $level;
                }
            }
            if($levelmum[0]>$ratioData[1]['indexNum']){
                $lmun =array_sum($levelmum);
                if ($lmun>$ratioData[1]['sumNum']){
                    $level = 2;
                    $resData['msg'] = '你可以成为二级代理';
                    $resData['level'] = $level;
                }
            }
            if($levelmum[0]>$ratioData[2]['indexNum']){
                $lmun =array_sum($levelmum);
                if ($lmun>$ratioData[2]['sumNum']){
                    $level = 3;
                    $resData['msg'] = '你可以成为三级代理';
                    $resData['level'] = $level;
                }
            }
            $oldLevel = $userModle->where('id',$userId)->field('level')->find();
            if ($oldLevel['level'] == $level){
                $resData['level'] = 4;
                $resData['msg'] = '你还没有达成，成为上级代理的条件';
                return $resData ;
            }
            return $resData;
        }
        if ($type == 2){
            $level = input('level');
            $agencyData=[
                'time' => time(),
                'level' => $level,
                'userId' =>$userId,
            ];
            $logRes = db('agencylog')->insert($agencyData);
            $userRes = db('user')->where('id',$userId)->update(['level'=>$level]);
            if ($userRes){
                return 1;
            }else {
                return 2;
            }
        }

        return view('getAgency');


//      dump($level);
//      dump($levelmum);
//      dump($agencyData);die();
    }

    public function embody(){
        //体现
        $this->isSm();
        $userId = session('userId');
        if (request()->isPost()){
            $txBeans = input('txBeans');
            if(empty($txBeans)){
            	echo "<script>alert('必须输入金额')</script>" ;
            	return view('embody');
            }
            if ($txBeans%100 != 0){
                return '体现金额必须是一百的倍数';
            }
              $file = request()->file('image');
//            dump($file);die();
              if (empty($file)){
              	echo "<script>alert('必须上传收款二维码')</script>" ;
            	return view('embody');

              }
            $userBeans = db('user')->where('id',$userId)->field('beans')->find();
            $map['user_id']=['=',$userId];
            $map['type']=['=',2];
            $map['status']=['=',2];
//            $recharge = db('recharge')->where($map)->field('sum(money)')->find();
//            if (!empty($recharge)){
//                $userBeans['beans'] = $userBeans['beans'] - $recharge['sum(money)'];
//            }
//            dump($txBeans);dump()
            if ($txBeans > $userBeans['beans']){
                echo "<script>alert('青春豆不够')</script>" ;
                return view('embody');
            }
            $txMoney = intval(0.8*$txBeans);
            $embodyData = [
                'emcode'=> $this->randomkeys(6),
                'time' =>time(),
                'userId' =>$userId,
                'txbeans' =>$txBeans,
                'txmoney' =>$txMoney,
                'tximg' => $this->upload(),
            ];
//            dump($embodyData);die();
            $embodyRes = db('embody')->insert($embodyData);

            if ($embodyData){
                echo "<script>alert('提现提交成功')</script>" ;
            	return view('embody');
            }else{
               echo "<script>alert('提现提交失败')</script>" ;
            	return view('embody');
            }


        }
        
        return view('embody');
    }
    public function ft(){
//        echo "复投";
        if (request()->isPost()){
            $ftKey = input('type');
            $ftMun = input('ftNum');
//            if (!isset($ftMun)){
//                $ftMun = 'all';
//            }
//            dump($ftMun);
            if ($ftKey == 'ft'){
//                return $ftMun;
                $userId = session('userId');
                $userModel = Model('User');
                $oldMbeans = $userModel->field('beans,mbeans')->where('id','=',$userId)->find();
                if ($oldMbeans['beans'] < 100){
                    return '青春豆大于一百才能复投';
                }
                if ($ftMun != 0){
                    $nowMBeans =$oldMbeans['mbeans'] + $ftMun*2;
                    $nowBeans = $oldMbeans['beans'] - $ftMun;
                    $resUser = $userModel->where('id',$userId)->update(['mbeans'=>$nowMBeans,'beans'=>$nowBeans]);
                }elseif ($ftMun == 'all'){
                    $nowMBeans = $oldMbeans['beans']*2+$oldMbeans['mbeans'];
                    $nowBeans = 0;
                    $resUser = $userModel->where('id',$userId)->update(['mbeans'=>$nowMBeans,'beans'=>$nowBeans]);
                }
//                dump($nowMBeans);die();
                $ftData = [
                    'time'=>time(),
                    'userId' => $userId,
                    'ft_num' => $oldMbeans['beans'],
                    'now_num' => $nowMBeans,
                ];
                $resFt = db('ftlog')->insert($ftData);

                if ($resFt&&$resUser){
                    return '复投成功';
                }else{
                    return '复投失败';
                }
            }

        }
        $userId = session('userId');
        $ftData = db('ftlog')->where('userId','=',$userId)->select();
        $memberBeans = db('user')->where('id',$userId)->field('mbeans')->find();
        foreach ($ftData as $k => $v){
            $ftData[$k]['time'] = date("y/m/d H:i:s",$v['time']);
        }
        $this->assign([
            'ftData'=>$ftData,
            'memberBeans'=>$memberBeans['mbeans']
        ]);
        return view('ft');


    }

    public  function  logout(){
        Session::clear('think');
        $this->redirect('index/login');
    }

    public function  editUser(){
        //修改用户资料
        $userModel = model('user');
        $userId = session('userId');
        $getData = input('edit');
        $oldUserData = $userModel->where('id',$userId)->find();
        if ($getData=='edit'){
            $this->assign('oldUserData',$oldUserData);
            return view('eUser');
        }
        if (request()->isPost()){
            $postData = input('post.');
            $file = request()->file('image');
            if (!empty($file)){
                $postData['headimg']=$this->upload();

            }else{
                $postData['headimg']=$oldUserData['headimg'];
            }
            $postData['username'] = $postData['username']?$postData['username']:$oldUserData['username'];

            $postData['password'] = md5($postData['password']);
            unset($postData['image']);
            $editRes = $userModel->where('id',$userId)->update($postData);
            if ($editRes){
                $this->redirect('member/index');
            }else{
                return 2;
            }
        }


        $userData = $userModel->find($userId);
        $yqr = $userData->where('id',$userData['pid'])->find();
        $this->assign('userData',$userData);
        $this->assign('yqr',$yqr['username']);
        return view('editUser');
    }


    //签到
    public function qd(){
//        echo "签到";
        $userId =session('userId');
        $isSm = db('user')->where('id',$userId)->field('isSm')->find();
        if($isSm['isSm'] ==3)
        {
            return 100;
        }
        if($isSm['isSm'] !=1)
        {

            return 100;
        }




        $qd = input('data');
        $userModel = model('user');
        $userId = session('userId');
        $oldMbeans = $userModel->field('mbeans')->find($userId);
//        if ($oldMbeans['mbeans']<=0){
//            return 0;
//        }
        //判断今天是否可以签到

        $today = model('loguser')->isOK2($userId);
        if ($today ==1){
            $returnData['msg']="已签到,不能重复签到";
            $returnData['code']=100;
            $returnData['qd']=1;
            return $returnData;
        }
        if ($qd == 'qd'){
            $res = $userModel->qd($userId);
            if ($res['res'] == 1){

                //修改用户签到状态
                $userModel->where('id','=',$userId)->update(['isqd'=>1]);
                $returnData['msg']="签到成功";
                $returnData['code']=$res['add'];
                $returnData['qd']=2;
                return $returnData;
//                return $res['add'] ;
            }else{
                return 0;
            }
        }


    }
    public  function  beOrder(){
        //青春豆记录
        $userId = session('userId');
        $map['userId'] = $userId;
        $map['status'] = 1;
        $userBeans = db('user')->where('id',$userId)->field('beans')->find();
        $beData = db('beanslog')->where($map)->order('id desc')->field('time,beansMum,type')->select();
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
            }
            else if($v['type'] == 6){
                $beData[$k]['type'] = '服务中心奖励';
            }
        }
        $this->assign([
            'userBeans'=>$userBeans,
            'beData' =>$beData,
        ]);
        return view('beOrder');
    }

    public function reOrder(){
        //充值记录
        $userId = session('userId');
        $reData = db('recharge')->where('user_id',$userId)->order('id desc')->field('time,money,status')->select();
        $map['user_id']=$userId;
        $map['status']=2;
        $sum = db('recharge')->where($map)->field('sum(money)')->select();
//        dump($reData);die();
        foreach ($reData as $k => $v){
            $reData[$k]['time'] = date("y/m/d H:i:s",$v['time']);
//            echo $v['time'];
            if ($v['status'] == 1){
                $reData[$k]['status'] = '待审核';
            }else if($v['status'] == 2){
                $reData[$k]['status'] = '充值成功';
            }
        }
        $this->assign([
            'reData'=>$reData,
            'sum' => $sum
        ]);
        return view('reOrder');
    }

    public function confirm(){
        if (request()->isPost()){

        }
        $ewm = db('reEwm')->find(1);
        $this->assign('ewm',$ewm);
        return view('confirm');
    }

    public function recharge(){
//        echo "充值";
        if (request()->isPost()){
            $postData = input('post.');
            $uid = session('userId');
            $pid = db('user')->field('pid,member')->find($uid);
            if (!isset($postData['member'])){
                $postData['member'] = 0;
            }
            //计算获得的青春豆
            if ($postData['type']==1){
                //会员不能重复充值
//                if($postData['member'] <= $pid['member']){
//                    return 'no';
//                }
                //会员套餐
                if ($postData['member']==1&&$postData['mon']=='199'){
                    $rbeans = 300;
                }else if($postData['member']==2&&$postData['mon']=='299'){
                    $rbeans = 598;
                }else if($postData['member']==3&&$postData['mon']=='599'){
                    $rbeans = 1797;
                }
            }else if ($postData['type']==2){
                $rbeans = $postData['mon'];
            }
            $rcode = $this->randomkeys(4);
            $rechargeData = [
                'time' =>time(),
                'user_id' => $uid,
                'money' => $postData['mon'],
                'type' => $postData['type'],
                'member' => $postData['member'],
                'status' => 1,
                'pid' => $pid['pid']?$pid['pid']:0,
                'rcode' => 'YM'.$rcode,
                'rbeans' => $rbeans,
            ];
//            dump($rechargeData);die();
            $res = model('Recharge')->save($rechargeData);
            if ($res){
                return 1;
            }else{
                return false;
            }

        }
        return view();
    }

    public function oder(){
        $userId = session('userId');
        $orderdata = db('embody')->where('userId',$userId)->select();
        foreach ($orderdata as $k =>$v){
            $orderdata[$k]['time'] = date("y/m/d H:i:s",$v['time']);
            if ($v['status'] == 1){
                $orderdata[$k]['status'] = '等待确认';
            }else if($v['status'] == 2){
                $orderdata[$k]['status'] = '确认通过';
            }
        }
//        dump($orderdata);die();
        $this->assign([
            'orderdata' =>$orderdata,
        ]);
        return view('oder');
    }

    function  agency(){
        //代理
        $userId = session('userId');
        $userModel = model('user');
        $userData = $userModel->where('id',$userId)->find();
        $agencyRatio = db('range')->select();
//        dump($agencyRatio);die();
        if ($userData['level'] == 1){
            $userData['level']='一级代理';
//            $userData['ratio'] = "5%";
            $userData['ratio'] = ($agencyRatio[0]['ratio']*100)."%";
        }
        elseif ($userData['level'] == 2){
            $userData['level']='二级代理';
            $userData['ratio'] = ($agencyRatio[1]['ratio']*100)."%";
        }
        elseif ($userData['level'] == 3){
            $userData['level']='三级代理';
            $userData['ratio'] = ($agencyRatio[2]['ratio']*100)."%";
        }
        elseif ($userData['level'] == 0){
            $userData['level']='你还不是代理';
            $userData['ratio'] = "0%";
        }
        $agencyData = $userModel->agencyC($userId);
        $this->assign([
            'userData' => $userData,
            'agencyData' =>$agencyData,
        ]);
        return view();

    }

    public function upload(){
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('image');

        // 移动到框架应用根目录/public/uploads/ 目录下
        if($file){
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
            if($info){
                // 成功上传后 获取上传信息
                // 输出 jpg
//                echo $info->getExtension();
                // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
//                echo $info->getSaveName();
                return $info->getSaveName();
                // 输出 42a79759f284b767dfcb2a0197904287.jpg
//                echo $info->getFilename();
            }else{
                // 上传失败获取错误信息
                echo $file->getError();
            }
        }
    }
    public function uploads($name){
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file($name);

        // 移动到框架应用根目录/public/uploads/ 目录下
        if($file){
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
            if($info){
                // 成功上传后 获取上传信息
                // 输出 jpg
//                echo $info->getExtension();
                // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
//                echo $info->getSaveName();
                return $info->getSaveName();
                // 输出 42a79759f284b767dfcb2a0197904287.jpg
//                echo $info->getFilename();
            }else{
                // 上传失败获取错误信息
                echo $file->getError();
            }
        }
    }


    function share(){
        $userId = session('userId');
        $userCode = model('user')->where('id',$userId)->field('code')->find();
        $data = 'http://cc.jwhcx.com/index.php?code='.$userCode['code'];
        $ewm = $this->createTempQrcode($data);
        $this->assign([
            'ewm' => $ewm,
            'userCode' => $userCode['code'],
        ]);
        return view('share');
//        echo "<img src=".$ewm['data'].">";
    }
    function randomkeys($length)
    {
        $pattern = 'ABCDEFGHIJKLOMNOPQRSTUVWXYZ';
        $key =null;
        for($i=0;$i<$length-2;$i++)
        {
            $key .= $pattern{mt_rand(0,23)};    //生成php随机数
        }
        $pattern2 = '1234567890';
        $key2 =null;
        for($i=0;$i<$length+2;$i++)
        {
            $key .= $pattern2{mt_rand(0,9)};    //生成php随机数
        }
        return $key.$key2;
    }

    public function createTempQrcode($data)
    {
        Vendor('phpqrcode.phpqrcode');
        $object = new \QRcode();
        $errorCorrectionLevel = 'L';    //容错级别
        $matrixPointSize = 5;            //生成图片大小

        //打开缓冲区
        ob_start();
        //生成二维码图片
        $returnData = $object->png($data,false,$errorCorrectionLevel, $matrixPointSize, 2);
        //这里就是把生成的图片流从缓冲区保存到内存对象上，使用base64_encode变成编码字符串，通过json返回给页面。
        $imageString = base64_encode(ob_get_contents());
        //关闭缓冲区
        ob_end_clean();
        $base64 = "data:image/png;base64,".$imageString;

        $result['errcode'] = 0;
        $result['errmsg'] = 'ok';
        $result['data'] = $base64;
        return $result;
    }
}
