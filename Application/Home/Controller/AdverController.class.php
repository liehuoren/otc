<?php

namespace Home\Controller;

class AdverController extends HomeController
{
    //发布广告首页
    public function index($userid , $token)
    {
        $this->checkLog($userid , $token);

        $user = M('User')->where(array(
            'id' => $userid
        ))->find();

        if ($user['sm_is_ok'] != 2) {
            $data = array(
                'type' => 0,
                'code' => 4,
                'msg' => '请先完成实名认证'
            );
            $this->ajaxReturn($data);
        }


        $moneytype = M('MoneyType')->where('status = 1')->select();

        $arr = array();

        $arr['moneytype'] = $moneytype;

        $arr['usdt'] = 10;

        $fee = M('Fee')->where(array(
            'coinname' => 'usdt'
        ))->find();

        $arr['fee'] = $fee['fee'];

        $this->ajaxReturn($arr, 'JSON');
    }

    //发布广告
    public function addAdver($userid,$token,$price,$trade_type,$money_type,$min_limit,$max_limit,$pay_type,$message, $coin_name ,$paypassword){

        $this->checkLog($userid, $token);

        //round 保留小数点后8位

        $coin_id =M('Coin')->where(array(
            'name' =>$coin_name
        ))->getField('id');

        $price=round($price,2);
        $min_limit=round($min_limit,2);
        $max_limit=round($max_limit,2);

        if ($trade_type == 1) {
            $adverData = M('Adver')->where(array(
                'userid' => $userid,
                'trade_type' => 1,
                'coin_type'=>$coin_name,
                'status' => 1
            ))->select();
        }

        if ($trade_type == 2) {
            $adverData = M('Adver')->where(array(
                'userid' => $userid,
                'trade_type' => 2,
                'coin_type'=>$coin_name,
                'status' => 1
            ))->select();
        }

        if (!M('Coin')->where(array(
            'name' => $coin_name
        ))->find()) {
            $this->ajaxError('您输入的币种不存在');
        }

        if ($adverData) {
            $this->ajaxError('同一类型相同币种的广告只能发布一条');
        }

        $user = M('User')->where('id = ' . $userid)->find();
        if ($user['sm_is_ok'] != 2) {
            $data = array(
                'type' => 0,
                'code' => 4,
                'msg' => '请先完成实名认证'
            );
            $this->ajaxReturn($data);
        }

        if ($min_limit < 500) {
            $this->ajaxError('最低限额不能低于500');
        }

        if ($max_limit >200000) {
            $this->ajaxError('请输入交易金额最高限制不能超过20W');
        }

        if (!$user['email']) {
            $data = array(
                'type' => 0,
                'code' => 4,
                'msg' => '请绑定电子邮件'
            );
            $this->ajaxReturn($data);
        }

        if (!$user['moble']) {
            $data = array(
                'type' => 0,
                'code' => 4,
                'msg' => '请绑定手机'
            );
            $this->ajaxReturn($data);
        }

        if ($price > $price * 1.2) {
            $this->ajaxError('价格不能超出市场价格的 20%');
        }

        if ($price < $price * 0.8) {
            $this->ajaxError('价格不能低于市场价格的 80%');
        }

        if ($paypassword == '' || $paypassword == null){
            $this->ajaxError('请输入资金密码');
        }

        if (!$user['paypassword']){
            $arr=array(
                'type' => 0,
                'code' => 4,
                'msg' => '请设置资金密码'
            );
            $this->ajaxReturn($arr);
        }
        if ($paypassword != $user['paypassword']){
            $this->ajaxError('资金密码输入错误');
        }

        $userData = M('UserCoin')->where(array(
            'userid' => $userid
        ))->find();

        if ($trade_type == '') {
            $this->ajaxError('请输入广告类型');
        }

        if ($money_type == '') {
            $this->ajaxError('请输入交易的币种类型');
        }

        if ($min_limit == '') {
            $this->ajaxError('请输入交易金额最低限制');
        }

        if ($min_limit == 0) {
            $this->ajaxError('最小限额不能为0');
        }

        if ($max_limit == 0) {
            $this->ajaxError('最大限额不能为0');
        }


        if ($max_limit == '') {
            $this->ajaxError('请输入交易金额最高限制');
        }

        if ($max_limit < $min_limit) {
            $this->ajaxError('您的价格区间有问题');
        }

        if ($pay_type == '' || $pay_type ==null) {
            $this->ajaxError('请输入支付类型');
        }


//        if ($message == ''||$message==null){
//            $this->ajaxError('请输入广告备注');
//        }

        $rand=rand(11,99);

        $adver_code = date('mdHis').$rand;

        $pay_type = implode(',' ,$pay_type);

        $m = M();
        $m->execute('set autocommit = 0');
        $m->execute('lock tables trade_adver write , trade_user_coin write');

        $rs[] = $m->table('trade_adver')->add(array(
            'userid' => $user['id'],
            'trade_type' => $trade_type,
            'adver_code' => $adver_code,
            'money_type' => $money_type,
            'price' => $price,
            'coin_type' => $coin_name,
            'min_limit' => $min_limit,
            'max_limit' => $max_limit,
            'pay_type' => $pay_type,
            'message' => $message,
            'status' => 1,
            'addtime' => time(),
        ));

        //做数组验证
        if (check_arr($rs)) {
            $m->execute('commit');
            $m->execute('unlock tables');
            $data =array(
                'msg' =>'发布成功',
                'type' =>1,
                'coin_id' => $coin_id
            );
            $this->ajaxReturn($data,'JSON');
//            $this->ajaxSuccess('发布成功',$coin_id);
        } else {
            $m->execute('rollback');
            $m->execute('unlock tables');
            $data =array(
                'msg' =>'发布成功',
                'type' =>0,
                'coin_id' => $coin_id
            );
            $this->ajaxReturn($data,'JSON');
//            $this->ajaxError('发布失败',$coin_id);
        }
    }

