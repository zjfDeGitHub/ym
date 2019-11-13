<?php
namespace app\admin\controller;
use think\Controller;
class Count extends Controller
{
    public function index(){
        $Recharge =  db('recharge');
        $User =  db('User');
        $CountRe = $Recharge->where('status',2)->field('SUM(money)')->find();
        $totleUser = $User->field('count(*)')->find();
        $totleMbeans = $User->field('sum(Mbeans)')->find();
        $totleBeans = $User->field('sum(Beans)')->find();
        $start = date('Y-m-d 00:00:00');
        $end = date('Y-m-d H:i:s');
        $map['time']=['>=',strtotime($start)];
        $map['status']=['=',2];
        $todayRechargeNum = $Recharge->where($map)->field('sum(money)')->find();
//        dump($todayRechargeNum);die();
        $todayEmbodyNum = db('embody')->where($map)->field('sum(txmoney)')->find();
//        dump($totleUser);
//        dump($totleMbeans);
//        dump($totleBeans);
//        dump($CountRe);
        $starttime = time() -3600*24*3;
        $enttime = time();
        $where['r.time'] = array('between', array($starttime,$enttime));
        $ReData = $Recharge->where($where)->alias('r')->order('id desc')->join('user u','r.user_id = u.id','left')->field('r.*,u.username')->select();
        foreach ($ReData as $k =>$v){
            $ReData[$k]['time'] = date('Y-m-d',$v['time']);
        }
        $OrderData = $Recharge
            ->field("DATE_FORMAT(FROM_UNIXTIME(time),'%Y-%m-%d') as date,sum(money) as totalmoney , count(*) as total")
            ->group("DATE_FORMAT(FROM_UNIXTIME(time),'%Y-%m-%d')")
            ->select();
        $memberYk = $User->where('member','=',0)->field('count(*)')->find();
        $member1 = $User->where('member','=',1)->field('count(*)')->find();
        $member2 = $User->where('member','=',2)->field('count(*)')->find();
        $member3 = $User->where('member','=',3)->field('count(*)')->find();

        $this->assign([
            'todayRechargeNum'=>$todayRechargeNum['sum(money)']?$todayRechargeNum['sum(money)']:0,
            'todayEmbodyNum'=>$todayEmbodyNum['sum(txmoney)']?$todayEmbodyNum['sum(txmoney)']:0,
            'totleUser'=>$totleUser['count(*)']?$totleUser['count(*)']:0,
            'totleMbeans'=>$totleMbeans['sum(Mbeans)']?$totleMbeans['sum(Mbeans)']:0,
            'totleBeans'=>$totleBeans['sum(Beans)']?$totleBeans['sum(Beans)']:0,
            'ReData' =>$ReData?$ReData:0,
            'CountRe'=>$CountRe['SUM(money)']?$CountRe['SUM(money)']:0,
            'OrderData' =>$OrderData?$OrderData:0,
            'memberYk'=>$memberYk['count(*)']?$memberYk['count(*)']:0,
            'member1'=>$member1['count(*)']?$member1['count(*)']:0,
            'member2'=>$member2['count(*)']?$member2['count(*)']:0,
            'member3'=>$member3['count(*)']?$member3['count(*)']:0,
        ]);
        return view();
    }
}