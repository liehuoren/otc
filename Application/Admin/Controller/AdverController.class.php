<?php

namespace Admin\Controller;

use Think\Controller;

class AdverController extends Controller
{
    public function index($page = 1,$adver_code=null, $username = NULL, $starttime = NULL, $endtime = NULL)
    {
        if ($username) {
            $username=trim($username);
        $where['a.userid'] = M('User')->where(array(
            'username' => $username
        ))->getField('id');
    }
        if ($adver_code){
            $adver_code=trim($adver_code);
            $where['a.adver_code']=$adver_code;
        }

        if (!empty($starttime) && !empty($endtime)) {
            $where['_string'] = ' a.addtime > ' . strtotime($starttime) . ' AND a.addtime < ' . strtotime($endtime . ' 23:59:59');
        } else {
            if (!empty($starttime)) {
                $where['a.addtime'] = array(
                    'GT',
                    strtotime($starttime)
                );
            } else
                if (!empty($endtime)) {
                    $where['a.addtime'] = array(
                        'LT',
                        strtotime($endtime . ' 23:59:59')
                    );
                }
        }

        $info = M()->table('trade_adver as a')
            ->field('a.id,a.adver_code,a.coin_type,a.num,a.price,a.trade_type,a.pay_type,a.country,a.min_limit,a.max_limit,a.addtime,a.status,b.username')
            ->join('left join trade_user as b on a.userid = b.id')
            ->where($where)->limit(($page-1) * 15 , 15)
            ->select();
        $total = M()->table('trade_adver as a')
            ->field('a.id,a.adver_code,a.coin_type,a.num,a.price,a.trade_type,a.pay_type,a.country,a.min_limit,a.max_limit,a.addtime,a.status,b.username')
            ->join('left join trade_user as b on a.userid = b.id')
            ->where($where)
            ->select();
        foreach ($info as $k => $v){
            $trade=M('trade')->where(array(
                'adver_id'=>$v['id'],
                'status' => 1
            ))->select();

            $info[$k]['count']=count($trade);
        }
        $data = array();

        $data['info'] = $info;
        $data['total'] = count($total);
        //$info['total']['total'] = count($total);
        $this->ajaxReturn($data, 'JSON');
    }

    public function pageTotal($type) {
        $total = M($type)->select();

        $data = array();

        $data['count'] = count($total);
    }
}