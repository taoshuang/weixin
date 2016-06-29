<?php
namespace Weixin;
use Weixin\Helpers;

/**
 * 微信代扣
 * https://pay.weixin.qq.com/wiki/doc/api/pap.php?chapter=18_1
 */
class Entrust
{
    const base_url = "https://api.mch.weixin.qq.com/";

    const bind_base_url = self::base_url . "papay/entrustweb";

    const pay_base_url = self::base_url . "pay/pappayapply";

    const unbind_base_url = self::base_url . "papay/deletecontract";

    const default_fee_type = "CNY";

    const default_trade_type = "PAP";

    const version = "1.0";

    /**
     *
     * @param $appid 微信支付分配的公众账号id
     * @param $mchid 微信支付分配的商户号
     * @param $key 商户支付密钥
     */
    public function __construct($appid="", $mchid="", $key="", $plan_id=""){
        $this->setAppId($appid);
        $this->setMchid($mchid);
        $this->setKey($key);
        $this->setPlanId($plan_id);
    }

    /**
     * appId
     * 微信公众号身份的唯一标识。
     *
     * @var string
     */
    private $appId = "";

    public function setAppId($appId)
    {
        $this->appId = $appId;
    }

    public function getAppId()
    {
        if (empty($this->appId)) {
            throw new Exception('AppId未设定');
        }
        return $this->appId;
    }

    private $plan_id = "";

    public function setPlanId($plan_id)
    {
        $this->plan_id = $plan_id;
    }

    public function getPlanId()
    {
        if (empty($this->plan_id)) {
            throw new Exception('AppId未设定');
        }
        return $this->plan_id;
    }

    /**
     * appSecret
     * 微信公众号秘钥。
     *
     * @var string
     */
    // private $appSecret = "";

    // public function setAppSecret($appSecret)
    // {
    //     $this->appSecret = $appSecret;
    // }

    // public function getAppSecret()
    // {
    //     if (empty($this->appSecret)) {
    //         throw new Exception('AppSecret未设定');
    //     }
    //     return $this->appSecret;
    // }

    /**
     * access_token微信公众平台凭证。
     */
    // private $accessToken = "";

    // public function setAccessToken($accessToken)
    // {
    //     $this->accessToken = $accessToken;
    // }

    // public function getAccessToken()
    // {
    //     if (empty($this->accessToken)) {
    //         throw new Exception('access token未设定');
    //     }
    //     return $this->accessToken;
    // }

    /**
     * Mchid 商户 ID ，身份标识
     */
    private $mchid = "";

    public function setMchid($mchid)
    {
        $this->mchid = $mchid;
    }

    public function getMchid()
    {
        if (empty($this->mchid)) {
            throw new Exception('Mchid未设定');
        }
        return $this->mchid;
    }


    /**
     * Key 商户支付密钥。登录微信商户后台，进入栏目【账设置】【密码安全】【 API密钥】，进入设置 API密钥。
     */
    private $key = "";

    public function setKey($key)
    {
        $this->key = $key;
    }

    public function getKey()
    {
        if (empty($this->key)) {
            throw new Exception('Key未设定');
        }
        return $this->key;
    }

    /**
     * cert 商户证书。
     *
     * @var string
     */
    // private $cert = "";

    // public function setCert($cert)
    // {
    //     $this->cert = $cert;
    // }

    // public function getCert()
    // {
    //     if (empty($this->cert)) {
    //         throw new Exception('商户证书未设定');
    //     }
    //     return $this->cert;
    // }

    /**
     * certKey 商户证书秘钥。
     *
     * @var string
     */
    // private $certKey = "";

    // public function setCertKey($certKey)
    // {
    //     $this->certKey = $certKey;
    // }

    // public function getCertKey()
    // {
    //     if (empty($this->certKey)) {
    //         throw new Exception('商户证书秘钥未设定');
    //     }
    //     return $this->certKey;
    // }



    public function bind($params){
        $para = [
            "mch_id" => $this->getMchid(),
            "appid" => $this->getAppId(),
            "plan_id" => $this->getPlanId(),
            "contract_code" => $params['contract_code'], //签约协议号,商户端生成
            "request_serial" => $params['request_serial'], //商户请求签约时的序列号，商户侧须唯一。序列号主要用于排序，不作为查询条件
            "contract_display_account" => $params['contract_display_account'], //签约用户的名称，用于页面展示
            "notify_url" => $params['notify_url'], //调通知的url,传输时需要对url进行encode
            "version" => self::version, //固定值1.0
            "clientip" => $params['clientip'],
            "timestamp" => time(),
            // "mobile" => $params['mobile'],
            // "email" => $params['email'],
            // "qq" => $params['qq'],
            // "openid" => $params['openid'],
            // "creid" => $params['creid'], //用户身份证号
            // "outerid" => $params['outerid'],  //商户侧用户标识
            "return_web" => $params['return_web'],
        ];
        $sign = $this->getSign($para);
        $para['sign'] = $sign;
        $para['notify_url'] = urlencode($para['notify_url']);
        $param_str = Helpers::createLinkstring($para);
        // $param_str = urlencode($param_str)
        return self::bind_base_url . "?" . $param_str;
    }

