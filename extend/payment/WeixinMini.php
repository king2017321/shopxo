<?php
// +----------------------------------------------------------------------
// | ShopXO 国内领先企业级B2C免费开源电商系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2018 http://shopxo.net All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Devil
// +----------------------------------------------------------------------
namespace payment;

/**
 * 微信小程序支付
 * @author   Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2018-09-19
 * @desc    description
 */
class WeixinMini
{
    // 插件配置参数
    private $config;

    /**
     * 构造方法
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-17
     * @desc    description
     * @param   [array]           $params [输入参数（支付配置参数）]
     */
    public function __construct($params = [])
    {
        $this->config = $params;
    }

    /**
     * 配置信息
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-19
     * @desc    description
     */
    public function Config()
    {
        // 基础信息
        $base = [
            'name'          => '微信',  // 插件名称
            'version'       => '0.0.1',  // 插件版本
            'apply_version' => '不限',  // 适用系统版本描述
            'apply_terminal'=> ['wechat'], // 适用终端 默认全部 ['pc', 'h5', 'app', 'alipay', 'wechat', 'baidu']
            'desc'          => '适用微信小程序，即时到帐支付方式，买家的交易资金直接打入卖家账户，快速回笼交易资金。 <a href="https://pay.weixin.qq.com/" target="_blank">立即申请</a>',  // 插件描述（支持html）
            'author'        => 'Devil',  // 开发者
            'author_url'    => 'http://shopxo.net/',  // 开发者主页
        ];

        // 配置信息
        $element = [
            [
                'element'       => 'input',
                'type'          => 'text',
                'default'       => '',
                'name'          => 'appid',
                'placeholder'   => '小程序ID',
                'title'         => '小程序ID',
                'is_required'   => 0,
                'message'       => '请填写微信分配的小程序ID',
            ],
            [
                'element'       => 'input',
                'type'          => 'text',
                'default'       => '',
                'name'          => 'mch_id',
                'placeholder'   => '微信支付商户号',
                'title'         => '微信支付商户号',
                'is_required'   => 0,
                'message'       => '请填写微信支付分配的商户号',
            ],
            [
                'element'       => 'input',
                'type'          => 'text',
                'default'       => '',
                'name'          => 'key',
                'placeholder'   => '密钥',
                'title'         => '密钥',
                'is_required'   => 0,
                'message'       => '请填写密钥',
            ],
            [
                'element'       => 'textarea',
                'name'          => 'rsa_public',
                'placeholder'   => '应用公钥',
                'title'         => '应用公钥',
                'is_required'   => 0,
                'rows'          => 6,
                'message'       => '请填写应用公钥',
            ],
            [
                'element'       => 'textarea',
                'name'          => 'rsa_private',
                'placeholder'   => '应用私钥',
                'title'         => '应用私钥',
                'is_required'   => 0,
                'rows'          => 6,
                'message'       => '请填写应用私钥',
            ],
            [
                'element'       => 'textarea',
                'name'          => 'out_rsa_public',
                'placeholder'   => '支付宝公钥',
                'title'         => '支付宝公钥',
                'is_required'   => 0,
                'rows'          => 6,
                'message'       => '请填写支付宝公钥',
            ],
        ];

        return [
            'base'      => $base,
            'element'   => $element,
        ];
    }

