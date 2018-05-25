<?php

namespace Home\Controller;

use Think\Controller;

class QueueController extends Controller
{

    public function qianbaoETH()
    {
        $start = time();
        $coinList = M('Coin')->where(array(
            'status' => 1
        ))->select();
        foreach ($coinList as $k => $v) {
            if ($v['type'] != 'eth') {
                continue;
            }

            $coin = $v['name'];
            if (!$coin) {
                echo 'MM';
                continue;
            }

            $dj_address = $v['dj_zj'];
            $dj_port = $v['dj_dk'];
            echo 'start ' . $coin . "\n";
            echo 'client ' . $dj_address . "   port" . $dj_port;
            $eth = new \Common\Ext\Ethereum($dj_address, $dj_port);
            if (!$eth->eth_protocolVersion()) {
                echo '钱包链接失败！';
                continue;
            }
            $listtransactions = $eth->listLocalTransactions($coin);
            //todo  加判断是否是失败的 订单
            echo 'listtransactions:' . count($listtransactions) . "\n";
            $zrflag = false;
            foreach ($listtransactions as $trans) {
                if (!$trans->to) {
                    echo 'empty to continue' . "<br>";
                    continue;
                }
                $token = M('Coin')->where(array(
                    'contact_address' => $trans->to
                ))->find();

                if ($token) {

                    if ($trans->logs == '' || $trans->logs == null) {
                        continue;
                    }
                    $token_message = str_replace($token['method_id'], '', $trans->input);
                    $toaddressInfo = substr($token_message, 0, strlen($token_message) / 2);
                    $moneyInfo = substr($token_message, strlen($token_message) / 2);
                    $toaddress = $eth->getridof_zero($toaddressInfo, true);
                    $money = $eth->decode_hex($eth->getridof_zero($moneyInfo, false)) / 10000;
                    $trans->to = $toaddress;
                    $trans->value = $money;
                    $true_amount = $trans->value;
                    $flag = 1;
                    $coin = $token['name'];
                } else {
                    $user = M('UserCoin')->where(array(
                        'ethb' => $trans->to
                    ))->find();

                    if (!$user) {
                        continue;
                    }

                    $true_amount = $eth->real_banlance($eth->decode_hex($trans->value));
                    $flag = 2;
                    $coin = 'eth';
                }

                $user = M('UserCoin')->where(array(
                    $coin . 'b' => $trans->to
                ))->find();


                if ($user) {
                    $txInfo = M('Myzr')->where(array(
                        'txid' => $trans->hash,
                        'status' => '1'
                    ))->find();

                    if ($txInfo) {
                        echo '已经存在hash' . "<br>";
                        continue;
                    }
                    echo '开始接收myzr' . "<br>";
                    $sfee = 0;
                    $final_amount = $true_amount;


                    $res = M('Myzr')->where(array(
                        'txid' => $trans->hash
                    ))->find();
                    echo '保存第一下';
                    if ($res) {
                        continue;
                    } else {
                        if ($flag == 1) {
                            M('Myzr')->add(array(
                                'userid' => $user['userid'],
                                'username' => $trans->to,
                                'coinname' => $coin,
                                'fee' => $sfee,
                                'txid' => $trans->hash,
                                'num' => $true_amount,
                                'mum' => $final_amount,
                                'addtime' => time(),
                                'status' => 0,
                                'is_send_hotwallet' => 'S',
                                'is_apply_fee' => 'S'
                            ));
                        }else{
                            M('Myzr')->add(array(
                                'userid' => $user['userid'],
                                'username' => $trans->to,
                                'coinname' => $coin,
                                'fee' => $sfee,
                                'txid' => $trans->hash,
                                'num' => $true_amount,
                                'mum' => $final_amount,
                                'addtime' => time(),
                                'status' => 0,
                                'is_send_hotwallet' => 'S'
                            ));
                        }


                        $zrflag = true;
                        echo '保存第一下';
                    }
                    if ($zrflag) {
                        $mo = M();
                        $mo->execute('set autocommit=0');
                        $mo->execute('lock tables  trade_user_coin write , trade_myzr  write');
                        $rs = array();
                        echo '保存第二下' . $user['userid'];

                        $rs[] = $mo->table('trade_user_coin')
                            ->where(array(
                                'userid' => $user['userid']
                            ))
                            ->setInc($coin, $final_amount);


                        if ($res = $mo->table('trade_myzr')
                            ->where(array(
                                'txid' => $trans->hash
                            ))
                            ->find()
                        ) {
                            echo 'trade_myzr 设置状态为1';
                            $rs[] = $mo->table('trade_myzr')->save(array(
                                'id' => $res['id'],
                                'addtime' => time(),
                                'status' => 1
                            ));
                        } else {
                            echo 'trade_myzr 没有发现记录 生成一条' . "\n";

                            if ($flag == 1) {
                                $rs[] = $mo->table('trade_myzr')->add(array(
                                    'userid' => $user['userid'],
                                    'username' => $trans->to,
                                    'coinname' => $coin,
                                    'fee' => $sfee,
                                    'txid' => $trans->hash,
                                    'num' => $true_amount,
                                    'mum' => $final_amount,
                                    'addtime' => time(),
                                    'status' => 1,
                                    'is_send_hotwallet' => 'S',
                                    'is_apply_fee' => 'S'
                                ));
                            }else{
                                $rs[] = $mo->table('trade_myzr')->add(array(
                                    'userid' => $user['userid'],
                                    'username' => $trans->to,
                                    'coinname' => $coin,
                                    'fee' => $sfee,
                                    'txid' => $trans->hash,
                                    'num' => $true_amount,
                                    'mum' => $final_amount,
                                    'addtime' => time(),
                                    'status' => 1,
                                    'is_send_hotwallet' => 'S'
                                ));
                            }

                        }

                        if (check_arr($rs)) {
                            $mo->execute('commit');
                            $mo->execute('unlock tables');
                            echo $final_amount . ' receive ok ' . $coin . ' ' . $final_amount . "<br>";
                            echo 'commit ok' . "\n" . "<br>";
                        } else {
                            echo $final_amount . 'receive fail ' . $coin . ' ' . $final_amount . "<br>";
                            echo var_export($rs, true);
                            $mo->execute('rollback');
                            $mo->execute('unlock tables');
                            print_r($rs);
                            echo 'rollback ok' . "\n" . "<br>";
                        }
                    }
                }
            }
        }
        $endtime = time();
        $time = $endtime - $start;
        echo '共执行' . $time . '秒';
    }

