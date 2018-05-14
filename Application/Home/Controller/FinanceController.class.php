<?php

namespace Home\Controller;

use Think\Controller;

use Common\Ext\Ethereum_Transaction;

class FinanceController extends HomeController
{
    public function index($userid, $token)
    {

        $this->checkLog($userid, $token);

        $userCoin = M('UserCoin')->where(array(
            'userid' => $userid
        ))->find();

        $data['coin'] = array(
            array(
                'name' => 'btc',
                'usable' => $userCoin['btc'],
                'freeze' => $userCoin['btcd'],
                'total' => $userCoin['btc'] + $userCoin['btcd']
            ),
            array(
                'name' => 'usdp',
                'usable' => $userCoin['usdp'],
                'freeze' => $userCoin['usdpd'],
                'total' => $userCoin['usdp'] + $userCoin['usdpd']
            ),
        );

        $this->ajaxReturn($data, 'JSON');
    }

    public function wallet($coin, $userid, $token, $page = null)
    {
        $this->checkLog($userid, $token);

        $user = D('User')->where('id = ' . $userid)->find();

        if ($user['sm_is_ok'] != 2) {
            $arr = array(
                'type' => 0,
                'code' => 4,
                'msg' => '请先完成实名认证'
            );

            $this->ajaxReturn($arr);
        }

        if (!$user['paypassword']) {
            $arr = array(
                'type' => 0,
                'code' => 4,
                'msg' => '请先设置资金密码'
            );

            $this->ajaxReturn($arr);
        }

        if (C('coin')[$coin]) {
            $coin = trim($coin);
        }

        if (!C('coin')[$coin]) {
            $this->ajaxError('不存在此币种');
        }

        $Coin = C('Coin');
        foreach ($Coin as $k => $v) {
            $coin_list[$v['name']] = $v;
        }
        $user_coin = M('UserCoin')->where(array(
            'userid' => $user['id']
        ))->find();


        $Coin = M('Coin')->where(array(
            'name' => $coin
        ))->find();

        if (!$Coin['zr_jz']) {
            $qianbao = '当前币种禁止转入';
        } else {
            $qbdz = $coin . 'b';
            if (!$user_coin[$qbdz]) {
                if ($Coin['type'] == 'eth') {
                    $dj_address = $Coin['dj_zj'];
                    $dj_port = $Coin['dj_dk'];
                    $eth = new \Common\Ext\Ethereum($dj_address, $dj_port);
                    if (!$eth->eth_protocolVersion()) {
                        $this->ajaxError('钱包链接失败');
                    } else {
                        $pass = createRandomStr(16);
                        $qianbao = $eth->personal_newAccount($pass);
                        $rs = M('UserCoin')->where(array(
                            'userid' => $user['id']
                        ))->save(array(
                            $coin . 's' => $pass
                        ));
                    }
                }

                if ($Coin['type'] == 'qbb') {

                    $dj_username = $Coin['dj_yh'];
                    $dj_password = $Coin['dj_mm'];
                    $dj_address = $Coin['dj_zj'];
                    $dj_port = $Coin['dj_dk'];

                    $CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);

                    $json = $CoinClient->getinfo();

                    if (!isset($json['version']) || !$json['version']) {
                        $this->ajaxError('钱包连接失败');
                    }

                    $qianbao_addr = $CoinClient->getaddressesbyaccount($user['email']);

                    if (!is_array($qianbao_addr)) {
                        $qianbao_ad = $CoinClient->getnewaddress($user['email']);

                        if (!$qianbao_ad) {
                            $this->ajaxError('生成钱包地址出错');
                        } else {
                            $qianbao = $qianbao_ad;
                        }
                    } else {
                        $qianbao = $qianbao_addr[0];
                    }
                }
                if (!$qianbao) {
                    $this->ajaxError('生成钱包地址失败');
                }
                $rs = M('UserCoin')->where(array(
                    'userid' => $user['id']
                ))->save(array(
                    $qbdz => $qianbao
                ));

                if (!$rs) {
                    $this->ajaxError('生成钱包地址出错');
                }
            } else {
                //TODO 测试参数替换
                $qianbao = $user_coin[$coin . 'b'];
            }
        }