    /**
     * 支付入口
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-19
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Pay($params = [])
    {
        if(empty($params))
        {
            return DataReturn('参数不能为空', -1);
        }

        // 获取支付参数
        $data = $this->GetPayParams($params);
        if($data['code'] != 0)
        {
            return $data;
        }

        // xml
        $xml = '<xml>
                <appid>'.$this->config['appid'].'</appid>
                <body>'.$data['data']['data']['name'].'</body>
                <mch_id>'.$this->config['mch_id'].'</mch_id>
                <nonce_str>'.$data['data']['data']['nonce_str'].'</nonce_str>
                <notify_url>'.$data['data']['data']['notify_url'].'</notify_url>
                <openid>'.$data['data']['data']['user_openid'].'</openid>
                <out_trade_no>'.$data['data']['data']['order_no'].'</out_trade_no>
                <spbill_create_ip>'.$data['data']['data']['spbill_create_ip'].'</spbill_create_ip>
                <total_fee>'.$data['data']['data']['total_price'].'</total_fee>
                <trade_type>'.$data['data']['data']['trade_type'].'</trade_type>
                <attach>'.$data['data']['data']['attach'].'</attach>
                <sign>'.$data['data']['sign'].'</sign>
            </xml>';

        $result = $this->XmlToArray($this->HttpRequest('https://api.mch.weixin.qq.com/pay/unifiedorder', $xml));

        print_r($result);die;
        if(!empty($result['return_code']) && $result['return_code'] == 'SUCCESS' && !empty($result['prepay_id']))
        {
            // 返回数据
            $pay_data = array(
                    'appid'         =>  $this->config['appid'],
                    'partnerid'     =>  $this->config['mch_id'],
                    'prepayid'      =>  $result['prepay_id'],
                    'package'       =>  'prepay_id='.$result['prepay_id'],
                    'noncestr'      =>  md5(time().rand()),
                    'timestamp'     =>  time(),
                );
            $pay_data['sign'] = $this->GetSign($pay_data);
            return DataReturn('参数不能为空', 0, $pay_data);
        }
        return DataReturn('参数不能为空', -1);
    }

    /**
     * 获取支付参数
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-01-07
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function GetPayParams($params = [])
    {
        if(empty($params)) return '';

        $params['appid'] = $this->config['appid'];
        $params['mch_id'] = $this->config['mch_id'];
        $params['nonce_str'] = md5(time().rand().$params['order_no']);
        $params['spbill_create_ip'] = GetClientIP();
        $params['trade_type'] = empty($params['trade_type']) ? 'JSAPI' : $params['trade_type'];
        $params['attach'] = empty($params['attach']) ? 'shopxo-attach' : $params['attach'];
        $data = array(
            'sign'  =>  $this->GetSign($params),
            'data'  =>  $params,
        );
        return DataReturn('success', 0, $data);
    }

    /**
     * 支付回调处理
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-19
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Respond($params = [])
    {
        
        return DataReturn('test', -100);
    }

    /**
     * 签名生成
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-01-07
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function GetSign($params = [])
    {
        ksort($params);
        $sign  = '';
        foreach($params as $k=>$v)
        {
            if($k != 'sign') $sign .= "$k=$v&";
        }
        return strtoupper(md5($sign.'key='.$this->config['key']));
    }

    /**
     * xml转数组
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-01-07
     * @desc    description
     * @param   [string]          $xml [xm数据]
     */
    private function XmlToArray($xml)
    {
        if(!$this->XmlParser($xml)) return '';

        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    }


    /**
     * 判断字符串是否为xml格式
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-01-07
     * @desc    description
     * @param   [string]          $string [字符串]
     */
    function XmlParser($string)
    {
        $xml_parser = xml_parser_create();
        if(!xml_parse($xml_parser, $string, true))
        {
          xml_parser_free($xml_parser);
          return false;
        } else {
          return (json_decode(json_encode(simplexml_load_string($string)),true));
        }
    }

    /**
     * [HttpRequest 网络请求]
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2017-09-25T09:10:46+0800
     * @param    [string]          $url         [请求url]
     * @param    [array]           $data        [发送数据]
     * @param    [boolean]         $use_cert    [是否需要使用证书]
     * @return   [mixed]                        [请求返回数据]
     */
    private function HttpRequest($url, $data, $use_cert = false)
    {
        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $data,
        );

        if($use_cert == true)
        {
            //设置证书
            //使用证书：cert 与 key 分别属于两个.pem文件
            $options[CURLOPT_SSLCERTTYPE] = 'PEM';
            $options[CURLOPT_SSLCERT] = WEB_ROOT.'cert/wechat_app_apiclient_cert.pem';
            $options[CURLOPT_SSLKEYTYPE] = 'PEM';
            $options[CURLOPT_SSLKEY] = WEB_ROOT.'cert/wechat_app_apiclient_key.pem';
        }
 
        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}
?>