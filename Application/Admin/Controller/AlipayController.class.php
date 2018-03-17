<?php
/**
 * Created by PhpStorm.
 * User: heqing
 * Date: 15/9/10
 * Time: 14:32
 */

namespace Admin\Controller;


class AlipayController extends BaseController
{

	public function _initialize()
	{
		//你可以在此覆盖父类方法
		parent::_initialize();
	}
	

    public function index()
    {

    }

    public function set()
    {
        if (IS_POST) {
            if (M("Alipay")->find()) {
                M("Alipay")->where(array("id" => "1"))->save($_POST);
            } else {
                M("Alipay")->add($_POST);
            }
            $this->ajaxReturn(array("status"=>"1","msg"=>"设置成功"));
        } else {
            $alipay = M("Alipay")->find();
            $this->assign("alipay", $alipay);
            $this->display();
        }
    }
    
    //CMS后台Vip支付宝提现订单
    public function txorder()
    {
    	//设置面包导航，主加载器请配置
    	$bread = array(
    			'0' => array(
    					'name' => '会员中心',
    					'url' => U('Admin/Vip/#'),
    			),
    			'1' => array(
    					'name' => '支付宝提现订单',
    					'url' => U('Admin/Alipay/txorder'),
    			),
    	);
    	$this->assign('breadhtml', $this->getBread($bread));
    	$status = I('status');
    	if ($status || $status == '0') {
    		$map['status'] = $status;
    	}
    	$this->assign('status', $status);
    	$m = M('Vip_alitx');
    	$p = $_GET['p'] ? $_GET['p'] : 1;
    	$name = I('name') ? I('name') : '';
    	if ($name) {
    		//提现人姓名
    		$map['txname'] = array('like', "%$name%");
    		$this->assign('name', $name);
    	}
    	$psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
    	$cache = $m->where($map)->page($p, $psize)->order('id DESC')->select();
    
    	$count = $m->where($map)->count();
    	$this->getPage($count, $psize, 'App-loader', '会员支付宝提现订单', 'App-search');
    	$this->assign('cache', $cache);
    	$this->display();
    }
    public function txorderOk()
    {    
    	$options['appid'] = self::$SYS['set']['wxappid'];
    	$options['appsecret'] = self::$SYS['set']['wxappsecret'];
    	$wx = new \Util\Wx\Wechat($options);
    	
    	$data = I('post.');
    	if(!$data['id']) {
    		$this->error('参数错误！');
    	}
    	if(!$data['voucher']) {
    		$this->error('请上传转账凭证！');
    	}
    	$m = M('Vip_alitx');
    	$old = $m->where(array('id'=>$data['id']))->find();
    	if(!$old) {
    		$this->error('提现项目不存在！');
    	}
    	if($old['status'] == 0) {
    		$this->error('该项目已取消提现，不能提现！');
    	}
    	if($old['status'] == 2) {
    		$this->error('该项目已提现，不能提现！');
    	}
    	$mlog = M('Vip_message');
    	$mvip = M('Vip');
    	 
    	$err = TRUE;
    	$old['voucher'] = $data['voucher'];
    	$old['status'] = 2;
    	$old['txtime'] = time();
    	$rv = $m->save($old);
    	if ($rv !== FALSE) {
    		$data_msg['pids'] = $old['vip_id'];
    		$data_msg['title'] = "亲爱的用户，提现已完成！" . $old['txprice'] . self::$SHOP['set']['yjname'] . "已成功发放到您的支付宝帐户里面了！";
    		$data_msg['content'] = "提现订单编号：" . $old['id'] . "<br><br>提现申请" . self::$SHOP['set']['yjname'] . "：" . $old['txprice'] . "<br><br>提现完成时间：" . date('Y-m-d H:i', $old['txtime']) . "<br><br>您的提现申请已完成，如有异常请联系客服！";
    		$data_msg['ctime'] = time();
    
    		// 发送信息===============
    		$customer = M('Wx_customer')->where(array('type' => 'tx2'))->find();
    		$vip = $mvip->where(array('id' => $old['vip_id']))->find();
    		$msg = array();
    		$msg['touser'] = $vip['openid'];
    		$msg['msgtype'] = 'text';
    		$str = $customer['value'];
    		$msg['text'] = array('content' => $str);
    		$ree = $wx->sendCustomMessage($msg);
    		// 发送消息完成============ 
    		$rmsg = $mlog->add($data_msg);
    	} else {
    		$err = FALSE;
    	}		
    	if ($err) {
    		$info['status'] = 1;
    		$info['msg'] = '提现完成成功!';
    	} else {
    		$info['status'] = 0;
    		$info['msg'] = '提现完成失败，请刷新后重新尝试!';
    	}
    	$this->ajaxReturn($info);
    }
    
