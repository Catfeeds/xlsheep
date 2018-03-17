<?php
// 金融模块
namespace App\Controller;

class BuyController extends BaseController
{

	protected $bonusway = null;
	
	public $pagesize = 10;
	
    public function _initialize()
    {
        //你可以在此覆盖父类方法	
        parent::_initialize();
        $shopset = M('Shop_set')->where('id=1')->find();
        if ($shopset['pic']) {
            $listpic = $this->getPic($shopset['pic']);
            $shopset['sharepic'] = $listpic['imgurl'];
        }
        if ($shopset) {
            self::$WAP['shopset'] = $_SESSION['WAP']['shopset'] = $shopset;
            $this->assign('shopset', $shopset);
        } else {
            $this->diemsg(0, '您还没有进行商城配置！');
        }
        $this->bonusway = array('1' => '到期分红', '2' => '按月分红', '3' => '按天分红');
    }

    public function index()
    {
    	//正常逻辑
    	$m = M('Finance_goods');
    	
    	$type = intval(I('cid')) ? intval(I('cid')) : 0;
    	$this->assign('cid', $cid);
    	if ($type) {
    		$map['cid'] = $type;
            $map['status'] = array('neq',3);
    	}else{
             $map['status'] = 1;
             $map['status'] = array('neq',3);
        }
        //状态:1：销售中，2：售罄，3：到期
    	//每页显示条数
        $map['issj'] = 1;
    	$cache = $m->where($map)->page(1, $this->pagesize)->order(array('status','id'=>'desc'))->select();
    	foreach ($cache as $k => $v) {
            $cache[$k]['percent'] = round($v['sells']/($v['num']+$v['sells'])*100,1);
            if($cache[$k]['isproject']&&$cache[$k]['endtime']&&$cache[$k]['endtime']<time()){
                if($cache[$k]['percent']>50){
                    $re = $m->where('id = '.$cache[$k]['id'])->setField('status',2);
                    $cache[$k]['status'] = '2';
                }else{
                    $re = $m->where('id = '.$cache[$k]['id'])->setField('issj',0);
                    unset($cache[$k]);
                    continue;
                }
            }else{
                $cache[$k]['cate'] = M('Finance_cate')->where('id=' . $v['cid'])->find();
                $listpic = $this->getPic($v['pic']);
                $cache[$k]['imgurl'] = $listpic['imgurl'];
                $cache[$k]['ro'] = intval($v['sells']/($v['sells']+$v['num'])*360/10)*10;
            }
            if($v['isobject']&&$v['ismoney']){
                $cache[$k]['way'] = 3;
            }else{
                if($v['isobject']&&!$v['ismoney']){
                    $cache[$k]['way'] = 2;
                }else{
                    $cache[$k]['way'] = 1;
                }
            }
    	}
    	$count = $m->where($map)->count();
    	//var_dump($cache);die;
    	$this->assign('cid', $type);
    	$this->assign('cache', $cache);
    	$this->assign('datamore', $count > $this->pagesize ? 1 :0);
    	//高亮底导航
    	$this->assign('actname', 'fthome');
    	$this->display();
    }
    //我的钱包
    public function income()
    {
    	$vipid = $_SESSION['WAP']['vipid'];
    	$data = M("vip")->where(array("id"=>$vipid))->find();

    	//本月收益
    	$fhlModel = M('Finance_fhlog');
    	$bysyMap['to'] = $vipid;
    	$bysyMap['type'] = 1;
    	$bysyMap['status'] = 1;
    	$bysyMap['etime'] = array('egt',strtotime(date('Y-m-01')));
    	$bysy = $fhlModel->where($bysyMap)->sum('money');
    	$data['bysy'] = $bysy > 0 ? number_format($bysy,2) : 0.00;
    	//已得收益
    	$ydsyMap['to'] = $vipid;
    	$ydsyMap['type'] = 1;
    	$ydsyMap['status'] = 1;
    	$ydsy = $fhlModel->where($ydsyMap)->sum('money');
    	$data['ydsy'] = $ydsy > 0 ? $ydsy : 0;
    	//待返羊款
    	$dfykMap['to'] = $vipid;
    	$dfykMap['type'] = 2;
    	$dfykMap['status'] = 0;
    	$dfyk = $fhlModel->where($dfykMap)->sum('money');
    	$data['dfyk'] = $dfyk > 0 ? $dfyk : 0;
    	//待收收益
    	$dssyMap['to'] = $vipid;
    	$dssyMap['type'] = 1;
    	$dssyMap['status'] = 0;
    	$dssy = $fhlModel->where($dssyMap)->sum('money');
    	$data['dssy'] = $dssy > 0 ? $dssy : 0;  
    	//最后充值
    	$zhczMap['vipid'] = $vipid;
    	$zhczMap['type'] = 7;
    	$zhczMap['status'] = 2;
    	$zhcz = M('Vip_log')->where($zhczMap)->order('id DESC')->find();
    	$this->assign('zhcz', $zhcz);
    	//最后提现
    	$zhtxMap['vipid'] = $vipid;
    	$zhtx = M('Vip_tx')->where($zhtxMap)->order('id DESC')->find();
    	$this->assign('zhtx', $zhtx);
    	//最后收入
    	$zhsrMap['vipid'] = $vipid;
    	$zhsrMap['money'] = array('gt', 0);
    	$zhsr = M('Vip_log_money')->where($zhsrMap)->order('id DESC')->find();
    	$zhsr = empty($zhsr) ? 0 : $zhsr['money'];
    	$this->assign('zhsr', $zhsr);
    	//最后支出
    	$zhzcMap['vipid'] = $vipid;
    	$zhzcMap['money'] = array('lt', 0);
    	$zhzc = M('Vip_log_money')->where($zhzcMap)->order('id DESC')->find();
    	$zhzc = empty($zhzc) ? 0 : $zhzc['money'];
    	$this->assign('zhzc', $zhzc);    	
    	$this->assign('data', $data);
    	$this->assign('actname', 'ftvip');
    	$this->display();
    }
    //资金流水和订单记录
    public function record() {
    	$type = intval(I('type')) ? intval(I('type')) : 0;
    	$vlmModel = M('Vip_log_money');
    	$map['vipid'] = $_SESSION['WAP']['vipid'];
    	$pagesize = 10;
    	if($type) {
    		$map['type'] = $type;
    	}
    	$data = $vlmModel->where($map)->limit(0, $pagesize)->order('id DESC')->select();
    	foreach($data as $k => $v) {
    		$data[$k]['money'] = number_format($data[$k]['money'], 2);
    	}
    	$this->assign('data', $data);
    	$this->assign('type', $type);
    	$this->assign('t', time());
    	$this->assign('actname', 'ftvip');
    	$this->display();   	 
    }
    //记录详情
    public function recorddetails()
    {
    	$id = I('id') ? I('id') : $this->diemsg(0, '缺少ID参数!');
    	$vlmModel = M('Vip_log_money');
    	$cache = $vlmModel->where('id=' . $id)->find();
    	if(!$cache) {
    		$this->error('记录不存在！');
    	}
    	if($cache['vipid'] != $_SESSION['WAP']['vipid']) {
    		$this->error('您没有权限查看该记录！');
    	}
    	$cache['money'] = number_format($cache['money'],2);
    	$cache['balance'] = number_format($cache['balance'],2);
    	$this->assign('cache', $cache);
    	$this->assign('actname', 'ftvip');
    	$this->display();
    }
    public function details()
    {
    	$id = I('id') ? I('id') : $this->diemsg(0, '缺少ID参数!');
    	$morder = M('Finance_order');
        $m = M('Finance_goods');
    	$where['goodsid'] = $id;
    	$where['status'] = 1;
    	$where['vipid'] = $_SESSION['WAP']['vipid'];
    	$old_order = $morder->where($where)->find();
        if($old_order){
            if(strtotime($old_order['ctime'],'+15 minute')<time()){
                $r = $morder->where($where)->setField('status',0);
                $re_num = $m->where('id = ' . $old_order['goodsid'])->setInc('num', $old_order['totalnum']);
                $re_sells = $m->where('id = ' . $old_order['goodsid'])->setDec('sells', $old_order['totalnum']);
            }else{
                $this->assign('old_order', $old_order);
            }
        }
    	$cache = $m->where('id=' . $id)->find();
        if($cache['party']&&is_numeric($cache['party'])){
            $cache['partyinfo'] = M('Party')->where('id = '.$cache['party'])->find();
        }
    	if (!$cache) {
    		$this->error('此商品已下架！', U('App/Buy/index'));
    	}
    	if (!$cache['issj']) {
    		$this->error('此商品已下架！', U('App/Buy/index'));
    	}
        if($cache['stime']>time()){
            $this->assign('isys',1);
        }
    	$m->where(array("id" => $id))->setInc("clicks");
    	//绑定图集
    	if ($cache['album']) {
    		$appalbum = $this->getAlbum($cache['album']);
    		if ($appalbum) {
    			$this->assign('appalbum', $appalbum);
    		}
    	}
    	//绑定图片
    	if ($cache['pic']) {
    		$apppic = $this->getPic($cache['pic']);
    		if ($apppic) {
    			$this->assign('apppic', $apppic);
    		}
    	}
    	//分类
    	$mc = M('Finance_cate');
    	$cates = getCate($mc);
    	$cache['cate'] = isset($cates[$cache['cid']]) ? $cates[$cache['cid']] : '';
    	//分红方式
    	$bonusway = $cache['bonusway'];
    	$cache['bonusway'] = isset($this->bonusway[$bonusway]) ? $this->bonusway[$bonusway] : '';
    	$cache['price_formated'] = number_format($cache['price'],2);
    	$cache['copies_price_formated'] = number_format($cache['copies_price'],2);
    	$cache['content'] = filter_the_content(htmlspecialchars_decode($cache['content']));
    	//绑定登陆跳转地址
    	$backurl = base64_encode(U('App/Buy/details', array('id' => $id)));
    	$loginback = U('App/Vip/login', array('backurl' => $backurl));
    	//详细购买记录
    	$m = M('Finance_order');
    	$map['goodsid'] = $id;
    	$map['O.status'] = array('gt', 1);
    	$userlistcount = $m->alias('as O')
    	->join('LEFT JOIN `'.C('DB_PREFIX').'vip` AS V ON O.vipid = V.id')
    	->where($map)
    	->count();
    	$userlist = $m->alias('as O')
    	->join('LEFT JOIN `'.C('DB_PREFIX').'vip` AS V ON O.vipid = V.id')
    	->field('V.headimgurl,V.nickname,O.totalnum,O.ctime')
    	->where($map)
    	->limit(10)
    	->order('O.ctime DESC')
    	->select();
    	//本期排行榜
    	$userranklist = $m->alias('as O')
    	->join('LEFT JOIN `'.C('DB_PREFIX').'vip` AS V ON O.vipid = V.id')
    	->field('V.headimgurl,V.nickname,sum(O.totalnum) as num')
    	->where($map)
    	->group('O.vipid')
    	->limit(10)
    	->order('num DESC,O.ctime asc')
    	->select();
    	$index = 1;
    	foreach ($userranklist as $k => $v) {
    		$userranklist[$k]['index'] = $index;
    		$index++;
    	}
        if($cache['isobject']&&$cache['ismoney']){
            $way = 3;
        }else{
            if($cache['isobject']&&!$cache['ismoney']){
                $way = 2;
            }else{
                $way = 1;
            }
        }
        $this->assign('way',$way);
    	$this->assign('userlist', $userlist);
    	$this->assign('usermore', $userlistcount > 1 ? 1 :0);
    	$this->assign('userranklist', $userranklist);
    	$this->assign('loginback', $loginback);
    	$this->assign('lasturl', $backurl);
    	$this->assign('cache', $cache);
    	//高亮底导航
    	$this->assign('actname', 'fthome');
    	$this->display();
    }

