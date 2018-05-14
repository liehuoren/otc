<?php
namespace Home\Controller;




class AppealController extends HomeController {
    public function index($userid ,$token){

        $this->checkLog($userid, $token);

        $appealdata=M('Appeal')->where(array(
            'appeal_id'=>$userid
        ))->select();
        foreach ($appealdata as $k=>$v){
            $buyer=M('User')->where(array(
                'id'=>$v['buyer_id']
            ))->getField('username');
            $seller=M('User')->where(array(
                'id'=>$v['seller_id']
            ))->getField('username');

            $appealer=M('User')->where(array(
                'id'=>$v['appeal_id']
            ))->getField('username');

            $v['buyer_id']=$buyer;
            $v['seller_id']=$seller;
            $v['appeal_id']=$appealer;

            if ($v['appeal_status']==0){
                $v['appeal_status']='审核中';
             }
            if ($v['appeal_status']==1){
                $v['appeal_status']='申诉完成 ';
            }
            if ($v['appeal_status']==2){
                $v['appeal_status']='申诉失败 ';
            }
             $appdata[]=$v;
        }
        $this->ajaxReturn($appdata,'JSON');
    }

    //申诉
    public function appeal($order_id , $userid , $token , $message) {

        $this->checkLog($userid , $token);
        //获取IMG路径
        $data=I("post.");

        $tradeData = M('Trade')->where(array(
            'id' => $order_id
        ))->find();

        if ($tradeData['order_status'] != 2) {
            $this->ajaxError('错误操作');
        }

        if ($tradeData['order_status'] == 4) {
            $this->ajaxError('此订单已经申诉');
        }

        $res = M('Trade')->where(array(
            'id' => $order_id
        ))->setField('order_status' , 4);

        if (!$res) {
            $this->ajaxError('修改订单状态失败');
        }

        $appeal=M('Appeal')->where('trade_id ='.$order_id)->select();
        if ($appeal){
            $rs=M('Appeal')->where('trade_id = '.$order_id)->save(array(
                'appeal_id' => $userid,
                'status' => 1
                ));
        }else{
            if ($tradeData['type'] == 1){
                $rs = M('Appeal')->add(array(
                    'trade_id' => $order_id,
                    'buyer_id' => $tradeData['userid'],
                    'seller_id' => $tradeData['trade_id'],
                    'appeal_id' => $data['userid'],
                    'addtime' => time(),
                    'message' => $message,
                    'status' => 1
                ));
            }
            if ($tradeData['type'] == 2){
                $rs = M('Appeal')->add(array(
                    'trade_id' => $order_id,
                    'buyer_id' => $tradeData['trade_id'],
                    'seller_id' => $tradeData['userid'],
                    'appeal_id' => $data['userid'],
                    'addtime' => time(),
                    'message' => $message,
                    'status' => 1
                ));
            }

        }

        if ($rs) {
            if ($userid == $tradeData['userid']) {
                if ($tradeData['type'] == 1) {
                    M('UserOperation')->add(array(
                        'userid' => $userid,
                        'trade_id' => $tradeData['trade_id'],
                        'addtime' => time(),
                        'status' => 0,
                        'order_id' => $tradeData['order_id'],
                        'tradeid' => $tradeData['id']
                    ));
                }

                if ($tradeData['type'] == 2) {
                    M('UserOperation')->add(array(
                        'userid' => $userid,
                        'trade_id' => $tradeData['trade_id'],
                        'addtime' => time(),
                        'status' => 0,
                        'order_id' => $tradeData['order_id'],
                        'tradeid' => $tradeData['id']
                    ));
                }
            }

            if ($userid == $tradeData['trade_id']) {
                if ($tradeData['type'] == 1){
                    M('UserOperation')->add(array(
                        'userid' => $userid,
                        'trade_id' => $tradeData['userid'],
                        'addtime' => time(),
                        'status' => 0,
                        'order_id' => $tradeData['order_id'],
                        'tradeid' => $tradeData['id']
                    ));
                }

                if ($tradeData['type'] ==2) {
                    M('UserOperation')->add(array(
                        'userid' => $userid,
                        'trade_id' => $tradeData['userid'],
                        'addtime' => time(),
                        'status' => 0,
                        'order_id' => $tradeData['order_id'],
                        'tradeid' => $tradeData['id']
                    ));
                }

            }

            $this->ajaxSuccess('正在处理，请耐心等待');
        }else{
            $this->ajaxError('申诉失败');
        }
    }

    public function closeAppeal($order_id , $userid , $token){
        $this->checkLog($userid , $token);

        $tradeData = M('Trade')->where(array(
            'id' => $order_id
        ))->find();

        if ($tradeData['order_status'] != 4) {
            $this->ajaxError('错误操作！');
        }

        if ($order_id == '') {
            $this->ajaxError('订单id不能为空');
        }

        if ($userid == '') {
            $this->ajaxError('用户id不能为空');
        }

        $appeal=M('Appeal')->where('trade_id ='.$order_id)->getField('appeal_id');

        if ($userid != $appeal){
            $this->ajaxError('非本人操作申诉订单，不可取消申诉');
        }
        $res = M('Appeal')->where(array(
            'trade_id' => $order_id
        ))->setField('status' , 0);

        if (!$res) {
            $this->ajaxError('修改申诉的列表失败');
        }

        $rs = M('Trade')->where(array(
            'id' => $order_id
        ))->setField('order_status' , 2);

        if ($rs) {
            if ($userid == $tradeData['userid']) {
                if ($tradeData['type'] == 1) {
                    M('UserOperation')->add(array(
                        'userid' => $userid,
                        'trade_id' => $tradeData['trade_id'],
                        'addtime' => time(),
                        'status' => 0,
                        'order_id' => $tradeData['order_id'],
                        'tradeid' => $tradeData['id']
                    ));
                }

                if ($tradeData['type'] == 2) {
                    M('UserOperation')->add(array(
                        'userid' => $userid,
                        'trade_id' => $tradeData['trade_id'],
                        'addtime' => time(),
                        'status' => 0,
                        'order_id' => $tradeData['order_id'],
                        'tradeid' => $tradeData['id']
                    ));
                }
            }

            if ($userid == $tradeData['trade_id']) {
                if ($tradeData['type'] == 1){
                    M('UserOperation')->add(array(
                        'userid' => $userid,
                        'trade_id' => $tradeData['userid'],
                        'addtime' => time(),
                        'status' => 0,
                        'order_id' => $tradeData['order_id'],
                        'tradeid' => $tradeData['id']
                    ));
                }

                if ($tradeData['type'] ==2) {
                    M('UserOperation')->add(array(
                        'userid' => $userid,
                        'trade_id' => $tradeData['userid'],
                        'addtime' => time(),
                        'status' => 0,
                        'order_id' => $tradeData['order_id'],
                        'tradeid' => $tradeData['id']
                    ));
                }

            }
            $this->ajaxSuccess('取消申诉成功');
        }else{
            $this->ajaxError('取消申诉失败');
        }
    }
}