<?php
namespace App\Controller;

use Think\Controller;

class BaseoaController extends Controller
{
	public static $SET;//全局静态配置
	public static $WAP;//CMS全局静态变量
	//微信缓存
	protected static $_wxappid;
	protected static $_wxappsecret;

	//初始化验证模块
	protected function _initialize()
	{
		//缓存全局SET
		self::$SET = $_SESSION['SET'] = $this->checkSet();
		self::$_wxappid = self::$SET['wxappid'];
		self::$_wxappsecret = self::$SET['wxappsecret'];
		//刷新全局会员配置
		self::$WAP['vipset'] = $_SESSION['WAP']['vipset'] = $this->checkVipSet();
		//微信授权
		
		if (strpos($_SERVER["HTTP_USER_AGENT"], "MicroMessenger")) {

			if (I('code')) {
				//第二次鉴权
				//dump(I('get'));
				if (I('code') != 'authdeny') {
					//用户授权
					$options['appid'] = self::$_wxappid;
					$options['appsecret'] = self::$_wxappsecret;
					$wx = new \Util\Wx\Wechat($options);
					$re = $wx->getOauthAccessToken(I('code'));//获取access_token和openid
					//dump($re);
					$access_token = $re['access_token'];
					$openid = $re['openid'];
					if ($re) {
						$_SESSION['sqmode'] = 'wecha';
						$_SESSION['sqopenid'] = $openid;
					}
					$user = $wx->getOauthUserinfo($access_token, $openid);
					if ($user) {
						// Employee 获取
						$employee = $_SESSION['oaemployee'] ? $_SESSION['oaemployee'] : 0;

						//容错，防止ppid不存在
						$ppid = $_SESSION['oappid'] ? $_SESSION['oappid'] : 0;
						$mvip = M('Vip');
						//容错，防止重复注册VIP
						$vip = $mvip->where(array('openid' => $openid))->find();
						if ($vip) {
							$this->redirect('App/Shop/index');
						}
						//处理邀请奖励
						$old = $mvip->where(array('id' => $ppid))->find();
						$shopset = M('Shop_set')->find();
						if ($old) {

							/*$tj_score = self::$WAP['vipset']['tj_score'];
							$tj_exp = self::$WAP['vipset']['tj_exp'];
							$tj_money = self::$WAP['vipset']['tj_money'];
							if ($tj_score || $tj_exp || $tj_money) {
								$msg = "推荐新用户奖励：<br>新用户：" . $user['nickname'] . "<br>奖励内容：<br>";
								$mglog = "获得新用户注册奖励:";
								if ($tj_score) {
									$old['score'] = array('exp', 'score+'.$tj_score);
									$msg = $msg . $tj_score . "个积分<br>";
									$mglog = $mglog . $tj_score . "个积分；";
								}
								if ($tj_exp) {
									$old['exp'] = array('exp', 'exp+'.$tj_exp);
									$msg = $msg . $tj_exp . "点经验<br>";
									$mglog = $mglog . $tj_exp . "点经验；";
								}
								if ($tj_money) {
									//检查是否超过佣金封顶
									$logmap['vipid'] = $old['id'];
									$logmap['status'] = array('neq',2);
									$tj_xf_money = M('Vip_log_xftj')->where($logmap)->sum('money');//带来消费佣金
									$tj_reg_money = M('Vip_log_tj')->where('vipid='.$old['id'])->sum('money');//带来注册佣金
									$tj_total_money = $tj_xf_money + $tj_reg_money;
									if($tj_total_money > self::$WAP['vipset']['max_tg_commission']) {
										$tj_money = 0;
									} elseif($tj_total_money + $tj_money > self::$WAP['vipset']['max_tg_commission']){
										$tj_money = self::$WAP['vipset']['max_tg_commission'] - $tj_total_money;
									}
									if($tj_money > 0) {
										$old['money'] = array('exp','money+'.$tj_money);
										$msg = $msg . $tj_money . "余额<br>";
										$mglog = $mglog . $tj_money . "余额；";
									}
								}
								$msg = $msg . "此奖励已自动打入您的帐户！感谢您的支持！";
								$rold = $mvip->save($old);
								if (FALSE !== $rold) {
									$data_msg['pids'] = $old['id'];
									$data_msg['title'] = "你获得一份推荐奖励！";
									$data_msg['content'] = $msg;
									$data_msg['ctime'] = time();
									$rmsg = M('Vip_message')->add($data_msg);
									$data_mglog['vipid'] = $old['id'];
									$data_mglog['nickname'] = $old['nickname'];
									$data_mglog['xxnickname'] = $user['nickname'];
									$data_mglog['money'] = $tj_money;
									$data_mglog['score'] = $tj_score;
									$data_mglog['msg'] = $mglog;
									$data_mglog['ctime'] = time();
									$rmglog = M('Vip_log_tj')->add($data_mglog);
									if($tj_money>0) {
										//资金流水记录
										$mlog = M('Vip_log_money');
										$flow['vipid'] = $old['id'];
										$flow['openid'] = $old['openid'];
										$flow['nickname'] = $old['nickname'];
										$flow['mobile'] = $old['mobile'];
										$flow['money'] = $tj_money;
										$flow['paytype'] = '';
										$flow['balance'] = M('Vip')->where('id='.$ppid)->getField('money');
										$flow['type'] = 11;
										$flow['oid'] = '';
										$flow['ctime'] = time();
										$flow['remark'] = '带来注册奖励（'.$user['nickname'].'）';
										$rflow = $mlog->add($flow);
									}
									if ($tj_score>0) {
										log_credit($old['id'], $tj_score, 2);
									}
								}
							}*/

							//赠送操作
							if (self::$WAP['vipset']['isinvite']) {
								$invite = explode(",", self::$WAP['vipset']['invite_detail']);
								$cardnopwd = $this->getCardNoPwd();
								$data_card['type'] = $invite[0];
								$data_card['vipid'] = $old['id'];
								$data_card['money'] = $invite[1];
								$data_card['usemoney'] = $invite[3];
								$data_card['cardno'] = $cardnopwd['no'];
								$data_card['cardpwd'] = $cardnopwd['pwd'];
								$data_card['status'] = 1;
								$data_card['stime'] = $data_card['ctime'] = time();
								$data_card['etime'] = time() + $invite[2] * 24 * 60 * 60;
								$rcard = M('vip_card')->add($data_card);
								if (FALSE !== $rcard) {
									$msg = "推荐新用户奖励：<br>新用户：" . $user['nickname'] . "<br>奖励内容：<br>";
									$mglog = "获得推荐新用户奖励:";
									$mglog = $mglog .$invite[1].'元代金券';
									$msg = $msg . $invite[1].'元代金券,';
									$msg = $msg . "此奖励已自动打入您的帐户！感谢您的支持！";
									$data_msg['pids'] = $old['id'];
									$data_msg['title'] = "你获得一份推荐奖励！";
									$data_msg['content'] = $msg;
									$data_msg['ctime'] = time();
									$rmsg = M('Vip_message')->add($data_msg);
									$data_mglog['vipid'] = $old['id'];
									$data_mglog['nickname'] = $old['nickname'];
									$data_mglog['xxnickname'] = $user['nickname'];
									$data_mglog['msg'] = $mglog;
									$data_mglog['ctime'] = time();
									$rmglog = M('vip_log_tj')->add($data_mglog);
								}
							}
						}
						$data['pid'] = $old ? $old['id'] : 0;
						$data['path'] = $old ? ($old['path'] . '-' . $old['id']) : 0;
						$data['plv'] = $old ? ($old['plv'] + 1) : 1;
						$data['openid'] = $user['openid'];
						$data['nickname'] = $user['nickname'];
						$data['sex'] = $user['sex'];
						$data['city'] = $user['city'];
						$data['province'] = $user['province'];
						$data['country'] = $user['country'];
						$data['headimgurl'] = $user['headimgurl'];
						$data['score'] = self::$WAP['vipset']['reg_score'];
						$data['exp'] = self::$WAP['vipset']['reg_exp'];
						$data['cur_exp'] = self::$WAP['vipset']['reg_exp'];
						$level = $this->getLevel($data['exp']);
						$data['levelid'] = $level['levelid'];
						$data['ctime'] = time();
						$data['cctime'] = time();
						$data['isfx'] = 1;
						$rvip = $mvip->add($data);
						if ($rvip) {
							if($data['score']>0) {
								log_credit($rvip, $data['score'], 1);
							}
							//赠送操作
							if (self::$WAP['vipset']['isgift']) {
								$gift = explode(",", self::$WAP['vipset']['gift_detail']);
								$cardnopwd = $this->getCardNoPwd();
								$data_card['type'] = $gift[0];
								$data_card['vipid'] = $rvip;
								$data_card['money'] = $gift[1];
								$data_card['usemoney'] = $gift[3];
								$data_card['cardno'] = $cardnopwd['no'];
								$data_card['cardpwd'] = $cardnopwd['pwd'];
								$data_card['status'] = 1;
								$data_card['stime'] = $data_card['ctime'] = time();
								$data_card['etime'] = time() + $gift[2] * 24 * 60 * 60;
								$rcard = M('vip_card')->add($data_card);
							}
							//发送注册通知消息
							//记录日志
							$data_log['ip'] = get_client_ip();
							$data_log['vipid'] = $rvip;
							$data_log['ctime'] = time();
							$data_log['openid'] = $data['openid'];
							$data_log['nickname'] = $data['nickname'];
							$data_log['event'] = "会员注册";
							$data_log['score'] = $data['score'];
							$data_log['exp'] = $data['exp'];
							$data_log['type'] = 4;
							$rlog = M('Vip_log')->add($data_log);
							//正常处理完成，返回原链接
							$rurl = $_SESSION['oaurl'];
							header("Location:" . $rurl);
						} else {
							//跳转回去重新执行一边
							$rurl = $_SESSION['oaurl'];
							session(null);
							header("Location:" . $rurl);
						}
					} else {
						//跳转回去重新执行一边
						$rurl = $_SESSION['oaurl'];
						session(null);
						header("Location:" . $rurl);
					}

				} else {
					//用户未授权
					$this->diemsg(0, '本应用需要您的授权才可以使用!');
				}
			} else {
				$_url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
				$options['appid'] = self::$_wxappid;
				$options['appsecret'] = self::$_wxappsecret;
				$wx = new \Util\Wx\Wechat($options);
				$squrl = $wx->getOauthRedirect($_url, '1', 'snsapi_userinfo');
				header("Location:" . $squrl);
			}


		} else {
			//其他浏览器不做授权跳出
			$this->diemsg(0, '请使用微信浏览器访问本应用！');
		}

	}

	public function index()
	{
		//目前什么都不做
	}

	//返回全局配置
	public function checkSet()
	{
		$set = M('Set')->find();
		return $set ? $set : utf8error('系统全局设置未定义！');
	}

	//返回VIP配置
	public function checkVipSet()
	{
		$set = M('vip_set')->find();
		return $set ? $set : utf8error('会员设置未定义！');
	}

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
			return false;
		}
		return $level;
	}

	public function getCardNoPwd()
	{
		$dict_no = "0123456789";
		$length_no = 10;
		$dict_pwd = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$length_pwd = 10;
		$card['no'] = "";
		$card['pwd'] = "";
		for ($i = 0; $i < $length_no; $i++) {
			$card['no'] .= $dict_no[rand(0, (strlen($dict_no) - 1))];
		}
		for ($i = 0; $i < $length_pwd; $i++) {
			$card['pwd'] .= $dict_pwd[rand(0, (strlen($dict_pwd) - 1))];
		}
		return $card;
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
}