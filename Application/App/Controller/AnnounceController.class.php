<?php

namespace App\Controller;

use Think\Controller;

class AnnounceController extends Controller
{
    public function index()
    {
        if(!I("get.id")){
            return;
        }
        $announce = M("Announce")->where(array("id" => I("get.id")))->find();
        $this->assign("announce", $announce);

        M("Announce")->where(array("id" => I("get.id")))->setInc("visiter");
        $this->display();
    }
    public function lists()
    {
    	$m = M('Announce');
    	$count = $m->count();
    	//每页显示条数
    	$pagesize = 10;
    	$cache = $m->limit(0, $pagesize)->order('id DESC')->select();
    	$this->assign('cache', $cache);
    	$this->assign('datamore', $count > 10 ? 1 :0);
    	$this->assign('t', time());
    	$this->display();
    }
}