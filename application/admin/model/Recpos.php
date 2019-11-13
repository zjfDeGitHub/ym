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

class Recpos extends \think\Model {

        public function getGoods($recposId = 0){
                $RecModel = new self;
                $goodsData = db('recpos')->alias('r')->where('r.id'.$recposId)->join('rec_item ri','r.id = ri.recpos_id','right')
                            ->join('goods g','ri.value_id=goods_id')->filed('r.rec_name,g.*')->select();
                return $goodsData;
        }

}
