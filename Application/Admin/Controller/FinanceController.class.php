<?php

namespace Admin\Controller;

use Think\Controller;

class FinanceController extends Controller
{
    public function myzr($page, $username = NULL, $starttime = NULL, $endtime = NULL)
    {
        if ($username) {
            $username=trim($username);
            $where['userid'] = M('User')->where(array(
                'username' => $username
            ))->getField('id');
        }

        if (!empty($starttime) && !empty($endtime)) {
            $where['_string'] = ' addtime > ' . strtotime($starttime) . ' AND addtime < ' . strtotime($endtime . ' 23:59:59');
        } else {
            if (!empty($starttime)) {
                $where['addtime'] = array(
                    'GT',
                    strtotime($starttime)
                );
            } else
                if (!empty($endtime)) {
                    $where['addtime'] = array(
                        'LT',
                        strtotime($endtime . ' 23:59:59')
                    );
                }
        }

        $list = M('Myzr')
            ->field('username,id,userid,num,addtime,status,coinname')
            ->where($where)
            ->order('id desc')
            ->limit(($page - 1) * 15, 15)
            ->select();

        $total = M('Myzr')
            ->field('username,id,userid,num,addtime,status,coinname')
            ->where($where)
            ->order('id desc')
            ->select();

        foreach ($list as $k => $v) {
            $list[$k]['usernamea'] = M('User')->where(array(
                'id' => $v['userid']
            ))->getField('username');
        }

        $data = array();

        $data['list'] = $list;
        $data['total'] = count($total);

        $this->ajaxReturn($data, 'JSON');
    }

    public function myzc($page, $username = NULL, $starttime = NULL, $endtime = NULL)
    {
        if ($username) {
            $username=trim($username);
            $where['userid'] = M('User')->where(array(
                'username' => $username
            ))->getField('id');
        }
        if (!empty($starttime) && !empty($endtime)) {
            $where['_string'] = ' addtime > ' . strtotime($starttime) . ' AND addtime < ' . strtotime($endtime . ' 23:59:59');
        } else {
            if (!empty($starttime)) {
                $where['addtime'] = array(
                    'GT',
                    strtotime($starttime)
                );
            } else
                if (!empty($endtime)) {
                    $where['addtime'] = array(
                        'LT',
                        strtotime($endtime . ' 23:59:59')
                    );
                }
        }

        $list = M('Myzc')
            ->field('username,id,userid,num,addtime,status,coinname,mum,fee')
            ->where($where)
            ->order('id desc')
            ->limit(($page - 1) * 15, 15)
            ->select();

        $total = M('Myzc')
            ->field('username,id,userid,num,addtime,status,coinname,mum,fee')
            ->where($where)
            ->order('id desc')
            ->select();

        foreach ($list as $k => $v) {
            $list[$k]['usernamea'] = M('User')->where(array(
                'id' => $v['userid']
            ))->getField('username');
        }
        //$list['total'] = count($total);

        $data = array();

        $data['list'] = $list;
        $data['total'] = count($total);
        $this->ajaxReturn($data, 'JSON');
    }

    public function myzcQueren($id)
    {
        $myzc = M('Myzc')->where(array(
            'id' => trim($id)
        ))->find();
        $addr = $myzc['username'];

        if (!$myzc) {
            $this->ajaxError('转出错误！');
        }

        if ($myzc['status']) {
            $this->ajaxError('已经处理过！');
        }

        $coinInfo = M('Coin')->where(array(
            'name' => $myzc['coinname']
        ))->find();
        if ($coinInfo['name'] == 'usd') {
            $dj_address = $coinInfo['dj_zj'];
            $dj_port = $coinInfo['dj_dk'];

            if ($coinInfo['type'] == 'eth') {
                $eth = new \Common\Ext\Ethereum($dj_address, $dj_port);
                if (!$eth->eth_protocolVersion()) {
                    $this->ajaxError('钱包链接失败！');
                }
            }

            $json = $eth->eth_contract_getBalance($coinInfo['contact_address'], $coinInfo['dj_mian_address']);

            if ($json < $myzc['num']) {
                $this->ajaxError('钱包余额不足');
            }

            $ethJson = $eth->eth_getBalance($coinInfo['dj_mian_address']);

            $tokenFee = bcmul($coinInfo['gas'] , $coinInfo['gasprice']);

            $realFee = $eth->real_banlance($tokenFee);

            if ($ethJson < $realFee) {
                $this->ajaxError('以太坊的手续费不足 ，请补充以太坊');
            }
        }

        if ($coinInfo['name'] == 'eth') {
            $dj_address = $coinInfo['dj_zj'];
            $dj_port = $coinInfo['dj_dk'];

            if ($coinInfo['type'] == 'eth') {
                $eth = new \Common\Ext\Ethereum($dj_address, $dj_port);
                if (!$eth->eth_protocolVersion()) {
                    $this->ajaxError('钱包链接失败！');
                }
            }

            $ethjson = $eth->eth_getBalance($coinInfo['dj_mian_address']);

            if ($ethjson < $myzc['num']) {
                $this->ajaxError('钱包余额不足');
            }

            $tokenFee = bcmul($coinInfo['gas'] , $coinInfo['gasprice']);

            $realFee = $eth->real_banlance($tokenFee);

            if ($ethjson + $realFee < $myzc['num']) {
                $this->ajaxError('以太坊的手续费不足 ，请补充以太坊');
            }
        }

        if ($coinInfo['name'] == 'btc') {
            if ($coinInfo['type'] == 'qbb') {
                $dj_username = $coinInfo['dj_yh'];
                $dj_password = $coinInfo['dj_mm'];
                $dj_address = $coinInfo['dj_zj'];
                $dj_port = $coinInfo['dj_dk'];
                $CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);
                $json = $CoinClient->getinfo();
                if (!isset($json['version']) || !$json['version']) {
                    $this->error('钱包链接失败！');
                }

                $valid_res = $CoinClient->validateaddress($addr);

                if (!$valid_res['isvalid']) {
                    $this->error($addr . '不是一个有效的钱包地址！');
                }
            }
        }


