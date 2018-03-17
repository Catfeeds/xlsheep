<?php
// 微信支付JSAPI版本
// 基于版本 V3
// 金融产品支付
// By App 2015-1-20
namespace Home\Controller;

use Think\Controller;

class WxpayjrController extends Controller
{
    //App全局相关
    public static $_url; //动态刷新
    public static $_opt; //参数缓存
    public static $_logs = ''; //log地址
    //JOELCMS设置缓存
    protected static $SET;
    protected static $SHOP;
    protected static $VIP;

    //微信缓存
    protected static $_wx;
    protected static $_wxappid;
    protected static $_wxappsecret;

    public function __construct()
    {
        //App自定义全局
        parent::__construct();
        header("Content-type: text/html; charset=utf-8");
        //刷新全局地址
        self::$_url = "http://" . $_SERVER['HTTP_HOST'];
        //获取全局配置
        self::$SET = M('Set')->find();
        self::$SHOP = M('Shop_set')->find();
        self::$VIP = M('Vip_set')->find();

        if (!self::$SET) {
            die('系统未配置！');
        }
        //全局缓存微信
        self::$_wxappid = self::$SET['wxappid'];
        self::$_wxappsecret = self::$SET['wxappsecret'];
        $options['appid'] = self::$_wxappid;
        $options['appsecret'] = self::$_wxappsecret;
        self::$_wx = new \Util\Wx\Wechat($options);
    }

    //支付宝业务逻辑 By App.
    public function index()
    {

        echo "Hello World!";

    } //index类结束

    public function info()
    {

    }
    //支付出口
    //App 2015.1.20
    //无返回值，接受订单参数并转向到微信支付接口
    public function pay()
    {
        $opt = I('get.');

        self::$_opt['oid'] = $oid = $_GET['oid'];
        self::$_opt['openid'] = $openid = $_SESSION['wxpayopenid'];

        if (!$oid) {
            $this->diemsg(0, '订单参数不完整！请重新尝试！');
        }
        if (!$openid) {
            $this->diemsg(0, '未获取会员数据，请重新尝试！');
        }

        //取ORDER
        $cache = M('Finance_order')->where(array('oid' => $oid))->find();
        if (!$cache) {
            $this->error('此订单不存在！', 'App/Buy/index');
        }
        if ($cache['ispay']) {
            $this->error('此订单已支付！请勿重复支付！', 'App/Buy/index');
        }

        $this->assign('cache', $cache);

        //微信支付封装
        $options['appid'] = self::$_wxappid;
        $options['appsecret'] = self::$_wxappsecret;
        $options['mchid'] = self::$SET['wxmchid'];
        $options['mchkey'] = self::$SET['wxmchkey'];
        $paysdk = new \Util\Wx\Wxpaysdk($options);

        $paysdk->setParameter("openid", $openid); //会员openid
        $paysdk->setParameter("body", "支付商品订单"); //商品描述
        //自定义订单号，此处仅作举例
        $timeStamp = time();
        $paysdk->setParameter("out_trade_no", $cache['oid']); //商户订单号
        $paysdk->setParameter("total_fee", intval($cache['payprice'] * 100)); //总金额单位为分，不允许有小数
        $paysdk->setParameter("notify_url", 'http://' . $_SERVER['HTTP_HOST'] . __ROOT__ . '/Home/Wxpayjr/nd/'); //交易通知地址
        $paysdk->setParameter("trade_type", "JSAPI"); //交易类型

        $prepayid = $paysdk->getPrepayId();
        if ($prepayid) {
            $paysdk->setPrepayId($prepayid);
        } else {
            $this->diemsg(0, '未成功生成支付订单，请重新尝试！');
        }

        //获取前端PAYAPI
        $wOpt['appId'] = self::$_wxappid;
        $timeStamp = time();
        $wOpt['timeStamp'] = "$timeStamp";
        $wOpt['nonceStr'] = $this->createNoncestr(8);
        $wOpt['package'] = 'prepay_id=' . $prepayid;
        //$wOpt['package'] = $prepayid;
        $wOpt['signType'] = 'MD5';
        ksort($wOpt, SORT_STRING);
        $string = "";
        foreach ($wOpt as $key => $v) {
            $string .= "{$key}={$v}&";
        }
        $string .= "key=" . self::$SET['wxmchkey'];
        $wOpt['paySign'] = strtoupper(md5($string));
        $wOpt['package'] = $prepayid;
        $str = "";
        foreach ($wOpt as $key => $v) {
            $str .= "{$key}={$v}&";
        }
        $url = "http://" . $_SERVER['HTTP_HOST'] . __ROOT__ . "/wxpayjr.php?$str";
        header("Location:" . $url);
    }

