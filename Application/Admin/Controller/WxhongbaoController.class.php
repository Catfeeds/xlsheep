<?php
// +----------------------------------------------------------------------
// | 用户后台基础类--CMS分组魔法关键词类
// +----------------------------------------------------------------------
namespace Admin\Controller;

class WxhongbaoController extends BaseController
{
    public function _initialize()
    {
        //你可以在此覆盖父类方法
        parent::_initialize();
    }   

    public function ceshi()
    {
        $wxtx = D("VipWxtx")->getList($map, true);
        dump($wxtx);   
    } 
	//裂变红包支付
	public function RedBagGroup(){
	    
        Vendor("WxHongBao.WxHongBao");
        $WxHongBao = new \WxHb (self::$SYS['set']['wxappid'], self::$SYS['set']['wxappsecret'], self::$SYS['set']['wxmchid'],self::$SYS['set']['wxmchkey']);
        $WxHongBao->inited();
        
		$obj = array();
		$obj['wxappid'] = self::$SYS['set']['wxappid'];//微信AppID
		$obj['mch_id'] = self::$SYS['set']['wxmchid']; //商户号
		$obj['mch_billno'] = '132432345632';//订单号
		$obj['re_openid'] = "oojFxs1CQD_2G98Os0bI5qNAIhcw";//测试接收的OpenID
		$obj['total_amount'] = 300;//金额最低300
		$obj['total_num'] = 3;//最低3人
		$obj['amt_type'] = 'ALL_RAND';
		$obj['send_name'] = 'wemall官方';//发送者名字
		$obj['wishing'] = '恭喜发财';//祝福语
		$obj['act_name'] = '六一儿童节快乐';//活动名称
		$obj['remark'] = '有钱，任性';//备注
		$url = "https://api.mch.weixin.qq.com/mmpaymkttransfers/sendgroupredpack";//裂变红包

        $responseXml = $WxHongBao->Pay($url,$obj);
		$responseObj = simplexml_load_string ( $responseXml, 'SimpleXMLElement', LIBXML_NOCDATA );
		//转换成数组
		$responseArr = ( array ) $responseObj;

		$return_code = $responseArr ['return_code'];
		$result_code = $responseArr ['result_code'];
		//判断是否红包是否发送成功
		if ($return_code == "SUCCESS" && $result_code == "SUCCESS") {
			dump ( $responseArr );
			echo "SUCCESS";
		} else {
			echo "发送失败";
			dump ( $responseArr );
		}	 
	}
    
    //CMS后台Vip微信红包提现订单
    public function wxtxorder()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '会员中心',
                'url' => U('Admin/Vip/#'),
            ),
            '1' => array(
                'name' => '微信提现订单',
                'url' => U('Admin/Vip/txorder'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        $this->BagSelect();

        $m = M('Vip_wxtx');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $name = I('name') ? I('name') : '';
        if ($name) {
            //提现人姓名
            $map['txname'] = array('like', "%$name%");
            $this->assign('name', $name);
        }
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $cache = $m->where($map)->page($p, $psize)->select();

        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '会员微信红包提现订单', 'App-search');

        $this->assign('cache', $cache);
        $this->display();
    }  
	/**
	*红包领取状态查询
	*@return array
	*/
	public function BagSelect(){

        $map['status'] = 1;//待领取
        $wxtx = M('Vip_wxtx')->where($map)->select();	    
        Vendor("WxHongBao.WxHongBao");
        $WxHongBao = new \WxHb (self::$SYS['set']['wxappid'], self::$SYS['set']['wxappsecret'], self::$SYS['set']['wxmchid'],self::$SYS['set']['wxmchkey']);
        $WxHongBao->inited();
        foreach ($wxtx as $key => $value) {

            $responseXml = $WxHongBao->BagSelect($value['billno']);
        	$responseObj = simplexml_load_string ( $responseXml, 'SimpleXMLElement', LIBXML_NOCDATA );
    		//转换成数组
    		$responseArr = ( array ) $responseObj;    
    		//判断是否红包是否发送成功
    		if ($responseArr["status"] == "RECEIVED") {
    		    $m = M('Vip_wxtx'); 
    		    $rold = $m->where('id=' . $value["id"])->setField('status', 2);
    		}            
        }
	}
