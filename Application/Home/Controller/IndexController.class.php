<?php

namespace Home\Controller;

class IndexController extends HomeController
{
    //首页
    public function index()
    {
        $usernum = count(M('User')->field('id')->where(array(
            'status' => 1
        ))->select());

        $totaldeal = M('Trade')->field("sum(deal) as 'totaldealnum' , sum(price) as 'totaldealprice'")->where('order_status >= 3')->select();

        $activeuser = count(M('User')->field('id')->where('lasttime between '.time().'-86400 and ' .time().'+86400')->select());
        //随机获取vip用户发布的usdp 买单
        $vip_udspUserId =M()->table('trade_user as a')
                ->field('a.id')->join('left join trade_adver as b on a.id = b.userid ')
                ->where(array(
                    'a.is_vip' => 1,
                    'b.trade_type' => 1,
                    'b.coin_type' =>'usdp',
                    'b.status' =>1
                ))->select();

        $randUserid = $vip_udspUserId[rand(0,count($vip_udspUserId)-1)];

        $vipbuy_usdpData = M()->table('trade_adver as a')
            ->field('a.userid,a.id,a.price,a.min_limit,a.max_limit,a.trade_type,a.pay_type,a.num,a.coin_type,b.username,b.headimg,b.is_vip,c.trade_num,c.user_trust,c.user_praise')
            ->join('left join trade_user as b on a.userid = b.id left join trade_user_credit as c on a.userid=c.userid')
            ->where(array(
                'a.userid' => $randUserid['id'],
                'a.coin_type' => 'usdp',
                'a.trade_type' => 1,
                'a.status' =>1
                ))->limit(1)->select();

        //随机获取vip用户发布的usdp 卖单
        $vip_usdp_UserId =M()->table('trade_user as a')
            ->field('a.id')->join('left join trade_adver as b on a.id = b.userid ')
            ->where(array(
                'a.is_vip' => 1,
                'b.trade_type' => 2,
                'b.coin_type' =>'usdp',
                'b.status' =>1
            ))->select();

        $randUserid = $vip_usdp_UserId[rand(0,count($vip_usdp_UserId)-1)];

        $vipsell_usdpData = M()->table('trade_adver as a')
            ->field('a.userid,a.id,a.price,a.min_limit,a.max_limit,a.trade_type,a.pay_type,a.num,a.coin_type,b.username,b.headimg,b.is_vip,c.trade_num,c.user_trust,c.user_praise')
            ->join('left join trade_user as b on a.userid = b.id left join trade_user_credit as c on a.userid=c.userid')
            ->where(array(
                'a.userid' => $randUserid['id'],
                'a.coin_type' => 'usdp',
                'a.trade_type' => 2,
                'a.status' =>1
            ))->limit(1)->select();

    //随机获取vip用户发布的usdp 买单
        $vip_btcUserId =M()->table('trade_user as a')
            ->field('a.id')->join('left join trade_adver as b on a.id = b.userid ')
            ->where(array(
                'a.is_vip' => 1,
                'b.trade_type' => 1,
                'b.coin_type' =>'btc',
                'b.status' =>1
            ))->select();

        $randUserid = $vip_btcUserId[rand(0,count($vip_btcUserId)-1)];

        $vipbuy_btcData = M()->table('trade_adver as a')
            ->field('a.userid,a.id,a.price,a.min_limit,a.max_limit,a.trade_type,a.pay_type,a.num,a.coin_type,b.username,b.headimg,b.is_vip,c.trade_num,c.user_trust,c.user_praise')
            ->join('left join trade_user as b on a.userid = b.id left join trade_user_credit as c on a.userid=c.userid')
            ->where(array(
                'a.userid' => $randUserid['id'],
                'a.coin_type' => 'btc',
                'a.trade_type' => 1,
                'a.status' =>1
            ))->limit(1)->select();

        //随机获取vip用户发布的btc 卖单
        $vip_btc_UserId =M()->table('trade_user as a')
            ->field('a.id')->join('left join trade_adver as b on a.id = b.userid ')
            ->where(array(
                'a.is_vip' => 1,
                'b.trade_type' => 2,
                'b.coin_type' =>'btc',
                'b.status' =>1
            ))->select();

        $randUserid = $vip_btc_UserId[rand(0,count($vip_btc_UserId)-1)];

        $vipsell_btcData = M()->table('trade_adver as a')
            ->field('a.userid,a.id,a.price,a.min_limit,a.max_limit,a.trade_type,a.pay_type,a.num,a.coin_type,b.username,b.headimg,b.is_vip,c.trade_num,c.user_trust,c.user_praise')
            ->join('left join trade_user as b on a.userid = b.id left join trade_user_credit as c on a.userid=c.userid')
            ->where(array(
                'a.userid' => $randUserid['id'],
                'a.coin_type' => 'btc',
                'a.trade_type' => 2,
                'a.status' =>1
            ))->limit(1)->select();

        $viptotaldata = array_merge($vipbuy_usdpData,$vipsell_usdpData,$vipbuy_btcData,$vipsell_btcData);

        foreach ($viptotaldata as $k => $v) {
            $viptotaldata[$k]['pay_type']=explode(',',$v['pay_type']);
            $viptotaldata[$k]['price'] = $v['price'] + 0;
            $viptotaldata[$k]['limit'] = ($v['min_limit'] + 0) . "-" . ($v['max_limit'] + 0);
        }

        $imgData = M('System')->order('sort asc')->limit(3)->select();

        $data = array();

        $data['totaldealnum'] = $totaldeal[0]['totaldealnum'];

        $data['totaldealprice'] = $totaldeal[0]['totaldealprice'];

        $data['vipdata'] = $viptotaldata;

        $data['usernum'] = $usernum;

        $data['activeuser'] = $activeuser;

        $data['img'] = $imgData;

        $this->ajaxReturn($data , 'JSON');
    }
    public function coinlist(){
        $data = M('Coin')->field('id,name')->where(array(
            'status' => 1
        ))->select();
        $this->ajaxReturn($data,'JSON');
    }