    public function applyFee()
    {
        $myzrInfo = M('Myzr')->where(array(
            'is_apply_fee' => 'F',
            'is_send_hotwallet' => 'S',
            'coinname' => 'uspt'
        ))->select();

        foreach ($myzrInfo as $k => $v) {
            $arrid[$k]['id'] = $v['id'];
            $arrid[$k]['username'] = $v['username'];
        }

        foreach ($myzrInfo as $k => $v) {
            $myzrData[$v['username']] = $v;
        }

        if (!$myzrInfo) {
            exit('没有需要转的数据');
        }
        $coin = M('Coin')->where(array(
            'name' => 'usdt'
        ))->find();

        $dj_address = $coin['dj_zj'];

        $dj_port = $coin['dj_dk'];

        $eth = new \Common\Ext\Ethereum($dj_address, $dj_port);

        if (!$eth->eth_protocolVersion()) {
            die('钱包链接失败！');
        }


        foreach ($myzrData as $k => $v){
            $json = $eth->eth_contract_getBalance($coin['contact_address'],$v['username']);

            if ($json >= $coin['auto_num']) {

                $tokenFee = bcmul($coin['gas'] , $coin['gasprice']);

                $realFee = $eth->real_banlance($tokenFee);

                //申请以太坊手续费

                $sendrs = $eth->eth_sendTransaction($coin['dj_mian_address'], $coin['dj_mian_address_password'], $realFee, $v['username'], false, "", $eth->encode_dec($coin['eth_gas']),$eth->encode_dec($coin['eth_gasprice']));

                if ($sendrs) {
                    echo "转人手续费成功";

                    $rs = M('SendFee')->add(array(
                        'fromaddr' => $coin['dj_mian_address'],
                        'toaddr' => $v['username'],
                        'num' => $realFee,
                        'txid' => $sendrs,
                        'status' => 1,
                        'addtime' => time()
                    ));
                    if ($rs) {
                        echo "存入成功";
                    }else{
                        echo "存入手续费失败";
                    }

                    foreach ($arrid as  $v1) {
                        if ($v1['username'] == $v['username']) {
                            M('Myzr')->where(array(
                                'id' => $v1['id']
                            ))->setField('is_apply_fee' , 'S');
                        }
                    }

                }else{
                    echo "转入手续费失败";
                }
            }
        }
    }

    private function to0xValue($num){
        return '0x' .dechex($num);
    }

