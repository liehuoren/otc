<?php

namespace Admin\Controller;
use Think\Controller;

class SystemController extends Controller{
    public function indexImg() {
        $img = M('System')->select();

        $this->ajaxReturn($img ,'JSON');

    }

    public function uploadImg()
    {
        $img = $_FILES['img'];
        $upload = new \Think\Upload();
        $upload->maxSize = 3145728;
        $upload->exts = array(
            'jpg',
            'gif',
            'png',
            'jpeg'
        );
        $upload->savePath = '/Public/Uploads/index';
        $upload->autoSub = true;
        $info = $upload->uploadOne($img);
        $path = $info['savepath'] . $info['savename'];
        $data = array(
            'path' => $path
        );
        $this->ajaxReturn($data,'JSON');
    }


    public function submitImg($id =NULL ,$img , $sort , $title , $url)
    {
        if ($img == '') {
            $this->ajaxError('ID 不能为空');
        }

        if ($title == '') {
            $this->ajaxError('标题不能为空');
        }
        if ($sort == '') {
            $this->ajaxError('排序不能为空');
        }
//        if (!is_int($sort)) {
//            $this->ajaxError('排序必须填数字');
//        }
        if ($id == '') {
            $rs = M('System')->add(array(
                'img' => $img,
                'addtime' => time(),
                'sort' => $sort,
                'title' => $title,
                'url' => $url
            ));

            if ($rs){
                $this->ajaxSuccess('添加成功');
            }else{
                $this->ajaxError('添加失败');
            }
        }else{
            $rs = M('System')->where(array(
                'id' => $id
            ))->save(array(
                'img' => $img,
                'addtime' => time(),
                'sort' => $sort,
                'title' => $title,
                'url' => $url
            ));

            if ($rs) {
                $this->ajaxSuccess('修改成功');
            }else{
                $this->ajaxError('修改失败');
            }
        }
    }

    public function detital($id)
    {
        if ($id == '') {
            $this->ajaxError('ID 不能为空');
        }
        $imgData = M('System')->where(array(
            'id' => $id
        ))->find();

        $this->ajaxReturn($imgData , 'JSON');
    }

    public function deleteImg($id)
    {
        if ($id == '') {
            $this->ajaxError('ID 不能为空');
        }
        $rs = M('System')->where(array(
            'id' => $id
        ))->delete();

        if ($rs) {
            $this->ajaxSuccess('删除成功');
        }else{
            $this->ajaxError('删除失败');
        }
    }
    public function problemIndex()
    {
        $data = M('Comproblem')->select();
        $this->ajaxReturn($data,'JSON');
    }

    public function uploadProblem($content,$type )
    {
        if ($content == '' || $content == null) {
            $this->ajaxError('内容不能为空');
        }

        if ($type == '' || $type == null){
            $this->ajaxError('类型不能为空');
        }
        $data = M('Comproblem')->where(array(
            'type' => $type
        ))->select();
        if ($data){
            $rs=M('Comproblem')->where(array(
                'type' => $type
            ))->save(array(
                'content'      => $content,
                'type'     => $type
            ));
        }else{
            $rs = M('Comproblem')->add(array(
                'content'      => $content,
                'type'     => $type
            ));
        }

        if ($rs){
            $this->ajaxSuccess('添加成功');
        }else{
            $this->ajaxError('添加失败');
        }
    }

    public function guideIndex(){
        $data =M('Guide')->select();

        $this->ajaxReturn($data ,'JSON');
    }

    public function checkGuide($id){
        if ($id==null || $id == ''){
            $this->ajaxError('ID不能为空');
        }

        $data=M('Guide')->where('id ='.$id)->find();
        $this->ajaxReturn($data ,'JSON');
    }

    public function uploadGuide($title ,$content,$sort){

        if ($title == null || $title == ''){
            $this->ajaxError('标题不能为空');
        }
        if ($content == null || $content == ''){
            $this->ajaxError('内容不能为空');
        }
        if ($sort == null || $sort == ''){
            $this->ajaxError('排序值不能为空');
        }

        $rs = M('Guide')->add(array(
            'title' => $title ,
            'content' => $content ,
            'sort' => $sort ,
            'addtime'=>time()
        ));
        if ($rs){
            $this->ajaxSuccess('添加成功');
        }else{
            $this->ajaxSuccess('添加失败');
        }
    }

    public function updateGuide($id,$title,$content,$sort){
        if ($id==null || $id == ''){
            $this->ajaxError('ID不能为空');
        }
        $rs=M('Guide')->where('id = '.$id)->save(array(
            'title' => $title,
            'content' => $content,
            'sort' => $sort,
            'addtime'=>time()
        ));

        if ($rs){
            $this->ajaxSuccess('修改成功');
        }else{
            $this->ajaxSuccess('修改失败');
        }
    }
    public function delGuide($id){
        if ($id==null || $id == ''){
            $this->ajaxError('ID不能为空');
        }
        $rs=M('Guide')->where('id ='.$id)->delete();

        if ($rs){
            $this->ajaxSuccess('删除成功');
        }else{
            $this->ajaxSuccess('删除失败');
        }
    }
}