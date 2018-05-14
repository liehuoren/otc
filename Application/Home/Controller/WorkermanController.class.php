<?php

namespace Home\Controller;

use Think\Controller;
use Workerman\Worker;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Lib\Timer;

class WorkerManController
{

    protected $socket = 'websocket://0.0.0.0:2346';
    protected $processes = 1;
    protected $uidConnections = array();

    private $uid = [];

    public function chat()
    {
        if (!IS_CLI) {
            die("无法直接访问，请通过命令行启动");
        }
        $clients = [];

        $users = [];

        $worker = new \Workerman\Worker('websocket://0.0.0.0:2346');

        $this->count = 3;


        $worker->onConnect = function ($connection) {

            global $clients;

        };

        $worker->onMessage = function ($connection, $data) {
            $arr = explode(',', $data);
            global $clients;

            if ($arr[0] == 'login') {

                if (!isset($connection->uid)) {
                    //没有验证的话把第一个包当作uid
                    $connection->uid = 'u' . $arr[1] . 'd' . $arr[2];

                    /**
                     * 保存uid到connection的映射，这样科研方便的通过uid 查找connection
                     * 实现对特定uid推送数据
                     */
                    $this->uidConnections[$connection->uid] = $connection;
                    //return;
                }
//                $key = array();
//                foreach ($this->uidConnections as $k => $v) {
//                    foreach ($v as $a => $b) {
//                        $key[$a] = $a .'----'. $b;
//                        dump($b);
//                    }
//                }
//                dump($key);

                $tradeInfo = M('Trade')->where(array(
                    'id' => $arr[2]
                ))->find();
                $chatLogs = M('Chat')->where(array(
                    'trade_id' => $arr[2],
                    'status' => 0,
                ))->order('addtime asc')->select();

                if ($chatLogs) {
                    foreach ($chatLogs as $k => $v) {
                        if ($v['userid'] == $arr[1]) {
                            $chatLogs[$k]['symbol'] = 1;
                        }

                        if ($v['chatid'] == $arr[1]) {
                            $chatLogs[$k]['symbol'] = 2;
                        }
                    }
                    $msg = array(array(
                        'msg' => '买家已拍下，等待买家付款',
                        'order_status' => $tradeInfo['order_status'],
                        'symbol' => 0,
                        'chatlogs' => $chatLogs
                    ));
                }else{
                    $chatLogs = M('Chat')->where(array(
                        'status' => 1,
                        'trade_id' => $arr[2]
                    ))->order('addtime asc')->select();

                    if ($chatLogs) {
                        foreach ($chatLogs as $k => $v) {
                            if ($v['userid'] == $arr[1]) {
                                $chatLogs[$k]['symbol'] = 1;
                            }

                            if ($v['chatid'] == $arr[1]) {
                                $chatLogs[$k]['symbol'] = 2;
                            }
                        }

                    }else{
                        $chatLogs =array();
                    }

                    $msg = array(array(
                        'msg' => '买家已拍下，等待买家付款',
                        'order_status' => $tradeInfo['order_status'],
                        'symbol' => 0,
                        'chatlogs' => $chatLogs
                    ));
                }

                $uid = $arr[1];
//                foreach ($this->uidConnections as $k => $v){
//                    dump($k." ---  ".$v);
//                }
//                dump($this->uidConnections[$connection->uid]);

                $connection = $this->uidConnections['u' . $arr[1] . 'd' . $arr[2]];
                $content = json_encode($msg);
                $connection->send($content);


                $rs = M('Chat')->where(array(
                    'trade_id' => $arr[2],
                    'status' => 0
                ))->setField('status' , 1);

            }
            if ($arr[0] == 'chat') {
                $tradeid = $arr[3];

                $msg = array();

                $tradeData = M('Trade')->where(array(
                    'id' => $tradeid
                ))->find();
//                if (!isset($connection->uid)) {
//                    //没有验证的话把第一个包当作uid
//                    $connection->uid = $tradeData['userid'];
//                    /**
//                     * 保存uid到connection的映射，这样科研方便的通过uid 查找connection
//                     * 实现对特定uid推送数据
//                     */
//                    $this->uidConnections[$connection->uid] = $connection;
//
//                    $connection->uid = $tradeData['trade_id'];
//                    $this->uidConnections[$connection->uid] = $connection;
//                    //return;
//                }

                if ($arr[1] == $tradeData['userid']) {
                    $uid = $tradeData['trade_id'];

                    $msg['symbol'] = 1;
                }

                if ($arr[1] == $tradeData['trade_id']) {
                    $uid = $tradeData['userid'];

                    $msg['symbol'] = 2;
                }

                if ($arr[4] == '') {
                    $content = '';
                    $img = $arr[5];
                }else{
                    $content = $arr[4];
                    $img = '';
                }
//                dump($uid);
//                $aa = array();
//                foreach ($this->uidConnections[$uid] as $k => $v) {
//                    foreach ($v as $a => $s) {
//                        dump($a .$s) ;
//                    }
//                }
//                dump($aa);
                if ($this->uidConnections['u' .$uid . 'd' .$arr[3]] != null) {
                    M('Chat')->add(array(
                        'userid' => $arr[1],
                        'chatid' => $uid,
                        'content' => $content,
                        'addtime' => time(),
                        'status' => 1,
                        'trade_id' => $arr[3],
                        'img' => $img
                    ));

                    $msg['userid'] = $arr[1];

                    if ($arr[4] == '') {
                        $msg['img'] = $arr[5];
                    }else{
                        $msg['msg'] = $arr[4];
                    }

                    $msg['addtime'] = time();
//                    dump($connection['uid']);
                    $connection = $this->uidConnections['u' .$uid . 'd' .$arr[3]];
                    $content = json_encode($msg);
                    $connection->send($content);
                }else{
                    M('Chat')->add(array(
                        'userid' => $arr[1],
                        'chatid' => $uid,
                        'content' => $content,
                        'addtime' => time(),
                        'status' => 0,
                        'trade_id' => $arr[3],
                        'img' => $img
                    ));
                }
//                if (isset($this->uidConnections[$uid])) {
//                    M('Chat')->add(array(
//                        'userid' => $arr[1],
//                        'chatid' => $uid,
//                        'content' => $content,
//                        'addtime' => time(),
//                        'status' => 1,
//                        'trade_id' => $arr[3],
//                        'img' => $img
//                    ));
//
//                    $msg['userid'] = $arr[1];
//
//                    if ($arr[4] == '') {
//                        $msg['img'] = $arr[5];
//                    }else{
//                        $msg['msg'] = $arr[4];
//                    }
//
//
//                    $msg['addtime'] = time();
//                    $connection = $this->uidConnections[$uid];
//                    $content = json_encode($msg);
//                    $connection->send($content);
//
//                } else {
//                    M('Chat')->add(array(
//                        'userid' => $arr[1],
//                        'chatid' => $uid,
//                        'content' => $content,
//                        'addtime' => time(),
//                        'status' => 0,
//                        'trade_id' => $arr[3],
//                        'img' => $img
//                    ));
//                }
            }
            if ($arr[0] == 'symbolPay') {
//                if (!isset($connection->uid)) {
//                    //没有验证的话把第一个包当作uid
//                    $connection->uid = $arr[1];
//                    /**
//                     * 保存uid到connection的映射，这样科研方便的通过uid 查找connection
//                     * 实现对特定uid推送数据
//                     */
//                    $this->uidConnections[$connection->uid] = $connection;
//                    //return;
//                }
                $tradeid = $arr[3];
                $tradeData = M('Trade')->where(array(
                    'id' => $tradeid
                ))->find();

                if ($tradeData['type'] == 1){
                    $uid = $tradeData['trade_id'];
                    if ($this->uidConnections['u' .$uid . 'd' .$arr[3]] != null) {
                        $connection = $this->uidConnections['u' . $uid . 'd' . $arr[3]];
                        $msg = array(
                            'code' => 'success',
                            'time' => time(),
                            'order_status' => $tradeData['order_status'],
                            'msg' => '买家已付款，等待卖家放行USDP'
                        );
                        $content = json_encode($msg);
                        $connection->send($content);

                        $uid = $tradeData['userid'];

                        $connection = $this->uidConnections['u' . $uid . 'd' . $arr[3]];
                        $msg = array(
                            'code' => 'success',
                            'time' => time(),
                            'order_status' => $tradeData['order_status'],
                            'msg' => '买家已付款，等待卖家放行USDP'
                        );
                        M('Chat')->add(array(
                            'content' => '买家已付款，等待卖家放行USDP',
                            'addtime' => time(),
                            'status' => 1,
                            'trade_id' => $arr[3],
                            'system' => 1
                        ));
                        $content = json_encode($msg);
                        $connection->send($content);
                    }else{
                        M('Chat')->add(array(
                            'content' => '买家已付款，等待卖家放行USDP',
                            'addtime' => time(),
                            'status' => 0,
                            'trade_id' => $arr[3],
                            'system' => 1
                        ));

                    }
                }

                if ($tradeData['type'] == 2) {
                    $uid = $tradeData['userid'];
                    if ($this->uidConnections['u' .$uid . 'd' .$arr[3]] != null) {
                        $connection = $this->uidConnections['u' . $uid . 'd' . $arr[3]];
                        $msg = array(
                            'code' => 'success',
                            'time' => time(),
                            'order_status' => $tradeData['order_status'],
                            'msg' => '买家已付款，等待卖家放行USDP'
                        );
                        $content = json_encode($msg);
                        $connection->send($content);

                        $uid = $tradeData['trade_id'];

                        $connection = $this->uidConnections['u' . $uid . 'd' . $arr[3]];
                        $msg = array(
                            'code' => 'success',
                            'time' => time(),
                            'order_status' => $tradeData['order_status'],
                            'msg' => '买家已付款，等待卖家放行USDP'
                        );
                        M('Chat')->add(array(
                            'content' => '买家已付款，等待卖家放行USDP',
                            'addtime' => time(),
                            'status' => 1,
                            'trade_id' => $arr[3],
                            'system' => 1
                        ));
                        $content = json_encode($msg);
                        $connection->send($content);
                    }else{
                        M('Chat')->add(array(

                            'content' => '买家已付款，等待卖家放行USDP',
                            'addtime' => time(),
                            'status' => 0,
                            'trade_id' => $arr[3],
                            'system' => 1
                        ));
                    }
                }

            }
            if ($arr[0] == 'sendCoin') {
//                if (!isset($connection->uid)) {
//                    //没有验证的话把第一个包当作uid
//                    $connection->uid = $arr[1];
//                    /**
//                     * 保存uid到connection的映射，这样科研方便的通过uid 查找connection
//                     * 实现对特定uid推送数据
//                     */
//                    $this->uidConnections[$connection->uid] = $connection;
//                    //return;
//                }
                $tradeid = $arr[3];
                $tradeData = M('Trade')->where(array(
                    'id' => $tradeid
                ))->find();

                if ($tradeData['type'] == 1) {
                    $uid = $tradeData['userid'];
                    if ($this->uidConnections['u' .$uid . 'd' .$arr[3]] != null) {
                        $connection = $this->uidConnections['u' . $uid . 'd' . $arr[3]];
                        $msg = array(
                            'code' => 'success',
                            'time' => time(),
                            'order_status' => $tradeData['order_status'],
                            'msg' => '交易完成，卖家已放行USDP'
                        );
                        $content = json_encode($msg);
                        $connection->send($content);

                        $uid = $tradeData['trade_id'];

                        $connection = $this->uidConnections['u' . $uid . 'd' . $arr[3]];
                        $msg = array(
                            'code' => 'success',
                            'time' => time(),
                            'order_status' => $tradeData['order_status'],
                            'msg' => '交易完成，卖家已放行USDP'
                        );
                        M('Chat')->add(array(

                            'content' => '交易完成，卖家已放行USDP',
                            'addtime' => time(),
                            'status' => 1,
                            'trade_id' => $arr[3],
                            'system' => 1
                        ));

                        $content = json_encode($msg);
                        $connection->send($content);
                    }else{
                        M('Chat')->add(array(
                            'content' => '交易完成，卖家已放行USDP',
                            'addtime' => time(),
                            'status' => 0,
                            'trade_id' => $arr[3],
                            'system' => 1
                        ));
                    }
                }

                if ($tradeData['type'] == 2) {
                    $uid = $tradeData['trade_id'];
                    if ($this->uidConnections['u' .$uid . 'd' .$arr[3]] != null) {
                        $connection = $this->uidConnections['u' . $uid . 'd' . $arr[3]];
                        $msg = array(
                            'code' => 'success',
                            'time' => time(),
                            'order_status' => $tradeData['order_status'],
                            'msg' => '交易完成，卖家已放行USDP'
                        );
                        $content = json_encode($msg);
                        $connection->send($content);

                        $uid = $tradeData['userid'];

                        $connection = $this->uidConnections['u' . $uid . 'd' . $arr[3]];
                        $msg = array(
                            'code' => 'success',
                            'time' => time(),
                            'order_status' => $tradeData['order_status'],
                            'msg' => '交易完成，卖家已放行USDP'
                        );
                        M('Chat')->add(array(
                            'content' => '交易完成，卖家已放行USDP',
                            'addtime' => time(),
                            'status' => 1,
                            'trade_id' => $arr[3],
                            'system' => 1
                        ));

                        $content = json_encode($msg);
                        $connection->send($content);
                    }else {
                        M('Chat')->add(array(
                            'content' => '交易完成，卖家已放行USDP',
                            'addtime' => time(),
                            'status' => 0,
                            'trade_id' => $arr[3],
                            'system' => 1
                        ));

                    }
                }
            }
            if ($arr[0] == 'appeal') {
//                if (!isset($connection->uid)) {
//                    //没有验证的话把第一个包当作uid
//                    $connection->uid = $arr[1];
//                    /**
//                     * 保存uid到connection的映射，这样科研方便的通过uid 查找connection
//                     * 实现对特定uid推送数据
//                     */
//                    $this->uidConnections[$connection->uid] = $connection;
//                    //return;
//                }
                $tradeid = $arr[3];
                $tradeData = M('Trade')->where(array(
                    'id' => $tradeid
                ))->find();
                if ($tradeData['type'] == 1) {
                    if ($tradeData['userid'] == $arr[1]){
                        $uid = $tradeData['userid'];
                        if ($this->uidConnections['u' .$uid . 'd' .$arr[3]] != null) {
                            $connection = $this->uidConnections['u' . $uid . 'd' . $arr[3]];
                            $msg = array(
                                'code' => 'success',
                                'time' => time(),
                                'order_status' => $tradeData['order_status'],
                                'msg' => '买家已经发起申诉'
                            );
                            $content = json_encode($msg);
                            $connection->send($content);

                            $uid = $tradeData['trade_id'];

                            $connection = $this->uidConnections['u' . $uid . 'd' . $arr[3]];
                            $msg = array(
                                'code' => 'success',
                                'time' => time(),
                                'order_status' => $tradeData['order_status'],
                                'msg' => '买家已经发起申诉'
                            );
                            M('Chat')->add(array(
                                'content' => '买家已经发起申诉',
                                'addtime' => time(),
                                'status' => 1,
                                'trade_id' => $arr[3],
                                'system' => 1
                            ));
                            $content = json_encode($msg);
                            $connection->send($content);
                        }else{
                            M('Chat')->add(array(
                                'content' => '买家已经发起申诉',
                                'addtime' => time(),
                                'status' => 0,
                                'trade_id' => $arr[3],
                                'system' => 1
                            ));
                        }
                    }

                    if ($tradeData['trade_id'] == $arr[1]){
                        $uid = $tradeData['userid'];
                        if ($this->uidConnections['u' .$uid . 'd' .$arr[3]] != null) {
                            $connection = $this->uidConnections['u' . $uid . 'd' . $arr[3]];
                            $msg = array(
                                'code' => 'success',
                                'time' => time(),
                                'order_status' => $tradeData['order_status'],
                                'msg' => '卖家已经发起申诉'
                            );
                            $content = json_encode($msg);
                            $connection->send($content);

                            $uid = $tradeData['trade_id'];

                            $connection = $this->uidConnections['u' . $uid . 'd' . $arr[3]];
                            $msg = array(
                                'code' => 'success',
                                'time' => time(),
                                'order_status' => $tradeData['order_status'],
                                'msg' => '卖家已经发起申诉'
                            );
                            M('Chat')->add(array(
                                'content' => '卖家已经发起申诉',
                                'addtime' => time(),
                                'status' => 1,
                                'trade_id' => $arr[3],
                                'system' => 1
                            ));
                            $content = json_encode($msg);
                            $connection->send($content);
                        }else{
                            M('Chat')->add(array(
                                'content' => '卖家已经发起申诉',
                                'addtime' => time(),
                                'status' => 0,
                                'trade_id' => $arr[3],
                                'system' => 1
                            ));
                        }
                    }
                }

                if ($tradeData['type'] == 2) {
                    if ($tradeData['userid'] == $arr[1]) {
                        $uid = $tradeData['trade_id'];
                        if ($this->uidConnections['u' .$uid . 'd' .$arr[3]] != null) {
                            $connection = $this->uidConnections['u' . $uid . 'd' . $arr[3]];
                            $msg = array(
                                'code' => 'success',
                                'time' => time(),
                                'order_status' => $tradeData['order_status'],
                                'msg' => '卖家已经发起申诉'
                            );
                            $content = json_encode($msg);
                            $connection->send($content);

                            $uid = $tradeData['userid'];

                            $connection = $this->uidConnections['u' . $uid . 'd' . $arr[3]];
                            $msg = array(
                                'code' => 'success',
                                'time' => time(),
                                'order_status' => $tradeData['order_status'],
                                'msg' => '卖家已经发起申诉'
                            );
                            M('Chat')->add(array(
                                'content' => '卖家已经发起申诉',
                                'addtime' => time(),
                                'status' => 1,
                                'trade_id' => $arr[3],
                                'system' => 1
                            ));
                            $content = json_encode($msg);
                            $connection->send($content);
                        }else{
                            M('Chat')->add(array(
                                'content' => '卖家已经发起申诉',
                                'addtime' => time(),
                                'status' => 0,
                                'trade_id' => $arr[3],
                                'system' => 1
                            ));
                        }
                    }

                    if ($tradeData['trade_id'] == $arr[1]) {
                        $uid = $tradeData['trade_id'];
                        if ($this->uidConnections['u' .$uid . 'd' .$arr[3]] != null) {
                            $connection = $this->uidConnections['u' . $uid . 'd' . $arr[3]];
                            $msg = array(
                                'code' => 'success',
                                'time' => time(),
                                'order_status' => $tradeData['order_status'],
                                'msg' => '买家已经发起申诉'
                            );
                            $content = json_encode($msg);
                            $connection->send($content);

                            $uid = $tradeData['userid'];

                            $connection = $this->uidConnections['u' . $uid . 'd' . $arr[3]];
                            $msg = array(
                                'code' => 'success',
                                'time' => time(),
                                'order_status' => $tradeData['order_status'],
                                'msg' => '买家已经发起申诉'
                            );
                            M('Chat')->add(array(
                                'content' => '买家已经发起申诉',
                                'addtime' => time(),
                                'status' => 1,
                                'trade_id' => $arr[3],
                                'system' => 1
                            ));
                            $content = json_encode($msg);
                            $connection->send($content);
                        }else{
                            M('Chat')->add(array(
                                'content' => '买家已经发起申诉',
                                'addtime' => time(),
                                'status' => 0,
                                'trade_id' => $arr[3],
                                'system' => 1
                            ));
                        }
                    }
                }
            }

            if ($arr[0] == 'closeAppeal') {
//                if (!isset($connection->uid)) {
//                    //没有验证的话把第一个包当作uid
//                    $connection->uid = $arr[1];
//                    /**
//                     * 保存uid到connection的映射，这样科研方便的通过uid 查找connection
//                     * 实现对特定uid推送数据
//                     */
//                    $this->uidConnections[$connection->uid] = $connection;
//                    //return;
//                }
                $tradeid = $arr[3];
                $tradeData = M('Trade')->where(array(
                    'id' => $tradeid
                ))->find();
                if ($tradeData['type'] == 1) {
                    if ($tradeData['userid'] == $arr[1]){
                        $uid = $tradeData['userid'];
                        if ($this->uidConnections['u' .$uid . 'd' .$arr[3]] != null) {
                            $connection = $this->uidConnections['u' . $uid . 'd' . $arr[3]];
                            $msg = array(
                                'code' => 'success',
                                'time' => time(),
                                'order_status' => $tradeData['order_status'],
                                'msg' => '买家已经取消申诉'
                            );
                            $content = json_encode($msg);
                            $connection->send($content);

                            $uid = $tradeData['trade_id'];

                            $connection = $this->uidConnections['u' . $uid . 'd' . $arr[3]];
                            $msg = array(
                                'code' => 'success',
                                'time' => time(),
                                'order_status' => $tradeData['order_status'],
                                'msg' => '买家已经取消申诉'
                            );
                            M('Chat')->add(array(
                                'content' => '买家已经取消申诉',
                                'addtime' => time(),
                                'status' => 1,
                                'trade_id' => $arr[3],
                                'system' => 1
                            ));
                            $content = json_encode($msg);
                            $connection->send($content);
                        }else{
                            M('Chat')->add(array(
                                'content' => '买家已经取消申诉',
                                'addtime' => time(),
                                'status' => 0,
                                'trade_id' => $arr[3],
                                'system' => 1
                            ));
                        }
                    }

                    if ($tradeData['trade_id'] == $arr[1]){
                        $uid = $tradeData['userid'];
                        if ($this->uidConnections['u' .$uid . 'd' .$arr[3]] != null) {
                            $connection = $this->uidConnections['u' . $uid . 'd' . $arr[3]];
                            $msg = array(
                                'code' => 'success',
                                'time' => time(),
                                'order_status' => $tradeData['order_status'],
                                'msg' => '卖家已经取消申诉'
                            );
                            $content = json_encode($msg);
                            $connection->send($content);

                            $uid = $tradeData['trade_id'];

                            $connection = $this->uidConnections['u' . $uid . 'd' . $arr[3]];
                            $msg = array(
                                'code' => 'success',
                                'time' => time(),
                                'order_status' => $tradeData['order_status'],
                                'msg' => '卖家已经取消申诉'
                            );
                            M('Chat')->add(array(
                                'content' => '卖家已经取消申诉',
                                'addtime' => time(),
                                'status' => 1,
                                'trade_id' => $arr[3],
                                'system' => 1
                            ));
                            $content = json_encode($msg);
                            $connection->send($content);
                        }else{
                            M('Chat')->add(array(
                                'content' => '卖家已经取消申诉',
                                'addtime' => time(),
                                'status' => 0,
                                'trade_id' => $arr[3],
                                'system' => 1
                            ));
                        }
                    }

                }

                if ($tradeData['type'] == 2) {
                    if ($tradeData['userid'] == $arr[1]) {
                        $uid = $tradeData['trade_id'];
                        if ($this->uidConnections['u' .$uid . 'd' .$arr[3]] != null) {
                            $connection = $this->uidConnections['u' . $uid . 'd' . $arr[3]];
                            $msg = array(
                                'code' => 'success',
                                'time' => time(),
                                'order_status' => $tradeData['order_status'],
                                'msg' => '卖家已经取消申诉'
                            );
                            $content = json_encode($msg);
                            $connection->send($content);

                            $uid = $tradeData['userid'];

                            $connection = $this->uidConnections['u' . $uid . 'd' . $arr[3]];
                            $msg = array(
                                'code' => 'success',
                                'time' => time(),
                                'order_status' => $tradeData['order_status'],
                                'msg' => '卖家已经取消申诉'
                            );
                            M('Chat')->add(array(
                                'content' => '卖家已经取消申诉',
                                'addtime' => time(),
                                'status' => 1,
                                'trade_id' => $arr[3],
                                'system' => 1
                            ));
                            $content = json_encode($msg);
                            $connection->send($content);
                        }else{
                            M('Chat')->add(array(
                                'content' => '卖家已经取消申诉',
                                'addtime' => time(),
                                'status' => 0,
                                'trade_id' => $arr[3],
                                'system' => 1
                            ));
                        }
                    }


                    if ($tradeData['trade_id'] == $arr[1]) {
                        $uid = $tradeData['trade_id'];
                        if ($this->uidConnections['u' .$uid . 'd' .$arr[3]] != null) {
                            $connection = $this->uidConnections['u' . $uid . 'd' . $arr[3]];
                            $msg = array(
                                'code' => 'success',
                                'time' => time(),
                                'order_status' => $tradeData['order_status'],
                                'msg' => '买家已经取消申诉'
                            );
                            $content = json_encode($msg);
                            $connection->send($content);

                            $uid = $tradeData['userid'];

                            $connection = $this->uidConnections['u' . $uid . 'd' . $arr[3]];
                            $msg = array(
                                'code' => 'success',
                                'time' => time(),
                                'order_status' => $tradeData['order_status'],
                                'msg' => '买家已经取消申诉'
                            );
                            M('Chat')->add(array(
                                'content' => '买家已经取消申诉',
                                'addtime' => time(),
                                'status' => 1,
                                'trade_id' => $arr[3],
                                'system' => 1
                            ));
                            $content = json_encode($msg);
                            $connection->send($content);
                        }else{
                            M('Chat')->add(array(
                                'content' => '买家已经取消申诉',
                                'addtime' => time(),
                                'status' => 0,
                                'trade_id' => $arr[3],
                                'system' => 1
                            ));
                        }
                    }

                }
            }

            if ($arr[0] == 'closeTrade') {
//                if (!isset($connection->uid)) {
//                    //没有验证的话把第一个包当作uid
//                    $connection->uid = $arr[1];
//                    /**
//                     * 保存uid到connection的映射，这样科研方便的通过uid 查找connection
//                     * 实现对特定uid推送数据
//                     */
//                    $this->uidConnections[$connection->uid] = $connection;
//                    //return;
//                }
                $tradeid = $arr[3];
                $tradeData = M('Trade')->where(array(
                    'id' => $tradeid
                ))->find();
                if ($tradeData['type'] == 1) {
                    if ($tradeData['userid'] == $arr[1]){
//                        if (!isset($connection->uid)) {
//                            //没有验证的话把第一个包当作uid
//                            $connection->uid = 'u' . $arr[1] . 'd' . $arr[3];
//                            /**
//                             * 保存uid到connection的映射，这样科研方便的通过uid 查找connection
//                             * 实现对特定uid推送数据
//                             */
//                            $this->uidConnections[$connection->uid] = $connection;
//                            //return;
//                        }
                        $uid = $tradeData['userid'];
                        if ($this->uidConnections['u' .$uid . 'd' .$arr[3]] != null) {
                            $connection = $this->uidConnections['u' . $uid . 'd' . $arr[3]];
                            $msg = array(
                                'code' => 'success',
                                'time' => time(),
                                'order_status' => $tradeData['order_status'],
                                'msg' => '买家已经取消交易'
                            );
                            $content = json_encode($msg);
                            $connection->send($content);

                            $uid = $tradeData['trade_id'];

                            $connection = $this->uidConnections['u' . $uid . 'd' . $arr[3]];
                            $msg = array(
                                'code' => 'success',
                                'time' => time(),
                                'order_status' => $tradeData['order_status'],
                                'msg' => '买家已经取消交易'
                            );
                            M('Chat')->add(array(
                                'content' => '买家已经取消交易',
                                'addtime' => time(),
                                'status' => 1,
                                'trade_id' => $arr[3],
                                'system' => 1
                            ));
                            $content = json_encode($msg);
                            $connection->send($content);
                        }else{
                            M('Chat')->add(array(
                                'content' => '买家已经取消交易',
                                'addtime' => time(),
                                'status' => 0,
                                'trade_id' => $arr[3],
                                'system' => 1
                            ));
                        }
                    }

                }

                if ($tradeData['type'] == 2) {
                    if (!isset($connection->uid)) {
                        //没有验证的话把第一个包当作uid
                        $connection->uid = 'u' . $tradeData['trade_id'] . 'd' . $arr[2];
                        /**
                         * 保存uid到connection的映射，这样科研方便的通过uid 查找connection
                         * 实现对特定uid推送数据
                         */
                        $this->uidConnections[$connection->uid] = $connection;
                        //return;
                    }
                    $uid = $tradeData['trade_id'];
                    if ($this->uidConnections['u' .$uid . 'd' .$arr[3]] != null) {
                        $connection = $this->uidConnections['u' . $uid . 'd' . $arr[3]];
                        $msg = array(
                            'code' => 'success',
                            'time' => time(),
                            'order_status' => $tradeData['order_status'],
                            'msg' => '卖家已经取消交易'
                        );
                        $content = json_encode($msg);
                        $connection->send($content);

                        $uid = $tradeData['userid'];

                        $connection = $this->uidConnections['u' . $uid . 'd' . $arr[3]];
                        $msg = array(
                            'code' => 'success',
                            'time' => time(),
                            'order_status' => $tradeData['order_status'],
                            'msg' => '卖家已经取消交易'
                        );
                        M('Chat')->add(array(
                            'content' => '卖家已经取消交易',
                            'addtime' => time(),
                            'status' => 1,
                            'trade_id' => $arr[3],
                            'system' => 1
                        ));
                        $content = json_encode($msg);
                        $connection->send($content);
                    }else{
                        M('Chat')->add(array(
                            'content' => '卖家已经取消交易',
                            'addtime' => time(),
                            'status' => 0,
                            'trade_id' => $arr[3],
                            'system' => 1
                        ));
                    }
                }
            }
//            if ($arr[0] == 'pushMessage') {
//                if (!isset($connection->uid)) {
//                    //没有验证的话把第一个包当作uid
//                    $connection->uid = $arr[1];
//                    /**
//                     * 保存uid到connection的映射，这样科研方便的通过uid 查找connection
//                     * 实现对特定uid推送数据
//                     */
//                    $this->uidConnections[$connection->uid] = $connection;
//                    //return;
//                }
//
//                $messageData = M('UserOperation')->field('id , userid , message , addtime')->where(array(
//                    'status' => 0,
//                    'userid' => $arr[1]
//                ))->select();
//                $uid = $arr[1];
//                $connection = $this->uidConnections['u' .$uid . 'd' .$arr[3]];
//                $content = json_encode($messageData);
//                $connection->send($content);
//            }

//            if ($arr[0] == 'symbolRead') {
////                if (!isset($connection->uid)) {
////                    //没有验证的话把第一个包当作uid
////                    $connection->uid = $arr[2];
////                    /**
////                     * 保存uid到connection的映射，这样科研方便的通过uid 查找connection
////                     * 实现对特定uid推送数据
////                     */
////                    $this->uidConnections[$connection->uid] = $connection;
////                    //return;
////                }
//
//                $rs = M('UserOperation')->where(array(
//                    'id' => $arr[1]
//                ))->setField('status' , 1);
//            }
        };

        $worker->onError = function($connection, $code, $msg)
        {
            echo "error $code $msg\n";
        };

        $worker->onClose = function ($connection) //客户端主动关闭
        {

            global $this;
            if (isset($connection->uid)) {
                //连接断开是删除映射
                unset($this->uidConnections[$connection->uid]);
            }

        };
        // 运行worker
        Worker::runAll();
    }


