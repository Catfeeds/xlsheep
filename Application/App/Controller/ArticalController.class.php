<?php
/**
 * Created by PhpStorm.
* User: heqing
* Date: 15/9/1
* Time: 09:17
*/

namespace App\Controller;

use Think\Controller;

class ArticalController extends BaseController
{
	public function index()
	{
		if(!I("get.id")){
			return;
		}
		$artical = M("Artical")->where(array("id" => I("get.id")))->find();
		$this->assign("artical", $artical);

		M("Artical")->where(array("id" => I("get.id")))->setInc("visiter");
		$this->display();
	}
	public function lists()
	{
		$m = M('Artical');
		 
		$cid = intval(I('cid')) ? intval(I('cid')) : 0;
		$this->assign('cid', $cid);
		if ($cid) {
			$map['cid'] = $cid;
		}
		$count = $m->where($map)->count();
		//每页显示条数
		$pagesize = 10;
		$cache = $m->where($map)->limit(0, $pagesize)->order('id DESC')->select();
		foreach ($cache as $k => $v) {
			$listpic = $this->getPic($v['pic']);
			$cache[$k]['imgurl'] = $listpic['imgurl'];
			if(!$v['sub']){
				$cache[$k]['sub'] = mb_substr(trim(str_replace('&nbsp;','',strip_tags($v['content']))), 0, 40, 'utf-8');
			}
		}
		//文章分类
		$temp = M('Artical_cate')->select();
		$cates = array();
		foreach($temp as $k => $v) {
			$cates[$v['id']] = $v['name'];
		}
		$this->assign('temp', $temp);
		$this->assign('cache', $cache);
		$this->assign('cates', $cates);
		$this->assign('datamore', $count > 10 ? 1 :0);
		$this->assign('t', time());
		$this->display();
	}

	public function Party()
	{
		if(!I("get.id")){
			return;
		}
		$party = M("Party")->where(array("id" => I("get.id")))->find();
		$this->assign("party", $party);
		$this->display();
	}
}