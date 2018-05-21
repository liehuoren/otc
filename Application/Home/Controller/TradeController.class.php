<?php

namespace Home\Controller;

class TradeController extends HomeController
{
    public function index($id  ,$userid  , $token )
    {

        $this->checkLog($userid, $token);

        $tradeData = M('Trade')->where(array(
            'id' => $id
        ))->find();

        if (!$tradeData) {
            $this->ajaxError('订单不存在');
        }

        if ($userid != $tradeData['userid'] && $userid != $tradeData['trade_id']) {
            $this->ajaxError('此订单不是您的订单');
        }
        // 1是发起订单的用户    2是发布广告的用户

        if ($tradeData['type'] == 1) {
            if ($userid == $tradeData['userid']) {
                $userData = M()->table('trade_trade as a')
                    ->field('a.mum,a.addtime as starttime,a.paycode,a.order_status,a.id,a.userid,a.trade_id,a.order_id,a.status,a.price,a.num,a.pay_type,a.coin_type,b.personalnote,b.addtime,b.username,b.headimg,b.sm_is_ok,b.email_is_ok,c.first_tradetime,c.trade_num,c.user_praise,c.user_trust,d.message')
                    ->join('left join trade_user as b on a.trade_id = b.id left join trade_user_credit as c on a.trade_id = c.userid left join trade_adver as d on a.adver_id = d.id')
                    ->where("a.id=" . $id)->find();
                $userData['price']=$userData['price']+'0';
                $userData['num']=$userData['num']+'0';
                $userData['symbol'] = 1;
            }

            if ($userid == $tradeData['trade_id']) {
                $userData = M()->table('trade_trade as a')
                    ->field('a.mum,a.addtime as starttime,a.paycode,a.order_status,a.id,a.userid,a.trade_id,a.order_id,a.status,a.price,a.num,a.pay_type,a.coin_type,b.personalnote,b.addtime,b.username,b.headimg,b.sm_is_ok,b.email_is_ok,c.first_tradetime,c.trade_num,c.user_praise,c.user_trust,d.message')
                    ->join('left join trade_user as b on a.userid = b.id left join trade_user_credit as c on a.userid = c.userid left join trade_adver as d on a.adver_id = d.id')
                    ->where("a.id=" . $id)->find();
                $userData['price']=$userData['price']+'0';
                $userData['num']=$userData['num']+'0';
                $userData['symbol'] = 2;
            }
        }

        if ($tradeData['type'] == 2) {
            if ($userid == $tradeData['userid']) {
                $userData = M()->table('trade_trade as a')
                    ->field('a.mum,a.addtime as starttime,a.paycode,a.order_status,a.id,a.userid,a.trade_id,a.order_id,a.status,a.price,a.num,a.pay_type,a.coin_type,b.personalnote,b.addtime,b.username,b.headimg,b.sm_is_ok,b.email_is_ok,c.first_tradetime,c.trade_num,c.user_praise,c.user_trust,d.message')
                    ->join('left join trade_user as b on a.trade_id = b.id left join trade_user_credit as c on a.trade_id = c.userid left join trade_adver as d on a.adver_id = d.id')
                    ->where("a.id=" . $id)->find();

                $userData['price']=$userData['price']+'0';
                $userData['num']=$userData['num']+'0';
                $userData['symbol'] = 2;
            }

            if ($userid == $tradeData['trade_id']) {
                $userData = M()->table('trade_trade as a')
                    ->field('a.mum,a.addtime as starttime,a.paycode,a.order_status,a.id,a.userid,a.trade_id,a.order_id,a.status,a.price,a.num,a.pay_type,b.personalnote,b.addtime,b.username,b.headimg,b.sm_is_ok,b.email_is_ok,c.first_tradetime,c.trade_num,c.user_praise,c.user_trust,d.message')
                    ->join('left join trade_user as b on a.userid = b.id left join trade_user_credit as c on a.userid = c.userid left join trade_adver as d on a.adver_id = d.id')
                    ->where("a.id=" . $id)->find();

                $userData['price']=$userData['price']+'0';
                $userData['num']=$userData['num']+'0';
                $userData['symbol'] = 1;
            }
        }
        $userData['pay_type'] = explode(',' , $userData['pay_type']);
        $userData['endtime'] = $userData['starttime'] - 1800;
        $userData['starttime'] = $userData['starttime'] - (time() - $userData['starttime']);

        $this->ajaxReturn($userData, 'JSON');
    }

