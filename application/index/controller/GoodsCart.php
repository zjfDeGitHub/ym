<?php
namespace app\index\controller;

use think\Controller;
use model\User;
class GoodsCart extends  Controller
{
    public function index(){
//购物车
        $userId = session('userId');
//        $userId = 102;
        $goodsData = db('goods_cart')->alias('gc')->join('goods g','gc.goods_id=g.id')->field('gc.*,g.og_thumb,g.goods_name')
                    ->where('gc.user_id','=',$userId)->where('gc.status','=',1)->select();
//      dump($goodsData);
        $this->assign([
            'goodsData'=>$goodsData,
        ]);
        return view();
    }

    public function addOrSubtract(){
        $postData = input('post.');
//        dump($postData);die();
        $CartDb = db('goods_cart');
        $oldCartData = $CartDb->where('id','=',$postData['goods_cart_id'])->find();
        if ($postData['type'] == 'add'){
            $newNum = $oldCartData['goods_num'] + 1;
            $newSumprice = $oldCartData['goods_price'] + $oldCartData['goods_sum_price'];
            $res = $CartDb->where('id','=',$postData['goods_cart_id'])->update(['goods_num'=>$newNum,'goods_sum_price'=>$newSumprice]);
            if ($res){
                $resData['msg']="添加成功";
                $resData['code']= 1;
//                return json_encode($resData);
            }else{
                $resData['msg']='添加失败';
                $resData['code']=2;
//                return json_encode($resData);
            }
        }elseif ($postData['type'] == 'subtract'){
            $newNum = $oldCartData['goods_num'] - 1;
            if ($newNum < 0){
                $resData['msg']='不能小于0';
                $resData['code']=3;
//                return json_encode($resData);
            }
            $newSumprice = $oldCartData['goods_sum_price'] - $oldCartData['goods_price'];
            $res = $CartDb->where('id','=',$postData['goods_cart_id'])->update(['goods_num'=>$newNum,'goods_sum_price'=>$newSumprice]);
            if ($res){
                $resData['msg']='减少成功';
                $resData['code']=1;
//                return json_encode($resData);
            }else{
                $resData['msg']='减少失败';
                $resData['code']=2;
//                return json_encode($resData);
            }
        }
        return json($resData);
    }

    public function delCart(){
//        return 123;
        $postData = input('post.');
        if ($postData['type'] != 'del'){
            $resData['msg']='type No';
            $resData['code']=3;
            return json($resData);
        }
        $res = db('goods_cart')->where('id','=',$postData['goods_cart_id'])->delete();
        if ($res){
            $resData['msg']='删除成功';
            $resData['code']=1;
        }else{
            $resData['msg']='删除失败';
            $resData['code']=2;
        }
        return json($resData);
    }
}
