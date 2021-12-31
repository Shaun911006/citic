<?php
/**
 * Author:Shaun·Yang
 * Date:2020/6/12
 * Time:下午5:30
 * Description:
 */

namespace citic;

class CiticClient
{
    private string $userName; //用户名
    private string $payAccountNo; //支付账号
    private string $clientUrl; //客户端地址

    public function __construct($config = [])
    {
        $this->userName     = isset($config['userName']) ? $config['userName'] : '';
        $this->payAccountNo = isset($config['payAccountNo']) ? $config['payAccountNo'] : '';
        $this->clientUrl    = isset($config['clientUrl']) ? $config['clientUrl'] : '';
    }

    /**
     * 账户余额查询
     * @return array
     */
    public function balance(): array
    {
        $requestData = [
            'action' => 'DLBALQRY',
            'userName' => $this->userName,
            'list' => [
                'row' => [
                    'accountNo' => $this->payAccountNo,
                ]
            ]
        ];
        return $this->getResult($this->sendRequest($requestData));
    }

    /**
     * 银联快付经办
     * @param $clientID
     * @param $money
     * @param $recAccountNo
     * @param $recAccountName
     * @param $remark
     * @param $recBankCode
     * @return array
     */
    public function payByUnionPay($clientID, $money, $recAccountNo, $recAccountName, $remark = '转账', $recBankCode = '')
    {
        $remark      = $remark === '' ? '转账' : $remark;
        $requestData = [
            'action' => 'DLUPRSUB',
            'userName' => $this->userName,
            'clientID' => $clientID,
            'payAccountNo' => $this->payAccountNo,
            'totalNumber' => 1,
            'totalAmount' => $money,
            'chkNum' => $clientID,
            'abstract' => $remark,
            'list' => [
                'row' => [
                    'ID' => '99999',
                    'recAccountNo' => $recAccountNo,
                    'recAccountName' => $recAccountName,
                    'recBankCode' => $recBankCode,
                    'tranAmount' => $money,
                    'abstract' => $remark,
                ]
            ]
        ];
        return $this->getResult($this->sendRequest($requestData));
    }

    /**
     * 银联快付经办流水查询
     * @param $date
     * @return array
     */
    public function unionPayFlow($date)
    {
        $requestData = [
            'action' => 'DLUPRDWN',
            'userName' => $this->userName,
            'checkDate' => $date,
            'accountNo' => $this->payAccountNo,
        ];
        return $this->getResult($this->sendRequest($requestData));
    }

    /**
     * 支付转账
     * @param $clientID
     * @param $money
     * @param $recAccountNo
     * @param $recAccountName
     * @param $recOpenBankName
     * @param string $recOpenBankCode
     * @param string $remark
     * @return array
     */
    public function pay($clientID, $money, $recAccountNo, $recAccountName, $recOpenBankName, $recOpenBankCode = '', $remark = '转账')
    {
        if ($recOpenBankCode == '99') {
            $payType         = 2; //行内转账
            $recOpenBankName = '';
            $recOpenBankCode = '';
        } else {
            $payType = 1; //跨行转账
            if ($recOpenBankName == '' && $recOpenBankCode == '') {
                return [
                    'res' => false,
                    'msg' => '收款账号开户行名与收款账号开户行联行网点号至少输一项',
                    'data' => []
                ];
            }
        }

        $remark      = $remark === '' ? '转账' : $remark;
        $requestData = [
            'action' => 'DLINTTRN',
            'userName' => $this->userName,
            'list' => [
                'row' => [
                    'clientID' => $clientID,
                    'preFlg' => 0,
                    'preDate' => '',
                    'preTime' => '',
                    'payType' => $payType,
                    'payFlg' => 1,
                    'payAccountNo' => $this->payAccountNo,
                    'recAccountNo' => $recAccountNo,
                    'recAccountName' => $recAccountName,
                    'recOpenBankName' => $recOpenBankName,
                    'recOpenBankCode' => $recOpenBankCode,
                    'tranAmount' => $money,
                    'abstract' => $remark,
                    'memo' => $remark,
                    'chkNum' => $clientID,
                ]
            ]
        ];
        return $this->getResult($this->sendRequest($requestData));
    }

    /**
     * 银联快付明细查询
     * @param $clientID
     * @return array
     */
    public function queryByUnionPay($clientID)
    {
        $requestData = [
            'action' => 'DLUPRDET',
            'userName' => $this->userName,
            'clientID' => $clientID,
            'stt' => '',
            'controlFlag' => 1
        ];
        return $this->getResult($this->sendRequest($requestData));
    }

    /**
     * 银联快付明细查询
     * @param $clientID
     * @return array
     */
    public function query($clientID)
    {
        $requestData = [
            'action' => 'DLCIDSTT',
            'userName' => $this->userName,
            'clientID' => $clientID,
            'type' => '',
        ];
        return $this->getResult($this->sendRequest($requestData));
    }

    private function sendRequest($requestData)
    {
        self::log(json_encode($requestData, JSON_UNESCAPED_UNICODE), 1);
        $requestData = XmlTools::encode($requestData, 'GBK', true);
        $requestData = CharsetTools::utf8ToGbk($requestData);
        $res         = HttpTools::post_curls($this->clientUrl, $requestData);
        return XmlTools::decode($res);
    }

    private function getResult($res)
    {
        self::log(json_encode($res, JSON_UNESCAPED_UNICODE), 2);
        return [
            'res' => (isset($res['status']) && $res['status'] === 'AAAAAAA') ? true : false,
            'msg' => isset($res['statusText']) ? $res['statusText'] : '',
            'data' => $res
        ];
    }

    public static function log($content, $type = 1)
    {
        file_put_contents('./citic' . $type . '.log', '[' . date('Ymd-His') . ']' . $content . PHP_EOL, FILE_APPEND);
    }
}