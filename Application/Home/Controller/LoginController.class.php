<?php

namespace Home\Controller;

class LoginController extends HomeController
{
    /*
     * invit 用户邀请码
     * invit_1 邀请人邀请码
     *
     */

    public function upregister($username,$email, $password ,$code,$invit_1 = null)
    {

        $emailCode = M('EmailCode')->where(array(
            'email' => $email
        ))->order('id desc')->find();


        if (!$emailCode) {
            $this->ajaxError('请发送验证码');
        }

        if (time() > $emailCode['addtime'] + 300){
            $this->ajaxError('您的验证码过期，请重新输入验证码');
        }

        if ($emailCode['email'] . $emailCode['code'] != $email.$code) {
            $this->ajaxError('您输入的验证码有误');
        }

        if (!check($email, 'email')) {
            $this->ajaxError('邮箱格式错误');
        }


        if (strlen($password) !=32) {
            $this->ajaxError('登录密码格式错误');
        }


        $user = M('User')->where(array(
            'email' => $email
        ))->find();
        if ($user) {
            $this->ajaxError('邮箱已存在');
        }

        if (M('User')->where(array(
            'username' => $username
        ))->find()){
            $this->ajaxError('昵称已经存在');
        }
        if ($invit_1)
        {
            $invit_user=M('User')->where(array(
                'invit' =>$invit_1
            ))->find();
            if (!$invit_user)
            {
                $this->ajaxError('邀请人不存在,请重新确认');
            }
            $invit_2 = M('User')->where(array(
                'invit' => $invit_1
            ))->getField('invit_1');

        }
        $invit = get_invit_id();

        if (M('User')->where(array(
            'invit' =>$invit
        ))->find())
        {
            $invit = get_invit_id();
        }

        $mo = M();
        $mo->execute('set autocommit=0');
        $mo->execute('lock tables trade_user write , trade_user_coin write , trade_user_credit write, trade_invit_reward write');
        $rs = array();
        $rs[] = $mo->table('trade_user')->add(array(
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'tpwdsetting' => 1,
            'addip' => get_client_ip(),
            'addr' => get_city_ip(),
            'addtime' => time(),
            'email_is_ok' => 1,
            'status' => 1,
            'invit' => $invit,
            'invit_1' => $invit_1,
            'invit_2' => $invit_2
        ));

        $rs[] = $mo->table('trade_user_coin')->add(array(
            'userid' => $rs[0]
        ));

        $rs[] = $mo->table('trade_user_credit')->add(array(
            'userid' => $rs[0]
        ));

        if (check_arr($rs)) {
            $mo->execute('commit');
            $mo->execute('unlock tables');
            $this->ajaxSuccess('注册成功!');
        } else {
            $mo->execute('rollback');
            $mo->execute('unlock tables');
            $this->ajaxError('注册失败');
        }
    }


    public function submit($email, $password)
    {
        if (!check($email,'email')){
            $this->ajaxError('用户名格式错误');
        }

        if (strlen($password) != 32) {
            $this->ajaxError('登录密码格式错误');
        }

        $u = M('User')->where(array(
            'email' => $email
        ))->find();

        if (!$u) {
            $this->ajaxError('帐号密码不匹配，请重新输入!');
        }
        if ($email != $u['email']) {
            $this->ajaxError('用户名错误');
        }


        if ($password != $u['password']) {
            $this->ajaxError('密码错误');
        }

        $salt = rand(0000,9999);

        $time = time();

        $token = md5($u['id'] . $time . md5($salt));

        $tokenrs = M('User')->where(array(
            'id' => $u['id']
        ))->save(array(
            'endtime' => $time,
            'salt' => $salt,
            'token' => $token,
            'lasttime' => $time,
        ));

        if(!$tokenrs){
            $this->ajaxError('存入token失败');
        }

        // 判断用户帐号状态
        $user = M('User')->where(array(
            'id' => $u['id']
        ))->find();

        $mo = M();
        $mo->execute('set autocommit=0');
        $mo->execute('lock tables trade_user write , trade_user_log write ');
        $rs = array();
        $rs[] = $mo->table('trade_user')
            ->where(array(
                'id' => $user['id']
            ))
            ->setInc('logins', 1);
        $rs[] = $mo->table('trade_user_log')->add(array(
            'userid' => $user['id'],
            'type' => 'WEB',
            'remark' => 'Mobile',
            'addtime' => time(),
            'addip' => get_client_ip(),
            'addr' => get_city_ip(),
            'status' => 1
        ));

        if (check_arr($rs)) {
            $mo->execute('commit');
            $mo->execute('unlock tables');

            $data = array(
                'type' => 1,
                'msg' => '登陆成功',
                'userid' => $user['id'],
                'username' => $user['username'],
                'token' => $token
            );
            $this->ajaxReturn($data,'JSON');
        } else {
            $mo->execute('rollback');
            $mo->execute('unlock tables');
            $this->ajaxError('登录失败');
        }
    }


    //设置资金密码
    public function setPaypasswordUp($paypassword, $repaypassword, $login_password)
    {
        if (!userid()) {
            $this->ajaxError('请登录!');
        }

//        if (!check($paypassword, 'password')) {
//            $this->ajaxError('资金密码格式错误');
//        }
        if (strlen($paypassword) != 32) {
            $this->ajaxError('资金密码格式错误');
        }

        if ($paypassword != $repaypassword) {
            $this->ajaxError('两次密码不一致，请重新输入');
        }

        $user = M('User')->where(array(
            'id' => userid()
        ))->find();

        if ($user['password'] != $login_password) {
            $this->ajaxError('登录密码错误');
        }

        if ($user['password'] == $paypassword) {
            $this->ajaxError('资金密码不能和登录密码一样');
        }

        if (M('User')->where(array(
            'id' => userid()
        ))->save(array(
            'paypassword' => $paypassword
        ))
        ) {
            $this->ajaxSuccess('操作成功');
        } else {
            $this->ajaxError('操作失败');
        }
    }

    //设置实名认证
    public function setTruenameUp()
    {
        if (empty($_POST['firstName'])) {
            $this->ajaxError('请填写名!');
        }

        if (empty($_POST['lastName'])) {
            $this->ajaxError('请填写姓!');
        }

        if (empty($_POST['idType'])) {
            $this->ajaxError('请选择证件类型!');
        }

        if (empty($_POST['idCard'])) {
            $this->ajaxError('请填写证件号码!');
        }

        if (empty($_POST['nation'])) {
            $this->ajaxError('请选择国籍!');
        }

        if (M('User')->where(array(
            'id' => userid()
        ))->save(array(
            'truename' => $_POST['lastName'] . $_POST['firstName'],
            'idcard' => $_POST['idCard'],
            'nation' => $_POST['nation'],
            'id_type' => $_POST['idType']
        ))
        ) {
            $this->ajaxSuccess('操作成功');
        } else {
            $this->ajaxError('操作失败');
        }
    }

}