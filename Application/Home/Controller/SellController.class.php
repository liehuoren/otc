<?php

namespace Home\Controller;

class SellController extends HomeController
{
    //统计分页总数
    public function selltotal(){
        //查询vip用户广告数
        $vip = M()->table('trade_adver as a ')
            ->field('a.userid,a.id,a.price,a.pay_type,a.min_price,a.max_limit,a.min_limit,b.username,b.headimg,b.is_vip,c.trade_num,c.user_praise,c.user_trust')
            ->join('left join trade_user as b on a.userid = b.id left join trade_user_credit as c on a.userid = c.userid')
            ->where('a.trade_type = 2 and a.status =1 and b.is_vip =1')->select();

        //查询普通用户广告数量
        $ordinary = M()->table('trade_adver as a ')
            ->field('a.userid,a.id,a.price,a.pay_type,a.min_price,a.max_limit,a.min_limit,b.username,b.headimg,b.is_vip,c.trade_num,c.user_praise,c.user_trust')
            ->join('left join trade_user as b on a.userid = b.id left join trade_user_credit as c on a.userid = c.userid')
            ->where('a.trade_type = 2 and a.status =1 and b.is_vip =0')->select();

        $sellData['vip']=count($vip);
        $sellData['ordinary']=count($ordinary);

        $this->ajaxReturn($sellData,'JSON');
    }

    //卖单显示
    public function index($page=1,$coin_id, $country = NULL , $money_type = NULL , $pay_type = NULL , $username= NULL)
    {
        $coin_name =M('Coin')->where('id ='.$coin_id)->getField('name');

            $whereVip=array(
                'a.coin_type' => $coin_name,
                'a.trade_type' => 2,
                'a.status' => 1,
                'b.is_vip' => 1
            );
            $whereOrdinary = array(
                'a.coin_type' => $coin_name,
                'a.trade_type' => 2,
                'a.status' => 1,
                'b.is_vip' => 0
            );



        //查询vip用户卖单
        $vipsellData = M()->table('trade_adver as a ')
            ->field('a.userid,a.id,a.price,a.pay_type,a.min_price,a.max_limit,a.min_limit,a.coin_type,b.username,a.num,b.headimg,c.trade_num')
            ->join('left join trade_user as b on a.userid = b.id left join trade_user_credit as c on a.userid = c.userid')
            ->where(
//                array('a.trade_type' => 2,'a.status' => 1,'b.is_vip' => 1 )
                $whereVip
            )->select();

        foreach ($vipsellData as $k => $v) {
            $vipsellData[$k]['price'] = $v['price'] + 0;
            $vipsellData[$k]['limit'] = ($v['min_limit'] + 0) . "-" . ($v['max_limit'] + 0);
            $vipsellData[$k]['pay_type']=explode(',',$v['pay_type']);
        }

        $i = 0;
        foreach ($vipsellData as $k => $v) {
            if ($k % 4 == 0) {
                $i++;
            }
            $vipData[$i-1][] = $v;
        }
        $data['vip']=$vipData;

        //查询普通用户卖单
        $ordinaryData = M()->table('trade_adver as a ')
            ->field('a.userid,a.id,a.price,a.pay_type,a.min_price,a.max_limit,a.min_limit,b.username,a.num,b.headimg,c.trade_num,c.user_praise,c.user_trust')
            ->join('left join trade_user as b on a.userid = b.id left join trade_user_credit as c on a.userid = c.userid')
            ->where(
                $whereOrdinary
//                array('a.trade_type'=>2,'a.status'=>1,'b.is_vip'=>0 )

            )->limit(($page-1)*15,15)->select();
        $total = M()->table('trade_adver as a ')
            ->field('a.userid,a.id,a.price,a.pay_type,a.min_price,a.max_limit,a.min_limit,b.username,a.num,b.headimg,c.trade_num,c.user_praise,c.user_trust')
            ->join('left join trade_user as b on a.userid = b.id left join trade_user_credit as c on a.userid = c.userid')
            ->where(
//                array('a.trade_type'=>2,'a.status'=>1,'b.is_vip'=>0 )
            $whereOrdinary
            )->limit(($page-1)*15,15)->select();
        foreach ($ordinaryData as $k => $v) {
            $ordinaryData[$k]['price'] = $v['price'] + 0;
            $ordinaryData[$k]['limit'] = ($v['min_limit'] + 0) . "-" . ($v['max_limit'] + 0);
            $ordinaryData[$k]['pay_type']=explode(',',$v['pay_type']);
        }
        $data['coin_name']=$coin_name;
        $data['ordinary']=$ordinaryData;
        $data['count'] = count($total);
        $this->ajaxReturn($data, 'JSON');
    }

