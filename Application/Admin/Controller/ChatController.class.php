<?php

namespace Admin\Controller;

use Think\Controller;

class ChatController extends Controller
{
    public function index($page=1,$trade_id = null, $username = NULL, $starttime = NULL, $endtime = NULL)
    {

        if ($username) {
            $username=trim($username);
            $where['userid'] = M('User')->where(array(
                'username' => $username
            ))->getField('id');
        }
        if ($trade_id) {
            $trade_id=trim($trade_id);
        $where['trade_id'] = M('trade')->where(array(
            'order_id' => $trade_id
        ))->getField('id');
    }

        if (!empty($starttime) && !empty($endtime)) {
            $where['_string'] = ' addtime > ' . strtotime($starttime) . ' AND addtime < ' . strtotime($endtime . ' 23:59:59');
        } else {
            if (!empty($starttime)) {
                $where['addtime'] = array(
                    'GT',
                    strtotime($starttime)
                );
            } else
                if (!empty($endtime)) {
                    $where['addtime'] = array(
                        'LT',
                        strtotime($endtime . ' 23:59:59')
                    );
                }
        }

        $info = M('Chat')
            ->field('userid,chatid,content,addtime,trade_id,img')
            ->where($where)
            ->limit(($page-1) * 15 , 15)
            ->select();

        $total = M('Chat')
            ->field('userid,chatid,content,addtime,trade_id,img')
            ->where($where)
            ->select();

        foreach ($info as $k => $v) {
            $username = M('User')->where(array(
                'id' => $v['userid']
            ))->getField('username');

            $info[$k]['username'] = $username;
            $info[$k]['trade_id'] =M('Trade')->where('id='.$v['trade_id'])->getField('order_id');
            $chatname = M('User')->where(array(
                'id' => $v['userid']
            ))->getField('username');

            $info[$k]['chatname'] = $chatname;
        }

        $data = array();

        $data['info'] = $info;
        $data['total'] = count($total);
        $this->ajaxReturn($data , 'JSON');
    }
}