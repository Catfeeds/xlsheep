<?php
/**
 * Created by PhpStorm.
 * User: heqing
 * Date: 15/8/2
 * Time: 10:36
 */

namespace App\Controller;

use Think\Controller;

class AlipayController extends Controller
{
    public $appUrl = "";
    // 缓存全局商城配置对象
    public static $_shop;
    //App全局相关
    public static $_logs = './logs/alipaywap/';//log地址
    public static $_opt;//参数缓存


    public function __construct()
    {
        parent::__construct();
        $this->appUrl = "http://" . I("server.HTTP_HOST");
    }

    public function init()
    {
        $alipay_config = M("Alipay")->find();
        $config = array(
            // 即时到账方式
            'payment_type' => 1,
            // 传输协议
            'transport' => 'http',
            // 编码方式
            'input_charset' => 'utf-8',
            // 签名方法
            'sign_type' => 'MD5',
            // 支付完成异步通知调用地址
            'notify_url' => $this->appUrl . U('App/Alipay/notify_url'),
            // 支付完成同步返回地址
            'return_url' => $this->appUrl . U('App/Alipay/return_url'),
            // 证书路径
            'cacert' => DATA_PATH . 'Alipay/cacert.pem',
            // 支付宝商家 ID
            'partner' => $alipay_config['partner'],
            // 支付宝商家 KEY
            'key' => $alipay_config['key'],
            // 支付宝商家注册邮箱
            'seller_email' => $alipay_config['alipayname']
        );
        return $config;
    }

    public function alipay()
    {
//        $is_weixin = $this->is_weixin();
//        if (!$is_weixin) {
//            $this->redirect("Empty/index");
//            return;
//        }
        self::$_opt['oid'] = $oid = $_GET['oid'];
        self::$_opt['price'] = $price = $_GET['price'];

        if ($oid == '' || $price == '') {
            $msg = '订单参数不完整！';
            die($msg);
        }

        Vendor("Alipay.Alipay#class");
        $config = $this->init();
        $alipay = new \Alipay($config, TRUE);

        $params = $alipay->prepareMobileTradeData(array(
            'out_trade_no' => $oid,
            'subject' => $oid,
            'body' => $oid,
            'total_fee' => floatval($price),
            'merchant_url' => $this->appUrl . U('App/Vip/czRecord'),
            'req_id' => date('Ymdhis')
        ));

        // 移动网页版接口只支持 GET 方式提交
        $url = $alipay->buildRequestFormHTML($params, 'get');

        $this->assign("url", $url);
        $this->display();
    }

    public function simplest_xml_to_array($xmlstring)
    {
        return json_decode(json_encode((array)simplexml_load_string($xmlstring)), true);
    }

