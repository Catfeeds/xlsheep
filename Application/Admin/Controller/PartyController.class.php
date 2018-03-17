<?php
/**
 * Created by PhpStorm.
 * User: heqing
 * Date: 15/9/1
 * Time: 09:17
 */

namespace Admin\Controller;


class PartyController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $party = M('party'); // 实例化User对象
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $name = I('name') ? I('name') : '';
        $map = array();
        if ($name) {
        	$map['title'] = array('like', "%$name%");
        	$this->assign('name', $name);
        }
        $count = $party->where($map)->count();// 查询满足要求的总记录数
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 12;
        $this->getPage($count, $psize, 'App-loader', '文章管理', 'App-search');       
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $party = $party->where($map)->page($p, $psize)->order('id DESC')->select();
        $this->assign("party", $party);// 赋值数据集
        $this->assign('url', "http://" . I("server.HTTP_HOST"));
        $this->display(); // 输出模板
    }

    public function add()
    {
        $party = M('party')->where(array("id" => I("get.id")))->find();
        $this->assign("party", $party);
        //AppTree快速无限分类
        $field = array("id", "pid", "name", "concat(path,'-',id) as bpath");
        $this->display();
    }

    public function addparty()
    {
    	$_POST['time'] = time();
        if ($_POST["id"] == 0) {
            M("party")->add($_POST);
        } else {
            M("party")->save($_POST);
        }
        $this->ajaxReturn(array("status"=>"1","msg"=>"添加成功"));
    }

    public function del()
    {
        M("party")->where(array("id" => I("get.id")))->delete();
        $this->ajaxReturn(array("status"=>"1","msg"=>"删除成功"));
    }
}