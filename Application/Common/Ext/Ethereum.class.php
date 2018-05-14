<?php
namespace Common\Ext;

class Ethereum extends ETHClient
{
    public function ether_request($method, $params = array())
    {
        $ret = $this->request($method, $params);
        return $ret->result;
    }

    public function real_banlance($input)
    {
        if ($input > 1) {
            return $input / 1000000000000000000;
        }
        return 0;
    }

    public function to_real_value($input)
    {
        if ($input > 0) {
            return bcmul((string) $input, '1000000000000000000');
        }
        return 0;
    }

    public function decode_hex($input)
    {
        if (preg_match('/[a-f0-9]+/', $input))
            return hexdec($input);
        return $input;
    }

    //补0
    public function fillZero($input){
        $num = substr($input, 2);
        $numlen = strlen($num);
        $zero = '';
        for ($j = $numlen; $j < 65; $j++) {
            $zero .= 0;
        }
        $input = $zero . $num;
        return $input;
    }

//    private function dec_to_hex($dec)
//    {
//        $sign = ""; // suppress errors
//        if ($dec < 0) {
//            $sign = "-";
//            $dec = abs($dec);
//        }
//
//        $hex = Array(
//            0 => 0,
//            1 => 1,
//            2 => 2,
//            3 => 3,
//            4 => 4,
//            5 => 5,
//            6 => 6,
//            7 => 7,
//            8 => 8,
//            9 => 9,
//            10 => 'a',
//            11 => 'b',
//            12 => 'c',
//            13 => 'd',
//            14 => 'e',
//            15 => 'f'
//        );
//
//        do {
//            $h = $hex[($dec % 16)] . $h;
//            $dec /= 16;
//        } while ($dec >= 1);
//
//        return $sign . $h;
//    }

    public function encode_dec($input)
    {
        if (preg_match('/[0-9]+/', $input)) {
            $intSwitch = new IntSwitch();
            return '0x' . $intSwitch->changeBigInt($input, 16 , 0);
        }
        
        return $input;
    }

    public function encode_sixteen($input)
    {
        if (preg_match('/[0-9]+/', $input)) {
            $intSwitch = new IntSwitch();
            return $intSwitch->changeBigInt($input, 16 , 0);
        }

        return $input;
    }

    public function fill_zero($input)
    {
        $input = str_replace('0x','',$input);
        $input = str_pad($input,64,'0',STR_PAD_LEFT);
        return $input;
    }

    public function getridof_zero($input , $isAddress)
    {
        if ($isAddress){
            if (strlen($input) > 40 ){
                return '0x'.substr($input,strlen($input) - 40);
            }else{
                return $input;
            }
        }else{
            $input = preg_replace('/^0+/','',$input);
            return $input;
        }
    }



    function personal_unlockAccount($address, $password, $time = 60)
    {
        return $this->ether_request(__FUNCTION__, array(
            $address,
            $password,
            $time
        ));
    }

    function personal_lockAccount($address)
    {
        return $this->ether_request(__FUNCTION__, array(
            $address
        ));
    }

    function eth_protocolVersion()
    {
        return $this->ether_request(__FUNCTION__);
    }

    function eth_accounts()
    {
        return $this->ether_request(__FUNCTION__);
    }

    function eth_blockNumber($decode_hex = FALSE)
    {
        $block = $this->ether_request(__FUNCTION__);
        if ($decode_hex)
            $block = $this->decode_hex($block);
        
        return $block;
    }

    function eth_getBalance($address, $block = 'latest')
    {
        $balance = $this->ether_request(__FUNCTION__, array(
            $address,
            $block
        ));
        
        $balance = $this->decode_hex($balance);
        
        return $this->real_banlance($balance);
    }

    function eth_getTransactionCount($address, $block = 'latest', $decode_hex = FALSE)
    {
        $count = $this->ether_request(__FUNCTION__, array(
            $address,
            $block
        ));
        
        if ($decode_hex)
            $count = $this->decode_hex($count);
        
        return $count;
    }

    function eth_sign($address, $input)
    {
        return $this->ether_request(__FUNCTION__, array(
            $address,
            $input
        ));
    }

//    function eth_call($from ,$to, $data)
//    {
//        $send_tran = array(array(
//            'from' => '',
//            'to' => $to,
//            'value' => 0,
//            'gas' => 0,
//            'gasPrice'=> 0,
//            'data' => $data
//        ));
//        $balance = $this->ether_request(__FUNCTION__, $send_tran);
////        $balance = $this->ether_request(__FUNCTION__, array(
////            $send_tran
////        ));
//
//        $balance = $this->decode_hex($balance);
//
//        return $balance / 10000;
//    }

    function eth_contract_getBalance($contractAddress, $address)
    {
        $method  = '0x70a08231';
        $balance = $this->ether_request('eth_call', array(array(
            'to'=> $contractAddress,
            'data'=>$method.$this->fill_zero($address)
        ),"latest"));
        $balance = $this->decode_hex($balance);
        return $balance / 10000;
    }

    function eth_call($hash)
    {
        return $this->ether_request(__FUNCTION__, array(
            $hash
        ));
    }

    function eth_sendTransaction($address, $password, $value, $toaddress ,$isAll = false , $data = null , $gas = '21000', $gasPrice = 1000000000)
    {

        $this->personal_unlockAccount($address, $password);
        if ($isAll){
            $real_value = $value - 0.0001 - ($this->real_banlance($gasPrice) * $this->real_banlance($gas) * $value);
        }else{
            $real_value = $value;
        }

        $send_tran = array(array(
            'from' => $address,
            'to' => $toaddress,
            'value' => $this->encode_dec($this->to_real_value($real_value)),
            'gas' => $gas,
            'gasPrice'=> $gasPrice,
            'data' => $data,
        ));


        $result = $this->ether_request(__FUNCTION__, $send_tran);
        //echo $result;
        return $result;
    }