    public function notify_url()
    {
        Vendor("Alipay.Alipay#class");
        $config = $this->init();
        //计算得出通知验证结果
        $alipay = new \Alipay($config, TRUE);
        $verify_result = $alipay->verifyCallback(TRUE);

        if ($verify_result) {//验证成功
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            //请在这里加上商户的业务逻辑程序代


            //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——

            //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
            //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
            $_POST = $this->simplest_xml_to_array($_POST['notify_data']);

            //商户订单号

            $out_trade_no = $_POST['out_trade_no'];

            //支付宝交易号

            $trade_no = $_POST['trade_no'];

            //交易状态
            $trade_status = $_POST['trade_status'];


            if ($_POST['trade_status'] == 'TRADE_FINISHED') {
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //如果有做过处理，不执行商户的业务程序

                //注意：
                //该种交易状态只在两种情况下出现
                //1、开通了普通即时到账，买家付款成功后。
                //2、开通了高级即时到账，从该笔交易成功时间算起，过了签约时的可退款时限（如：三个月以内可退款、一年以内可退款等）后。

                //调试用，写文本函数记录程序运行情况是否正常
                //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
            } else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //如果有做过处理，不执行商户的业务程序

                //注意：
                //该种交易状态只在一种情况下出现——开通了高级即时到账，买家付款成功后。

                //调试用，写文本函数记录程序运行情况是否正常
                //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
            	$this->endcz($out_trade_no);
            }


            //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——

            echo "success";        //请不要修改或删除

            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        } else {
            //验证失败
            echo "fail";

            //调试用，写文本函数记录程序运行情况是否正常
            //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
        }
    }

    public function return_url()
    {
        $this->redirect("App/Alipay/czSuccess");
    }

    //充值成功后操作
    public function endcz($oid)
    {
        $m = M('vip_log');
        $order = $m->where(array('opid' => $oid))->find();
        if ($order) {
            if ($order['status'] == 1) {
                //修改状态
                $order['status'] = 2;
                $order['ctime'] = time();
                $re = $m->save($order);
                if (FALSE !== $re) {
                    //修改会员账户金额、经验、积分、等级
                    $zsmoney = $this->getZsmoney($order['money']);//充值活动赠送
                    $addmoney = $order['money'] + $zsmoney;
                    $data_vip['id'] = $order['vipid'];
                    $data_vip['money'] = array('exp', 'money+' . $addmoney);
                    $data_vip['score'] = array('exp', 'score+' . $order['score']);

                    if ($order['exp'] > 0) {
                    	$vip = M('vip')->where('id=' . $order['vipid'])->find();
                        $vipset = M('vip_set')->find();
                        $data_vip['exp'] = array('exp', 'exp+' . $order['exp']);
                        $data_vip['cur_exp'] = array('exp', 'cur_exp+' . $order['exp']);
                        $level = $this->getLevel($vip['cur_exp'] + $order['exp']);
                        $data_vip['levelid'] = $level['levelid'];
                    }
                    if (FALSE === M('vip')->save($data_vip)) {
                        //记录报警信息
                        $str = "订单号：" . $oid . "充值成功但更新会员信息失败！";
                        file_put_contents(self::$_logs . 'App_error.txt', '支付宝移动支付报警:' . date('Y-m-d H:i:s') . PHP_EOL . '通知信息:' . $str . PHP_EOL . PHP_EOL . '交易类型:TRADE_SUCCESS' . PHP_EOL . PHP_EOL, FILE_APPEND);
                    } else {
                    	$vip = M('vip')->where('id=' . $order['vipid'])->find();
                    	//发送站内消息
                    	$mmsg = M('Vip_message');
                    	$data_msg['pids'] = $vip['id'];
                    	$data_msg['title'] = "亲爱的用户，充值已完成！";
                    	$data_msg['content'] = "充值金额：".$order['money']."！，当前账户".self::$SHOP['set']['yjname'].':'.$vip['money'].',，如有异常请联系客服！';
                    	$data_msg['ctime'] = time();
                    	$rmsg = $mmsg->add($data_msg);
                    	//资金流水记录
                    	$mlog = M('Vip_log_money');
                    	$flow['vipid'] = $vip['id'];
                    	$flow['openid'] = $vip['openid'];
                    	$flow['nickname'] = $vip['nickname'];
                    	$flow['mobile'] = $vip['mobile'];
                    	$flow['money'] = $order['money'];
                    	$flow['paytype'] = 'alipay';
                    	$flow['balance'] = $vip['money'];
                    	$flow['type'] = 1;
                    	$flow['oid'] = $order['opid'];
                    	$flow['ctime'] = time();
                    	$flow['remark'] = '充值订单：'.$order['opid'].'完成入款';
                    	$rflow = $mlog->add($flow);
                    }
                } else {
                    //记录报警信息
                    $str = "订单号：" . $oid . "充值成功但未更新日志信息！";
                    file_put_contents(self::$_logs . 'App_error.txt', '支付宝移动支付报警:' . date('Y-m-d H:i:s') . PHP_EOL . '通知信息:' . $str . PHP_EOL . PHP_EOL . '交易类型:TRADE_SUCCESS' . PHP_EOL . PHP_EOL, FILE_APPEND);
                }
            }
        }
    }

    //根据充值金额计算赠送金额
    public function getZsmoney($money)
    {
        $vipset = M('vip_set')->find();
        $cz_rule = explode(",", $vipset['cz_rule']);
        $zsmoney = 0;
        foreach ($cz_rule as $k => $v) {
            $cz_rule[$k] = explode(":", $v);
        }
        foreach ($cz_rule as $k => $v) {
            if ($k + 1 == count($cz_rule)) {
                if ($money >= $cz_rule[$k][0]) {
                    $zsmoney = intval($cz_rule[$k][1]);
                }
            } else {
                if ($money >= $cz_rule[$k][0] && $money < $cz_rule[$k + 1][0]) {
                    $zsmoney = intval($cz_rule[$k][1]);
                }
            }
        }
        return $zsmoney;
    }
    
    public function czSuccess(){
    	$this->display();
    }
}