<?php
namespace App\Controller;
use Util\Dh\Sms;

class SmsController extends BaseController{

   //手机号码绑定
   public function getCode() {
   	
   		if(!self::$WAP['vipid']) {
   			$this->error('您未登陆！');
   		}
   		if(self::$WAP['vip']['mobile'] != '') {
   			$this->error('您已验证手机号！');
   		}
        
        $mobile = I('post.mobile', '', 'strip_tags');
	   	if(!preg_match("/^(13[0-9]|14[57]|15[012356789]|17[0678]|18[0-9])\d{8}$/",$mobile)){
	   		$this->error('手机号码格式不正确');
	   	}
	   	$vip = M('Vip')->where(array('mobile'=>$mobile))->find();
	   	if($vip) {
	   		$this->error('该手机号码已被绑定');
	   	}
        
	   	// 短信内容参数
	   	$code = $this->randString();
        session(array("name"=>"Vcode", "expire"=>300));
        session("Vcode", $code);
	   	// 设置请求参数
        $sms = new Sms;
        $content = '您的短信验证码：' . $code . '，请勿泄露。';
        $result = $sms->send($mobile, $content);
	   	if($result === true) {
	   		$this->success('发送成功');
	   	} else {
	   		$this->error('发送失败!');
	   	}
  }
  
  //重置支付密码
  public function getPayPwdCode() {
  
  	if(!self::$WAP['vipid']) {
  		$this->error('您未登陆！');
  	}
  	$vip = self::$WAP['vip'];
    
  	if(!$vip['is_auth']) {
  		$this->error('您未进行实名认证');
  	}
  	if(!$vip['mobile']) {
  		$this->error('您未验证手机号码');
  	}
    
  	// 短信内容参数
    $code = $this->randString();
    session(array("name"=>"Vcode", "expire"=>300));
    session("Vcode", $code);
  	// 设置请求参数
    $sms = new Sms;
    $content = '您的短信验证码：' . $code . '，请勿泄露。';
    session(array("name"=>"PwdVcode", "expire"=>300));
    session("PwdVcode", $smsParams['code']);
    $result = $sms->send($vip['mobile'], $content);
    if($result === true) {
        $this->success('发送成功');
    } else {
        $this->error('发送失败!');
    }
  }
  
  /**
   * 获取随机位数数字
   * @param  integer $len 长度
   * @return string
   */
  protected static function randString($len = 6)
  {
  	$chars = str_repeat('0123456789', $len);
  	$chars = str_shuffle($chars);
  	$str   = substr($chars, 0, $len);
  	return $str;
  }
}
