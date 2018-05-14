<?php

namespace Admin\Controller;

use Think\Controller;

class EmailController extends Controller
{
    public function emailList() {
        $email = M('ConfigEmail')->select();

        $this->ajaxReturn($email , 'JSON');
    }

    public function emailDetital($id) {
        $email = M('ConfigEmail')->where(array(
            'id' => $id
        ))->find();

        $this->ajaxReturn($email , 'JSON');
    }

    public function emailConfig() {

        $email = I('post.');

        $rs = M('ConfigEmail')->where(array(
            'id' => 1
        ))->save(array(
            'email' => $email['email'],
            'emailpassword' => $email['emailpassword']
        ));


        if ($rs) {
            $this->ajaxSuccess('操作成功');
        }else{
            $this->ajaxError('操作失败');
        }
    }


}