    //待收货
    public function waitRecive($id)
    {
        $tradePrice = M('Trade')->where('id =' . $id)->getField("price");
        $this->ajaxReturn($tradePrice, 'JSON');
    }


    //评价  usertype  1代码买家 2代码卖家
    public function evaluate($id , $user_praise , $userid , $token)
    {
        $this->checkLog($userid, $token);
        $tradeData = M('Trade')->where(array(
            'id' => $id
        ))->find();

        if ($tradeData['order_status'] == 1 || $tradeData['order_status'] == 2) {
            $this->ajaxError('订单还没付款不能评价');
        }

        if ($tradeData['order_status'] == 3) {
            $this->ajaxReturn('还没确认收到比特币，不能评价');
        }

        if ($tradeData['order_status'] == 5) {
            $this->ajaxError('不能重复评价');
        }


        //买家评价卖家
        if ($userid == $tradeData['userid']) {

            if ($tradeData['evaluatseller'] == 1) {
                $this->ajaxError('不能重复评价');
            }

            $creditData = M('UserCredit')->where('userid =' . $tradeData['trade_id'])->find();

            $trade_num = $creditData['trade_num'] + 1;

            $user_praise = ($creditData['user_praise'] + $user_praise * 20) / $creditData['praise_num'];

            $rs = M('UserCredit')->where(array(
                'userid' => $tradeData['trade_id']
            ))->save(array(
                'trade_num' => $trade_num,
                'user_praise' => $user_praise
            ));

            M('Trade')->where('id =' . $id)->setField("evaluatseller", 1);
        }
        if ($userid == $tradeData['trade_id']) {

            if ($tradeData['evaluatbuyer'] == 1) {
                $this->ajaxError('不能重复评价');
            }

            //评论买家
            $buyerId = M('Trade')->where(array(
                'id' => $id
            ))->getField("userid");

            $creditData = M('UserCredit')->where('userid =' . $buyerId)->find();

            $trade_num = $creditData['trade_num'] + 1;

            $praise = $creditData['praise_num'] +1;

            $user_praise = ($creditData['user_praise'] + $user_praise * 20) / $creditData['praise_num'];

            $rs = M('UserCredit')->where(array(
                'userid' => $buyerId
            ))->save(array(
                'trade_num' => $trade_num,
                'user_praise' => $user_praise,
                'praise' => $praise
            ));
            M('Trade')->where('id =' . $id)->setField("evaluatbuyer", 1);
        }

        if ($rs) {
            $this->ajaxSuccess('评论成功');
        } else {
            $this->ajaxError('评论失败');
        }
    }

