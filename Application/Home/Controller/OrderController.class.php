<?php

namespace Home\Controller;

class OrderController extends HomeController {
    //用户订单详情
    public function index($userid , $token){

        $this->checkLog($userid , $token);

        $tradeData = M('Trade')
            ->field('adver_id,order_id,id,userid,addtime,coin_type,type,price,num,mum,trade_id,paycode,fee,status,order_status,mum')
            ->where('userid = ' .$userid . ' or trade_id = ' . $userid)->order('addtime desc')
            ->select();

        foreach ($tradeData as $k => $v) {
            $tradeData[$k]['fee']=$v['fee']+ '0';
            $tradeData[$k]['price']=$v['price']+ '0';
            $tradeData[$k]['num']=$v['num']+ '0';
            $tradeData[$k]['mum']=$v['mum']+ '0';
            $username = M('User')->where(array(
                'id' => $v['userid']
            ))->getField('username');

            $tradename = M('User')->where(array(
                'id' => $v['trade_id']
            ))->getField('username');

            $tradeData[$k]['username'] = $username;
            $tradeData[$k]['tradename'] = $tradename;
        }


        foreach ($tradeData as $k => $v) {
            $chatInfo = M('Chat')->where(array(
                'chatid' => $userid,
                'status' => 0,
                'trade_id' => $v['id']
            ))->getField('id');

            if ($chatInfo){
                $tradeData[$k]['chatlog'] = 1;
            }else{
                $tradeData[$k]['chatlog'] = 0;
            }
        }
        $this->ajaxReturn($tradeData,'JSON');
    }
}