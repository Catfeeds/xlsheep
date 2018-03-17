<?php
// +----------------------------------------------------------------------
// | 用户后台基础类--CMS分组日志管理类
// +----------------------------------------------------------------------
// | AppCMS V1.0 Beta
// +----------------------------------------------------------------------
// | Copyright (c) 2014 http://www.AppCMS.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: App <2094157689@qq.com>
// +----------------------------------------------------------------------
namespace Admin\Controller;

class LogController extends BaseController
{

    public function _initialize()
    {
        //你可以在此覆盖父类方法
        parent::_initialize();
    }

    //CMS后台日志管理引导页
    public function index()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '日志管理',
                'url' => U('Admin/Log/index')
            )
        );
        $this->display();
    }


    //CMS后台日志-会员
    public function vip()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '日志管理',
                'url' => U('Admin/Log/index')
            ),
            '1' => array(
                'name' => '会员日志',
                'url' => U('Admin/Log/vip')
            )
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //绑定搜索条件与分页
        $m = M('Vip_log');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $name = I('name') ? I('name') : '';
        if ($name) {
            $map['vipid'] = array('like', "%$name%");
            $this->assign('name', $name);
        }
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $cache = $m->where($map)->page($p, $psize)->order('ctime desc')->select();
        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '会员日志', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }

    //CMS后台日志-订单
    public function order()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '日志管理',
                'url' => U('Admin/Log/index')
            ),
            '1' => array(
                'name' => '会员日志',
                'url' => U('Admin/Log/order')
            )
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //绑定搜索条件与分页
        $m = M('Shop_order_syslog');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $name = I('name') ? I('name') : '';
        if ($name) {
            $map['vipid'] = array('like', "%$name%");
            $this->assign('name', $name);
        }
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $cache = $m->where($map)->page($p, $psize)->order('ctime desc')->select();
        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '订单日志', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }

    //CMS后台日志-分销
    public function fx()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '日志管理',
                'url' => U('Admin/Log/index')
            ),
            '1' => array(
                'name' => '会员日志',
                'url' => U('Admin/Log/order')
            )
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //绑定搜索条件与分页
        $m = M('Fx_syslog');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $name = I('name') ? I('name') : '';
        if ($name) {
            $map['to'] = array('like', "%$name%");
            $this->assign('name', $name);
        }
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $cache = $m->where($map)->page($p, $psize)->order('ctime desc')->select();
        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '分销日志', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }

    //CMS后台日志-金融产品订单日志
    public function finance()
    {
    	//设置面包导航，主加载器请配置
    	$bread = array(
    			'0' => array(
    					'name' => '日志管理',
    					'url' => U('Admin/Log/index')
    			),
    			'1' => array(
    					'name' => '金融产品订单日志',
    					'url' => U('Admin/Log/finance')
    			)
    	);
    	$this->assign('breadhtml', $this->getBread($bread));
    	//绑定搜索条件与分页
    	$m = M('Finance_order_syslog');
    	$p = $_GET['p'] ? $_GET['p'] : 1;
    	$name = I('name') ? I('name') : '';
    	if ($name) {
    		$map['to'] = array('like', "%$name%");
    		$this->assign('name', $name);
    	}
    	$psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
    	$cache = $m->where($map)->page($p, $psize)->order('ctime desc')->select();
    	$count = $m->where($map)->count();
    	$this->getPage($count, $psize, 'App-loader', '金融产品订单日志', 'App-search');
    	$this->assign('cache', $cache);
    	$this->display();
    }
    
    //CMS后台日志-金融产品分红日志
    public function fh()
    {
    	//设置面包导航，主加载器请配置
    	$bread = array(
    			'0' => array(
    					'name' => '日志管理',
    					'url' => U('Admin/Log/index')
    			),
    			'1' => array(
    					'name' => '金融产品分红日志',
    					'url' => U('Admin/Log/finance')
    			)
    	);
    	$this->assign('breadhtml', $this->getBread($bread));
    	//绑定搜索条件与分页
    	$m = M('Finance_fhlog');
    	$p = $_GET['p'] ? $_GET['p'] : 1;
    	$name = I('name') ? I('name') : '';
    	$type = I('type') ? I('type') : '';
    	if($type == 1) {
    		$map['status'] = 0;
    		$map['getdate'] = array('elt', time());
    	}
    	$this->assign('type', $type);
    	if ($name) {
    		$map['to'] = array('eq', $name);
    		$this->assign('name', $name);
    	}
    	$map['type'] = 1;
    	$count = $m->where($map)->count();
    	$psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
    	$cache = $m->where($map)->page($p, $psize)->order('ctime desc')->select();
    	foreach($cache as $k => $v) {
    		if($v['getdate'] > time() && $v['status'] == 0) {
    			$cache[$k]['status'] = -1;//未到结算时间
    		} 
    	}
    	$this->getPage($count, $psize, 'App-loader', '金融产品分红日志', 'App-search');
    	$this->assign('cache', $cache);
    	$this->display();
    }
    
    //CMS后台日志-金融产品分红自动发放日志
    public function autofh()
    {
    	//设置面包导航，主加载器请配置
    	$bread = array(
    			'0' => array(
    					'name' => '日志管理',
    					'url' => U('Admin/Log/index')
    			),
    			'1' => array(
    					'name' => '金融产品自动发放分红日志',
    					'url' => U('Admin/Log/finance')
    			)
    	);
    	$this->assign('breadhtml', $this->getBread($bread));
    	//绑定搜索条件与分页
    	$m = M('Finance_fhlog');
    	$p = $_GET['p'] ? $_GET['p'] : 1;
    	$name = I('name') ? I('name') : '';
    	$map['status'] = 1;
    	if ($name) {
    		$map['to'] = array('eq', $name);
    		$this->assign('name', $name);
    	}
    	$map['type'] = 1;
    	$map['status'] = 1;
    	$count = $m->where($map)->count();
    	$psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
    	$cache = $m->where($map)->page($p, $psize)->order('etime desc')->select();
    	$this->getPage($count, $psize, 'App-loader', '金融产品自动发放分红日志', 'App-search');
    	$this->assign('cache', $cache);
    	$this->display();
    }

    //CMS后台日志-金融产品订单日志
    public function money()
    {
    	//设置面包导航，主加载器请配置
    	$bread = array(
    			'0' => array(
    					'name' => '日志管理',
    					'url' => U('Admin/Log/index')
    			),
    			'1' => array(
    					'name' => '会员资金流水日志',
    					'url' => U('Admin/Log/money')
    			)
    	);
    	$this->assign('breadhtml', $this->getBread($bread));
    	//绑定搜索条件与分页
    	$m = M('Vip_log_money');
    	$p = $_GET['p'] ? $_GET['p'] : 1;
    	$search = I('search') ? I('search') : '';
    	if ($search) {
    		$map['nickname|mobile'] = array('like', "%$search%");
    		$this->assign('search', $search);
    	}
    	$psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
    	$cache = $m->where($map)->page($p, $psize)->order('ctime desc')->select();
    	$count = $m->where($map)->count();
    	$this->getPage($count, $psize, 'App-loader', '会员资金流水日志', 'App-search');
    	$this->assign('cache', $cache);
    	$this->display();
    }
    
    //CMS后台日志-推广
    public function tj()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '日志管理',
                'url' => U('Admin/Log/index')
            ),
            '1' => array(
                'name' => '推广日志',
                'url' => U('Admin/Log/tj')
            )
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //绑定搜索条件与分页
        $m = M('Fx_log_tj');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $name = I('name') ? I('name') : '';
        if ($name) {
            $map['vipid'] = array('like', "%$name%");
            $this->assign('name', $name);
        }
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $cache = $m->where($map)->page($p, $psize)->order('ctime desc')->select();
        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '推广日志', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }


}