    //立刻购买逻辑
    public function fastbuy()
    {
    	if (IS_AJAX) {
    		$data = I('post.');
    		if (!$data) {
    			$info['status'] = 0;
    			$info['msg'] = '未获取数据，请重新尝试';
    			$this->ajaxReturn($info);
    		}
    		$vip = self::$WAP['vip'];
    		if($vip['is_auth'] != 2) {
    			$info['status'] = 0;
    			$info['zl'] = 1;
    			$info['msg'] = '您未进行实名认证，马上去验证？';
    			$this->ajaxReturn($info);
    		}
    		$m = M('Finance_goods');
        	$item = $m->where('id=' . $data['id'])->find();

    		if ($item) {
    			if(!$item['issj']) {
    				$info['status'] = 0;
    				$info['msg'] = '该产品已下架！';    	
    			} else {
    				if($item['status'] == 2) {
    					$info['status'] = 0;
    					$info['msg'] = '该产品已售罄！';
    				} elseif($item['status'] == 3) {
    					$info['status'] = 0;
    					$info['msg'] = '该产品已到期！';
    				} elseif(($item['num'])<$data['num']){
    					$info['status'] = 0;
    					$info['msg'] = '该产品剩余数量不足！';
    				} else {
    					$info['status'] = 1;
    					$info['msg'] = '正在跳转页面';
    				}    				
    			}
    		} else {    	
    			$info['status'] = 0;
    			$info['msg'] = '产品不存在！';    			
    		}
    		if($item['restrict'] > 0) {
	    		$morder = M('Finance_order');
	    		$buyed = $morder->where(array('vipid'=>$_SESSION['WAP']['vipid'],'goodsid'=>$item['id'],'ispay'=>1))->sum('totalnum');
	    		if(($buyed + $data['num'])>$item['restrict']) {
	    			$info['status'] = 0;
	    			$info['msg'] = '该产品限购'.$item['restrict'].',您已购买'.$buyed.'！';
	    		}
    		}
    		$this->ajaxReturn($info);
    	} else {
    		$this->diemsg(0, '禁止外部访问！');
    	}
    }

