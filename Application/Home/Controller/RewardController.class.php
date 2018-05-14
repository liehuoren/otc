<?php
namespace Home\Controller;
use Think\Controller;

class RewardController extends Controller
{
    public function index()
    {
//        session('userid',52);
        $mo= M();
        $where = ' invit1_id ='.session('userid').' or invit2_id = '.session('userid');
        $res = $mo->table('trade_invit_reward as a')
            ->field('a.trade_user,a.invit1_id,a.invit1_fee,a.invit2_id,a.invit2_fee,a.coin_type,a.trade_time,b.num,b.fee')
            ->join('trade_trade as b  on a.tradeid = b.id')
            ->where($where)
            ->select();
        foreach ($res as $k=>$v)
        {
            if ($v['invit1_id'] == session('userid'))
            {
                $res[$k]['level'] = '一级';
                $res[$k]['invit_fee'] = $v['invit1_fee'];

            }

            if ($v['invit2_id'] == session('userid'))
            {
                $res[$k]['level'] = '二级';
                $res[$k]['invit_fee'] = $v['invit2_fee'];
            }

            $res[$k]['time']=date('Y-m-d H:i:s',$v['trade_time']);
            $res[$k]['tradeuser']=$mo->table('trade_user')->where(array(
                'id' => $v['trade_user']
            ))->getField('email');

            $res[$k]['invit1_name']=$mo->table('trade_user')->where(array(
                'id' => $v['invit1_id']
            ))->getField('email');

            $res[$k]['invit2_name']=$mo->table('trade_user')->where(array(
                'id' => $v['invit2_id']
            ))->getField('email');
        }

        $this->ajaxReturn($res);
    }
}