    public function sell_index($id ,$userid ,$token)
    {
        //TODO 校验是否自买自卖  已修改
        $this->checkLog($userid, $token);

        $trade_log = M('Trade')->where('order_status != 0 and order_status != 3 and order_status !=4 and userid = ' .$userid)->find();

        if ($trade_log){
            $this->ajaxError('请先处理您正在交易的订单');
        }

        $adverData = M('Adver')->where('status = 1 and id= '.$id)->getField('userid');

        if ($adverData == $userid){
            $this->ajaxError('此广告是您自己发布的，不能进行交易');
        }

        $user = M('User')->where('id = ' . $userid)->find();

        if ($user['sm_is_ok'] !=2) {
            $data = array(
                'type' => 0,
                'code' => 4,
                'msg' => '请先完成实名认证'
            );
            $this->ajaxReturn($data);
        }

        $tradeData = M()->table('trade_adver as a ')
            ->field('a.num,a.price,a.pay_type,a.min_limit,a.max_limit,a.coin_type,b.moble,b.email_is_ok,b.sm_is_ok,b.id,b.username,b.headimg,c.trade_num,c.user_praise,c.user_trust')
            ->join('left join trade_user as b on a.userid = b.id left join trade_user_credit as c on a.userid = c.userid')
            ->where('a.id=' . $id)->find();
        if ($tradeData['moble']){
            $tradeData['moble_type'] = 1 ;
        }else{
            $tradeData['moble_type'] = 0 ;
        }

        $tradeBtcTotal = M('Trade')->field("sum(deal) as 'deal'")->where('order_status >=3 and (userid = ' . $userid . ' or trade_id = ' .$userid .')')->select();
        $tradePriceTotal = M('Trade')->field("sum(price) as 'price'")->where('order_status >=3 and (userid = ' . $userid . ' or trade_id = ' .$userid .')')->select();
        $tradeData['historyprice'] = $tradePriceTotal[0]['price'];
        $tradeData['historydeal'] = $tradeBtcTotal[0]['deal'];

        $data = array(
            'adverData' => $tradeData,
            'type' => 1,
        );
        $data['adverData']['price']=$data['adverData']['price'] + 0 ;
        $data['adverData']['min_limit']=$data['adverData']['min_limit'] + 0 ;
        $data['adverData']['max_limit']=$data['adverData']['max_limit'] + 0 ;


        $this->ajaxReturn($data, 'JSON');
    }

    public function sell_submit($id , $price , $num ,$userid ,$token )
    {
        $this->checkLog($userid, $token);

        //round 保留小数点后8位
        $price=round($price,2);
        $num=round($num,8);

        $trade_log = M('Trade')->where('order_status != 0 and order_status != 3 and  order_status !=4 and userid = ' .$userid)->find();

        if ($trade_log){
            $this->ajaxError('请先处理您正在交易的订单');
        }

        if ($price == '') {
            $this->ajaxError('请输入卖出的金额');
        }

        if ($num == '') {
            $this->ajaxError('请输入卖出的数量');
        }

        $tradeData = M('Adver')->where("id=" . $id)->find();

        $user = M('User')->where(array(
            'id' => $userid
        ))->getField('sm_is_ok');

        if ($user != 2) {
            $data = array(
                'type' => 0,
                'code' => 4,
                'msg' => '请先完成实名认证'
            );
            $this->ajaxReturn($data);
        }

        if ($tradeData['userid'] == $userid){
            $this->ajaxError('此广告是您发布的，不能进行交易');
        }

        if ($price < $tradeData['min_limit'] || $price > $tradeData['max_limit']) {
            $this->ajaxError("您的价格不在区间范围内");
        }

        $userCoin = M('UserCoin')->where(array(
            'userid' => $userid
        ))->find();

        if ($userCoin[$tradeData['coin_type']] < $num) {
            $this->ajaxError("您的账号下所持有的虚拟币不足");
        }

        $m = M();
        $m->execute('set autocommit = 0');
        $m->execute('lock tables trade_user_coin write , trade_trade write , trade_user_operation write');

        $rs[] = $m->table('trade_user_coin')->where(array(
            'userid' => $userid
        ))->setDec($tradeData['coin_type'], $num);

        $rs[] = $m->table('trade_user_coin')->where(array(
            'userid' => $userid
        ))->setInc($tradeData['coin_type'] . "d" , $num);

        //TODO 订单号生成逻辑，建议时间戳+数据表对应ID方式拼接 已修改
        $order_id = substr(time(),'-4') . $id;

        $paycode = substr(time() , '-4') . rand(100,999);

        $rs[] = $m->table('trade_trade')->add(array(
            'adver_id' => $id,
            'userid' => $userid,
            'trade_id' => $tradeData['userid'],
            'price' => $price,
            'num' => $num,
            'addtime' => time(),
            'order_status' => 1,
            'status' => 1,
            'type' => 2,
            'money_type' => $tradeData['money_type'],
            'order_id' => $order_id,
            'coin_type' => $tradeData['coin_type'],
            'paycode' => $paycode,
            'pay_type' => $tradeData['pay_type'],
            'adver_code' => $tradeData['adver_code'],
            'mum' => $tradeData['price'],
        ));

        $rs[] = $m->table('trade_user_operation')->add(array(
            'userid' => $userid,
            'trade_id' => $tradeData['userid'],
            'status' => 0,
            'addtime' => time(),
            'order_id' => $order_id,
            'tradeid' => $rs[2]
        ));

        if (check_arr($rs)) {
            $m->execute('commit');
            $m->execute('unlock tables');
            $arr = array(
                'type' => 1,
                'order_id' => $rs[2],
                'msg' => '下单OK'
            );
            $email=M('user')->where('id='.$tradeData['userid'])->find();
            $content = '尊敬的T-Bees用户您好,<br/>您有一笔买入 '.$num.' '.$tradeData['coin_type'].' 的订单'.$order_id.'尚未支付，请尽快完成支付并在网页点击“付款已完成”，否则订单将在30分钟后因超时被取消。如果您已经完成了支付，请在订单页面点击“付款已完成”，否则将无法收到对方支付的数字资产。';
            $result = sendMail($email['email'] ,'',$content , C('email') , C('emailpassword'));

            if ($result){
                $arr['mail_status']='1';//邮件下发ok
            }else{
                $arr['mail_status']='0'; //邮件下发failed
            }

            $this->ajaxReturn($arr, 'JSON');
        } else {
            $m->execute('rollback');
            $m->execute('unlock tables');
            $this->ajaxError("下单失败");
        }
    }

}