    //我的广告
    public function myAdver($userid , $token)
    {
        $this->checkLog($userid, $token);

        $myAdver = M('Adver')->field('id,adver_code,trade_type,status,addtime,min_limit,max_limit,price,full_num,country,coin_type')
            ->where(array(
                'userid' => $userid,
                'status' => 1
            ))->select();


        foreach ($myAdver as $k => $v){
            $myAdver[$k]['price']=$myAdver[$k]['price']+ '0';

            $myAdver[$k]['limt']=($v['min_limit'] +0) .'-'. ($v['max_limit'] +0);

            $trade=M('trade')->where('adver_id = '.$v['id'].' and status = 1 ')->select();

            $myAdver[$k]['count']=count($trade);
        }
        $this->ajaxReturn($myAdver, 'JSON');
    }

    //下架商品  id是广告的id
    public function delAdver($adver_id , $userid ,$token)
    {
        $this->checkLog($userid, $token);

        $adverData = M('Adver')->where(array(
            'id' => $adver_id
        ))->find();

        if ($adverData['status'] == 0) {
            $this->ajaxError('您的广告已经下架');
        }

        //TODO 验证广告拥有权  已修改
        if ($adverData['userid'] != $userid) {
            $this->ajaxError('此广告不是您发布的');
        }

        $tradeingData = M('Trade')->where('adver_id = ' .$adver_id .' and (order_status = 1 or order_status =2)')->select();

        if (!$tradeingData){
            $res = M('Adver')->where(array(
                'id' => $adver_id
            ))->setField('status' ,0);

            if ($res){
                $this->ajaxSuccess('删除成功');
            }else{

                $this->ajaxError('删除失败');
            }

        }else{
            $this->ajaxError('当前广告存在未完成订单，暂不可下架操作');
        }

//        if ($res){
//            $this->ajaxSuccess('删除成功');
//        }else{
//            $this->ajaxError('删除失败');
//        }
    }
}