    /**
     * 处理xml形式返回结果
     */
    public function returnResult($rst)
    {
        $rst = Helpers::xmlToArray($rst);
        if (! empty($rst['return_code'])) {
            if ($rst['return_code'] == 'FAIL') {
                throw new \Exception($rst['return_msg']);
            } else {
                if ($rst['result_code'] == 'FAIL') {
                    throw new \Exception($rst['err_code'] . ":" . $rst['err_code_des']);
                } else {
                    return $rst;
                }
            }
        } else {
            throw new \Exception("网络请求失败");
        }
    }


    /**
     * Sign签名生成方法
     *
     * @param array $para
     * @throws Exception
     * @return string
     */
    public function getSign(array $para)
    {
        /**
         * a.除sign 字段外，对所有传入参数按照字段名的ASCII 码从小到大排序（字典序）后，
         * 使用URL 键值对的格式（即key1=value1&key2=value2…）拼接成字符串string1，
         * 注意： 值为空的参数不参与签名 ；
         */
        // 过滤不参与签名的参数
        $paraFilter = Helpers::paraFilter($para);
        // 对数组进行（字典序）排序
        $paraFilter = Helpers::argSort($paraFilter);
        // 进行URL键值对的格式拼接成字符串string1
        $string1 = Helpers::createLinkstring($paraFilter);
        /**
         * b.
         * 在string1 最后拼接上key=Key(商户支付密钥 ) 得到stringSignTemp 字符串，
         * 并对stringSignTemp 进行md5 运算，再将得到的字符串所有字符转换为大写，得到sign 值signValue。
         */
        $sign = $string1 . '&key=' . $this->getKey();
        $sign = strtoupper(md5($sign));
        return $sign;
    }

    public function checkAuth($data){
        $sign = $this->getSign($data);
        return ($sign == $data['sign']);
    }

    public function query(){

    }

    public function pay($params){
        $para = [
            "mch_id" => $this->getMchid(),
            "appid" => $this->getAppId(),
            "nonce_str" => genSecret(12, 1),
            // "sign" => "" ,
            "body" => $params['body'],  //商品或支付单简要描述
            "detail" => $params['detail'], //商品名称明细列表
            "attach" => $params['attach'], //附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据
            "out_trade_no" => $params['out_trade_no'], //商户系统内部的订单号,32个字符内、可包含字母, 其他说明见
            "total_fee" => $params['total_fee'],
            "fee_type" => self::default_fee_type, //符合ISO 4217标准的三位字母代码，默认人民币：CNY
            // "spbill_create_ip" => $params['spbill_create_ip'] //调用微信支付API的机器IP
            "goods_tag" => $params['goods_tag'],  //商品标记，代金券或立减优惠功能的参数，说明详见代金券或立减优惠
            "notify_url" => $params['notify_url'],
            "trade_type" => self::default_trade_type,  //交易类型PAP-微信委托代扣支付
            "contract_id" => $params['contract_id'], //签约成功后，微信返回的委托代扣协议id
            "mobile" => $params['mobile'],
            "email" => $params['email'],
            "qq" => $params['qq'],
            "openid" => $params['openid'],
            "creid" => $params['creid'],
            "outerid" => $params['outerid'],  //用户在商户侧的标识
            "timestamp" => $params['timestamp'],
            // "clientip" => $params['clientip'],  //点分IP格式(客户端IP)
            // "deviceid" => $params['deviceid'],
        ];
        $sign = $this->getSign($para);
        $para['sign'] = $sign;

        $xml = Helpers::arrayToXml($para);
        $rst = $this->post(self::pay_base_url , $xml);
        return $rst;
    }

    public function unbind($params){
        $params = [
            "mch_id" => $this->getMchid(),
            "appid" => $this->getAppId(),
            "plan_id" => $params['plan_id'],  //商户在微信商户平台配置的代扣模版id，选择plan_id+contract_code解约，则此参数必填
            "contract_code" => $params['contract_code'],  //商户请求签约时传入的签约协议号，商户侧须唯一。选择plan_id+contract_code解约，则此参数必填
            "contract_id" => $params['contract_id'],
            "contract_termination_remark" => $params['contract_termination_remark'],  //解约原因的备注说明，如：签约信息有误，须重新签约
            "version" => self::version,
        ];
        $sign = $this->getSign($para);
        $para['sign'] = $sign;

        $xml = Helpers::arrayToXml($para);
        $rst = $this->post(self::pay_base_url , $xml);
        return $rst;
    }

    public function order_query(){

    }
}
