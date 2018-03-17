<?php
// 微信支付JSAPI版本
// 基于版本 V3
// By App 2015-1-20
namespace Home\Controller;

use Think\Controller;

class WxpayczController extends Controller
{
    //App全局相关
    public static $_url; //动态刷新
    public static $_opt; //参数缓存
    public static $_logs = ''; //log地址
    //JOELCMS设置缓存
    protected static $SET;
    protected static $SHOP;

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
    //在线充值
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
    	$cache = M('vip_log')->where(array('opid' => $oid,'type'=>7))->find();
    	if (!$cache) {
    		$this->error('此订单不存在！', 'App/vip/cz');
    	}
    	if ($cache['ispay']) {
    		$this->error('此订单已支付！请勿重复支付！', 'App/vip/cz');
    	}
    
    	//微信支付封装
    	$options['appid'] = self::$_wxappid;
    	$options['appsecret'] = self::$_wxappsecret;
    	$options['mchid'] = self::$SET['wxmchid'];
    	$options['mchkey'] = self::$SET['wxmchkey'];
    	$paysdk = new \Util\Wx\Wxpaysdk($options);
    	
    	$paysdk->setParameter("openid", $openid); //会员openid
    	$paysdk->setParameter("body", "在线充值"); //商品描述
    	//自定义订单号，此处仅作举例
    	$timeStamp = time();
    	$paysdk->setParameter("out_trade_no", $cache['opid']); //商户订单号
    	$paysdk->setParameter("total_fee", intval($cache['money'] * 100)); //总金额单位为分，不允许有小数
    	$paysdk->setParameter("notify_url", 'http://' . $_SERVER['HTTP_HOST'] . '/Home/Wxpaycz/nd/'); //交易通知地址
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
    	$wOpt['signType'] = 'MD5';
    	ksort($wOpt, SORT_STRING);
    	$string = "";
    	foreach ($wOpt as $key => $v) {
    		$string .= "{$key}={$v}&";
    	}
    	$string .= "key=" . self::$SET['wxmchkey'];
    	$wOpt['paySign'] = strtoupper(md5($string));
        $wOpt['package'] = $prepayid;
        $wOpt['orderMake'] = $_SESSION['orderMake'];
        unset($_SESSION['orderMake']);
    	$str = "";
    	foreach ($wOpt as $key => $v) {
    		$str .= "{$key}={$v}&";
    	}
        // var_dump($str);exit;
    	$url = "http://" . $_SERVER['HTTP_HOST'] . "/wxpaycz.php?$str";
    