    public function btcnum($userid = NULL){
        if ($userid) {
            $btcnum = M('UserCoin')->field('btc,btcd')->where(array(
                'userid' => $userid
            ))->find();

            $btctotal = $btcnum['btc'] + $btcnum['btcd'];

            $arr = array();

            $arr['btc'] = $btcnum['btc'] + 0;

            $arr['btcd'] = $btcnum['btcd'] + 0;

            $arr['btctotal'] = $btctotal;

            $arr['type'] = 1;

            $this->ajaxReturn($arr , 'JSON');
        }
    }

    public function chatLog($userid = NULL){
        $chatLog = M('Chat')->where(array(
            'chatid' => $userid,
            'status' => 0
        ))->order('addtime asc')->select();

        $data = array();

        if ($chatLog) {
            $data['chatlog'] = 1;
        }else{
            $data['chatlog'] = 0;
        }

        $this->ajaxReturn($data , 'JSON');
    }

    public function pushMessage($userid){
        $messageData = M('UserOperation')->field('id,tradeid,order_id , userid ,addtime')->where(array(
            'trade_id' => $userid,
            'status' => 0
        ))->order('id desc')->select();

        $message = array();
        if (empty($messageData)) {
            $message = array();
        }

        foreach ($messageData as $k => $v) {
            $arr = M('Trade')->where(array(
                'id' => $v['tradeid']
            ))->find();

            $username = M('User')->where(array(
                'id' => $v['userid']
            ))->getField('username');

            $message[$k]['type'] = $arr['type'];
            $message[$k]['username'] = $username;
//            $message[$k]['addtime']= $v['addtime'];
            $message[$k]['order_id'] = $v['order_id'];
            $message[$k]['id'] = $v['tradeid'];
            $message[$k]['messageid'] = $v['id'];


        }

        $result=array();
        foreach ($message as $k => $v){
            $has = false;
            foreach ($result as $k => $val){
                if ($val['order_id']==$v['order_id']){
                    $has =true;
                    break;
                }
            }
         if (!$has){
             $result[]=$v;
         }
        }

        foreach ($result as $k => $v){
            $result[$k]['addtime']=M('UserOperation')->where(array(
                'trade_id' => $userid,
                'status' => 0,
                'order_id' => $v['order_id']
                ))->order('addtime desc')->limit(1)->getField('addtime');
        }
        $this->ajaxReturn($result , 'JSON');
    }

    public function symbolRead($id,$userid ,$token , $tradeid){
        $this->checkLog($userid,$token);

        $rs = M('UserOperation')->where(array(
            'tradeid' => $tradeid,
            'trade_id' => $userid
        ))->setField('status' , 1);

        if ($rs) {
            $this->ajaxSuccess('标记已读成功');
        }else{
            $this->ajaxError('标记已读失败');
        }
    }

    public function showProblem(){
        $rs = M('Comproblem')->select();

        $this->ajaxReturn($rs,'JSON');
    }

    public function showGuide(){
        $data =M('Guide')->select();

        $this->ajaxReturn($data ,'JSON');
    }

    public function checkGuide($id){
        if ($id==null || $id == ''){
            $this->ajaxError('ID不能为空');
        }

        $data=M('Guide')->where('id ='.$id)->order('sort desc')->find();
        $this->ajaxReturn($data ,'JSON');
    }
}