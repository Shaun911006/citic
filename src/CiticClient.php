<?php
/**
 * Author:Shaun·Yang
 * Date:2020/6/12
 * Time:下午5:30
 * Description:
 */

namespace citic;

use citic\exception\CiticException;

class CiticClient
{
    protected string $userName; //用户名
    protected string $payAccountNo; //支付账号
    protected string $clientUrl; //客户端地址
    protected string $selfSubAccNo; //自有资金分簿号

    public function __construct($config = [])
    {
        $this->userName     = $config['userName'] ?? '';
        $this->payAccountNo = $config['payAccountNo'] ?? '';
        $this->clientUrl    = $config['clientUrl'] ?? '';
        $this->selfSubAccNo = $config['selfSubAccNo'] ?? '';
    }

    /**
     * 账户余额查询
     * @return array
     * @throws CiticException
     */
    public function balance(): array
    {
        $requestData = [
            'action'   => 'DLBALQRY',
            'userName' => $this->userName,
            'list'     => new ListData('userDataList', [['accountNo' => $this->payAccountNo]])
        ];

        $res = $this->sendRequest($requestData);
        return $this->getResult($res);
    }

    /**
     * 银联快付经办
     * @param $clientID
     * @param $money
     * @param $recAccountNo
     * @param $recAccountName
     * @param string $remark
     * @param string $recBankCode
     * @return array
     * @throws CiticException
     */
    public function payByUnionPay($clientID, $money, $recAccountNo, $recAccountName, string $remark = '转账', string $recBankCode = ''): array
    {
        $remark      = $remark === '' ? '转账' : $remark;
        $requestData = [
            'action'       => 'DLUPRSUB',
            'userName'     => $this->userName,
            'clientID'     => $clientID,
            'payAccountNo' => $this->selfSubAccNo ?: $this->payAccountNo, //如果配置了自有分簿账号，用自有分簿账号支付，否则用主账号支付
            'totalNumber'  => 1,
            'totalAmount'  => $money,
            'chkNum'       => $clientID,
            'abstract'     => $remark,
            'list'         => new ListData('userDataList', [
                [
                    'ID'             => '99999',
                    'recAccountNo'   => $recAccountNo,
                    'recAccountName' => $recAccountName,
                    'recBankCode'    => $recBankCode,
                    'tranAmount'     => $money,
                    'abstract'       => $remark,
                ]
            ])
        ];
        $res = $this->sendRequest($requestData);
        return $this->getResult($res);
    }

