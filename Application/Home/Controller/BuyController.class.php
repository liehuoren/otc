<?php

namespace Home\Controller;

class BuyController extends HomeController
{
    //统计分页总数
    public function buytotal(){
        $buyData = M()->table('trade_adver as a ')
            ->field('a.userid,a.id,a.price,a.money_type,a.pay_type,a.min_price,a.max_limit,a.min_limit,b.username,b.headimg,c.trade_num,c.user_praise,c.user_trust')
            ->join('left join trade_user as b on a.userid = b.id left join trade_user_credit as c on a.userid = c.userid')
            ->where('a.trade_type = 1 and a.status = 1')->select();
        $this->ajaxReturn(count($buyData),'JSON');
    }

    //卖单显示
    public function index($page=1 ,$coin_id, $country = NULL , $money_type = NULL , $pay_type = NULL , $username = NULL)
    {
        $coin_name =M('Coin')->where('id = '.$coin_id)->getField('name');
        if ($coin_id){

            $whereVip=array(
                'a.coin_type' => $coin_name,
                'a.trade_type' => 1,
                'a.status' => 1,
                'b.is_vip' => 1
            );
            $whereOrdinary = array(
                'a.coin_type' => $coin_name,
                'a.trade_type' => 1,
                'a.status' => 1,
                'b.is_vip' => 0
            );
        }

        $vipBuyData = M()->table('trade_adver as a ')
            ->field('a.userid,a.id,a.price,a.money_type,a.pay_type,a.min_price,a.max_limit,a.min_limit,a.num,a.coin_type,b.username,b.headimg,b.is_vip,c.trade_num,c.user_praise,c.user_trust')
            ->join('left join trade_user as b on a.userid = b.id left join trade_user_credit as c on a.userid = c.userid')
            ->where($whereVip)->select();
//            (array('a.trade_type' => 1,'a.status' => 1,'b.is_vip' => 1)
        foreach ($vipBuyData as $k => $v) {
            $vipBuyData[$k]['price']=$vipBuyData[$k]['price']+'0';
            $vipBuyData[$k]['max_limit']=$vipBuyData[$k]['max_limit']+'0';
            $vipBuyData[$k]['min_limit']=$vipBuyData[$k]['min_limit']+'0';
            $vipBuyData[$k]['limit'] = ($v['min_limit'] + '0') . "-" . ($v['max_limit']+ '0');
            $vipBuyData[$k]['pay_type']=explode(',',$v['pay_type']);
        }

        $i=0;
        foreach ($vipBuyData as $k => $v){
            if ($k % 4 == 0){
                $i++;
            }
            $vipData[$i-1][]=$v;

        }
        $buyData['vip']=$vipData;

        $ordinaryBuyData = M()->table('trade_adver as a ')
            ->field('a.userid,a.id,a.price,a.money_type,a.pay_type,a.min_price,a.max_limit,a.min_limit,a.num,b.username,b.headimg,b.is_vip,c.trade_num,c.user_praise,c.user_trust')
            ->join('left join trade_user as b on a.userid = b.id left join trade_user_credit as c on a.userid = c.userid')
            ->where(
//                array('a.trade_type' => 1,'a.status' => 1,'b.is_vip' => 0)
                $whereOrdinary
            )->limit(($page-1) * 15 , 15)->select();
        $total = M()->table('trade_adver as a ')
            ->field('a.userid,a.id,a.price,a.money_type,a.pay_type,a.min_price,a.max_limit,a.min_limit,a.num,b.username,b.headimg,b.is_vip,c.trade_num,c.user_praise,c.user_trust')
            ->join('left join trade_user as b on a.userid = b.id left join trade_user_credit as c on a.userid = c.userid')
            ->where(
//                array('a.trade_type' => 1,'a.status' => 1,'b.is_vip' => 0)
                $whereOrdinary
            )->select();
        foreach ($ordinaryBuyData as $k => $v) {
            $ordinaryBuyData[$k]['price']=$ordinaryBuyData[$k]['price']+'0';
            $ordinaryBuyData[$k]['max_limit']=$ordinaryBuyData[$k]['max_limit']+'0';
            $ordinaryBuyData[$k]['min_limit']=$ordinaryBuyData[$k]['min_limit']+'0';
            $ordinaryBuyData[$k]['limit'] = ($v['min_limit'] + '0') . "-" . ($v['max_limit'] + '0');
            $ordinaryBuyData[$k]['pay_type']=explode(',',$v['pay_type']);
        }

        $buyData['coin_name']=$coin_name;
        $buyData['ordinary']=$ordinaryBuyData;
        $buyData['count'] = count($total);
        $this->ajaxReturn($buyData, 'JSON');
    }