    public function onWorkerStart($worker)
    {
        //开启一个内部断开，方便内部系统推送数据，Text协议格式，文本+换行符
        $inner_text_worder = new \Workerman\Worker('text://0.0.0.0:5678');
        $inner_text_worder->onMessage = function ($connection, $buffer) {
            echo 'hhh';
            //$data数组格式，里面有uid，表示向那个uid的页面推送数据
            $data = json_decode($buffer, true);
            $uid = $data['uid'];
            //通过workerman向uid的页面推送数据
            $ret = $this->sendMessageByUid($uid, $buffer);
            //返回推送结果
            $connection->send($ret ? 'ok' : 'fail');
        };
        //执行监听
        $inner_text_worder->listen();
    }

    public function syncUsers()
    {
        global $clients;
        $users = 'users:' . json_encode(array_column($clients, 'userid', 'ip')); //准备要广播的数据
        foreach ($clients as $ip => $client) {
            $client['conn']->send($users);
        }
    }

    public function onWorkerReload($worker)
    {
        foreach ($worker->connections as $connection) {
            $connection->send('worker reloading');
        }
    }

    //向所有验证的用户推送数据
    function broadcast($message)
    {
        foreach ($this->uidConnections as $connection) {
            $connection->send($message);
        }
    }

    //针对uid推送数据
    function sendMessageByUid($uid, $message)
    {
        if (isset($this->uidConnections[$uid])) {
            $connection = $this->uidConnections[$uid];
            $connection->send($message);
            return true;
        }
        return false;

    }

    public function onClose($connection)
    {
        global $this;
        if (isset($connection->uid)) {
            //连接断开是删除映射
            unset($this->uidConnections[$connection->uid]);
        }
    }


    public function onMessage($connection, $data)
    {

        //判断当前客户端是否已经验证，即是否设置啦uid
        if (!isset($connection->uid)) {
            //没有验证的话把第一个包当作uid
            $connection->uid = $data;
            /**
             * 保存uid到connection的映射，这样科研方便的通过uid 查找connection
             * 实现对特定uid推送数据
             */
            $this->uidConnections[$connection->uid] = $connection;
            return;
        }
    }
}


