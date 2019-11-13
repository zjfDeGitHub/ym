<?php
namespace app\index\controller;

use think\Controller;
use model\User;
use think\Model;

class Shop extends  Controller
{
    public function index(){
//        echo "shop";
        $newGoodsData = Model('Recpos')->getGoods(4,4);
        $brandGoodsData = db('brand')->alias('b')->where('b.status',1)->where('g.on_sale',1)->join('goods g','g.brand_id=b.id','left')->where('g.active_id',0)->select();
        foreach ($brandGoodsData as $k=>$v){
            $brandGoodsData2[$v['brand_id']][]=$v;
        }
//        dump($brandGoodsData2);die();
        $this->assign([
            'brandGoodsData2'=>$brandGoodsData2,
            'newGoodsData'=>$newGoodsData,
        ]);
        return view();
    }

    public function xinrenfuli(){
        //新用户条件为：没有购买过一单产品；
        $userId =session('userId');
        $isNew = db('order')->where('user_id',$userId)->field('count(id)')->find()['count(id)'];
        if ($isNew>0){
            return "<script>window.alert = function(name){
 var iframe = document.createElement(\"IFRAME\");
iframe.style.display=\"none\";
iframe.setAttribute(\"src\", 'data:text/plain,');
document.documentElement.appendChild(iframe);
window.frames[0].window.alert(name);
iframe.parentNode.removeChild(iframe);
};alert('活动只能新人参与!!!');window.location='http://cc.jwhcx.com/index.php/index/shop';</script>";
        }
        $activeImg = db('active')->where('id',1)->find();
        $xrData = db('goods')->where('active_id',1)->where('on_sale',1)->select();
        $this->assign([
            'xrData'=>$xrData,
            'activeImg'=>$activeImg,
        ]);
        return view();
    }

    public function indexs(){
//        echo "shop";
        $newGoodsData = Model('Recpos')->getGoods(4,4);
        $brandGoodsData = db('brand')->alias('b')->where('b.status',1)->join('goods g','g.brand_id=b.id','left')->where('g.active_id',0)->select();
        foreach ($brandGoodsData as $k=>$v){
            $brandGoodsData2[$v['brand_id']][]=$v;
        }
//        dump($brandGoodsData2);die();
        $this->assign([
            'brandGoodsData2'=>$brandGoodsData2,
            'newGoodsData'=>$newGoodsData,
        ]);
        return view();
    }
    
   public function GetGoods(){
        //新品推荐
       $newGoodsData = Model('Recpos')->getGoods(4,4);
       $this->assign([
           'newGoodsData'=>$newGoodsData,
       ]);
       dump($newGoodsData);die();
   }

   public function ShopOrder(){
       $userId = session('userId');
       $orderDb = db('order');
        $dfk = $orderDb->alias('o')->where('o.user_id',$userId)->join('order_goods og','o.id=og.order_id')->join('goods g','g.id=og.goods_id')
                ->where('o.pay_status','=',0)->field('o.*,og.*,g.og_thumb')->order('o.id desc')->select();
        $dfh = $orderDb->alias('o')->where('o.user_id',$userId)->join('order_goods og','o.id=og.order_id')->join('goods g','g.id=og.goods_id')->field('o.*,og.*,g.og_thumb')
                ->where('o.pay_status',1)->where('o.post_status',0)->order('o.id desc')->select();
//        dump($dfh);die();
        $dsh = $orderDb->alias('o')->where('o.user_id',$userId)->join('order_goods og','o.id=og.order_id')->join('goods g','g.id=og.goods_id')->field('o.*,og.*,g.og_thumb')
                ->where('o.pay_status',1)->where('o.post_status',1)->order('o.id desc')->select();
        $ywc = $orderDb->alias('o')->where('o.user_id',$userId)->join('order_goods og','o.id=og.order_id')->join('goods g','g.id=og.goods_id')->field('o.*,og.*,g.og_thumb')
                ->where('o.pay_status',1)->where('o.post_status',2)->order('o.id desc')->select();
//        dump($dfk);
//        dump($dfh);
//        dump($dsh);
//        dump($ywc);die();
       $this->assign([
           'dfk'=>$dfk,
           'dfh'=>$dfh,
           'dsh'=>$dsh,
           'ywc'=>$ywc,
       ]);
        return view();
   }


    public function  orderInfo(){
        $orderId = input('orderId');
        $msg = urldecode(input('msg'));
        $orderData = db('order')->where('id',$orderId)->field('out_trade_no,province,city,country,address,phone,name,order_time,order_total_price,pay_status,post_status')->find();
        $orderGoods = db('order_goods')->alias('og')->where('order_id',$orderId)->join('goods g','g.id=og.goods_id')
                    ->field('g.og_thumb,og.goods_price,og.goods_num,og.goods_name,goods_attr_str')->select();
        $this->assign([
            'msg'=>$msg,
            'orderData'=>$orderData,
            'orderGoods'=>$orderGoods,
        ]);
        return view();
    }

}
