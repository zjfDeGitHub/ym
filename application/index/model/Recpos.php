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

class Recpos extends \think\Model {

    public function getGoods($recposId = 0,$limit = 100){
        $RecModel = new self;

        $goodsData = db('recpos')->alias('r')->where('r.id',$recposId)->where('g.active_id',0)->where('g.on_sale',1)->join('rec_item ri','r.id = ri.recpos_id','right')
            ->join('goods g','ri.value_id=g.id')->field('r.rec_name,g.*')->limit($limit)->select();

        return $goodsData;
    }

}
