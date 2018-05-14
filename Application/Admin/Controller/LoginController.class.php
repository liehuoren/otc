<?php
namespace Admin\Controller;
class LoginController extends \Think\Controller
{

    public function login($username = NULL, $password = NULL)
    {
        $admin = M('Admin')->where(array(
            'username' => $username
        ))->find();

        if (!$admin) {
            $this->ajaxError('没有此用户');
        }

        if ($admin['password'] != md5($password)) {
            $this->ajaxError('用户名或密码错误！');
        } else {
            $data = array(
                'msg' => '登陆成功!',
                'type' => 1,
                'username' => $admin['username']
            );
            $this->ajaxReturn($data , 'JSON');
        }
    }

}

?>