    public function createNoncestr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    //停止不动的信息通知页面处理
    public function diemsg($status, $msg)
    {
        //成功为1，失败为0
        $status = $status ? $status : '0';
        $this->assign('status', $status);
        $this->assign('msg', $msg);
        $this->display('Base_diemsg');
        die();
    }

    //用户中断支付的跳转地址
    public function paycancel()
    {
        $url = self::$_url . '/App/Buy/orderList/sid/' . $_SESSION['wxpaysid'];
        header('Location:' . $url); //取消支付并跳转回商城
    }

    //当支付成功后的返回控制器
    public function payback()
    {
        //$status=I('status');
        $sta = '0';
        $msg = '';
        //dump($_GET);
        $verify_result = $this->verifyReturn();
        if ($verify_result) {
            //验证成功
            $out_trade_no = $_GET['out_trade_no']; //支付宝交易号
            $trade_no = $_GET['trade_no']; //支付宝交易号
            $result = $_GET['result']; //交易状态
            if ($result == 'success') {
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //如果有做过处理，不执行商户的业务程序
                $sta = '1';
                $msg = '支付成功!';
                //修改订单状态
                $this->endpay($out_trade_no);
                $url = self::$_url . '/App/Buy/orderList/sid/' . self::$_opt['sid'];
                header('Location:' . $url);
            } else {
                echo "支付失败"; //这里永远不会调用
                $url = self::$_url . '/App/Buy/orderList/sid/' . self::$_opt['sid'];
                header('Location:' . $url);
            }
        } else {
            die('验证失败');
        }
    }

    //支付成功后后台接受方案
    public function nd()
    {
        $str = "";
        foreach ($_POST as $k => $v) {
            $str = $str . $k . "=>" . $v . '  ';
        }
        file_put_contents(self::$_logs . './Data/app_wxpayjrdnd.txt', '响应参数:' . date('Y-m-d H:i:s') . PHP_EOL . '通知信息:' . $str . PHP_EOL . PHP_EOL . PHP_EOL, FILE_APPEND);
        //使用通用通知接口
        $notify = new \Util\Wx\Wxpayndsdk();

        //存储微信的回调
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        $notify->saveData($xml);
        //验证签名，并回应微信。
        //对后台通知交互时，如果微信收到商户的应答不是成功或超时，微信认为通知失败，
        //微信会通过一定的策略（如30分钟共8次）定期重新发起通知，
        //尽可能提高通知的成功率，但微信不保证通知最终能成功。
        if ($notify->checkSign() == FALSE) {
            $notify->setReturnParameter("return_code", "FAIL"); //返回状态码
            $notify->setReturnParameter("return_msg", "签名失败"); //返回信息
        } else {
            $notify->setReturnParameter("return_code", "SUCCESS"); //设置返回码
        }
        $returnXml = $notify->returnXml();
        echo $returnXml;

        //==商户根据实际情况设置相应的处理流程，此处仅作举例=======

        if ($notify->checkSign() == TRUE) {
            //获取订单号
            $out_trade_no = $notify->data["out_trade_no"];

            if ($notify->data["return_code"] == "FAIL") {
                //此处应该更新一下订单状态，商户自行增删操作
                //$log_->log_result($log_name,"【通信出错】:\n".$xml."\n");
                file_put_contents(self::$_logs . './Data/app_wxpayjr_err.txt', '通讯出错:' . date('Y-m-d H:i:s') . PHP_EOL . '通知信息:' . $str . PHP_EOL . '订单号:' . $out_trade_no . PHP_EOL . '交易结果:通讯出错' . PHP_EOL . PHP_EOL, FILE_APPEND);
            } elseif ($notify->data["result_code"] == "FAIL") {
                //此处应该更新一下订单状态，商户自行增删操作
                //$log_->log_result($log_name,"【业务出错】:\n".$xml."\n");
                file_put_contents(self::$_logs . './Data/app_wxpayjr_err.txt', '业务出错:' . date('Y-m-d H:i:s') . PHP_EOL . '通知信息:' . $str . PHP_EOL . '订单号:' . $out_trade_no . PHP_EOL . '交易结果:业务出错' . PHP_EOL . PHP_EOL, FILE_APPEND);
            } else {
                //此处应该更新一下订单状态，商户自行增删操作
                //$log_->log_result($log_name,"【支付成功】:\n".$xml."\n");
                $this->endpay($out_trade_no);
                file_put_contents(self::$_logs . './Data/app_wxpayjr_ok.txt', '支付成功:' . date('Y-m-d H:i:s') . PHP_EOL . '通知信息:' . $str . PHP_EOL . '订单号:' . $out_trade_no . PHP_EOL . '交易结果:交易成功' . PHP_EOL . PHP_EOL, FILE_APPEND);

            }

            //商户自行增加处理流程,
            //例如：更新订单状态
            //例如：数据库操作
            //例如：推送支付完成信息
        }
    }
    
