<?php
namespace app\index\controller;
use think\Controller;
class Active extends Controller
{

    public function index(){

    }

    public function pt(){
        $userId = session('userId');

        if (request()->isPost()){
            $ptOrderId = input('ptOrderId');
            $ptTime = cookie('name');

        }
        $ptOrderId = input('ptOrderId');
        $ptOrderId = 4;
        //查询此团成员和状况
        $ptOrderData = db('pt_order')->alias('po')->where('po.id',$ptOrderId)->join('goods g','g.id=po.goods_id')->field('g.og_thumb,po.*')->find();
        $ptUser = db('order')->where('pt_id',$ptOrderId)->field('id,user_id,name,order_time')->select();
        $userLen = count($ptUser);
//        dump($ptUser);die();
        // 初始化
        cookie(['prefix' => 'think_', 'expire' => 3600]);
        // 设置
        cookie('pt_time', 'value',$ptOrderData['pt_time'] );

        $this->assign([
            'userLen'=>$userLen,
            'ptOrderData'=>$ptOrderData,
            'ptUser'=>$ptUser
        ]);
        return view();
    }
    public function ptlist(){
        $ptGoodsList = db('goods')->where(['active'=>2,'on_sale'=>1])->select();
        $this->assign('ptGoodsList',$ptGoodsList);
        return view();
    }
    public function ptGoods(){
        if (request()->isPost()){
            $userId = session('userId');
            $postData = input('post.');
            $postData['out_trade_no']=time().rand(1000,9999);
            $postData['order_time']=time();
            $postData['tz_id'] = $userId;
            $postData['user_list'] = $userId;
            $postData['status'] = 4;
            $ptOrderModel = model('pt_order');
            //记录拼团订单
            $res = $ptOrderModel->save($postData);
           if ($res){
               $this->redirect('active/ptPay',['ptOrderId'=>$ptOrderModel->id]);
           }

        }
        $ptGoodsId = input('ptGoodsId');
        $ptGoodsData = db('goods')->where('id',$ptGoodsId)->find();
        dump($ptGoodsData);die();
        $this->assigin('ptGoodsData',$ptGoodsData);
        return view();
    }

    public function ptPay(){
        $userId = session('userId');
        if (request()->isPost()){
            $insertData = input('post.');
            dump($insertData);
            $postPtId = input('ptOrderId')?input('ptOrderId'):$insertData['pt_id'];
            $ptOrderDb = db('pt_order');
            $userDb = db('user');
            $ptOrderData = $ptOrderDb->where('id',$postPtId)->find();
            $userData = $userDb->where('id',$userId)->find();
            $pay = $userData['consumption'] - $ptOrderData['pt_price'];
            dump($pay);
            if ($pay > 0){
                //写入个人订单
                $orderModel = model('order');
                $orderGoodsModel = model('order_goods');
                $insertData['order_time'] = time();
                $insertData['user_id'] = $userId;
                $insertData['pay_status'] = 1;
                $insertData['out_trade_no'] = time().rand(111111,999999);
                $res = $orderModel->allowField(true)->save($insertData);
                $orderId = $orderModel->id;
                //写入个人订单属性
                $insertData = $ptOrderData;
                unset($insertData['id']);
                $insertData['order_id'] =$orderId;
                $insertData['goods_price'] =$ptOrderData['pt_price'];
                $insertData['shop_price'] =$ptOrderData['pt_price'];
                $res = $orderGoodsModel->allowField(true)->save($insertData);
                return salert('支付成功！！',"http://cc.jwhcx.com/index.php/index/active/pt");
            }else{
                return salert('购物金不足，请充值！！',"http://cc.jwhcx.com/index.php/index/active/ptList");
            }
        }
        $ptOrderId = input('ptOrderId')?input('ptOrderId'):4;
        $userAddr = db('user_addr')->where(['user_id'=>$userId,'status'=>3])->find();
        $ptOrderData = db('pt_order')->alias('po')->where('po.id',$ptOrderId)->join('goods g','po.goods_id = g.id')
                        ->field('g.og_thumb,po.id,po.goods_name,po.pt_num,po.pt_time,po.pt_price,po.goods_attr_str')->find();
        dump($userAddr);
        dump($ptOrderData);die();
        $this->assign([
            'userAddr'=>$userAddr,
            'ptOrderData'=>$ptOrderData,
        ]);
        return view();
    }



}