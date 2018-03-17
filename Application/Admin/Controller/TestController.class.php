<?php
/**
 * Author  :  Max.wen
 * DateTime: <15/9/20 16:47>
 */

namespace Admin\Controller;


class TestController extends BaseController
{

    /**
     * 企业付款测试
     */
    public function rebate()
    {
        
        $mchPay = new \Util\Wx\WxMchPay();
       
        $xml = $mchPay->createXml();
        header('Content-Type:text/xml');
        print_r($xml);
        exit;
        $response = $mchPay->postXmlSSLCurl($xml,$mchPay->url);
      
        if( !empty($response) ) {
            $data = simplexml_load_string($response, null, LIBXML_NOCDATA);
            echo json_encode($data,JSON_UNESCAPED_UNICODE);
        }else{
            echo json_encode( array('return_code' => 'FAIL', 'return_msg' => 'transfers_接口出错', 'return_ext' => array()) );
        }
    }
}