    public function sendHotWallet()
    {
        $myzrData = M('Myzr')->where(array(
            'is_send_hotwallet' => 'S',
            'is_apply_fee' => 'S'
        ))->select();

        foreach ($myzrData as $k => $v) {
            $arrid[$k]['id'] = $v['id'];
            $arrid[$k]['username'] = $v['username'];
        }

        foreach ($myzrData as $k => $v) {
            $myzrInfo[$v['username']] = $v;
        }

        $coin = M('Coin')->where(array(
            'name' => 'usdt'
        ))->find();

        $dj_address = $coin['dj_zj'];
        $dj_port = $coin['dj_dk'];

        $eth = new \Common\Ext\Ethereum($dj_address, $dj_port);
        if (!$eth->eth_protocolVersion()) {
            die('钱包链接失败！');
        }

        foreach ($myzrInfo as $k => $v) {

            if ($v['coinname'] == 'usdt') {
                continue;
            }

            $balence = $eth->eth_getBalance($v['username']);
            $tokenFee = bcmul($coin['gas'] , $coin['gasprice']);
            if ($balence < $eth->real_banlance($tokenFee)) {
                echo "eth手续费不足:";
                continue;
            }else{
                if ($v['coinname'] == 'usdt') {
                    $tokenJson = $eth->eth_contract_getBalance($coin['contact_address'],$v['username']);
                    $gas = $eth->encode_dec($coin['gas']);
                    $gasPrice = $eth->encode_dec($coin['gasprice']);
                    $dizhi = $eth->fill_zero($coin['dj_mian_address']);
                    $numreal = bcmul($tokenJson , $coin['unit']);
                    $shuliang = $eth->encode_sixteen($numreal);
                    $real_num = $eth->fill_Zero($shuliang);
                    $inputdata = $coin['method_id'] . $dizhi . $real_num;

                    $userCoin = M('UserCoin')->where(array(
                        'usdtb' => $v['username']
                    ))->find();

                    $sendrs = $eth->eth_sendTransaction($v['username'], $userCoin['usdts'], 0, $coin['contact_address'], false, $inputdata, $gas, $gasPrice);
                }

                if ($v['coinname'] == 'eth') {
                    $userCoin = M('UserCoin')->where(array(
                        'ethb' => $v['username']
                    ))->find();
                    $balence = $eth->eth_getBalance($v['username']);
                    $tokenFee = bcmul($coin['eth_gas'] , $coin['eth_gasprice']);
                    $ethFee = $eth->real_banlance($tokenFee);
                    $realnum = $balence - $ethFee;

                    $sendrs = $eth->eth_sendTransaction($v['username'], $userCoin['eths'], $realnum, $coin['dj_mian_address'], false, "", $eth->encode_dec($coin['eth_gas']),$eth->encode_dec($coin['eth_gasprice']));
                }

                if ($sendrs) {
                    $res = M('SendHot')->add(array(
                        'userid' => $v['userid'],
                        'fromaddr' => $v['username'],
                        'toaddr' => $coin['dj_mian_address'],
                        'addtime' => date('Y-m-d H:i:s' , time()),
                        'status' => 1,
                        'num' => $tokenJson,
                        'hash' => $sendrs,
                        'coinname' => $v['coinname']
                    ));


                    foreach ($arrid as $v1) {
                        if ($v1['username'] == $v['username']) {
                            $rs = M('Myzr')->where(array(
                                'id' => $v1['id']
                            ))->setField('is_send_hotwallet', 'F');
                        }
                    }

                    if ($rs) {
                        echo "转入主钱包成功";
                    } else {
                        echo "转入主钱包失败";
                    }
                } else {
                    echo "eth转账失败";

                }
            }
        }
    }


    public function autoDeleteAdver() {
        $adver = M('Adver')->where(array(
            'status' => 1
        ))->select();

        foreach ($adver as $k => $v) {

            $userCoin = M('UserCoin')->where(array(
                'userid' => $v['userid']
            ))->find();

            if (($v['min_limit'] / $v['price']) > $userCoin[$v['coin_type']]) {

                $tradeData = M('Trade')->where('adver_id = ' . $v['id'] . ' and order_status >= 1')->find();
                if ($tradeData) {
                    continue;
                }else{
                    $rs = M('Adver')->where(array(
                        'id' => $v['id']
                    ))->setField('status' , 0);

                    if ($rs) {
                        echo "关闭成功";
                    }else{
                        echo "关闭失败";
                    }
                }
            }
        }
    }

    public function sctonum($num, $double = 6){
        if(false !== stripos($num, "e")){
            $a = explode("e",strtolower($num));
            return bcmul($a[0], bcpow(10, $a[1], $double), $double);
        }
    }

