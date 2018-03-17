<?php

namespace Util\Wx;

/**
 * 微信企业付款操作类
 * Author  :  Max.wen
 * DateTime: <15/9/16 11:00>
 */
class WxMchPay extends Wxpaysdk
{
    
    protected $wx_config;

    public function __construct($amount, $openid, $oidNum,$desc="零钱提现",$ip = '120.24.19.107')
    {		

    	$m = M('Set');
    	$this->wx_config = $m->find();

    	$this->parameters = [	
    							'mch_appid' => $this->wx_config['wxappid'],
    							'mchid' => '1451387602',
    							
    							'nonce_str' => $this->createNoncestr(),
    							'partner_trade_no' => $oidNum,
    							'openid' => $openid,
    							'check_name' => 'NO_CHECK',
    							'amount' => $amount,
    							'desc' => $desc,
    							'spbill_create_ip' => $ip,	
    						];

     	$this->parameters['sign'] = $this->getSign($this->parameters);
        $this->url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
        $this->curl_timeout = 30;

    }


    /**
	 * 	作用：使用证书，以post方式提交xml到对应的接口url
	 */
	function postXmlSSLCurl($xml, $url, $second = 30) {

		
		$ch = curl_init();
		//超时时间
		curl_setopt($ch, CURLOPT_TIMEOUT, $second);
		//这里设置分销，如果有的话
		//curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
		//curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
		curl_setopt($ch, CURLOPT_URL, $this->url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		//设置header
		curl_setopt($ch, CURLOPT_HEADER, FALSE);

		//要求结果为字符串且输出到屏幕上
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		//设置证书
		//使用证书：cert 与 key 分别属于两个.pem文件
		//默认格式为PEM，可以注释
	

		//默认格式为PEM，可以注释
	
		curl_setopt($ch,CURLOPT_SSLCERT,getcwd().'/cert/apiclient_cert.pem');
		curl_setopt($ch,CURLOPT_SSLKEY,getcwd().'/cert/apiclient_key.pem');
		curl_setopt($ch,CURLOPT_CAINFO,'rootca.pem'); 

		//post提交方式
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
		$data = curl_exec($ch);

		//返回结果
		if ($data) {
			curl_close($ch);
			return $data;
		} else {
			$error = curl_errno($ch);
			echo "curl出错，错误码:$error" . "<br>";
			echo "<a href='http://curl.haxx.se/libcurl/c/libcurl-errors.html'>错误原因查询</a></br>";
			curl_close($ch);
			return false;
		}
	}
   

	/**
	 * 	作用：生成签名
	 */
	public function getSign($Obj) {
		foreach ($Obj as $k => $v) {
			$Parameters[$k] = $v;
		}
		// var_dump($Parameters);
		// exit;
		//签名步骤一：按字典序排序参数
		ksort($Parameters);
		$String = $this->formatBizQueryParaMap($Parameters, false);
		//echo '【string1】'.$String.'</br>';
		//签名步骤二：在string后加入KEY
		$String = $String . "&key=" . $this->wx_config['wxmchkey'] ;
		//echo "【string2】".$String."</br>";
		//签名步骤三：MD5加密
		$String = md5($String);
		//echo "【string3】 ".$String."</br>";
		//签名步骤四：所有字符转为大写
		$result_ = strtoupper($String);
		//echo "【result】 ".$result_."</br>";
		return $result_;
	}



   /**
     * 生成请求xml数据
     * @return string
     */
    public function createXml()
    {	
    	
        return  $this->arrayToXml($this->parameters);
      
    }

    public function payExec(){
    	$xml = $this->createXml();
    	
    	$response = $this->postXmlSSLCurl($xml,$mchPay->url);
      
        if( !empty($response) ) {
            $data = simplexml_load_string($response, null, LIBXML_NOCDATA);
            return json_encode($data,JSON_UNESCAPED_UNICODE);
        }else{
            return json_encode( array('return_code' => 'FAIL', 'return_msg' => 'transfers_接口出错', 'return_ext' => array()) );
        }
    }

}