    //支付成功后后台接受方案
    public function nderr()
    {
        $str = "";
        foreach ($_POST as $k => $v) {
            $str = $str . $k . "=>" . $v . '  ';
        }
        file_put_contents(self::$_logs . 'App_wxpayjr_err.txt', '响应参数:' . date('Y-m-d H:i:s') . PHP_EOL . '通知信息:' . $str . PHP_EOL . PHP_EOL . PHP_EOL, FILE_APPEND);

    }

    //付款成功后操作
    private function endpay($oid)
    {
        $m = M('Finance_order');
        $mgoods = M('Finance_goods');
        $dwechat = D('Wechat');
        $order = $m->where(array('oid' => $oid))->find();
        if ($order) {
            if ($order['status'] == 1) {
                //修改状态
                $order['ispay'] = 1;
                $order['status'] = 2;
                $order['paytime'] = time();
                $order['contract'] = build_contract_no();
                //$order['aliaccount'] = $buyer_email;
                $re = $m->save($order);
                if (FALSE !== $re) {
                   	//销量计算-只减不增
    				$rsell = doSells($order);
    				if($rsell) {
                        $goods = $mgoods->where('id=' . $order['goodsid'])->find();
                        if($goods['isproject']){
                            handleCommissionBuylog($order, $orderid);
                        }else{
                            handleCommissionBuy($order, $orderid);//发放分销佣金
                        }
    					//生成订单商品详情
    					doOrderGoods($order);
    					$goods = $mgoods->where('id=' . $order['goodsid'])->find();
    					if($goods['ismoney']){
                            $address = M('Finance_huibao')->where('id = '.$order['tcid'])->getField('address');
                            if($address==0){
                                doFhLog($order);
                            }
                        }
    					//生成合同
    					if(!$goods['isproject']){
                            $order['contract'] = build_contract_no();
                            $m -> where('id = '.$orderid) -> setField('contract',$order['contract']);
                            doContract($order);
                        }
    					//记录日志
    					$mlog = M('Finance_order_log');
    					$mslog = M('Finance_order_syslog');
    					$dlog['oid'] = $order['id'];
    					$dlog['msg'] = '微信付款成功';
    					$dlog['ctime'] = time();
    					$mlog->add($dlog);
    					$dlog['type'] = 2;
    					
    					$dlog['paytype'] = $order['paytype'];
    					$mslog->add($dlog);
    					$mvip = M('Vip');
    					$vip = $mvip->where('id=' . $order['vipid'])->find();
    					//资金流水记录
    					$mlog = M('Vip_log_money');
    					$flow['vipid'] = $vip['id'];
    					$flow['openid'] = $vip['openid'];
    					$flow['nickname'] = $vip['nickname'];
    					$flow['mobile'] = $vip['mobile'];
    					$flow['money'] = -$order['payprice'];
    					$flow['paytype'] = 'wxpay';
    					$flow['balance'] = $vip['money'];
    					$flow['type'] = 4;
    					$flow['oid'] = $order['oid'];
    					$flow['ctime'] = time();
    					$goodsinfo = M('Finance_goods')->where('id='. $order['goodsid'])->find();
    					$flow['remark'] = $goodsinfo ? '众筹创业项目：'.$goods['name'] : '';
    					$rflow = $mlog->add($flow);
    					/*
    					//检查是否当前用户首单
    					$map['vipid'] = $vip['id'];
    					$map['ispay'] = 1;
    					$fmap['vipid'] = $vip['id'];
    					$fmap['ispay'] = 1;
    					$fmap['id'] = array('neq', $order['id']);
    					$shop_order = M('Shop_order')->where($map)->count();
    					$finance_order = M('Finance_order')->where($fmap)->count();
    					$firstorder = false;
    					if(empty($shop_order) && empty($finance_order)) {
    						$firstorder = true;
    					}
    					$old = $mvip->where('id='.$vip['pid'])->find();
    					if(!empty($old)) {
    						$tj_xf_money = self::$VIP['tj_xf_money'];
    						$tj_xf_score = self::$VIP['tj_xf_score'];
    						if ($tj_xf_money || $tj_xf_score) {
    							$msg = "带来消费奖励：<br>消费用户：" . $vip['nickname'] . "<br>奖励内容：<br>";
    							$mglog = "获得带来消费奖励:";
    							if ($tj_xf_score>0) {
    								$msg = $msg . $tj_xf_score . "个积分<br>";
    								$mglog = $mglog . $tj_xf_score . "个积分；";
    							}
    							//检查是否超过佣金封顶
    							$logmap['vipid'] = $vip['id'];
    							$logmap['status'] = array('neq',2);
    							$tj_money = M('Vip_log_xftj')->where($logmap)->sum('money');
    							$money =  $order['payprice']/1000*$tj_xf_money;
    							if($tj_money > self::$VIP['max_tg_commission']) {
    								$money = 0;
    							} elseif($tj_money + $money > self::$VIP['max_tg_commission']){
    								$money = self::$VIP['max_tg_commission'] - $tj_money;
    							}
    							if ($firstorder && $money>0 && $tj_xf_money>0) {
    								$msg = $msg . $money . "余额<br>";
    								$mglog = $mglog . $money . "余额；";
    								$msg = $msg . "奖励将于稍后自动打入您的帐户！感谢您的支持！";
									$data_mglog['vipid'] = $old['id'];
									$data_mglog['origin'] = 2;
									$data_mglog['oid'] = $order['id'];
									$data_mglog['money'] = $money;
									$data_mglog['score'] = $tj_xf_score;
									$data_mglog['msg'] = $mglog;
									$data_mglog['status'] = 0;
									$data_mglog['ctime'] = time();
									$rmglog = M('Vip_log_xftj')->add($data_mglog);
								}
								if($msg != '') {
									$data_msg['pids'] = $old['id'];
									$data_msg['title'] = "你获得一份带来消费奖励！";
									$data_msg['content'] = $msg;
									$data_msg['ctime'] = time();
									$rmsg = M('Vip_message')->add($data_msg);
								}
    						}
    					}
    					*/
    					// 插入订单支付成功模板消息=====================
    					$templateidshort = 'OPENTM200444326';
    					$dwechat = D('Wechat');
    					$templateid = $dwechat->getTemplateId($templateidshort);
                        $goodsname = M('Finance_goods')->where('id = '.$order['goodsid'])->getField('name');
    					if ($templateid) { // 存在才可以发送模板消息
    						$data = array();
    						$data['touser'] = $vip['openid'];
    						$data['template_id'] = $templateid;
    						$data['topcolor'] = "#00FF00";
                            $data['url'] = $_SERVER['HTTP_HOST'] . U("/App/Buy/orderDetail", array("orderid" => $order["id"]));
    						$data['data'] = array(
    								'first' => array('value' => '您好，您的众筹创业项目已付款成功'),
                                    'keyword1' => array('value' => $order['oid']),
                                    'keyword2' => array('value' => $goodsname),
                                    'keyword3' => array('value' => $order['payprice'].'元'),
                                    'keyword4' => array('value' => date("Y-m-d H:i:s",time())),
                                    'remark' => array('value' => '感谢您对众筹项目的支持！')
    						);
    						$options['appid'] = self::$_wxappid;
    						$options['appsecret'] = self::$_wxappsecret;
    						$wx = new \Util\Wx\Wechat($options);
    						$re = $wx->sendTemplateMessage($data);
    					}
    				} else {
    					//退回余额
    					$re = $mvip->where('id=' . $_SESSION['WAP']['vipid'])->setInc('money', $order['payprice']);
    					//后端日志
    					if(FALSE !== $re) {
    						$mlog = M('Finance_order_syslog');
    						$dlog['oid'] = $order['id'];
    						$dlog['msg'] = '微信支付失败（库存不足）';
    						$dlog['type'] = -1;
    						$dlog['ctime'] = time();
    						$rlog = $mlog->add($dlog);
    					} 
    					//记录报警信息
    					$str = "金融订单号：" . $oid . "支付成功但因库存不足未更新订单状态！" . "买家ID：" . $order['vipid'];
    					file_put_contents(self::$_logs . '/Data/app_wxpayjr_err.txt', '微信支付报警:' . date('Y-m-d H:i:s') . PHP_EOL . '通知信息:' . $str . PHP_EOL . PHP_EOL . '交易类型:TRADE_SUCCESS' . PHP_EOL . PHP_EOL, FILE_APPEND);
    				}
    	
                } else {
                    //记录报警信息
                    $str = "金融订单号：" . $oid . "支付成功但未更新订单状态！" . "买家ID：" . $order['vipid'];
                    file_put_contents(self::$_logs . '/Data/app_wxpayjr_err.txt', '微信支付报警:' . date('Y-m-d H:i:s') . PHP_EOL . '通知信息:' . $str . PHP_EOL . PHP_EOL . '交易类型:TRADE_SUCCESS' . PHP_EOL . PHP_EOL, FILE_APPEND);
                }
            }
        }
    }
    //发送商家模板消息
    //type=0:订单新生成（未付款）
    //type=1:订单已付款
    function sendMobanMsmToShop($oid, $type, $flag = FALSE)
    {
        //构造消息体
        $order = M('wds_order')->where('id=' . $oid)->find();
        $shop = M('Shop')->where('id=' . $order['sid'])->find();
        $ppid = $order['ppid'];
        if ((($type == 0 && $shop['newmsg'] == 1) || ($type == 1 && $shop['paymsg'] == 1)) && $shop['kfids'] != '') {
            if ($order['mobile'] == '') {
                $addressinfo = M('vip_address')->where('ppid=' . $ppid)->order('isdefault asc')->select();
                if ($addressinfo) {
                    $customerInfo = $addressinfo[0]['name'] . ' ' . $addressinfo[0]['mobile'];
                } else {
                    $customerInfo = '暂未登记';
                }
            } else {
                $customerInfo = $order['name'] . ' ' . $order['mobile'];
            }

            if ($type == 0) {
                $first = "新订单通知（暂未付款）";
            } else {
                $first = $flag ? "订单通知（货到付款）" : "订单通知（已付款）";
            }
            $first = $first . "\\n订单编号：" . $order['oid'];

            $arr = explode('|', $order['goods']);
            $money = 0;
            $remark = "\\n";
            foreach ($arr as $k => $val) {
                $a = explode(',', $val);
                $money = $money + $a['5'] * $a['3'];
                $remark = $remark . $a['1'] . "：" . $a['3'] . $a['4'] . "\\n";
            }

            $template = array(
                'touser' => "",
                'template_id' => "Gk4Olsma5qlneSsdAWK3J9w1t7eRxVkNRGFcxiHfk6g",
                'url' => "",
                'topcolor' => "#70c02f",
                'data' => array(
                    //标题
                    'first' => array(
                        'value' => urlencode($first),
                        'color' => "#FF0000",
                    ),
                    //提交时间
                    'tradeDateTime' => array(
                        'value' => urlencode(date('Y-m-d H:i:s', time())),
                        //'color' => "#0000FF",
                    ),
                    //订单类型
                    'orderType' => array(
                        'value' => urlencode($shop['name']),
                        //'color' => "#0000FF",
                    ),
                    //顾客信息
                    'customerInfo' => array(
                        'value' => urlencode($customerInfo),
                        //'color' => "#0000FF",
                    ),
                    //商品名称
                    'orderItemName' => array(
                        'value' => urlencode("订单总价"),
                        //'color' => "#0000FF",
                    ),
                    //商品规格及数量
                    'orderItemData' => array(
                        'value' => urlencode($order['money'] . '元'),
                        'color' => "#006cff",
                    ),
                    //备注
                    'remark' => array(
                        'value' => urlencode($remark),
                        'color' => "#565656",
                    ),
                ),
            );
            //发送消息
            $options['appid'] = self::$_wxappid;
            $options['appsecret'] = self::$_wxappsecret;
            $mx = new \Util\Wx\Wechat($options);

            $shop['kfids'] = $shop['kfids'] == '' ? '10' : $shop['kfids'] . ',10'; //发送给指定客服id：10

            $kfidArr = explode(",", $shop['kfids']);
            foreach ($kfidArr as $k => $val) {
                $openid = M('vip')->where('id=' . $val)->getField('openid');
                $template['touser'] = $openid;
                $rtn = $mx->sendTemplateMessage($template);
                file_put_contents('./logs/message/msg.txt', PHP_EOL . '发送商家模板消息成功:' . date('Y-m-d H:i:s') . $rtn, FILE_APPEND);
            }
        }
    }