    //买入详情  id 为  广告的id
    public function buy_index($id ,$userid ,$token )
    {
        $this->checkLog($userid, $token);

        $trade_log = M('Trade')->where('order_status != 0 and order_status != 3 and order_status !=4 and userid = ' .$userid)->select();

        if ($trade_log){
            $this->ajaxError('请先处理您正在交易的订单');
        }

        $adverData = M('Adver')->where('status = 1 and id= '.$id)->getField('userid');

        if(!$adverData){
            $this->ajaxError('没有此广告');
        }

        if ($adverData == $userid){
            $this->ajaxError('此广告是您自己发布的，不能进行交易');
        }

        $user = M('User')->where('id = ' .$userid)->find();

        if ($user['sm_is_ok'] != 2) {
            $data = array(
                'type' => 0,
                'code' => 4,
                'msg' => '请先完成实名认证'
            );
            $this->ajaxReturn($data);
        }

        $adverData = M()->table('trade_adver as a ')
            ->field('a.num,a.full_num,a.price,a.pay_type,a.min_price,a.max_limit,a.min_limit,a.message,a.coin_type,b.moble,b.id,b.email_is_ok,b.sm_is_ok,b.username,b.headimg,c.trade_num,c.user_praise,c.user_trust')
            ->join('left join trade_user as b on a.userid = b.id left join trade_user_credit as c on a.userid = c.userid')
            ->where('a.id=' . $id)->find();

        if ($adverData['moble']){
            $adverData['moble_type']=1;
        }else{
            $adverData['moble_type']=0;
        }
        $tradeBtcTotal = M('Trade')->field("sum(deal) as 'deal'")->where('status =2 and (userid = ' . $userid . ' or trade_id = ' .$userid .')')->select();
        $adverData['historydeal'] = $tradeBtcTotal[0]['deal'];
        $tradePriceTotal = M('Trade')->field("sum(price) as 'price'")->where('status =2 and (userid = ' . $userid . ' or trade_id = ' .$userid .')')->select();
        $adverData['historyprice'] = $tradePriceTotal[0]['price'];
        $data = array(
            'adverData' => $adverData,
            'type' => 1,
        );
        $data['adverData']['price']= $data['adverData']['price'] + 0 ;
        $data['adverData']['max_limit']= $data['adverData']['max_limit'] + 0 ;
        $data['adverData']['min_limit']= $data['adverData']['min_limit'] + 0 ;

        $this->ajaxReturn($data, 'JSON');
    }

