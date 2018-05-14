<?php

namespace Home\Controller;

class UserController extends HomeController
{
    //用户中心首页
    public function index($userid , $token)
    {
        $this->checkLog($userid, $token);

        $m = M();
        $userData = $m->table('trade_user as a')
            ->field('a.moble,a.email,a.personalnote,a.sm_is_ok,a.email_is_ok,a.username,a.headimg,a.addtime,b.user_praise,b.trade_num,b.user_trust,b.first_tradetime')
            ->join('left join trade_user_credit as b on a.id = b.userid')
            ->where(array(
                'a.id' => $userid
            ))->find();

        if ($userData['email_is_ok'] == 1 && $userData['sm_is_ok'] == 2 && $userData['moble']) {
            $userData['is_hide'] = 1;
        }else{
            $userData['is_hide'] = 0;
        }

        $paypassword = M('User')->where(array(
            'id' => $userid
        ))->getField('paypassword');

        if ($paypassword) {
            $userData['is_paypassword'] = 1;
        }else{
            $userData['is_paypassword'] = 0;
        }

        $tradeBtcTotal = M('Trade')->field("sum(deal) as 'deal' , sum(price) as 'price'")->where('order_status >=3 and (userid = ' . $userid . ' or trade_id = ' .$userid .') and coin_type = "btc"')->select();
        $tradeUsdpTotal = M('Trade')->field("sum(deal) as 'deal' , sum(price) as 'price'")->where('order_status >=3 and (userid = ' . $userid . ' or trade_id = ' .$userid .') and coin_type = "usdp"')->select();
        $userData['historydeal'] = $tradeBtcTotal[0]['deal'] + '0';
        $userData['historyusdpdeal'] = $tradeUsdpTotal[0]['deal'] + '0';
        $userData['historyprice'] = $tradeBtcTotal[0]['price'] + '0';
        $this->ajaxReturn($userData, 'JSON');
        $this->display();
    }

    public function upmoble($userid , $token){

        $this->checkLog($userid,$token);

        $data = I('post.');

        if (!check($data['moble'], 'moble')) {
            $this->ajaxError('手机号码格式有误');
        }

        $rs = M('User')->where(array(
            'id' => $userid
        ))->save($data);

        if ($rs) {
            $this->ajaxSuccess('手机认证成功');
        }else{
            $this->ajaxError('手机认证失败');
        }
    }

    public function updatemoble(){

        $data = I('post.');

        $this->checkLog($data['userid'],$data['token']);

        if (!check($data['moble'], 'moble')) {
            $this->ajaxError('手机号码格式有误');
        }
        $rs = M('User')->where(array(
            'id' => $data['userid']
        ))->save(array(
            'moble' => $data['moble']
            ));

        if ($rs) {
            $this->ajaxSuccess('手机号码修改成功');
        }else{
            $this->ajaxError('手机号码修改失败');
        }
    }

    public function myAdver($trade_type, $userid, $token)
    {
//        if (!userid()){
//            $this->ajaxError('请先登录');
//        }

        $this->checkLog($userid, $token);

        $adverData = M('Adver')->field('id,trade_type,country,price,addtime,status')->where(array(
            'userid' => $userid,
            'trade_type' => $trade_type
        ))->select();

        $this->ajaxReturn($adverData, 'JSON');
        $this->display();
    }

    //填写用户实名认证
    public function upshiming($userid, $token)
    {
        $this->checkLog($userid, $token);

        $data = I("post.");

        $reidcard = M('User')->where(array(
            'idcard' => $data['idcard']
        ))->find();

        if ($reidcard) {
            $this->ajaxError('身份证号已存在');
        }

        $user = M('User')->where(array(
            'id' => $userid
        ))->find();

        if ($user['sm_is_ok'] == 2) {
            $this->ajaxError('您已通过实名认证');
        }

        if (!check($data['truename'], 'truename')) {
            $this->ajaxError('真实姓名格式错误');
        }

        if (!check($data['idcard'], 'idcard')) {
            $this->ajaxError('身份证号格式错误');
        }

        if ($data['sm_sc_zheng']) {
            $data['sm_time'] = time();
            $data['sm_is_ok'] = 1;
            $rs = M('User')->where(array(
                'id' => $userid
            ))->save($data);

            if ($rs) {
                $this->ajaxSuccess('提交成功!');
            } else {
                $this->ajaxError('提交失败');
            }
        }
    }

