<?php
namespace Home\Model;

use Think\Model;

class UserModel extends Model
{

    public function get_userid($username = NULL)
    {
        if (empty($username)) {
            return null;
        }
        
        $get_userid_user = (APP_DEBUG ? null : S('get_userid_user' . $username));
        
        if (! $get_userid_user) {
            $get_userid_user = M('User')->where(array(
                'username' => $username
            ))->getField('id');
            S('get_userid_user' . $username, $get_userid_user);
        }
        
        return $get_userid_user;
    }

    public function get_username($id = NULL)
    {
        if (empty($id)) {
            return null;
        }
        
        $user = (APP_DEBUG ? null : S('get_username' . $id));
        
        if (! $user) {
            $user = M('User')->where(array(
                'id' => $id
            ))->getField('username');
            S('get_username' . $id, $user);
        }
        
        return $user;
    }

    /**
     * 添加用户登录日志
     */
    public function add_login_log($uid = null, $status = 1)
    {
        if (empty($uid)) {
            return false;
        }
        
        return M('UserLog')->add(array(
            'userid' => $uid,
            'type' => 'WEB',
            'remark' => 'Mobile',
            'addtime' => time(),
            'addip' => get_client_ip(),
            'addr' => get_city_ip(),
            'status' => $status
        ));
    }

    /**
     * 取单位时间内用户登录日志数量
     */
    public function get_login_num($uid, $status = 0, $interval_time = 7200)
    {
        if (empty($uid)) {
            return false;
        }
        return M('UserLog')->where(array(
            'userid' => $uid,
            'status' => $status,
            'addtime' => array(
                'EGT',
                time() - $interval_time
            )
        ))->count();
    }

    /**
     * 修改用户帐号状态
     */
    public function update_user_status($uid, $status = 1)
    {
        if (empty($uid)) {
            return false;
        }
        
        return M('User')->where(array(
            'id' => $uid
        ))->save(array(
            'status' => $status,
            'endtime' => time()
        ));
    }
}

?>