    //Order逻辑
    public function orderMake()
    {
    	if (IS_POST) {
    		$vip = self::$WAP['vip'];
    		if($vip['is_auth'] != 2) {
    			$this->error('您未进行实名认证');
    		}
    		$morder = M('Finance_order');
    		$mgoods = M('Finance_goods');
    		$data = I('post.');
    		$data['ispay'] = 0;
    		$data['status'] = 1;//订单成功，未付款
    		$data['ctime'] = time();
    		$data['totalnum'] = intval($data['totalnum']);
    		if($data['totalnum']<=0) {
    			$this->error('数据异常！');
    		}
    		$data['goodsid'] = intval($data['goodsid']);
    		if($data['goodsid']<=0) {
    			$this->error('数据异常！');
    		}
    		$goods = $mgoods->where('id=' . $data['goodsid'])->find();
    		if(empty($goods)) {
    			$this->error('数据异常！');
    		}
    		if($data['delivery'] == 'since') {
    			if($data['sinceid']) {
    				$data['address'] = M('since')->where('id='.$data['sinceid'])->getField('address');
    			}
    			$data['vipname'] = $vip['name'];
    			$data['vipmobile'] = $vip['mobile'];
    		} else {
    			if($data['address']) {
    				$data['address'] = M('vip_address')->where('id='.$data['address'])->getField('address');
    			}
    		}
    		$isaddress = 0;
			if($data['tcid']>0) {
    			$isaddress = M('Finance_huibao')->where('id = '.$data['tcid'])->getField('address');
    			if($isaddress && $data['address'] == '') {
    				$this->error('请选择收货地址');
    			}
    		}
			if ($isaddress) {
				$totalprice = $data['totalnum'] * $goods['price'];
			} else {
				$totalprice = $data['totalnum'] * $goods['copies_price'];
			}
    		if($totalprice != $data['totalprice'] ) {
    			$this->error('数据异常！');
    		}
			
    		$map['status'] = 1;
    		$map['vipid'] = $_SESSION['WAP']['vipid'];
    		$order = $morder->where($map)->find();
    		if($order) {
    			$this->error('您有订单尚未支付，请先处理！',U('App/buy/details',array('id'=>$order['goodsid'])));
    		}
    		if($goods['issj']) {
    			if($goods['status'] == 1) {
    				if($goods['stime'] && $goods['stime']>time()) {
    					$this->error('该产品尚未开售，请耐心等待！');
    				}
					if ($isaddress) {
						if(($goods['num']) < $data['totalnum']) {
							$this->error('库存不足，下单失败！');
						}
					} else {
						if(($goods['copies']) < $data['totalnum']) {
							$this->error('库存不足，下单失败！');
						}
					}
    			} else {
    				if($goods['status']==2) {
    					$this->error('该产品已售罄，下单失败！');
    				}
    				if($goods['status']==3) {
    					$this->error('该产品已到期，下单失败！');
    				}
    			}
    		} else {
    			$this->error('该产品已下架，下单失败！');
    		}
    		$data['payprice'] = $totalprice;
    		//代金卷流程
    		if ($data['djqid']) {
    			$mcard = M('Vip_card');
    			$djq = $mcard->where('id=' . $data['djqid'])->find();
    			if (!$djq) {
    				$this->error('通讯失败！请重新尝试支付！');
    			}
    			if ($djq['usetime']) {
    				$this->error('此代金卷已使用！');
    			}
    			$djq['status'] = 2;
    			$djq['usetime'] = time();
    			$rdjq = $mcard->save($djq);
    			if (FALSE === $rdjq) {
    				$this->error('通讯失败！请重新尝试支付！');
    			}
    			//修改支付价格
    			$data['payprice'] = $data['totalprice'] - $djq['money'];
    		}
            $fh = M('finance_huibao') -> where('id = '.$data['tcid']) -> getField('address');
            if($fh){
                if($data['delivery']!='since'){
                    if($data['totalnum']>1){
                        $postage = $goods['postage']+($data['totalnum']-1)*$goods['postage']*0.5;
                    }elseif($data['totalnum']==1){
                        unset($data['name']);
                        unset($data['mobile']);
                        $postage = $goods['postage'];
                    }
                    $data['payprice'] += $postage;
                }
            }else{
                unset($data['delivery']);
            }
    		$mgoods->startTrans();
    		//减库存
			if ($isaddress) {
				$re_num = $mgoods->where('id=' . $data['goodsid'].' AND num>='.$data['totalnum'])->setDec('num', $data['totalnum']);
			} else {
				$re_num = $mgoods->where('id=' . $data['goodsid'].' AND copies>='.$data['totalnum'])->setDec('copies', $data['totalnum']);
			}
    		//增加销量
    		$re_sells = $mgoods->where('id=' . $data['goodsid'])->setInc('sells', $data['totalnum']);
    		//生成订单
    		$data['isobject'] = $goods['isobject'];
    		$re = $morder->add($data);
    		if($re_num && $re_sells && $re) {
    			$mgoods->commit();
    			$order = $morder->where('id=' . $re)->find();
    			if($order['goodsid']) {
    				$goods = M('Finance_goods')->where('id=' . $order['goodsid'])->find();
    			}
    			//返回到期时间
                if(!$goods['isproject']){
                    if(!$goods['rtime']){
                        $return_time = $goods['stime']+$goods['cycle']*86400;
                    }else{
                        $return_time = $goods['rtime'];
                    }
                    // $return_time = getFhDate($order['rtime'], $goods['cycle']);
                }else{
                    $return_time = getFhDate($goods['endtime'], $goods['cycle']);
                     // $return_time = getFhDate($goods['endtime'], $goods['cycle']);
                }
    			$old = $morder->where('id=' . $re)->setField(array('oid' => 'F'.date('YmdHis') . '-' . $re, 'rtime'=>$return_time));
    			//后端日志
    			$mlog = M('Finance_order_syslog');
    			$dlog['oid'] = $re;
    			$dlog['msg'] = '订单创建成功';
    			$dlog['type'] = 1;
    			$dlog['ctime'] = time();
    			$rlog = $mlog->add($dlog);
    			$this->success('订单创建成功，转向支付界面!',U('App/Buy/pay/',array('orderid'=>$re)));
    			$this->redirect('App/Buy/pay/', array('orderid' => $re));
    		} else {
    			$mgoods->rollback();//不成功，回滚
    			$this->error('下单失败，该产品库存不足，3分钟后未付款的订单将会释放，还有机会！');
    		}
    	} else {
    		//非提交状态
            $set = M('set')->find();   
    		$lasturl = $_GET['lasturl'] ? $_GET['lasturl'] : $this->diemsg(0, '缺少LastURL参数');
    		$backurl = base64_encode($lasturl);
    		$basketloginurl = U('App/Vip/login', array('backurl' => $backurl));
    		$re = $this->checkLogin($backurl);
    		$this->assign('lasturlencode', $lasturl);
    		$id = I('id');
    		$totalnum = I('num');
            $tcid = I('tcid');
    		$m = M('Finance_goods');
            $orderURL=base64_encode(U('App/Buy/orderMake',array('id'=>$id,'tcid'=>$tcid,'num'=>$totalnum,'lasturl'=>$lasturl)));
            $_SESSION['orderMake'] = $orderURL;
            $this->assign('orderurl',$orderURL);
    		$cache = $m->where('id=' . $id)->find();
    		//绑定图片
    		if ($cache['pic']) {
    			$apppic = $this->getPic($cache['pic']);
    			if ($apppic) {
    				$this->assign('apppic', $apppic);
    			}
    		}
			
            $isaddress = M('Finance_huibao')->where('id = '.$tcid)->getField('address');
            $this->assign('isaddress',$isaddress);
            if($isaddress==1){
                $vipadd = I('vipadd');
                if ($vipadd) {
                    $vipaddress = M('Vip_address')->where('id=' . $vipadd)->find();
                } else {
                    $addr['vipid'] = $_SESSION['WAP']['vipid']; 
                    $addr['is_default']=1;
                    $vipaddress = M('Vip_address')->where($addr)->find();
                }
                $this->assign('vipaddress', $vipaddress);
            }
			
    		$totalprice = 0;
			if($cache['status'] == 1) {
				if ($isaddress) {
					$totalprice = $cache['price'] * min($cache['num'], $totalnum);
				} else {
					$totalprice = $cache['copies_price'] * min($cache['copies'], $totalnum);
				}
			}
            if($totalnum>1){
                $postage = $cache['postage']+($totalnum-1)*$cache['postage']*0.5;
            }elseif($totalnum==1){
                $postage = $cache['postage'];
            }
            $this->assign('postage',$postage);
    		$cache['price_formated'] = number_format($cache['price'],2);
    		//可用代金卷
    		$mdjq = M('Vip_card');
    		$mapdjq['type'] = 2;
    		$mapdjq['vipid'] = $_SESSION['WAP']['vipid'];
    		$mapdjq['status'] = 1;//1为可以使用
    		$mapdjq['usetime'] = 0;
    		$mapdjq['etime'] = array('gt', time());
    		$mapdjq['usemoney'] = array('lt', $totalprice);
    		$djq = $mdjq->field('id,money')->where($mapdjq)->select();
    		$this->assign('djq', $djq);
            $ispassword = M('vip')->where('id='.$_SESSION['WAP']['vipid'])->getField('pay_password');
    		//是否可以用余额支付
    		$useryue = $_SESSION['WAP']['vip']['money'];
    		$isyue = $useryue - $totalprice >= 0 ? 0 : 1;
            if($cache['isobject']){
                if($cache['adressid']&&$cache['postage']){
                    $way = 3;
                }else{
                    if($cache['adressid']&&!$cache['postage']){
                        $way = 2;
                    }else{
                        $way = 1;
                    }
                }
            }
            //获取省份
            $p = M('region')->where(array('parent_id' => 0, 'level' => 1))->select();
            $this->assign('province', $p);
            $this->assign('iswx',$set['iswx']);
            $this->assign('ispassword',$ispassword);
            $this->assign('fahuo',$way);
    		$this->assign('isyue', $isyue);
            $this->assign('tcid', $tcid);
    		$this->assign('cache', $cache);
    		$this->assign('totalprice', $totalprice);
    		$this->assign('totalnum', $totalnum);
    		$this->display();
    	}
    }

