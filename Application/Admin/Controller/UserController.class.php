<?php

namespace Admin\Controller;

use Think\Controller;

class UserController extends Controller
{
    //显示user表列表及删除
    public function index($page=1, $username = NULL,$email=null, $starttime = NULL, $endtime = NULL )
    {
        if ($username) {
            $where['a.id'] = M('User')->where(array(
                'username' => $username
            ))->getField('id');
        }

        if ($email) {
            $where['a.id'] = M('User')->where(array(
                'email' => $email
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

//        $coin=M('Coin')
////            ->where('status = 1')
//            ->getField('name');
        $info = M()->table('trade_user as a')
            ->field('a.id,a.username,a.email,a.addtime,a.sm_is_ok,a.is_vip,a.sm_sc_zheng,a.idcard,a.truename,a.moble ,b.usdt,b.usdtd')
            ->join('left join trade_user_coin as b on b.userid = a.id')
            ->where($where)->limit(($page-1) * 15 , 15)
            ->select();

        $total = M()->table('trade_user as a')
            ->field('a.id,a.username,a.email,a.addtime,a.sm_is_ok,a.is_vip,a.sm_sc_zheng,a.idcard,a.truename,a.moble,b.usdt,b.usdtd')
            ->join('left join trade_user_coin as b on b.userid = a.id')
            ->where($where)
            ->select();

        $data = array();

        $data['info'] = $info;
        $data['total'] = count($total);
        $this->ajaxReturn($data, 'JSON');
    }





    public function upshiming($status, $userid)
    {
        if ($status != NULL && $userid != NULL) {
            if (M('User')->where(array(
                'id' => $userid
            ))->setField('sm_is_ok', $status)
            ) {
                $this->ajaxSuccess('编辑成功！');
            } else {
                $this->ajaxError('编辑失败！');
            }
        }
        $this->ajaxError('编辑失败！');
    }

    //审核不通过
    public function refuseShiming($status , $userid , $remark){
        if ($status != NULL && $userid != NULL){

            $rs=M('User')->where(array(
                'id' => $userid,
            ))->save(array(
                'remark' => $remark,
                'sm_is_ok' => $status
            ));

            if ($rs){
                $this->ajaxSuccess('编辑成功！');
            }else{
                $this->ajaxSuccess('编辑失败！');
            }
        }

        $this->ajaxError('编辑失败！');
    }

    public function appeal($page = 1,$order_id =null)
    {
        if ($order_id){
            $$order_id=trim($order_id);
            $where['trade_id']=M('trade')->where(array(
                'order_id' => $order_id
            ))->getField('id');
        }

        $appeal = M('Appeal')->where($where)->limit(($page-1) * 15 , 15)->select();
        $total = M('Appeal')->where($where)->select();
//        $appeal = M()->table('trade_appeal as a')
//            ->field('a.id,a.buyer_id,a.sellerid,a.appeal_id,a.addtime,a.message,a.status,b.id,b.name')
//            ->join('User as b on a.appeal_id = b.id')->select();

        foreach ($appeal as $k => $v){
            $sell =M('User')->where(array(
                'id' => $appeal[$k]['seller_id']
            ))->find();

            $appeal[$k]['seller_id']=$sell['email'].'/'.$sell['username'];

            $buy =M('User')->where(array(
                'id' => $appeal[$k]['buyer_id']
            ))->find();

            $appeal[$k]['buyer_id']=$buy['email'].'/'.$buy['username'];

            $apel=M('User')->where(array(
                'id' => $appeal[$k]['appeal_id']
            ))->find();

            $appeal[$k]['appeal_id']=$apel['email'].'/'.$apel['username'];

            $appeal[$k]['order_id']=M('Trade')->where('id ='.$appeal[$k]['trade_id'])->getField('order_id');

        }

        $data = array();
        $data['appeal'] = $appeal;
        $data['total'] = count($total);

        $this->ajaxReturn($data, 'JSON');
    }

    public function closeTrade($id,$appeal_id)
    {
        //TODO 校验状态 已修改
        $tradeData = M('Trade')->where('id=' . $id)->find();

        if ($tradeData['order_status'] == 0) {
            $this->ajaxError('您的交易已经关闭');
        }

        $m = M();
        $m->execute('set autocommit = 0');
        $m->execute('lock tables trade_trade write , trade_user_coin write , trade_appeal write');

        if ($tradeData['type'] == 2) {
            $rs[] = $m->table('trade_user_coin')->where('userid=' . $tradeData['userid'])->setDec($tradeData['coin_type'] . "d", $tradeData['num']);
            $rs[] = $m->table('trade_user_coin')->where('userid=' . $tradeData['userid'])->setInc($tradeData['coin_type'], $tradeData['num']);
        }

        $rs[] = $m->table('trade_trade')->where(array(
            'id' => $id
        ))->setField('order_status', 0);

        if ($tradeData['type'] == 1){
            $rs[] = $m->table('trade_user_coin')->where(array(
                'userid' => $tradeData['trade_id']
            ))->setDec($tradeData['coin_type'] .'d' , $tradeData['num'] + $tradeData['fee']);

            $rs[] = $m->table('trade_user_coin')->where(array(
                'userid' => $tradeData['trade_id']
            ))->setInc($tradeData['coin_type'] , $tradeData['num'] + $tradeData['fee']);
        }
        $rs[] = $m->table('trade_appeal')->where(array(
            'id' => $appeal_id
        ))->setField('status' , 0);

        if (check_arr($rs)) {
            $m->execute('commit');
            $m->execute('unlock tables');

            $arr = array(
                'type' => 1,
                'msg' => '关闭成功'
            );
            $this->ajaxReturn($arr, 'JSON');

        } else {
            $m->execute('rollback');
            $m->execute('unlock tables');
            $this->ajaxError("关闭失败");
        }
    }


    public function checkAppeal($tradeid)
    {
        $tradeData = M('Trade')->where(array(
            'id' => $tradeid
        ))->select();

        if ($tradeData['type'] == 1) {
            $rs = M('User')->where(array(
                'id' => $tradeData['trade_id']
            ))->save(array(
                'status' => 0
            ));
        }

        if ($tradeData['type'] == 2) {
            $rs = M('User')->where(array(
                'id' => $tradeData['userid']
            ))->save(array(
                'status' => 0
            ));
        }

        if ($rs) {
            $this->ajaxSuccess('提交成功');
        }else{
            $this->ajaxError('提交失败');
        }
    }

    public function setvip($userid){

        $userData = M('User')->where(array(
            'id' => $userid
        ))->find();
        if ($userData['is_vip'] == 1) {
            $rs = M('User')->where(array(
                'id' => $userid
            ))->setField('is_vip' , 0);
        }
        if ($userData['is_vip'] == 0) {
            $rs = M('User')->where(array(
                'id' => $userid
            ))->setField('is_vip' , 1);
        }

        if ($rs){
            $this->ajaxSuccess('提交成功');
        }else{
            $this->ajaxError('提交失败');
        }
    }

    public function uppassword ($oldpassword , $newpassword , $repassword) {
        $admin = M('Admin')->where(array(
            'id' => 1
        ))->find();

        if (md5($oldpassword) != $admin['password']) {
            $this->ajaxError('原密码错误');
        }

        if ($newpassword != $repassword) {
            $this->ajaxError('两次输入密码不一致');
        }

        $rs = M('Admin')->where(array(
            'id' => 1
        ))->setField('password' , md5($newpassword));

        if ($rs) {
            $this->ajaxSuccess('修改成功');
        }else{
            $this->ajaxError('修改失败');
        }
    }

    public function usercoin($userid)
    {
        $coin = M('Coin')->where(array(
            'status' =>1
        ))->select();
        $usercoin = M('UserCoin')->where(array(
            'userid' => $userid
        ))->find();
        foreach ($coin as $k => $v)
        {
            $info[$k]=array(
                'coinname' => $v['name'],
                'available' =>$usercoin[$coin[$k]['name']] + 0,
                'frozen' =>$usercoin[$coin[$k]['name'].'d'] + 0,
                'total' => ($usercoin[$coin[$k]['name']] + $usercoin[$coin[$k]['name'].'d']) + 0

            );

        }

        $this->ajaxReturn($info,'JSON');
    }
}