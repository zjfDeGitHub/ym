<?php
namespace app\admin\controller;

use think\Controller;
use model\User;
use think\Session;

class Index extends  Base
{
    public function logout(){
        session(null);
        session(null, 'think');
        $this->redirect('login/index');
    }
    public function index(){

        return $this->fetch();
    }

    public function rlst(){

        $rechatgeData = db('recharge')->alias('r')->order('id desc')->join('user u','r.user_id = u.id','left')->field('r.*,u.username');
        //搜索
        if (request()->isPost()){
            $postData = input('post.');
//            dump($postData);die();
            if($postData['type'] == 'rcode'){
                $rechatgeData = $rechatgeData->where('rcode','like',$postData['value']);
            }
            if ($postData['type'] == 'username'){
                $rechatgeData = $rechatgeData->where('u.username','like',$postData['value']);
            }
        }
        if (request()->isGet()){
            $getData = input('status');
            if ($getData==1){
                $rechatgeData = $rechatgeData->where('status','=',1);
            }

        }
        $rechatgeData= $rechatgeData->paginate(15);
//        dump($rechatgeData);die();
//        foreach ($rechatgeData as $k =>$v){
//            if($v['type'] == 1){
//                if ($v['member'] == 1){
//                    $rechatgeData[$k]['member'] = '青春达人';
//                }else if($v['member'] == 2){
//                    $rechatgeData[$k]['member'] = '美丽达人';
//                }else if($v['member'] == 3){
//                    $rechatgeData[$k]['member'] = '时尚达人';
//                }else{
//                    $rechatgeData[$k]['member'] = '直接充值';
//                }
//
//            }
//        }
        $this->assign([
            'data'=>$rechatgeData,
        ]);
        return view('rlist');
    }

    public function  reEwm(){
        if (request()->isPost()){
            $reEwm['img'] = $this->uploads('img');
            $reEwm['time'] = time();
            db('reEwm')->where('id',1)->update($reEwm);
            $this->redirect('index/reEwm');
        }
        $ewm = db('reEwm')->select();
        $ewm[0]['time'] = date('Y-m-d H:i',$ewm[0]['time']);
        $this->assign(['ewm'=>$ewm]);
        return view('reEwm');
    }


    public function passOrder(){
        $orderCode = input('rcode');
        $orderInfo = db('recharge')->where('rcode','=',$orderCode)->find();
        if ($orderInfo['status'] !=1){
            $this->error('订单无效','index/rlst');
        }

        $userModel = model('User');
        //判断充值类型
        if ($orderInfo['type'] == 2){
            //直接充值
            $oldData = $userModel->where('id','=',$orderInfo['user_id'])->find();
            //直接加入购物金
//            $newBeans = $oldData['beans'] + $orderInfo['rbeans'];
            $newConsumption = $oldData['consumption'] + $orderInfo['money'];
            $res = $userModel->where('id','=',$orderInfo['user_id'])->update(['consumption'=>$newConsumption]);

            //记录充值状态 beanslog
            $logData=[
              'time'=>time(),
              'beansMum' => $orderInfo['rbeans'],
                'userId' => $orderInfo['user_id'],
                'status' =>1,
                'type' =>2,
                'pid' => $orderInfo['pid'],
            ];
            $logRes = model('Beanslog')->save($logData);

            //判断代理及代理获益

            $agencyRes = $this->agencyNum($orderInfo['user_id'],$orderInfo['money']);
//            dump($agencyRes);die();
            //上级和上上级获得分享奖励
            $pRes = $userModel->fxj($orderInfo['user_id'],$orderInfo['money']);
            if ($res){
                $su = db('recharge')->where('rcode','=',$orderCode)->update(['status'=>2]);
                $this->redirect('index/rlst');
            }

        }

        if ($orderInfo['type'] == 1){
            //充值会员
            $oldData = $userModel->where('id','=',$orderInfo['user_id'])->find();
            //加入会员豆分期获得mbeans
            $newmBeans = $oldData['mbeans'] + $orderInfo['rbeans'];
            $newConsumption = $oldData['consumption'] + $orderInfo['money'];
            //判断是不是 首冲
            $firstNum = $oldData['firstNum'];
            if (empty($firstNum)){
                $firstNum = $orderInfo['rbeans'];
            }

            //会员等级 不降级
            if ($oldData['member'] > $orderInfo['member']){
                $orderInfo['member'] = $oldData['member'];
            }
            $res = $userModel->where('id','=',$orderInfo['user_id'])->update(['mbeans'=>$newmBeans,'consumption'=>$newConsumption,'member'=>$orderInfo['member'],'firstNum'=>$firstNum]);

            //记录充值状态 beanslog
            $logData=[
                'time'=>time(),
                'beansMum' => $orderInfo['rbeans'],
                'userId' => $orderInfo['user_id'],
                'status' =>1,
                'type' =>2,
                'pid' => $orderInfo['pid'],
            ];
            $logRes = model('Beanslog')->save($logData);

            //判断代理及代理获益

            $agencyRes = $this->agencyNum($orderInfo['user_id'],$orderInfo['money']);

            //上级和上上级获得分享奖励
            $pRes = $userModel->fxj($orderInfo['user_id'],$orderInfo['money']);
            if ($res){
                $su = db('recharge')->where('rcode','=',$orderCode)->update(['status'=>2]);
                $this->redirect('index/rlst');
            }

        }

    }

    public function agencyNum($id=0,$num=0){
        $RechargeModel = model('Recharge');
        $dataArr = $RechargeModel->agencyIndex($id,$num);
        return $dataArr;
    }

    public function luckNum($id=0,$num=0){
        $RechargeModel = model('Recharge');
        $dataArr = $RechargeModel->luckNum($id,$num);
        return $dataArr;
    }

    public function delOrder(){
        $delCode = input('rcode');
        $delRes = db('recharge')->where('rcode','=',$delCode)->delete();
        if ($delRes){
            $this->redirect('rlst');
        }else{
            $this->error('删除失败','rlst');
        }
    }

     function uploads($name){
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

}
