<?php
namespace app\admin\controller;
use think\Controller;
class Order extends Controller
{
    public function lst()
    {
        $where=array();
        //不会显示拼团订单
        $orderCode = input('orderCode');
        if (!empty($orderCode)){
            $where['pay_status']=['like',$orderCode];
        }
        $post_status = input('post_status');
        if (input('pay_status')==1){
            $where['pay_status']=['=',1];
        }elseif($post_status==0){
            $where['post_status']=['=',0];
        }elseif ($post_status==1){
            $where['post_status']=['=',1];
        }
        if (input('pt_id')==1){
            $where['pt_id']=['neq',0];
        }
        $orderRes=db('order')->alias('o')->join("user u","o.user_id = u.id")->field('o.id,o.pt_id,o.out_trade_no,o.goods_total_price,o.pay_status,o.order_status,o.post_status,o.distribution,o.payment,o.name,o.phone,o.order_time,u.username')->where($where)->order('o.id DESC')->paginate(10);
        $this->assign([
            'orderRes'=>$orderRes,
        ]);
        return view('list');
    }

    public function detail($id){
        $orderInfo = db('order')->alias('o')->join("user u","o.user_id = u.id")->field('o.*,u.username')->find($id);
        $this->assign('orderInfo',$orderInfo);
        return view('detail');
    }


    public function edit()
    {
        if(request()->isPost()){
            $data=input('post.');
            $userId = db('user')->where('username',$data['username'])->value('id');
            if($userId){
                $data['user_id'] = $userId;
            }
            $data['order_time'] = strtotime($data['order_time']);
//             dump($data); die;
//            //验证
//            $validate = validate('order');
//            if(!$validate->check($data)){
//                $this->error($validate->getError());
//            }
            $save=model('order')->allowField(true)->isUpdate(true)->save($data);
            if($save !== false){
                $this->success('修改订单成功！','lst','',1);
            }else{
                $this->error('修改订单失败！');
            }
            return;
        }
        $id=input('id');
        $orderInfo = db('order')->alias('o')->where('o.id',$id)->join("user u","o.user_id = u.id")->field('o.*,u.username')->find();
//        dump($orderInfo);die();
        $this->assign([
            'orderInfo'=>$orderInfo,
        ]);
        return view();
    }

    public function orderGoods($id){
        $orderGoodsRes = db('orderGoods')->where('order_id',$id)->select();
        $goodAttr = db('goods_attr');
        foreach ($orderGoodsRes as $k =>$v){
            $attrArr = explode(',',$v['goods_attr_id']);
            $orderGoodsRes[$k]['attr_st'] = null;
            $comma_separated = null;
            foreach ($attrArr as $k2 =>$v2){
                $str =$goodAttr->where('id',$v2)->field('attr_value,attr_price')->find();
                @$comma_separated .= implode(",", $str).'；';
                if ($comma_separated == '；'){
                    $orderGoodsRes[$k]['attr_st']= $orderGoodsRes[$k]['goods_attr_str'];
                    break 2;
                }
            }
            $orderGoodsRes[$k]['attr_st'] =str_replace(',','￥',$comma_separated);
        }
//        dump($orderGoodsRes);die();
        $this->assign([
            'orderGoodsRes' => $orderGoodsRes
        ]);
        return view();
    }

    public function orderGoodsEdit(){
        if(request()->isPost()){
            $data = input('post.');
            $save = db('order_goods')->update($data);
            if($save !== false){
                $this->success('修改订单商品成功！');
            }else{
                $this->error('修改订单商品失败！');
            }
        }
        $orderGoodsId = input('id');
        $orderGoodsInfo = db('orderGoods')->find($orderGoodsId);
        $this->assign([
            'orderGoodsInfo'=>$orderGoodsInfo,
        ]);
        return view();
    }

    public function orderGoodsDel($id){
        $res = db('orderGoods')->delete($id);
        $this->success('删除订单商品成功！');
    }

    public function del($id)
    {
        $order=db('order');
//        $orders=$order->field('order_img')->find($id);
//        $orderImg=IMG_UPLOADS.$orders['order_img'];
//        if(file_exists($orderImg)){
//            @unlink($orderImg);
//        }
        @$del=db('order_goods')->where('order_id',$id)->delete();
        $del=$order->where('id',$id)->delete();
        if($del){
            $this->success('删除订单成功！','lst');
        }else{
            $this->error('删除订单失败！');
        }
    }

    //上传图片
    public function upload(){
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('order_img');

        // 移动到框架应用根目录/public/uploads/ 目录下
        if($file){
            $info = $file->move(ROOT_PATH . 'public' . DS . 'static'. DS .'uploads');
            if($info){
                return $info->getSaveName();
            }else{
                // 上传失败获取错误信息
                echo $file->getError();
                die;
            }
        }
    }


}