    public function qianbao()
    {
        $coinList = M('Coin')->where(array(
            'status' => 1
        ))->select();

        foreach ($coinList as $k => $v) {
            if ($v['type'] != 'qbb') {
                continue;
            }

            $coin = $v['name'];

            if (!$coin) {
                echo 'MM';
                continue;
            }

            $dj_username = $v['dj_yh'];
            $dj_password = $v['dj_mm'];
            $dj_address = $v['dj_zj'];
            $dj_port = $v['dj_dk'];
            echo 'start ' . $coin . "\n";
            $CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);
            $json = $CoinClient->getinfo();

            if (!isset($json['version']) || !$json['version']) {
                echo '###ERR#####***** ' . $coin . ' connect fail***** ####ERR####>' . "<br/>";
                continue;
            }

            echo 'Cmplx ' . $coin . ' start,connect ' . (empty($CoinClient) ? 'fail' : 'ok') . ' :' . "<br/>";
            $listtransactions = $CoinClient->listtransactions('*', 100, 0);

            echo 'listtransactions:' . count($listtransactions) . "<br/>";
            krsort($listtransactions);

            foreach ($listtransactions as $trans) {
                if (!$trans['account']) {
                    echo 'empty account continue' . "<br/>";
                    continue;
                }

                if (!($user = M('User')->where(array(
                    'email' => $trans['account']
                ))->find())
                ) {
                    echo 'no account find continue' . "<br/>";
                    continue;
                }

                if (M('Myzr')->where(array(
                    'txid' => $trans['txid'],
                    'status' => '1'
                ))->find()
                ) {
                    echo 'txid had found continue' . "<br/>";
                    continue;
                }

                echo 'all check ok ' . "<br/>";

                if ($trans['category'] == 'receive') {
                    echo 'start receive do:' . "<br/>";
                    $sfee = 0;
                    $true_amount = $trans['amount'];


                    if ($trans['confirmations'] < $v['zr_dz']) {
                        echo $trans['account'] . ' confirmations ' . $trans['confirmations'] . ' not elengh ' . C('Coin')[$coin]['zr_dz'] . ' continue ' . "<br/>";
                        echo 'confirmations <  c_zr_dz continue' . "<br/>";

                        if ($res = M('myzr')->where(array(
                            'txid' => $trans['txid']
                        ))->find()
                        ) {
                            M('myzr')->save(array(
                                'id' => $res['id'],
                                'addtime' => time(),
                                'status' => intval($trans['confirmations'] - C('Coin')[$coin]['zr_dz'])
                            ));
                        } else {
                            M('myzr')->add(array(
                                'userid' => $user['id'],
                                'username' => $trans['address'],
                                'coinname' => $coin,
                                'fee' => $sfee,
                                'txid' => $trans['txid'],
                                'num' => $true_amount,
                                'mum' => $trans['amount'],
                                'addtime' => time(),
                                'status' => intval($trans['confirmations'] - C('Coin')[$coin]['zr_dz']),
                                'is_send_hotwallet' => 'F',
                                'is_apply_fee' => 'S'
                            ));
                        }

                        continue;
                    } else {
                        echo 'confirmations full' . "<br/>";
                    }

                    $mo = M();
                    $mo->execute('set autocommit=0');
                    $mo->execute('lock tables  trade_user_coin write , trade_myzr  write');
                    $rs = array();
                    $rs[] = $mo->table('trade_user_coin')
                        ->where(array(
                            'userid' => $user['id']
                        ))
                        ->setInc($coin, $trans['amount']);

                    if ($res = $mo->table('trade_myzr')
                        ->where(array(
                            'txid' => $trans['txid']
                        ))
                        ->find()
                    ) {
                        echo 'trade_myzr find and set status 1';
                        $rs[] = $mo->table('trade_myzr')->save(array(
                            'id' => $res['id'],
                            'addtime' => time(),
                            'status' => 1
                        ));
                    } else {
                        echo 'trade_myzr not find and add a new hketrade_myzr' . "<br/>";
                        $rs[] = $mo->table('trade_myzr')->add(array(
                            'userid' => $user['id'],
                            'username' => $trans['address'],
                            'coinname' => $coin,
                            'fee' => $sfee,
                            'txid' => $trans['txid'],
                            'num' => $true_amount,
                            'mum' => $trans['amount'],
                            'addtime' => time(),
                            'status' => 1,
                            'is_send_hotwallet' => 'F',
                            'is_apply_fee' => 'S'
                        ));
                    }

                    if (check_arr($rs)) {
                        $mo->execute('commit');
                        echo $trans['amount'] . ' receive ok ' . $coin . ' ' . $trans['amount'];
                        $mo->execute('unlock tables');
                        echo 'commit ok' . "<br/>";
                    } else {
                        echo $trans['amount'] . 'receive fail ' . $coin . ' ' . $trans['amount'];
                        echo var_export($rs, true);
                        $mo->execute('rollback');
                        $mo->execute('unlock tables');
                        echo 'rollback ok' . "<br/>";
                    }
                }
            }

            if ($trans['category'] == 'send') {
                echo 'start send do:' . "<br/>";

                if (3 <= $trans['confirmations']) {
                    $myzc = M('Myzc')->where(array(
                        'txid' => $trans['txid']
                    ))->find();

                    if ($myzc) {
                        if ($myzc['status'] == 0) {
                            M('Myzc')->where(array(
                                'txid' => $trans['txid']
                            ))->save(array(
                                'status' => 1
                            ));
                            echo $trans['amount'] . '成功转出' . $coin . ' 币确定';
                        }
                    }
                }
            }
        }
    }
}
