<?php
namespace App\Controller;
use Think\Model;

class VipController extends BaseController
{
	private $pagesize = 10;
	
    public function _initialize()
    {
        //在此覆盖父类方法
        parent::_initialize();
    }

    public function index()
    {


    	$vipid = $_SESSION['WAP']['vipid'];
        $backurl = base64_encode(U('App/Vip/index'));
        $this->checkLogin($backurl);
        // M('Vip')->where();
        $data = self::$WAP['vip'];
        //本月收益
        $fhlModel = M('Finance_fhlog');
        $bysyMap['to'] = $vipid;
        $bysyMap['type'] = 1;
        $bysyMap['status'] = 1;
        $bysyMap['etime'] = array('egt',strtotime(date('Y-m-01')));
        $bysy = $fhlModel->where($bysyMap)->sum('money');
        $data['bysy'] = $bysy > 0 ? $bysy : 0;
        //待返羊款
        $dfykMap['to'] = $vipid;
        $dfykMap['type'] = 2;
        $dfykMap['status'] = 0;
        $dfyk = $fhlModel->where($dfykMap)->sum('money');
        $data['dfyk'] = $dfyk > 0 ? $dfyk : 0;
        //羊圈羊数
        $morder = M('Finance_order');
        $map['vipid'] = $vipid;
        $map['status'] = 2;
        $totalnum = $morder->where($map)->sum('totalnum');
        $data['totalnum'] = $totalnum > 0 ? $totalnum : 0;
        //计算未读消息
        $Model = new Model ();
        $sql = 'SELECT count(id) as count  FROM (SELECT id FROM `' . C ( 'DB_PREFIX' ) . 'vip_message`
        		WHERE `pids` = '.$vipid.'
        		UNION ALL
        		SELECT id FROM ' . C ( 'DB_PREFIX' ) . 'vip_message
        		WHERE `pids` = "" AND ctime>='.$data['ctime'].') as a';
        $msg = $Model->query ( $sql );
        $msg_count = $msg[0]['count'];
        if ($msg_count) {
        	$msgread = M('vip_log')->where('vipid=' . $vipid . ' and type=5')->count();
        	$data['unread'] = $msg_count - $msgread;
        	$data['unread'] = $data['unread'] > 99 ? '99+' : $data['unread'];
        } else {
        	$data['unread'] = 0;
        }
        $vip = M('vip') -> where('id = '.$vipid) -> field('score') -> find();
        $this ->assign('vip',$vip);
        $this->assign('data', $data);
        $this->assign('actname', 'ftvip');
        $this->display();
    }
    public function bound()
    {
    	$vipid = $_SESSION['WAP']['vipid'];
    	$userinfo = M('Vip')->where(array('id' => $vipid))->find();
    	//登录后绑定手机
    	if(IS_POST){ 		
    		$m = M('vip');
    		$post = I('post.');
    		$vipid = $post['vid'];
    		if(!$vipid){
    			$info['status'] = 0;
    			$info['msg'] = "参数错误！";
    			$this->ajaxReturn($info);
    		}
    		$mobile = $post['mobile'];
    		if( !preg_match("/^(?:13\d|14\d|15\d|18[0123456789])-?\d{5}(\d{3}|\*{3})$/", $mobile)){
    			$this->error('手机号码不正确！','/App/Vip/bound');
    		}
    		$us = M('Vip')->where(array('mobile' => $mobile))->find();
    		if($us){
    			$info['status'] = 0;
    			$info['msg'] = "此手机号已被绑定！";
    			$this->ajaxReturn($info);
    		}
    		$password = $post['password']; 
    		$conpassword = $post['conpassword'];
    		$data['mobile'] = $mobile;
    		$data['password'] = md5($password);
    		$user = M('Vip')->where(array('id' => $vipid))->save($data);
    		if($user == true){
    			$info['status'] = 1;
    			$info['msg'] = "绑定成功";
    			$this->ajaxReturn($info);
    		}else {
    			$info['status'] = 0;
    			$info['msg'] = "绑定失败，请重新绑定！";
    			$this->ajaxReturn($info);
    		}
    	}
    	$this->assign('actname', 'ftvip');
    	$this->assign('userinfo', $userinfo);
    	$this->display();
    }
    public function sign()
    {
        $backurl = base64_encode(U('App/Vip/index'));
        $this->checkLogin($backurl);
        $vipid = self::$WAP['vipid'];

        $sign_score = explode(',', self::$WAP['vipset']['sign_score']);
        $sign_exp = explode(',', self::$WAP['vipset']['sign_exp']);
        $vip = self::$WAP['vip'];
        $d1 = date_create(date('Y-m-d', $vip['signtime']));
        $d2 = date_create(date('Y-m-d', time()));
        $diff = date_diff($d1, $d2);
        $late = $diff->format("%a");
        //判断是否签到过
        if ($late < 1) {
            $info['status'] = 0;
            $info['msg'] = "您今日已经签过到了！";
            $this->ajaxReturn($info);
        }
        //正常签到累计流程
        if ($late >= 1 && $late < 2) {
            $vip['sign'] = $vip['sign'] ? $vip['sign'] : 0; //防止空值

            $data_vip['sign'] = $vip['sign'] + 1; //签到次数+1
            //积分
            if ($data_vip['sign'] >= count($sign_score)) {
                $score = $sign_score[count($sign_score) - 1];
            } else {
                $score = $sign_score[$data_vip['sign']];
            }
            //经验
            if ($data_vip['sign'] >= count($sign_exp)) {
                $exp = $sign_exp[count($sign_exp) - 1];
            } else {
                $exp = $sign_exp[$data_vip['sign']];
            }
        } else {
            $data_vip['sign'] = 0; //签到次数置零
            $score = $sign_score[0];
            $exp = $sign_exp[0];
        }
        $data_vip['score'] = array('exp', 'score+' . $score);
        $data_vip['exp'] = array('exp', 'exp+' . $exp);
        $data_vip['signtime'] = time();
        $data_vip['cur_exp'] = array('exp', 'cur_exp+' . $exp);
        $level = $this->getlevel(self::$WAP['vip']['cur_exp'] + $exp);
        $data_vip['levelid'] = $level['levelid'];
        $m = M('Vip');
        $r = $m->where(array('id' => $vipid))->save($data_vip);

        if ($r) {
            //增加签到日志
            $data_log['ip'] = get_client_ip();
            $data_log['vipid'] = $vipid;
            $data_log['event'] = '会员签到-连续' . $data_vip['sign'] . '天';
            $data_log['score'] = $score;
            $data_log['exp'] = $exp;
            $data_log['type'] = 2;
            $data_log['ctime'] = time();
            M('vip_log')->add($data_log);
            $info['status'] = 1;
            $info['msg'] = "签到成功！";
            $data_log['levelname'] = $level['levelname'];
            $info['data'] = $data_log;
        } else {
            $info['status'] = 0;
            $info['msg'] = "签到失败！" . $r;
        }
        $this->ajaxReturn($info);
    }

    public function reg()
    {
        if (IS_POST) {
            $m = M('vip');
            $post = I('post.');
            //判断重复注册
            if ($m->where('mobile=' . $post['mobile'])->find()) {
                $info['status'] = 0;
                $info['msg'] = '此手机号已注册过！';
                $this->ajaxReturn($info, "json");
            }
            //判断验证码
            if (self::$WAP['vipset']['isverify'] == 1) {
                $last_ver = M('vip_log')->where('mobile=' . $post['mobile'] . ' and type=1')->order('ctime desc')->find();
                if ($last_ver['code'] != $post['code']) {
                    $info['status'] = 0;
                    $info['msg'] = '验证码错误！';
                    $this->ajaxReturn($info, "json");
                }
            }
            $post['password'] = md5($post['password']);
            $post['score'] = self::$WAP['vipset']['reg_score'];
            $post['exp'] = self::$WAP['vipset']['reg_exp'];
            $post['cur_exp'] = self::$WAP['vipset']['reg_exp'];
            $level = $this->getLevel($post['exp']);
            $post['levelid'] = $level['levelid'];
            $post['ctime'] = time();
            unset($post['code']);
            $r = $m->add($post);
            if ($r) {
                //赠送操作
                if (self::$WAP['vipset']['isgift']) {
                    $gift = explode(",", self::$WAP['vipset']['gift_detail']);
                    $cardnopwd = $this->getCardNoPwd();
                    $data_card['type'] = $gift[0];
                    $data_card['vipid'] = $r;
                    $data_card['money'] = $gift[1];
                    $data_card['usemoney'] = $gift[3];
                    $data_card['cardno'] = $cardnopwd['no'];
                    $data_card['cardpwd'] = $cardnopwd['pwd'];
                    $data_card['status'] = 1;
                    $data_card['stime'] = $data_card['ctime'] = time();
                    $data_card['etime'] = time() + $gift[2] * 24 * 60 * 60;
                    M('vip_card')->add($data_card);

                    //发送赠送通知消息
                    //					$data_msg['pids']=$r;
                    //					$data_msg['title']="新人礼包";
                    //					$data_msg['content']="新用户注册赠送新人礼包，内含代金券，请至个人中心查收！";
                    //					$data_msg['ctime']=time();
                    //					M('vip_message')->add($data_msg);
                }
                //记录日志
                $data_log['ip'] = get_client_ip();
                $data_log['vipid'] = $r['id'];
                $data_log['ctime'] = time();
                $data_log['event'] = "会员注册";
                $data_log['score'] = $post['score'];
                $data_log['exp'] = $post['exp'];
                $data_log['type'] = 4;
                M('vip_log')->add($data_log);

                $info['status'] = 1;
                $info['msg'] = '注册成功！马上去登陆';
                $info['mobile'] = $post['mobile'];
            } else {
                $info['status'] = 0;
                $info['msg'] = '注册失败！';
            }
            $this->ajaxReturn($info, "json");
        } else {
            if (self::$WAP['vipset']['isverify'] == 1) {
                if ($_SESSION['mobile_tmp']) {
                    $mobile = $_SESSION['mobile_tmp'];
                    $last_ver = M('vip_log')->where('mobile=' . $mobile)->order('ctime desc')->find();
                    $times = $last_ver['ctime'] + self::$WAP['vipset']['ver_interval'] * 60 - time();
                }
            }
            $status = $times > 0 ? 0 : 1;
            $times = $times > 0 ? $times : 0;
            $this->assign('status', $status);
            $this->assign('times', $times);
            $this->assign('isverify', self::$WAP['vipset']['isverify']);
            $this->display();
        }
    }
    public function addheadimg()
    {
    	//用户头像
    	header("Content-type:text/html;charset=utf-8");
    	$vipid = $_SESSION['WAP']['vipid'];
    	$data = M('Vip')->where(array('id'=>$vipid))->find();
    	if(IS_POST){
    		$post = I('post.');
    		$time = date('Y-m-d');
    		$file = "Upload/headimgurl/$time/$vipid";
    		if (file_exists($file) == false) {
    			$paths="Upload/headimgurl/$time/$vipid";
    			$re = mkdir($paths,0777,true);
    		}
    		$extArr = array("jpg", "png", "gif","jpeg");
    		if(isset($_POST) and $_SERVER['REQUEST_METHOD'] == "POST"){
    			$name = $_FILES['pic']['name'];
    			$size = $_FILES['pic']['size'];
    			if(empty($name)){
    				echo '请选择要上传的图片';
    				exit;
    			}
    			$ext = extend($name);
    			if(!in_array($ext,$extArr)){
    				echo '图片格式错误！';
    				exit;
    			}
    			if($size>(1024*1024)){
    				echo '图片大小不能超过1MB';
    				exit;
    			}
    			$pic = $data['headimgurl'];
    			$hosts =  'http://'.$_SERVER['HTTP_HOST'].'/';
    			$len = strlen($hosts);//判断字符域名长度
    			$str = substr($pic,$len);
    			$result = unlink($str);
    			$image_name = time().rand(100,999).".".$ext;
    			$tmp = $_FILES['pic']['tmp_name'];
    			$path = "Upload/headimgurl/$time/$vipid/";
    			if(move_uploaded_file($tmp, $path.$image_name)){
    				$image_thumb = '<img src="'.$path.$image_name.'" class="preview">';
    				$host =  'http://'.$_SERVER['HTTP_HOST'];
    				$datas['headimgurl'] = "$host/$path$image_name";
    				$user = M('Vip')->where(array('id'=>$vipid))->save($datas);
    				if($user==true){
    					$this->success('保存成功','/Mobile/Vip/index');
    				}
    			}else{
    				echo '上传出错了！';
    			}
    			exit;
    		}
    	}
    	$this->assign('data', $data);
    	$this->display();
    }
    private function getCardNoPwd()
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

    public function sendCode()
    {
        $m = M('vip_log');
        $post = I('get.');

        //已验证次数
        $counts = $m->where('mobile=' . $post['mobile'])->count();
        if ($counts >= self::$WAP['vipset']['ver_times']) {
            $info['status'] = 0;
            $info['msg'] = "超出验证次数！";
            $this->ajaxReturn($info);
        }
        $data_log['ip'] = get_client_ip();
        $post['code'] = rand(1000, 9999);
        $post['ctime'] = time();
        $post['event'] = "注册获取验证码";
        $post['type'] = 1;
        $r = $m->add($post);

        if ($r) {
            $info['status'] = 1;
            $info['msg'] = "验证码发送成功！";
            $info['times'] = self::$WAP['vipset']['ver_interval'] * 60;
            $_SESSION['mobile_tmp'] = $post['mobile'];
        } else {
            $info['status'] = 0;
            $info['msg'] = "发送失败！";
        }
        $this->ajaxReturn($info);
    }

    public function login()
    {
        if (IS_POST) {
            $m = M('vip');
            $post = I('post.');
            $r = $m->where("mobile='" . $post['mobile'] . "' and password='" . md5($post['password']) . "'")->find();
            if ($r) {
                //记录日志
                $data_log['ip'] = get_client_ip();
                $data_log['vipid'] = $r['id'];
                $data_log['ctime'] = time();
                $data_log['event'] = "会员登陆";
                $data_log['type'] = 3;
                M('vip_log')->add($data_log);
                //记录最后登陆
                $data_vip['cctime'] = time();
                $m->where('id=' . $r['id'])->save($data_vip);

                $info['status'] = 1;
                $info['msg'] = "登陆成功！";

                $_SESSION['WAP']['vipid'] = $r['id'];
                $_SESSION['WAP']['vip'] = $r;
            } else {
                $info['status'] = 0;
                $info['msg'] = "账号密码错误！";
            }
            $this->ajaxReturn($info);
        } else {
            $this->assign('mobile', I('mobile'));
            $this->assign('backurl', base64_decode(I('backurl')));
            $this->display();
        }
    }

    public function logout()
    {
        session(null);
        $this->redirect('App/Vip/login');
    }

    public function message()
    {
    	$backurl = base64_encode(U('App/Vip/message'));
    	$this->checkLogin($backurl);
    	$vipid = self::$WAP['vipid'];
    	$vip = self::$WAP['vip'];
    	$Model = new Model ();
    	$sql = 'SELECT *  FROM (SELECT * FROM `' . C ( 'DB_PREFIX' ) . 'vip_message`
        		WHERE `pids` = '.$vipid.'
        		UNION ALL
        		SELECT * FROM ' . C ( 'DB_PREFIX' ) . 'vip_message
        		WHERE `pids` = "" AND ctime>='.$vip['ctime'].') as a
        		ORDER BY a.id DESC LIMIT 0, '.$this->pagesize;
    	$data = $Model->query ( $sql );
    	foreach ($data as $k => $val) {
    		$read = M('vip_log')->where('vipid=' . $vipid . ' and opid=' . $val['id'] . ' and type=5')->find();
    		$data[$k]['read'] = $read ? 1 : 0;
    	}
    	$this->assign('data', $data);
    	$this->assign('datamore', count($data) >= $this->pagesize ? 1 : 0 );
    	$this->assign('actname', 'ftvip');
    	$this->display();
    }

    public function msgRead()
    {
    	$backurl = base64_encode(U('App/Vip/message'));
    	$this->checkLogin($backurl);
    	$vipid = self::$WAP['vipid'];
    	
    	$m = M('vip_message');
    	$id = I('id');
    	
    	$msgread = M('vip_log')->where('opid=' . $id . ' and type=5 and vipid=' . $vipid)->find();
    	if ($msgread) {
    		$info['status'] = 0;
    	} else {
    		$data_log['ip'] = get_client_ip();
    		$data_log['event'] = "会员浏览消息";
    		$data_log['type'] = 5;
    		$data_log['vipid'] = $vipid;
    		$data_log['opid'] = $id;
    		$data_log['ctime'] = time();
    		
    		M('vip_log')->add($data_log);
    		$info['status'] = 1;
    	}
    	$data = $m->where('id=' . $id)->find();
    	if(!$data) {
    		$this->error('站内信不存在！');
    	}
    	if($data['pids'] != '' && $data['pids'] != $vipid) {
    		$this->error('您没有权限查看该站内信！');
    	}
    	$info['data'] = $data;
    	$this->assign('data', $data);
    	$this->assign('actname', 'ftvip');
    	$this->display();
    }

    public function info()
    {
        $backurl = base64_encode(U('App/Vip/info'));
        $this->checkLogin($backurl);
        $vipid = self::$WAP['vipid'];

        if (IS_POST) {
            $m = M('vip');
            $post = I('post.');
            $r = $m->where("id=" . $vipid)->save($post);
            if ($r) {
                $info['status'] = 1;
                $info['msg'] = "资料修改成功！";
            } else {
                $info['status'] = 0;
                $info['msg'] = "资料修改失败！";
            }
            $this->ajaxReturn($info);
        } else {
            $data = self::$WAP['vip'];
            $this->assign('data', $data);
            $this->display();
        }
    }

    public function tx()
    {
        $backurl = base64_encode(U('App/Vip/tx'));
        $this->checkLogin($backurl);
        $vipid = self::$WAP['vipid'];
        if (IS_POST) {
            $m = M('vip');
            $vip = $m->where("id=" . $vipid)->find();
            if(!$vip['name'] || !$vip['mobile']) {
            	$this->error('请先绑定个人资料！', U('App/Vip/bind'));
            }
            $post = I('post.');
            if(!$post['txtype'] || !in_array($post['txtype'], array(1,2))) {
            	$this->error('参数错误！');
            }
            if($post['txtype'] == 1) {
            	$data['alipay'] = $post['alipay'];            	
            } else {
            	if($vip['txname'] && $vip['txname'] != $post['txname']) {
            		$this->error('姓名必须与第一次绑定的提现姓名一致！');
            	}
            	unset($post['alipay']);
            	unset($post['txtype']);
            	$data = $post;
            } 
            $r = $m->where("id=" . $vipid)->save($data);
            //dump($m->getLastSql());
            //die('ok');
            if ($r !== FALSE) {
                $this->success('提现资料修改成功！');
            } else {
                $this->error('提现资料修改失败！');
            }
        }
    }
	public function bind()
	{
		$type = intval(I('type')) ? intval(I('type')) : 1;
		if($type < 1 || $type > 4) {
			$this->error('参数错误');
		}
		if (IS_POST) 
		{
			$post = I('post.');
			$mbReg = "/^(13[0-9]|14[57]|15[012356789]|17[0678]|18[0-9])\d{8}$/";
			$emailReg = "/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i";
			$m = M('vip');
			$vipid = self::$WAP['vipid'];
			$vip = $m->where("id=" . $vipid)->find();
			if($type != 1 && (!$vip['name'] || !$vip['mobile'])) {
				$this->error('请先绑定个人资料！', U('App/Vip/bind'));
			}
			switch ($type) {
				case 1:
					$data['name'] = $post['name'];
					$data['mobile'] = $post['mobile'];
					$data['email'] = $post['email'];
					if(!$data['name']) {
						$this->error('姓名不能为空！');
					}
					if(!$data['mobile']) {
						$this->error('手机号码不能为空！');
					}
					if(!preg_match($mbReg, $data['mobile'])) {
						$this->error('手机号码格式不正确！');
					}
					// if(!preg_match($emailReg,$data['email'])){
					// 	$this->error('邮箱格式不正确！');
					// }					
					break;
				case 2:
					$data['txwxhao'] = $post['wxhao'];
					$data['txnickname'] = $post['txnickname'];
                    if(!$data['txwxhao']) {
						$this->error('微信号不能为空！');
					}
					break;
				case 3:
					$data['alipay'] = $post['alipay'];
					if(!$data['alipay']) {
						$this->error('支付宝账号不能为空！');
					}
					if(!preg_match($emailReg,$data['alipay']) && !preg_match($mbReg,$data['alipay'])) {
						$this->error('支付宝账号格式不正确！');
					}
					break;
				case 4:
					$data['txname'] = $post['txname'];
					$data['txmobile'] = $post['txmobile'];
					$data['txyh'] = $post['txyh'];
					$data['txfh'] = $post['txfh'];
					$data['txszd'] = $post['txszd'];
					$data['txcard'] = $post['txcard'];
					if(!$data['txname']) {
						$this->error('请填写提现姓名！');
					}
					if(!$data['txmobile']) {
						$this->error('请填写提现手机！');
					}
					if(!$data['txyh']) {
						$this->error('请填写开户银行！');
					}
					if(!$data['txfh']) {
						$this->error('请填写开户银行分行！没有请填写总行!');
					}
					if(!$data['txszd']) {
						$this->error('请填写提现所在地！');
					}
					if(!$data['txcard']) {
						$this->error('请填写提现银行卡号！');
					} 
					if(self::$WAP['vip']['txname'] && (self::$WAP['vip']['txname'] != $data['txname'])) {
						$this->error('提现姓名必须与第一次绑定的提现姓名一致！');
					}
					break;
			}
			$r = $m->where("id=" . $vipid)->setField($data);
			if ($r !== false) {
				$info['status'] = 1;
				$info['info'] = "资料修改成功！";
			} else {
				$info['status'] = 0;
				$info['info'] = "资料修改失败！";
			}
			$this->ajaxReturn($info);
		} 
		else 
		{
			$data = self::$WAP['vip'];
			$this->assign('actname', 'ftvip');
			$this->assign('data', $data);
			$this->assign('type', $type);
			$this->display();
		}
	}
     public function txOrder()
    {
        $backurl = base64_encode(U('App/Vip/txOrder'));
        $this->checkLogin($backurl);
        $vipid = self::$WAP['vipid'];
        $m = M('vip');
        $vip = $m->where('id=' . $vipid)->find();
        $this->assign('vip', $vip);
        if (IS_POST) {
        	if(!$vip['name'] || !$vip['mobile']) {
        		$this->error('请先绑定个人资料！', U('App/Vip/bind'));
        	}
            $mtx = M('vip_tx');
            $post = I('post.');


            if (!$post['txprice']) {
                $this->error('提现' . $_SESSION['SHOP']['set']['yjname'] . '不能为空！');
            }
            if ($post['txprice'] < self::$WAP['vipset']['tx_money']) {
                $this->error('提现' . $_SESSION['SHOP']['set']['yjname'] . '不得少于' . self::$WAP['vipset']['tx_money'] . '元！');
            }
            if ($post['txprice'] > $vip['money']) {
                $this->error('您的' . $_SESSION['SHOP']['set']['yjname'] . '不足！');
            }

            if($post['type'] == 'BankCard'){
                if(!$vip['txname'] || !$vip['txmobile'] || !$vip['txyh'] ||
                			!$vip['txfh'] || !$vip['txszd'] || !$vip['txcard'] ) {
                	$this->error('请先绑定银行卡信息');
                }    
            }




            if($post['type'] == 'Weui')
                !$vip['txwxhao'] && $this->error('请先绑定微信号');
                
                

            $vip['money'] = $vip['money'] - $post['txprice'];
            $rvip = $m->save($vip);
            
            if (FALSE !== $rvip) {                	
				$cardtx['vipid'] = $vipid;
                $cardtx['txprice'] = $post['txprice'];
				if($post['type'] == 'BankCard'){
    				$cardtx['txname'] = $vip['txname'];
    				$cardtx['txmobile'] = $vip['txmobile'];
    				$cardtx['txyh'] = $vip['txyh'];
    				$cardtx['txfh'] = $vip['txfh'];
    				$cardtx['txszd'] = $vip['txszd'];
    				$cardtx['txcard'] = $vip['txcard'];
                }
                
                if($post['type'] == 'Weui'){
                    $cardtx['txwxhao'] = $vip['txwxhao'];
                    $cardtx['txnickname'] = $vip['txnickname'];
                }

                $cardtx['txtype'] = $post['txtype'];
				$cardtx['vipid'] = $vipid;
				$cardtx['status'] = 1;
				$cardtx['txsqtime'] = time();
                $r = $mtx->add($cardtx); 

                if ($r) {
                	//资金流水记录
                	$mlog = M('Vip_log_money');
                	$flow['vipid'] = $vip['id'];
                	$flow['openid'] = $vip['openid'];
                	$flow['nickname'] = $vip['nickname'];
                	$flow['mobile'] = $vip['mobile'];
                	$flow['money'] = -$post['txprice'];
                	$flow['paytype'] = '';
                	$flow['balance'] = $vip['money'];
                	$flow['type'] = 2;
                	$flow['oid'] = '提现编号：'.$r;
                	$flow['ctime'] = time();
                	
                	$flow['remark'] = '用户申请提现';
                	$rflow = $mlog->add($flow);
                	
                    $data_msg['pids'] = $vipid;
                    $data_msg['title'] = "您的" . $post['txprice'] . $_SESSION['SHOP']['set']['yjname'] . "提现申请已成功提交！会在三个工作日内审核完毕并发放！";
                    $data_msg['content'] = "提现订单编号：" . $r . "<br><br>提现申请数量：" . $post['txprice'] . "<br><br>提现申请时间：" . date('Y-m-d H:i', time()) . "<br><br>提现申请将在三个工作日内审核完成，如有问题，请联系客服！";
                    $data_msg['ctime'] = time();
                    $rmsg = M('vip_message')->add($data_msg);

                    // 发送信息===============
                    $customer = M('Wx_customer')->where(array('type' => 'tx1'))->find();
                    $options['appid'] = self::$_wxappid;
                    $options['appsecret'] = self::$_wxappsecret;
                    $wx = new \Util\Wx\Wechat($options);
                    $msg = array();
                    $msg['touser'] = $vip['openid'];
                    $msg['msgtype'] = 'text';
                    $str = $customer['value'];
                    $msg['text'] = array('content' => $str);
                    $ree = $wx->sendCustomMessage($msg);
                    // 发送消息完成============

                    $this->success('提现申请成功！');
                } else {
                    $data_msg['pids'] = $vipid;
                    $data_msg['title'] = "您的" . $post['txprice'] . $_SESSION['SHOP']['set']['yjname'] . "提现申请已成功提交！会在三个工作日内审核完毕并发放！";
                    $data_msg['content'] = "提现订单编号：" . $r . "<br><br>提现申请数量：" . $post['txprice'] . "<br><br>提现申请时间：" . date('Y-m-d H:i', time()) . "<br><br>" . $_SESSION['SHOP']['set']['yjname'] . "余额已扣除，但未成功生成提现订单，凭此信息联系客服补偿损失！";
                    $data_msg['ctime'] = time();
                    $rmsg = M('vip_message')->add($data_msg);
                    $this->error($_SESSION['SHOP']['set']['yjname'] . '余额扣除成功，但未成功生成提现申请，请联系客服！');
                }
            } else {
                $this->error('提现申请失败！请重新尝试！');
            }

        } else {
            $data = self::$WAP['vip'];
            if(!$data['name'] || !$data['mobile']) {
            	$this->error('请先绑定个人资料！', U('App/Vip/bind'));
            }
            $this->assign('actname', 'ftvip');
            $this->assign('data', $data);
            $this->display();
        }
    }

    public function address()
    {
        $backurl = base64_encode(U('App/Vip/address'));
        $this->checkLogin($backurl);
        $vipid = self::$WAP['vipid'];
        $m = M('VipAddress');
        $data = $m->where('vipid=' . $vipid)->select();
        $this->assign('actname', 'ftvip');
        $this->assign('data', $data);
        $this->display();
    }

    public function addressSet()
    {
        $backurl = base64_encode(U('App/Vip/address'));
        $this->checkLogin($backurl);
        $vipid = self::$WAP['vipid'];
        $m = M('VipAddress');
        if (IS_POST) {
            $post = I('post.');
            $post['vipid'] = $vipid;
            $r = $post['id'] ? $m->save($post) : $m->add($post);
            if ($r) {
                $info['status'] = 1;
                $info['msg'] = "地址保存成功！";
            } else {
                $info['status'] = 0;
                $info['msg'] = "地址保存失败！";
            }
            $this->ajaxReturn($info);
        } else {
            $data['mobile'] = self::$WAP['vip']['mobile'];
            $data['name'] = self::$WAP['vip']['name'];
            if (I('id')) {
                $data = $m->where('id=' . I('id'))->find();
            }
            $this->assign('actname', 'ftvip');
            $this->assign('data', $data);
            $this->display();
        }
    }

    public function addressDel()
    {
        $backurl = base64_encode(U('App/Vip/address'));
        $this->checkLogin($backurl);
        $vipid = self::$WAP['vipid'];
        $m = M('VipAddress');
        if (IS_POST) {
            $r = $m->where('id=' . I('id') . ' and vipid=' . $vipid)->delete();
            if ($r) {
                $info['status'] = 1;
                $info['msg'] = "地址删除成功！";
            } else {
                $info['status'] = 0;
                $info['msg'] = "地址删除失败！";
            }
            $this->ajaxReturn($info);
        }
    }
    //合同列表
    public function contract()
    {
    	$vipid = $_SESSION['WAP']['vipid'];
    	$m = M('Finance_contract');
    	$map['vipid'] = $vipid;
    	//已完结保单数
    	$count = $m->where($map)->count();
    	$cache = $m->where($map)->page(1,$this->pagesize)->order('ctime desc')->select();
    	$i=1;
    	foreach($cache as $k => $v) {
    		$cache[$k]['index'] = $i;
    		$i++;
    	}
    	$this->assign('actname', 'ftvip');
    	$this->assign('count', $count);
    	$this->assign('cache', $cache);
    	$this->assign('datamore', $count > $this->pagesize ? 1 :0);;
    	$this->display();
    }
    //合同详情
    public function contract_info()
    {
    	$no = I('no') <> '' ? I('no') : $this->diemsg(0, '缺少no参数');
    	$vipid = $_SESSION['WAP']['vipid'];
    	$m = M('Finance_contract');
    	$map['vipid'] = $vipid;
    	$map['no'] = $no;
    	$cache = $m->where($map)->find();
        $contract_info = M('contract_templet')->where('id = '.$cache['contract_id']) -> find();
    	if(!$cache) {
    		$this->error('合同不存在！');
    	}
        $goods = M('finance_goods') -> where('id = '.$cache['qid']) -> field('party,number')->find();
        $tcid = M('finance_order') -> where('id = '.explode('-',$cache['oid'])[1]) -> getField('tcid');
        $fh = M('finance_huibao') -> where('id = '.$tcid) -> getField('address');
        if(is_numeric($goods['party'])){
            $goods['party'] = M('party')->where('id = '.$goods['party'])->getField('name');
        }
        if($fh){
            $this->assign('fh','实物');
        }else{
            $this->assign('fh','余额');
        }
    	$vip = M('Vip')->where('id='.$vipid)->find();
    	$this->assign('actname', 'ftvip');
        $this->assign('contract_info', $contract_info);
        $this->assign('goods', $goods);
    	$this->assign('cache', $cache);
    	$this->assign('vip', $vip);
    	$this->display();
    }
    //保险列表
    public function insurance() 
    {
    	$vipid = $_SESSION['WAP']['vipid'];
    	$m = M('Finance_insurance');
    	$map['vipid'] = $vipid;
    	//已完结保单数
    	$count = $m->where($map)->count();
    	$cache = $m->where($map)->page(1,$this->pagesize)->select();
    	$i=1;
    	foreach($cache as $k => $v) {
    		$cache[$k]['index'] = $i;
    		$i++;
    	}
    	$this->assign('actname', 'ftvip');
    	$this->assign('count', $count);
    	$this->assign('cache', $cache);
    	$this->assign('datamore', $count > $this->pagesize ? 1 :0);;
    	$this->display();
    }
    //我的银行卡
    public function bank_card()
    {
    	$vip = self::$WAP['vip'];
    	if(!$vip['is_auth']) {
    		$this->error('请先进行实名认证！', U('App/Vip/auth'));
    	}
    	if (IS_POST)
    	{
    		$post = I('post.');
    		$mbReg = "/^(13[0-9]|14[57]|15[012356789]|17[0678]|18[0-9])\d{8}$/";
    		$emailReg = "/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i";
    		$m = M('vip');
    		$vipid = self::$WAP['vipid'];
    		if(!$vip['is_auth']) {
    			$this->error('添加银行卡前应先实名认证！', U('App/Vip/auth'));
    		}
    		$data['txyh'] = $post['txyh'];
    		$data['txfh'] = $post['txfh'];
    		$data['txszd'] = $post['txszd'];
    		$data['txcard'] = $post['txcard'];
    		if(!$data['txyh']) {
    			$this->error('请填写开户银行！');
    		}
    		if(!$data['txfh']) {
    			$this->error('请填写开户银行分行！没有请填写总行!');
    		}
    		if(!$data['txszd']) {
    			$this->error('请填写提现所在地！');
    		}
    		if(!$data['txcard']) {
    			$this->error('请填写提现银行卡号！');
    		}
    		$re = $m->where("id=" . $vipid)->setField($data);
    		if(FALSE !== $re) {
    			$this->success('提交成功！');
    		} else {
    			$this->error('提交失败！');
    		}
    	} else {
    		$this->assign('actname', 'ftvip');
    		$this->assign('data', $vip);
    		$this->display();
    	}
    }
    //实名认证
    public function auth()
    {
    	if (IS_POST)
    	{
    		$vipid = self::$WAP['vipid'];
    		$m = M('vip');
    		$post = I('post.');
    		$data['name'] = $post['name'];
    		$data['idno'] = $post['idno'];

            $vipinfo = self::$WAP['vip'];
            if(!$vipinfo['mobile']){
                $data['mobile'] =$post['mobile'];
                $data['code'] = $post['code'];
                if($data['code'] != session('Vcode')) {
                    $this->error('验证码错误！');
                }
                session('Vcode', null);
                $reg = "/^(13[0-9]|14[57]|15[012356789]|17[03678]|18[0-9])\d{8}$/";
                if(!$data['mobile']) {
                    $this->error('联系电话不能为空！');
                }
                if(!preg_match($reg, $data['mobile'])) {
                    $this->error('手机号码格式不正确！');
                }
                $vip = $m->where(array('mobile'=>$data['mobile'],'id <> '.$vipid))->find();
                if($vip) {
                    $this->error('该手机号码已被绑定！');
                }
            }
    		if(!$data['name']) {
    			$this->error('真实姓名不能为空！');
    		}
    		if(!$data['idno']) {
    			$this->error('身份证号不能为空！');
    		}
    		if(!checkIdCard($data['idno'])) {
    			$this->error('身份证号格式不正确！');
    		}
    		$data['is_auth'] = $_SESSION['WAP']['vipset']['isneedaudit'] == 1 ? 1 : 2;
    		$re = $m->where("id=" . $vipid)->setField($data);
    		if (FALSE !== $re) {
    			if($_SESSION['WAP']['vipset']['isneedaudit']) {
    				$this->success("提交成功，请耐心等待审核！");
    			} else {
    				$this->success("实名认证成功！");
    			}
    		} else {
    			$this->error("提交失败！");
    		}
    	} else {
    		$data = self::$WAP['vip'];
    		if($data['idno']) {
    			$data['idno'] = substr_replace($data['idno'],'************',2,13);
    		}
            // var_dump($data);exit;
    		$this->assign('actname', 'ftvip');
    		$this->assign('data', $data);
    		$this->display();
    	}
    }
    //手机认证
    public function mobile()
    {
    	$vipid = self::$WAP['vipid'];
    	if(!$vipid) {
    		$this->error('未登录');
    	}
    	$data = self::$WAP['vip'];
    	if($data['mobile'] != '') {
    		$this->error('您已验证手机号码！');
    	}
    	if (IS_POST)
    	{
    		$m = M('vip');
    		$mobile = I('mobile');
    		$code = I('code');
    		if($code != session('Vcode')) {
    			$this->error('验证码错误！');
    		}
    		session('Vcode', null);
    		$reg = "/^(13[0-9]|14[57]|15[012356789]|17[0678]|18[0-9])\d{8}$/";
    		if(!$mobile) {
    			$this->error('联系电话不能为空！');
    		}
    		if(!preg_match($reg, $mobile)) {
    			$this->error('手机号码格式不正确！');
    		}
    		$vip = $m->where(array('mobile'=>$mobile))->find();
    		if($vip) {
    			$this->error('该手机号码已被绑定！');
    		}
    		$re = $m->where('id='.$vipid)->setField('mobile', $mobile);
    		if(FALSE !== $re) {
    			$this->success('手机号码验证成功！');
    		} else {
    			$this->error('手机号码验证失败！');
    		}
    	} else {
    		$this->assign('actname', 'ftvip');
    		$this->display();
    	}
    }
    public function xqChoose()
    {
        $m = M('xq');
        if (IS_POST) {
            $post = I('post.');
            $post['vipid'] = $vipid;
            $post['xqgroupid'] = M('xq')->where('id=' . $post['xqid'])->getField('groupid');
            $r = $post['id'] ? $m->save($post) : $m->add($post);
            if ($r) {
                $info['status'] = 1;
                $info['msg'] = "地址保存成功！";
            } else {
                $info['status'] = 0;
                $info['msg'] = "地址保存失败！";
            }
            $this->ajaxReturn($info);
        } else {
            $data = $m->ORDER("convert(name USING gbk)")->select();
            foreach ($data as $k => $v) {
                $data[$k]['char'] = $this->getfirstchar($v['name']);
                if ($data[$k]['char'] == $data[$k - 1]['char']) {
                    $data[$k]['charshow'] = 0;
                } else {
                    $data[$k]['charshow'] = 1;
                }
            }
            if (I('addressid')) {
                $this->assign('addressid', I('addressid'));
            }
            $this->assign('data', $data);
            $this->display();
        }
    }

    //获取中文首字拼音字母
    public function getfirstchar($s0)
    {
        //手动添加未识别记录
        if (mb_substr($s0, 0, 1, 'utf-8') == "怡") {
            return "Y";
        }

        if (mb_substr($s0, 0, 1, 'utf-8') == "泗") {
            return "S";
        }

        $fchar = ord(substr($s0, 0, 1));
        if (($fchar >= ord("a") and $fchar <= ord("z")) or ($fchar >= ord("A") and $fchar <= ord("Z"))) {
            return strtoupper(chr($fchar));
        }

        $s = iconv("UTF-8", "GBK", $s0);
        $asc = ord($s{0}) * 256 + ord($s{1}) - 65536;
        //dump($s0.':'.$asc);
        if ($asc >= -20319 and $asc <= -20284) {
            return "A";
        }

        if ($asc >= -20283 and $asc <= -19776) {
            return "B";
        }

        if ($asc >= -19775 and $asc <= -19219) {
            return "C";
        }

        if ($asc >= -19218 and $asc <= -18711) {
            return "D";
        }

        if ($asc >= -18710 and $asc <= -18527) {
            return "E";
        }

        if ($asc >= -18526 and $asc <= -18240) {
            return "F";
        }

        if ($asc >= -18239 and $asc <= -17923) {
            return "G";
        }

        if ($asc >= -17922 and $asc <= -17418) {
            return "H";
        }

        if ($asc >= -17417 and $asc <= -16475) {
            return "J";
        }

        if ($asc >= -16474 and $asc <= -16213) {
            return "K";
        }

        if ($asc >= -16212 and $asc <= -15641) {
            return "L";
        }

        if ($asc >= -15640 and $asc <= -15166) {
            return "M";
        }

        if ($asc >= -15165 and $asc <= -14923) {
            return "N";
        }

        if ($asc >= -14922 and $asc <= -14915) {
            return "O";
        }

        if ($asc >= -14914 and $asc <= -14631) {
            return "P";
        }

        if ($asc >= -14630 and $asc <= -14150) {
            return "Q";
        }

        if ($asc >= -14149 and $asc <= -14091) {
            return "R";
        }

        if ($asc >= -14090 and $asc <= -13319) {
            return "S";
        }

        if ($asc >= -13318 and $asc <= -12839) {
            return "T";
        }

        if ($asc >= -12838 and $asc <= -12557) {
            return "W";
        }

        if ($asc >= -12556 and $asc <= -11848) {
            return "X";
        }

        if ($asc >= -11847 and $asc <= -11056) {
            return "Y";
        }

        if ($asc >= -11055 and $asc <= -10247) {
            return "Z";
        }

        return "?";
    }

    public function about()
    {
        $temp = M('Shop_set')->find();
        $this->assign('shop', $temp);
        $this->display();
    }

    public function intro()
    {
        $this->display();
    }

    public function cz()
    {   
    	$this->assign('actname', 'ftvip');
        $this->display();
    }

    public function zxczSet()
    {
        $backurl = base64_encode(U('App/Vip/cz'));
        $this->checkLogin($backurl);
        $vipid = self::$WAP['vipid'];
        $money = I('money');
        $type = I('type');
        $num = I('num');
        $goodsid = I('goodsid');
        //记录充值log，同时作为充值返回数据调用
        $data_log['ip'] = get_client_ip();
        $data_log['vipid'] = $vipid;
        $data_log['openid'] = $_SESSION['WAP']['vip']['openid'];
        $data_log['unionid'] = $_SESSION['WAP']['vip']['unionid'];
        $data_log['nickname'] = $_SESSION['WAP']['vip']['nickname'];
        $data_log['ctime'] = time();
        $data_log['event'] = "会员在线充值";
        $data_log['money'] = $money;
        $data_log['score'] = round($money * self::$WAP['vipset']['cz_score'] / 100);
        $data_log['exp'] = round($money * self::$WAP['vipset']['cz_exp'] / 100);
        $data_log['opid'] = date('Ymd') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
        $data_log['status'] = 1;
        $data_log['type'] = 7;
        $re = M('vip_log')->add($data_log);
        //跳转充值页面
        if ($re) {
            switch ($type) {
                case '1':
                    $this->redirect('App/Alipay/alipay', array('price' => $money, 'oid' => $data_log['opid']));
                    break;
                case '2':
                	$_SESSION['wxpaysid'] = 0;
                	$_SESSION['wxpayopenid'] = $_SESSION['WAP']['vip']['openid'];//追入会员openid
                    $this->redirect('Home/Wxpaycz/pay', array('price' => $money, 'oid' => $data_log['opid']));
                    break;
                default:
                    $this->error('支付方式未知！');
                    break;
            }
        } else {
            $this->error('出错啦！');
        }

    }

    public function card()
    {
        $backurl = base64_encode(U('App/Vip/card'));
        $this->checkLogin($backurl);
        $vipid = self::$WAP['vipid'];
        $m = M('vip_card');
        $status = I('status') ? intval(I('status')) : 1;
        $map['status'] = $status;
        $today = strtotime(date('Y-m-d'));
        if ($status == 3) {
            $map['etime'] = array('LT', $today);
            $map['status'] = 1;
        } else if ($status == 1) {
            $map['etime'] = array('EGT', $today);
        }
        $map['vipid'] = $vipid;
        $map['type'] = 2; //代金券

        $data = $m->where($map)->select();

        $this->assign('data', $data);
        $this->assign('status', $status);
        $this->display();
    }

    public function addCard()
    {
        $backurl = base64_encode(U('App/Vip/card'));
        $this->checkLogin($backurl);
        $vipid = self::$WAP['vipid'];
        $m = M('VipCard');
        $map = I('post.');
//        $map['type'] = 2; //充值卡充值
        $card = $m->where($map)->find();
        if ($card) {
            if ($card['status'] == 0) {
                //未发卡
                $info['status'] = 0;
                $info['msg'] = '此卡尚未激活，请重试或联系管理员！';
            } else if ($card['status'] == 2) {
                //已使用
                $info['status'] = 0;
                $info['msg'] = '此卡已使用过了哦！';
            } else if ($card['status'] == 1) {
                //修改会员信息：账户金额、积分、经验、等级
                $data_vip['money'] = array('exp', 'money+' . $card['money']);
                $data_vip['score'] = array('exp', 'score+' . round($card['money'] * self::$WAP['vipset']['cz_score'] / 100));
                if (round($card['money'] * self::$WAP['vipset']['cz_exp'] / 100) > 0) {
                    $data_vip['exp'] = array('exp', 'exp+' . round($card['money'] * self::$WAP['vipset']['cz_exp'] / 100));
                    $data_vip['cur_exp'] = array('exp', 'cur_exp+' . round($card['money'] * self::$WAP['vipset']['cz_exp'] / 100));
                    $level = $this->getLevel(self::$WAP['vip']['cur_exp'] + round($card['money'] * self::$WAP['vipset']['cz_exp'] / 100));
                    $data_vip['levelid'] = $level['levelid'];
                }
                $re = M('vip')->where('id=' . $vipid)->save($data_vip);
                if ($re) {
                    //修改卡状态
                    $card['status'] = 2;
                    $card['vipid'] = $vipid;
                    $card['usetime'] = time();
                    $m->save($card);
                    //记录日志
                    $data_log['ip'] = get_client_ip();
                    $data_log['vipid'] = $vipid;
                    $data_log['ctime'] = time();
                    $data_log['event'] = "会员充值卡充值";
                    $data_log['money'] = $card['money'];
                    $data_log['score'] = round($card['money'] * self::$WAP['vipset']['cz_score'] / 100);
                    $data_log['exp'] = round($card['money'] * self::$WAP['vipset']['cz_exp'] / 100);
                    $data_log['opid'] = $card['id'];
                    $data_log['type'] = 6;
                    M('vip_log')->add($data_log);

                    $info['status'] = 1;
                    $info['msg'] = '充值成功！前往会员中心查看？';
                } else {
                    $info['status'] = 0;
                    $info['msg'] = '充值失败，请重试或联系管理员！';
                }
            } else {
                $info['status'] = 0;
                $info['msg'] = '此卡状态异常，请重试或联系管理员！';
            }
        } else {
            $info['status'] = 0;
            $info['msg'] = '卡号密码有误，请核对后重试！';
        }
        $this->ajaxReturn($info);
    }

    public function addVipCard()
    {
        $backurl = base64_encode(U('App/Vip/card'));
        $this->checkLogin($backurl);
        $vipid = self::$WAP['vipid'];
        $m = M('VipCard');
        $map = I('post.');
        $map['type'] = 2; //代金券
        $card = $m->where($map)->find();
        if ($card) {
            if ($card['status'] == 0) {
                $m->where(array("id"=>$card["id"]))->save(array("vipid"=>$vipid ,"status"=>1));
                $this->ajaxReturn(array("info"=>"充值成功"));
            }else{
                $this->ajaxReturn(array("info"=>"充值失败"));
            }
        }
        $this->ajaxReturn(array("info"=>"充值失败"));

    }
    //生在订单号
	public function GenBillNo(){
		$rnd_num = array('0','1','2','3','4','5','6','7','8','9');
		$rndstr = "";
		while(strlen($rndstr)<10){
			$rndstr .= $rnd_num[array_rand($rnd_num)];    
		}

		return $this->mchid.date("Ymd").$rndstr;
	}
	//推广佣金
	public function procommission()
	{
		$type = I('type',0,'intval');
		$data = self::$WAP['vip'];
		$this->assign('data', $data);
		$this->assign('actname', 'ftvip');
		$this->display();	
	}
	//我的活动
	public function activity()
	{
		$this->display();
	}
	//我的余额
	public function balance()
	{
		$vipid = $_SESSION['WAP']['vipid'];
		$vip = M('Vip')->where(array('id' => $vipid))->find();
		$m = M('vip_log');
		$map['vipid'] = $vipid;
		$map['type'] = 7;
		$map['status'] = 1;
		$log = $m->where($map)->select();
		
		$this->assign('vip', $vip);
		$this->assign('log', $log);
		$this->display();
	}
	public function edit()
	{
		$vipid = $_SESSION['WAP']['vipid'];
		$data = M('Vip')->where(array('id' => $vipid))->find();
		switch ($data['is_auth']) {
			case '0':
				$data['auth_status'] = '未认证';
				break;
			case '1':
				$data['auth_status'] = '审核中';
				break;
			case '2':
				$data['auth_status'] = '已认证';
				break;
		}


		if($data['mobile']) {
			$data['mobile'] = substr_replace($data['mobile'],'*****',2,5);
		}
		if($data['idno']) {
			$data['idno'] = substr($data['idno'], -6);
		}
		$this->assign('actname', 'ftvip');
		$this->assign('data', $data);
		$this->display();
	}
	//上传头像
	public function avatar()
	{
		$vipid = self::$WAP['vipid'];
		if(!$vipid) {
			$this->error('未登录');
		}
		if(IS_POST) {
			$avatar = I('post.avatar', '', 'strip_tags');
			if(preg_match('/^(data:\s*image\/(\w+);base64,)/', $avatar, $result)) {
				$type = $result[2];
				$type = str_replace('jpeg', 'jpg', strtolower($type));
				$uploadPath = './Upload/avatar/'.$vipid. '/';
				if(!file_exists($uploadPath)) {
					mkdirs($uploadPath);
				}
				$new_file = $uploadPath.time().$type ;
				$image = new \Think\Image();
				if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $avatar)))) {
					$thumb = $uploadPath .'200x200_'.time().'.'.$type;
					$image->open($new_file);
					$image->thumb(200, 200)->save($thumb);
					@unlink ($new_file);
					if(!file_exists($thumb)) {
						$this->error("上传头像失败！");
					} else {
						$thumbUrl = '/Upload/avatar/'.$vipid. '/' .'200x200_'.time().'.'.$type;
						$re = M('Vip')->where('id='.$vipid)->setField('headimgurl',$thumbUrl);
						if(FALSE !== $re) {
							$this->success("上传头像成功！");
						} else {
							@unlink ($thumb);
							$this->error("上传头像失败！");
						}
					}
				} else {
					$this->error("上传头像失败！");
				}
			} else {
				$this->error("未接收到图片文件！");
			}
		} else {
			$data = self::$WAP['vip'];
			$this->assign('actname', 'ftvip');
			$this->assign('data', $data);
			$this->display();
		}
	}
	//设置支付密码
	public function pay_password()
	{
		$backurl = base64_encode(U('App/Vip/index'));
		$this->checkLogin($backurl);
		$vipid = self::$WAP['vipid'];
		$m = M('Vip');
		if(IS_POST) {
			$oldpassword = I('oldpassword');
			$password = I('password');
			$rpassword = I('rpassword');
			$vip = $m->where('id='.$vipid)->find();
			if(md5($oldpassword) != $vip['pay_password']) {
				$this->error('原密码不正确！');
			}
			if(!is_numeric($password) || strlen($password)!=6) {
				$this->error('请输入6位数字密码！');
			}
			if($password != $rpassword) {
				$this->error('两次输入密码不一致！');
			}
			$re = $m->where('id='.$vipid)->setField('pay_password', md5($password));
			if(FALSE !== $re) {
				$this->success('修改成功！');
			} else {
				$this->error('修改失败！');
			}
		} else {
			$this->assign('actname', 'ftvip');
			$this->display();
		}
	}
	//重置支付密码
	public function pay_password_reset()
	{
		$backurl = base64_encode(U('App/Vip/index'));
		$this->checkLogin($backurl);
		$vipid = self::$WAP['vipid'];
		$m = M('Vip');
		if(IS_POST) {
			$idno = I('idno');
			$oldpassword = I('oldpassword');
			$password = I('password');
			$rpassword = I('rpassword');
			$code= I('code');
			$vip = $m->where('id='.$vipid)->find();
			if(!$idno) {
				$this->error('请输入您的身份证号码！');
			}
			if(!$vip['idno']) {
				$this->error('您未实名认证！');
			}
			if($idno != $vip['idno']) {
				$this->error('证件号码不正确！');
			}
			if(!is_numeric($password) || strlen($password)!=6) {
				$this->error('请输入6位数字密码！');
			}
			if($password != $rpassword) {
				$this->error('两次输入密码不一致！');
			}
			if(!$code) {
				$this->error('请输入验证码！');
			}
			if($code != session('PwdVcode')) {
				$this->error('验证码错误！');
			}
			session('PwdVcode',null);
			$re = $m->where('id='.$vipid)->setField('pay_password', md5($password));
			if(FALSE !== $re) {
				$this->success('修改成功！');
			} else {
				$this->error('修改失败！');
			}
		} else {
			$this->assign('actname', 'ftvip');
			$this->display();
		}
	}
	//充值记录
	public function czRecord() {
		$m = M('Vip_log');
		$map['vipid'] = $_SESSION['WAP']['vipid'];
		$map['type'] = 7;
		$map['status'] = 2;
		$cache = $m->where($map)->page(1,$this->pagesize)->order('id DESC')->select();
		$this->assign('cache', $cache);
		$this->assign('datamore', count($cache) >= $this->pagesize ? 1 : 0 );
		$this->assign('actname', 'ftvip');
		$this->display();
	}
	//提现记录
	public function txRecord() {
		$m = M('Vip_tx');
		$map['vipid'] = $_SESSION['WAP']['vipid'];
		$cache = $m->where($map)->page(1,$this->pagesize)->order('id DESC')->select();
		foreach($cache as $k => $v) {
			$cache[$k]['txcard'] = substr($v['txcard'], -4);
		}
		$this->assign('cache', $cache);
		$this->assign('datamore', count($cache) >= $this->pagesize ? 1 : 0 );
		$this->assign('actname', 'ftvip');
		$this->display();
	}
	//我的积分
	public function credit()
	{
		$type = I('type', 0, 'intval');
		$this->assign('type', $type);
		$vip = self::$WAP['vip'];
		$m = M('Vip_log_credit');
		if($type) {
			$map['type'] = $type;
		}
		$map['vipid'] = $vip['id'];
		$cache = $m->where($map)->page(1, $this->pagesize)->order('id DESC')->select();
		$this->assign('cache', $cache);
		$this->assign('datamore', count($cache) >= $this->pagesize ? 1 : 0 );
		$this->assign('vip', $vip);
		$this->assign('actname', 'ftvip');
		$this->display();
	}
	//我的推广
	public function tg()
	{
		$vipid = $_SESSION['WAP']['vipid'];
		$type = I('type', 0, 'intval');
		$this->assign('type', $type);
		$mlog = M('Vip_log_tj');
		
		//带来注册人数
		$reg_count = $mlog->where('vipid='.$vipid)->count();
		//带来注册佣金
		$reg_money = $mlog->where('vipid='.$vipid)->sum('money');
		//带来首次购买人数
		$xfMap['vipid'] = $vipid;
		$xfMap['status'] = array('neq', 2);
		$xf_count = M('Vip_log_xftj')->where($xfMap)->count();
		//佣金拟
		$xf_money_ni = M('Vip_log_xftj')->where($xfMap)->sum('money');
		//已到账佣金
		$xf_money_dz = M('Vip_log_xftj')->where('vipid='.$vipid.' and status=1')->sum('money');
		//未到账佣金
		$xf_money_wdz = M('Vip_log_xftj')->where('vipid='.$vipid.' and status=0')->sum('money');
		$this->assign('reg_count', $reg_count);
		$this->assign('reg_money', $reg_money > 0 ? $reg_money : 0);
		$this->assign('xf_count', $xf_count);
		$this->assign('xf_money_ni', $xf_money_ni > 0 ? $xf_money_ni : 0);
		$this->assign('xf_money_dz', $xf_money_dz > 0 ? $xf_money_dz : 0);
		$this->assign('xf_money_wdz', $xf_money_wdz > 0 ? $xf_money_wdz : 0);
		$this->assign('vipset', self::$WAP['vipset']);
		$this->assign('actname', 'ftvip');
		$this->display();
	}
	//推广佣金
	public function tglog()
	{
		$type = I('type', 0, 'intval');
		$this->assign('type', $type);
		$this->assign('actname', 'ftvip');
		$this->display();
	}
	//购买VIP会员
	public function upgrade()
	{  

		$backurl = base64_encode(U('App/Vip/upgrade'));
		$this->checkLogin($backurl);
		$vipid = self::$WAP['vipid'];
		$vip_rule = explode(",", self::$WAP['vipset']['vip_rule']);
		foreach ($vip_rule as $k => $v) {
			$vip_rule[$k] = explode(":", $v);
		}
		if(IS_POST) {
			$paytype = I('paytype');
			$password = I('password');
			$type = I('type');
			$money = 0;
			foreach($vip_rule as $k => $v) {
				if($v[0] == $type) {
					$money = $v[1];
				}
			}
			if($money <=0){
				$this->error('数据异常');
			}
			$mvip = M('Vip');
			$vip = $mvip->where('id='.$vipid)->find();
			if($vip['groupid']==2){
				$this->error('您已是VIP会员！');
			}
           
            if($vip['vip_apply_status'] != 0){
                $this->error('您已经申请过vip会员！');
            }

			//会员升级log，同时作为升级返回数据调用
			$data_log['ip'] = get_client_ip();
			$data_log['vipid'] = $vipid;
			$data_log['openid'] = $_SESSION['WAP']['vip']['openid'];
			$data_log['nickname'] = $_SESSION['WAP']['vip']['nickname'];
			$data_log['ctime'] = time();
			$data_log['event'] = "会员购买VIP";
			$data_log['money'] = $money;
			$data_log['opid'] = date('Ymd') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
			$data_log['code'] = $type;
			$data_log['money'] = $money;
			$data_log['status'] = 1;
			$data_log['type'] = 13;
			$logid = M('vip_log')->add($data_log);
			if($paytype == 'money') {
				//检查支付密码
				$mlog = M('Vip_paypassword_error_log');
				$start_time = strtotime(date('Y-m-d'));
				$end_time = $start_time + 60*60*24 -1;
				//删除今天零时以前的记录
				$mlog->where('ctime<'.$start_time)->delete();
				$map['vipid'] = $data['vipid'];
				$map['ctime'] = array ('BETWEEN',array ($start_time,$end_time));
				$count = $mlog->where($map)->count();
				if($count>=3) {
					$this->error('输入错误密码超过3次!');
				}
				$pay_password = $mvip->where('id='.$vipid)->getField('pay_password');
				if(md5($password) != $pay_password) {
					//写入日志
					$log['vipid'] = $vipid;
					$log['ctime'] = time();
					$mlog->add($log);
					$this->error('支付密码错误!');
				} else {
					$vipMap['id'] = $vipid;
					$vipMap['money'] = array('egt', $money);
					$re = $mvip->where($vipMap)->setDec('money', $money);
					if(FALSE !== $re) {
						$upgradeData['vip_apply_time'] = time();
                        $upgradeData['vip_apply_status'] = 1;
                        $upgradeData['vip_apply_type'] = $type;
                        $re = $mvip->where('id='.$vipid)->save($upgradeData);

                         /*$upgradeData['groupid'] = 2;
                        $upgradeData['vtime'] = time();
                        $upgradeData['vip_expiration_time'] = strtotime('+'.$type.'months',time());
                        $re = $mvip->where('id='.$vipid)->save($upgradeData);*/

					
						if(FALSE !== $re) {
							//资金流水记录
							$mlog = M('Vip_log_money');
							$flow['vipid'] = $vip['id'];
							$flow['openid'] = $vip['openid'];
							$flow['nickname'] = $vip['nickname'];
							$flow['mobile'] = $vip['mobile'];
							$flow['money'] = $money;
							$flow['paytype'] = 'money';
							$flow['balance'] = $vip['money']-$money;
							$flow['type'] = 13;
							$flow['oid'] = $data_log['opid'];
							$flow['ctime'] = time();
							$flow['remark'] = '升级VIP会员';
							$rflow = $mlog->add($flow);

							M('vip_log')->where('id='.$logid)->setField('status',2);
							$this->success('已发送VIP会员申请！请您等待管理员审核');
						} else {
							$this->error('申请失败但扣款成功，请联系管理员！');
						}
					} else {
						$this->error('升级失败，请稍后再试！');
					}
				}
				
			} elseif($paytype == 'wxpay') {
				$_SESSION['wxpaysid'] = 0;
				$_SESSION['wxpayopenid'] = $_SESSION['WAP']['vip']['openid'];//追入会员openid
				$this->redirect('Home/Wxpayupgrade/pay', array('oid' => $data_log['opid']));
				break;
			} else {
				$this->error('未知支付方式');
			}
		} else {
			$this->assign('vip_rule', $vip_rule);
			$this->assign('actname', 'ftvip');
			$this->display();
		}
	}
	//个人信息
	public function myinfo() {
		$vipid = $_SESSION['WAP']['vipid'];
		$backurl = base64_encode(U('App/Vip/index'));
		$this->checkLogin($backurl);
		if(IS_POST){
			$m = M('vip');
			$post = I('post.');
			$data['nickname'] = $post['nickname'];
			if($data['nickname'] == '') {
				$this->error('昵称不能为空');
			}
			$data['sex'] = $post['sex'];
			if(!$data['sex']) {
				$this->error('请选择性别');
			}			
			$re = M('Vip')->where(array('id' => $vipid))->save($data);
			if(FALSE !== $re){
				$this->success('修改成功');
			}else {
				$this->error('修改失败');
			}
		} else {
			$data = self::$WAP['vip'];
			$this->assign('data', $data);
			$this->assign('actname', 'ftvip');
			$this->display();
		}
	}
	//支付
	public function pay() {
		
	}

    //申请分销商
    public function applyFx()
    {       
        if (IS_POST)
        {
            $vipid = self::$WAP['vipid'];
            $m = M('vip');
            $vip = $m->where('id='.$vipid)->find();
            if($vip['is_auth'] != 2) {
                $this->error('请先进行实名认证！');
            }
            if($vip['isfx'] == 1){
                $this->error('您已是分销商，无须重复申请！');
            }
            if($vip['apply_fx_status'] == 1) {
                $this->error('您的申请正在审核中，请耐心等待！');
            }
            $post = I('post.');
            $data['mobile'] = $post['mobile'];
            $reg = "/^(13[0-9]|14[0-9]|15[0-9]|17[0-9]|18[0-9])\d{8}$/";
            if(!$data['mobile']) {
                $this->error('联系电话不能为空！');
            }
            if(!preg_match($reg, $data['mobile'])) {
                $this->error('手机号码格式不正确！');
            }
            $data['apply_fx_status'] = 1;
            $re = $m->where("id=" . $vipid)->save($data);
            if (FALSE !== $re) {
                $this->success("提交成功，请耐心等待审核");
            } else {
                $this->error("提交失败");
            }
        } else {
            $data = self::$WAP['vip'];
            if($data['idno']) {
                $data['idno'] = substr_replace($data['idno'],'************',3,12);
            }
            $this->assign('data', $data);
            $this->display();
        }
    }
}
