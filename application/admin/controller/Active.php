<?php
namespace app\admin\controller;
use think\Controller;
class Active extends Controller
{
    public function lst()
    {
        $recposRes=db('active')->paginate(6);
        $this->assign([
            'recposRes'=>$recposRes,
        ]);
        return view('list');
    }

    public function add()
    {
        if(request()->isPost()){
            $data=input('post.');
            if($_FILES['active_img']['tmp_name']){
                $data['active_img']=$this->upload();
            }
            //验证
//            $validate = validate('recpos');
//            if(!$validate->check($data)){
//                $this->error($validate->getError());
//            }
            $add=db('active')->insert($data);
            if($add){
                $this->success('添加活动成功！','lst');
            }else{
                $this->error('添加活动失败！');
            }
            return;
        }
        return view();
    }

    public function edit()
    {
        if(request()->isPost()){
            $data=input('post.');
            if($_FILES['active_img']['tmp_name']){
                $data['active_img']=$this->upload();
                $categorys=db('active')->field('active_img')->where('id',$data['id'])->find();
                if($categorys['active_img']){
                    $imgSrc=IMG_UPLOADS.$categorys['active_img'];
                    if(file_exists($imgSrc)){
                        @unlink($imgSrc);
                    }
                }
            }
            //验证
//            $validate = validate('recpos');
//            if(!$validate->check($data)){
//                $this->error($validate->getError());
//            }
            $save=db('active')->update($data);
            if($save !== false){
                $this->success('修改活动成功！','lst');
            }else{
                $this->error('修改活动失败！');
            }
            return;
        }
        $id=input('id');
        $recpos=db('active')->find($id);
        $this->assign([
            'recpos'=>$recpos,
        ]);
        return view();
    }

    public function del($id)
    {
        $del=db('active')->delete($id);
        if($del){
            $this->success('删除活动成功！','lst');
        }else{
            $this->error('删除活动失败！');
        }
    }

    public function upload(){
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('active_img');

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