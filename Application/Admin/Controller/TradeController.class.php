<?php

namespace Admin\Controller;

use Think\Controller;

class TradeController extends Controller
{
    public function index($page, $order_id = null, $username = NULL, $starttime = NULL, $endtime = NULL)
    {
        if ($username) {
            $where['userid'] = M('User')->where(array(
                'username' => $username
            ))->getField('id');
        }
        if ($order_id) {
            $where['order_id'] = $order_id;
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

        $info = M('Trade')
            ->field('userid,num,price,money_type,pay_type,type,coin_type,addtime,trade_id,deal,order_status,order_id,mum,paycode,fee')
            ->where($where)->limit(($page - 1) * 15, 15)
            ->select();

        $total = M('Trade')
            ->field('userid,num,price,money_type,pay_type,type,coin_type,addtime,trade_id,deal,order_status,order_id,mum,paycode,fee')
            ->select();


        foreach ($info as $k => $v) {
            $username = M('User')->where(array(
                'id' => $v['userid']
            ))->getField('username');

            $info[$k]['username'] = $username;

            $tradename = M('User')->where(array(
                'id' => $v['trade_id']
            ))->getField('username');

            $info[$k]['tradename'] = $tradename;

            $moneytype = M('MoneyType')->where(array(
                'id' => $v['money_type']
            ))->getField('zh_name');

            $info[$k]['money_zh_name'] = $moneytype;
        }


        $data = array();

        $data['info'] = $info;
        $data['total'] = count($total);
        $this->ajaxReturn($data, 'JSON');
    }


    public function fee()
    {

        $fee = M('Fee')->select();

        $this->ajaxReturn($fee, 'JSON');
    }


    public function upfee()
    {
        if ($_POST['fee'] < 0 || $_POST['fee'] > 1) {
            $this->ajaxError('手续费应在 0 - 1 之间');
        }

        $rs = M('Fee')->where(array(
            'coinname' => $_POST['coinname']
        ))->save($_POST);

        if ($rs) {
            $this->ajaxSuccess('修改成功');
        } else {
            $this->ajaxError('修改失败');
        }
    }

    public function feeList()
    {

        $info = M('Fee')->where(array(
            'id' => $_GET['id']
        ))->find();

        $this->ajaxReturn($info, 'JSON');
    }
}