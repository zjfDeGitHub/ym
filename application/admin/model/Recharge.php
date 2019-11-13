<?php

/**
 *
 * @file   admin.php
 * @date   2016-8-30 15:22:57
 * @author Zhenxun Du<5552123@qq.com>
 * @version    SVN:$Id:$
 */

namespace app\admin\model;

use think\Db;

class Recharge extends \think\Model {

    public function agencyNum($id =0,$dataArr=array()){
        $User =  db('user');
        $pid = $User->where('id',$id)->find()['pid'];
        if ($pid == 0){
            return $dataArr;
        }
        $tD=$dataArr[] =$User->where('id',$pid)->field('id,level,isFw')->find();
//        if ($tD['level'] == 3){
//            return $dataArr;
//        }

       return $this->agencyNum($tD['id'],$dataArr);
    }

    public function agencyIndex($id =0 ,$rechargeNum =0){
        $User =  db('user');
        $dataArr = $this->agencyNum($id);
        $levelRaito = db('range')->select();
//        dump($levelRaito);die();
        $isL1 =0; $isL2=0;$isL3=0;$ispj=0;
//        dump($dataArr);
        foreach ($dataArr as $k => $v){
            if($v['isFw'] == 1){
                //服务中心奖励
                $ratio = db('range')->where('level','=',4)->find()['ratio'];
                $agencyNum = $rechargeNum * $ratio;
//                dump($agencyNum);die();
//                $res1 = $this->old2new($v['id'],$User,'levelNum',$agencyNum);
                $res1 = $this->old2new($v['id'],$User,'fwNum',$agencyNum);
                $res2 = $this->addBeansLog($v['id'],$id,$agencyNum,6,$ratio);
            }
            if($v['level'] ==3 && $isL3==1&&$ispj==1){
                //评级奖励 0.01
                $ratio = 0.01;
                $agencyNum = $rechargeNum * $ratio;
//                dump($agencyNum);die();
                $res1 = $this->old2new($v['id'],$User,'levelNum',$agencyNum);
                $res1 = $this->old2new($v['id'],$User,'beans',$agencyNum);
                $res2 = $this->addBeansLog($v['id'],$id,$agencyNum,5,$ratio);
                $isL3 =1;
                break;
            }
            if ($v['level'] == 3 &&$isL3==0){
                if ($isL1 ==0 && $isL2==0){
                    $ratio = $levelRaito[2]['ratio'];
//                    $agencyNum = $rechargeNum * 0.09;
                    $agencyNum = $rechargeNum * $ratio;
                }elseif($isL1 ==1 && $isL2==0){
                    $ratio = $levelRaito[2]['ratio'] - $levelRaito[0]['ratio'];
//                    $agencyNum = $rechargeNum * 0.04;
                    $agencyNum = $rechargeNum * $ratio;
                }elseif( $isL2==1){
                    $ratio = $levelRaito[2]['ratio'] - $levelRaito[1]['ratio'];
//                    $agencyNum = $rechargeNum * 0.02;
                    $agencyNum = $rechargeNum * $ratio;
                }

//                $oldUserLevelNum = $User->where('id',$v['id'])->find()['levelNum'];
////              $newUserLevelNum = $oldUserLevelNum + $agencyNum;
////              $User->where('id',$v['id'])->update(['levelNum'=>$newUserLevelNum]);
                $res1 = $this->old2new($v['id'],$User,'levelNum',$agencyNum);
                $res1 = $this->old2new($v['id'],$User,'beans',$agencyNum);
                $res2 = $this->addBeansLog($v['id'],$id,$agencyNum,5,$ratio);
               $isL3 =1;
               $isL1 =1;
               $isL2 =1;
                $ispj=1;
            }

            if ($v['level'] == 1 && $isL1 == 0){
                $ratio = $levelRaito[0]['ratio'];
//                $agencyNum = $rechargeNum * 0.05;
                $agencyNum = $rechargeNum * $ratio;
                $res1 = $this->old2new($v['id'],$User,'levelNum',$agencyNum);
                $res1 = $this->old2new($v['id'],$User,'beans',$agencyNum);
                $res2 = $this->addBeansLog($v['id'],$id,$agencyNum,5,$ratio);
                $isL1=1;

            }
            if ($v['level'] == 2 &&$isL2==0){
                if ($isL1 == 0){
                    $ratio = $levelRaito[1]['ratio'];
//                    $agencyNum = $rechargeNum * 0.07;
                    $agencyNum = $rechargeNum *$ratio;
                }elseif($isL1 == 1){
                    $ratio = $levelRaito[1]['ratio'] - $levelRaito[0]['ratio'];
//                    $agencyNum = $rechargeNum * 0.02;
                    $agencyNum = $rechargeNum * $ratio;
                }
                $res1 = $this->old2new($v['id'],$User,'levelNum',$agencyNum);
                $res1 = $this->old2new($v['id'],$User,'beans',$agencyNum);
                $res2 = $this->addBeansLog($v['id'],$id,$agencyNum,5,$ratio);
                $isL2=1;
                $isL1=1;

            }
        }
        return 1;
    }

