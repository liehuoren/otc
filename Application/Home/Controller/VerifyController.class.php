<?php

namespace Home\Controller;

class VerifyController extends HomeController
{

    public function __construct()
    {
        parent::__construct();
    }



    public function sendEmailCode()
    {
        $input = I('post.');

        if (!check($input['email'], 'email')) {
            $this->ajaxError('邮箱格式错误');
        }

        $user = M('User')->where(array(
            'email' => $input['email']
        ))->find();
        if ($user) {
            $this->ajaxError('邮箱已存在');
        }

        $code = rand(111111, 999999);
        $content = '尊敬的用户： 您的验证码是：'.$code .'。你正在进行注册操作，请不要把验证码泄露给其他人。 感谢您对T-Bees的支持,祝您生活愉快！ 【T-Bees】';

        // $email = M('ConfigEmail')->where(array(
        //     'id' => 1
        // ))->find();

        // $result = sendMail($input['email'] ,'',$content , $email['email'] , $email['emailpassword']);
        $result = sendMail($input['email'] ,'',$content , C('email') , C('emailpassword'));

        if ($result){

            $rs = M('EmailCode')->add(array(
                'email' => $input['email'],
                'code' => $code,
                'addtime' => time()
            ));

            if ($rs){
                $this->ajaxSuccess('邮箱验证码已发送到您的邮箱');
            }else{
                $this->ajaxError('邮箱验证码发送失败,请重新点击发送');
            }
        }
    }

    public function findpwdEmailCode(){
        $input = I('post.');

        if (!check($input['email'], 'email')) {
            $this->error(L('邮箱格式错误'));
        }

        $user = M('User')->where(array(
            'email' => $input['email']
        ))->find();

        if (!$user){
            $this->ajaxError('您输入的邮箱不存在');
        }

        $code = rand(111111, 999999);
        $content = '尊敬的用户： 您的验证码是：'. $code .'。你正在进行找回密码操作，请不要把验证码泄露给其他人。 感谢您对T-Bees的支持,祝您生活愉快！ 【T-Bees】';

        // $email = M('ConfigEmail')->where(array(
        //     'id' => 1
        // ))->find();

        // $result = sendMail($input['email'] ,'',$content ,$email['email'] , $email['emailpassword']);
        $result = sendMail($input['email'] ,'',$content , C('email') , C('emailpassword'));
        if ($result){

            $rs = M('EmailCode')->add(array(
                'email' => $input['email'],
                'code' => $code,
                'addtime' => time()
            ));

            if ($rs){
                $this->ajaxSuccess('邮箱验证码已发送到您的邮箱');
            }else{
                $this->ajaxError('邮箱验证码发送失败,请重新点击发送');
            }
        }
    }

    public function findPayPassWord() {
        $input = I('post.');

        $user = M('User')->where(array(
            'id' => $input['userid']
        ))->find();

        $code = rand(111111, 999999);
        $content = '尊敬的用户： 您的验证码是：' . $code . '。您正在进行修改资金密码操作，请不要把验证码泄露给其他人。 感谢您对T-Bees的支持,祝您生活愉快！ 【T-Bees】';

        // $email = M('ConfigEmail')->where(array(
        //     'id' => 1
        // ))->find();

        // $result = sendMail($user['email'] ,'',$content , $email['email'] , $email['emailpassword']);
        $result = sendMail($user['email'] ,'',$content , C('email') , C('emailpassword'));
        if ($result){
            $rs = M('EmailCode')->add(array(
                'email' => $user['email'],
                'code' => $code,
                'addtime' => time()
            ));

            if ($rs){
                $this->ajaxSuccess('邮箱验证码已发送到您的邮箱');
            }else{
                $this->ajaxError('邮箱验证码发送失败,请重新点击发送');
            }
        }
    }


    public function myzcCode() {
        $input = I('post.');

        $user = M('User')->where(array(
            'id' => $input['userid']
        ))->find();

        $code = rand(111111, 999999);
        $content = '尊敬的用户： 您的验证码是：' . $code . '。您正在进行转出操作，请不要把验证码泄露给其他人。 感谢您对T-Bees的支持,祝您生活愉快！ 【T-Bees】';

        // $email = M('ConfigEmail')->where(array(
        //     'id' => 1
        // ))->find();
        // $result = sendMail($user['email'] ,'',$content , $email['email'] , $email['emailpassword']);
        $result = sendMail($user['email'] ,'',$content , C('email') , C('emailpassword'));
        if ($result){
            $rs = M('EmailCode')->add(array(
                'email' => $user['email'],
                'code' => $code,
                'addtime' => time()
            ));

            if ($rs){
                $this->ajaxSuccess('邮箱验证码已发送到您的邮箱');
            }else{
                $this->ajaxError('邮箱验证码发送失败,请重新点击发送');
            }
        }
    }
}

?>