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
		$p = $_GET['p'] ? $_GET['p'] : 1;
		$name = I('name') ? I('name') : '';
		if ($name) {
			$map['name'] = array('like', "%$name%");
			$this->assign('name', $name);
		}
		$psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
		$cache = $m->where($map)->page($p, $psize)->select();
		$count = $m->where($map)->count();
		$this->getPage($count, $psize, 'App-loader', '积分管理', 'App-search');
		$this->assign("cache", $cache);
		$this->display();
	}
	public function goodsSet()
	{
		$id = I('id');
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
				),
				'2' => array(
						'name' => '积分商品设置',
						'url' => $id ? U('Admin/Score/goodsSet', array('id' => $id)) : U('Admin/Md/mdSet'),
				),
		);
		$this->assign('breadhtml', $this->getBread($bread));
		//处理POST提交
		if (IS_POST) {
			$data = I('post.');
			$data['status'] = 1;
			$data['ctime'] = $data['etime'] = time();
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
		$m = M('Score');
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

	public function order()
	{
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
						'name' => '积分订单管理',
						'url' => U('Admin/Score/order'),
				),
		);
		$this->assign('breadhtml', $this->getBread($bread));
		$p = $_GET['p'] ? $_GET['p'] : 1;
		$name = I('name') ? I('name') : '';
		if ($name) {
			//订单号邦定
			$map['orderid'] = array('like', "%$name%");
			$this->assign('name', $name);
		}
		$m = D('ScoreOrder');
		$psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
		$cache = $m->where($map)->page($p, $psize)->order("id desc")->relation(true)->select();
		$count = $m->where($map)->count();
		$this->getPage($count, $psize, 'App-loader', '积分订单管理', 'App-search');
		$this->assign("cache", $cache);
		$this->display();
	}
	//订单发货
	public function orderDeliver()
	{
		$id = I('id');
		if (!$id) {
			$info['status'] = 0;
			$info['msg'] = '未正常获取ID数据！';
			$this->ajaxReturn($info);
		}
		$re = M('ScoreOrder')->where('id=' . $id)->setField('status', 2);
		$dwechat = D('Wechat');
		if (FALSE !== $re) {

			// 插入订单发货模板消息=====================
			$order = M('Score_order')->where('id=' . $id)->find();
			$vip = M('vip')->where(array('id' => $order['user_id']))->find();
			$templateidshort = 'OPENTM201541214';
			$templateid = $dwechat->getTemplateId($templateidshort);

			if ($templateid) { // 存在才可以发送模板消息
				$data = array();
				$data['touser'] = $vip['openid'];
				$data['template_id'] = $templateid;
				$data['topcolor'] = "#0000FF";
				$data['data'] = array(
						'first' => array('value' => '您好，您的积分商品订单已发货'),
						'keyword1' => array('value' => $order['orderid']),
						'keyword2' => array('value' => $order['fahuokd']),
						'keyword3' => array('value' => $order['fahuokdnum']),
						'remark' => array('value' => '')
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

	//完成订单
	public function ordersuccess()
	{
		$id = I('id');
		if (!$id) {
			$info['status'] = 0;
			$info['msg'] = '未正常获取ID数据！';
			$this->ajaxReturn($info);
		}
		$m = M("ScoreOrder");
		$map['id'] = $id;
		$cache = $m->where($map)->find();
		if (!$cache) {
			$info['status'] = 0;
			$info['msg'] = '操作失败！';
			$this->ajaxReturn($info);
		}
		if ($cache['status'] != 2) {
			$info['status'] = 0;
			$info['msg'] = '操作失败！';
			$this->ajaxReturn($info);
		}
		$data['status'] = 3;
		$re = $m->save($data);
		if(FALSE !== $re) {
			$this->ajaxReturn(array("status"=>"1","msg"=>"操作成功"));
		} else {
			$this->ajaxReturn(array("status"=>"0","msg"=>"操作失败"));
		}
	}
	public function goodsStatus()
	{
		$m = M('Score');
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
	public function orderFhkd()
	{
		$map['id'] = I('id');
		$cache = M('Score_order')->where($map)->find();
		$this->assign('cache', $cache);
		$mb = $this->fetch();
		$this->ajaxReturn($mb);
	}
}