        $mo = M();
        $mo->execute('set autocommit=0');
        $mo->execute('lock tables trade_user_coin write  , trade_myzc write  , trade_myzr write , trade_myzc_fee write');
        $rs = array();


        $rs[] = $mo->table('trade_myzc')
            ->where(array(
                'id' => trim($id)
            ))
            ->save(array(
                'status' => 1,
                'endtime' => time()
            ));

        $rs[] = $mo->table('trade_myzc_fee')->add(array(
            'userid' => $myzc['userid'],
            'username' => $myzc['username'],
            'coinname' => $myzc['coinname'],
            'fee' => 0,
            'txid' => '',
            'num' => $myzc['fee'],
            'mum' => $myzc['fee'],
            'addtime' => time(),
            'status' => 1
        ));

        if (check_arr($rs)) {
            switch ($coinInfo['type']) {
                case 'eth':
                    if ($coinInfo['name'] == 'eth') {

                        $sendrs = $eth->eth_sendTransaction($coinInfo['dj_mian_address'], $coinInfo['dj_mian_address_password'], $myzc['mum'], $addr, false, "", $eth->encode_dec($coinInfo['eth_gas']),$eth->encode_dec($coinInfo['eth_gasprice']));
                    }else{
                        $gas = $eth->encode_dec($coinInfo['gas']);
                        $gasPrice = $eth->encode_dec($coinInfo['gasprice']);
                        $dizhi = $eth->fill_zero($addr);
                        $numreal = bcmul($myzc['mum'] , $coinInfo['unit']);
                        $shuliang = $eth->encode_sixteen($numreal);
                        $real_num = $eth->fill_Zero($shuliang);

                        $inputdata = $coinInfo['method_id'] . $dizhi . $real_num;

                        $sendrs = $eth->eth_sendTransaction($coinInfo['dj_mian_address'], $coinInfo['dj_mian_address_password'], 0, $coinInfo['contact_address'], false, $inputdata, $gas, $gasPrice);

                    }
                    if ($sendrs) {
                        $flag = 1;
                        // 记录txid
                        $rs[] = $mo->table('trade_myzc')
                            ->where(array(
                                'id' => trim($id)
                            ))->save(array(
                                'txid' => $sendrs
                            ));
                    } else {
                        $flag = 0;
                    }
                    break;
                case 'qbb':

                    $balance = $json['balance'];

                    if ($balance < $myzc['num']) {
                        $this->ajaxError('钱包余额不足');
                    }

                    $sendrs = $CoinClient->sendtoaddress($addr, floatval($myzc['mum']));
                    if ($sendrs) {
                        $flag = 1;
                        $arr = json_decode($sendrs, true);

                        if (isset($arr['status']) && ($arr['status'] == 0)) {
                            $flag = 0;
                        }
                        if (isset($arr['result']) && $arr['result'] != NULL) {
                            $flag = 1;
                            // 记录txid
                            $rs[] = $mo->table('trade_myzc')
                                ->where(array(
                                    'id' => trim($id)
                                ))
                                ->save(array(
                                    'txid' => $arr['result']
                                ));
                        }
                    } else {
                        $flag = 0;
                    }
                    break;
            }

            if (!$flag) {
                $mo->execute('rollback');
                $mo->execute('unlock tables');
                $this->ajaxError('钱包服务器转出币失败!');
            } else {
                $mo->execute('commit');
                $mo->execute('unlock tables');
                $this->ajaxSuccess('转账成功！');
            }
        } else {
            $mo->execute('rollback');
            $mo->execute('unlock tables');
            $this->ajaxError('转出失败!' . implode('|', $rs) . $myzc['fee']);
        }
    }