    //订单支付
    public function pay()
    {
    	$orderid = I('orderid') <> '' ? I('orderid') : $this->diemsg(0, '缺少ORDERID参数');
    	$bkurl = U('App/Buy/pay', array('orderid' => $orderid));
    	$backurl = base64_encode($bkurl);
    	$loginurl = U('App/Vip/login', array('backurl' => $backurl));
    	$re = $this->checkLogin($backurl);
    	//已登陆
    	$m = M('Finance_order');
    	$mgoods = M('Finance_goods');
    	$order = $m->where('id=' . $orderid)->find();
    	if (!$order) {
    		$this->error('此订单不存在！');
    	}
    	if ($order['status'] <> 1) {
    		$this->error('此订单不可以支付！');
    	}
    	$paytype = I('type') ? I('type') : $order['paytype'];
    	switch ($paytype) {
    		case 'money':
    			$mvip = M('Vip');
    			$vip = $mvip->where('id=' . $_SESSION['WAP']['vipid'])->find();
    			$pp = $vip['money'] - $order['payprice'];

                if (empty($vip['is_auth'])) {
                    $this->error('尚未实名认证，不能购买！', U('App/Vip/index'));
                }

    			if ($pp >= 0) {
    				$vipData['money'] = array('exp','money-'.$order['payprice']);
    				$re = $mvip->where('id=' . $_SESSION['WAP']['vipid'])->save($vipData);
    				if (FALSE !== $re) {
    					$order['paytype'] = 'money';
    					$order['ispay'] = 1;
    					$order['paytime'] = time();
    					$order['status'] = 2;
    					$rod = $m->save($order);
    					if (FALSE !== $rod) {
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
								//生成分红日志
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
    							//前端日志
    							$mlog = M('Finance_order_log');
    							$dlog['oid'] = $order['id'];
    							$dlog['msg'] = '余额-付款成功';
    							$dlog['ctime'] = time();
    							$rlog = $mlog->add($dlog);
    							//后端日志
    							$mlog = M('Finance_order_syslog');
    							$dlog['type'] = 2;
    							$rlog = $mlog->add($dlog);
    							//资金流水记录
    							$mlog = M('Vip_log_money');
    							$flow['vipid'] = $vip['id'];
    							$flow['openid'] = $vip['openid'];
    							$flow['nickname'] = $vip['nickname'];
    							$flow['mobile'] = $vip['mobile'];
    							$flow['money'] = -$order['payprice'];
    							$flow['paytype'] = 'money';
    							$flow['balance'] = $pp;
    							$flow['type'] = 4;
    							$flow['oid'] = $order['oid'];
    							$flow['ctime'] = time();
    							$flow['remark'] = $goods ? '众筹创业项目：'.$goods['name'] : '';
    							$rflow = $mlog->add($flow);
    							/*
    							if(self::$WAP['vipset']['vip_buy_amount']>0 && self::$WAP['vipset']['vip_buy_period']>0){
	    							//设置VIP会员
	    							$half_year_ago = strtotime('-6months',time());//半年内的消费有效
	    							if($vip['ftime'] && ($vip['ftime'] > $half_year_ago)) {
	    								$vtime = $vip['ftime'];    			
	    							} else {
	    								$vtime = $half_year_ago;
	    							}   	
	    							//半年来金融消费总额
	    							$totalpay = get_user_totalpay($vip['id'], 2, $vtime);
                                    if($vip['groupid']==1){
    	    							if($totalpay >= self::$WAP['vipset']['vip_buy_amount']){
    		    							$expiration_time = strtotime("+".intval(self::$WAP['vipset']['vip_buy_period'])." months", time()); //有效期
    			    						if($expiration_time > $vip['vip_expiration_time']) {
    			    							$data_vip['groupid'] = 2;
    			    							$data_vip['ftime'] = time();
    			    							$data_vip['vip_expiration_time'] = $expiration_time;
    			    							$re = $mvip->where('id='.$vip['id'])->save($data_vip);
    			    							if(FALSE !== $re) {
    			    								$data_msg['pids'] = $vip['id'];
    			    								$data_msg['title'] = "恭喜您，成功升级为VIP会员！";
    			    								$data_msg['content'] = '您半年内在'.self::$SHOP['set']['name'].'消费达到'.self::$WAP['vipset']['vip_buy_amount'].'元。为此系统自动将您升级为VIP会员，购买商品将享受会员专享价，有效期至'.date('Y-m-d',$expiration_time).'，感谢您的支持！';
    			    								$data_msg['ctime'] = time();
    			    								$rmsg = M('Vip_message')->add($data_msg);
    			    							}
    			    						}
    	    							}
                                    }
    							}
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
    								$tj_xf_money = self::$WAP['vipset']['tj_xf_money'];
    								$tj_xf_score = self::$WAP['vipset']['tj_xf_score'];
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
    									//带来消费佣金
    									$tj_money = M('Vip_log_xftj')->where($logmap)->sum('money');
    									//带来注册佣金
    									$tj_reg_money = M('Vip_log_tj')->where('vipid='.$vip['id'])->sum('money');
    									$tj_money = $tj_money + $tj_reg_money;
    									$money =  $order['payprice']/1000*$tj_xf_money;
    									if($tj_money > self::$WAP['vipset']['max_tg_commission']) {
    										$money = 0;
    									} elseif($tj_money + $money > self::$WAP['vipset']['max_tg_commission']){
    										$money = self::$WAP['vipset']['max_tg_commission'] - $tj_money;
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
                                $this->success('余额付款成功！', U('App/Buy/orderList'));  
                                // if (self::$SHOP['set']['isfx']) {
                                //     //首次支付成功自动变为花蜜
                                //     if ($vip && !$vip['isfx']) {
                                //         $rvip = $mvip->where('id=' . $_SESSION['WAP']['vipid'])->setField('isfx', 1);
                                //         $data_msg['pids'] = $_SESSION['WAP']['vipid'];
        
                                //         $shopset = self::$WAP['shopset'] = $_SESSION['WAP']['shopset'];
                                //         $data_msg['title'] = "您成功升级为" . $shopset['name'] . "的" . $shopset['fxname'] . "！";
                                //         $data_msg['content'] = "欢迎成为" . $shopset['name'] . "的" . $shopset['fxname'] . "，开启一个新的旅程！";
                                //         $data_msg['ctime'] = time();
                                //         $rmsg = M('vip_message')->add($data_msg);
                                //     }
        
                                //     //代收花生米计算-只减不增
                                //     $rds = $this->doDs($order);
                                // }   
    						} else {
    							//退回余额
    							$re = $mvip->where('id=' . $_SESSION['WAP']['vipid'])->setInc('money', $order['payprice']);
	    						//后端日志
	    						if(FALSE !== $re) {
	    							$mlog = M('Finance_order_syslog');
	    							$dlog['oid'] = $order['id'];
	    							$dlog['msg'] = '余额付款失败（库存不足）';
	    							$dlog['type'] = -1;
	    							$dlog['ctime'] = time();
	    							$rlog = $mlog->add($dlog);
	    							$this->error('余额付款失败，本期产品已售罄！');
    							} else {
    								$this->error('余额付款失败！请联系客服！');
    							}
    						}  						  		
    					} else {
    						//后端日志
    						$mlog = M('Finance_order_syslog');
    						$dlog['oid'] = $order['id'];
    						$dlog['msg'] = '余额付款失败';
    						$dlog['type'] = -1;
    						$dlog['ctime'] = time();
    						$rlog = $mlog->add($dlog);
    						$this->error('余额付款失败！请联系客服！');
    					}
    
    				} else {
    					//后端日志
    					$mlog = M('Finance_order_syslog');
    					$dlog['oid'] = $order['id'];
    					$dlog['msg'] = '余额付款失败';
    					$dlog['type'] = -1;
    					$dlog['ctime'] = time();
    					$this->error('余额支付失败，请重新尝试！');
    				}
    			} else {
    				$this->error('余额不足，请充值之后再进行付款！');
    			}
    			break;
    		case 'alipayApp':
    			$this->redirect("App/Alipay/alipay", array('price' => $order['payprice'], 'oid' => $order['oid']));
    			break;
    		case 'wxpay':
    			$_SESSION['wxpaysid'] = 0;
    			$_SESSION['wxpayopenid'] = $_SESSION['WAP']['vip']['openid'];//追入会员openid
    			$this->redirect('Home/Wxpayjr/pay', array('oid' => $order['oid']));
    			break;
    		default:
    			$this->error('支付方式未知！');
    			break;
    	}   
    }
    //我的金融订单列表
    public function orderList() 
    {
    	$bkurl = U('App/Buy/orderList');
    	$backurl = base64_encode($bkurl);
    	$loginurl = U('App/Vip/login', array('backurl' => $backurl));
    	$re = $this->checkLogin($backurl);
    	//已登陆
    	$vipid = $_SESSION['WAP']['vipid'];
    	$map['vipid'] = $vipid;
    	$map['O.status']= array('egt', 2);
    	$m = M('Finance_order');
    	$cache = $m->alias('as O')->join('LEFT JOIN `'.C('DB_PREFIX').'finance_goods` AS G ON O.goodsid = G.id')
    				->where($map)->field('O.*,G.bonusway,G.name,G.vname,G.rate,G.cycle,G.cid,G.price, G.copies_price,G.unit,G.isproject,G.endtime,G.status as goodstatus')->order('ctime desc')->select();
    	if ($cache) {
    		foreach ($cache as $k => $v) {
    			if ($v['goodsid']) {
    				if($v['cid']) {
    					$category = M('Finance_cate')->where('id=' . $cache[$k]['cid'])->find();
    				}
    				$cache[$k]['cycle'] = $cache[$k]['cycle'].'天';
    				$cache[$k]['rate'] = ($cache[$k]['rate']*100).'%';
    				$cache[$k]['catname'] = isset($category)&&$category['name'] ? $category['name'] : '';
    			}
                if($v['tcid']){
                    $cache[$k]['isaddress'] = M('finance_huibao')->where('id = '.$v['tcid'])->getField('address');
                }
    		}
    	}
        // var_dump(time());exit;
        $this->assign('now',time());
    	$this->assign('actname', 'ftvip');
    	$this->assign('cache', $cache);
    	$this->display();
    }
    
    //订单详情
    public function orderDetail()
    {
    	$orderid = I('orderid') <> '' ? I('orderid') : $this->diemsg(0, '缺少ORDERID参数');
    	$bkurl = U('App/Buy/orderDetail', array('orderid' => $orderid));
    	$backurl = base64_encode($bkurl);
    	$loginurl = U('App/Vip/login', array('backurl' => $backurl));
    	$re = $this->checkLogin($backurl);
    	//已登陆
    	$m = M('Finance_order');
    	$vipid = $_SESSION['WAP']['vipid'];
    	$map['id'] = $orderid;
    	$cache = $m->where($map)->find();
    	if (!$cache) {
    		$this->diemsg('此订单不存在!');
    	}
        if($cache['tcid']){
            $cache['isaddress'] = M('finance_huibao')->where('id = '.$cache['tcid'])->getField('address');
        }
    	$mgoods = M('Finance_goods');
    	$cache['goods'] = $mgoods->where(array('id'=>$cache['goodsid']))->find();
        $needday = $cache['goods']['cycle'];
    	if($cache['goods']){
    		$category = M('Finance_cate')->where('id=' . $cache['goods']['cid'])->limit(1)->find();
    		$cache['goods']['catname'] = !empty($category) ? $category['name'] : '';
    		$cache['goods']['cycle'] = $cache['goods']['cycle'].'天';
    		$cache['goods']['rate'] = ($cache['goods']['rate']*100).'%';
    	}
    	$cache['now'] = time();//现在时间

        if($cache['goods']['isproject']&&$cache['goods']['endtime']>$cache['now']){//项目众筹
            $cache['ctime'] = $cache['goods']['endtime'];
            $time = '+ '.$needday.' day';
            $cache['rtime'] = strtotime($time,$cache['ctime']);
            $cache['lefttime'] = $needday;
        }else{//实物众筹
            $datetime = $cache['rtime'];
            // $cache['past'] = diffBetweenTwoDays($cache['ctime'], $cache['now']);//过去的天数
              $cache['past'] = diffBetweenTwoDays($cache['ctime'], $cache['now']);//过去的天数
            // $cache['lefttime'] = ($needday - $cache['past']) > 0 ? ($needday - $cache['past']) : '0';
            if($cache['rtime']>$cache['now'])
            {
              $cache['lefttime'] =  diffBetweenTwoDays($cache['rtime'], $cache['now']);
            }
            else
            {
                $cache['lefttime']=0;
            }
        }
    	//分红日志
    	$mlog = M('Finance_fhlog');
    	$expert_bonuses = $mlog->where(array('oid'=>$cache['id'],'type'=>1))->sum('money');//预期分红
    	$granted_bonuses = $mlog->where(array('oid'=>$cache['id'],'type'=>1, 'status'=>1))->sum('money');//已发分红
    	$cache['expert_bonuses'] = $expert_bonuses > 0 ? round($expert_bonuses, 2) : 0;
    	$cache['granted_bonuses'] = $granted_bonuses > 0 ? round($granted_bonuses, 2) : 0;
    	$log = $mlog->where(array('oid'=>$cache['id'], 'status'=>1, 'type'=>1))->select();
    	$bjlog = $mlog->where(array('oid'=>$cache['id'], 'status'=>1, 'type'=>0))->find();
    	$fee = $cache['totalnum']*$cache['goods']['fee'];//按比例收取手续费
    	$this->assign('fee', $fee);
    	$this->assign('log', $log);
    	$this->assign('bjlog', $bjlog);
    	$this->assign('cache', $cache);
    	$this->assign('actname', 'ftvip');
    	$this->display();
    }
    
    //保单列表
    public function userbaodan()
    {
    	$orderid = I('orderid') <> '' ? I('orderid') : $this->diemsg(0, '缺少orderid参数');
    	//$this->display();
    }
    
    //充值记录
    public function viprecord()
    {
    	$vipid = $_SESSION['WAP']['vipid'];
    	$m = M('Vip_log');
    	if(IS_GET){
    		$get = I('get.');
    		$page = $get['page'];//获取请求的页数
    		if($page<1)$page = 1;
    		$size = 5;
    		$count = $m->where(array('vipid'=>$vipid,'type'=>7))->count();//总数
    		$totalPage = ceil($count/$size);//总页数
    		if($page>$totalPage)$page = $totalPage;
    		$data = $m->where(array('vipid'=>$vipid,'type'=>7))->order('id desc')->page($page,$size)->select();
    	}
    	$this->assign('page', $page);
    	$this->assign('totalPage', $totalPage);
    	$this->assign('data', $data);
    	$this->display();
    }
    //羊圈
    public function sheepfold()
    {
    	$vipid = $_SESSION['WAP']['vipid'];
    	$m = M('Finance_order_goods');
    	$map['B.vipid'] = $vipid;
    	$map['B.status'] = 2;
    	$cache = $m->alias('as A')
		    	->join('LEFT JOIN `'.C('DB_PREFIX').'finance_order` AS B ON A.oid = B.id')
		    	->join('LEFT JOIN `'.C('DB_PREFIX').'finance_goods` AS C ON B.goodsid = C.id')
		    	->where($map)
		    	->field('A.id as sid, C.*')
		    	->order('A.id desc')
		    	->select();
    	if(empty($cache)) {
    		$goodslist = M('Finance_goods')->where(array('status'=>1))->select();
    		$this->assign('goodslist', $goodslist);
    	}
    	//高亮底导航
    	$this->assign('actname', 'ftvip');
    	$this->assign('cache', $cache);
    	$this->display();
    }
    //养只详情
    public function sheepdetail()
    {
    	$id = I('id') ? I('id') : $this->diemsg(0, '缺少ID参数!');
    	$vipid = $_SESSION['WAP']['vipid'];
    	$vip = self::$WAP['vip'];
    	$m = M('Finance_order_goods');
    	$mgoods = M('Finance_goods');
        $map['B.status'] = 2;
    	$map['A.id'] = $id;
    	$cache = $m->alias('as A')
                ->join('LEFT JOIN `'.C('DB_PREFIX').'finance_order` AS B ON A.oid = B.id')->where($map)->find();

    	if(!$cache) {
    		$this->error('物品不存在！',U('App/Buy/sheepfold'));
    	}
    	$cache['goods'] = $mgoods->where(array('id'=>$cache['goodsid']))->find();
    	if ($cache['goods']['pic']) {
    		$listpic = $this->getPic($cache['goods']['pic']);
    		$cache['goods']['imgurl'] = $listpic['imgurl'];
    	}
        if ($cache['goods']['bigpic']) {
            $listpic = $this->getPic($cache['goods']['bigpic']);
            $cache['goods']['bigimgurl'] = $listpic['imgurl'];
        }
        $message = explode('。',$cache['goods']['message']);
        array_pop($message);
        $this -> assign('message',$message);
        $list['orderid'] = $cache['id'];
        $list['userid'] = $cache['vipid'];
        $data = M('user_message') -> where($list) -> find();
        $here['givelikeid'] = $data['id'];
        $here['userid'] = $vipid;
        $re = M('user_givelike') -> where($here) -> find();
        $vipinfo = M('vip') -> where('id = '.$cache['vipid']) -> find();
        $this->assign('vipinfo', $vipinfo);
        if(!empty($data)){
            $this -> assign('data',$data);
        }
        if($vipid == $cache['vipid']){
            $show = 1;
        }else{
            $show = 0;
        }
        if($re&&$re['status']!=0){
            $this -> assign('re',$re);
        }
        $this -> assign('show',$show);
    	//高亮底导航
    	$this->assign('actname', 'ftvip');
        $this->assign('id', $id);
    	$this->assign('vip', $vip);
    	$this->assign('cache', $cache);
    	$this->display();
    }

    public function message(){
        $m = M('user_message');
        if(IS_POST){
            $id = I('get.id') ? I('get.id') : $this->diemsg(0, '缺少ID参数!');
            $data = I('post.');
            $result['userid'] = $_SESSION['WAP']['vipid'];
            $result['orderid'] = $id;
            $result['content'] = $data['remark'];
            if($data['cid']){
                $result['etime'] = time();
                $re = $m -> where('id = '.$data['cid']) -> save($result);
                if($re){
                    $info['status'] = 1;
                    $info['msg'] = '修改成功';
                    $this->ajaxReturn($info);
                }else{
                    $info['status'] = 0;
                    $info['msg'] = '修改失败';
                    $this->ajaxReturn($info);
                }
            }else{
                $result['ctime'] = $result['etime'] = time();
                $result['givelike'] = 0;
                $re = $m -> add($result);
                if($re){
                    $info['status'] = 1;
                    $info['msg'] = '添加成功';
                    $this->ajaxReturn($info);
                }else{
                    $info['status'] = 0;
                    $info['msg'] = '添加失败';
                    $this->ajaxReturn($info);
                }
            }
        }else{
            $id = I('id') ? I('id') : $this->diemsg(0, '缺少ID参数!');
            $vipid = $_SESSION['WAP']['vipid'];
            $map['orderid'] = $id;
            $map['userid'] = $vipid;
            $data = $m -> where($map) -> find();
            if(!empty($data)){
                $this -> assign('data',$data);
            }
        }
        $this -> assign('id',$id);
        $this -> display();
    }

    public function givelike(){
        if(IS_POST){
            $data = I();
            $vipid = $_SESSION['WAP']['vipid'];
            $map['givelikeid'] = $data['id'];
            $map['userid'] = $vipid;
            $res = M('user_givelike') -> where($map) -> find();
            if($res){
                if($data['value'] == 0){
                    $re = M('user_message') -> where('id = '.$data['id']) -> setInc('givelike');
                    $r = M('user_givelike') -> where($map) -> setField('status','1');
                    if($re){
                        $info['status'] = 1;
                        $info['msg'] = '点赞成功';
                        $info['value'] = '1';
                        $info['url'] = '/Public/App/images/like.png';
                        $this -> ajaxReturn($info);
                    }
                }else{
                    $give = M('user_message') -> where('id = '.$data['id']) -> getField('givelike');
                    if($give>0){
                        $r = M('user_givelike') -> where($map) -> setField('status','0');
                        $re = M('user_message') -> where('id = '.$data['id']) -> setDec('givelike');
                        if($re){
                            $info['status'] = 1;
                            $info['msg'] = '取消点赞成功';
                            $info['value'] = '0';
                            $info['url'] = '/Public/App/images/give.png';
                            $this -> ajaxReturn($info);
                        }
                    }
                }
            }else{
                $info['givelikeid'] = $data['id'];
                $info['userid'] = $vipid;
                if($data['value'] == 0){
                    $re = M('user_message') -> where('id = '.$data['id']) -> setInc('givelike');
                    $info['status'] = '1';
                    $r = M('user_givelike') -> where($map) -> add($info);
                    if($re){
                        $info['status'] = 1;
                        $info['msg'] = '点赞成功';
                        $info['value'] = '1';
                        $info['url'] = '/Public/App/images/like.png';
                        $this -> ajaxReturn($info);
                    }
                }else{
                    $info['msg'] = '非法操作';
                    $info['value'] = '0';
                    $info['url'] = '/Public/App/images/give.png';
                    $this -> ajaxReturn($info);
                }
            }
        }
    }
	public function choose(){
		$id = I('id') ? I('id') : $this->diemsg(0, '缺少ID参数!');
        $num = I('num') ? I('num') : $this->diemsg(0, '缺少num参数!');
        $vipid = $_SESSION['WAP']['vipid'];
        $mobile = M('vip') -> where('id = '.$vipid) -> getField('mobile');
        if(!$mobile){
            $this->error('请先认证手机号码',U('App/Vip/mobile'));
        }
		$m = M('Finance_goods');
		$info = $m->where('id=' . $id)->find();
        $map['zid'] = $id;
        $map['status'] = 1;
		$cate = M('Finance_huibao') -> where($map) -> select();
		$backurl = base64_encode(U('App/Buy/details', array('id' => $id)));
        $chooseurl = base64_encode(U('App/Buy/choose', array('id' => $id,'num'=>$num)));
        $this->assign('chooseurl', $chooseurl);
		$this->assign('lasturl', $backurl);
        $this -> assign('id',$id);
        $this -> assign('num',$num);
		$this -> assign('info',$info);
		$this -> assign('cate',$cate);
		$this->display();
	}
    public function success1(){
        $id = I('id') ? I('id') : $this->diemsg(0, '缺少ID参数!');
        $chooseurl = I('chooseurl') ? I('chooseurl') : $this->diemsg(0, '缺少chooseurl参数!');
        $url = base64_decode($chooseurl);
        $this->assign('url',$url);
        $cate = M('Finance_huibao') -> where('id = '.$id) -> find();
        $this -> assign('cate',$cate);
        $this->display();
    }

    //订单地址跳转
    public function orderAddress()
    {
        $sid = I('sid');
        $lasturlencode = I('lasturl');
        $_SESSION['WAP']['orderURL'] = base64_decode($lasturlencode);
        $this->redirect('App/Vip/address');
    }

    public function clorSave(){
        $id = I('id');
        $m = M('Finance_order');
        if (!$id) {
            $info['status'] = 0;
            $info['msg'] = 'ID不能为空!';
            $this->ajaxReturn($info);
        }
        $re = $m->where('id = '.$id)->setField('status',3);
        if ($re!==FALSE) {
            $info['status'] = 1;
            $info['msg'] = '核销成功!';
        } else {
            $info['status'] = 0;
            $info['msg'] = '核销失败!';
        }
        $this->ajaxReturn($info);
    }

}