    	header("Location:" . $url);
    }
    
    //支付成功后后台接受方案(在线充值)
    public function nd()
    {
    	$str = "";
    	foreach ($_POST as $k => $v) {
    		$str = $str . $k . "=>" . $v . '  ';
    	}
    	file_put_contents(self::$_logs . './Data/app_wxcznd.txt', '响应参数:' . date('Y-m-d H:i:s') . PHP_EOL . '通知信息:' . $str . PHP_EOL . PHP_EOL . PHP_EOL, FILE_APPEND);
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
    
    	if ($notify->checkSign() == TRUE) {
    		//获取订单号
    		$out_trade_no = $notify->data["out_trade_no"];
    
    		if ($notify->data["return_code"] == "FAIL") {
    			file_put_contents(self::$_logs . './Data/app_wxczerr.txt', '通讯出错:' . date('Y-m-d H:i:s') . PHP_EOL . '通知信息:' . $str . PHP_EOL . '在线充值订单号:' . $out_trade_no . PHP_EOL . '交易结果:通讯出错' . PHP_EOL . PHP_EOL, FILE_APPEND);
    		} elseif ($notify->data["result_code"] == "FAIL") {
    			file_put_contents(self::$_logs . './Data/app_wxczerr.txt', '业务出错:' . date('Y-m-d H:i:s') . PHP_EOL . '通知信息:' . $str . PHP_EOL . '在线充值订单号:' . $out_trade_no . PHP_EOL . '交易结果:业务出错' . PHP_EOL . PHP_EOL, FILE_APPEND);
    		} else {
    			$this->end($out_trade_no);
    			file_put_contents(self::$_logs . './Data/app_wxczok.txt', '支付成功:' . date('Y-m-d H:i:s') . PHP_EOL . '通知信息:' . $str . PHP_EOL . '在线充值订单号:' . $out_trade_no . PHP_EOL . '交易结果:交易成功' . PHP_EOL . PHP_EOL, FILE_APPEND);
    
    		}
    	}
    }
    
	//充值完成后操作
	public function end($oid) 
	{
		$m = M('vip_log');
		$order = $m->where(array('opid' => $oid,'type'=>7))->find();
		if ($order) {
			if ($order['status'] == 1) {
				//修改状态
				$order['status'] = 2;
				$order['ctime'] = time();
				$re = $m->save($order);
				if (FALSE !== $re) {
					//修改会员账户金额、经验、积分、等级
// 					$zsmoney = $this->getZsmoney($order['money']);//充值活动赠送
// 					$addmoney = $order['money'] + $zsmoney;
					$data_vip['id'] = $order['vipid'];
					$data_vip['money'] = array('exp', 'money+' . $order['money']);
					$data_vip['score'] = array('exp', 'score+' . $order['score']);
					if ($order['exp'] > 0) {
						$vip = M('vip')->where('id=' . $order['vipid'])->find();
						$vipset = M('vip_set')->find();
						$data_vip['exp'] = array('exp', 'exp+' . $order['exp']);
						$data_vip['cur_exp'] = array('exp', 'cur_exp+' . $order['exp']);
						$level = $this->getLevel($vip['cur_exp'] + $order['exp']);
						$data_vip['levelid'] = $level['levelid'];
					}
					$res = M('vip')->save($data_vip);
					if (FALSE !== $res) {
						$mvip = M('Vip');
						$vip = $mvip->where('id=' . $order['vipid'])->find();
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
						$flow['paytype'] = 'wxpay';
						$flow['balance'] = $vip['money'];
						$flow['type'] = 1;
						$flow['oid'] = $order['opid'];
						$flow['ctime'] = time();
						$flow['remark'] = '充值订单：'.$order['opid'].'完成入款';
						$rflow = $mlog->add($flow);
						//记录报警信息
						$str = "订单号：" . $oid . "充值成功但更新会员信息失败！";
						file_put_contents(self::$_logs . '/Data/app_wxczerr.txt', '微信支付报警:' . date('Y-m-d H:i:s') . PHP_EOL . '通知信息:' . $str . PHP_EOL . PHP_EOL . '交易类型:TRADE_SUCCESS' . PHP_EOL . PHP_EOL, FILE_APPEND);
					}
				} else {
					//记录报警信息
					$str = "订单号：" . $oid . "充值成功但未更新日志信息！";
					file_put_contents(self::$_logs . '/Data/app_wxczerr.txt', '微信支付报警:' . date('Y-m-d H:i:s') . PHP_EOL . '通知信息:' . $str . PHP_EOL . PHP_EOL . '交易类型:TRADE_SUCCESS' . PHP_EOL . PHP_EOL, FILE_APPEND);
				}
			}
		}
	}

    //根据当前经验计算等级信息
    public function getlevel($exp)
    {
        $data = M('vip_level')->order('exp')->select();
        if ($data) {
            $level = array();
            foreach ($data as $k => $v) {
                if ($k + 1 == count($data)) {
                    if ($exp >= $data[$k]['exp']) {
                        $level['levelid'] = $data[$k]['id'];
                        $level['levelname'] = $data[$k]['name'];
                    }
                } else {
                    if ($exp >= $data[$k]['exp'] && $exp < $data[$k + 1]['exp']) {
                        $level['levelid'] = $data[$k]['id'];
                        $level['levelname'] = $data[$k]['name'];
                    }
                }
            }
        } else {
            return utf8error('会员等级未定义！');
        }
        return $level;
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
            'url' => "http://" . $_SERVER['HTTP_HOST'] . "/App/Wds/orderdetail/id/" . $oid,
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

} //Wxpay类结束