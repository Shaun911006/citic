<?php
/**
 * Author:Shaun·Yang
 * Date:2022/4/13
 * Description:资金分簿
 */
namespace citic;

class SubClient extends CiticClient
{
    /**
     * 3.5.1资金分簿支付转账
     * @description 客户可使用该接口实现支付转账功能：
     *    (1)资金分簿支付转账只支持人民币账户交易；
     *    (2)当支付方式payType为1-跨行转账、交易金额小于100W时，智能路由到小额支付；
     *    (3)当支付方式payType为1-跨行转账、交易金额大于100W时，智能路由到大额支付；
     *    (4)当支付方式payType为2-行内、3-企业内转账时，支付时效payFlg必须为1-普通，
     * 收方账号必须为中信账号，支持实体账号、附属账号和卡号。
     *    (5)当支付方式payType为3-企业内部转账时，付款账户与收款账户必须属于同一客户。
     *    (6)当支付方式payType为1-跨行转账时，若收款账户开户行联行网点号为空且收款账
     * 户开户行名输入错误，则资金分簿支付转账交易将在银行柜面落地处理；
     * @param mixed $clientID 交易单号
     * @param mixed $money 金额
     * @param mixed $recAccountNo 收款账号
     * @param mixed $recAccountName 收款人姓名
     * @param mixed $recOpenBankName 开户行
     * @param mixed $recOpenBankCode 联行号
     * @param mixed $remark 备注
     * @return array
     */
    public function DLINTSUB($clientID, $money, $recAccountNo, $recAccountName, $recOpenBankName, $recOpenBankCode = '', $remark = '转账'): array
    {
        if ($recOpenBankCode == '302100011000') {
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
            }else{
                if ($recOpenBankCode){
                    $recOpenBankName = '';
                }else{
                    $recOpenBankCode = '';
                }
            }
        }

        $requestData = [
            'action' => 'DLINTSUB',
            'userName' => $this->userName,
            'list' => [
                'row' => [
                    'clientID' => $clientID,
                    'preFlg' => 0,
                    'preDate' => '',
                    'preTime' => '',
                    'payType' => $payType,
                    'payFlg' => 1,
                    'mainAccNo' => $this->payAccountNo,
                    'payAccountNo' => $this->selfSubAccNo,
                    'recAccountNo' => $recAccountNo,
                    'recAccountName' => $recAccountName,
                    'recOpenBankName' => $recOpenBankName,
                    'recOpenBankCode' => $recOpenBankCode,
                    'tranAmount' => $money,
                    'abstract' => $remark,
                ]
            ]
        ];
        return $this->getResult($this->sendRequest($requestData));
    }

    /**
     * 3.5.2资金分簿内部转账
     * @description 客户可使用该接口实现同一主体账户下的资金分簿之间的快捷转账，主体账
     * 户资金没有变化，仅是登记资金分簿交易明细。付款方是资金分簿时，收款方必须是与付款方
     * 同一主体账户下的资金分簿。收款方和付款方可以是公共资金分簿或公共计息收费资金分簿。
     *
     * @param mixed $clientID 交易单号
     * @param mixed $money 金额
     * @param mixed $fromAccNo 付款账号
     * @param mixed $toAccNo 收款账号
     * @param mixed $toAccName 收款方账户名称
     * @return array
     */
    public function DLSINSUB($clientID, $money, $fromAccNo, $toAccNo, $toAccName = ''): array
    {
        $requestData = [
            'action' => 'DLSINSUB',
            'userName' => $this->userName,
            'clientID' => $clientID,
            'mainAccNo' => $this->payAccountNo,
            'payAccNo' => $fromAccNo,
            'recvAccNo' => $toAccNo,
            'recvAccNm' => $toAccName,
            'tranAmt' => $money,
            'preFlg' => 0,
            'preTime' => ''
        ];
        return $this->getResult($this->sendRequest($requestData));
    }

    /**
     * 3.5.5资金分簿信息查询
     * @description 客户可使用该接口查询签约资金分簿的账户信息。
     *
     * @param mixed $subAccNo 资金分簿账号
     * @return array
     */
    public function DLSUBINF($subAccNo): array
    {
        $requestData = [
            'action' => 'DLSUBINF',
            'userName' => $this->userName,
            'subAccNo' => $subAccNo,
        ];
        return $this->getResult($this->sendRequest($requestData));
    }

    /**
     * 3.5.6资金分簿余额查询
     * @description 客户可以使用该接口查询资金分簿的资金分簿编号、资金分簿名
     * 称、余额、透支金额、冻结金额、可用资金等等信息。
     *
     * @param mixed $subAccNo 资金分簿账号
     * @return array
     */
    public function DLSUBBAL($subAccNo): array
    {
        $requestData = [
            'action' => 'DLSUBBAL',
            'userName' => $this->userName,
            'subAccNo' => $subAccNo,
        ];
        return $this->getResult($this->sendRequest($requestData));
    }

    /**
     * 3.5.7资金分簿交易明细查询
     * @description 客户可使用该接口查询签约资金分簿的交易明细信息。查询待调账交易
     * 明细时，资金分簿编号必须为公共调账账号，其中起始截止日期间隔不能超过3个月。
     *    字段controlFlag有标签且上送值为1时，返回交易时间字段。
     *    字段controlFlag无标签或者有标签且值为0时，不返回交易时间字段。
     *
     * @param mixed $subAccNo 资金分簿账号
     * @param mixed $startDate 起始日期 格式 YYYYMMDD
     * @param mixed $endDate 截止日期 格式 YYYYMMDD
     * @param mixed $tranType 交易类型 空：查询全部交易明细；1：查询待调账交易明细
     * @param mixed $minAmt 起始金额 两位小数
     * @param mixed $maxAmt 截止金额 两位小数
     * @return array
     */
    public function DLSUBDTL($subAccNo,$startDate = '',$endDate = '',$tranType = '',$minAmt = '',$maxAmt = ''): array
    {
        $requestData = [
            'action' => 'DLSUBDTL',
            'userName' => $this->userName,
            'subAccNo' => $subAccNo,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'tranType' => $tranType,
            'minAmt' => $minAmt,
            'maxAmt' => $maxAmt,
            'controlFlag' => 1,
        ];
        return $this->getResult($this->sendRequest($requestData));
    }

    /**
     * 3.5.18资金分簿关联账户查询
     * @description 客户可使用该接口查询资金分簿与现金池成员账户的对应绑定关系。
     *
     * @param mixed $subAccNo 资金分簿账号
     * @return array
     */
    public function DLRELPRY($subAccNo): array
    {
        $requestData = [
            'action' => 'DLRELPRY',
            'userName' => $this->userName,
            'subAccNo' => $subAccNo,
        ];
        return $this->getResult($this->sendRequest($requestData));
    }

    /**
     * 3.5.19资金分簿历史余额查询
     * @description 客户可使用该接口查询资金分簿的历史余额信息，其中起始截止日期间隔不能超过30天。
     *
     * @param mixed $subAccNo 资金分簿账号
     * @param mixed $startDate 起始日期 格式 YYYYMMDD
     * @param mixed $endDate 截止日期 格式 YYYYMMDD
     * @return array
     */
    public function DLSUBBLH($subAccNo,$startDate = '',$endDate = ''): array
    {
        $requestData = [
            'action' => 'DLSUBBLH',
            'userName' => $this->userName,
            'subAccNo' => $subAccNo,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];
        return $this->getResult($this->sendRequest($requestData));
    }
}