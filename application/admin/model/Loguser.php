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

class Loguser extends \think\Model {

    public function isOK($id =0 ){
        $loguserModel = new self;
        $qdtime = $loguserModel->where('userId',$id)->field('qdtime')->find();
//        dump($qdtime['qdtime']);die();
        $nowtime = time();
        if ($nowtime  > 86400 + $qdtime['qdtime']){
            return 1;
        }else if ($nowtime  < 86400 +$qdtime['qdtime']){
            return 0;
        }

    }

}