        $where['userid'] = $user['id'];
        $where['coinname'] = $coin;
        $Moble = M('Myzr');

        $list = $Moble->where($where)
            ->order('id desc')
            ->select();

        $data = array(
            $coin => $user_coin[$coin],
            $coin . 'd' => $user_coin[$coin . 'd'],
            $coin . 'total' => $user_coin[$coin] + $user_coin[$coin . 'd'],
            'qiaobao' => $qianbao,
            'tradelog' => $list
        );

//        $data['btc'] = array(
//            'btc' => $user_coin['btc'],
//            'btcd' => $user_coin['btcd'],
//            'total' => $user_coin['btc'] + $user_coin['btcd']
//        );
//
//        $data['eth'] = array(
//            'eth' => $user_coin['eth'],
//            'ethd' => $user_coin['ethd'],
//            'total' => $user_coin['eth'] + $user_coin['ethd']
//        );


        //转出记录
        $zcData = M('Myzc')->field('num,userid,username,coinname,addtime,status')->where(array(
            'userid' => $userid,
            'coinname' => $coin
        ))->select();

        foreach ($zcData as $k => $v) {
            $zcData[$k]['num'] = $zcData[$k]['num'] + 0;

        }

        $data['zcData'] = $zcData;

        //转入记录

        $zrData = M('Myzr')->field('num,userid,username,coinname,addtime,status')->where(array(
            'userid' => $userid,
            'coinname' => $coin
        ))->select();

        foreach ($zrData as $k => $v) {
            $zrData[$k]['num'] = $zrData[$k]['num'] + '0';

        }

        $data['zrData'] = $zrData;
        $this->ajaxReturn($data, 'JSON');
    }

