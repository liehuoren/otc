<?php
namespace Admin\Controller;
use Think\Controller;
//更新测试
class RewardController extends Controller
{
    public function index($page =1)
    {
        
        $mo=M();
        $rew['InvitFee'] = $mo->table('trade_invit_fee')->order('id desc')->find();
        $count =count($mo->table('trade_invit_reward')->select());
        $reward = $mo->table('trade_invit_reward')
            ->limit(($page-1)*15,15)
            ->select();
        foreach ($reward as $k=>$v)
        {
            $trade = $mo->table('trade_trade')->where(array(
                'id' => $v['tradeid']
            ))->find();
            $rwd[$k]['tradeid'] = $v['tradeid'];
            $rwd[$k]['time'] = date('Y-m-d H:i:s',$v['trade_time']);
            $rwd[$k]['trade_name'] = $mo->table('trade_user')->where(array(
                'id' => $v['trade_user']
            ))->getField('email');
            $rwd[$k]['num'] = $trade['num'];
            $rwd[$k]['fee'] = $trade['fee'];
            $rwd[$k]['invit1_name'] = $mo->table('trade_user')->where(array(
                'id' => $v['invit1_id']
            ))->getField('email');

            $rwd[$k]['invit1_fee'] = $v['invit1_fee']
            ;
            $rwd[$k]['invit2_name'] = $mo->table('trade_user')->where(array(
                'id' => $v['invit2_id']
            ))->getField('email');

            $rwd[$k]['invit2_fee'] = $v['invit2_fee'];
        }
        $rew['reward_info']=$rwd;
        $rew['count']=$count;
        $this->ajaxReturn($rew,'JSON');
    }

    public function upRewardFee()
    {
        $fee = I('post.');
        $rs = M('InvitFee')->add(array(
            'fee1' =>$fee['level1'],
            'fee2' =>$fee['level2']
            ));
        if ($rs)
        {
            $this->ajaxSuccess('操作成功');
        }else{
            $this->ajaxError('操作失败');
        }
    }
}