    //发送会员模板消息
    function sendMobanMsmToVip($oid)
    {
        //构造消息体
        $order = M('wds_order')->where('id=' . $oid)->find();
        $shop = M('Shop')->where('id=' . $order['sid'])->find();
        $ppid = $order['ppid'];

        $openid = M('vip')->where('id=' . $ppid)->getField('openid');

        $arr = explode('|', $order['goods']);
        $money = 0;
        $goodsinfo = $shop['name'] . "\\n\\n";
        foreach ($arr as $k => $val) {
            $a = explode(',', $val);
            $money = $money + $a['5'] * $a['3'];
            $goodsinfo = $goodsinfo . $a['1'] . "：" . $a['3'] . $a['4'] . "\\n";
        }

        $template = array(
            'touser' => $openid,
            'template_id' => "5qJqoRxrgO8W9amLF6aejYMag4mAxSUh3OpMrgnJ2cw",
            'url' => "http://" . $_SERVER['HTTP_HOST'] . __ROOT__ . "/App/Wds/orderdetail/id/" . $oid,
            'topcolor' => "#70c02f",
            'data' => array(
                //标题
                'first' => array(
                    'value' => urlencode(date('Y-m-d H:i:s', time()) . "\\n您的订单已完成付款"),
                    'color' => "#FF0000",
                ),
                //订单金额
                'orderProductPrice' => array(
                    'value' => urlencode($order['money'] . "元"),
                    'color' => "#006cff",
                ),
                //商品详情
                'orderProductName' => array(
                    'value' => urlencode($goodsinfo),
                    'color' => "#565656",
                ),
                //收货信息
                'orderAddress' => array(
                    'value' => urlencode($order['address'] . "  " . $order['name']),
                    //'color' => "#0000FF",
                ),
                //订单编号
                'orderName' => array(
                    'value' => urlencode($order['oid'] . "\\n验证码：" . $order['pin']),
                    //'color' => "#0000FF",
                ),
                //备注
                'remark' => array(
                    'value' => urlencode(""),
                    //'color' => "#006cff",
                ),
            ),
        );

        //发送消息
        $options['appid'] = self::$_wxappid;
        $options['appsecret'] = self::$_wxappsecret;
        $mx = new \Util\Wx\Wechat($options);
        $rtn = $mx->sendTemplateMessage($template);
        file_put_contents('./logs/message/msg.txt', PHP_EOL . '发送会员模板消息成功:' . date('Y-m-d H:i:s') . $rtn, FILE_APPEND);
    }
} //Wxpayjr类结束