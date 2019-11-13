<?php

/**
 *
 * @file   admin.php
 * @date   2016-8-30 15:22:57
 * @author Zhenxun Du<5552123@qq.com>
 * @version    SVN:$Id:$
 */

namespace app\index\model;

use think\Db;

class User extends \think\Model {

    public function agencyS($idc){
        $userModel = new self;
        $sum = 0;
        if (is_int($idc)){
            $id[]=$idc;
        }else{
            $id = $idc;
        }
        if (is_array($id)){
            if (empty($id)){
                return 0;
            }
            foreach ($id as $k =>$v){
//                dump('sum1:'.$sum);
                $sum += intval($userModel->where('pid','=',$v)->field('SUM(consumption)')->find()['SUM(consumption)']);
//                dump('sum2:'.$sum);
                $pid = $userModel->where('pid','=',$v)->field('id')->select();
                if ($pid == 0){
                    continue ;
                }
                $pid = $this->agencyZ($pid);
//                dump($pid);die();
                $sum = $sum + $this->agencyS($pid);

            }
        }

        return $sum;

    }

    public function agencyC($id =0){
        $userModel = new self;
        $downData = $userModel->where('pid','=',$id)->field('consumption,id,username')->select();
        foreach ($downData as $k => $v){
//               dump($v['id']);
            $downData[$k]['mun']= $this->agencyS($v['id']);
//               dump($downData[$k]['mun']);
        }
        return $downData;
    }
    public function agencyZ($arr){
        $rArr = [];
        foreach ($arr as $k => $v){
            $rArr[$k] = $v['id'];
        }
        return $rArr;
    }


    public function qd($id = 0){
        $userModel = new self;
        $level = $userModel->where('id', '=',$id)->field('member,beans,pid,mbeans')->find();
        //如果有推荐人，推荐人豆子加一
        if ($level['pid']){
            $pbeans = $userModel->where('id','=',$level['pid'])->field('beans')->find();
            $pbeans = $pbeans['beans'] +1;
            $userModel->where('id','=',$level['pid'])->update(['beans'=>$pbeans]);
            //记录beanslog
            $beansData = [
                'time' => time(),
                'beansMum' => 1,
                'type' => 3,
                'uid' =>$id,
                'status' =>1,
                'userId' =>$level['pid'],
            ];
            $logRes=db('beanslog')->insert($beansData);
            if (!$logRes){
                die('记录错误');
            }
        }
        //未注册送签到送一个，一级两个，二级五个，三级二十个
        if ($level['member'] == 0){
            $add = 1;
            $isM = 1;
        }else if($level['member'] == 1){
            $add =2;
            $isM = 2;
        }else if($level['member'] == 2){
            $add =5;
            $isM = 2;
        }else if($level['member'] == 3){
            $add =20;
            $isM = 2;
        }
        //不是会员的情况
        if ($isM == 1){
            $beans = $level['beans'] +$add;
            $res['add']=$add;
            $res['res'] = $userModel->where('id', '=',$id)->update(['beans'=>$beans]);
        }else if($isM == 2){
            //会员需要从会员豆（mbeans）中扣除
            $mbeans = $level['mbeans'];
            $mbeans = $mbeans - $add;
            if ($mbeans<0){
                $returnData['msg']="会员豆子不足";
                $returnData['code']=0;
                $returnData['qd']=1;
                return $returnData;
            }
            $beans = $level['beans'] +$add;
            $res['add']=$add;
            $res['res'] = $userModel->where('id', '=',$id)->update(['beans'=>$beans,'mbeans'=>$mbeans]);
        }

        //记录beanslog
        $beansData = [
            'time' => time(),
            'beansMum' => $add,
            'type' => 1,
//            'uid' =>$id,
            'status' =>1,
            'userId' =>$id,
        ];
        $logRes=db('beanslog')->insert($beansData);
        if (!$logRes){
            die('记录错误');
        }

        //写入签到时间
        $loguserModel = model('loguser');
        $logData=['userId'=>$id,'qdtime'=>time(),'IP'=>request()->ip()];
        if ($loguserModel->get($id)){
            $log=$loguserModel->isUpdate(true)->save($logData);
        }else{
            $log=$loguserModel->save($logData);
        }
        return $res;
    }

    public function whatMember($arr,$key=0){
//        dump($arr);die();
        if (count($arr) == count($arr, 1)) {
//            echo '是一维数组';
            $arr2[0] = $arr;
            $arr = $arr2;
            $isew = 1;
        } else {
//            echo '不是一维数组';
            $isew = 2;
        }
        foreach ($arr as $k =>$v){
            if ($v[$key] == 1){
                $arr[$k]['memberName'] = '青春达人';
                $arr[$k]['wmbeans'] = 300;
            }else if($v[$key] == 2){
                $arr[$k]['memberName'] = '时尚达人';
                $arr[$k]['wmbeans'] = 597;
            }else if($v[$key] == 3){
                $arr[$k]['memberName'] = '魅力达人';
                $arr[$k]['wmbeans'] = 1797;
            }else{
                $arr[$k]['memberName'] = ' ';
                $arr[$k]['wmbeans'] = 0;
            }
        }
        if ($isew == 1) {
            $arr2 = $arr[0];
            $arr = $arr2;
        }
        return $arr;
    }

    public function fxj($id=15,$money=100,$len=2){
        $pstring = null;
        $userModel = new self;
        $cid =$id;
        for ($i=0;$i<$len;$i++){
            $pid = $userModel->field('pid')->find($cid)['pid'];
            $cid = $pid;
            $pstring = $pstring."|".$pid;
        }
        $parr = explode('|',$pstring);
        $parr=array_filter($parr);
        $parr=array_values($parr);
//        dump($parr);die();
        foreach ($parr as $k =>$v){
            if ($k == 0){
                //推荐奖励，第一代百分之十， 直接加入青春豆
                $jl = $money*0.1;
                $oldBeans = $userModel->where('id','=',$v)->field('beans')->find()['beans'];
                $newBeans = $oldBeans +$jl;
                $userModel->where('id','=',$v)->update(['beans'=>$newBeans]);
                //记录 beanslog
                $logData=[
                    'time'=>time(),
                    'beansMum' => $jl,
                    'userId' => intval($v),
                    'status' =>1,
                    'type' =>4,
                    'uid' =>$id,
                    'level' => $k,
                ];
//               dump($logData);die();
                $logRes = model('Beanslog')->isUpdate(false)->save($logData);
                if (!$logRes){
                    die('logRes错误1');
                }
            }
            if ($k ==1){
                $jl = $money*0.08;
                //没有三个下级则不加
                $xj = $userModel->where('pid','=',$v)->count('id');
                if ($xj<3){
                    continue ;
                }
                $oldBeans = $userModel->where('id','=',$v)->field('beans')->find()['beans'];
                $newBeans = $oldBeans +$jl;
                $userModel->where('id','=',$v)->update(['beans'=>$newBeans]);
                //记录 beanslog
                $logData2=[
                    'time'=>time(),
                    'beansMum' => $jl,
                    'userId' => $v,
                    'status' =>1,
                    'type' =>4,
                    'uid' =>$parr[$k-1],
                    'level' => $k,
                ];
//               dump($logData);die();
                $logRes = model('Beanslog')->insert($logData2);
                if (!$logRes){
                    die('logRes错误2');
                }
            }

        }
//        dump($parr);die();
    }


}