    public function txorderCancel()
    {
    	$id = I('id');
    	if (!$id) {
    		$info['status'] = 0;
    		$info['msg'] = '未正常获取ID数据！';
    		$this->ajaxReturn($info);
    	}
    	$m = M('Vip_alitx');
    	$mvip = M('Vip');
    	$old = $m->where('id=' . $id)->find();
    	if (!$old) {
    		$info['status'] = 0;
    		$info['msg'] = '未正常获取提现订单数据！';
    		$this->ajaxReturn($info);
    	}
    	if ($old['status'] != 1) {
    		$info['status'] = 0;
    		$info['msg'] = '只可以操作新申请订单！';
    		$this->ajaxReturn($info);
    	}
    	if(!$old['vip_id']) {
    		$info['status'] = 0;
    		$info['msg'] = '未正常获取相关会员信息！';
    		$this->ajaxReturn($info);
    	}
    	$vip = $mvip->where('id=' . $old['vip_id'])->find();
    	if (!$vip) {
    		$info['status'] = 0;
    		$info['msg'] = '未正常获取相关会员信息！';
    		$this->ajaxReturn($info);
    	}
    	$rold = $m->where('id=' . $id)->setField('status', 0);
    	if ($rold !== FALSE) {
    		$rvip = $mvip->where('id=' . $old['vip_id'])->setInc('money', $old['txprice']);
    		if ($rvip) {
    			//资金流水记录
    			$mlog = M('Vip_log_money');
    			$flow['vipid'] = $vip['id'];
    			$flow['openid'] = $vip['openid'];
    			$flow['nickname'] = $vip['nickname'];
    			$flow['mobile'] = $vip['mobile'];
    			$flow['money'] = $old['txprice'];
    			$flow['paytype'] = '';
    			$flow['balance'] = $vip['money'] + $old['txprice'];
    			$flow['type'] = 2;
    			$flow['oid'] = '提现编号：'.$old['id'];
    			$flow['ctime'] = time();
    			$flow['remark'] = '用户申请支付宝提现未通过审核，提现金额退回到用户账号余额';
    			$rflow = $mlog->add($flow);
    			
    			$data_msg['pids'] = $vip['id'];
    			$data_msg['title'] = "提现申请未通过审核！" . $old['txprice'] . self::$SHOP['set']['yjname'] . "已成功退回您的帐户余额！";
    			$data_msg['content'] = "提现订单编号：" . $old['id'] . "<br><br>提现申请" . self::$SHOP['set']['yjname'] . "：" . $old['txprice'] . "<br><br>提现退回时间：" . date('Y-m-d H:i', time()) . "<br><br>您的提现申请未通过审核，如有疑问请联系客服！";
    			$data_msg['ctime'] = time();
    			$rmsg = M('Vip_message')->add($data_msg);
    			$info['status'] = 1;
    			$info['msg'] = '取消提现申请成功！提现' . self::$SHOP['set']['yjname'] . '已自动退回用户帐户余额！';
    
    			// 发送信息===============
    			$customer = M('Wx_customer')->where(array('type' => 'tx3'))->find();
    			$options['appid'] = self::$SYS['set']['wxappid'];
    			$options['appsecret'] = self::$SYS['set']['wxappsecret'];
    			$wx = new \Util\Wx\Wechat($options);
    			$msg = array();
    			$msg['touser'] = $vip['openid'];
    			$msg['msgtype'] = 'text';
    			$str = $customer['value'];
    			$msg['text'] = array('content' => $str);
    			$ree = $wx->sendCustomMessage($msg);
    			// 发送消息完成============
    
    			$this->ajaxReturn($info);
    		} else {
    			$info['status'] = 0;
    			$info['msg'] = '取消成功，但自动退还' . self::$SHOP['set']['yjname'] . '至用户余额失败，请联系此会员！';
    			$this->ajaxReturn($info);
    		}
    	} else {
    		$info['status'] = 0;
    		$info['msg'] = '操作失败，请重新尝试！';
    		$this->ajaxReturn($info);
    	}
    }
    
    public function txorderExport()
    {
    	$id = I('id');
    	$status = I('status');
    	if ($id) {
    		$map['id'] = array('in', in_parse_str($id));
    	} else {
    		$map['status'] = $status;
    	}
    	switch ($status) {
    		case 0:
    			$tt = "支付宝提现失败";
    			break;
    		case 1:
    			$tt = "支付宝新提现申请";
    			break;
    		case 2:
    			$tt = "支付宝提现完成";
    			break;
    	}
    	$data = M('Vip_alitx')->where($map)->select();
    	foreach ($data as $k => $v) {
    		switch ($v['status']) {
    			case 0:
    				$data[$k]['status'] = "提现失败";
    				break;
    			case 1:
    				$data[$k]['status'] = "新申请";
    				break;
    			case 2:
    				$data[$k]['status'] = "提现完成";
    				break;
    		}
    		$data[$k]['txsqtime'] = date('Y-m-d H:i:s', $v['txsqtime']);
    		$data[$k]['txtime'] = $v['txtime'] ? date('Y-m-d H:i:s', $v['txtime']) : '未执行';
    	}
    	$title = array('ID', '会员ID', '提现金额', '提现姓名', '提现电话', '提现银行', '提现分行', '提现银行所在地', '提现银行卡卡号', '提现申请时间', '提现完成时间', '订单状态');
    	$this->exportexcel($data, $title, $tt . '订单' . date('Y-m-d H:i:s', time()));
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
    
    //上传提现凭证
    public function txVoucher()
    {
    	$map['id'] = I('id');
    	$cache = M('Vip_alitx')->where($map)->find();
    	$this->assign('cache', $cache);
    	$mb = $this->fetch();
    	$this->ajaxReturn($mb);
    }
}