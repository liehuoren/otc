<?php

//保存登录用户的ID
function userid($username = NULL, $type = 'username')
{
    if ($username && $type) {

        $userid = (APP_DEBUG ? NULL : S('userid' . $username . $type));
        if (!$userid) {
            $userid = M('User')->where(array(
                $type => $username
            ))->getField('id');
            S('userid' . $username . $type, $userid);
        }
    } else {
        $userid = session('userId');
    }
    return $userid ? $userid : NULL;
}

//获取国家表
function getCountry()
{
    return D('Country')->where(array(
        'is_show' => 1
    ))->select();
}

//执行方法做验证的参数
function check($data, $rule = NULL, $ext = NULL)
{
    $data = trim(str_replace(PHP_EOL, '', $data));

    if ($data == '') {
        return false;
    }

    $validate['require'] = '/.+/';
    $validate['url'] = '/^http(s?):\\/\\/(?:[A-za-z0-9-]+\\.)+[A-za-z]{2,4}(?:[\\/\\?#][\\/=\\?%\\-&~`@[\\]\':+!\\.#\\w]*)?$/';
    $validate['currency'] = '/^\\d+(\\.\\d+)?$/';
    $validate['number'] = '/^\\d+$/';
    $validate['zip'] = '/^\\d{6}$/';
    $validate['cny'] = '/^(([1-9]{1}\\d*)|([0]{1}))(\\.(\\d){1,2})?$/';
    $validate['integer'] = '/^[\\+]?\\d+$/';
    $validate['double'] = '/^[\\+]?\\d+(\\.\\d+)?$/';
    $validate['english'] = '/^[A-Za-z]+$/';
    $validate['idcard'] = '/^[1-9]\d{7}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}$|^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}([0-9]|X|x)$/';
    $validate['truename'] = '/^[\\x{4e00}-\\x{9fa5}]{2,4}$/u';
    $validate['username'] = '/^[a-zA-Z]{1}[0-9a-zA-Z_]{5,15}$/';
    $validate['email'] = '/^\\w+([-+.]\\w+)*@\\w+([-.]\\w+)*\\.\\w+([-.]\\w+)*$/';
    $validate['moble'] = '/^(((1[0-9][0-9]{1})|159|153)+\\d{8})$/';
    $validate['password'] = '/^[a-zA-Z0-9_\\@\\#\\$\\%\\^\\&\\*\\(\\)\\!\\,\\.\\?\\-\\+\\|\\=]{6,16}$/';
    $validate['xnb'] = '/^[a-zA-Z]$/';
    $validate['coinname'] = '/^[a-z]+$/';

    if (isset($validate[strtolower($rule)])) {
        $rule = $validate[strtolower($rule)];
        return preg_match($rule, $data);
    }

    $Ap = '\\x{4e00}-\\x{9fff}' . '0-9a-zA-Z\\@\\#\\$\\%\\^\\&\\*\\(\\)\\!\\,\\.\\?\\-\\+\\|\\=';
    $Cp = '\\x{4e00}-\\x{9fff}';
    $Dp = '0-9';
    $Wp = 'a-zA-Z';
    $Np = 'a-z';
    $Tp = '@#$%^&*()-+=';
    $_p = '_';
    $pattern = '/^[';
    $OArr = str_split(strtolower($rule));
    in_array('a', $OArr) && ($pattern .= $Ap);
    in_array('c', $OArr) && ($pattern .= $Cp);
    in_array('d', $OArr) && ($pattern .= $Dp);
    in_array('w', $OArr) && ($pattern .= $Wp);
    in_array('n', $OArr) && ($pattern .= $Np);
    in_array('t', $OArr) && ($pattern .= $Tp);
    in_array('_', $OArr) && ($pattern .= $_p);
    isset($ext) && ($pattern .= $ext);
    $pattern .= ']+$/u';
    return preg_match($pattern, $data);
}
//生成邀请码
function get_invit_id()
{

//    $rs=M('User')->order('id desc')->find();
    $str='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890';
    $start= rand(0,strlen($str)-6);
    $randStr = str_shuffle($str);//打乱字符串
    $rands= substr($randStr,$start,6);//substr(string,start,length);返回字符串的一部分
//    $rands.=$rs['id']+1;
    return $rands;
}

//检查执行事务期间，返回的所有的结果
function check_arr($rs)
{
    foreach ($rs as $v) {
        if (!$v) {
            return false;
        }
    }

    return true;
}

function get_city_ip($ip = NULL)
{
    if (empty($ip)) {
        $ip = get_client_ip();
    }

    $Ip = new Org\Net\IpLocation('ThinkPHP\Library\Org\Net\data.dat');
    $area = $Ip->getlocation($ip);
    $str = $area['country'] . $area['area'];
    $str = iconv('GB2312', 'UTF-8', $str);

    if (($ip == '127.0.0.1') || ($str == false) || ($str == 'IANA保留地址用于本地回送')) {
        $str = 'localhost';
    }

    return $str;
}

