<?php
namespace Admin\Controller;

use Think\Controller;

class ConfigController extends Controller
{
    public function coin()
    {
        $list = M('Coin')->order('id desc')->select();

        $this->ajaxReturn($list , 'JSON');
    }

    public function coinList($id = NULL) {
        if (empty($id)) {
            $data = array();
        } else {
            $data = M('Coin')->where(array(
                'id' => trim($id)
            ))->find();
        }

        $this->ajaxReturn($data , 'JSON');
    }

    public function coinEdit()
    {
        if ($_POST['dj_dk']) {
            $this->ajaxError('违规操作');
        }

        if ($_POST['dj_yh']) {
            $this->ajaxError('违规操作');
        }

        if ($_POST['dj_zj']) {
            $this->ajaxError('违规操作');
        }

        if ($_POST['dj_main_address']) {
            $this->ajaxError('违规操作');
        }

        if ($_POST['dj_main_address_password']) {
            $this->ajaxError('违规操作');
        }

        if ($_POST['name']) {
            $this->ajaxError('违规操作');
        }

        if ($_POST['type']) {
            $this->ajaxError('违规操作');
        }

        if ($_POST['js_yw']) {
            $this->ajaxError('违规操作');
        }

        $_POST['fee'] = floatval($_POST['zc_fee_two']);
        $_POST['zc_min'] = intval($_POST['zc_min']);
        $_POST['zc_max'] = intval($_POST['zc_max']);


        if ($_POST['id']) {
            $rs = M('Coin')->where(array(
                'id' => $_POST['id']
            ))->save($_POST);
        } else {

            $_POST['name'] = strtolower($_POST['name']);

            if (check($_POST['name'], 'username')) {
                $this->ajaxError('币种名称格式不正确！');
            }

            if (M('Coin')->where(array(
                'name' => $_POST['name']
            ))->find()) {
                $this->ajaxError('币种存在！');
            }

//            $rea = M()->execute('ALTER TABLE  `trade_user_coin` ADD  `' . $_POST['name'] . '` DECIMAL(20,8) UNSIGNED NOT NULL');
//            $reb = M()->execute('ALTER TABLE  `trade_user_coin` ADD  `' . $_POST['name'] . 'd` DECIMAL(20,8) UNSIGNED NOT NULL ');
//            $rec = M()->execute('ALTER TABLE  `trade_user_coin` ADD  `' . $_POST['name'] . 'b` VARCHAR(200) NOT NULL ');
//            if ($_POST['type'] == 'peb' || $_POST['type'] == 'eth') {
//                $rea = M()->execute('ALTER TABLE  `trade_user_coin` ADD  `' . $_POST['name'] . 's` VARCHAR(200) NOT NULL');
//            }
            $rs = M('Coin')->add($_POST);
        }

        if ($rs) {
            $this->ajaxSuccess('操作成功！');
        } else {
            $this->ajaxError('数据未修改！');
        }
    }

    public function coinImage()
    {
        $upload = new \Think\Upload();
        $upload->maxSize = 3145728;
        $upload->exts = array(
            'jpg',
            'gif',
            'png',
            'jpeg'
        );
        $upload->rootPath = './Upload/coin/';
        $upload->autoSub = false;
        $info = $upload->upload();
        
        foreach ($info as $k => $v) {
            $path = $v['savepath'] . $v['savename'];
            echo $path;
            exit();
        }
    }
}

?>