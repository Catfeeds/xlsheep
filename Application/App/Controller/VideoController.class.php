<?php
/**
 * Created by PhpStorm.
 * User: heqing
 * Date: 15/9/1
 * Time: 09:17
 */

namespace App\Controller;

use Think\Controller;

class VideoController extends BaseController
{
    public function lists()
    {
        $m = M('Video');
        $count = $m->count();
        //每页显示条数
        $pagesize = 100;
        
       
        
        $cate = M('Video_cate')->select();
         
        foreach ($cate as $key => $value) {
          $where = ' cid='.$key;
          $cache = $m->where($where)->limit(0, $pagesize)->order('id ASC')->select();
          foreach ($cache as $k => $v) {
                $listpic = $this->getPic($v['pic']);
                $cache[$k]['imgurl'] = $listpic['imgurl'];
             }
             $cate[$key]['videolist'] = $cache;
        }
          

          
        
        $this->assign('cate', $cate);


        // $this->assign('datamore', $count > $pagesize ? 1 :0);
        $this->assign('t', time());
        $this->display();
    }
    
    public function show()
    {
    	$id = I("get.id",0,'intval');
    	$m = M("Video");
    	if(!$id){
    		$this->error('缺少参数！');
    	}
    	$video = $m->where(array("id" => $id))->find();
    	if(!$video) {
    		$this->error('视频不存在！');
    	}
    	$listpic = $this->getPic($video['pic']);
    	$video['imgurl'] = $listpic['imgurl'];
    	$m->where(array("id" => $id))->setInc("visiter");
    	$this->assign("video", $video);   
    	$this->assign("actname",'ftlive');
    	$this->display();
    }
}