<?php
// 本类由系统自动生成，仅供测试用途
namespace App\Controller;

class ScoreshopController extends BaseController
{

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
    }

    public function index()
    {
    	$data = self::$WAP['vip'];
    	$this->assign('data', $data);
    	$m = M('Shop_goods');
    	$map['status'] = 1;
        $map['cid']=13;
    	$cache = $m->where($map)->select();
    	foreach ($cache as $k => $v) {
    		$listpic = $this->getPic($v['listpic']);
    		$cache[$k]['imgurl'] = $listpic['imgurl'];
    	}
    	$this->assign('cache', $cache);
    	$this->assign('actname', 'ftvip');
    	$this->display();
    }
    
    public function goods()
    {
    	$id = I('id') ? I('id') : $this->diemsg(0, '缺少ID参数!');
    	$m = M('Score');
    	$cache = $m->where('id=' . $id)->find();
    	if (!$cache) {
    		$this->error('此商品已下架！', U('App/Scoreshop/index'));
    	}
    	if (!$cache['status']) {
    		$this->error('此商品已下架！', U('App/Scoreshop/index'));
    	}
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
    	$cache['content'] = filter_the_content(htmlspecialchars_decode($cache['content']));
    	$this->assign('cache', $cache);
    	$this->display();
    }
    //积分兑换
    public function exchange()
    {
    	if (IS_AJAX) {
    		$id = I('id');
    		if (!$id) {
    			$this->error('未获取数据，请重新尝试');
    		}
    		$vip = self::$WAP['vip'];
    		$m = M('Score');
    		$item = $m->where('id=' . $id)->find();
    
    		if ($item) {
    			if(!$item['status']) {
    				$this->error('该商品已下架！');
    			} else {
    				if(($item['num'])<1){
    					$this->error('该商品已兑完！');
    				} elseif($vip['score']<$item['score']){
    					$this->error('您的积分不足！');
    				} else {
    					$this->success('正在生成订单');
    				}
    			}
    		} else {
    			$this->error('商品不存在！');
    		}
    	} else {
    		$this->diemsg(0, '禁止外部访问！');
    	}
    }
    //生成积分订单
    public function orderMake(){
    	if (IS_POST) {
    		$data = I('post.');
    		if(!$data['score_id']) {
    			$this->error('缺少参数！');
    		}   
    		if(!$data['address_id']) {
    			$this->error('请选择收货地址！');
    		}
    		$vipid = $_SESSION['WAP']['vipid'];
    		$data['user_id'] = $vipid;
    		$map['status'] = 1;
    		$map['user_id'] = $vipid;
    		$m = M('Score');
    		$morder = M('Score_order');
    		$item = $m->where('id=' . $data['score_id'].' and status=1')->find();
    		if(!$item) {
    			$this->error('商品不存在！');
    		}
    		if($item['num'] <= 0) {
    			$this->error('商品已兑完！');
    		}
    		$vip = self::$WAP['vip'];
    		if($vip['score'] < $item['score']) {
    			$this->error('您的积分不足！');
    		}
    		$data['totalscore'] = $item['score'];
    		$data['status'] = 1;
    		$data['time'] = time();
    		$m->startTrans();
    		//减积分
    		$map['id'] = $vip['id'];
    		$map['score'] = array('egt', $item['score']);
    		$re_score = M('Vip')->where($map)->setDec('score', $item['score']);
    		//减库存
    		$re_num = $m->where('id=' . $data['score_id'].' AND num>=1')->setDec('num', 1);
    		//增加销量
    		$re_sells = $m->where('id=' . $data['score_id'])->setInc('sells', 1);
    		//生成订单
    		$re = $morder->add($data);
    		if($re_score && $re_num && $re_sells && $re) {
    			$m->commit();   
    			$oid = date('YmdHis') . '-' . $re;
    			$morder->where('id=' . $re)->setField(array('orderid' => $oid));
    			//积分日志
    			log_credit($vipid, $item['score'], 4, $oid);
    			$this->success('兑换成功',U('App/Scoreshop/index'));
    		} else {
    			$m->rollback();//不成功，回滚
    			$this->error('兑换失败，请稍后再试');
    		}
    	} else {
    		$id = I('id');
    		$m = M('Score');
    		$cache = $m->where('id=' . $id)->find();
    		//绑定图片
    		if ($cache['listpic']) {
    			$listpic = $this->getPic($cache['listpic']);
    			$cache['imgurl'] = $listpic['imgurl'];
    			
    		}
    		//收货地址
    		$vipadd = I('vipadd');
    		if ($vipadd) {
    			$address = M('Vip_address')->where('id=' . $vipadd)->find();
    		} else {
    			$address = M('Vip_address')->where('vipid=' . $_SESSION['WAP']['vipid']. ' and is_default=1')->find();
    		}
    		$this->assign('address', $address);
    		$this->assign('cache', $cache);
    		$this->display();
    	}
    }

    //订单地址跳转
    public function orderAddress()
    {
    	$id = I('id');
    	$backurl = U('App/Scoreshop/orderMake', array('id' => $id));
    	$_SESSION['WAP']['orderURL'] = $backurl;
    	$this->redirect('App/Vip/address');
    }
}