//    public function upmyzc($coin, $num, $addr, $paypassword, $userid, $token, $code)
//    {
//        $this->checkLog($userid, $token);
//
//        $num = abs($num);
//
//        $user = M('User')->where(array(
//            'id' => $userid
//        ))->find();
//
//        if ($coin == '') {
//            $this->ajaxError('币种不能为空');
//        }
//
//        if ($num == '') {
//            $this->ajaxError('数量不能为空');
//        }
//
//        if ($addr == '') {
//            $this->ajaxError('地址不能为空');
//        }
//
//        if ($paypassword == '') {
//            $this->ajaxError('交易密码不能为空');
//        }
//
//        if ($code == '') {
//            $this->ajaxError('验证码不能为空');
//        }
//
//        $emailCode = M('EmailCode')->where(array(
//            'email' => $user['email']
//        ))->order('id desc')->find();
//
//        if (!$emailCode) {
//            $this->ajaxError('请发送验证码');
//        }
//
//        if (time() > $emailCode['addtime'] + 300) {
//            $this->ajaxError('您的验证码过期，请重新输入验证码');
//        }
//
//        if ($emailCode['email'] . $emailCode['code'] != $user['email'] . $code) {
//            $this->ajaxError('您输入的验证码有误');
//        }
//
//        if (!check($num, 'currency')) {
//            $this->ajaxError('数量格式错误');
//        }
//
//        if (!check($addr, 'dw')) {
//            $this->ajaxError('钱包地址格式错误!');
//        }
//
//        if (!check($paypassword, 'password')) {
//            $this->ajaxError('交易密码格式错误');
//        }
//
//        if (!check($coin, 'n')) {
//            $this->ajaxError('币种格式错误');
//        }
//
//        if (!C('coin')[$coin]) {
//            $this->ajaxError('当前币种错误');
//        }
//
//        $Coin = M('Coin')->where(array(
//            'name' => $coin
//        ))->find();
//
//        if (!$Coin) {
//            $this->ajaxError('当前币种错误');
//        }
//        $myzc_min = ($Coin['zc_min'] ? abs($Coin['zc_min']) : 0.0001);
//        $myzc_max = ($Coin['zc_max'] ? abs($Coin['zc_max']) : 10000000);
//
//        if ($num < $myzc_min) {
//            $this->ajaxError('转出数量超过系统最小限制' . $myzc_min . '!');
//        }
//
//        if ($myzc_max < $num) {
//            $this->ajaxError('转出数量超过系统最大限制' . $myzc_max . '!');
//        }
//
//        if (md5($paypassword) != $user['paypassword']) {
//            $this->ajaxError('交易密码错误');
//        }
//
//        $user_coin = M('UserCoin')->where(array(
//            'userid' => $user['id']
//        ))->find();
//
//        if ($user_coin['usdp'] < $num) {
//            $this->ajaxError('USDP 可用余额不足');
//        }
//
//        $qbdz = $coin . 'b';
//
//        $fee = 0;
//        $mum = $num;
//        if ($Coin['type'] == 'eth') {
//
//            $dj_address = $Coin['dj_zj'];
//            $dj_port = $Coin['dj_dk'];
//
//            if ($Coin['type'] == 'eth') {
//                $eth = new \Common\Ext\Ethereum($dj_address, $dj_port);
//                if (!$eth->eth_protocolVersion()) {
//                    $this->ajaxError('钱包链接失败');
//                }
//            }
//
//
//            $mo = M();
//            $mo->execute('set autocommit=0');
//            $mo->execute('lock tables  trade_user_coin write  , trade_myzc write');
//            $rs = array();
//            $rs[] = $mo->table('trade_user_coin')->where(array(
//                    'userid' => $user['id']
//                ))->setDec($coin, $num);
//            debug(array(
//                'res' => $rs[0],
//                'lastsql' => $mo->table('trade_user_coin')->getLastSql()
//            ), '更新用户人民币');
//            $rs[] = $mo->table('trade_myzc')->add(array(
//                'userid' => $userid,
//                'username' => $addr,
//                'coinname' => $coin,
//                'num' => $num,
//                'fee' => $fee,
//                'mum' => $mum,
//                'addtime' => time(),
//                'status' => 0
//            ));
//
//            debug(array(
//                'res' => $rs[1],
//                'lastsql' => $mo->table('trade_myzc')->getLastSql()
//            ), '转出记录');
//
//            if (check_arr($rs)) {
//                $mo->execute('commit');
//                $mo->execute('unlock tables');
//                $this->ajaxSuccess('转出申请成功 请等待审核');
//            } else {
//                $mo->execute('rollback');
//                $mo->execute('unlock tables');
//                $this->ajaxError('转出失败');
//            }
//        }
//    }

