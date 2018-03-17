<?php
namespace Home\Controller;

use Think\Controller;

class IndexController extends Controller
{
    public function index()
    {
        $this->display();
    }

    public function help()
    {
        $this->display();
    }

    public function about()
    {
        $temp = M('Shop_set')->find();
        $this->assign('shop', $temp);
        $this->display();
    }
    
    //定时任务执行分红发放操作
    public function fenhong()
    {
    	file_put_contents('./Data/autofenhonglog.txt', '执行时间:' . date('Y-m-d H:i:s'). "\r\n". PHP_EOL, FILE_APPEND);
    	$m = M('Finance_fhlog');
    	$m_goods = M('Finance_goods');
    	$m_order = M('Finance_order');
    	$m_contract = M('Finance_contract');
    	$mvip = M('vip');
    	$today = strtotime(date('Y-m-d', time())) + 24 * 60 * 60 -1;
    	$map['getdate'] = array('elt' ,$today);
    	$map['status'] = 0;
    	$fhlist = $m->where($map)->select();
    	if(!$fhlist) {
            echo '暂无待处理分红记录';
    		return false;
    	}
    	foreach($fhlist as $k => $v) {
    		$goods = $m_goods->where('id=' . $v['qid'])->find();
    		if(!$goods) {
    			continue;
    		}
    		$order = $m_order->where('id=' . $v['oid'])->find();
    		if(!$order) {
    			continue;
    		}
    		$vip = $mvip->where('id='.$v['to'])->find();
    		if(!$vip) {
    			continue;
    		}
    		if($vip) {
    			$result = $m->where('id='.$v['id'])->save(array('status'=>1,'etime'=>time()));
    			if($result) {
    				$re = $mvip->where('id='.$v['to'])->setInc('money', $v['money']);
    				if($re) {
    					//到期分红
    					if($goods['bonusway'] == 1) {
    						$bonusway = '到期分红';
    					} elseif($goods['bonusway'] == 2) {
    						$bonusway = '按月分红';
    					} elseif($goods['bonusway'] == 3) {
    						$bonusway = '按天分红';
    					}
    					//资金流水记录
    					$mlog = M('Vip_log_money');
    					$flow['vipid'] = $vip['id'];
    					$flow['openid'] = $vip['openid'];
    					$flow['nickname'] = $vip['nickname'];
    					$flow['mobile'] = $vip['mobile'];
    					$flow['money'] = $v['money'];
    					$flow['paytype'] = '';
    					$flow['balance'] = $vip['money'];
    					if($v['type'] == 1) {
    						$flow['type'] = 5;
    					} elseif($v['type'] == 2) {
    						$flow['type'] = 6;
    					}
    					$flow['oid'] = $order['oid'];
    					$flow['ctime'] = time();
    					if($v['type'] == 1) {
    						$flow['remark'] = '第'.$goods['id'].'期：'.$goods['name'].'收益发放('.$bonusway.')';
    					} elseif($v['type'] == 2) {
    						$flow['remark'] = '第'.$goods['id'].'期：'.$goods['name'].'本金返还('.$bonusway.')';
    					}
    					$rflow = $mlog->add($flow);
    					if($v['type'] == 2) {
    						//标记合同为已完结
    						$m_contract->where(['oid' => $order['oid']])->setField('status', 1);
    						//标记订单为完成状态
    						$m_order->where(array('id'=>$v['oid']))->setField('status',3);
    						if($goods['status'] == 2) {
    							//标记产品为到期返回全部款项状态
    							$m_goods->where('id=' . $goods['id'])->setField(array('status'=> 3,'etime'=>time()));
    						}
    					}
    				}
    			}
    		}
    	}
        echo 'SUCCESS';
    	return true;
    }
    //定时任务释放产品库存(自动关闭超过3分钟未支付的订单)
    public function releaseStocks()
    {
    	file_put_contents('./Data/autoReleaseStocks.txt', '执行时间:' . date('Y-m-d H:i:s'). "\r\n". PHP_EOL, FILE_APPEND);
    	$m_order = M('Finance_order');
    	$m_goods = M('Finance_goods');
    	$m_log = M('Finance_release_stocks');
    	$map['status'] = 1;
    	$map['ispay'] = 0;
    	$map['ctime'] = array('lt', time()-3*60);
    	$order_list = $m_order->where($map)->select();
    	foreach($order_list as $k => $v) {
    		$re = $m_order->where(array('id'=>$v['id']))->setField('status',0);
    		if(FALSE !== $re) {
    			//记录日志
    			$log['oid'] = $v['id'];
    			$log['goodsid'] = $v['goodsid'];
    			$log['vipid'] = $v['vipid'];
    			$log['totalnum'] = $v['totalnum'];
    			$log['ctime'] = time();
    			$m_log->add($log);
    			//还原库存和销量
    			$m_goods->where(array('id'=>$v['goodsid']))->setInc('num', $v['totalnum']);
    			$m_goods->where(array('id'=>$v['goodsid']))->setDec('sells', $v['totalnum']);
    			//如果产品状态是售罄，修改产品状态
    			$goods = $m_goods->where(array('id'=>$v['goodsid']))->find();
    			if($goods['num']>0 && $goods['status'] == 2) {
    				$m_goods->where(array('id'=>$v['goodsid']))->setField('status',1);
    			}
    		}
    	}
    }
    //定时查看是否有众筹订单过期
    public function pro()
    {
        //file_put_contents('./Data/autoPro.txt', '执行时间:' . date('Y-m-d H:i:s'). "\r\n". PHP_EOL, FILE_APPEND);
        $m_goods = M('Finance_goods');
        $m = M('finance_order');
        $time = time();
        $map['isproject'] = 1;
        $map['ishandle'] = 1;
        $map['endtime'] =array('lt',$time);
        $cache = $m_goods -> where($map) -> select();
        foreach ($cache as $k => $v) {
            if($cache[$k]['endtime']){
                $cache[$k]['percent'] = round($v['sells']/($v['num']+$v['sells'])*100,1);
                if($cache[$k]['percent']<50){
                    $set['issj'] = 0;
                    $set['ishandle'] = 0;
                    $re = $m_goods->where('id = '.$cache[$k]['id'])->setField($set);
                    $whe['goodsid'] = $cache[$k]['id'];
                    $whe['status'] = 2;
                    $vipData = $m->where($whe)->field('vipid,payprice')->select();
                    $sta = $m->where('goodsid = '.$cache[$k]['id'])->setField('status',0);
                    foreach ($vipData as $key => $value) {
                        $r = M('vip') -> where('id = '.$value['vipid']) -> setInc('money',$value['payprice']);
                        if($r){
                            $data_msg['pids'] = $value['vipid'];
                            $data_msg['title'] = "众筹失败，退回资金";
                            $data_msg['content'] = '您之前参加的'.$cache[$k]['name'].'众筹失败,现退回当时投资资金'.$value['payprice'].'元。感谢您的支持！';
                            $data_msg['ctime'] = time();
                            $rmsg = M('Vip_message')->add($data_msg);
                        }
                    }
                }else{
                    $set['status'] = 2;
                    $set['ishandle'] = 0;
                    $re = $m_goods->where('id = '.$cache[$k]['id'])->setField($set);
                    $sta = $m->where('goodsid = '.$cache[$k]['id'])->select();
                    foreach ($sta as $k => $v) {
                        $sta[$k]['contract'] = build_contract_no();
                        $m -> where('id = '.$v['id']) -> setField('contract',$sta[$k]['contract']);
                        $info = M('fx_syslog')->where('oid = '.$v['id'])->find();
                        $fx1 = M('vip')->where('id=' . $info['to'])->find();
                        $fx1['money'] = $fx1['money'] + $info['fxyj'];
                        $fx1['total_xxbuy'] = $fx1['total_xxbuy'] + 1; //下线中购买产品总次数
                        $fx1['total_xxyj'] = $fx1['total_xxyj'] + $info['fxyj']; //下线贡献佣金
                        $rfx = M('vip')->save($fx1);
                        if (FALSE !== $rfx) {
                            M('fx_syslog')->where('oid = '.$v['id'])->setField('status',1);
                        } 
                        doContract($sta[$k]);
                    }
                }
            }
        }

    }