    //标记已付款  id是订单id
    public function pay($id, $userid, $token ,$paypassword = NULL)
    {
        $this->checkLog($userid, $token);
        //TODO 增加订单状态where  已修改
        $tradeData = M('Trade')->where('id=' . $id)->find();

        if ($userid != $tradeData['userid'] && $userid != $tradeData['trade_id']) {
            $this->ajaxError('此订单不是您的订单');
        }

        if ($tradeData['order_status'] == 0) {
            $this->ajaxError('交易已经关闭');
        }

        if ($tradeData['order_status'] == 3) {
            $this->ajaxError('已经支付成功的订单');
        }

        if ($tradeData['deal'] > $tradeData['num']) {
            $this->ajaxError('已经成交,不能在支付');
        }

        //操作交易用户
        if ($userid == $tradeData['userid']) {
            if ($tradeData['type'] == 2) {
                $rss = M('UserCredit')->where(array(
                    'userid' => $userid
                ))->setInc('trade_num' ,1);
                $res = M('UserCredit')->where(array(
                    'userid' => $tradeData['trade_id']
                ))->setInc('trade_num' ,1);
                $first_trade = M('UserCredit')->where(array(
                    'userid' => $userid
                ))->getField('first_tradetime');

                if (!$first_trade) {
                    M('UserCredit')->where(array(
                        'userid' => $userid
                    ))->save(array(
                        'first_tradetime' => time()
                    ));
                }
                $first_trade_user = M('UserCredit')->where(array(
                    'userid' => $tradeData['trade_id']
                ))->getField('first_tradetime');
                if (!$first_trade_user) {
                    M('UserCredit')->where(array(
                        'userid' => $tradeData['trade_id']
                    ))->save(array(
                        'first_tradetime' => time()
                    ));
                }
            }
        }
        //挂单用户
        if ($userid == $tradeData['trade_id']) {
            if ($tradeData['type'] == 1) {
                $rss = M('UserCredit')->where(array(
                    'userid' => $userid
                ))->setInc('trade_num' ,1);
                $res = M('UserCredit')->where(array(
                    'userid' => $tradeData['userid']
                ))->setInc('trade_num' ,1);
                $first_trade = M('UserCredit')->where(array(
                    'userid' => $userid
                ))->getField('first_tradetime');

                if (!$first_trade) {
                    M('UserCredit')->where(array(
                        'userid' => $userid
                    ))->save(array(
                        'first_tradetime' => time()
                    ));
                }
                $first_trade_user = M('UserCredit')->where(array(
                    'userid' => $tradeData['userid']
                ))->getField('first_tradetime');

                if (!$first_trade_user) {
                    M('UserCredit')->where(array(
                        'userid' => $tradeData['userid']
                    ))->save(array(
                        'first_tradetime' => time()
                    ));
                }
            }
        }

        $fee = M('Fee')->where(array(
            'coinname' => $tradeData['coin_type']
        ))->getField('fee');

        //round 保留小数点后8位
        $feenum = round($tradeData['num'] * $fee , 8);

        $trueSellNum = $tradeData['num'] - $feenum;

        if ($userid == $tradeData['trade_id']) {
            if ($tradeData['type'] == 1){
                if ($paypassword == NULL){
                    $this->ajaxError('交易密码不能为空');
                }
                $userpaypassword = M('User')->where(array(
                    'id' => $userid
                ))->getField('paypassword');
                if (md5($paypassword) != $userpaypassword) {
                    $this->ajaxError('交易密码错误');
                }
            }
        }

        if ($userid == $tradeData['userid']) {
            if ($tradeData['type'] == 2){
                if ($paypassword == NULL){
                    $this->ajaxError('交易密码不能为空');
                }
                $userpaypassword = M('User')->where(array(
                    'id' => $userid
                ))->getField('paypassword');
                if (md5($paypassword) != $userpaypassword) {
                    $this->ajaxError('交易密码错误');
                }
            }
        }

        $m = M();
        $m->execute('set autocommit = 0');
        $m->execute('lock tables trade_trade write , trade_user_coin write , trade_adver write , trade_user_operation write');


        //操作交易用户
        if ($userid == $tradeData['userid']) {
            //卖单
            if ($tradeData['type'] == 1) {

                if ($tradeData['order_status'] != 1) {
                    $this->ajaxError('订单状态错误');
                }
                $rs[] = $m->table('trade_trade')->where('id=' . $id)->setField('order_status', 2);
            }

            if ($tradeData['type'] == 2) {

                if ($m->table('trade_trade')->where('id =' . $id)->getField('order_status') != 2) {
                    $this->ajaxError('对方还没标记付款');
                }

                $rs[] = $m->table('trade_user_coin')->where(array(
                    'userid' => $tradeData['userid']
                ))->setDec($tradeData['coin_type'] . 'd', $tradeData['num']);

                $rs[] = $m->table('trade_user_coin')->where(array(
                    'userid' => $tradeData['trade_id']
                    //round 保留小数点后8位
                ))->setInc($tradeData['coin_type'], $trueSellNum);


                $rs[] = $m->table('trade_trade')->where('id=' . $id)->setInc('deal', $tradeData['num']);

                $rs[] = $m->table('trade_trade')->where('id =' . $id)->save(array(
                    'order_status' => 3,
                    'status' => 2
                ));

                $rs[] = $m->table('trade_trade')->where('id=' .$id)->setInc('fee' , $feenum);
//                dump($m->getLastSql());die;
            }
            //买单

            if ($tradeData['type'] == 1) {
                $rs[] = $m->table('trade_user_operation')->add(array(
                    'userid' => $userid,
                    'trade_id' => $tradeData['trade_id'],
                    'addtime' => time(),
                    'status' => 0,
                    'order_id' => $tradeData['order_id'],
                    'tradeid' => $tradeData['id']
                ));
            }

            if ($tradeData['type'] == 2) {
                $rs[] = $m->table('trade_user_operation')->add(array(
                    'userid' => $userid,
                    'trade_id' => $tradeData['trade_id'],
                    'addtime' => time(),
                    'status' => 0,
                    'order_id' => $tradeData['order_id'],
                    'tradeid' => $tradeData['id']
                ));
            }

            if (check_arr($rs)) {
                $m->execute('commit');
                $m->execute('unlock tables');
                $arr = array(
                    'type' => 1,
                    'id' => $id,
                    'msg' => '付款OK'
                );

                if ($userid == $tradeData['userid']){
                    if ($tradeData['type'] == 1){
                        $email=M('user')->where('id='.$tradeData['trade_id'])->find();
                        $content ='尊敬的SPEEDOTC用户您好,<br/>用户买家将订单 '.$tradeData['order_id'].' 标记为“已付款”状态，请及时登录收款账户查看！长时间不处理订单将对您在本平台的信誉造成影响。
                        如果您没有收到对方付款，可以尽快联系对方或者向管理员申诉！';
                        $result = sendMail($email['email'] ,'',$content , C('email') , C('emailpassword'));
                    }
                    if ($tradeData['type'] == 2){
                        $email=M('user')->where('id='.$tradeData['trade_id'])->find();
                        $content = '尊敬的SPEEDOTC用户您好,<br/>在您的订单'.$tradeData['order_id'].'中'.'，卖家已放行 '.$tradeData['num'].' '.$tradeData['coin_type'].' 。如果您对该订单处理有疑问，请联系客服。';
                        $result = sendMail($email['email'] ,'',$content , C('email') , C('emailpassword'));
                    }
                }

                if ($result){
                    $arr['mail_status']='11111';//邮件下发ok
                }else{
                    $arr['mail_status']='22222'; //邮件下发failed
                }

                $this->ajaxReturn($arr, 'JSON');
            } else {
                $m->execute('rollback');
                $m->execute('unlock tables');
                $this->ajaxError("付款失败");
            }
        }

        //挂单用户
        if ($userid == $tradeData['trade_id']) {
            //卖单
            if ($tradeData['type'] == 1) {

                if ($m->table('trade_trade')->where('id =' . $id)->getField('order_status') != 2) {
                    $this->ajaxError('对方还没标记付款');
                }
                //扣除手续费
                $rs[] = $m->table('trade_user_coin')->where(array(
                    'userid' => $tradeData['trade_id']
                ))->setDec($tradeData['coin_type'] . 'd', $tradeData['num'] + $tradeData['fee']);

                $rs[] = $m->table('trade_user_coin')->where(array(
                    'userid' => $tradeData['userid']
                ))->setInc($tradeData['coin_type'], $tradeData['num']);

                $rs[] = $m->table('trade_trade')->where('id=' . $id)->setInc('deal', $tradeData['num']);

                $rs[] = $m->table('trade_trade')->where('id=' . $id)->save(array(
                    'order_status' => 3,
                    'status' => 2
                ));

                //交易成功减掉广告剩余量
            }

            //买单
            if ($tradeData['type'] == 2) {
                //TODO 校验状态  已修改
                if ($m->table('trade_trade')->where('id =' . $id)->getField('order_status') != 1) {
                    $this->ajaxError('订单状态有错误');
                }
                $rs[] = $m->table('trade_trade')->where('id=' . $id)->setField('order_status', 2);
            }

            if ($tradeData['type'] == 1) {
                $rs[] = $m->table('trade_user_operation')->add(array(
                    'userid' => $userid,
                    'trade_id' => $tradeData['userid'],
                    'addtime' => time(),
                    'status' => 0,
                    'order_id' => $tradeData['order_id'],
                    'tradeid' => $tradeData['id']
                ));
            }

            if ($tradeData['type'] == 2) {
                $rs[] = $m->table('trade_user_operation')->add(array(
                    'userid' => $userid,
                    'trade_id' => $tradeData['userid'],
                    'addtime' => time(),
                    'status' => 0,
                    'order_id' => $tradeData['order_id'],
                    'tradeid' => $tradeData['id']
                ));
            }
            if (check_arr($rs)) {
                $m->execute('commit');
                $m->execute('unlock tables');
                $arr = array(
                    'type' => 1,
                    'id' => $id,
                    'msg' => '收款OK'
                );
                if ($userid == $tradeData['trade_id']){
                    if ($tradeData['type'] == 1){
                        $email=M('user')->where('id='.$tradeData['userid'])->find();
                        $content = '尊敬的SPEEDOTC用户您好,<br/>在您的订单'.$tradeData['order_id'].'中'.'，卖家已放行 '.$tradeData['num'].' '.$tradeData['coin_type'].' 。如果您对该订单处理有疑问，请联系客服。';
                        $result = sendMail($email['email'] ,'',$content , C('email') , C('emailpassword'));
                    }
                    if ($tradeData['type'] == 2){
                        $email=M('user')->where('id='.$tradeData['userid'])->find();
                        $content = '尊敬的SPEEDOTC用户您好,<br/>在您的订单'.$tradeData['order_id'].'中'.'，标记为“已付款”状态，请及时登录收款账户查看！长时间不处理订单将对您在本平台的信誉造成影响。如果您没有收到对方付款，可以尽快联系对方或者向管理员申诉！';

                        $result = sendMail($email['email'] ,'',$content , C('email') , C('emailpassword'));
                    }
                }
                //发放佣金
                $arr['reward']=$this->rewardfee($id);
                if (!$arr['reward'])
                {
                    $this->ajaxError("佣金发放失败");
                }

                if ($result){
                    $arr['mail_status']='33333';//邮件下发ok
                }else{
                    $arr['mail_status']='44444'; //邮件下发failed
                }
                $this->ajaxReturn($arr, 'JSON');
            } else {
                $m->execute('rollback');
                $m->execute('unlock tables');
                $this->ajaxError("收款失败");
            }
        }
    }