    //用户的实名认证
    public function nameauth($userid, $token)
    {
//        if (!userid()) {
//            $this->ajaxError('请先登录');
//        }
        $this->checkLog($userid, $token);

        $user = M('User')->where(array(
            'id' => $userid
        ))->find();

        if ($user['idcard']) {
            $user['idcard'] = substr_replace($user['idcard'], '********', 6, 8);
        }
        $this->ajaxReturn($user, 'JSON');
    }

    //修改用户的登录密码
    public function uppassword($oldpassword, $newpassword, $repassword, $userid, $token)
    {

        $this->checkLog($userid, $token);

        if (!check($oldpassword, 'password')) {
            $this->ajaxError('旧登录密码格式错误');
        }

        if (!check($newpassword, 'password')) {
            $this->ajaxError('新登录密码格式错误');
        }

        if ($newpassword != $repassword) {
            $this->ajaxError('两次密码不一致，请重新输入!');
        }

        $password = M('User')->where(array(
            'id' => $userid
        ))->getField('password');

        if (md5($oldpassword) != $password) {
            $this->ajaxError('旧登录密码错误');
        }

        $rs = M('User')->where(array(
            'id' => $userid
        ))->save(array(
            'password' => md5($newpassword)
        ));

        if ($rs) {
            $this->ajaxSuccess('修改成功!');
        } else {
            $this->ajaxError('修改失败');
        }
    }

    //找回密码验证
    public function checkFindPwd($email, $code)
    {
        if (!check($email, 'email')) {
            $this->ajaxError('邮箱格式错误');
        }

        if ($email == ''){
            $this->ajaxError('邮箱不能为空');
        }

        if ($code == '') {
            $this->ajaxError('验证码不能为空');
        }

        $user = M('User')->where(array(
            'email' => $email
        ))->find();

        if (!$user) {
            $this->ajaxError('此用户不存在');
        }


        $emailCode = M('EmailCode')->where(array(
            'email' => $user['email']
        ))->order('id desc')->find();

        if (!$emailCode) {
            $this->ajaxError('请发送验证码');
        }

        if (time() > $emailCode['addtime'] + 300){
            $this->ajaxError('您的验证码过期，请重新输入验证码');
        }

        if ($emailCode['email'] . $emailCode['code'] != $user['email'].$code) {
            $this->ajaxError('您输入的验证码有误');
        }else{
            $data = array(
                'message' => '验证成功',
                'type' => 1,
                'userid' => $user['id']
            );

            $this->ajaxReturn($data , 'JSON');
        }
    }

    //注册邀请信息
    public function invit_info($userid, $token)
    {
        $this->checkLog($userid, $token);
        $user= M('User')->where('id ='.$userid)->find();
        $invitinfo['invit1']= M('User')->where(array(
            'invit_1' => $user['invit']
        ))->select();

        $invitinfo['invit2'] = M('User')->where(array(
            'invit_2' => $user['invit']
        ))->select();


        $this->ajaxReturn($invitinfo,'JSON');
    }
    //找回密码
    public function findPassword($newpassword, $repassword, $userid)
    {
        if (!check($newpassword, 'password')) {
            $this->ajaxError('旧交易密码格式错误!');
        }

        if ($newpassword != $repassword) {
            $this->ajaxError('两次密码不一致，请重新输入!');
        }

        $rs = M('User')->where(array(
            'id' => $userid
        ))->save(array(
            'password' => md5($newpassword)
        ));

        if ($rs) {
            $this->ajaxSuccess('修改成功!');
        } else {
            $this->ajaxError('修改失败');
        }

    }
    //支付密码