    // public function aadd(){
    //     $where="address REGEXP '^[0-9]+$'";
    //     $data = M('Finance_order')->where($where) -> select();
    //     foreach ($data as $key => $value) {
    //         $map['vipid'] = $value['vipid'];
    //         $map['id'] = $value['address'];
    //         $address = M('vip_address') -> where($map) -> find();
    //         $info['name'] = $address['name'];
    //         $info['mobile'] = $address['mobile'];
    //         $info['address'] = $address['address'];
    //         $re = M('Finance_order') -> where('id = '.$value['id']) -> setField($info);
    //     }
    // }

    //发放带来消费佣金
    public function tgbuy()
    {
    	$m = M('Vip_log_xftj');
    	$mvip = M('Vip');
    	$map['status'] = 0;
    	$map['ctime'] = array('egt', strtotime('-1months',time()));
    	$cache = $m->where($map)->select();
    	foreach($cache as $k => $v) {
    		if($v['origin'] == 1) {
    			$order = M('Shop_order')->where('id='.$v['oid'])->find();
    		} else {
    			$order = M('Finance_order')->where('id='.$v['oid'])->find();
    		}
    		if(empty($order)) {
    			$m->where('id='.$v['id'])->setField('status',2);
    			continue;
    		}
    		if(!in_array($order['status'], array(2,3,5))){
    			$m->where('id='.$v['id'])->setField('status',2);
    			continue;
    		}
    		$vip = $mvip->where('id='.$v['pid'])->find();
    		if(!$vip['pid']) continue;
    		$old = M('Vip')->where('id='.$vip['pid'])->find();
    		if(empty($old)) continue;
    		$msg = "带来消费奖励：<br>消费用户：" . $vip['nickname'] . "<br>奖励内容：<br>";
    		if ($v['money']>0) {
    			$old['money'] = array('exp','money+'.$v['money']);
    			$msg = $msg . $money . "元余额<br>";
    		}
    		if ($v['score']>0) {
    			$old['score'] = array('exp','score+'.$v['score']);
    			$msg = $msg . $v['score'] . "个积分<br>";
    		}
    		$rold = $mvip->save($old);
    		if (FALSE !== $rold) {
    			$re = $m->where('id='.$v['id'])->setField('status',1);
    			if(FALSE != $re) {
	    			$data_msg['pids'] = $old['id'];
	    			$data_msg['title'] = "你的一份带来消费奖励已到账！";
	    			$data_msg['content'] = $msg;
	    			$data_msg['ctime'] = time();
	    			$rmsg = M('Vip_message')->add($data_msg);
	    			if($v['money']>0) {
                        $mlog = M('Vip_log_money');
	    				//资金流水记录
	    				$flow['vipid'] = $old['id'];
	    				$flow['openid'] = $old['openid'];
	    				$flow['nickname'] = $old['nickname'];
	    				$flow['mobile'] = $old['mobile'];
	    				$flow['money'] = $money;
	    				$flow['paytype'] = '';
	    				$flow['balance'] = $old['money'];
	    				$flow['type'] = 12;
	    				$flow['oid'] = $order['oid'];
	    				$flow['ctime'] = time();
	    				$flow['remark'] = '带来消费奖励（'.$vip['nickname'].'）';
	    				$rflow = $mlog->add($flow);
	    			}
	    			if ($v['score']>0) {
	    				log_credit($old['id'], $v['score'], 5, $order['oid'], $vip['id'], $vip['nickname']);
	    			}
    			}
    		}
    	}
    }