    function eth_gasPrice()
    {
        return $this->ether_request(__FUNCTION__);
    }

    function eth_getBlockByHash($hash, $full_tx = TRUE)
    {
        return $this->ether_request(__FUNCTION__, array(
            $hash,
            $full_tx
        ));
    }

    function eth_getBlockByNumber($block = 'latest', $full_tx = TRUE)
    {
        return $this->ether_request(__FUNCTION__, array(
            $block,
            $full_tx
        ));
    }

    function eth_getTransactionByHash($hash)
    {
        return $this->ether_request(__FUNCTION__, array(
            $hash
        ));
    }

    function eth_getTransactionReceipt($hash)
    {
        return $this->ether_request(__FUNCTION__, array(
            $hash
        ));
    }



//    /**
//     * 列出账号所有地址
//     */
//
//    /**
//     * 列出本地交易
//     */
//    function listLocalTransactions($name = 'eth')
//    {
////        $file_path = DATABASE_PATH . 'check_qianbao_' . $name . '.json';
//        $qianbao = M('CheckQianbao')->where(array('coin'=>$name))->find();
//        $blockNumber = 4189362;
//        $currentBlockNumber = $this->eth_blockNumber(true);
////        $currentBlockNumber = 4136357;
//        if ($qianbao) {
//            $blockNumber = $qianbao['blocknumber'];
//        }else{
//            M('CheckQianbao')->add(array(
//                'coin' => $name,
//                'blocknumber' => $blockNumber
//            ));
//        }
//
//        echo '当前区块高度:'.$currentBlockNumber."<br/>";
//        echo '编写代码时最新区块:'.$blockNumber."<br/>";
//        $transactions = array();
//        if ($currentBlockNumber >= $blockNumber) {
//            for ($i = $blockNumber; $i <= $currentBlockNumber; $i ++) {
//                $count = hexdec($this->eth_getBlockTransactionCountByNumber($i));
//                for ($k = 0; $k < $count; $k ++) {
//                    echo 'count'.$count."";
//                    $transactions[] = $this->eth_getTransactionByBlockNumberAndIndex($i, $k);
//                }
////                file_put_contents($file_path, $i + 1);
//                if ($qianbao) {
//                    M('CheckQianbao')->where(
//                        array('coin'=>$name)
//                    )->setField('blocknumber',$i + 1);
//                }
//            }
//        }
//        return $transactions;
//    }

    /**
     * 列出本地交易
     */
    function listLocalTransactions($name)
    {
        //set_time_limit(0);
        $qianbao = M('CheckQianbao')->where(array('cointype'=>$name))->find();
        $blockNumber = 4177692; // 编写代码时最新区块
        $currentBlockNumber = $this->eth_blockNumber(true);
        if ($qianbao) {
            $blockNumber = $qianbao['blocknumber'];
        }else{
            M('CheckQianbao')->add(array(
                'coinid' => 5,
                'blocknumber' => $blockNumber,
                'cointype' => $name
            ));
        }

        echo '当前区块高度:'.$currentBlockNumber."<br/>";
        echo '编写代码时最新区块:'.$blockNumber."<br/>";
        $transactions = array();
        if ($currentBlockNumber -16 >= $blockNumber) {
            $currentBlockNumber = $blockNumber  > $currentBlockNumber ? $currentBlockNumber : $blockNumber + 15;
            for ($i = $blockNumber; $i <= $currentBlockNumber; $i ++) {
                $count = hexdec($this->eth_getBlockTransactionCountByNumber($i));
                for ($k = 0; $k < $count; $k ++) {
                    //echo 'count'.$count;
                    $transactions[] = $this->eth_getTransactionByBlockNumberAndIndex($i, $k);
                }
                if ($qianbao) {
                    M('CheckQianbao')->where(
                        array('cointype'=>$name)
                    )->setField('blocknumber',$i + 1);
                }
            }
        }
        return $transactions;
    }


    function eth_getTransactionByBlockNumberAndIndex($blockNumber, $index)
    {
        $data = $this->ether_request(__FUNCTION__, array(
            $this->encode_dec($blockNumber),
            $this->encode_dec($index)
        ));

        $rs = $this->eth_getTransactionReceipt($data->hash);

        if ($rs->status == '0x1'){
            return $data;
        }
    }


    function eth_getBlockTransactionCountByNumber($index)
    {
        return $this->ether_request(__FUNCTION__, array(
            $this->encode_dec($index)
        ));
    }

    /**
     * 创建地址
     *
     * @param unknown $pass            
     */
    function personal_newAccount($pass)
    {
        return $this->ether_request(__FUNCTION__, array(
            $pass
        ));
    }
}

/**
 * Ethereum transaction object
 */
class Ethereum_Transaction
{

    private $to, $from, $gas, $value, $gasPrice , $inputdata;

    function __construct($from, $to, $value, $gas , $gasPrice ,$inputdata=NULL)
    {
        $this->from = $from;
        $this->to = $to;
        $this->gas = $gas;
        $this->value = $value;
        $this->gasPrice = $value;
        $this->inputdata = $inputdata;
        $this->toArray();
    }

    public function setValue($value){
        $this->value = $value;
    }

    public function setGasPrice($price)
    {
        $this->gasPrice = $price;
    }

    function toArray()
    {
        return array(
            array(
                'from' => $this->from,
                'to' => $this->to,
                'gas' => $this->gas,
                'value' => $this->value,
                'gasPrice' => $this->gasPrice,
                'inputdata' => $this->inputdata
            )
        );
    }
}
?>