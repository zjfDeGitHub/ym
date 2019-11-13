<?php
namespace app\index\controller;

use think\Controller;
use model\User;
class Goods extends  Controller
{

    
    public function index(){
//        echo "goods";
        $id = input('id');
        $goodsInfo=db('goods')->where('id',$id)->find($id);
//        dump($goodsInfo);
        //商品主图信息数组
        $goodsThumbArr=array();
        if($goodsInfo['og_thumb']){
            $goodsThumbArr['sm_photo']=$goodsInfo['sm_thumb'];
            $goodsThumbArr['mid_photo']=$goodsInfo['mid_thumb'];
            $goodsThumbArr['big_photo']=$goodsInfo['big_thumb'];
            $goodsThumbArr['og_photo']=$goodsInfo['og_thumb'];
        }
        //获取当前商品相册信息
        $goodsPhotoRes=db('goods_photo')->field('sm_photo,mid_photo,big_photo,og_photo')->where(array('goods_id'=>$id))->select();
//        dump($goodsPhotoRes);

        //将商品主图放到相册最前面
        array_unshift($goodsPhotoRes, $goodsThumbArr);
        //获取并处理商品属性信息

        $gaArr=db('goods_attr')->alias('ga')->field('ga.*,a.attr_name,a.attr_type')->join('attr a',"ga.attr_id = a.id")->where(array('ga.goods_id'=>$id))->select();
        $radioAttrArr=array();
        $uniAttrArr=array();
        foreach ($gaArr as $k => $v) {
            if($v['attr_type'] == 1){
                $radioAttrArr[$v['attr_id']][]=$v;
            }else{
                $uniAttrArr[]=$v;
            }
        }
//        dump($radioAttrArr);
//        dump($uniAttrArr);
        $radioAttrArrJ = array();
        foreach ($radioAttrArr as $k =>$v){
            $radioAttrArrJ[]=$v;
        }
//        dump($radioAttrArrJ);
        $this->assign([
            'show_right'=>1,//文章列表和商品列表头部偏移判断
            'goodsInfo' =>$goodsInfo,
            'goodsPhotoRes'=>$goodsPhotoRes,
            'uniAttrArr'=>$uniAttrArr,
            'radioAttrArr'=>$radioAttrArr,
            'radioAttrArrJ'=>json_encode($radioAttrArrJ),
        ]);
        return view();
    }

    public function creatCart(){
        if (request()->isPost()){
            $userId = session('userId');
            $goods_cart = model('goods_cart');
            $postData = input('post.');
            $postData['user_id'] = $userId;
            $postData['status'] = 1;
            $postData['time']=time();
            $postData['post_type']=intval($postData['post_type']);
            $postData['goods_num']=intval($postData['goods_num']);
            $postData['goods_id']=intval($postData['goods_id']);
            $postData['goods_sum_price']=doubleval($postData['goods_sum_price']);
            $postData['goods_marke_price']=doubleval($postData['goods_marke_price']);
            $postData['goods_price']=doubleval($postData['goods_price']);
            $postData['goods_name'] = db('goods')->where('id',$postData['goods_id'])->find()['goods_name'];
//            return $postData;
//            dump($postData);
            $res = $goods_cart->allowField(true)->save($postData);
            $resId = $goods_cart->id;
//            return $res;
            $reData['post_type']=$postData['post_type'];
            if ($res){
                $reData['code']=1;
                $reData['msg']='提交成功';
                $reData['goodsCartId']=$resId;
                return json($reData);
            }else{
                $reData['code']=2;
                $reData['msg']='提交失败';
                return json($reData);
            }
        }
    }

}