    /**
     * 银联快付经办流水查询
     * @param $date
     * @param string $accountNo
     * @return array
     * @throws CiticException
     */
    public function unionPayFlow($date, string $accountNo = ''): array
    {
        $requestData = [
            'action'    => 'DLUPRDWN',
            'userName'  => $this->userName,
            'checkDate' => $date,
            'accountNo' => $accountNo ?: $this->payAccountNo,
        ];
        $res = $this->sendRequest($requestData);
        return $this->getResult($res);
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
     * @throws CiticException
     */
    public function pay($clientID, $money, $recAccountNo, $recAccountName, $recOpenBankName, string $recOpenBankCode = '', string $remark = '转账'): array
    {
        if ($recOpenBankCode == '302100011000') {
            $payType         = 2; //行内转账
            $recOpenBankName = '';
            $recOpenBankCode = '';
        } else {
            $payType = 1; //跨行转账
            if ($recOpenBankName == '' && $recOpenBankCode == '') {
                return [
                    'res'  => false,
                    'msg'  => '收款账号开户行名与收款账号开户行联行网点号至少输一项',
                    'data' => []
                ];
            } else {
                if ($recOpenBankCode) {
                    $recOpenBankName = '';
                } else {
                    $recOpenBankCode = '';
                }
            }
        }

        $remark      = $remark === '' ? '转账' : $remark;
        $requestData = [
            'action'   => 'DLINTTRN',
            'userName' => $this->userName,
            'list'     => new ListData('userDataList',[
                [
                    'clientID'        => $clientID,
                    'preFlg'          => 0,
                    'preDate'         => '',
                    'preTime'         => '',
                    'payType'         => $payType,
                    'payFlg'          => 1,
                    'payAccountNo'    => $this->payAccountNo,
                    'recAccountNo'    => $recAccountNo,
                    'recAccountName'  => $recAccountName,
                    'recOpenBankName' => $recOpenBankName,
                    'recOpenBankCode' => $recOpenBankCode,
                    'tranAmount'      => $money,
                    'abstract'        => $remark,
                    'memo'            => $remark,
                    'chkNum'          => $clientID,
                ]
            ])
        ];
        $res = $this->sendRequest($requestData);
        return $this->getResult($res);
    }

    /**
     * 银联快付明细查询
     * @param $clientID
     * @return array
     * @throws CiticException
     */
    public function queryByUnionPay($clientID): array
    {
        $requestData = [
            'action'      => 'DLUPRDET',
            'userName'    => $this->userName,
            'clientID'    => $clientID,
            'stt'         => '',
            'controlFlag' => 1
        ];
        $res = $this->sendRequest($requestData);
        return $this->getResult($res);
    }

    /**
     * 银联快付明细查询
     * @param $clientID
     * @return array
     * @throws CiticException
     */
    public function query($clientID)
    {
        $requestData = [
            'action'   => 'DLCIDSTT',
            'userName' => $this->userName,
            'clientID' => $clientID,
            'type'     => '',
        ];
        $res = $this->sendRequest($requestData);
        return $this->getResult($res);
    }

    /**
     * 发送请求
     * step1:报文转成xml字符串
     * step2:将utf8转成gbk
     * step3:curl发送post请求
     * step4:响应结果xml转成json字符串
     * step5:json字符串Gbk转utf8
     * step6:json字符串转数组
     * @param array $requestArr
     * @return mixed|string
     * @throws CiticException
     */
    protected function sendRequest(array $requestArr)
    {
        $num = rand(1000, 9999);
        self::log($num, $requestArr, 1);
        //step1:报文转成xml字符串
        $requestXml = XmlTools::encode($requestArr, 'GBK', true);
        //step2:将utf8转成gbk
        $requestXml = CharsetTools::utf8ToGbk($requestXml);
        //step3:curl发送post请求
        $responseXmlGbk = HttpTools::post_curls($this->clientUrl, $requestXml);
        if (!$responseXmlGbk) {
            throw new CiticException('CURL响应结果为空');
        }
        //step4:响应结果xml(Gbk)转成json字符串(Gbk)
        $responseJsonGbk = XmlTools::decode($responseXmlGbk);
        //step5:json字符串Gbk转utf8
        $responseJson = CharsetTools::gbkToUtf8($responseJsonGbk);
        //step6:json字符串转数组
        $responseArr = json_decode($responseJson, true);
        self::log($num, $responseArr, 2);
        return $responseArr;
    }

    protected function getResult($res): array
    {
        return [
            'res'  => isset($res['status']) && $res['status'] === 'AAAAAAA',
            'msg'  => $res['statusText'] ?? '',
            'data' => $res
        ];
    }

    /**
     * 记录日志
     * @param string $num 请求的编号
     * @param mixed $content 记录内容
     * @param int $type 内容类型 1.请求 2.响应
     * @return void
     */
    public static function log(string $num = '1000', $content = '', int $type = 1)
    {
        $dir1 = 'citic_log';
        if (!is_dir($dir1)) {
            mkdir($dir1, 0777, true);
        }
        $dir2 = $dir1 . DIRECTORY_SEPARATOR . date('Y-m');
        if (!is_dir($dir2)) {
            mkdir($dir2, 0777, true);
        }
        $file = $dir2 . DIRECTORY_SEPARATOR . date('d') . '.log';

        $logStr = '[' . date('H:i:s') . ']  (' . $num . ')  ' . ($type === 1 ? 'Request' : ($type === 2 ? 'Response' : '')) . '   >>>>>>' . PHP_EOL .
            var_export($content, true) . PHP_EOL;
        file_put_contents($file, $logStr . PHP_EOL, FILE_APPEND);
    }
}