    public function actg(){
        $mact = M('activity');
        $mgroup = M('activity_group');
        $groups = $mgroup-> where('status = 0 and rtime <='.time()) -> select();
        $set = M('Set')->find();
        $options['appid'] = $set['wxappid'];
        $options['appsecret'] = $set['wxappsecret'];
        $wx = new \Util\Wx\Wechat($options);
        foreach ($groups as $key => $value) {
            $where['groupid'] = $value['id'];
            $where['status'] = 0;
            $num = $mact -> where($where) -> count();
            $goods = M('shop_goods') -> where('id = '.$value['goods_id']) -> find();
            if($num < $goods['peoplenum']){
                $info = $mact -> where($where) -> select();
                $actdel = $mact -> where($where) -> setField('status',2);
                $groupRes = $mgroup->where(array('id'=>$value['id']))->setField('status',2);
                foreach ($info as $k => $v) {
                    $cache = M('Shop_order')->where('id = '.$v['orderid']) -> find();
                    $m = M('Shop_order');
                    $mvip = M('Vip');
                    $mlog = M('Shop_order_log');
                    $mslog = M('Shop_order_syslog');
                    $vip = $mvip->where('id=' . $cache['vipid'])->find();
                    $payprice = $cache['payprice'];
                    $orderData['status'] = 6;
                    $orderData['closetime'] = time();
                    $re = $m->where('id=' . $cache['id'])->save($orderData);
                    if (FALSE !== $re) {
                    	M('shop_goods') -> where('id = '.$value['goodsid']) -> setDec('sells', $cache['totalnum']);                    	
                        $log['oid'] = $cache['id'];
                        $log['msg'] = '订单关闭-成功';
                        $log['ctime'] = time();
                        $rlog = $mlog->add($log);
                        if ($cache['ispay']) {
                            $mm = $vip['money'] + $payprice;
                            $rvip = $mvip->where('id=' . $cache['vipid'])->setField('money', $mm);
                            if ($rvip) {
                                //资金流水记录
                                $flow['vipid'] = $vip['id'];
                                $flow['openid'] = $vip['openid'];
                                $flow['nickname'] = $vip['nickname'];
                                $flow['mobile'] = $vip['mobile'];
                                $flow['money'] = $payprice;
                                $flow['paytype'] = '';
                                $flow['balance'] = $mm;
                                $flow['type'] = 9;
                                $flow['oid'] = $cache['oid'];
                                $flow['ctime'] = time();
                                $flow['remark'] = '拼团失败，订单退款，自动退款到用户余额';
                                $rflow = $mlog->add($flow);
                                //前端LOG
                                $log['oid'] = $cache['id'];
                                $log['msg'] = '自动退款' . $payprice . '元至用户余额-成功';
                                $log['ctime'] = time();
                                $rlog = $mlog->add($log);
                                $log['type'] = 6;
                                $log['paytype'] = $cache['paytype'];
                                $rslog = $mslog->add($log);
                                //后端LOG
                                $data_msg['pids'] = $cache['vipid'];
                                $data_msg['title'] = "拼团失败,已自动退款";
                                $data_msg['content'] = '您的订单'.$cache['oid'].'关闭成功。金额已自动退到您的账户余额。感谢您的支持！';
                                $data_msg['ctime'] = time();
                                $rmsg = M('Vip_message')->add($data_msg);
								
								$data = array();
								$data['touser'] = $vip['openid'];
								$data['template_id'] = 'yZV4GD_QvQ4zTg1zz3AaOPniHdYDtrKOtQJwpkUb2EM';
								$data['topcolor'] = "#00FF00";
								$data['data'] = array(
									'first' => array('value' => '您好，您参加的拼团由于团已过期，拼团失败。'),
									'keyword1' => array('value' => $goods['name']),
									'keyword2' => array('value' => '￥'.$payprice),
									'keyword3' => array('value' => '￥'.$payprice),
									'remark' => array('value' => '订单金额已退回您的账户，请注意查收！')
								);
								$re = $wx->sendTemplateMessage($data);
                            } else {
                                //前端LOG
                                $log['oid'] = $cache['id'];
                                $log['msg'] = '自动退款' . $payprice . '元至用户余额-失败!请联系客服!';
                                $log['ctime'] = time();
                                $rlog = $mlog->add($log);
                                //后端LOG
                                $log['type'] = -1;
                                $log['paytype'] = $cache['paytype'];
                                $rslog = $mslog->add($log);
                            }
                        }
                    }
                }
            }
        }
        if(IS_AJAX) {
        	$this->success('执行成功');
        }
    }  
    