function CoinClient($username, $password, $ip, $port, $timeout = 3, $headers = array(), $suppress_errors = false)
{
    return new \Common\Ext\CoinClient($username, $password, $ip, $port, $timeout, $headers, $suppress_errors);
}


function mlog($text)
{
    $text = time() . ' ' . $text . "\n";
    file_put_contents(APP_PATH . '/../trade_error.txt', $text, FILE_APPEND);
}

function debug($value, $type = 'DEBUG', $verbose = false, $encoding = 'UTF-8')
{
    if (M_DEBUG) {

        if (!IS_CLI) {
            Common\Ext\FirePHP::getInstance(true)->log($value, $type);
        }

    }
}

function username($id = NULL, $type = 'id')
{
    if ($id && $type) {
        $username = (APP_DEBUG ? NULL : S('username' . $id . $type));

        if (!$username) {
            $username = M('User')->where(array(
                $type => $id
            ))->getField('username');
            S('username' . $id . $type, $username);
        }
    } else {
        $username = session('userName');
    }

    return $username ? $username : NULL;
}

function curlget($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}

function btcdata($url){
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, 1);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//这个是重点。
    $data = curl_exec($curl);
    curl_close($curl);
    return $data;
}

function postcurl($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    // post数据
    curl_setopt($ch, CURLOPT_POST, 1);
    // post的变量
    curl_setopt($ch, CURLOPT_POSTFIELDS,'');
    $output = curl_exec($ch);
    curl_close($ch);

    //打印获得的数据
    return $output;
}

function sendMail($to, $subject = '验证码', $content, $email , $emailpassword)
{
    Vendor('PHPMailer.PHPMailerAutoload');
    $mail = new PHPMailer(); // 实例化
    $mail->IsSMTP(); // 启用SMTP
    $mail->Host = 'smtp.exmail.qq.com'; // smtp服务器的名称
    $mail->SMTPSecure = 'ssl';
    $mail->Port = 465;
    $mail->SMTPAuth = true; // 启用smtp认证
    $mail->Username = $email; // 你的邮箱名
    $mail->Password = $emailpassword; // 邮箱密码
    $mail->From = $email; // 发件人地址（也就是你的邮箱地址）
    $mail->FromName = $email; // 发件人姓名
    // $mail->Username = 'infor1@tokenview.net'; // 你的邮箱名
    // $mail->Password = 'Aa654321'; // 邮箱密码
    // $mail->From = 'infor1@tokenview.net'; // 发件人地址（也就是你的邮箱地址）
    // $mail->FromName = 'infor1@tokenview.net'; // 发件人姓名
    $mail->AddAddress($to, '');
    $mail->IsHTML(true); // 是否HTML格式邮件
    $mail->CharSet = 'utf-8'; // 设置邮件编码
    $mail->Subject = $subject; // 邮件主题
    $mail->Body = $content; // 邮件内容
    return $mail->Send() ? true : false;
}
//function sendMail($to, $subject = '验证码', $content)
//{
//    Vendor('PHPMailer.PHPMailerAutoload');
//    $mail = new PHPMailer(); // 实例化
//    $mail->IsSMTP(); // 启用SMTP
//    $mail->Host = 'smtp.exmail.qq.com'; // smtp服务器的名称
////    $mail->SMTPSecure = 'ssl';
////    $mail->Port = 465;
//    $mail->SMTPAuth = true; // 启用smtp认证
//    $mail->Username = 'xinyu@m-chain.com'; // 你的邮箱名
//    $mail->Password = 'Abc81026960.'; // 邮箱密码
//    $mail->From = 'xinyu@m-chain.com'; // 发件人地址（也就是你的邮箱地址）
//    $mail->FromName = 'xinyu@m-chain.com'; // 发件人姓名
//    $mail->AddAddress($to, '');
//    $mail->IsHTML(true); // 是否HTML格式邮件
//    $mail->CharSet = 'utf-8'; // 设置邮件编码
//    $mail->Subject = $subject; // 邮件主题
//    $mail->Body = $content; // 邮件内容
//    return $mail->Send() ? true : false;
//}

function send_email($email, $content)
{
    debug(array(
        $content,
        $email
    ), 'send_moble');

    $post_data = "sname=" . C('moble_user') . "&spwd=" . C('moble_pwd') . "&scorpid=&sprdid=1012888&sdst=" . $email . "&smsg=" . rawurlencode($content) . "Jerry";
    $file_contents = send_post(C('moble_url'), $post_data);
    return $file_contents;
}

function checkuser($id, $token)
{
    $user = M('User')->where(array(
        'id' => $id,
        'token' => $token
    ))->find();

    if ($user) {
        return $user['id'];
    } else {
        return false;
    }
}


function createRandomStr($length)
{
    $str = array_merge(range(0, 9), range('a', 'z'), range('A', 'Z'));
    shuffle($str);
    $str = implode('', array_slice($str, 0, $length));
    return $str;
}