//    public function myzcQueren($id)
//    {
//        $myzc = M('Myzc')->where(array(
//            'id' => trim($id)
//        ))->find();
//        $addr = $myzc['username'];
//
//        if (!$myzc) {
//            $this->ajaxError('转出错误！');
//        }
//
//        if ($myzc['status']) {
//            $this->ajaxError('已经处理过！');
//        }
//
//        $coinInfo = M('Coin')->where(array(
//            'name' => $myzc['coinname']
//        ))->find();
//
//        $dj_address = $coinInfo['dj_zj'];
//        $dj_port = $coinInfo['dj_dk'];
//
//        if ($coinInfo['type'] == 'eth') {
//            $eth = new \Common\Ext\Ethereum($dj_address, $dj_port);
//            if (!$eth->eth_protocolVersion()) {
//                $this->ajaxError('钱包链接失败！');
//            }
//        }
//
//        $json = $eth->eth_contract_getBalance($coinInfo['contact_address'], $coinInfo['dj_mian_address']);
//
//        if ($json < $myzc['num']) {
//            $this->ajaxError('钱包余额不足');
//        }
//
//        $ethJson = $eth->eth_getBalance($coinInfo['dj_mian_address']);
//
////        if ($ethJson < $this->sctonum($eth->real_banlance(bcmul($coinInfo['gas'] , $coinInfo['gasprice'])))) {
////            $this->ajaxError('以太坊的手续费不足 ，请补充以太坊');
////        }
//
//        $tokenFee = bcmul($coinInfo['gas'] , $coinInfo['gasprice']);
//
//        //$realFee = $this->sctonum($eth->real_banlance($tokenFee));
//        $realFee = $eth->real_banlance($tokenFee);
//
//        if ($ethJson < $realFee) {
//            $this->ajaxError('以太坊的手续费不足 ，请补充以太坊');
//        }
//
////        $fee_user = M('UserCoin')->where(array(
////            $coin . 'b' => $Coin['zc_user']
////        ))->find();
////        $user_coin = M('UserCoin')->where(array(
////            'userid' => $myzc['userid']
////        ))->find();
//
//        $mo = M();
//        $mo->execute('set autocommit=0');
//        $mo->execute('lock tables  trade_user_coin write  , trade_myzc write  , trade_myzr write');
//        $rs = array();
//
//
//        $rs[] = $mo->table('trade_myzc')
//            ->where(array(
//                'id' => trim($id)
//            ))
//            ->save(array(
//                'status' => 1,
//                'endtime' => time()
//            ));
//
//        if (check_arr($rs)) {
//            switch ($coinInfo['type']) {
//                case 'eth':
//
//                    $gas = $eth->encode_dec($coinInfo['gas']);
//                    $gasPrice = $eth->encode_dec($coinInfo['gasprice']);
//                    $dizhi = $eth->fill_zero($addr);
//                    $numreal = bcmul($myzc['num'] , $coinInfo['unit']);
//                    $shuliang = $eth->encode_sixteen($numreal);
//                    $real_num = $eth->fill_Zero($shuliang);
//
//                    $inputdata = $coinInfo['method_id'] . $dizhi . $real_num;
//
//                    $sendrs = $eth->eth_sendTransaction($coinInfo['dj_mian_address'], $coinInfo['dj_mian_address_password'], 0, $coinInfo['contact_address'], false, $inputdata, $gas, $gasPrice);
//
//                    if ($sendrs) {
//                        $flag = 1;
//                        // 记录txid
//                        $rs[] = $mo->table('trade_myzc')
//                            ->where(array(
//                                'id' => trim($id)
//                            ))->save(array(
//                                'txid' => $sendrs
//                            ));
//                    } else {
//                        $flag = 0;
//                    }
//                    break;
//            }
//
//            if (!$flag) {
//                $mo->execute('rollback');
//                $mo->execute('unlock tables');
//                $this->ajaxError('钱包服务器转出币失败!');
//            } else {
//                $mo->execute('commit');
//                $mo->execute('unlock tables');
//                $this->ajaxSuccess('转账成功！');
//            }
//        } else {
//            $mo->execute('rollback');
//            $mo->execute('unlock tables');
//            $this->ajaxError('转出失败!' . implode('|', $rs) . $myzc['fee']);
//        }
//    }

    public function notPass($id){
        if ($id == '') {
            $this->ajaxError('ID不能为空');
        }

        $myzc = M('myzc')->where(array(
            'id' => trim($id)
        ))->find();

        if (!$myzc) {
            $this->ajaxError('转出错误');
        }

        $m = M();

        $m->execute('set autocommit = 0');
        $m->execute('lock tables trade_myzc write , trade_user_coin write');

        $rs[] = $m->table('trade_user_coin')->where(array(
            'userid' => $myzc['userid']
        ))->setInc($myzc['coinname'] , $myzc['num']);


        $rs[] = $m->table('trade_myzc')->where(array(
            'id' => trim($id)
        ))->setField('status' , '-1');

        if (check_arr($rs)) {
            $m->execute('commit');
            $m->execute('unlock tables');
            $this->ajaxSuccess('操作成功！');
        }else{
            $m->execute('rollback');
            $m->execute('unlock tables');
            $this->ajaxError('操作失败！');
        }
    }


    public function sctonum($num, $double = 6){
        if(false !== stripos($num, "e")){
            $a = explode("e",strtolower($num));
            return bcmul($a[0], bcpow(10, $a[1], $double), $double);
        }
    }
}

?>