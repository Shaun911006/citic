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
     * 银联快付经办
     * @param $clientID
     * @param $money
     * @param $recAccountNo
     * @param $recAccountName
     * @param string $remark
     * @return array
     */
    public function payByUnionPay($clientID, $money, $recAccountNo, $recAccountName, $remark = '转账')
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
                    'ID' => '00001',
                    'recAccountNo' => $recAccountNo,
                    'recAccountName' => $recAccountName,
                    'tranAmount' => $money,
                    'abstract' => $remark,
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

    private function sendRequest($requestData)
    {
        self::log(json_encode($requestData, JSON_UNESCAPED_UNICODE));
        $requestData = XmlTools::encode($requestData, 'GBK', true);
        $requestData = CharsetTools::utf8ToGbk($requestData);
        $res         = HttpTools::post_curls($this->clientUrl, $requestData);
        return XmlTools::decode($res);
    }

    private function getResult($res)
    {
        return [
            'res' => (isset($res['status']) && $res['status'] === 'AAAAAAA') ? true : false,
            'msg' => isset($res['statusText']) ? $res['statusText'] : '',
            'data' => $res
        ];
    }

    public static function log($content)
    {
        file_put_contents('./citic.log',$content . PHP_EOL, FILE_APPEND);
    }
}