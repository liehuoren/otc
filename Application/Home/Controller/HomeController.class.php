<?php

namespace Home\Controller;

class HomeController extends \Think\Controller{
    protected function _initialize()
    {
        if($_SERVER['REQUEST_METHOD'] == 'GET'){
            $this->ajaxError('请求错误');
        }
        if (! session('userId')) {
            session('userId', 0);
        } else {
            if (isset($_GET[C('VAR_LANGUAGE')])) {
                $langSet = $_GET[C('VAR_LANGUAGE')]; // url中设置了语言变量
                M('User')->where(array(
                    'id' => session('userId')
                ))->setField('lang', $langSet);
            } elseif (cookie('think_language')) { // 获取上次用户的选择
            } elseif (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) { // 自动侦测浏览器语言
                preg_match('/^([a-z\d\-]+)/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches);
                $langSet = $matches[1];
                M('User')->where(array(
                    'id' => session('userId')
                ))->setField('lang', $langSet);
            }
        }

        $email = M('ConfigEmail')->where(array(
            'id' => 1
        ))->find();

        C($email);

        $coin = (APP_DEBUG ? null : S('home_coin'));

        if (! $coin) {
            $coin = M('Coin')->where(array(
                'status' => 1,
                'name' => array(
                    'neq',
                    'cny'
                )
            ))->select();

            S('home_coin', $coin);
        }

        $coinList = array();

        foreach ($coin as $k => $v) {
            $showName = LANG_SET == 'zh-cn' ? $v['title'] : $v['js_yw'];
            $v['title'] = $showName;

            $coinList['coin'][$v['name']] = $v;

            if ($v['name'] != 'cny') {
                $coinList['coin_list'][$v['name']] = $v;
            }

            if ($v['type'] == 'rmb') {
                $coinList['rmb_list'][$v['name']] = $v;
            } else {
                $coinList['xnb_list'][$v['name']] = $v;
            }

            if ($v['type'] == 'rgb') {
                $coinList['rgb_list'][$v['name']] = $v;
            }

            if ($v['type'] == 'qbb') {
                $coinList['qbb_list'][$v['name']] = $v;
            }

            if ($v['type'] == 'peb') {
                $coinlist['peb_list'][$v['name']] = $v;
            }
            if ($v['type'] == 'eth') {
                $coinlist['eth_list'][$v['name']] = $v;
            }
        }

        C($coinList);

        if (userid()) {
            $userCoin_top = M('UserCoin')->where(array(
                'userid' => userid()
            ))->find();
            $userCoin_banlance = array();
            foreach ($coin as $k => $v)
                if ($userCoin_top[$v['name']] > 0 || $userCoin_top[$v['name'] . 'd'] > 0) {
                    $userCoin_banlance[] = array(
                        'name' => $v['name'],
                        'title' => LANG_SET == 'zh-cn' ? $v['title'] : $v['js_yw'],
                        'ky' => $userCoin_top[$v['name']],
                        'dj' => $userCoin_top[$v['name'] . 'd'],
                        'img' => $v['img']
                    );
                }
        }
    }
}