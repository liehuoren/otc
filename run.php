<?php
header("Content-Type: text/html; charset=UTF-8");
$domain = "127.0.0.1"; // 填写网站域名
$queues = array(
    'Home/Queue/applyFee',
    'Home/Queue/sendHotWallet',
);

for ($i = 0; $i < count($queues); $i ++) {
    http_get("http://" . $domain . ":82/" . $queues[$i]);
}
echo "本次执行完毕" . time();

function http_get($url)
{
    $oCurl = curl_init();
    if (stripos($url, "https://") !== FALSE) {
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); // CURL_SSLVERSION_TLSv1
    }
    curl_setopt($oCurl, CURLOPT_URL, $url);
    curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
    $sContent = curl_exec($oCurl);
    $aStatus = curl_getinfo($oCurl);
    curl_close($oCurl);
    if (intval($aStatus["http_code"]) == 200) {
        return true;
    } else {
        return false;
    }
}
?>