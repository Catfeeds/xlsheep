<?php
/**
 * 广告管理
 */
namespace Admin\Controller;

class AdController extends BaseController{
   
    public function adList(){
    	//设置面包导航，主加载器请配置
    	$bread = array(
    			'0' => array(
    					'name' => '广告管理',
    					'url' => U('Admin/Ad/adList')
    			),
    			'1' => array(
    					'name' => '广告列表',
    					'url' => U('Admin/Ad/adList')
    			)
    	);
    	$this->assign('breadhtml', $this->getBread($bread));
        $m =  M('ad'); 
        $where = "1=1";
        if(I('pid')){
        	$where = "pid=".I('pid');
        	$this->assign('pid',I('pid'));
        }
        $keywords = I('keywords',false);
        if($keywords){
        	$where = "ad_name like '%$keywords%'";
        }
        $count = $m->where($where)->count();// 查询满足要求的总记录数
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $res = $m->where($where)->order('pid desc')->page($p, $psize)->select();
        $list = array();
        if($res){
        	$media = array('图片','文字','flash');
        	foreach ($res as $val){
        		$listpic = $this->getPic($val['ad_pic']);
        		$val['imgurl'] = $listpic['imgurl'];
        		$val['media_type'] = $media[$val['media_type']];        		
        		$list[] = $val;
        	}
        }
        $ad_position_list = M('AdPosition')->getField("position_id,position_name,is_open");                        
        $this->assign('ad_position_list',$ad_position_list);//广告位 
        $this->getPage($count, $psize, 'App-loader', '广告列表', 'App-search');
        $this->assign('list',$list);// 赋值数据集
        $this->display();
    }
    
    public function adSet()
    {
    	$id = I('ad_id');
    	$m = M('ad');
    	//设置面包导航，主加载器请配置
    	$bread = array(
    			'0' => array(
    					'name' => '广告管理',
    					'url' => U('Admin/Ad/adList')
    			),
    			'1' => array(
    					'name' => '广告列表',
    					'url' => U('Admin/Ad/adList')
    			),
    			'2' => array(
    					'name' => '广告编辑',
    					'url' => U('Admin/Ad/adSet', array('id' => $id))
    			)
    	);
    	$this->assign('breadhtml', $this->getBread($bread));
    	//处理POST提交
    	if (IS_POST) {
    		$data = I('post.');
    		$data['start_time'] = strtotime($data['begin']);
    		$data['end_time'] = strtotime($data['end']);
    		if ($data['ad_id']) {
    			$re = $m->where('ad_id='.$data['ad_id'])->save($data);
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
    	} else {
    		//处理编辑界面
    		if ($id) {
    			$ad_info = M('ad')->where('ad_id='.$id)->find();
    			$ad_info['start_time'] = date('Y-m-d',$ad_info['start_time']);
    			$ad_info['end_time'] = date('Y-m-d',$ad_info['end_time']);
    		}
    		$default_start_time = date('Y-m-d');
    		$default_end_time = date('Y-m-d', strtotime('+3 year', time()));
    		$position = M('ad_position')->where('1=1')->select();
    		$this->assign('info',$ad_info);
    		$this->assign('position',$position);
    		$this->assign('default_start_time',$default_start_time);
    		$this->assign('default_end_time',$default_end_time);
    		$this->display();
    	}
    }
    
    public function adDel() {
    	$id = $_GET['ad_id']; //必须使用get方法
    	$m = M('ad');
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
    
    public function positionSet(){
    	$id = I('position_id');
    	$m = M('ad_position');
    	//设置面包导航，主加载器请配置
    	$bread = array(
    			'0' => array(
    					'name' => '广告管理',
    					'url' => U('Admin/Ad/adList')
    			),
    			'1' => array(
    					'name' => '广告位置',
    					'url' => U('Admin/Ad/positionList')
    			),
    			'2' => array(
    					'name' => '广告位置编辑',
    					'url' => U('Admin/Ad/positionSet', array('id' => $id))
    			)
    	);
    	$this->assign('breadhtml', $this->getBread($bread));
    	//处理POST提交
    	if (IS_POST) {
    		$data = I('post.');
    		if ($data['position_id']) {
    			$re = $m->where('position_id='.$data['position_id'])->save($data);
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
    	} else {
    		//处理编辑界面
    		if ($id) {
    			$info = M('ad_position')->where('position_id='.$id)->find();
    			$this->assign('info',$info);
    		}
    		$this->display();
    	}
    }
    
    public function positionList(){
    	//设置面包导航，主加载器请配置
    	$bread = array(
    			'0' => array(
    					'name' => '广告管理',
    					'url' => U('Admin/Ad/adList')
    			),
    			'1' => array(
    					'name' => '广告位置',
    					'url' => U('Admin/Ad/positionList')
    			)
    	);
    	$this->assign('breadhtml', $this->getBread($bread));
        $m =  M('ad_position');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $where = "1=1";
        $keywords = I('keywords',false);
        if($keywords){
        	$where = "position_name like '%$keywords%'";
        }
        $count = $m->where('1=1')->count();// 查询满足要求的总记录数
        $list = $m->order('position_id DESC')->page($p, $psize)->select();
        $this->getPage($count, $psize, 'App-loader', '广告位置', 'App-search');
        $this->assign('list',$list);// 赋值数据集                
        $this->display();
    }   
    
    public function positionDel(){
    	$id = I('get.position_id', 0, 'intval'); //必须使用get方法
    	$m = M('ad_position');
    	if (!$id) {
    		$info['status'] = 0;
    		$info['msg'] = 'ID不能为空!';
    		$this->ajaxReturn($info);
    	}
    	if(M('ad')->where('pid='.$id)->count()>0){
    		$info['status'] = 0;
    		$info['msg'] = '此广告位下还有广告，请先清除';
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
    
    public function changeAdField(){
    	$data[$_REQUEST['field']] = I('GET.value');
    	$data['ad_id'] = I('GET.ad_id');
    	M('ad')->save($data); // 根据条件保存修改的数据
    }
    
    //获取单张图片
    public function getPic($id)
    {
    	$m = M('Upload_img');
    	$map['id'] = $id;
    	$list = $m->where($map)->find();
    	if ($list) {
    		$list['imgurl'] = __ROOT__ . "/Upload/" . $list['savepath'] . $list['savename'];
    	}
    	return $list ? $list : "";
    }
}