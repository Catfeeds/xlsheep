<?php
/**
 * Created by PhpStorm.
 * User: heqing
 * Date: 15/9/1
 * Time: 09:17
 */

namespace Admin\Controller;


class ArticalController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $artical = M('Artical'); // 实例化User对象
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $name = I('name') ? I('name') : '';
        $map = array();
        if ($name) {
        	$map['title'] = array('like', "%$name%");
        	$this->assign('name', $name);
        }
        $count = $artical->where($map)->count();// 查询满足要求的总记录数
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 12;
        $this->getPage($count, $psize, 'App-loader', '文章管理', 'App-search');       
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $artical = $artical->where($map)->page($p, $psize)->order('id DESC')->select();
        foreach ($artical as $k => $v) {
        	$listpic = $this->getPic($v['pic']);
        	$artical[$k]['imgurl'] = $listpic['imgurl'];
        }
        //文章分类
		$cates = M('Artical_cate')->select();
		$temp = array();
		foreach($cates as $k => $v) {
			$temp[$v['id']] = $v['name'];
		}
		$this->assign("cates", $temp);
        $this->assign("artical", $artical);// 赋值数据集
        $this->assign('url', "http://" . I("server.HTTP_HOST"));
        $this->display(); // 输出模板
    }

    public function add()
    {
        $artical = M('Artical')->where(array("id" => I("get.id")))->find();
        $this->assign("artical", $artical);
        //AppTree快速无限分类
        $field = array("id", "pid", "name", "concat(path,'-',id) as bpath");
        $cate = appTree(M('Artical_cate'), 0, $field);
        $this->assign('cate', $cate);
        $this->display();
    }

    public function addArtical()
    {
    	$_POST['time'] = time();
        if ($_POST["id"] == 0) {
            M("Artical")->add($_POST);
        } else {
            M("Artical")->save($_POST);
        }
        $this->ajaxReturn(array("status"=>"1","msg"=>"添加成功"));
    }

    public function del()
    {
        M("Artical")->where(array("id" => I("get.id")))->delete();
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
    					'name' => '文章分类',
    					'url' => U('Admin/Artical/cate'),
    			),
    	);
    	$this->assign('breadhtml', $this->getBread($bread));
    	//绑定搜索条件与分页
    	$m = M('Artical_cate');
    	$p = $_GET['p'] ? $_GET['p'] : 1;
    	$name = I('name') ? I('name') : '';
    	$map = array();
    	if ($name) {
    		$map['name'] = array('like', "%$name%");
    		$this->assign('name', $name);
    	}
    	//AppTree快速无限分类
    	$field = array("id", "pid", "lv", "name", "summary", "soncate", "sorts", "concat(path,'-',id) as bpath");
    	$cache = appTree($m, 0, $field, $map);
    	$this->assign('cache', $cache);
    	$this->display();
    }
    
    //CMS后台商城分类设置
    public function cateSet()
    {
    	$id = I('id');
    	$m = M('Artical_cate');
    	//设置面包导航，主加载器请配置
    	$bread = array(
    			'0' => array(
    					'name' => '首页',
    					'url' => U('Admin/Index/index'),
    			),
    			'1' => array(
    					'name' => '文章分类',
    					'url' => U('Admin/Artical/cate'),
    			),
    			'2' => array(
    					'name' => '分类设置',
    					'url' => $id ? U('Admin/Artical/cateSet', array('id' => $id)) : U('Admin/Artical/cateSet'),
    			),
    	);
    	$this->assign('breadhtml', $this->getBread($bread));
    	//处理POST提交
    	if (IS_POST) {
    		//die('aa');
    		$data = I('post.');
    		if ($id) {
    			//保存时判断
    			if($data['pid'] == $id) {
    				$info['status'] = 0;
    				$info['msg'] = '不能添加自己为父类！';
    				$this->ajaxReturn($info);
    			}
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
    	$m = M('Artical_cate');
    	if (!$id) {
    		$info['status'] = 0;
    		$info['msg'] = 'ID不能为空!';
    		$this->ajaxReturn($info);
    	}
    	//删除时判断
    	$self = $m->where('id=' . $id)->limit(1)->find();
    	// 存在子类不删除
    	// if($self['soncate']){
    	// 	$info['status']=0;
    	// 	$info['msg']='不能删除，存在子分类！';
    	// 	$this->ajaxReturn($info);
    	// }
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
}