//    public function upmyzc($coin, $num, $addr, $paypassword, $userid, $token, $code)
//    {
//        $this->checkLog($userid, $token);
//
//        $num = abs($num);
//
//        $user = M('User')->where(array(
//            'id' => $userid
//        ))->find();
//
//        if ($coin == '') {
//            $this->ajaxError('币种不能为空');
//        }
//
//        if ($num == '') {
//            $this->ajaxError('数量不能为空');
//        }
//
//        if ($addr == '') {
//            $this->ajaxError('地址不能为空');
//        }
//
//        if ($paypassword == '') {
//            $this->ajaxError('交易密码不能为空');
//        }
//
//        if ($code == '') {
//            $this->ajaxError('验证码不能为空');
//        }
//
//        $emailCode = M('EmailCode')->where(array(
//            'email' => $user['email']
//        ))->order('id desc')->find();
//
//        if (!$emailCode) {
//            $this->ajaxError('请发送验证码');
//        }
//
//        if (time() > $emailCode['addtime'] + 300) {
//            $this->ajaxError('您的验证码过期，请重新输入验证码');
//        }
//
//        if ($emailCode['email'] . $emailCode['code'] != $user['email'] . $code) {
//            $this->ajaxError('您输入的验证码有误');
//        }
//
//        if (!check($num, 'currency')) {
//            $this->ajaxError('数量格式错误');
//        }
//
//        if (!check($addr, 'dw')) {
//            $this->ajaxError('钱包地址格式错误!');
//        }
//
//        if (!check($paypassword, 'password')) {
//            $this->ajaxError('交易密码格式错误');
//        }
//
//        if (!check($coin, 'n')) {
//            $this->ajaxError('币种格式错误');
//        }
//
//        if (!C('coin')[$coin]) {
//            $this->ajaxError('当前币种错误');
//        }
//
//        $Coin = M('Coin')->where(array(
//            'name' => $coin
//        ))->find();
//
//        if (!$Coin) {
//            $this->ajaxError('当前币种错误');
//        }
//        $myzc_min = ($Coin['zc_min'] ? abs($Coin['zc_min']) : 0.0001);
//        $myzc_max = ($Coin['zc_max'] ? abs($Coin['zc_max']) : 10000000);
//
//        if ($num < $myzc_min) {
//            $this->ajaxError('转出数量超过系统最小限制' . $myzc_min . '!');
//        }
//
//        if ($myzc_max < $num) {
//            $this->ajaxError('转出数量超过系统最大限制' . $myzc_max . '!');
//        }
//
//        if (md5($paypassword) != $user['paypassword']) {
//            $this->ajaxError('交易密码错误');
//        }
//
//        $user_coin = M('UserCoin')->where(array(
//            'userid' => $user['id']
//        ))->find();
//
//        if ($user_coin['usdp'] < $num) {
//            $this->ajaxError('USDP 可用余额不足');
//        }
//
//        $qbdz = $coin . 'b';
//
//        $fee = 0;
//        $mum = $num;
//        if ($Coin['type'] == 'eth') {
//
//            $dj_address = $Coin['dj_zj'];
//            $dj_port = $Coin['dj_dk'];
//
//            if ($Coin['type'] == 'eth') {
//                $eth = new \Common\Ext\Ethereum($dj_address, $dj_port);
//                if (!$eth->eth_protocolVersion()) {
//                    $this->ajaxError('钱包链接失败');
//                }
//            }
//
//
//            $mo = M();
//            $mo->execute('set autocommit=0');
//            $mo->execute('lock tables  trade_user_coin write  , trade_myzc write');
//            $rs = array();
//            $rs[] = $mo->table('trade_user_coin')->where(array(
//                'userid' => $user['id']
//            ))->setDec($coin, $num);
//            debug(array(
//                'res' => $rs[0],
//                'lastsql' => $mo->table('trade_user_coin')->getLastSql()
//            ), '更新用户人民币');
//            $rs[] = $mo->table('trade_myzc')->add(array(
//                'userid' => $userid,
//                'username' => $addr,
//                'coinname' => $coin,
//                'num' => $num,
//                'fee' => $fee,
//                'mum' => $mum,
//                'addtime' => time(),
//                'status' => 0
//            ));
//
//            debug(array(
//                'res' => $rs[1],
//                'lastsql' => $mo->table('trade_myzc')->getLastSql()
//            ), '转出记录');
//
//            if (check_arr($rs)) {
//                $mo->execute('commit');
//                $mo->execute('unlock tables');
//                $this->ajaxSuccess('转出申请成功 请等待审核');
//            } else {
//                $mo->execute('rollback');
//                $mo->execute('unlock tables');
//                $this->ajaxError('转出失败');
//            }
//        }
//    }
    public function upmyzc($coin, $num, $addr, $paypassword, $userid, $token, $code)
    {
        $this->checkLog($userid, $token);

        $num = abs($num);

        $user = M('User')->where(array(
            'id' => $userid
        ))->find();

        if ($coin == '') {
            $this->ajaxError('币种不能为空');
        }

        if ($num == '') {
            $this->ajaxError('数量不能为空');
        }

        if ($addr == '') {
            $this->ajaxError('地址不能为空');
        }

        if ($paypassword == '') {
            $this->ajaxError('交易密码不能为空');
        }

        if ($code == '') {
            $this->ajaxError('验证码不能为空');
        }

        $emailCode = M('EmailCode')->where(array(
            'email' => $user['email']
        ))->order('id desc')->find();

        if (!$emailCode) {
            $this->ajaxError('请发送验证码');
        }

        if (time() > $emailCode['addtime'] + 300) {
            $this->ajaxError('您的验证码过期，请重新输入验证码');
        }

        if ($emailCode['email'] . $emailCode['code'] != $user['email'] . $code) {
            $this->ajaxError('您输入的验证码有误');
        }

        if (!check($num, 'currency')) {
            $this->ajaxError('数量格式错误');
        }

        if (!check($addr, 'dw')) {
            $this->ajaxError('钱包地址格式错误!');
        }

        if (!check($paypassword, 'password')) {
            $this->ajaxError('交易密码格式错误');
        }

        if (!check($coin, 'n')) {
            $this->ajaxError('币种格式错误');
        }

        if (!C('coin')[$coin]) {
            $this->ajaxError('当前币种错误');
        }

        $Coin = M('Coin')->where(array(
            'name' => $coin
        ))->find();

        if (!$Coin) {
            $this->ajaxError('当前币种错误');
        }
        $myzc_min = ($Coin['zc_min'] ? abs($Coin['zc_min']) : 0.0001);
        $myzc_max = ($Coin['zc_max'] ? abs($Coin['zc_max']) : 10000000);

        if ($num < $myzc_min) {
            $this->ajaxError('转出数量超过系统最小限制' . $myzc_min . '!');
        }

        if ($myzc_max < $num) {
            $this->ajaxError('转出数量超过系统最大限制' . $myzc_max . '!');
        }

        if (md5($paypassword) != $user['paypassword']) {
            $this->ajaxError('交易密码错误');
        }

        $user_coin = M('UserCoin')->where(array(
            'userid' => $user['id']
        ))->find();

        if ($user_coin['usdp'] < $num) {
            $this->ajaxError($coin .'可用余额不足');
        }

        //$qbdz = $coin . 'b';

//        $fee = 0;
//        $mum = $num;
        if ($Coin['type'] == 'eth') {

            $dj_address = $Coin['dj_zj'];
            $dj_port = $Coin['dj_dk'];

            if ($Coin['type'] == 'eth') {
                $eth = new \Common\Ext\Ethereum($dj_address, $dj_port);
                if (!$eth->eth_protocolVersion()) {
                    $this->ajaxError('钱包链接失败');
                }
            }
        }

        if ($Coin['type'] == 'qbb') {
            $dj_username = $Coin['dj_yh'];
            $dj_password = $Coin['dj_mm'];
            $dj_address = $Coin['dj_zj'];
            $dj_port = $Coin['dj_dk'];
            $CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);
            $json = $CoinClient->getinfo();
            if (!isset($json['version']) || !$json['version']) {
                $this->error('钱包链接失败');
            }

            $valid_res = $CoinClient->validateaddress($addr);

            if (!$valid_res['isvalid']) {
                $this->error($addr . '不是一个有效的钱包地址');
            }
        }

        $sFee = $num * ($Coin['fee'] / 100);
        $mum = $num - $sFee;

        $mo = M();
        $mo->execute('set autocommit=0');
        $mo->execute('lock tables  trade_user_coin write  , trade_myzc write');
        $rs = array();
        $rs[] = $mo->table('trade_user_coin')->where(array(
            'userid' => $user['id']
        ))->setDec($coin, $num);

        debug(array(
            'res' => $rs[0],
            'lastsql' => $mo->table('trade_user_coin')->getLastSql()
        ), '更新用户人民币');
        $rs[] = $mo->table('trade_myzc')->add(array(
            'userid' => $userid,
            'username' => $addr,
            'coinname' => $coin,
            'num' => $num,
            'fee' => $sFee,
            'mum' => $mum,
            'addtime' => time(),
            'status' => 0,
            'is_send_fee' => 'S'
        ));

        debug(array(
            'res' => $rs[1],
            'lastsql' => $mo->table('trade_myzc')->getLastSql()
        ), '转出记录');

        if (check_arr($rs)) {
            $mo->execute('commit');
            $mo->execute('unlock tables');
            $this->ajaxSuccess('转出申请成功 请等待审核');
        } else {
            $mo->execute('rollback');
            $mo->execute('unlock tables');
            $this->ajaxError('转出失败');
        }

    }
}