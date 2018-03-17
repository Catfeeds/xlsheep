<?php
// 微信支付JSAPI版本
// 基于版本 V3
// By App 2015-1-20
namespace Home\Controller;

use Think\Controller;

class WxpayupgradeController extends Controller
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

    //升级VIP会员
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
        $cache = M('vip_log')->where(array('opid' => $oid, 'type' =>array('in',array(9,13))))->find();
        if (!$cache) {
            $this->error('此订单不存在！', 'App/vip/upgrade');
        }
        if ($cache['ispay']) {
            $this->error('此订单已支付！请勿重复支付！', 'App/vip/upgrade');
        }

        //微信支付封装
        $options['appid'] = self::$_wxappid;
        $options['appsecret'] = self::$_wxappsecret;
        $options['mchid'] = self::$SET['wxmchid'];
        $options['mchkey'] = self::$SET['wxmchkey'];
        $paysdk = new \Util\Wx\Wxpaysdk($options);

        $paysdk->setParameter("openid", $openid); //会员openid
        $paysdk->setParameter("body", "升级VIP会员"); //商品描述
        //自定义订单号，此处仅作举例
        $timeStamp = time();
        $paysdk->setParameter("out_trade_no", $cache['opid']); //商户订单号
        $paysdk->setParameter("total_fee", intval($cache['money'] * 100)); //总金额单位为分，不允许有小数
        $paysdk->setParameter("notify_url", 'http://' . $_SERVER['HTTP_HOST'] . __ROOT__ . '/Home/Wxpayupgrade/nd'); //交易通知地址
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
        $str = "";
        foreach ($wOpt as $key => $v) {
            $str .= "{$key}={$v}&";
        }
        $url = "http://" . $_SERVER['HTTP_HOST'] . __ROOT__ . "/wxpayupgrade.php?$str";

        header("Location:" . $url);
    }

    //支付成功后后台接受方案
    public function nd()
    {
        $str = "";
        foreach ($_POST as $k => $v) {
            $str = $str . $k . "=>" . $v . '  ';
        }
        file_put_contents(self::$_logs . './Data/app_wxupgradend.txt', '响应参数:' . date('Y-m-d H:i:s') . PHP_EOL . '通知信息:' . $str . PHP_EOL . PHP_EOL . PHP_EOL, FILE_APPEND);
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
               
                file_put_contents(self::$_logs . './Data/app_wxupgradeerr.txt', '通讯出错:' . date('Y-m-d H:i:s') . PHP_EOL . '通知信息:' . $str . PHP_EOL . '在线充值订单号:' . $out_trade_no . PHP_EOL . '交易结果:通讯出错' . PHP_EOL . PHP_EOL, FILE_APPEND);
            } elseif ($notify->data["result_code"] == "FAIL") {
              
                file_put_contents(self::$_logs . './Data/app_wxupgradeerr.txt', '业务出错:' . date('Y-m-d H:i:s') . PHP_EOL . '通知信息:' . $str . PHP_EOL . '在线充值订单号:' . $out_trade_no . PHP_EOL . '交易结果:业务出错' . PHP_EOL . PHP_EOL, FILE_APPEND);
            } else {
                $this->end($out_trade_no);
              
                file_put_contents(self::$_logs . './Data/app_wxupgradeok.txt', '支付成功:' . date('Y-m-d H:i:s') . PHP_EOL . '通知信息:' . $str . PHP_EOL . '在线充值订单号:' . $out_trade_no . PHP_EOL . '交易结果:交易成功' . PHP_EOL . PHP_EOL, FILE_APPEND);

            }
             
        }
         
        
    }

    //充值完成后操作
    protected function end($oid)
    {
        $m = M('vip_log');
        $order = $m->where(array('opid' => $oid, 'type' => array('in',array('9','13')) ))->find();
         file_put_contents(self::$_logs . './Data/app_wxupgradend.txt', '成功+3 88:' . $oid . '99:' . serialize($order), FILE_APPEND);
        if ($order) {

            if ($order['status'] == 1) {
                //修改状态
                $order['status'] = 2;
                $order['ctime'] = time();

                $re = $m->save($order);
                if (FALSE !== $re) {
                    /*$upgradeData['groupid'] = 2;
                    $upgradeData['vtime'] = time();
                    $upgradeData['vip_expiration_time'] = strtotime('+'.$order['code'].'months',time());
                    $res = $mvip->where('id='.$vipid)->save($upgradeData);*/
                     $mvip = M('Vip');
                    if($order['type'] == 13){
                        $upgradeData['vip_apply_status'] = 1;

                        $mvip->where('id='.$order['vipid'])->save($upgradeData);
                    }

                    $vip = $mvip->where('id=' . $order['vipid'])->find();                    
                    //资金流水记录
                    $mlog = M('Vip_log_money');
                    $flow['vipid'] = $vip['id'];
                    $flow['openid'] = $vip['openid'];
                    $flow['nickname'] = $vip['nickname'];
                    $flow['mobile'] = $vip['mobile'];
                    $flow['money'] = $order['money'];
                    $flow['paytype'] = 'wxpay';
                    $flow['balance'] = $vip['money'];
                    $flow['type'] = 13;
                    $flow['oid'] = $order['opid'];
                    $flow['ctime'] = time();
                    $flow['remark'] = '升级VIP会员';
                    $rflow = $mlog->add($flow);
                    M('vip_log')->where('id=' . $order['id'])->save(array("status"=>2,"pay_time"=>time()));
                    //记录报警信息
                    $str = "订单号：" . $oid . "支付成功！";
                    file_put_contents(self::$_logs . '/Data/app_wxupgradeerr.txt', '微信支付报警:' . date('Y-m-d H:i:s') . PHP_EOL . '通知信息:' . $str . PHP_EOL . PHP_EOL . '交易类型:TRADE_SUCCESS' . PHP_EOL . PHP_EOL, FILE_APPEND);

                } else {
                    //记录报警信息
                    $str = "订单号：" . $oid . "充值成功但未更新日志信息！";
                    file_put_contents(self::$_logs . '/Data/app_wxupgradeerr.txt', '微信支付报警:' . date('Y-m-d H:i:s') . PHP_EOL . '通知信息:' . $str . PHP_EOL . PHP_EOL . '交易类型:TRADE_SUCCESS' . PHP_EOL . PHP_EOL, FILE_APPEND);
                }
            }
		}
}

//根据当前经验计算等级信息
public
function getlevel($exp)
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

public
function createNoncestr($length = 32)
{
    $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
    $str = "";
    for ($i = 0; $i < $length; $i++) {
        $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $str;
}

//停止不动的信息通知页面处理
public
function diemsg($status, $msg)
{
    //成功为1，失败为0
    $status = $status ? $status : '0';
    $this->assign('status', $status);
    $this->assign('msg', $msg);
    $this->display('Base_diemsg');
    die();
}

} //Wxpay类结束