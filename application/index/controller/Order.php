<?php
namespace app\index\controller;

use think\Controller;
use model\User;
class Order extends  Controller
{
    public function index(){

    }

    //读取省
    public function privance()
    {
        $list = db('city')->where('type',1)->select();
//        dump($list);die();
        return $list;
    }


    //读取市
    public function city($provinceid)
    {
        $provinceid = input('provinceid');
//        dump($provinceid);die();
        if ($provinceid) {
            $map['pid'] = ['=',$provinceid];
            $map['type'] = ['=',2];
        } else {
//            $where = "provinceid=110000";
        }
        $list = db('city')->where($map)->select();

        return $list;
    }

    //读取区
    public function area($cityid)
    {
        $cityid = input('cityid');
//        dump($cityid);die();
        if ($cityid) {
            $map['pid'] = ['=',$cityid];
            $map['type'] = ['=',3];
        } else {
//            $where = "cityid=110100";
        }
        $list = db('city')->where($map)->select();
        return $list;
    }

    public function addrList(){
        $userId = session('userId');
        $userAddrDb = db('user_addr');
        $type = input('type');
        if (isset($type)){
            $addrId = input('addrId');
                $res = $userAddrDb->where('user_id','=',$userId)->where('status','neq','2')->update(['status'=>1]);
                $res = $userAddrDb->where('id','=',$addrId)->update(['status'=>3]);
                if ($res){
                    $resData['msg'] = '默认地址修改成功';
                    $resData['code'] = 1;
                }else{
                    $resData['msg'] = '默认地址修改失败';
                    $resData['code'] = 2;
                }
                return json($resData);

        }
        $addrList = $userAddrDb->where('user_id','=',$userId)->select();
        $this->assign([
            'addrList'=>$addrList,
        ]);

        return view();
        

    }
    public function createAddr(){
        if (request()->isPost()){
            $userAddrDb = db('user_addr');
            $userId = session('userId');
            $postData = input('post.');
            $postData['privance']=$postData['province'];
            unset($postData['province']);
            $postData['city'] = explode(',',$postData['city'])['0'];
            $postData['privance'] = explode(',',$postData['privance'])['0'];
            $postData['user_id'] = $userId;
            $postData['status'] = 1;
            $postData['time'] = time();
//            //默认地址修改
//            if ($postData['status'] == 3){
//                $res = $userAddrDb->where('user_id','=',$userId)->where('status','neq','2')->update(['status'=>1]);
//            }
            $res = $userAddrDb->insert($postData);
            if ($res){
                $resData['msg'] = '地址添加成功';
                $resData['code'] = 1;
            }else{
                $resData['msg'] = '地址添加失败';
                $resData['code'] = 2;
            }
            return json($resData);
        }

        return view('createAddr');
    }

    public function editAddr(){
        if (request()->isPost()){
            $userAddrDb = db('user_addr');
            $userId = session('userId');
            $postData = input('post.');
            $postData['privance']=$postData['province'];
            unset($postData['province']);
            $postData['time'] = time();
            $postData['city'] = explode(',',$postData['city'])['0'];
            $postData['privance'] = explode(',',$postData['privance'])['0'];
            $res = $userAddrDb->where('id',$postData['id'])->update($postData);
            if($res){
            	$this->redirect('order/addrList');
            }
        }
        $addrId = input('addrId');
        $addrData = db('user_addr')->where('id',$addrId)->find();

        $this->assign([
           'addrData'=>$addrData,
        ]);
        return view();
    }
    public function delAddr(){
        $delId = input('addrId');
        $res = db('user_addr')->where('id',$delId)->delete();
        if ($res){
            $resData['msg'] = '地址删除成功';
            $resData['code'] = 1;
        }else{
            $resData['msg'] = '地址删除失败';
            $resData['code'] = 2;
        }
        return json($resData);

    }

    public function payOrder(){
        //结算页面
        $orderId = input('orderId');
        $userId = session('userId');
        if (request()->isPost()){
            $postData = input('post.');
            $orderId = $postData['orderId'];
            $orderInfo = db('order')->where('id',$orderId)->find();
            $money = $orderInfo['order_total_price'];
            $userInfo = db('user')->where('id',$userId)->find();
            //减购物金
            $pay = $userInfo['consumption'] - $money;
            if ($pay>=0){
                $res = db('order')->where('id',$orderId)->update(['pay_status'=>1]);
                $payLog=[
                  'time'=>time(),
                  'user_id'=>$userId,
                    'order_id'=>$orderId,
                    'money'=>$money,
                ];
                $res = db('consume_log')->insert($payLog);
//                $res = db('user')->where('id',$userId)->update(['beans'=>$pay]);
                $res = db('user')->where('id',$userId)->update(['consumption'=>$pay]);
                if ($res){
                    $reData['msg']='支付成功';
                    $reData['code']='1';
                    return $reData;
                }
            }else{
                $reData['msg']='金额不足';
                $reData['code']='3';
                return $reData;
            }
            return $postData;
        }

        $orderInfo = db('order')->where('id',$orderId)->find();
        $this->assign([
            'orderInfo'=>$orderInfo,
        ]);
        return view();
    }

   
    public function createOrder(){
        $goodsCartId = input('goodsCartId');
        if (!is_array($goodsCartId)){
            $goodsCartId = explode(',',$goodsCartId);
        }
        $userId = session('userId');
//        $userId = 1;
        if (request()->isPost()){
            $insertData = input('post.');
            $orderModel = model('order');
            $orderGoodsModel = model('order_goods');
            $insertData['order_time'] = time();
            $insertData['user_id'] = $userId;
            $insertData['pay_status'] = 0;
            $insertData['out_trade_no'] = time().rand(111111,999999);
            $res = $orderModel->allowField(true)->save($insertData);
            $orderId = $orderModel->id;
            $insertData['order_id']=$orderId;
            //写入订单商品属性
            $goodsCartData = db('goods_cart')->alias('gc')->where('gc.id','in',$insertData['cartId'])->select();
//            dump($goodsCartData);
            $md =array();
            foreach ($goodsCartData as $k=>$v){
                $v['shop_price'] = $v['goods_sum_price'];
                $v['order_id'] = $orderId;
                $res = db('goods_cart')->where('id',$v['id'])->delete();
                unset($v['id']);
                $md[]=$v;
            }
            $res = $orderGoodsModel->allowField(true)->saveAll($md);
            $this->redirect('order/payOrder', ['orderId' =>$orderId]);
        }

        $goodsCartData = db('goods_cart')->alias('gc')->where('gc.id','in',$goodsCartId)->join('goods g','gc.goods_id=g.id','right')->field('gc.*,g.og_thumb')->select();
        $userAddr = db('user_addr')->where('status',3)->where('user_id',$userId)->find();
        $goodsNum = 0;
        $orderSumPrice = 0;
        foreach ($goodsCartData as $k =>$v){
            $goodsNum +=$v['goods_num'];
            $orderSumPrice +=$v['goods_sum_price'];
        }

//        dump($userAddr);
//        dump($goodsCartData);
        $this->assign([
            'goodsNum'=>$goodsNum,
            'orderSumPrice'=>$orderSumPrice,
            'userAddr'=>$userAddr,
            'goodsCartData'=>$goodsCartData,
        ]);
        return view();


    }

}