    //忘记资金密码
//    public function checkFindpayPwd($email, $code)
//    {
//        //$this->checkLog($userid,$token);
//
//        $emailCode = M('EmailCode')->where(array(
//            'email' => $email
//        ))->order('id desc')->find();
//
//        if (!$emailCode) {
//            $this->ajaxError('请发送验证码');
//        }
//
//        if (time() > $emailCode['addtime'] + 60){
//            $this->ajaxError('您的验证码过期，请重新输入验证码');
//        }
//
//        if ($emailCode['email'] . $emailCode['code'] != $email.$code) {
//            $this->ajaxError('您输入的验证码有误');
//        }
//
//        if (!check($email, 'email')) {
//            $this->ajaxError('邮箱格式错误');
//        }
//
//        $user = M('User')->where(array(
//            'email' => $email
//        ))->find();
//
//        if (!$user) {
//            $this->ajaxError('此用户不存在');
//        }
//
//        $emailcode = file_get_contents('Public/code/' . $email . '.txt');
//
//        if (!$emailcode) {
//            $this->ajaxError('请发送验证码');
//        }
//
//        $msg = explode(':time',$emailcode);
//
//        if (time() > $msg[1] + 60){
//            $this->ajaxError('您的验证码过期，请重新输入验证码');
//        }
//
//        if ($msg[0] != $email.$code) {
//            $this->ajaxError('您输入的验证码有误');
//        } else {
//            $arr = array(
//                'type' => 1,
//                'msg' => '验证成功',
//                'userid' => $user['id']
//            );
//            $this->ajaxReturn($arr);
//        }
//    }

    //修改交易密码
    public function uppaypassword($newpaypassword, $repaypassword, $userid, $token , $code)
    {
        $this->checkLog($userid, $token);

        $user = M('User')->where(array(
            'id' => $userid
        ))->find();

        if ($newpaypassword == '') {
            $this->ajaxError('请填写充值所需信息');
        }

        if ($repaypassword == '') {
            $this->ajaxError('请填写充值所需信息');
        }


        $emailCode = M('EmailCode')->where(array(
            'email' => $user['email']
        ))->order('id desc')->find();

        if (!$emailCode) {
            $this->ajaxError('请发送验证码');
        }

        if (time() > $emailCode['addtime'] + 300){
            $this->ajaxError('您的验证码过期，请重新输入验证码');
        }

        if ($emailCode['email'] . $emailCode['code'] != $user['email'].$code) {
            $this->ajaxError('您输入的验证码有误');
        }



        if (!check($newpaypassword, 'password')) {
            $this->ajaxError('新交易密码格式错误');
        }

        if ($newpaypassword != $repaypassword) {
            $this->ajaxError('两次密码不一致，请重新输入!');
        }

        $user = M('User')->where(array(
            'id' => $userid
        ))->find();



        if (md5($newpaypassword) == $user['password']) {
            $this->ajaxError('交易密码不能和登录密码相同');
        }

        $rs = M('User')->where(array(
            'id' => $userid
        ))->save(array(
            'paypassword' => md5($newpaypassword)
        ));

        if ($rs) {
            $this->ajaxSuccess('修改成功!');
        } else {
            $this->ajaxError('修改失败');
        }
    }

    public function paypassword($paypassword , $repaypassword , $userid, $token)
    {
        $this->checkLog($userid,$token);

        if (!check($paypassword, 'password')) {
            $this->ajaxError('交易密码格式错误');
        }

        if (!check($repaypassword, 'password')) {
            $this->ajaxError('交易密码格式错误');
        }

        if ($paypassword != $repaypassword) {
            $this->ajaxError('两次输入密码不一致');
        }

        $rs = M('User')->where(array(
            'id' => $userid
        ))->save(array(
            'paypassword' => md5($paypassword)
        ));

        if ($rs) {
            $this->ajaxSuccess('修改成功!');
        } else {
            $this->ajaxError('修改失败');
        }
    }

    public function image()
    {
        $img = $_FILES['img'];
        $upload = new \Think\Upload();
        $upload->maxSize = 3145728;
        $upload->exts = array(
            'jpg',
            'gif',
            'png',
            'jpeg'
        );
        $upload->savePath = '/Public/Uploads/';
        $upload->autoSub = true;
        $info = $upload->uploadOne($img);
        $path = $info['savepath'] . $info['savename'];
        $data = array(
            'path' => $path
        );
        $this->ajaxReturn($data,'JSON');
    }


    public function headimage($userid)
    {
        $img = $_FILES['img'];
        $upload = new \Think\Upload();
        $upload->maxSize = 3145728;
        $upload->exts = array(
            'jpg',
            'gif',
            'png',
            'jpeg'
        );
        $upload->savePath = '/Public/headimgs/';
        $upload->autoSub = true;
        $info = $upload->uploadOne($img);
        $path = $info['savepath'] . $info['savename'];
        $data = array(
            'path' => $path
        );

        $this->ajaxReturn($data,'JSON');
    }

    public function saveheadimg($userid , $path){
        $rs = M('User')->where(array(
            'id' => $userid
        ))->setField('headimg' , $path);

        if ($rs) {
            $this->ajaxSuccess('保存头像成功');
        }else{
            $this->ajaxError('上传头像失败');
        }
    }