    public function old2new($id,$db,$value,$addNum){
        $oldUserLevelNum = $db->where('id',$id)->find()[$value];
        $newUserLevelNum = $oldUserLevelNum + $addNum;
        $res=$db->where('id',$id)->update([$value=>$newUserLevelNum]);
        if ($res){
            return 1;
        }
    }


    public function addBeansLog($userId=0,$uid=0,$beansMum=0,$type=0,$ratio=0){
        $logData = [
            'beansMum'=>$beansMum,
          'time'=> time(),
          'type' =>$type,
          'status'=>1,
            'userId'=>$userId,
            'uid'=>$uid,
            'ratio'=>$ratio,
        ];
        $beansLogModel = model('beanslog');
        $res = $beansLogModel->insert($logData);
        if ($res){
            return 1;
        }

    }


    public function luckNum($id=0,$mon){
        $luckArrQ = array();
        $luckArrH = array();
//        dump(count($luckArr));die();
        $id = 20;
        //前十位
        $num = 1;
        for ($i=0;;){
            $uid = $id - $num;
            $num =$num +1;
            if (count($luckArrQ) >=10){
                break;
            }
            if ($uid <=0 ){
                break;
            }
            $luckUser = $this->luckUser($uid);
            if ($luckUser == NULL){
                continue;
            }else{
                $luckArrQ[]=$luckUser;
            }
        }
        //后十位
        $num = 1;
        for ($i=0;;){
            $uid = $id + $num;
            $num =$num +1;
            if (count($luckArrH) >=10){
                break;
            }
            if ($uid <=0 ){
                break;
            }
            $luckUser = $this->luckUser($uid);
            if ($luckUser == NULL){
                continue;
            }else{
                $luckArrH[]=$luckUser;
            }
        }
//        dump($luckArrH);
//        dump($luckArrQ);die();
        $ratioData=db('ratio')->where('type',1)->select();
        $addQ = $this->luckadd($luckArrQ,$mon*$ratioData[0]['ratio']);
        $addH = $this->luckadd($luckArrH,$mon*$ratioData[1]['ratio']);
    }

    public function luckadd($arr,$mon){
        $User=db('user');
        foreach ($arr as $k => $v){
            $userNum = $User->where('id',$v)->field('firstNum,luckBeans');
            $firstNum = $userNum['firstNum'];
            $luckBeans = $userNum['luckBeans'];
            $nowLuck = $luckBeans + $mon;
            if ($nowLuck < $firstNum){
                $resAdd = $User->where('id',$v)->update(['luckBeans'=>$nowLuck]);
                $logAdd = $this->addBeansLog($v,0,$mon,6);
            }elseif ($nowLuck > $firstNum){
                $resAdd = $User->where('id',$v)->update(['luckBeans'=>$firstNum]);
                $logAdd = $this->addBeansLog($v,0,$mon,6);
            }

        }
    }

    //读取幸运用户，并判断他是否 有资格
    public function luckUser($id=0){
        @$luckUser = db('user')->where('id','=',$id)->field('id,luckBeans,member,level,firstNum')->find();
        if ($luckUser['member'] == 0){
            return NULL ;
        }
        if ($luckUser['luckBeans'] == $luckUser['firstNum']){
            return NULL;
        }
        $isactive = db('beanslog')->where('userId','=',$id)->count('id');
        if ($isactive == 0){
            return NULL;
        }

        return $luckUser;
    }

}
