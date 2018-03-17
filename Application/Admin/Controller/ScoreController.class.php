<?php
namespace Admin\Controller;

use Think\Controller;

class ScoreController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $m = M('Score');
        //设置面包导航，主加载器请配置
        $bread = array(
        		'0' => array(
        				'name' => '首页',
        				'url' => U('Admin/Index/index'),
        		),
        		'1' => array(
        				'name' => '积分管理',
        				'url' => U('Admin/Score/index'),
        		)
        );
        $this->assign('breadhtml', $this->getBread($bread));
        $m = M('Shop_goods');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $name = I('name') ? I('name') : '';
        if ($name) {
            $map['name'] = array('like', "%$name%");
            $this->assign('name', $name);
        }
        $map['cid'] = '13';
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $cache = $m->where($map)->page($p, $psize)->select();
        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '积分商品管理', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }
	public function goodsSet()
	{
		$id = I('id');
        $m = M('Shop_goods');
		//设置面包导航，主加载器请配置
		$bread = array(
				'0' => array(
						'name' => '首页',
						'url' => U('Admin/Index/index'),
				),
				'1' => array(
						'name' => '积分管理',
						'url' => U('Admin/Score/index'),
				),
				'2' => array(
						'name' => '积分商品设置',
						'url' => $id ? U('Admin/Score/goodsSet', array('id' => $id)) : U('Admin/Md/mdSet'),
				),
		);
		$this->assign('breadhtml', $this->getBread($bread));
		//处理POST提交
		if (IS_POST) {
            //die('aa');
            $data = I('post.');
            $data['content'] = trimUE($data['content']);
            if ($id) {
                $re = $m->save($data);
                if (FALSE !== $re) {
                    $info['status'] = 1;
                    $info['msg'] = '设置成功！';
                } else {
                    $info['status'] = 0;
                    $info['msg'] = '设置失败！';
                }
            } else {
                $re = $m->add($data);
                if ($re) {
                    $info['status'] = 1;
                    $info['msg'] = '设置成功！';
                } else {
                    $info['status'] = 0;
                    $info['msg'] = '设置失败！';
                }
            }
            $this->ajaxReturn($info);
        }
        //读取标签
        $label = M('Shop_label')->select();
        $this->assign('label', $label);
        //AppTree快速无限分类
        $field = array("id", "pid", "name", "sorts", "concat(path,'-',id) as bpath");
        $cate = appTree(M('Shop_cate'), 0, $field);
        $this->assign('cate', $cate);
        //处理编辑界面
        if ($id) {
            $cache = $m->where('id=' . $id)->find();
            $this->assign('cache', $cache);
        }
        $shopset = self::$SHOP['set'];
        $this->assign('shopset', $shopset);
        $this->display();
	}

    public function goodsDel()
    {
        $id = $_GET['id']; //必须使用get方法
        $m = M('Shop_goods');
        if (!$id) {
            $info['status'] = 0;
            $info['msg'] = 'ID不能为空!';
            $this->ajaxReturn($info);
        }
        $re = $m->delete($id);
        if ($re) {
            $info['status'] = 1;
            $info['msg'] = '删除成功!';
        } else {
            $info['status'] = 0;
            $info['msg'] = '删除失败!';
        }
        $this->ajaxReturn($info);
    }

 //    public function order()
 //    {
 //    	//设置面包导航，主加载器请配置
 //    	$bread = array(
 //    			'0' => array(
 //    					'name' => '首页',
 //    					'url' => U('Admin/Index/index'),
 //    			),
 //    			'1' => array(
 //    					'name' => '积分管理',
 //    					'url' => U('Admin/Score/index'),
 //    			),
 //    			'2' => array(
 //    					'name' => '积分订单管理',
 //    					'url' => U('Admin/Score/order'),
 //    			),
 //    	);
 //    	$this->assign('breadhtml', $this->getBread($bread));
 //    	$p = $_GET['p'] ? $_GET['p'] : 1;
 //    	$name = I('name') ? I('name') : '';
 //    	if ($name) {
 //    		//订单号邦定
 //    		$map['orderid'] = array('like', "%$name%");
 //    		$this->assign('name', $name);
 //    	}
 //        $m = D('ScoreOrder');
 //        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
 //        $cache = $m->where($map)->page($p, $psize)->order("id desc")->relation(true)->select();
 //        foreach($cache as $k => $v) {
 //        	$cache[$k]['contact'] = M('VipAddress')->where(array('id'=>$v['address_id']))->find();
 //        	$cache[$k]['goods'] = M('Score')->where(array('id'=>$v['score_id']))->find();
 //        }

 //        $count = $m->where($map)->count();
 //        $this->getPage($count, $psize, 'App-loader', '积分订单管理', 'App-search');
 //        $this->assign("cache", $cache);
 //        $this->display();
 //    }
 //    //订单发货
 //    public function orderDeliver()
 //    {
 //    	$id = I('id');
 //    	if (!$id) {
 //    		$info['status'] = 0;
 //    		$info['msg'] = '未正常获取ID数据！';
 //    		$this->ajaxReturn($info);
 //    	}
 //    	$re = M('ScoreOrder')->where('id=' . $id)->setField('status', 2);
 //    	$dwechat = D('Wechat');
 //    	if (FALSE !== $re) {
    
 //    		// 插入订单发货模板消息=====================
 //    		$order = M('Score_order')->where('id=' . $id)->find();
 //    		$vip = M('vip')->where(array('id' => $order['user_id']))->find();
 //    		$templateidshort = 'OPENTM201541214';
 //    		$templateid = $dwechat->getTemplateId($templateidshort);
    
 //    		if ($templateid) { // 存在才可以发送模板消息
 //    			$data = array();
 //    			$data['touser'] = $vip['openid'];
 //    			$data['template_id'] = $templateid;
 //    			$data['topcolor'] = "#0000FF";
 //    			$data['data'] = array(
 //    					'first' => array('value' => '您好，您的积分商品订单已发货'),
 //    					'keyword1' => array('value' => $order['orderid']),
 //    					'keyword2' => array('value' => $order['fahuokd']),
 //    					'keyword3' => array('value' => $order['fahuokdnum']),
 //    					'remark' => array('value' => '')
 //    			);
 //    			$options['appid'] = self::$SYS['set']['wxappid'];
 //    			$options['appsecret'] = self::$SYS['set']['wxappsecret'];
    
 //    			$wx = new \Util\Wx\Wechat($options);
 //    			$rere = $wx->sendTemplateMessage($data);
    
 //    		}
 //    		// 插入订单发货模板消息结束=================
 //    		$info['status'] = 1;
 //    		$info['msg'] = '操作成功！';
 //    	} else {
 //    		$info['status'] = 0;
 //    		$info['msg'] = '操作失败！';
 //    	}
 //    	$this->ajaxReturn($info);
 //    }
    
	// //完成订单
 //    public function ordersuccess()
 //    {
 //    	$id = I('id');
 //    	if (!$id) {
 //    		$info['status'] = 0;
 //    		$info['msg'] = '未正常获取ID数据！';
 //    		$this->ajaxReturn($info);
 //    	}
 //    	$m = M("ScoreOrder");
 //    	$map['id'] = $id;
 //    	$cache = $m->where($map)->find();
 //    	if (!$cache) {
 //    		$info['status'] = 0;
 //    		$info['msg'] = '操作失败！';
 //    		$this->ajaxReturn($info);
 //    	}
 //    	if ($cache['status'] != 2) {
 //    		$info['status'] = 0;
 //    		$info['msg'] = '操作失败！';
 //    		$this->ajaxReturn($info);
 //    	}
 //        $data['status'] = 3;
 //        $re = $m->save($data);
 //        if(FALSE !== $re) {
 //        	$this->ajaxReturn(array("status"=>"1","msg"=>"操作成功"));
 //        } else {
 //        	$this->ajaxReturn(array("status"=>"0","msg"=>"操作失败"));
 //        }
 //    }
    public function goodsStatus()
    {
        $m = M('Shop_goods');
        $now = I('status') ? 0 : 1;
        $map['id'] = I('id');
        $re = $m->where($map)->setField('status', $now);
        if ($re) {
            $info['status'] = 1;
            $info['msg'] = '设置成功!';
        } else {
            $info['status'] = 0;
            $info['msg'] = '设置失败!';
        }
        $this->ajaxReturn($info);
    }
    //发货快递
    // public function orderFhkd()
    // {
    // 	$map['id'] = I('id');
    // 	$cache = M('Score_order')->where($map)->find();
    // 	$this->assign('cache', $cache);
    // 	$mb = $this->fetch();
    // 	$this->ajaxReturn($mb);
    // }
    // public function orderFhkdSave()
    // {
    // 	$data = I('post.');
    // 	if (!$data) {
    // 		$info['status'] = 0;
    // 		$info['msg'] = '未正常获取数据！';
    // 	}
    // 	$data['changetime'] = time();
    // 	$re = M('Score_order')->where('id=' . $data['id'])->save($data);
    // 	if (FALSE !== $re) {
    // 		$info['status'] = 1;
    // 		$info['msg'] = '操作成功！';
    // 	} else {
    // 		$info['status'] = 0;
    // 		$info['msg'] = '操作失败！';
    // 	}
    // 	$this->ajaxReturn($info);
    // }

    public function log()
    {
    	//设置面包导航，主加载器请配置
    	$bread = array(
    			'0' => array(
    					'name' => '积分管理',
    					'url' => U('Admin/Score/index')
    			),
    			'1' => array(
    					'name' => '会员积分日志',
    					'url' => U('Admin/Score/log')
    			)
    	);
    	$this->assign('breadhtml', $this->getBread($bread));
    	//绑定搜索条件与分页
    	$m = M('Vip_log_credit');
    	$p = $_GET['p'] ? $_GET['p'] : 1;
    	$search = I('search') ? I('search') : '';
    	if ($search) {
    		$type = I('type', 0 , 'intval');
    		$vipid = I('vipid', 0 , 'intval');
    		$mobile = I('mobile') ? I('mobile') : '';
    		$name = I('name') ? I('name') : '';
    		if ($type) {
    			$map['L.type'] = array('eq', $type);
    			$this->assign('type', $type);
    		}
    		if ($vipid) {
    			$map['L.vipid'] = array('eq', $vipid);
    			$this->assign('vipid', $vipid);
    		}
    		if ($mobile) {
    			$map['V.mobile'] = array('eq', $mobile);
    			$this->assign('mobile', $mobile);
    		}
    		if ($name) {
    			$map['V.nickname|V.name'] = array('like', "%$name%");
    			$this->assign('name', $name);
    		}
    	}
    
    	$psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
    	$cache = $m->alias('as L')
    	->join('LEFT JOIN `'.C('DB_PREFIX').'vip` AS V ON V.id = L.vipid')
    	->where($map)
    	->field('L.*,V.nickname,V.name,V.mobile')
    	->order('L.id DESC')
    	->page($p, $psize)
    	->select();
    	$count = $m->alias('as L')
    	->join('LEFT JOIN `'.C('DB_PREFIX').'vip` AS V ON V.id = L.vipid')
    	->where($map)
    	->count();
    	$this->getPage($count, $psize, 'App-loader', '会员积分日志', 'App-search');
    	$this->assign('cache', $cache);
    	$this->display();
    }
}