    public function personalnote($personalnote,$userid,$token){
        $this->checkLog($userid,$token);

        $res = M('User')->where(array(
            'id' => $userid
        ))->save(array(
            'personalnote' => $personalnote
        ));

        if ($res) {
            $this->ajaxSuccess('保存成功');
        }else{
            $this->ajaxError('保存失败');
        }
    }


    public function trust($otherid,$userid,$token){

        $this->checkLog($userid,$token);

        $otherData = M('UserCredit')->where(array(
            'userid' => $otherid
        ))->getField('trustme');

        $Data = explode(',' , $otherData);

        if (in_array($userid , $Data)) {
            $this->ajaxError('不能重复点赞');
        }

        $rs = M('UserCredit')->where(array(
            'userid' => $otherid
        ))->setInc('user_trust',1);

        if (!$rs){
            $this->ajaxError('点击信任失败');
        }

        $trustData = M('UserCredit')->where(array(
            'id' => $userid
        ))->find();

        if ($trustData['mytrust'] == '') {
            $rs = M('UserCredit')->where(array(
                'userid' => $userid
            ))->save(array(
                'mytrust' => $otherid
            ));
        }else{
            $rs = M('UserCredit')->where(array(
                'userid' => $userid
            ))->setField('mytrust',$trustData['mytrust'] .',' .$otherid);
        }

        if (!$rs) {
            $this->ajaxError('添加信任用户失败');
        }

        if ($trustData['trustme'] == '') {
            $rs = M('UserCredit')->where(array(
                'userid' => $otherid
            ))->save(array(
                'trustme' => $userid
            ));
        }else{
            $rs = M('UserCredit')->where(array(
                'userid' => $otherid
            ))->setField('trustme',$trustData['trustme'] .',' .$userid);
        }

        if ($rs) {
            $this->ajaxSuccess('点击成功');
        }else{
            $this->ajaxError('添加信任我失败');
        }
    }

    //新人我的用户
    public function trustme($userid,$token){

        $this->checkLog($userid,$token);

        $trustme = M('UserCredit')->where(array(
            'userid' => $userid
        ))->getField('trustme');

        $data = explode(',' , $trustme);
        foreach ($data as  $k => $v){
            $arr[$k] = M('User')->field('username,headimg')->where(array(
                'id' => $v
            ))->find();
        }

        $this->ajaxReturn($arr ,'JSON');
    }

    //我信任的用户
    public function mytrust($userid,$token){

        $this->checkLog($userid,$token);

        $mytrust = M('UserCredit')->where(array(
            'userid' => $userid
        ))->getField('mytrust');

        $data = explode(',' , $mytrust);
        foreach ($data as  $k => $v){
            $arr[$k] = M('User')->field('username,headimg')->where(array(
                'id' => $v
            ))->find();
        }

        $this->ajaxReturn($arr ,'JSON');
    }


    public function userDetail($userid , $token , $bussinessid){

        $this->checklog($userid,$token);

        $bussinessData = M()->table('trade_user as a')
            ->field('a.personalnote,a.addtime,a.username,a.headimg,a.sm_is_ok,a.email_is_ok,b.first_tradetime,b.trade_num,b.user_praise,b.user_trust')
            ->join('left join trade_user_credit as b on a.id = b.userid')
            ->where("a.id=" . $bussinessid)->find();

        $adversell = M('Adver')->field('id,pay_type,min_limit,max_limit,price,trade_type')->where(array(
            'userid' => $bussinessid,
            'status' => 1,
            'trade_type' => 1
        ))->select();

        $adverbuy = M('Adver')->field('id,pay_type,min_limit,max_limit,price,trade_type')->where(array(
            'userid' => $bussinessid,
            'status' => 1,
            'trade_type' => 2
        ))->select();

        $tradeBtcTotal = M('Trade')->field("sum(deal) as 'deal'")->where('order_status >=3 and (userid = ' . $userid . ' or trade_id = ' .$userid .')')->select();

        $arr = array();

        $arr['bussinessData'] = $bussinessData;

        $arr['adverbuy'] = $adverbuy;

        $arr['adversell'] = $adversell;

        $arr['historydeal'] = $tradeBtcTotal[0]['deal'];

        $this->ajaxReturn($arr , 'JSON');
    }

}