//发放红包
    public function wxtxorderOk()
    {
        $id = I('id');
        if (!$id) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取ID数据！';
            $this->ajaxReturn($info);
        }
        $m = M('Vip_wxtx');
        $mvip = M('Vip');
        $mlog = M('Shop_order_log');
        $old = $m->where('id=' . $id)->find();
        if (!$old) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取提现订单数据！';
            $this->ajaxReturn($info);
        }
        if ($old['status'] != 0) {
            $info['status'] = 0;
            $info['msg'] = '只可以操作新申请订单！';
            $this->ajaxReturn($info);
        }
        $vip = $mvip->where('id=' . $old['vip_id'])->find();
        
        Vendor("WxHongBao.WxHongBao");
        $WxHongBao = new \WxHb (self::$SYS['set']['wxappid'], self::$SYS['set']['wxappsecret'], self::$SYS['set']['wxmchid'],self::$SYS['set']['wxmchkey']);
        $WxHongBao->inited();

		$obj = array();	    
		$obj['wxappid'] = self::$SYS['set']['wxappid'];//微信AppID
		$obj['mch_id'] = self::$SYS['set']['wxmchid']; //商户号
		$obj['mch_billno'] = $old["billno"];//订单号
		$obj['client_ip'] = $_SERVER['REMOTE_ADDR'];//触发ip
		$obj['re_openid'] = $vip["openid"];//测试接收的OpenID
		$obj['total_amount'] = $old["txprice"]*100;//金额
		$obj['total_num'] = 1;//红包数量
		$obj['send_name'] = 'wemall官方';//发送者名字
		$obj['wishing'] = '恭喜发财';//祝福语
		$obj['act_name'] = 'Vip红包提现';//活动名称
		$obj['remark'] = '有钱，任性';//备注
		$url = "https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack";//普通红包       

        $responseXml = $WxHongBao->Pay($url,$obj);
		$responseObj = simplexml_load_string ( $responseXml, 'SimpleXMLElement', LIBXML_NOCDATA );
		//转换成数组
		$responseArr = ( array ) $responseObj;

		$return_code = $responseArr ['return_code'];
		$result_code = $responseArr ['result_code'];
		//判断是否红包是否发送成功
		if ($return_code == "SUCCESS" && $result_code == "SUCCESS") {
		    $rold = $m->where('id=' . $id)->setField('status', 1);
		    if ($rold !== FALSE) {
		        $data_msg['pids'] = $vip['id'];
                $data_msg['title'] = "微信红包提现申请通过审核！" . $old['txprice'] . "已成功发送给您！";
                $data_msg['content'] = "提现订单编号：" . $old['id'] . "<br><br>提现申请" . self::$SHOP['set']['yjname'] . "：" . $old['txprice'] . "<br><br>提现发放时间：" . date('Y-m-d H:i', time()) . "<br><br>您的微信红包提现申请通过审核！";
                $data_msg['ctime'] = time();
                $rmsg = M('Vip_message')->add($data_msg);
                $info['status'] = 1;
                $info['msg'] = '红包发放成功！待领取';
                
                // 发送信息===============
                $options['appid'] = self::$SYS['set']['wxappid'];
                $options['appsecret'] = self::$SYS['set']['wxappsecret'];
                $wx = new \Util\Wx\Wechat($options);
                $msg = array();
                $msg['touser'] = $vip['openid'];
                $msg['msgtype'] = 'text';
                $str = "红包已发放，请注意查收！";
                $msg['text'] = array('content' => $str);
                $ree = $wx->sendCustomMessage($msg);
                // 发送消息完成============

                $this->ajaxReturn($info);                
		    } else {
                $info['status'] = 0;
                $info['msg'] = '操作失败，请重新尝试！';
                $this->ajaxReturn($info);
            }
		    
		} else {
			$info['status'] = 0;
            $info['msg'] = $responseArr["return_msg"];
            $this->ajaxReturn($info);
		}	
        
    }
    
    public function wxtxorderExport()
    {
        $id = I('id');
        if ($id) {
            $map['id'] = array('in', in_parse_str($id));
        }

        $data = M('Vip_wxtx')->where($map)->select();
        foreach ($data as $k => $v) {
            switch ($v['status']) {
                case 0:
                    $data[$k]['status'] = "未发放";
                    break;
                case 1:
                    $data[$k]['status'] = "待领取";
                    break;
                case 2:
                    $data[$k]['status'] = "已领取";
                    break;
            }
            $data[$k]['txtime'] = $v['txtime'] ? date('Y-m-d H:i:s', $v['txtime']) : '未执行';
        }
        $title = array('ID', '会员ID', '申请编号', '提现金额', '提现姓名', '提现电话', '提现微信号', '申请时间', '红包状态');
        $this->exportexcel($data, $title, $tt . '微信红包提现' . date('Y-m-d H:i:s', time()));
    }  
     /**
     * 导出数据为excel表格
     * @param $data    一个二维数组,结构如同从数据库查出来的数组
     * @param $title   excel的第一行标题,一个数组,如果为空则没有标题
     * @param $filename 下载的文件名
     * @examlpe
    $stu = M ('User');
     * $arr = $stu -> select();
     * exportexcel($arr,array('id','账户','密码','昵称'),'文件名!');
     */
    private function exportexcel($data = array(), $title = array(), $filename = 'report')
    {
        header("Content-type:application/octet-stream");
        header("Accept-Ranges:bytes");
        header("Content-type:application/vnd.ms-excel");
        header("Content-Disposition:attachment;filename=" . $filename . ".xls");
        header("Pragma: no-cache");
        header("Expires: 0");
        //导出xls 开始
        if (!empty($title)) {
            foreach ($title as $k => $v) {
                $title[$k] = iconv("UTF-8", "GB2312", $v);
            }
            $title = implode("\t", $title);
            echo "$title\n";
        }
        if (!empty($data)) {
            foreach ($data as $key => $val) {
                foreach ($val as $ck => $cv) {
                    $data[$key][$ck] = iconv("UTF-8", "GB2312", $cv);
                }
                $data[$key] = implode("\t", $data[$key]);

            }
            echo implode("\n", $data);
        }

    }   
    
    
    
    
    
    
    
    
    
}