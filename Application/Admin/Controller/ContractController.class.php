<?php
/**
 * Created by PhpStorm.
 * User: heqing
 * Date: 15/9/1
 * Time: 09:17
 */

namespace Admin\Controller;


class ContractController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $m = M('finance_contract'); // 实例化User对象
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $name = I('name') ? I('name') : '';
        $map = array();
        if ($name) {
        	$map['title'] = array('like', "%$name%");
        	$this->assign('name', $name);
        }
        $count = $m->where($map)->count();// 查询满足要求的总记录数
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 12;
        $this->getPage($count, $psize, 'App-loader', '合同管理', 'App-search');       
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $contract = $m->where($map)->page($p, $psize)->order('id DESC')->select();
        $this->assign("contract", $contract);// 赋值数据集
        $this->display(); // 输出模板
    }

    public function add()
    {
        $contract = M('finance_contract')->where(array("id" => I("get.id")))->find();
        $this->assign("contract", $contract);
        $this->display();
    }

    public function addContract()
    {
    	$_POST['time'] = time();
        if ($_POST["id"] == 0) {
            M("finance_contract")->add($_POST);
        } else {
            M("finance_contract")->save($_POST);
        }
        $this->ajaxReturn(array("status"=>"1","msg"=>"成功"));
    }

    public function del()
    {
        M("finance_contract")->where(array("id" => I("get.id")))->delete();
        $this->ajaxReturn(array("status"=>"1","msg"=>"删除成功"));
    }
    //CMS后台文章分类
    public function cate()
    {
    	//设置面包导航，主加载器请配置
    	$bread = array(
    			'0' => array(
    					'name' => '首页',
    					'url' => U('Admin/Index/index'),
    			),
    			'1' => array(
    					'name' => '合同模板',
    					'url' => U('Admin/Contract/cate'),
    			),
    	);
    	$this->assign('breadhtml', $this->getBread($bread));
    	//绑定搜索条件与分页
    	$m = M('contract_templet');
    	$p = $_GET['p'] ? $_GET['p'] : 1;
    	$name = I('name') ? I('name') : '';
    	$map = array();
    	if ($name) {
    		$map['name'] = array('like', "%$name%");
    		$this->assign('name', $name);
    	}
        $cache = $m -> where($map) -> page($p, $psize) -> order('id DESC') ->select();
    	$this->assign('cache', $cache);
    	$this->display();
    }
    
    //CMS后台商城分类设置
    public function cateSet()
    {
    	$id = I('id');
    	$m = M('contract_templet');
    	//设置面包导航，主加载器请配置
    	$bread = array(
    			'0' => array(
    					'name' => '首页',
    					'url' => U('Admin/Index/index'),
    			),
    			'1' => array(
    					'name' => '合同模板',
    					'url' => U('Admin/Contract/cate'),
    			),
    			'2' => array(
    					'name' => '模板设置',
    					'url' => $id ? U('Admin/Contract/cateSet', array('id' => $id)) : U('Admin/Contract/cateSet'),
    			),
    	);
    	$this->assign('breadhtml', $this->getBread($bread));
    	//处理POST提交
    	if (IS_POST) {
    		//die('aa');
    		$data = I('post.');
    		if ($id) {
    			//保存时判断
    			$re = $m->save($data);
    			if (FALSE !== $re) {
    				$info['status'] = 1;
    				$info['msg'] = '设置成功！';
    			} else {
    				$info['status'] = 0;
    				$info['msg'] = '设置失败！';
    			}
    		} else {
                $data['time'] = time();
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
    	//处理编辑界面
    	if ($id) {
    		$cache = $m->where('id=' . $id)->find();
    		$this->assign('contract', $cache);
    	}
    	$this->display();
    }
    
    public function cateDel()
    {
    	$id = $_GET['id']; //必须使用get方法
    	$m = M('contract_templet');
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
    		$this->ajaxReturn($info);
    	}
    	$this->ajaxReturn($info);
    }
}