    //关闭交易 id是订单的id
    public function closeTrade($id, $userid, $token)
    {
        //TODO 校验状态 已修改
        $tradeData = M('Trade')->where('id=' . $id)->find();

        $this->checkLog($userid, $token);

        if ($userid != $tradeData['userid'] && $userid != $tradeData['trade_id']) {
            $this->ajaxError('此订单不是您的订单');
        }

        if ($tradeData['type'] == 1) {
            if ($userid == $tradeData['trade_id']) {
                $this->ajaxError('卖家不能操作取消交易');
            }
        }

        if ($tradeData['type'] == 2) {
            if ($userid == $tradeData['userid']) {
                $this->ajaxError('卖家不能操作取消交易');
            }
        }

        if ($tradeData['order_status'] ==3) {
            $this->ajaxError('订单已经完成，不能关闭交易');
        }


        if ($tradeData['order_status'] == 0) {
            $this->ajaxError('您的交易已经关闭');
        }

        $appeal =M('Appeal')->where(array(
            'trade_id' =>$id,
            'status' => 1
        ))->find();

        $m = M();
        $m->execute('set autocommit = 0');
        $m->execute('lock tables trade_trade write , trade_user_coin write , trade_user_operation write , trade_appeal write');

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

        if ($tradeData['type'] == 1) {
            $rs[] = $m->table('trade_user_operation')->add(array(
                'userid' => $userid,
                'trade_id' => $tradeData['trade_id'],
                'status' => 0,
                'addtime' => time(),
                'order_id' => $tradeData['order_id'],
                'tradeid' => $tradeData['id']
            ));
        }

        if ($tradeData['type'] ==2) {
            $rs[] = $m->table('trade_user_operation')->add(array(
                'userid' => $userid,
                'trade_id' => $tradeData['userid'],
                'status' => 0,
                'addtime' => time(),
                'order_id' => $tradeData['order_id'],
                'tradeid' => $tradeData['id']
            ));
        }

        if ($appeal){
            $rs=$m->table('trade_appeal')->where(array(
                'trade_id' =>$id
            ))->setField('status',0);
        }

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

    public function rewardfee($tradeid = 90)
    {
        $res = null;
        if (!$tradeid) {
            return $res;
        }

        $trade = M('Trade')->where(array(
            'id' => $tradeid
        ))->find();
        if (!$trade )
        {
            return $res;
        }
        //判断订单是否已发放佣金

        $reward = M('InvitReward')->where(array(
            'tradeid' => $tradeid
        ))->find();

        if ($reward)
        {
            return $res;
        }

        if ($trade['status'] != 2)
        {
            return $res;
        }

        $user = M('User')->where(array(
            'id' => $trade['trade_id']
        ))->find();


        if (!$user['invit_1'] && !$user['invit_2']) {
            return $res;
        }
        if ($user['invit_1'])
        {
            $invit1uid =M('User')->where(array(
                'invit' => $user['invit_1']
            ))->find();
        }

        if ($user['invit_2'])
        {
            $invit2uid =M('User')->where(array(
                'invit' => $user['invit_2']
            ))->find();
        }

        $fee = M('InvitFee')->find();
        if ($invit1uid)
        {
//            $reward1fee = round( $trade['fee'] * $fee['invit1']/100 , 8);
            $reward1fee = floor( $trade['fee'] * $fee['invit1']/100 *100000000)/100000000;
        }
        if ($invit2uid)
        {
//            $reward2fee = round( $trade['fee'] * $fee['invit2']/100 , 8);
            $reward2fee = floor( $trade['fee'] * $fee['invit2']/100 *100000000)/100000000;
        }

        $m=M();
        $m->execute('set autocommit = 0');
        $m->execute('lock tables trade_invit_reward write,  trade_user_coin write');

        if ($invit1uid['id'])
        {
            $rs[]=$m->table('trade_user_coin')->where(array(
                'userid' => $invit1uid['id']
            ))->setInc($trade['coin_type'],$reward1fee);

        }

        if ($invit2uid['id'])
        {
            $rs[]=$m->table('trade_user_coin')->where(array(
                'userid' => $invit2uid['id']
            ))->setInc($trade['coin_type'],$reward2fee);
        }




        $rs[] =$m->table('trade_invit_reward')->add(array(
            'trade_user' => $trade['trade_id'],
            'tradeid'=>$trade['id'] ,
            'invit1_id' =>$invit1uid['id'],
            'invit1_fee' => $reward1fee,
            'invit2_id' =>$invit2uid['id'],
            'invit2_fee'=>$reward2fee,
            'coin_type'=>$trade['coin_type'],
            'trade_time'=>$trade['addtime']
        ));

        if (check_arr($rs)){
            $m->execute('commit');
            $m->execute('unlock tables');
        }else{
            $m->execute('rollback');
            $m->execute('unlock tables');
        }
        return $rs;
    }
}



