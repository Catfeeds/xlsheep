<?php
namespace Admin\Controller;


class AnnounceController extends BaseController
{
	public function __construct()
	{
		parent::__construct();
	}

	public function index()
	{
		$announce = M('Announce');
		$p = $_GET['p'] ? $_GET['p'] : 1;
		$name = I('name') ? I('name') : '';
		$map = array();
		if ($name) {
			$map['title'] = array('like', "%$name%");
			$this->assign('name', $name);
		}
		$count = $announce->where($map)->count();// 查询满足要求的总记录数
		$psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 12;
		$this->getPage($count, $psize, 'App-loader', '公告管理', 'App-search');
		// 进行分页数据查询 注意limit方法的参数要使用Page类的属性
		$announce = $announce->where($map)->page($p, $psize)->order('id DESC')->select();
		$this->assign("announce", $announce);// 赋值数据集
		$this->assign('page', $show);// 赋值分页输出
		$this->assign('url', "http://" . I("server.HTTP_HOST"));
		$this->display(); // 输出模板
	}

	public function add()
	{
		$announce = M('Announce')->where(array("id" => I("get.id")))->find();
		$this->assign("announce", $announce);
		$this->display();
	}

	public function addAnnounce()
	{
		if ($_POST["id"] == 0) {
			M("Announce")->add($_POST);
		} else {
			$_POST['time'] = time();
			M("Announce")->save($_POST);
		}
		$this->ajaxReturn(array("status"=>"1","msg"=>"操作成功"));
	}

	public function del()
	{
		M("Announce")->where(array("id" => I("get.id")))->delete();
		$this->ajaxReturn(array("status"=>"1","msg"=>"删除成功"));
	}
}