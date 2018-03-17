<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Admin\Controller;

class ExpressController extends BaseController
{

	public function _initialize()
	{
		//你可以在此覆盖父类方法
		parent::_initialize();
	}

    public function index()
    {
    	$bread = array(
    			'0' => array(
    					'name' => '系统设置管理',
    					'url' => U('Admin/User/userList'),
    			),
    			'1' => array(
    					'name' => '快递公司管理',
    					'url' => U('Admin/Express/index'),
    			)
    	);
    	$this->assign('breadhtml', $this->getBread($bread));
    	$m = M('Express');
    	$p = $_GET['p'] ? $_GET['p'] : 1;
    	$psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $count = $m->count();
        $cache = $m->page($p, $psize)->order('id')->select();
        $this->getPage($count, $psize, 'App-loader', '快递公司管理', 'App-search');
        $this->assign("cache", $cache);
        $this->display();
    }

    public function set()
    {
    	$id = I('id');
    	$m = M("Express");
    	//处理POST提交
    	if (IS_POST) {
    		$data = I('post.');
    		if ($id) {
    			$re = $m->save($data);
    		} else {
    			$re = $m->add($data);
    		}
    		if (FALSE !== $re) {
    			$info['status'] = 1;
    			$info['msg'] = '添加成功！';
    		} else {
    			$info['status'] = 0;
    			$info['msg'] = '添加失败！';
    		}
    		$this->ajaxReturn($info);
    	}
    	//处理编辑界面
    	if ($id) {
    		$cache = $m->where('id=' . $id)->find();   	
    		$this->assign('cache', $cache);
    	}
    	$this->display();
    }

    public function del()
    {
    	$id = $_GET['id']; //必须使用get方法
    	$m = M("Express");
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
    	$this->ajaxReturn($info);;
    }
}
