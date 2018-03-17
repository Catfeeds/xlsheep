<?php
/**
 * Created by Eclipse.
 * User: mapeijian
 * Date: 16/7/12
 * Time: 18:31
 */

namespace Admin\Controller;

class FinanceController extends BaseController
{


	protected $bonusway = null;
	
    public function _initialize()
    {
        //你可以在此覆盖父类方法
        parent::_initialize();
        //初始化两个配置
        self::$CMS['shopset'] = M('Shop_set')->find();
        self::$CMS['vipset'] = M('Vip_set')->find();
        $this->bonusway = array('1' => '到期分红', '2' => '按月分红', '3' => '按天分红' ,'4' => '按月分红');
    }

    //CMS后台商城管理引导页
    public function index()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '商城首页',
                'url' => U('Admin/Finance/index'),
            ),
        );
        $this->display();
    }

    //CMS后台门店设置
    public function set()
    {
        $id = I('id');
        $m = M('Shop_set');
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '商城管理',
                'url' => U('Admin/Finance/index'),
            ),
            '1' => array(
                'name' => '商城设置',
                'url' => U('Admin/Finance/set'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //处理POST提交
        if (IS_POST) {
            $data = I('post.');
            $old = $m->where('id=' . $id)->find();
            if ($old) {
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
        $cache = $m->where('id=1')->find();
        $this->assign('cache', $cache);
        $this->display();
    }

    //CMS后台商城分组
    public function goods()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '首页',
                'url' => U('Admin/Index/index'),
            ),
            '1' => array(
                'name' => '商品管理',
                'url' => U('Admin/Finance/goods'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //绑定搜索条件与分页
        $m = M('Finance_goods');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $name = I('name') ? I('name') : '';
        if ($name) {
            $map['name'] = array('like', "%$name%");
            $this->assign('name', $name);
        }
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $cache = $m->where($map)->order('id DESC')->page($p, $psize)->select();
        $count = $m->where($map)->count();
        
        $mc = M('Finance_cate');
        $cates = getCate($mc);
        foreach($cache as $k => $v) {
        	$cache[$k]['cate'] = isset($cates[$v['cid']]) ? $cates[$v['cid']] : ''; 
        	$cache[$k]['cycle'] = $v['cycle'].'天';
        	$cache[$k]['rate'] = ($v['rate']*100).'%';
        	$cache[$k]['bonusway'] = isset($this->bonusway[$v['bonusway']]) ? $this->bonusway[$v['bonusway']] : '';
        	$cache[$k]['restrict'] = $cache[$k]['restrict'] == 0 ? '不限' : $cache[$k]['restrict'];
        }
        $this->getPage($count, $psize, 'App-loader', '商品管理', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }

    //CMS后台商品设置
    public function goodsSet()
    {
        $id = I('id');      
        $m = M('Finance_goods');
        //dump($m);
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '首页',
                'url' => U('Admin/Index/index'),
            ),
            '1' => array(
                'name' => '金融管理',
                'url' => U('Admin/Finance/goods'),
            ),
            '2' => array(
                'name' => '金融产品设置',
                'url' => $id ? U('Admin/Finance/goodsSet', array('id' => $id)) : U('Admin/Md/mdSet'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //处理POST提交
        if (IS_POST) {
            $data = I('post.');
            $data['adressid'] = substr($data['adressid'],0,strlen($data['adressid'])-1);
            $data['content'] = trimUE($data['content']);
            $data['rate'] = $data['rate']/100;
            $data['stime'] = strtotime($data['stime']);
            $data['endtime'] = strtotime($data['endtime']);
            $data['rtime'] = strtotime($data['rtime']);
            if($data['isproject']){
                $data['ismoney'] = 0;
            }
            if(!$data['party']){
                $data['party'] = '';
            }
            if ($id) {
            	$goods = $m->where('id='.$id)->find();
            	if($goods['status'] == 2 && $data['num']>0) {
            		$data['status'] = 1;
            	}
                $data['etime'] = time();
                $re = $m->save($data);
                if (FALSE !== $re) {
                    $info['status'] = 1;
                    $info['msg'] = '设置成功！';
                } else {
                    $info['status'] = 0;
                    $info['msg'] = '设置失败！';
                }
            } else {
                $data['ctime'] = $data['etime'] = time();
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
        //AppTree快速无限分类
        $field = array("id", "pid", "name", "concat(path,'-',id) as bpath");
        $cate = appTree(M('Finance_cate'), 0, $field);
        $this->assign('cate', $cate);
        //处理编辑界面
        if ($id) {
            $cache = $m->where('id=' . $id)->find();
            $this->assign('cache', $cache);
            if($cache['isobject']&&$cache[$adressid]){
                $zi['id'] = array('in',$cache['adressid']);
                $ziti = M('Since')->where($zi)->select();
                $this->assign('ziti', $ziti);
            }   
        }
        // var_dump($cache);exit;
        $video = M('Video')->select();
        $this->assign('video', $video);
        $shopset = self::$SHOP['set'];
        $this->assign('shopset', $shopset);
        $this->display();
    }

    public function goodsDel()
    {
        $id = $_GET['id']; //必须使用get方法
        $m = M('Finance_goods');
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
	
    //上下架
    public function goodsStatus()
    {
        $m = M('Finance_goods');
        $now = I('status') ? 0 : 1;
        $map['id'] = I('id');
        $re = $m->where($map)->setField('issj', $now);
        if ($re) {
            $info['status'] = 1;
            $info['msg'] = '设置成功!';
        } else {
            $info['status'] = 0;
            $info['msg'] = '设置失败!';
        }
        $this->ajaxReturn($info);
    }
    
    //获取分红日期(返回订单日期$month个月后的日期时间戳)
    public function getFhDate($orderdate, $month)
    {
    	$str = "+".$month." months";
    	$result_date = strtotime($str, $orderdate);
    	return $result_date;
    }
    //CMS后台金融商品分类
    public function cate()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '首页',
                'url' => U('Admin/Index/index'),
            ),
            '1' => array(
                'name' => '金融产品分类',
                'url' => U('Admin/Finance/cate'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //绑定搜索条件与分页
        $m = M('Finance_cate');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $name = I('name') ? I('name') : '';
        $map = array();
        if ($name) {
            $map['name'] = array('like', "%$name%");
            $this->assign('name', $name);
        }
        //AppTree快速无限分类
        $field = array("id", "pid", "lv", "name", "summary", "soncate", "concat(path,'-',id) as bpath");
        $cache = appTree($m, 0, $field, $map);
        $this->assign('cache', $cache);
        $this->display();
    }

    //CMS后台商城分类设置
    public function cateSet()
    {
        $id = I('id');
        $m = M('Finance_cate');
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '首页',
                'url' => U('Admin/Index/index'),
            ),
            '1' => array(
                'name' => '金融商品分类',
                'url' => U('Admin/Finance/cate'),
            ),
            '2' => array(
                'name' => '分类设置',
                'url' => $id ? U('Admin/Finance/cateSet', array('id' => $id)) : U('Admin/Shop/cateSet'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //处理POST提交
        if (IS_POST) {
            //die('aa');
            $data = I('post.');
            if ($id) {
                //保存时判断
                $old = $m->where('id=' . $id)->limit(1)->find();
                if ($old['pid'] != $data['pid']) {
                    $hasson = $m->where('pid=' . $id)->limit(1)->find();
                    if ($hasson) {
                        $info['status'] = 0;
                        $info['msg'] = '此分类有子分类，不可以移动！';
                        $this->ajaxReturn($info);
                    }
                }
                if ($data['pid']) {
                    //更新Path，强制处理
                    $path = setPath($m, $data['pid']);
                    $data['path'] = $path['path'];
                    $data['lv'] = $path['lv'];
                } else {
                    $data['path'] = 0;
                    $data['lv'] = 1;
                }
                $re = $m->save($data);
                if (FALSE !== $re) {
                    //更新新老父级，暂不做错误处理
                    if ($old['pid'] != $data['pid']) {
                        $re = setSoncate($m, $data['pid']);
                        $rold = setSoncate($m, $old['pid']);
                        $info['status'] = 1;
                        $info['msg'] = $old['pid'];
                        $this->ajaxReturn($info);
                    } else {
                        $re = setSoncate($m, $data['pid']);
                    }
                    $info['status'] = 1;
                    $info['msg'] = '设置成功！';
                } else {
                    $info['status'] = 0;
                    $info['msg'] = '设置失败！';
                }
            } else {
                if ($data['pid']) {
                    //更新父级，强制处理
                    $path = setPath($m, $data['pid']);
                    $data['path'] = $path['path'];
                    $data['lv'] = $path['lv'];
                } else {
                    $data['path'] = 0;
                    $data['lv'] = 1;
                }
                $re = $m->add($data);
                if ($re) {
                    //更新父级，暂不做错误处理
                    if ($data['pid']) {
                        $re = setSoncate($m, $data['pid']);
                    }
                    $info['status'] = 1;
                    $info['msg'] = '设置成功！';
                } else {
                    $info['status'] = 0;
                    $info['msg'] = '设置失败！';
                }
            }
            $this->ajaxReturn($info);
        }
        //处理编辑界面
        if ($id) {
            $cache = $m->where('id=' . $id)->find();
            $this->assign('cache', $cache);
        }
        //AppTree快速无限分类
        $field = array("id", "pid", "name", "sorts", "concat(path,'-',id) as bpath");
        $map = array('lv' => 1);
        $cate = appTree($m, 0, $field, $map);
        $this->assign('cate', $cate);
        $this->display();
    }

    public function cateDel()
    {
        $id = $_GET['id']; //必须使用get方法
        $m = M('Finance_cate');
        if (!$id) {
            $info['status'] = 0;
            $info['msg'] = 'ID不能为空!';
            $this->ajaxReturn($info);
        }
        //删除时判断
        $self = $m->where('id=' . $id)->limit(1)->find();
        $re = $m->delete($id);
        // 删除所有子类
        $tempList = split(',', $self['soncate']);
        foreach ($tempList as $k => $v) {
            $res = $m->delete($v);
        }
        if ($re) {
            //更新上级soncate
            if ($self['pid']) {
                $re = setSoncate($m, $self['pid']);
            }
            $info['status'] = 1;
            $info['msg'] = '删除成功!';
        } else {
            $info['status'] = 0;
            $info['msg'] = '删除失败!';
            $this->ajaxReturn($info);
        }
        $this->ajaxReturn($info);
    }

    //CMS后台金融订单
    public function order()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '首页',
                'url' => U('Admin/Index/index'),
            ),
            '1' => array(
                'name' => '金融产品订单管理',
                'url' => U('Admin/Finance/order'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        $isad = I('isad');
        $status = I('status');
        if ($status) {
            $map['status'] = $status;
        }
        $date =  I('date') ? I('date') : '';
        if ($date) {
        	$timeArr = explode(" - ", $date);
        	$map['ctime'] = array('between',array(strtotime($timeArr[0]),strtotime($timeArr[1])+60*60*24));
        	$this->assign('date', $date);
        }
        $this->assign('status', $status);
        //绑定搜索条件与分页
        $m = M('Finance_order');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $name = I('name') ? I('name') : '';
        if ($name) {
            $map['oid|vipmobile'] = array('eq', $name);
            $this->assign('name', $name);
        }
        if($isad){
            $map['address'] = array('neq','');
        }else{
            $map['address'] = array('eq','');
        }
        $this->assign('isad',$isad);
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $cache = $m->where($map)->page($p, $psize)->order('ctime desc')->select();
        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '商城订单', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }

    // Admin后台金融订单当天报表
    public function orderReport()
    {
        // Prepare Data
        $mgoods = M('Finance_goods');
        $morder = D('finance_order');
        $data = $morder->today();

        $goods = array();
        $temp = $mgoods->select();
        foreach ($temp as $k => $v) {
            $goods[$v['id']] = $v;
        }
        $this->assign('goods', $goods);
        $this->assign('cache', $data);
        $this->display();
    }

    //CMS后台Order详情
    public function orderDetail()
    {
        $id = I('id');
        $m = M('Finance_order');
        $mlog = M('Finance_order_log');
        $fhlog = M('Finance_fhlog');
        $mog = M('Finance_order_goods');
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '首页',
                'url' => U('Admin/Index/index'),
            ),
            '1' => array(
                'name' => '金融订单',
                'url' => U('Admin/Finance/order'),
            ),
            '2' => array(
                'name' => '订单详情',
                'url' => $id ? U('Admin/Finance/orderDetail', array('id' => $id)) : U('Admin/Finance/orderDetail'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        $cache = $m->where('id=' . $id)->find();
        //坠入vip
        $vip = M('vip')->where('id=' . $cache['vipid'])->find();
        $this->assign('vip', $vip);
        $cache['items'] = M('Finance_goods')->where('id='.$cache['goodsid'])->find();
        if($cache['address']){
            if($cache['delivery']=='express'){
                // $vipaddress = M('vip_address') -> where('id = '.$cache['address']) -> find();
                $cache['shouhuo'] = '快递';
                // $this->assign('address',$vipaddress);
            }else{
                $cache['shouhuo'] = '自提';
            }
        }
        $log = $mlog->where('oid=' . $cache['id'])->select();
        $fhlog = $fhlog->where('oid=' . $cache['id'])->select();
        $orderGoods = $mog->where('oid=' . $cache['id'])->select();
        $this->assign('orderGoods', $orderGoods);
        $this->assign('log', $log);
        $this->assign('fhlog', $fhlog);
        $this->assign('cache', $cache);
        $shopset = self::$SHOP['set'];
        $this->assign('shopset', $shopset);
        $this->display();
    }
    //实物订单
    public function orderFahuoList(){
    	//设置面包导航，主加载器请配置
    	$bread = array(
    			'0' => array(
    					'name' => '首页',
    					'url' => U('Admin/Index/index'),
    			),
    			'1' => array(
    					'name' => '金融管理',
    					'url' => U('Admin/Finance/order'),
    			),
    			'2' => array(
    					'name' => '实物发放',
    					'url' => U('Admin/Finance/orderFahuoList'),
    			),
    	);
    	$p = $_GET['p'] ? $_GET['p'] : 1;
    	$m = M('Finance_order');
        $map['address'] = array('neq','');
        $map['ispay'] = 1;
    	$map['status'] = array('neq',0);
    	$type = I('type', 0 ,'intval');
    	$this->assign('type', $type);
    	switch ($type) {
    		case '1'://待发货
    			$map['rtime'] = array('elt', time());
    			$map['fahuostatus'] = 0;
    			break;
    		case '2'://已发货
    			$map['fahuostatus'] = 1;
    			break;
    		default://全部
    			break;
    	}
    	$psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
    	$cache = $m->where($map)->page($p, $psize)->order('id desc')->select();
    	foreach($cache as $k => $v) {
    		if($v['rtime']<= time()) {
    			$cache[$k]['canfh'] = 1;
    		} else {
    			$cache[$k]['canfh'] = 0;
    		}   		
    	}
    	$count = $m->where($map)->count();
    	$this->getPage($count, $psize, 'App-loader', '实物发货订单', 'App-search');
    	$this->assign('cache', $cache);
    	$this->display();
    }
    //发货快递
    public function orderFhkd()
    {
    	$map['id'] = I('id');
    	$cache = M('Finance_order')->where($map)->find();
    	$this->assign('cache', $cache);
    	$express = D('Express');
    	$express = $express->select();
    	$this->assign("express", $express);
    	$mb = $this->fetch();
    	$this->ajaxReturn($mb);
    }
    
    public function orderFhkdSave()
    {
    	$data = I('post.');
    	if (!$data) {
    		$info['status'] = 0;
    		$info['msg'] = '未正常获取数据！';
    		$this->ajaxReturn($info);
    	}
    	$order = M('Finance_order')->where('id=' . $data['id'])->find();
    	if(empty($order)) {
    		$info['status'] = 0;
    		$info['msg'] = '订单不存在！';
    		$this->ajaxReturn($info);
    	}
    	if($order['isobject'] == 0) {
    		$info['status'] = 0;
    		$info['msg'] = '虚拟订单不能发货！';
    		$this->ajaxReturn($info);
    	}
    	if($order['rtime'] > time()) {
    		$info['status'] = 0;
    		$info['msg'] = '未到发货时间！';
    		$this->ajaxReturn($info);
    	}
    	$data['fahuostatus'] = 1;
    	$data['changetime'] = time();
    	$re = M('Finance_order')->where('id=' . $data['id'])->save($data);
    	if (FALSE !== $re) {
    		//更新订单状态
    		M('Finance_order')->where('id=' . $data['id'])->setField('status', 4);
    		// 插入订单发货模板消息=====================
    		$vip = M('vip')->where(array('id' => $order['vipid']))->find();
    		$templateidshort = 'OPENTM201541214';
    		$dwechat = D('Wechat');
    		$templateid = $dwechat->getTemplateId($templateidshort);
            $order=array();
            $order = M('Finance_order')->where('id = ' . $data['id'])->find();
            // $address = M('vip_address')->where('id='.$order['adressid'])->find();
            if($order['delivery']=='express'){
                $remark='顾客您的订单已由快递揽单请保持手机畅通，留意签收。';
            }else{
                $remark='顾客您的订单已农牧源镖局押送至指定地点请您安排提货。';
                // $address = $order['adressid'];
            }
    		if ($templateid) { // 存在才可以发送模板消息
    			$data = array();
    			$data['touser'] = $vip['openid'];
    			$data['template_id'] = $templateid;
    			$data['topcolor'] = "#0000FF";
    			$data['data'] = array(
    					'first' => array('value' => '您好，您的众筹创业项目成果已发货'),
    					'keyword1' => array('value' => $order['oid']),
    					'keyword2' => array('value' => $order['fahuokd']),
                        'keyword3' => array('value' => $order['fahuokdnum']),
                        'keyword4' => array('value' => $order['payprice']),'元',
    					'keyword5' => array('value' => $order['address']),
    					'remark' => array('value' => $remark)
    			);
    			$options['appid'] = self::$SYS['set']['wxappid'];
    			$options['appsecret'] = self::$SYS['set']['wxappsecret'];
    		
    			$wx = new \Util\Wx\Wechat($options);
    			$rere = $wx->sendTemplateMessage($data);
    		
    		}
    		// 插入订单发货模板消息结束=================
    		$info['status'] = 1;
    		$info['msg'] = '操作成功！';
    	} else {
    		$info['status'] = 0;
    		$info['msg'] = '操作失败！';
    	}
    	$this->ajaxReturn($info);
    }
    //订单关闭
    public function orderClose()
    {
        $map['id'] = I('id');
        $cache = M('Finance_order')->where($map)->find();
        $this->assign('cache', $cache);
        $mb = $this->fetch();
        $this->ajaxReturn($mb);
    }

    public function orderCloseSave()
    {
        $data = I('post.');
        if (!$data) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取数据！';
        }
        $m = M('Finance_order');
        $mlog = M('Finance_order_log');
        $mslog = M('Finance_order_syslog');
        $cache = $m->where('id=' . $data['id'])->find();
        switch ($cache['status']) {
            case '1':
                $data['status'] = 6;
                $data['closetime'] = time();
                $re = $m->where('id=' . $data['id'])->save($data);
                if (FALSE !== $re) {
                    //前端LOG
                    $log['oid'] = $cache['id'];
                    $log['msg'] = '未支付订单关闭成功';
                    $log['ctime'] = time();
                    $rlog = $mlog->add($log);
                    //后端LOG
                    $log['type'] = 6;
                    $log['paytype'] = $cache['paytype'];
                    $rslog = $mslog->add($log);

                    $info['status'] = 1;
                    $info['msg'] = '关闭未支付订单成功！';
                } else {
                    //前端LOG
                    $log['oid'] = $cache['id'];
                    $log['msg'] = '未支付订单关闭失败';
                    $log['ctime'] = time();
                    $rlog = $mlog->add($log);
                    //后端LOG
                    $log['type'] = -1;
                    $log['paytype'] = $cache['paytype'];
                    $rslog = $mslog->add($log);
                    $info['status'] = 0;
                    $info['msg'] = '关闭未支付订单失败！';
                }
                $this->ajaxReturn($info);
                break;
            case '2':
                //已支付订单跳转到这里处理
                $this->orderClosePay($cache, $data);
                break;
            default:
                $info['status'] = 0;
                $info['msg'] = '只有未付款和已付款订单可以关闭!';
                $this->ajaxReturn($info);
                break;
        }

    }

    //已支付订单退款
    public function orderClosePay($cache, $data)
    {
        //关闭订单时不再处理库存
        $m = M('finance_order');
        $mvip = M('Vip');
        $mlog = M('Finance_order_log');
        $mslog = M('Finance_order_syslog');
        if (!$cache['ispay']) {
            $info['status'] = 0;
            $info['msg'] = '订单支付状态异常！请重试或联系技术！';
            $this->ajaxReturn($info);
        }
        //抓取会员数据
        $vip = $mvip->where('id=' . $cache['vipid'])->find();
        if (!$vip) {
            $info['status'] = 0;
            $info['msg'] = '会员数据获取异常！请重试或联系技术！';
            $this->ajaxReturn($info);
        }
        //支付金额
        $payprice = $cache['payprice'];
        //全部退款至余额
        $data['status'] = 6;
        $data['closetime'] = time();
        $re = $m->where('id=' . $cache['id'])->save($data);
        if (FALSE !== $re) {
            $log['oid'] = $cache['id'];
            $log['msg'] = '订单关闭-成功';
            $log['ctime'] = time();
            $rlog = $mlog->add($log);
            $info['status'] = 1;
            $info['msg'] = '关闭订单成功！';
            if ($cache['ispay']) {
                $mm = $vip['money'] + $payprice;
                $rvip = $mvip->where('id=' . $cache['vipid'])->setField('money', $mm);
                if ($rvip) {
                    //前端LOG
                    $log['oid'] = $cache['id'];
                    $log['msg'] = '自动退款' . $payprice . '元至用户余额-成功';
                    $log['ctime'] = time();
                    $rlog = $mlog->add($log);
                    $log['type'] = 6;
                    $log['paytype'] = $cache['paytype'];
                    $rslog = $mslog->add($log);
                    //后端LOG
                    $info['status'] = 1;
                    $info['msg'] = '关闭订单成功！自动退款' . $payprice . '元至用户余额成功!';
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
                    $info['status'] = 1;
                    $info['msg'] = '关闭订单成功！自动退款' . $payprice . '元至用户余额失败!请联系技术！';
                }
            }

        } else {
            $info['status'] = 0;
            $info['msg'] = '关闭订单失败！请重新尝试!';
        }
        $this->ajaxReturn($info);
    }

    
    public function orderGoodsSet() 
    {
    	$map['id'] = I('id');
    	$cache = M('Finance_order_goods')->where($map)->find();
    	if(!empty($cache)) {
    		$insurance = M('Finance_insurance')->where('ogid='.$cache['id'])->find();
    		$this->assign('insurance', $insurance);
    	}
    	$this->assign('shopset', self::$SHOP['set']);
    	$this->assign('cache', $cache);
    	$mb = $this->fetch();
    	$this->ajaxReturn($mb);
    }
    public function orderGoodsSave()
    {
    	$data = I('post.');
    	if (!$data) {
    		$info['status'] = 0;
    		$info['msg'] = '未正常获取数据！';
    	}
    	if($data['url']) {
    		$m = M('Finance_insurance');
    		$insurance = $m->where('ogid='.$data['id'])->find();
    		$i_data['company'] = $data['company'];
    		$i_data['no'] = $data['insurance_no'];
    		$i_data['url'] = $data['url'];
    		if(empty($insurance)) {
    			$i_data['ogid'] = $data['id'];
    			$i_data['ctime'] = time();
    			$m->add($i_data);
    		} else {
    			$i_data['etime'] = time();
    			$m->where('ogid='.$data['id'])->save($i_data);
    		}
    	}
    	$data['insurance'] = $data['url'];
    	$data['etime'] = time();
    	$re = M('Finance_order_goods')->where('id=' . $data['id'])->save($data);
    	if (FALSE !== $re) {
    		$info['status'] = 1;
    		$info['msg'] = '操作成功！';
    	} else {
    		$info['status'] = 0;
    		$info['msg'] = '操作失败！';
    	}
    	$this->ajaxReturn($info);
    }
    public function orderContractSave()
    {
    	$data = I('post.');
    	if (!$data) {
    		$info['status'] = 0;
    		$info['msg'] = '未正常获取数据！';
    	}
    	$order = M('Finance_order')->where('id=' . $data['id'])->find();
    	if($order) {
    		$re = M('Finance_order')->where('id=' . $data['id'])->setField('contract', $data['contract']);
    		if (FALSE !== $re) {
    			$info['status'] = 1;
    			$info['msg'] = '操作成功！';
    		} else {
    			$info['status'] = 0;
    			$info['msg'] = '操作失败！';
    		}
    	} else {
    		$info['status'] = 0;
    		$info['msg'] = '订单不存在！';
    	}
    	
    	$this->ajaxReturn($info);
    }
    public function fenhong()
    {
    	$map['id'] = I('id');
    	$cache = M('Finance_fhlog')->where($map)->find();
    	$this->assign('cache', $cache);
    	$mb = $this->fetch();
    	$this->ajaxReturn($mb);
    }
    public function fhSave()
    {
    	$data = I('post.');
    	if (!$data) {
    		$info['status'] = 0;
    		$info['msg'] = '未正常获取数据！';
    	}
    	$data['etime'] = time();
    	$m_fhlog = M('Finance_fhlog');
    	$fhlog = $m_fhlog->where(array('id'=> $data['id'],'type'=>1))->find();
    	if(!$fhlog) {
    		$info['status'] = 0;
    		$info['msg'] = '数据异常1！';
    	}
    	if($fhlog['status'] != 0) {
    		$info['status'] = 0;
    		$info['msg'] = '数据异常2！';
    	}
    	$m_order = M('Finance_order');
    	$order = $m_order->where('id=' . $fhlog['oid'])->find();
    	if(!$order) {
    		$info['status'] = 0;
    		$info['msg'] = '数据异常3！';
    	}
    	$m_goods = M('Finance_goods');
    	$goods = $m_goods->where('id=' . $fhlog['qid'])->find();
    	if(!$goods) {
    		$info['status'] = 0;
    		$info['msg'] = '数据异常4！';
    	}
    	$mvip = M('Vip');
    	$vip = $mvip->where('id=' . $fhlog['to'])->find();
    	if(!$vip) {
    		$info['status'] = 0;
    		$info['msg'] = '数据异常5！';
    	}
    	$data['rate'] = $data['rate']/100;
    	//到期分红
    	if($goods['bonusway'] == 1) {
    		//预期分红
    		$data['money'] = round($order['totalprice']*$data['rate']/12*$goods['cycle'], 2);
    		$bonusway = '到期分红';
    	} elseif($goods['bonusway'] == 2) {
    		//按月分红
    		if($goods['cycle'] > 0) {
    			$data['money'] = round($order['totalprice']*$data['rate']/12, 2);
    		}
    		$bonusway = '按月分红';
    	} elseif($goods['bonusway'] == 3) {
    		//按天分红
    		if($goods['cycle'] > 0 && $goods['day'] > 0) {
    			$data['money'] = round($order['totalprice']*$data['rate']/365*$goods['day'], 2);
    		}
    		$bonusway = '按天分红，每隔'.$goods['day'].'天分红一次';
    	}

    	$vip['money'] = $vip['money'] + $data['money'];
    	$rvip = $mvip->save($vip);
    	if($rvip) {
	    	$data['status'] = 1;
	    	$re = $m_fhlog->where(array('id'=> $data['id'],'type'=>1))->save($data);
	    	if (FALSE !== $re) {
	    		//资金流水记录
	    		$mlog = M('Vip_log_money');
	    		$flow['vipid'] = $vip['id'];
	    		$flow['openid'] = $vip['openid'];
	    		$flow['nickname'] = $vip['nickname'];
	    		$flow['mobile'] = $vip['mobile'];
	    		$flow['money'] = $data['money'];
	    		$flow['paytype'] = '';
	    		$flow['balance'] = $vip['money'];
	    		$flow['type'] = 5;
	    		$flow['oid'] = $order['oid'];
	    		$flow['ctime'] = time();
	    		$flow['remark'] = '第'.$goods['id'].'期：'.$goods['name'].'收益发放('.$bonusway.')';
	    		$rflow = $mlog->add($flow);
	    		
	    		//检查订单是否已完成
	    		$order_fh = $m_fhlog->where(array('oid'=>$order['id'], 'status'=>0))->select();
	    		if(empty($order_fh)) {
	    			//标记订单为完成状态
	    			$m_order->where('id=' . $order['id'])->setField(array('status'=> 3,'etime'=>time()));
	    		}
	    		//检查同期产品是否已返回全部款项
	    		$all_fh = $m_fhlog->where(array('oid'=>$order['id'],'type'=>1, 'status'=>0))->select();
	    		if(empty($all_fh)) {
	    			//返还本金
	    			$return_money = $m_fhlog->where(array('oid'=>$order['id'],'type'=>2, 'status'=>0))->find();
	    			if($return_money) {
	    				$vip = $mvip->where('id=' . $return_money['to'])->find();
	    				$vip['money'] = $vip['money'] + $return_money['money'];
	    				$rvip = $mvip->save($vip);
	    				if($rvip) {
	    					$return_money['status'] = 1;
	    					$m_fhlog->where('id=' . $return_money['id'])->save($return_money);	    					
	    					//资金流水记录
	    					$flow['vipid'] = $vip['id'];
	    					$flow['openid'] = $vip['openid'];
	    					$flow['nickname'] = $vip['nickname'];
	    					$flow['mobile'] = $vip['mobile'];
	    					$flow['money'] = $return_money['money'];
	    					$flow['paytype'] = '';
	    					$flow['balance'] = $vip['money'];
	    					$flow['type'] = 6;
	    					$flow['oid'] = $order['oid'];
	    					$flow['ctime'] = time();
	    					$flow['remark'] = '第'.$goods['id'].'期：'.$goods['name'].'本金返还('.$bonusway.')';
	    					$rflow = $mlog->add($flow);
	    				}	    			
	    			}
	    			if($goods['status'] == 2) {
		    			//标记产品为到期返回全部款项状态
		    			$m_goods->where('id=' . $goods['id'])->setField(array('status'=> 3,'etime'=>time()));
	    			}
	    		}
	    		$info['status'] = 1;
	    		$info['msg'] = '操作成功！';
	    	} else {
	    		$info['status'] = 0;
	    		$info['msg'] = '写入分红日志失败！';
	    	}
    	} else {
	    		$info['status'] = 0;
	    		$info['msg'] = '操作失败！';
	    }
    	$this->ajaxReturn($info);
    }

    //回报管理页面
    public function huibaos()
    {
        $id = I('get.id');
        $info = M('Finance_huibao')->where('zid='.$id)->select();

        $this->assign('info',$info);
        $this->display();
    }
    //回报管理设置
    public function huibaoSet()
    {
        $id = I('get.id');
        if($id){
            $info = M('Finance_huibao')->where('id='.$id)->find();
            $this->assign('info',$info);
            $this->display();
        }else{
            return;
        }
    }

    //添加回报
    public function huibaoAdd()
    {
        $this->display();
    }
    //添加回报提交
    public function huibaoPost()
    {
        $id = I('post.id');
        if($id){
            $data = $_POST;
            $re = M('Finance_huibao')->where('id='.$id)->save($data);
            if ($re) {
                $info['status'] = 1;
                $info['msg'] = '修改成功！';
            } else {
                $info['status'] = 0;
                $info['msg'] = '修改失败！';
            }
        }else{
            $data = $_POST;
            $re = M('Finance_huibao')->add($data);
            if ($re) {
                $info['status'] = 1;
                $info['msg'] = '添加成功！';
            } else {
                $info['status'] = 0;
                $info['msg'] = '添加失败！';
            }
        }
        $this->ajaxReturn($info);
    }
    //删除回报
    public function huibaoDel()
    {
        $id = $_GET['id']; //必须使用get方法
        $m = M('Finance_huibao');
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
    //自提点设置
    public function adSet(){
        $id = I('id');
        $m = M('Finance_goods');
         if (IS_POST) {
            $data = I('post.');
            $data['adressid'] = substr($data['adressid'],0,strlen($data['adressid'])-1);
            if ($id) {
                $data['etime'] = time();
                $re = $m->save($data);
                if (FALSE !== $re) {
                    $info['status'] = 1;
                    $info['msg'] = '设置成功！';
                } else {
                    $info['status'] = 0;
                    $info['msg'] = '设置失败！';
                }
            }else{
                $info['status'] = 0;
                $info['msg'] = '非法操作';
            }
            $this->ajaxReturn($info);
        }
        if ($id) {
            $cache = $m->where('id=' . $id)->find();
            $this->assign('cache', $cache);
        }
        $province = M('Since')->distinct('province')->field('province')->select();
        $ziti = array();
        foreach($province as $k => $v) {
        	$ziti['province'][$k]['id'] = $v['province'];
        	$cityids = get_region_child_ids($v['province']);
        	if($cityids) {
        		$city = M('Since')->where(array('city'=>array('in', $cityids)))->distinct('city')->field('city')->select();
        	} else {
        		$city = array();
        	}
        	foreach($city as $kk => $vv) {
        		$ziti['province'][$k]['city'][$kk]['id']= $vv['city'];
        		$districtids = get_region_child_ids($vv['city']);
        		if($districtids) {
        			$district = M('Since')->where(array('district'=>array('in', $districtids)))->distinct('district')->field('district')->select();
        			foreach($district as $kkk => $vvv) {
        				$ziti['province'][$k]['city'][$kk]['district'][$kkk]['id'] = $vvv['district'];
        				$ziti['province'][$k]['city'][$kk]['district'][$kkk]['sincelist'] = M('Since')->where('district='.$vvv['district'])->select();
        			}
        		} else {
        			$ziti['province'][$k]['city'][$kk]['district']= array();
        		}
        	}
        }
        //echo '<pre>';print_r($ziti);echo '</pre>';die;
        $this->assign('ziti', $ziti['province']);
        $region_list = get_region_list();
        $this->assign('region_list', $region_list);
        $this->display();
    }

     //实物订单
    public function clorder(){
        //设置面包导航，主加载器请配置
        $bread = array(
                '0' => array(
                        'name' => '首页',
                        'url' => U('Admin/Index/index'),
                ),
                '1' => array(
                        'name' => '金融管理',
                        'url' => U('Admin/Finance/order'),
                ),
                '2' => array(
                        'name' => '自提核销',
                        'url' => U('Admin/Finance/clorder'),
                ),
        );
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $m = M('Finance_order');
        $map['address'] = array('neq','');
        $map['delivery'] = 'since';
        $map['ispay'] = 1;
        $map['rtime'] = array('elt', time());
        $type = I('type', 0 ,'intval');
        $this->assign('type', $type);
        $name = I('name') ? I('name') : '';
        if ($name) {
            //订单号邦定
            $map['oid'] = array('like', "%$name%");
            $this->assign('name', $name);
        }
        switch ($type) {
            case '1'://待自提
                $map['status'] = 2;
                break;
            case '2'://已发货
                $map['status'] = 3;
                break;
            default://全部
                break;
        }
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $cache = $m->where($map)->page($p, $psize)->order('id desc')->select();
        foreach($cache as $k => $v) {
            if($v['rtime']<= time()) {
                $cache[$k]['canfh'] = 1;
            } else {
                $cache[$k]['canfh'] = 0;
            }           
        }

        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '自提核销', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }

    public function clorSave(){
        $id = $_GET['id'];
        $m = M('Finance_order');
        if (!$id) {
            $info['status'] = 0;
            $info['msg'] = 'ID不能为空!';
            $this->ajaxReturn($info);
        }
        $re = $m->where('id = '.$id)->setField('status',3);
        if ($re) {
            $info['status'] = 1;
            $info['msg'] = '核销成功!';
        } else {
            $info['status'] = 0;
            $info['msg'] = '核销失败!';
        }
        $this->ajaxReturn($info);
    }

    public function orderExport()
    {
        $status = I('status', 0 ,'intval');
        $date =  I('date') ? I('date') : '';
        $isad = I('isad', 0 ,'intval');
        if ($date) {
        	$timeArr = explode(" - ", $date);
        	$map['ctime'] = array('between',array(strtotime($timeArr[0]),strtotime($timeArr[1])+60*60*24));
        	$this->assign('date', $date);
        }
        $name = I('name') ? I('name') : '';
        if ($name) {
        	$map['oid|vipmobile'] = array('eq', $name);
        }
        $map['contract'] = array('neq','');
        if($status > 0) {
        	$map['status'] = $status;
        }
        switch ($status) {
            case 2:
                $tt = "已付款";
                break;
            case 3:
                $tt = "已完成";
                break; 
            case 4:
                $tt = "已发货";
                break;      
        }
        if($isad){
            $map['address'] = array('neq','');
        }else{
            $map['address'] = array('eq','');
        }
        $data = M('Finance_order')->where($map)->select();
        // $data = M('Finance_order')->alias('as O')->join('LEFT JOIN `'.C('DB_PREFIX').'finance_huibao` AS G ON O.tcid = G.id')
        // ->where($map)->field('O.*,G.summary')->order('paytime desc')->select();
        // var_dump($data);exit;
        //die();
        foreach ($data as $k => $v) {
            //过滤字段
            unset($data[$k]['djqid']);
            unset($data[$k]['ispay']);
            unset($data[$k]['vipopenid']);
            unset($data[$k]['etime']);
            unset($data[$k]['rtime']);
            unset($data[$k]['fenhong']);
            unset($data[$k]['fahuostatus']);
            unset($data[$k]['fahuokd']);
            unset($data[$k]['fahuokdcode']);
            unset($data[$k]['fahuokdnum']);
            unset($data[$k]['changetime']);
            $data[$k]['status'] = $tt;
            if($v['address']){
                $data[$k]['isobject'] = '实物';
                if($v['delivery']=='since'){
                    $data[$k]['way']='自提';
                }else{
                    $data[$k]['way']='快递';
                }
            }else{
                unset($data[$k]['name']);
                unset($data[$k]['mobile']);
                unset($data[$k]['address']);
                $data[$k]['isobject'] = '分红';
            }
            $data[$k]['tcname'] = M('finance_huibao')->where('id = '.$v['tcid']) ->getField('summary');
            $data[$k]['goodsname'] = M('finance_goods')->where('id = '.$v['goodsid']) ->getField('name');
            $data[$k]['ctime'] = date('Y-m-d H:i:s', $v['ctime']);
            $data[$k]['paytime'] = $v['paytime'] ? date('Y-m-d H:i:s', $v['paytime']) : '无';
            unset($data[$k]['delivery']);
            unset($data[$k]['tcid']);
            unset($data[$k]['goodsid']);
        }
        //dump($data);
        //die();
        
        if($info['isad']){
            $title = array('订单ID', '订单编号', '商品总价', '商品数量', '支付价格', '支付类型', '支付时间', '用户ID', '用户昵称', '用户手机', '创建时间', '状态', '合同ID', '收获方式','收件人姓名', '收件人手机', '收货地址', '订单留言', '收货方式', '选择套餐名称', '购买商品名称');
        }else{
            $title = array('订单ID', '订单编号', '商品总价', '商品数量', '支付价格', '支付类型', '支付时间', '用户ID', '用户昵称', '用户手机', '创建时间', '状态', '合同ID', '收获方式', '订单留言', '选择套餐名称', '购买商品名称');
        }
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
    
    //推送电影模板消息
    public function pushGoodsMsg() {
    	if(IS_AJAX) {
    		$id = I('id', 0, 'intval');
    		if($id <= 0) {
    			$info['status'] = 0;
    			$info['msg'] = '缺少参数!';
    			$this->ajaxReturn($info);
    		}
    		$goods = M('Finance_goods')->where('id='.$id)->find();
    		if(empty($goods)) {
    			$info['status'] = 0;
    			$info['msg'] = '产品不存在!';
    			$this->ajaxReturn($info);
    		}
    		$msg = M('Finance_tplmsg')->where('goodsid='.$id .' AND status=0')->count();
    		if($msg>0) {
    			$info['status'] = 0;
    			$info['msg'] = '该产品有'.$msg.'条消息未发送，请耐心等待!';
    			$this->ajaxReturn($info);
    		}
    		if($goods['ismoney'] && !$goods['isobject']) {
    			$type = '分红';
    		}
    		if(!$goods['ismoney'] && $goods['isobject']) {
    			$type = '实物';
    		}
    		if($goods['ismoney'] && $goods['isobject']) {
    			$type = '';
    		}
    		$count = 0;
    		$vipdata = M('vip')->where('subscribe=1')->field('id,openid')->select();
    		foreach ($vipdata as $key => $value) {
    			$data['vipid'] = $value['id'];
    			$data['openid'] = $value['openid'];
    			$data['goodsid'] = $goods['id'];
    			$data['first'] =  $type.'众筹项目-《'.$goods['name'].'项目》发布成功';
    			$data['keyword1'] = $goods['name'];
    			$data['keyword2'] = $goods['price'].'元';
    			$data['keyword3'] = '农牧源';
    			$data['keyword4'] = date("Y-m-d H:i:s", $goods['ctime']);
    			$data['remark'] = '感谢您的支持，点击查看详情！';
    			$data['status'] = 0;
    			$data['ctime'] = time();
    			$re = M('Finance_tplmsg')->add($data);
    			if(FALSE !== $re) {
    				$count++;
    			}
    		}
    		if($count>0) {
    			$info['status'] = 1;
    			$info['msg'] = '成功加入发送队列，系统将在1分钟内分批发送消息'.$count.'给位用户';
    			$this->ajaxReturn($info);
    		} else {
    			$info['status'] = 0;
    			$info['msg'] = '推送失败！';
    			$this->ajaxReturn($info);
    		}
    	}
    	
    }
}