    //点击买入  id为广告id  cny 为 订单的买入金额  num为订单买入数量
    public function buy_submit($id, $price, $num, $userid , $token)
    {
        $this->checkLog($userid, $token);

        //round 保留小数点后8位
        $price=round($price,2);
        $num=round($num,8);

        $trade_log = M('Trade')->where('order_status != 0 and order_status != 3 and order_status !=4 and userid = ' .$userid)->select();

        if ($trade_log){
            $this->ajaxError('请先处理您正在交易的订单');
        }

        if ($price == '') {
            $this->ajaxError('请输入买入金额');
        }

        if ($num == '') {
            $this->ajaxError('请输入买入数量');
        }

        $adverData = M('Adver')->where("status = 1 and id=" . $id)->find();

        if (!$adverData){
            $this->ajaxError("您买入的商品已下架");
        }

        $userInfo = M()->table('trade_user_coin as a')->field('a.'. $adverData['coin_type'] . ' , b.sm_is_ok')
            ->join('left join trade_user as b on a.userid = b.id')
            ->where(array(
            'userid' => $adverData['userid']
        ))->find();
//        dump(M()->getLastSql());die;
        $user = M('User')->where(array(
            'id' => $userid
        ))->find();

        if ($user['sm_is_ok'] !=2){
            $data = array(
                'type' => 0,
                'code' => 4,
                'msg' => '请先完成实名认证'
            );
            $this->ajaxReturn($data);
        }

        //TODO 校验是否自买自卖  已修改
        if ($adverData['userid'] == $userid){
            $this->ajaxError('此广告是您发布的，不能进行交易');
        }


        //手续费

        $fee = M('Fee')->where(array(
            'coinname' => $adverData['coin_type']
        ))->getField('fee');

        //round 保留小数点后8位
        $feenum = round($num * $fee,8);

        $truenum =round(($num + $num * $fee),8) ;

        if ($truenum > $userInfo[$adverData['coin_type']]) {
            $this->ajaxError('卖家没有足够的资金进行交易');
        }

        if ($price < $adverData['min_limit'] || $price > $adverData['max_limit']) {
            $this->ajaxError("您的价格不在区间范围内");
        }

        if (! check($price, 'double')) {
            $this->ajaxError('交易价格格式错误');
        }

        if (! check($num, 'double')) {
            $this->ajaxError('交易数量格式错误');
        }

        $order_id = substr(time(),'-4') . $id;

        $paycode = substr(time() , '-4') . rand(100,999);



        $m = M();
        $m->execute('set autocommit = 0');
        $m->execute('lock tables trade_user_coin write ,trade_trade write , trade_user_operation write');

        $rs[] = $m->table('trade_user_coin')->where(array(
            'userid' => $adverData['userid']
        ))->setInc($adverData['coin_type'] .'d' ,$truenum);

        $rs[] = $m->table('trade_user_coin')->where(array(
            'userid' => $adverData['userid']
        ))->setDec($adverData['coin_type'] , $truenum);

        //TODO 订单号生成逻辑，建议时间戳+数据表对应ID方式拼接  已修改

        $rs[] = $m->table('trade_trade')->add(array(
            'adver_id' => $id,
            'userid' => $userid,
            'trade_id' => $adverData['userid'],
            'price' => $price,
            'num' => $num,
            'money_type' => $adverData['money_type'],
            'addtime' => time(),
            'status' => 1,
            'order_status' => 1,
            'type' => 1,
            'order_id' => $order_id,
            'pay_type' => $adverData['pay_type'],
            'fee' => $feenum,
            'coin_type' => $adverData['coin_type'],
            'paycode' => $paycode,
            'adver_code' => $adverData['adver_code'],
            'mum' => $adverData['price']
        ));

        $rs[] = $m->table('trade_user_operation')->add(array(
            'userid' => $userid,
            'trade_id' => $adverData['userid'],
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

            //向挂单方发送通知邮件

            $email=M('user')->where('id='.$userid)->find();
            $content = '尊敬的SPEEDOTC用户您好,<br/>您有一笔买入 '.$num.' '.$adverData['coin_type'].' 的订单 '.$order_id.' 尚未支付，请尽快完成支付并在网页点击“付款已完成”，否则订单将在30分钟后因超时被取消。如果您已经完成了支付，请在订单页面点击“付款已完成”，否则将无法收到对方支付的数字资产。';
            $result = sendMail($email['email'] ,'订单提醒',$content , C('email') , C('emailpassword'));

            if ($result){
                $arr['mail_status']='1';//邮件下发ok
            }else{
                $arr['mail_status']='0'; //邮件下发failed
            }

            $this->ajaxReturn($arr, 'JSON');
        } else {
            $m->execute('rollback');
            $m->execute('unlock tables');
            $this->ajaxError('下单失败');
        }
    }

}