    public function actkj() {
    	$now = time();
    	$list = M('bargain')->where('time<='.$now. ' and vipid=helpvipid and status=0')->select();
    	$set = M('Set')->find();
    	$options['appid'] = $set['wxappid'];
    	$options['appsecret'] = $set['wxappsecret'];
    	$wx = new \Util\Wx\Wechat($options);
    	foreach($list as $k => $v) {
    		$map['helpvipid'] = $v['vipid'];
    		$map['goodsid'] = $v['goodsid'];
    		$data['status'] = 2;
    		$data['etime'] = time();
    		$re = M('bargain')->where($map)->save($data);
    		if(FALSE !== $re) {
    			$vip = M('Vip')->where('id='.$v['vipid'])->find();
    			$goods_name = M('Shop_goods')->where('id='.$v['goodsid'])->getField('name');
    			$bargain =  M('bargain')->where($map)->order('id DESC')->find();
    			$data = array();
    			$data['touser'] = $vip['openid'];
    			$data['template_id'] = 'YHkvC0atoLpbWE8RJyafBNTDf4nub0RqwrsxqvOkBnw';
    			$data['topcolor'] = "#00FF00";
    			$data['url'] = $_SERVER['HTTP_HOST'] . U("/App/Activity/bargain", array('id'=>$v['goodsid'],"vipid" => $v['helpvipid']));
    			$data['data'] = array(
    					'first' => array('value' => '您的砍价已结束。'),
    					'keyword1' => array('value' => $goods_name),
    					'keyword2' => array('value' => $bargain['price'].'元'),
    					'remark' => array('value' => '您的砍价已于'.date('Y年n月j日 H:i',$v['time']).'结束，感谢您对农牧源的支持！')
    			);
    			$re = $wx->sendTemplateMessage($data);
    		}
    	}
    }
    //发送众筹产品模板消息
    function sendTplMessage() {
    	$m = M('Finance_tplmsg');
    	$lists = $m->where('status=0')->limit(50)->order('id ASC')->select(); 
    	$set = M('Set')->find();
    	$options['appid'] = $set['wxappid'];
    	$options['appsecret'] = $set['wxappsecret'];
    	$wx = new \Util\Wx\Wechat($options);
    	$success = $fail = 0;
    	$i = 0;
    	foreach($lists as $k => $v) {
    		if($i ==0) {
    			$start_id = $v['id'];
    		}
    		$data = array();
    		$data['touser'] = $v['openid'];
    		$data['template_id'] = 'OfU3taC8_Z_wDCmMzjlgYLVJ0CLeHiutop14Yt43DnA';
    		$data['topcolor'] = "#00FF00";
    		$data['url'] = $_SERVER['HTTP_HOST'] . U('App/Buy/details',array('id'=>$v['goodsid']));
    		$data['data'] = array(
    				'first' => array('value' => $v['first']),
    				'keyword1' => array('value' => $v['keyword1']),
    				'keyword2' => array('value' => $v['keyword2']),
    				'keyword3' => array('value' => $v['keyword3']),
    				'keyword4' => array('value' => $v['keyword4']),
    				'remark' => array('value' => $v['remark'])
    		);

    		$res = $wx->sendTemplateMessage($data);
    		$error['errcode'] = $wx->errCode;
    		$error['errmsg'] = $wx->errMsg;
    		$error['sendtime'] = time();
    		if(FALSE === $res) {
    			$fail++;
    			$error['status'] = 2;
    		} else {
    			$success++;
    			$error['status'] = 1;
    		}
    		$m->where('id='.$v['id'])->save($error);
    		$i++;
    	}
    	$log['start_id'] = $start_id;
    	$log['end_id'] = $i;
    	$log['success'] = $success;
    	$log['fail'] = $fail;
    	$log['ctime'] = time();
    	M('Finance_tplmsg_log')->add($log);
    }
    
	function test(){
		$m = M('Vip');
		$user = $m->order('id asc')->select();
		foreach($user as $k => $v) {
			if($v['pid']==0) {
				$data['path'] = 0;
				$data['plv'] = 1;
				$m->where('id='.$v['id'])->save($data);
			} else {
				$pvip = $m->where('id='.$v['pid'])->find();
				if(!empty($pvip)) {
					$data['path'] = $pvip['path'] . '-' . $pvip['id'];
					$data['plv'] = $pvip['plv'] + 1;
					$m->where('id='.$v['id'])->save($data);
				}
			}
		}
	}
}