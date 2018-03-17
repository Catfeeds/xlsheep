<?php
// +----------------------------------------------------------------------
// | 用户后台基础类--CMS分组商城管理类
// +----------------------------------------------------------------------
namespace Admin\Controller;

class ShopController extends BaseController
{

    public function _initialize()
    {
        //你可以在此覆盖父类方法
        parent::_initialize();
        //初始化两个配置
        self::$CMS['shopset'] = M('Shop_set')->find();
        self::$CMS['vipset'] = M('Vip_set')->find();
        $this->assign("adminer",self::$CMS['user']);
    }

    //CMS后台商城管理引导页
    public function index()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '商城首页',
                'url' => U('Admin/Shop/index'),
            ),
        );
        $this->display();
    }

    //CMS后台门店设置
    public function set()
    {
        $id = I('id');
        $m = M('Shop_set');
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '商城管理',
                'url' => U('Admin/Shop/index'),
            ),
            '1' => array(
                'name' => '商城设置',
                'url' => U('Admin/Shop/set'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //处理POST提交
        if (IS_POST) {
            //die('aa');
            $data = I('post.');
            $old = $m->where('id=' . $id)->find();
            if ($old) {
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
        $cache = $m->where('id=1')->find();
        $this->assign('cache', $cache);
        $this->display();
    }

    //CMS后台商城分组
    public function goods()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '商城首页',
                'url' => U('Admin/Shop/index'),
            ),
            '1' => array(
                'name' => '商品管理',
                'url' => U('Admin/Shop/goods'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //绑定搜索条件与分页
        $m = M('Shop_goods');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $name = I('name') ? I('name') : '';
        if ($name) {
            $map['name'] = array('like', "%$name%");
            $this->assign('name', $name);
        }
        $user=self::$CMS['user'];
        if($user["user_type"]==1){
            $map["adminid"]=$user["id"];
        }
        $adminlist=M("user")->getField("id,username",true);
        $adminlist[0]="总管理";
        $this->assign("adminlist",$adminlist);
        $map['cid'] = array('not in','13,16,17');
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $cache = $m->where($map)->page($p, $psize)->select();
        $count = $m->where($map)->count();
        $cate_whe['id'] = array('not in','13,16,17');
        $cates = getCate(M('Shop_cate'),$cate_whe);
        $this->assign('cates', $cates);
        $this->getPage($count, $psize, 'App-loader', '商品管理', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }

    //CMS后台商品设置
    public function goodsSet()
    {
        $id = I('id');
        $m = M('Shop_goods');
        //dump($m);
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '商城首页',
                'url' => U('Admin/Shop/index'),
            ),
            '1' => array(
                'name' => '商品管理',
                'url' => U('Admin/Shop/goods'),
            ),
            '2' => array(
                'name' => '商品设置',
                'url' => $id ? U('Admin/Shop/goodsSet', array('id' => $id)) : U('Admin/Md/mdSet'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        $user=self::$CMS['user'];
        //处理POST提交
        if (IS_POST) {
            //die('aa');
            $data = I('post.');
            // $data['adressid'] = substr($data['adressid'],0,strlen($data['adressid'])-1);
            $data['content'] = trimUE($data['content']);
            //var_dump($data);die;
            if ($id) {

                $oldData=$m->where(array("id"=>$id))->find();
                if($user["user_type"]==1){
                    foreach (array_keys($data) as $k=>$v){
                        if($data[$v]!=$oldData[$v]){
                            $data["check_status"]=1;
                            $data["status"]=0;

                            break;
                        }
                    }
                    $data["adminid"]=$user["id"];
                    //如果是修改过，审核状态修改，并下架相应 的产品

                }
                $re = $m->save($data);
                if (FALSE !== $re) {
                    $info['status'] = 1;
                    $info['msg'] = '设置成功！';
                } else {
                    $info['status'] = 0;
                    $info['msg'] = '设置失败！';
                }
            } else {

                if($user["user_type"]==1){
                    $data["adminid"]=$user["id"];
                    $data["check_status"]=1;
                    $data["status"]=0;
                }
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
        //读取标签
        $label = M('Shop_label')->select();
        $this->assign('label', $label);
        //AppTree快速无限分类
        $cate_whe['id'] = array('neq','13');
        $field = array("id", "pid", "name", "sorts", "concat(path,'-',id) as bpath");
        $cate = appTree(M('Shop_cate'), 0, $field,$cate_whe);
        $this->assign('cate', $cate);


        $cate2 = appTree(M('Shop_cate2'), 0, $field,'');
        $this->assign('cate2', $cate2);

         
        //处理编辑界面
        if ($id) {
            $cache = $m->where('id=' . $id)->find();
            if($cache['extend_cid']) {
            	$cache['extend_cids'] = explode(',', $cache['extend_cid']);
            }
            $this->assign('cache', $cache);
            // if($cache['adressid']){
            //    $zi['id'] = array('in',$cache['adressid']);
            //     $ziti = M('Since')->where($zi)->select();
            //     $this->assign('ziti', $ziti); 
            // }      
        }
        $shopset = self::$SHOP['set'];
        $this->assign('shopset', $shopset);
        $this->display();
    }
    
    //自提点设置
    public function adSet(){
    	$id = I('id');
    	$m = M('Shop_goods');
    	if (IS_POST) {
    		$data = I('post.');
    		$data['adressid'] = substr($data['adressid'],0,strlen($data['adressid'])-1);
    		if ($id) {
    			$data['etime'] = time();
    			$re = $m->save($data);
    			if (FALSE !== $re) {
    				$info['status'] = 1;
    				$info['msg'] = '设置成功！';
    			} else {
    				$info['status'] = 0;
    				$info['msg'] = '设置失败！';
    			}
    		}else{
    			$info['status'] = 0;
    			$info['msg'] = '非法操作';
    		}
    		$this->ajaxReturn($info);
    	}
    	if ($id) {
    		$cache = $m->where('id=' . $id)->find();
    		$this->assign('cache', $cache);
    	}
    	$province = M('Since')->distinct('province')->field('province')->select();
    	$ziti = array();
    	foreach($province as $k => $v) {
    		$ziti['province'][$k]['id'] = $v['province'];
    		$cityids = get_region_child_ids($v['province']);
    		if($cityids) {
    			$city = M('Since')->where(array('city'=>array('in', $cityids)))->distinct('city')->field('city')->select();
    		} else {
    			$city = array();
    		}
    		foreach($city as $kk => $vv) {
    			$ziti['province'][$k]['city'][$kk]['id']= $vv['city'];
    			$districtids = get_region_child_ids($vv['city']);
    			if($districtids) {
	    			$district = M('Since')->where(array('district'=>array('in', $districtids)))->distinct('district')->field('district')->select();	  
	    			foreach($district as $kkk => $vvv) {
	    				$ziti['province'][$k]['city'][$kk]['district'][$kkk]['id'] = $vvv['district'];
	    				$ziti['province'][$k]['city'][$kk]['district'][$kkk]['sincelist'] = M('Since')->where('district='.$vvv['district'])->select();
	    			}
    			} else {
    				$ziti['province'][$k]['city'][$kk]['district']= array();
    			}
    		}
    	}
    	//echo '<pre>';print_r($ziti);echo '</pre>';die;
    	$this->assign('ziti', $ziti['province']);
    	$region_list = get_region_list();
    	$this->assign('region_list', $region_list);
    	$this->display();
    }
    public function goodsDel()
    {
        $id = $_GET['id']; //必须使用get方法
        $m = M('Shop_goods');
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
    /**
     * 审核
     * author: feng
     * create: 2017/9/21 9:59
     */
    public function goodsCheckStatus()
    {
        if(self::$CMS['user']['user_type']){
            $info['status'] = 0;
            $info['msg'] = '你没有权限操作!';
            $this->ajaxReturn($info);
        }
        $m = M('Shop_goods');
        $now = I('get.status');
        $map['id'] = I('get.id');
        //审核通过并上架，审核不通过下架
        if($now==0){
            $data["status"]=1;
            $data["check_status"]=0;
        }else{
            $data["status"]=0;
            $data["check_status"]=2;
        }
        $re = $m->where($map)->save($data);

        if ($re!==false) {
            $info['status'] = 1;
            $info['msg'] = '操作成功!';
        } else {
            $info['status'] = 0;
            $info['msg'] = '操作失败!';
        }

        $this->ajaxReturn($info);
    }
    public function goodsStatus()
    {
        $m = M('Shop_goods');
        $now = I('status') ? 0 : 1;
        $map['id'] = I('id');
        if($now == 1){
            $handle = $m->where($map)->setField('ishandle', 1);
        }
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
    
    public function recommend()
    {
    	$m = M('Shop_goods');
    	$now = I('status') ? 0 : 1;
    	$map['id'] = I('id');
    	$re = $m->where($map)->setField('is_recommend', $now);    	
    	if ($re) {
    		$info['status'] = 1;
    		$info['msg'] = '设置成功!';
    	} else {
    		$info['status'] = 0;
    		$info['msg'] = '设置失败!';
    	}
    	$this->ajaxReturn($info);
    }
    //CMS后台商城分类
    public function cate()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '商城首页',
                'url' => U('Admin/Shop/index'),
            ),
            '1' => array(
                'name' => '商城分类',
                'url' => U('Admin/Shop/cate'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //绑定搜索条件与分页
        $m = M('Shop_cate');
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

     //CMS后台商城分类
    public function cate2()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '商城首页',
                'url' => U('Admin/Shop/index'),
            ),
            '1' => array(
                'name' => '商城分类',
                'url' => U('Admin/Shop/cate2'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //绑定搜索条件与分页
        $m = M('Shop_cate2');
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
        $m = M('Shop_cate');
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '商城首页',
                'url' => U('Admin/Shop/index'),
            ),
            '1' => array(
                'name' => '商城分类',
                'url' => U('Admin/Shop/cate'),
            ),
            '2' => array(
                'name' => '分类设置',
                'url' => $id ? U('Admin/Shop/cateSet', array('id' => $id)) : U('Admin/Shop/cateSet'),
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
        $cate = appTree($m, 0, $field);
        $this->assign('cate', $cate);
        $this->display();
    }


    //CMS后台商城分类设置
    public function cateSet2()
    {
        $id = I('id');
        $m = M('Shop_cate2');
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '商城首页',
                'url' => U('Admin/Shop/index'),
            ),
            '1' => array(
                'name' => '商城分类',
                'url' => U('Admin/Shop/cate2'),
            ),
            '2' => array(
                'name' => '分类设置',
                'url' => $id ? U('Admin/Shop/cateSet2', array('id' => $id)) : U('Admin/Shop/cateSet2'),
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
        $cate = appTree($m, 0, $field);
        $this->assign('cate', $cate);
        $this->display();
    }

    public function cateDel()
    {
        $user=self::$CMS['user'];
        if($user["user_type"]==1){
            $info['status'] = 0;
            $info['msg'] = '你没有权限删除!';
            $this->ajaxReturn($info);
        }
        $id = $_GET['id']; //必须使用get方法
        $m = M('Shop_cate');
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


    public function cateDel2()
    {
        $user=self::$CMS['user'];
        if($user["user_type"]==1){
            $info['status'] = 0;
            $info['msg'] = '你没有权限删除!';
            $this->ajaxReturn($info);
        }
        $id = $_GET['id']; //必须使用get方法
        $m = M('Shop_cate2');
        if (!$id) {
            $info['status'] = 0;
            $info['msg'] = 'ID不能为空!';
            $this->ajaxReturn($info);
        }
        //删除时判断
        $self = $m->where('id=' . $id)->limit(1)->find();
        // 存在子类不删除
        // if($self['soncate']){
        //  $info['status']=0;
        //  $info['msg']='不能删除，存在子分类！';
        //  $this->ajaxReturn($info);
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

    //CMS后台商城分组
    public function group()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '商城首页',
                'url' => U('Admin/Shop/index'),
            ),
            '1' => array(
                'name' => '商城分组',
                'url' => U('Admin/Shop/group'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //绑定搜索条件与分页
        $m = M('Shop_group');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $name = I('name') ? I('name') : '';
        if ($name) {
            $map['name'] = array('like', "%$name%");
            $this->assign('name', $name);
        }
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $cache = $m->where($map)->page($p, $psize)->select();
        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '商城分组', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }

    //CMS后台分组设置
    public function groupSet()
    {
        $id = I('id');
        $m = M('Shop_group');
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '商城首页',
                'url' => U('Admin/Shop/index'),
            ),
            '1' => array(
                'name' => '商城分组',
                'url' => U('Admin/Shop/group'),
            ),
            '2' => array(
                'name' => '分组设置',
                'url' => $id ? U('Admin/Shop/groupSet', array('id' => $id)) : U('Admin/Shop/groupSet'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //处理POST提交
        if (IS_POST) {
            //die('aa');
            $data = I('post.');
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

    // 设置分组显示
    public function setGroup()
    {
        $id = $_GET['id']; //必须使用get方法
        $m = M('Shop_group');
        if (!id) {
            $info['status'] = 0;
            $info['msg'] = 'ID不能为空!';
            $this->ajaxReturn($info);
        }
        // 撤销原有分组
        $ree = $m->where(array('status' => 1))->save(array('status' => 0));
        $re = $m->where(array('id' => $id))->save(array('status' => 1));
        if ($re) {
            $info['status'] = 1;
            $info['msg'] = '分组显示更新成功!';
        } else {
            $info['status'] = 0;
            $info['msg'] = '设置失败!';
        }
        $this->ajaxReturn($info);
    }

    public function groupDel()
    {
        $id = $_GET['id']; //必须使用get方法
        $m = M('Shop_group');
        if (!id) {
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

    //CMS后台SKU属性
    public function skuattr()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '商城首页',
                'url' => U('Admin/Shop/index'),
            ),
            '1' => array(
                'name' => 'SKU属性',
                'url' => U('Admin/Shop/skuattr'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //绑定搜索条件与分页
        $m = M('Shop_skuattr');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $name = I('name') ? I('name') : '';
        if ($name) {
            $map['name'] = array('like', "%$name%");
            $this->assign('name', $name);
        }
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $cache = $m->where($map)->page($p, $psize)->select();
        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', 'SKU属性', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }

    //CMS后台SKU属性设置
    public function skuattrSet()
    {
        $id = I('id');
        $m = M('Shop_skuattr');
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '商城首页',
                'url' => U('Admin/Shop/index'),
            ),
            '1' => array(
                'name' => '商城分组',
                'url' => U('Admin/Shop/skuattr'),
            ),
            '2' => array(
                'name' => 'SKU属性设置',
                'url' => $id ? U('Admin/Shop/skuattrSet', array('id' => $id)) : U('Admin/Shop/skuattrSet'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //处理POST提交
        if (IS_POST) {
            //die('aa');
            $data = I('post.');
            if ($id) {
                $re = $m->save($data);
                if (FALSE !== $re) {
                    if ($data['newitem']) {
                        $mitem = M('Shop_skuattr_item');
                        $dit['pid'] = $id;
                        $items = array_filter(explode(',', $data['newitem']));
                        foreach ($items as $v) {
                            $dit['name'] = $v;
                            $rit = $mitem->add($dit);
                            if ($rit) {
                                $rr['path'] = $id . $rit;
                                $rerr = $mitem->where('id=' . $rit)->save($rr);
                            }
                        }
                        $son = $mitem->where('pid=' . $id)->field('name,path')->select();
                        $dson['items'] = "";
                        $dson['itemspath'] = "";
                        foreach ($son as $v) {
                            $dson['items'] = $dson['items'] . $v['name'] . ',';
                            $dson['itemspath'] = $dson['itemspath'] . $v['path'] . ',';
                        }
                        $rfather = $m->where('id=' . $id)->save($dson);
                    }
                    $info['status'] = 1;
                    $info['msg'] = '设置成功！';
                } else {
                    $info['status'] = 0;
                    $info['msg'] = '设置失败！';
                }
            } else {
                $dt['name'] = $data['name'];
                $dt['cctime'] = time();
                $re = $m->add($dt);
                if ($re) {
                    if ($data['newitem']) {
                        $mitem = M('Shop_skuattr_item');
                        $dit['pid'] = $re;
                        $items = array_filter(explode(',', $data['newitem']));
                        foreach ($items as $v) {
                            $dit['name'] = $v;
                            $rit = $mitem->add($dit);
                            if ($rit) {
                                $rr['path'] = $re . $rit;
                                $rerr = $mitem->where('id=' . $rit)->save($rr);
                            }
                        }
                        $son = $mitem->where('pid=' . $re)->field('name,path')->select();
                        $dson['items'] = "";
                        $dson['itemspath'] = "";
                        foreach ($son as $v) {
                            $dson['items'] = $dson['items'] . $v['name'] . ',';
                            $dson['itemspath'] = $dson['itemspath'] . $v['path'] . ',';
                        }
                        $rfather = $m->where('id=' . $re)->save($dson);
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
        $this->display();
    }

    public function skuattrDel()
    {
        $user=self::$CMS['user'];
        if($user["user_type"]==1){
            $info['status'] = 0;
            $info['msg'] = '你没有权限删除!';
            $this->ajaxReturn($info);
        }
        $id = $_GET['id']; //必须使用get方法
        $m = M('Shop_skuattr');
        if (!id) {
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

    //用于SKUINFO保存
    public function skuattrSave()
    {
        $id = $_GET['id']; //必须使用get方法
        if (!$id) {
            $info['status'] = 0;
            $info['msg'] = '商品ID不能为空!';
            $this->ajaxReturn($info);
        }
        //处理skuattr
        $data = I('data');
        if (!$data) {
            $info['status'] = 0;
            $info['msg'] = "您还没有选择任何属性！";
            $this->ajaxReturn($info);
        }
        $list=array();
        $arr = array_filter(explode(';', $data));
        foreach ($arr as $k => $v) {
            $arr2 = array_filter(explode('-', $v));
            $arrattr = explode(':', $arr2[0]);
            $arritem = array_filter(explode(',', $arr2[1]));
            $list[$k]['attrid'] = $arrattr[0];
            $list[$k]['attrlabel'] = $arrattr[1];
            $checked = "";
            //循环item
            foreach ($arritem as $kk => $vv) {
                $at = explode(':', $vv);
                $list[$k]['items'][$at[0]] = $at[1];
                $checked = $checked . $at[0] . ',';
            }
            $list[$k]['checked'] = $checked;
        }
        $list = list_sort_by($list, 'attrid', 'asc');
        //dump($list);
        //$info['status']=1;
        //$info['msg']=serialize($list);
        //$this->ajaxReturn($info);
        $m = M('Shop_goods');
        $skuinfo['skuinfo'] = serialize($list);
        $re = $m->where('id=' . $id)->save($skuinfo);
        if ($re !== FALSE) {
            $info['status'] = 1;
            $info['msg'] = 'SKU属性保存成功!如有变更请及时更新所有SKU!';
        } else {
            $info['status'] = 0;
            $info['msg'] = 'SKU属性保存失败!请重新尝试!';
        }
        $this->ajaxReturn($info);
    }

    //用于SKU生成
    public function skuattrMake()
    {
        $id = $_GET['id']; //必须使用get方法
        if (!$id) {
            $info['status'] = 0;
            $info['msg'] = '商品ID不能为空!';
            $this->ajaxReturn($info);
        }
        $m = M('Shop_goods');
        $goods = $m->where('id=' . $id)->find();
        $skuinfo = unserialize($goods['skuinfo']);
        //dump($skuinfo);
        if (!$skuinfo) {
            $info['status'] = 0;
            $info['msg'] = '您还未设置或保存SKU属性!';
            $this->ajaxReturn($info);
        }
        $cacheattrs = array(); //缓存所有属性表
        $cache=array(); //缓存skupath列表
        $tmpsku=array(); //缓存零时sku
        $tmpskuattrs=""; //sku属性对照表
        foreach ($skuinfo as $k => $v) {
            $cacheattrs = $cacheattrs + $skuinfo[$k]['items'];
            $cache[$k] = array_filter(explode(',', $v['checked']));
        }

        if (count($cache) > 1) {
            //快速排列
            $tmp = Descartes($cache);
            foreach ($tmp as $k => $v) {
                $sttr;
                foreach ($v as $kk => $vv) {
                    $sttr[$kk] = $cacheattrs[$vv];
                }
                $sk = $id . '-' . implode('-', $v);
                $tmpsku[$k] = $sk;
                $tmpskuattrs[$sk] = implode(',', $sttr);

            }
        } else {
            foreach ($cache[0] as $k => $v) {
                $sk = $id . '-' . $v;
                $tmpsku[$k] = $sk;
                $tmpskuattrs[$sk] = $cacheattrs[$v];
            }
        }
        //dump($tmpskuattrs);
        //dump($tmpsku);

        $fftmpsku = array_flip($tmpsku);
        //处理原始sku
        $msku = M('Shop_goods_sku');
        $oldsku = $msku->where('goodsid=' . $id)->select();
        if ($oldsku) {
            foreach ($oldsku as $k => $v) {
                //如果已经建立,判断状态
                if (!in_array($v['sku'], $tmpsku)) {
                    //如果不存在，禁用该sku
                    $v['status'] = 0;
                    $ro = $msku->save($v);
                } else {
                    //如果已经存在，开启该sku
                    $v['status'] = 1;
                    $ro = $msku->save($v);
                    //移除fftmpsku对应项目
                    unset($fftmpsku[$v['sku']]);
                }

            }
        }
        //最后需要添加的新sku
        $finaltmpsku = array_flip($fftmpsku);
        //dump($finaltmpsku);
        //die();
        if ($finaltmpsku) {
            $dsku;
            foreach ($finaltmpsku as $k => $v) {
                $dsku[$k]['goodsid'] = $id;
                $dsku[$k]['sku'] = $v;
                $dsku[$k]['skuattr'] = $tmpskuattrs[$v];
                $dsku[$k]['price'] = $goods['price'];
                $dsku[$k]['num'] = $goods['num'];
                $dsku[$k]['status'] = 1;
            }
            //强制重新排序
            sort($dsku);
            //计算总库存
            $re = $msku->addAll($dsku);
            if ($re) {
                $totalnum = $msku->where(array('goodsid' => $id, 'status' => 1))->sum('num');
                if ($totalnum) {
                    $rgg = $m->where('id=' . $id)->setField('num', $totalnum);
                }
                //计算总库存
                $info['status'] = 1;
                $info['msg'] = 'SKU更新成功!';
            } else {
                $info['status'] = 0;
                $info['msg'] = 'SKU更新失败!请重新尝试!';
            }
        } else {
            $totalnum = $msku->where(array('goodsid' => $id, 'status' => 1))->sum('num');
            if ($totalnum) {
                $rgg = $m->where('id=' . $id)->setField('num', $totalnum);
            }
            $info['status'] = 1;
            $info['msg'] = 'SKU更新成功!没有新增SKU!';
        }
        $this->ajaxReturn($info);
    }

    //CMS后台SKU管理
    public function sku()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '商城首页',
                'url' => U('Admin/Shop/index'),
            ),
            '1' => array(
                'name' => '商品管理',
                'url' => U('Admin/Shop/goods'),
            ),
            '1' => array(
                'name' => '商品SKU管理',
                'url' => U('Admin/Shop/skuattr', array('id' => $_GET['id'])),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        $goodsid = I('id');
        $this->assign('goodsid', $goodsid);
        //绑定商品和skuinfo
        $goods = M('Shop_goods')->where('id=' . $goodsid)->find();
        $this->assign('goods', $goods);
        if ($goods['skuinfo']) {
            $skuinfo = unserialize($goods['skuinfo']);
            $skm = M('Shop_skuattr_item');
            foreach ($skuinfo as $k => $v) {
                $checked = explode(',', $v['checked']);
                $attr = $skm->field('path,name')->where('pid=' . $v['attrid'])->select();
                foreach ($attr as $kk => $vv) {
                    $attr[$kk]['checked'] = in_array($vv['path'], $checked) ? 1 : '';
                }
                $skuinfo[$k]['allitems'] = $attr;
            }
        }
        //dump($skuinfo);

        $this->assign('skuinfo', $skuinfo);
        //绑定搜索条件与分页
        $m = M('Shop_goods_sku');
        //追入商品条件
        $map['goodsid'] = $goodsid;
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $name = I('name') ? I('name') : '';
        $map['status'] = 1;
        if ($name) {
            $map['skuattr'] = array('like', "%$name%");
            $this->assign('name', $name);
        }
        //$psize=self::$CMS['set']['pagesize']?self::$CMS['set']['pagesize']:20;
        $psize = 50;
        $cache = $m->where($map)->page($p, $psize)->select();
        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '商品SKU管理', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }

    //CMS后台sku设置
    public function skuSet()
    {
        $id = I('id');
        $m = M('Shop_goods_sku');
        //处理编辑界面
        $cache = $m->where('id=' . $id)->find();
        $this->assign('cache', $cache);
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '商城首页',
                'url' => U('Admin/Shop/index'),
            ),
            '1' => array(
                'name' => '商品SKU管理',
                'url' => U('Admin/Shop/sku', array('id' => $cache['goodsid'])),
            ),
            '2' => array(
                'name' => '商品SKU设置',
                'url' => U('Admin/Shop/skuSet', array('id' => $id)),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //处理POST提交
        if (IS_POST) {
            //只有保存模式
            $data = I('post.');
            $re = $m->where('id=' . $id)->save($data);
            if (FALSE !== $re) {
                //重新计算总库存
                $totalnum = $m->where(array('goodsid' => $cache['goodsid'], 'status' => 1))->sum('num');
                if ($totalnum) {
                    $rgg = M('Shop_goods')->where('id=' . $cache['goodsid'])->setField('num', $totalnum);
                }
                $info['status'] = 1;
                $info['msg'] = '设置成功！';
            } else {
                $info['status'] = 0;
                $info['msg'] = '设置失败！';
            }
            $this->ajaxReturn($info);
        }

        $this->display();
    }

    //CMS后台SKU查找带回管理器
    public function skuLoader()
    {
        $m = M('Shop_skuattr');
        $findback = I('fbid');
        $this->assign('findback', $findback);
        $map['id'] = array('not in', I('ids'));
        $cache = $m->where($map)->select();
        $this->assign('cache', $cache);
        $this->ajaxReturn($this->fetch());
    }

    //CMS后台SKU查找带回模板
    public function skuFindback()
    {
        if (IS_AJAX) {
            $m = M('Shop_skuattr');
            $id = I('id');
            $this->assign('findback', $findback);
            $map['id'] = $id;
            $cache = $m->where($map)->limit(1)->find();
            $this->assign('cache', $cache);
            $items = M('Shop_skuattr_item')->where('pid=' . $id)->select();
            $this->assign('items', $items);
            $this->ajaxReturn($this->fetch());
        } else {
            utf8error('非法访问！');
        }
    }

    //CMS后台广告分组
    public function ads()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '商城首页',
                'url' => U('Admin/Shop/index'),
            ),
            '1' => array(
                'name' => '商城广告',
                'url' => U('Admin/Shop/ads'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //绑定搜索条件与分页
        $m = M('Shop_ads');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $name = I('name') ? I('name') : '';
        if ($name) {
            $map['name'] = array('like', "%$name%");
            $this->assign('name', $name);
        }
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $cache = $m->where($map)->page($p, $psize)->select();
        foreach ($cache as $k => $v) {
            $listpic = $this->getPic($v['pic']);
            $cache[$k]['imgurl'] = $listpic['imgurl'];
        }
        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '商城广告', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }

    //CMS后台广告设置
    public function adsSet()
    {
        $id = I('id');
        $m = M('Shop_ads');
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '商城首页',
                'url' => U('Admin/Shop/index'),
            ),
            '1' => array(
                'name' => '商城广告',
                'url' => U('Admin/Shop/ads'),
            ),
            '2' => array(
                'name' => '广告设置',
                'url' => $id ? U('Admin/Shop/adsSet', array('id' => $id)) : U('Admin/Shop/adsSet'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //处理POST提交
        if (IS_POST) {
            //die('aa');
            $data = I('post.');
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

    public function adsDel()
    {
        $id = $_GET['id']; //必须使用get方法
        $m = M('Shop_ads');
        if (!id) {
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

    //CMS后台商城订单
    public function order()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '商城首页',
                'url' => U('Admin/Shop/index'),
            ),
            '1' => array(
                'name' => '订单管理',
                'url' => U('Admin/Shop/order'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        $status = I('status');
        if ($status || $status == '0') {
            $map['o.status'] = $status;
            //交易满7天
            if ($status == 8) {
                $map['o.status'] = 3;
                $seven = time() - 604800;
                $map['o.ctime'] = array('elt', $seven);
            }
            // 当天所有订单，零点算起
            if ($status == 9) {
                unset($map['status']);
                $today = strtotime(date("Y-m-d"));
                $map['o.ctime'] = array('egt', $today);
                //echo $today;
            }
            if ($status == 10) {
                $map['o.status'] = 8;
            }
        }
        $this->assign('status', $status);
        $delivery = I('delivery');
        if($delivery) {
        	$map['o.delivery'] = $delivery;
        }

        $this->assign('delivery', $delivery);
        $date =  I('date') ? I('date') : '';
        if ($date) {
        	$timeArr = explode(" - ", $date);
        	$map['o.ctime'] = array('between',array(strtotime($timeArr[0]),strtotime($timeArr[1])+60*60*24));
        	$this->assign('date', $date);
        }
        //绑定搜索条件与分页
        $m = M('Shop_order');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $name = I('name') ? I('name') : '';
        if ($name) {
            //订单号绑定
            $map['o.oid|o.vipmobile'] = array('like', "%$name%");
            $this->assign('name', $name);
        }
        $map['o.type'] =0;
        $user=self::$CMS['user'];
        if($user["user_type"]==1){
            $map["o.adminid"]=$user["id"];
        }
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $cache = $m->alias('o')
        ->join('LEFT JOIN '.C('DB_PREFIX').'vip as v on o.vipid=v.id')
        ->where($map)
        ->page($p, $psize)
        ->field('o.*,v.nickname')
        ->order('ctime desc')
        ->select();
        foreach($cache as $k => $v) {
        	if ($v['items']) {
        		$cache[$k]['items'] = unserialize($v['items']);
        	}
        }
        $count = $m->alias('o')
        ->join('LEFT JOIN '.C('DB_PREFIX').'vip as v on o.vipid=v.id')
        ->where($map)
        ->count();
        $this->getPage($count, $psize, 'App-loader', '商城订单', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }

    // Admin后台订单当天报表
    public function orderReport()
    {
        // Prepare Data
        $mgoods = M('Shop_goods');
        $msku = M('Shop_goods_sku');
        $morder = D('shop_order');
        $data = $morder->today();

        $goods = array();
        $sku = array();
        $temp = $mgoods->select();
        foreach ($temp as $k => $v) {
            $goods[$v['id']] = $v;
        }
        $temp = $msku->select();
        foreach ($temp as $k => $v) {
            $sku[$v['id']] = $v;
        }
        $this->assign('goods', $goods);
        $this->assign('sku', $sku);
        $this->assign('cache', $data);
        $this->display();
    }

    //CMS后台Order详情
    public function orderDetail()
    {
        $id = I('id');
        $m = M('Shop_order');
        $mlog = M('Shop_order_log');
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '商城首页',
                'url' => U('Admin/Shop/index'),
            ),
            '1' => array(
                'name' => '商城订单',
                'url' => U('Admin/Shop/order'),
            ),
            '2' => array(
                'name' => '订单详情',
                'url' => $id ? U('Admin/Shop/orderDetail', array('id' => $id)) : U('Admin/Shop/orderDetail'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        $cache = $m->where('id=' . $id)->find();
        //坠入vip
        $vip = M('vip')->where('id=' . $cache['vipid'])->find();
        $this->assign('vip', $vip);
        $cache['items'] = unserialize($cache['items']);
        $log = $mlog->where('oid=' . $cache['id'])->select();
        $fxlog = M('Fx_syslog')->where('oid=' . $cache['id'])->select();
        //微信卡劵
        $wxcache = $m->where(array('id'=>$id,'goodstype'=>'coupon'))->find();
        $wxkjorder = M('Vip_coupon')->where(array('vipid'=>$wxcache['vipid']))->find();
        if($cache["adminid"]){
           $adminUser= M("user")->where(array("id"=>$cache["adminid"]))->find();
           if($adminUser){
               $cache["adminUser"]=$adminUser;
           }
        }
        if($cache["deliveryid"]){
            $delivery=M("vip")->where(array("id"=>$cache["deliveryid"]))->find();
            if($delivery){
                if(!($cache["delivery_fee"]>0)){
                    $deliveryman=M("deliveryman")->where(array("vipid"=>$cache["deliveryid"]))->find();
                    if($deliveryman["fee"]){
                        $cache["delivery_fee"]=round($cache["totalprice"]*$deliveryman["fee"]/100,2);
                    }
                }
                $cache["delivery"]=$delivery;
            }
        }
        $this->assign('wxkjorder', $wxkjorder);
        $this->assign('log', $log);
        $this->assign('fxlog', $fxlog);
        $this->assign('cache', $cache);
        $shopset = self::$SHOP['set'];
        $this->assign('shopset', $shopset);
        $this->display();
    }

    //发货快递
    public function orderFhkd()
    {
        $map['id'] = I('id');
        $cache = M('Shop_order')->where($map)->find();
        $this->assign('cache', $cache);
        $express = D('Express');
        $express = $express->select();
        $this->assign("express", $express);
        $mb = $this->fetch();
        $this->ajaxReturn($mb);
    }

    public function orderFhkdSave()
    {
        $data = I('post.');
        if (!$data) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取数据！';
        }
        $data['changetime'] = time();
        $re = M('Shop_order')->where('id=' . $data['id'])->save($data);
        if (FALSE !== $re) {
            $info['status'] = 1;
            $info['msg'] = '操作成功！';
        } else {
            $info['status'] = 0;
            $info['msg'] = '操作失败！';
        }
        $this->ajaxReturn($info);
    }

    //订单改价
    public function orderChange()
    {
        $map['id'] = I('id');
        $cache = M('Shop_order')->where($map)->find();
        $this->assign('cache', $cache);
        $mb = $this->fetch();
        $this->ajaxReturn($mb);
    }

    public function orderChangeSave()
    {
        $data = I('post.');
        if (!$data) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取数据！';
        }
        $data['changetime'] = time();
        $data['oid'] = date('YmdHis') . '-' . $data['id'];
        $re = M('Shop_order')->where('id=' . $data['id'])->save($data);
        $mlog = M('Shop_order_log');
        if (FALSE !== $re) {
            $log['oid'] = $cache['oid'];
            $log['msg'] = '订单价格改为' . $data['payprice'] . '-成功';
            $log['ctime'] = time();
            $rlog = $mlog->add($log);
            $info['status'] = 1;
            $info['msg'] = '操作成功！';
        } else {
            $info['status'] = 0;
            $info['msg'] = '操作失败！';
        }
        $this->ajaxReturn($info);
    }

    //订单关闭
    public function orderClose()
    {
        $map['id'] = I('id');
        $cache = M('Shop_order')->where($map)->find();
        $this->assign('cache', $cache);
        $mb = $this->fetch();
        $this->ajaxReturn($mb);
    }

    public function orderCloseSave()
    {
        $data = I('post.');
        if (!$data) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取数据！';
        }
        $m = M('Shop_order');
        $mlog = M('Shop_order_log');
        $mslog = M('Shop_order_syslog');
        $cache = $m->where('id=' . $data['id'])->find();
        switch ($cache['status']) {
            case '1':
                $data['status'] = 6;
                $data['closetime'] = time();
                $re = $m->where('id=' . $data['id'])->save($data);
                $data_msg['pids'] = $cache['vipid'];
                $data_msg['title'] = "关闭订单成功";
                $data_msg['content'] = '您的订单'.$cache['oid'].'关闭成功。感谢您的支持！';
                $data_msg['ctime'] = time();
                $rmsg = M('Vip_message')->add($data_msg);
                if (FALSE !== $re) {
                    //前端LOG
                    $log['oid'] = $cache['id'];
                    $log['msg'] = '未支付订单关闭成功';
                    $log['ctime'] = time();
                    $rlog = $mlog->add($log);
                    //后端LOG
                    $log['type'] = 6;
                    $log['paytype'] = $cache['paytype'];
                    $rslog = $mslog->add($log);

                    $info['status'] = 1;
                    $info['msg'] = '关闭未支付订单成功！';
                } else {
                    //前端LOG
                    $log['oid'] = $cache['id'];
                    $log['msg'] = '未支付订单关闭失败';
                    $log['ctime'] = time();
                    $rlog = $mlog->add($log);
                    //后端LOG
                    $log['type'] = -1;
                    $log['paytype'] = $cache['paytype'];
                    $rslog = $mslog->add($log);
                    $info['status'] = 0;
                    $info['msg'] = '关闭未支付订单失败！';
                }
                $this->ajaxReturn($info);
                break;
            case '2':
                //已支付订单跳转到这里处理
                $this->orderClosePay($cache, $data);
                break;
            case '8':
                $this->orderClosePay($cache, $data);
                break;
            default:
                $info['status'] = 0;
                $info['msg'] = '只有未付款和已付款订单可以关闭!';
                $this->ajaxReturn($info);
                break;
        }

    }

    //已支付订单退款
    public function orderClosePay($cache, $data)
    {
        //关闭订单时不再处理库存
        $m = M('Shop_order');
        $mvip = M('Vip');
        $mlog = M('Shop_order_log');
        $mslog = M('Shop_order_syslog');
        if (!$cache['ispay']) {
            $info['status'] = 0;
            $info['msg'] = '订单支付状态异常！请重试或联系技术！';
            $this->ajaxReturn($info);
        }
        //抓取会员数据
        $vip = $mvip->where('id=' . $cache['vipid'])->find();
        if (!$vip) {
            $info['status'] = 0;
            $info['msg'] = '会员数据获取异常！请重试或联系技术！';
            $this->ajaxReturn($info);
        }
        //支付金额
        $payprice = $cache['payprice'];
        //全部退款至余额
        $data['status'] = 6;
        $data['closetime'] = time();
        $re = $m->where('id=' . $cache['id'])->save($data);
        if (FALSE !== $re) {
            $log['oid'] = $cache['id'];
            $log['msg'] = '订单关闭-成功';
            $log['ctime'] = time();
            $rlog = $mlog->add($log);
            $info['status'] = 1;
            $info['msg'] = '关闭订单成功！';
            if ($cache['ispay']) {
                $mm = $vip['money'] + $payprice;
                $jifen = $m -> where('id=' . $cache['id']) -> getField('integral');
                $jiVip = $mvip->where('id=' . $cache['vipid'])->setInc('score', $jifen);
                $rvip = $mvip->where('id=' . $cache['vipid'])->setField('money', $mm);
                if ($rvip) {
                	//资金流水记录
                	$flow['vipid'] = $vip['id'];
                	$flow['openid'] = $vip['openid'];
                	$flow['nickname'] = $vip['nickname'];
                	$flow['mobile'] = $vip['mobile'];
                	$flow['money'] = $payprice;
                	$flow['paytype'] = '';
                	$flow['balance'] = $mm;
                	$flow['type'] = 9;
                	$flow['oid'] = $cache['oid'];
                	$flow['ctime'] = time();
                	$flow['remark'] = '订单退款，自动退款到用户余额';
                	$rflow = $mlog->add($flow);
                    //前端LOG
                    $log['oid'] = $cache['id'];
                    $log['msg'] = '自动退款' . $payprice . '元至用户余额-成功';
                    $log['ctime'] = time();
                    $rlog = $mlog->add($log);
                    $log['type'] = 6;
                    $log['paytype'] = $cache['paytype'];
                    $rslog = $mslog->add($log);
                    //后端LOG
                    $info['status'] = 1;
                    $info['msg'] = '关闭订单成功！自动退款' . $payprice . '元至用户余额成功!';
                    $data_msg['pids'] = $cache['vipid'];
                    $data_msg['title'] = "关闭订单成功,已自动退款";
                    $data_msg['content'] = '您的订单'.$cache['oid'].'关闭成功。金额已自动退到您的账户余额。感谢您的支持！';
                    $data_msg['ctime'] = time();
                    $rmsg = M('Vip_message')->add($data_msg);
                } else {
                    //前端LOG
                    $log['oid'] = $cache['id'];
                    $log['msg'] = '自动退款' . $payprice . '元至用户余额-失败!请联系客服!';
                    $log['ctime'] = time();
                    $rlog = $mlog->add($log);
                    //后端LOG
                    $log['type'] = -1;
                    $log['paytype'] = $cache['paytype'];
                    $rslog = $mslog->add($log);
                    $info['status'] = 1;
                    $info['msg'] = '关闭订单成功！自动退款' . $payprice . '元至用户余额失败!请联系技术！';
                }
                $yuanyin = $m -> where('id=' . $cache['id']) -> getField('tuihuomsg');
                $data = array();
                $data['touser'] = $vip['openid'];
                $data['template_id'] = 'TjfH9p63y7iRgCn9Q73IvMBCS9QhWPFtrjbPiaK3NUg';
                $data['topcolor'] = "#00FF00";
                $data['data'] = array(
                    'first' => array('value' => '您好，您的退款申请已处理'),
                    'reason' => array('value' => $yuanyin),
                    'refund' => array('value' => $payprice),
                    'remark' => array('value' => '请注意查看金额是否有到账！')
                );
                $options['appid'] = self::$SYS['set']['wxappid'];
                $options['appsecret'] = self::$SYS['set']['wxappsecret'];
                $wx = new \Util\Wx\Wechat($options);
                $re = $wx->sendTemplateMessage($data);
            }

        } else {
            $info['status'] = 0;
            $info['msg'] = '关闭订单失败！请重新尝试!';
        }
        $this->ajaxReturn($info);
    }

    //订单发货
    public function orderDeliver()
    {
        $id = I('id');
        if (!$id) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取ID数据！';
        }
        $re = M('Shop_order')->where('id=' . $id)->setField('status', 3);
        $mlog = M('Shop_order_log');
        $mslog = M('Shop_order_syslog');
        $dwechat = D('Wechat');
        if (FALSE !== $re) {
            $log['oid'] = $id;
            $log['msg'] = '订单已发货';
            $log['ctime'] = time();
            $rlog = $mlog->add($log);
            //后端LOG
            $log['type'] = 3;
            $log['paytype'] = $cache['paytype'];
            $rslog = $mslog->add($log);

            // 插入订单发货模板消息=====================
            $order = M('Shop_order')->where('id=' . $id)->find();
            $vip = M('vip')->where(array('id' => $order['vipid']))->find();
            $templateidshort = 'OPENTM201541214';
            $templateid = $dwechat->getTemplateId($templateidshort);

            if ($templateid) { // 存在才可以发送模板消息
                $data = array();
                $data['touser'] = $vip['openid'];
                $data['template_id'] = $templateid;
                $data['topcolor'] = "#0000FF";
                $data['data'] = array(
                    'first' => array('value' => '您好，您的商品已发货'),
                    'keyword1' => array('value' => $order['oid']),
                    'keyword2' => array('value' => $order['fahuokd']),
                    'keyword3' => array('value' => $order['fahuokdnum']),
                    'keyword4' => array('value' => $order['payprice']),
                    'keyword5' => array('value' => $order['vipaddress']),
                    'remark' => array('value' => '商品已发出请注意查收')
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

    //订单批量发货
    public function orderDeliverAll()
    {
        $arr = array_filter(explode(',', $_GET['id'])); //必须使用get方法
        if (!$arr) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取ID数据！';
            $this->ajaxReturn($info);
        }
        $m = M('Shop_order');
        $mlog = M('Shop_order_log');
        $mslog = M('Shop_order_syslog');
        // ==========================================================
        $dwechat = D('Wechat');
        $options['appid'] = self::$SYS['set']['wxappid'];
        $options['appsecret'] = self::$SYS['set']['wxappsecret'];
        $wx = new \Util\Wx\Wechat($options);
        // ==========================================================
        $err = TRUE;
        foreach ($arr as $k => $v) {
            $old = $m->where('id=' . $v)->find();
            if ($old['status'] == 2) {
                $re = $m->where('id=' . $old['id'])->setField('status', 3);
                if (FALSE !== $re) {
                    $log['oid'] = $old['id'];
                    $log['msg'] = '订单已发货';
                    $log['ctime'] = time();
                    $rlog = $mlog->add($log);
                    //后端LOG
                    $log['type'] = 3;
                    $log['paytype'] = $cache['paytype'];
                    $rslog = $mslog->add($log);
                    // 插入订单发货模板消息=====================
                    $vip = M('vip')->where(array('id' => $old['vipid']))->find();
                    $templateidshort = 'OPENTM201541214';
                    $templateid = $dwechat->getTemplateId($templateidshort);
                    if ($templateid) { // 存在才可以发送模板消息
                        $data = array();
                        $data['touser'] = $vip['openid'];
                        $data['template_id'] = $templateid;
                        $data['topcolor'] = "#0000FF";
                        $data['data'] = array(
                            'first' => array('value' => '您好，您的订单已发货'),
                            'keyword1' => array('value' => $old['oid']),
                            'keyword2' => array('value' => $old['fahuokd']),
                            'keyword3' => array('value' => $old['fahuokdnum']),
                            'remark' => array('value' => '')
                        );
                        $re = $wx->sendTemplateMessage($data);
                    }
                    // 插入订单发货模板消息结束=================
                } else {
                    $err = FALSE;
                }
            }
        }
        if ($err) {
            $info['status'] = 1;
            $info['msg'] = '批量发货成功！';
        } else {
            $info['status'] = 0;
            $info['msg'] = '批量发货可能有部分失败，请刷新后重新尝试！';
        }

        $this->ajaxReturn($info);
    }

    //完成订单
    public function orderSuccess()
    {
        $id = I('id');
        if (!$id) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取ID数据！';
            $this->ajaxReturn($info);
        }
        //判断商城配置
        if (!self::$CMS['shopset']) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取商城配置信息！';
            $this->ajaxReturn($info);
        }
        //判断会员配置
        if (!self::$CMS['vipset']) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取会员配置信息！';
            $this->ajaxReturn($info);
        }
        //分销流程介入
        $m = M('Shop_order');
        $map['id'] = $id;
        $cache = $m->where($map)->find();
        if (!$cache) {
            $info['status'] = 0;
            $info['msg'] = '操作失败！';
            $this->ajaxReturn($info);
        }
        if ($cache['delivery'] == 'since' && ($cache['status'] <2 || $cache['status'] >3)) {
            $info['status'] = 0;
            $info['msg'] = '操作失败！';
            $this->ajaxReturn($info);
        }
        if ($cache['delivery'] != 'since' && $cache['status'] != 3) {
            $info['status'] = 0;
            $info['msg'] = '操作失败！';
            $this->ajaxReturn($info);
        }
        //追入会员信息
        $vip = M('Vip')->where('id=' . $cache['vipid'])->find();
        if (!$vip) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取此订单的会员信息！';
            $this->ajaxReturn($info);
        }
        $cache['etime'] = time(); //交易完成时间
        $cache['status'] = 5;
        $rod = $m->save($cache);
        if (FALSE !== $rod) {
            //修改会员账户金额、经验、积分、等级
            $data_vip['id'] = $cache['vipid'];
            $data_vip['score'] = array('exp', 'score+' . round($cache['payprice'] * self::$CMS['vipset']['cz_score'] / 100));
            if (self::$CMS['vipset']['cz_exp'] > 0) {
                $data_vip['exp'] = array('exp', 'exp+' . round($cache['payprice'] * self::$CMS['vipset']['cz_exp'] / 100));
                $data_vip['cur_exp'] = array('exp', 'cur_exp+' . round($cache['payprice'] * self::$CMS['vipset']['cz_exp'] / 100));
                $level = $this->getLevel($vip['cur_exp'] + round($cache['payprice'] * self::$CMS['vipset']['cz_exp'] / 100));
                $data_vip['levelid'] = $level['levelid'];
                /*
                if (self::$SHOP['set']['isfx']) {
	                //会员分销统计字段
	                //会员购买一次变成分销商
	                $data_vip['isfx'] = 1;
                }
                */
                //会员合计支付
                $data_vip['total_buy'] = $data_vip['total_buy'] + $cache['payprice'];
            }
            $re = M('vip')->save($data_vip);
            if (FALSE === $re) {
                $info['status'] = 0;
                $info['msg'] = '更新订单关联会员信息失败！';
                $this->ajaxReturn($info);
            }
            if (self::$SHOP['set']['isfx']) {
            	handleCommission($cache, $id);//发放分销佣金
            }
            $mlog = M('Shop_order_log');
            $dlog['oid'] = $cache['id'];
            $dlog['msg'] = '确认收货,交易完成。';
            $dlog['ctime'] = time();
            $rlog = $mlog->add($dlog);

            //后端日志
            $mlog = M('Shop_order_syslog');
            $dlog['oid'] = $cache['id'];
            $dlog['msg'] = '交易完成-后台点击';
            $dlog['type'] = 5;
            $dlog['paytype'] = $cache['paytype'];
            $dlog['ctime'] = time();
            $rlog = $mlog->add($dlog);
            //$this->success('交易已完成，感谢您的支持！');
            $info['status'] = 1;
            $info['msg'] = '后台确认收货操作完成！';
        } else {
            //后端日志
            $mlog = M('Shop_order_syslog');
            $dlog['oid'] = $cache['id'];
            $dlog['msg'] = '确认收货失败';
            $dlog['type'] = -1;
            $dlog['paytype'] = $cache['paytype'];
            $dlog['ctime'] = time();
            $rlog = $mlog->add($dlog);
            //$this->error('确认收货失败，请重新尝试！');
            $info['status'] = 0;
            $info['msg'] = '后台确认收货操作失败，请重新尝试！';
        }
        $this->ajaxReturn($info);
    }
    
    //拒绝订单退款
    public function orderRejectTuikuan()
    {
    	$map['id'] = I('id');
    	$cache = M('Shop_order')->where($map)->find();
    	$this->assign('cache', $cache);
    	$mb = $this->fetch();
    	$this->ajaxReturn($mb);
    }
    
    //拒绝订单退款
    public function orderRejectTuikuanSave()
    {
    	$data = I('post.');
    	if (!$data) {
    		$info['status'] = 0;
    		$info['msg'] = '未正常获取数据！';
    		$this->ajaxReturn($info);
    	}
    	$m = M('Shop_order');
    	$mlog = M('Shop_order_log');
    	$mslog = M('Shop_order_syslog');
    	$mvip = M('Vip');
    	$cache = $m->where('id=' . $data['id'])->find();
    	if (!$cache) {
    		$info['status'] = 0;
    		$info['msg'] = '未正常获取订单数据！';
    		$this->ajaxReturn($info);
    	}
    	if (!$cache) {
    		$info['status'] = 0;
    		$info['msg'] = '未正常获取此订单数据！';
    		$this->ajaxReturn($info);
    	}
    	//追入会员信息
    	$vip = $mvip->where('id=' . $cache['vipid'])->find();
    	if (!$vip) {
    		$info['status'] = 0;
    		$info['msg'] = '未正常获取此订单的会员信息！';
    		$this->ajaxReturn($info);
    	}
    	switch ($cache['status']) {
    		case '8':
    			$data['status'] = 2;
    			$data['rejecttuikuantime'] = time();
    			if (!$data['rejecttuikuanmsg']) {
    				$info['status'] = 0;
    				$info['msg'] = '拒绝退货原因不能为空！';
    				$this->ajaxReturn($info);
    			}
    			$re = $m->where('id=' . $data['id'])->save($data);
    			if (FALSE !== $re) {
    				//前端LOG
    				$log['oid'] = $cache['id'];
    				$log['msg'] = '商家拒绝退款，理由：'.$data['rejecttuikuanmsg'];
    				$log['ctime'] = time();
    				$rlog = $mlog->add($log);
    				$log['type'] = 8;
    				$log['paytype'] = $cache['paytype'];
    				$rslog = $mslog->add($log);
    				//后端LOG
    				$info['status'] = 1;
    				$info['msg'] = '拒绝退款成功！';
    				  				
    				$data_msg['pids'] = $cache['vipid'];
    				$data_msg['title'] = "商家拒绝了您的退款申请";
    				$data_msg['content'] = '您的订单'.$cache['oid'].'退款申请被商家拒绝，理由：'.$data['rejecttuihuomsg'].'。如有疑问，请联系客服！';
    				$data_msg['ctime'] = time();
    				$rmsg = M('Vip_message')->add($data_msg);
    			}
    			$this->ajaxReturn($info);
    			break;
    		default:
    			$info['status'] = 0;
    			$info['msg'] = '只有申请退款订单可以拒绝!';
    			$this->ajaxReturn($info);
    			break;
    	}
    }
    //拒绝订单退货
    public function orderRejectTuihuo()
    {
    	$map['id'] = I('id');
    	$cache = M('Shop_order')->where($map)->find();
    	$this->assign('cache', $cache);
    	$mb = $this->fetch();
    	$this->ajaxReturn($mb);
    }
    
    //拒绝订单退货
    public function orderRejectTuihuoSave()
    {
    	$data = I('post.');
    	if (!$data) {
    		$info['status'] = 0;
    		$info['msg'] = '未正常获取数据！';
    		$this->ajaxReturn($info);
    	}
    	$m = M('Shop_order');
    	$mlog = M('Shop_order_log');
    	$mslog = M('Shop_order_syslog');
    	$mvip = M('Vip');
    	$cache = $m->where('id=' . $data['id'])->find();
    	if (!$cache) {
    		$info['status'] = 0;
    		$info['msg'] = '未正常获取订单数据！';
    		$this->ajaxReturn($info);
    	}
    	if (!$cache) {
    		$info['status'] = 0;
    		$info['msg'] = '未正常获取此订单数据！';
    		$this->ajaxReturn($info);
    	}
    	//追入会员信息
    	$vip = $mvip->where('id=' . $cache['vipid'])->find();
    	if (!$vip) {
    		$info['status'] = 0;
    		$info['msg'] = '未正常获取此订单的会员信息！';
    		$this->ajaxReturn($info);
    	}
    	switch ($cache['status']) {
    		case '4':
    			$data['status'] = 3;
    			$data['rejecttuihuotime'] = time();
    			if (!$data['rejecttuihuomsg']) {
    				$info['status'] = 0;
    				$info['msg'] = '拒绝退货原因不能为空！';
    				$this->ajaxReturn($info);
    			}
    			$re = $m->where('id=' . $data['id'])->save($data);
    			if (FALSE !== $re) {
    				//前端LOG
    				$log['oid'] = $cache['id'];
    				$log['msg'] = '商家拒绝退货，理由：'.$data['rejecttuihuomsg'];
    				$log['ctime'] = time();
    				$rlog = $mlog->add($log);
    				$log['type'] = 7;
    				$log['paytype'] = $cache['paytype'];
    				$rslog = $mslog->add($log);
    				//后端LOG
    				$info['status'] = 1;
    				$info['msg'] = '拒绝退货成功！';
    				
    				$data_msg['pids'] = $cache['vipid'];
    				$data_msg['title'] = "商家拒绝了您的退货申请";
    				$data_msg['content'] = '您的订单'.$cache['oid'].'退货申请被商家拒绝，理由：'.$data['rejecttuihuomsg'].'。如有疑问，请联系客服！';
    				$data_msg['ctime'] = time();
    				$rmsg = M('Vip_message')->add($data_msg);
    			}
    			$this->ajaxReturn($info);
    			break;
    		default:
    			$info['status'] = 0;
    			$info['msg'] = '只有申请退货订单可以拒绝!';
    			$this->ajaxReturn($info);
    			break;
    	}	
    }
    //订单退货
    public function orderTuihuo()
    {
        $map['id'] = I('id');
        $cache = M('Shop_order')->where($map)->find();
        $this->assign('cache', $cache);
        $mb = $this->fetch();
        $this->ajaxReturn($mb);
    }

    public function orderTuihuoSave()
    {
        $data = I('post.');
        if (!$data) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取数据！';
            $this->ajaxReturn($info);
        }
        $m = M('Shop_order');
        $mlog = M('Shop_order_log');
        $mslog = M('Shop_order_syslog');
        $mvip = M('Vip');
        $cache = $m->where('id=' . $data['id'])->find();
        if (!$cache) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取订单数据！';
            $this->ajaxReturn($info);
        }
        if (!$cache) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取此订单数据！';
            $this->ajaxReturn($info);
        }
        //追入会员信息
        $vip = $mvip->where('id=' . $cache['vipid'])->find();
        if (!$vip) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取此订单的会员信息！';
            $this->ajaxReturn($info);
        }
        switch ($cache['status']) {
            case '4':
                $data['status'] = 7;
                $data['tuihuotime'] = time();
                if (!$data['tuihuoprice']) {
                    $info['status'] = 0;
                    $info['msg'] = '退货金额不能为空！';
                    $this->ajaxReturn($info);
                }
                $re = $m->where('id=' . $data['id'])->save($data);
                if (FALSE !== $re) {
                    $vip['money'] = $vip['money'] + $data['tuihuoprice'];
                    $rvip = $mvip->save($vip);
                    if ($rvip !== FALSE) {
                    	//资金流水记录
                    	$flow['vipid'] = $vip['id'];
                    	$flow['openid'] = $vip['openid'];
                    	$flow['nickname'] = $vip['nickname'];
                    	$flow['mobile'] = $vip['mobile'];
                    	$flow['money'] = $data['tuihuoprice'];
                    	$flow['paytype'] = '';
                    	$flow['balance'] = $vip['money'];
                    	$flow['type'] = 9;
                    	$flow['oid'] = $cache['oid'];
                    	$flow['ctime'] = time();
                    	$flow['remark'] = '商品退货，自动退款到用户余额';
                    	$rflow = $mlog->add($flow);
                    	
                        //前端LOG
                        $log['oid'] = $cache['id'];
                        $log['msg'] = '成功退货，自动退款' . $data['tuihuoprice'] . '元至用户余额-成功';
                        $log['ctime'] = time();
                        $rlog = $mlog->add($log);
                        $log['type'] = 6;
                        $log['paytype'] = $cache['paytype'];
                        $rslog = $mslog->add($log);
                        //后端LOG
                        $info['status'] = 1;
                        $info['msg'] = '关闭订单成功！自动退款' . $data['tuihuoprice'] . '元至用户余额成功!';
                    } else {
                        //前端LOG
                        $log['oid'] = $cache['id'];
                        $log['msg'] = '成功退货，自动退款' . $data['tuihuoprice'] . '元至用户余额-失败!请联系客服!';
                        $log['ctime'] = time();
                        $rlog = $mlog->add($log);
                        //后端LOG
                        $log['type'] = -1;
                        $log['paytype'] = $cache['paytype'];
                        $rslog = $mslog->add($log);
                        $info['status'] = 1;
                        $info['msg'] = '成功退货，自动退款' . $data['tuihuoprice'] . '元至用户余额失败!请联系技术！';
                    }
                    $data = array();
                    $data['touser'] = $vip['openid'];
                    $data['template_id'] = 'TjfH9p63y7iRgCn9Q73IvMBCS9QhWPFtrjbPiaK3NUg';
                    $data['topcolor'] = "#00FF00";
                    $data['data'] = array(
                        'first' => array('value' => '您好，您的退货申请已处理'),
                        'reason' => array('value' => $cache['tuihuomsg']),
                        'refund' => array('value' => $cache['payprice']),
                        'remark' => array('value' => '请注意查看金额是否有到账！')
                    );
                    $options['appid'] = self::$SYS['set']['wxappid'];
                    $options['appsecret'] = self::$SYS['set']['wxappsecret'];
                    $wx = new \Util\Wx\Wechat($options);
                    $re = $wx->sendTemplateMessage($data);

                } else {
                    //前端LOG
                    $log['oid'] = $cache['id'];
                    $log['msg'] = '订单退货失败';
                    $log['ctime'] = time();
                    $rlog = $mlog->add($log);
                    //后端LOG
                    $log['type'] = -1;
                    $log['paytype'] = $cache['paytype'];
                    $rslog = $mslog->add($log);
                    $info['status'] = 0;
                    $info['msg'] = '订单退货失败！';
                }
                $this->ajaxReturn($info);
                break;
            default:
                $info['status'] = 0;
                $info['msg'] = '只有未付款和已付款订单可以关闭!';
                $this->ajaxReturn($info);
                break;
        }
        //$info['status']=0;
        //$info['msg']='通讯失败，请重新尝试!';
        //$this->ajaxReturn($info);

    }

    public function orderExport()
    {
    	
        $status = I('status');
        $map['status'] = $status;
        $delivery = I('delivery');
        if($delivery) {
        	$map['delivery'] = $delivery;
        }
        $name = I('name') ? I('name') : '';
        if ($name) {
        	$map['oid|vipmobile'] = array('like', "%$name%");
        }
        $map['type'] =0;
        $date =  I('date') ? I('date') : '';
        if ($date) {
        	$timeArr = explode(" - ", $date);
        	$map['ctime'] = array('between',array(strtotime($timeArr[0]),strtotime($timeArr[1])+60*60*24));
        }
        switch ($status) {
            case 0:
                $tt = "交易取消";
                break;
            case 1:
                $tt = "未付款";
                break;
            case 2:
                $tt = "已付款";
                break;
            case 3:
                $tt = "已发货";
                break;
            case 4:
                $tt = "退货中";
                break;
            case 7:
                $tt = "退货完成";
                break;
            case 5:
                $tt = "交易成功";
                break;
            case 6:
                $tt = "交易关闭";
                break;
        }
        $user=self::$CMS['user'];
        if($user["user_type"]>0){
            $map["adminid"]=$user["id"];
        }
        $data = M('Shop_order')->where($map)->select();
        //dump($data);
        //die();
        foreach ($data as $k => $v) {
            //过滤字段
            unset($data[$k]['sid']);
            unset($data[$k]['ispay']);
            unset($data[$k]['kfmsg']);
            unset($data[$k]['vipxqname']);
            unset($data[$k]['vipxqid']);
            unset($data[$k]['ntime']);
            unset($data[$k]['dtime']);
            unset($data[$k]['etime']);
            unset($data[$k]['aliaccount']);
            unset($data[$k]['goodstype']);
            unset($data[$k]['integral']);  
            unset($data[$k]['fahuokdcode']); 
            unset($data[$k]['type']); 
            unset($data[$k]['groupid']); 
            unset($data[$k]['delivery']); 
            unset($data[$k]['sinceid']);
            unset($data[$k]['pickuptime']); 
            switch ($v['status']) {
                case 0:
                    $data[$k]['status'] = "交易取消";
                    break;
                case 1:
                    $data[$k]['status'] = "未付款";
                    break;
                case 2:
                    $data[$k]['status'] = "已付款";
                    break;
                case 3:
                    $data[$k]['status'] = "已发货";
                    break;
                case 4:
                    $data[$k]['status'] = "退货中";
                    break;
                case 7:
                    $data[$k]['status'] = "退货完成";
                    break;
                case 5:
                    $data[$k]['status'] = "交易成功";
                    break;
                case 6:
                    $data[$k]['status'] = "交易关闭";
                    break;
            }
            $data[$k]['ctime'] = date('Y-m-d H:i:s', $v['ctime']);
            $data[$k]['paytime'] = $v['paytime'] ? date('Y-m-d H:i:s', $v['paytime']) : '无';
            $data[$k]['changetime'] = $v['changetime'] ? date('Y-m-d H:i:s', $v['changetime']) : '无';
            $data[$k]['closetime'] = $v['closetime'] ? date('Y-m-d H:i:s', $v['closetime']) : '无';
            $data[$k]['tuihuosqtime'] = $v['tuihuosqtime'] ? date('Y-m-d H:i:s', $v['tuihuosqtime']) : '无';
            $data[$k]['tuihuotime'] = $v['tuihuotime'] ? date('Y-m-d H:i:s', $v['tuihuotime']) : '无';
            $tmpitems = unserialize($v['items']);
            $str = "";
            foreach ($tmpitems as $vv) {

                $tempattr=$vv['skuattr']?$vv['skuattr']:"无";
                $vt = '产品名称：' . $vv['name'] . '  属性：' . $tempattr. '  数量：' . $vv['num'] . '  单价：' . $vv['price'];
                if($vv["goodsid"]){
                    $vt=$vt."元/".M("shop_goods")->where(array("id"=>$vv["goodsid"]))->getField("unit");
                }
                $str = $str . $vt . ' / ';
            }
            $data[$k]['items'] = $str;
            
        }
        //dump($data);
        //die();
        $title = array('商品ID', '订单编号', '代金卷ID', '订单总价', '商品总数', '支付价格', '支付类型', '支付时间', '邮费', '会员ID', '会员微信ID', '收货姓名', '收货电话', '收货地址', '购买留言', '订单创建时间', '改价时间', '改价原因', '改价操作员', '关闭时间', '关闭原因', '关闭操作员', '退货退款金额', '退货退款申请时间', '退货退款完成时间', '退货快递公司', '退货快递单号', '退货原因', '退货操作员', '订单状态', '发货快递', '发货快递号', '订单商品详情');
        $this->exportexcel($data, $title, $tt . '订单' . date('Y-m-d H:i:s', time()));
    }
    
    public function sinceOrderExport()
    {

        $status = I('status');
        $map['status'] = $status;
        $map['delivery'] = 'since';
        $name = I('name') ? I('name') : '';
        if ($name) {
            $map['oid'] = array('eq', $name);
        }
        $map['type'] = 0;
        $date =  I('date') ? I('date') : '';
        if ($date) {
            $timeArr = explode(" - ", $date);
            $map['ctime'] = array('between',array(strtotime($timeArr[0]),strtotime($timeArr[1])+60*60*24));
        }
        $type = I('type', 0 , 'intval');
        switch ($type) {
            case '1'://待发货
                $map['status'] = 2;
                $tt = '待发货';
                break;
            case '2'://到达自提点
                $map['status'] = 3;
                $tt = '到达自提点';
                break;
            case '3'://已自提
                $map['status'] = 5;
                $tt = '已完成';
                break;
            default://全部
                $map['status'] = array('in', array(2,3,5));
                break;
        }
        $user=self::$CMS['user'];
        if($user["user_type"]>0){
            $map["adminid"]=$user["id"];
        }
        $data = M('Shop_order')->where($map)->select();
        //dump($data);
        //die();
         $title = array(
                '订单编号', 
                '订单总价', 
                '商品总数量', 
                '支付金额', 
                '支付方式', 
                '支付时间', 
                '会员ID', 
                '姓名', 
                '电话', 
                '地址', 
                 '订单状态', 
                '下单时间', 
                '订单商品详情', 
                );

         foreach ($data as $k => $v) {
                $mydata[$k]['oid']=$data[$k]['oid'];
                $mydata[$k]['totalprice']=$data[$k]['totalprice'];
                $mydata[$k]['totalnum']=$data[$k]['totalnum'];
                $mydata[$k]['payprice']=$data[$k]['payprice'];               
                if ($data[$k]['paytype'] == 'wxpay') {
                    $mydata[$k]['paytype']='微信支付';
                }
                if ($data[$k]['paytype'] == 'money') {
                    $mydata[$k]['paytype']='余额支付';
                }
                
        $mydata[$k]['paytime'] = $data[$k]['paytime'] ? date('Y-m-d H:i:s', $data[$k]['paytime']) : '无';
                
                $mydata[$k]['vipid']=$data[$k]['vipid'];
                $mydata[$k]['vipname']=$data[$k]['vipname'];
                $mydata[$k]['vipmobile']=$data[$k]['vipmobile'];
                $mydata[$k]['vipaddress']=$data[$k]['vipaddress'];

                switch ($v['status']) {
                case 2:
                    $mydata[$k]['status'] = "待发货";
                    break;
                case 3:
                    $mydata[$k]['status'] = "到达自提点";
                    break;
                case 5:
                    $mydata[$k]['status'] = "已自提";
                    break;              
                   }
                 $mydata[$k]['ctime'] = $data[$k]['ctime'] ? date('Y-m-d H:i:s', $data[$k]['ctime']) : '无';

            $tmpitems = unserialize($v['items']);
            $str = "";
            foreach ($tmpitems as $vv) {

                $tempattr=$vv['skuattr']?$vv['skuattr']:"无";
                $vt = '产品名称：' . $vv['name'] . '  属性：' . $tempattr. '  数量：' . $vv['num'] . '  单价：' . $vv['price'];
                $str = $str . $vt . ' / ';
            }
            $mydata[$k]['items'] = $str;
               
            }

        $this->exportexcel($mydata, $title, '自提'. $tt . '订单' . date('Y-m-d H:i:s', time()));

    	/*
    	$status = I('status');
    	$map['status'] = $status;
    	$map['delivery'] = 'since';
    	$name = I('name') ? I('name') : '';
    	if ($name) {
    		$map['oid'] = array('eq', $name);
    	}
    	$map['type'] = 0;
    	$date =  I('date') ? I('date') : '';
    	if ($date) {
    		$timeArr = explode(" - ", $date);
    		$map['ctime'] = array('between',array(strtotime($timeArr[0]),strtotime($timeArr[1])+60*60*24));
    	}
    	$type = I('type', 0 , 'intval');
    	switch ($type) {
    		case '1'://待发货
    			$map['status'] = 2;
    			$tt = '待发货';
    			break;
    		case '2'://到达自提点
    			$map['status'] = 3;
    			$tt = '到达自提点';
    			break;
    		case '3'://已自提
    			$map['status'] = 5;
    			$tt = '已完成';
    			break;
    		default://全部
    			$map['status'] = array('in', array(2,3,5));
    			break;
    	}
    	$data = M('Shop_order')->where($map)->select();
    	//dump($data);
    	//die();
    	foreach ($data as $k => $v) {
    		//过滤字段
    		unset($data[$k]['sid']);
    		unset($data[$k]['ispay']);
    		unset($data[$k]['kfmsg']);
    		unset($data[$k]['vipxqname']);
    		unset($data[$k]['vipxqid']);
    		unset($data[$k]['ntime']);
    		unset($data[$k]['dtime']);
    		unset($data[$k]['etime']);
    		unset($data[$k]['aliaccount']);
    		unset($data[$k]['goodstype']);
    		unset($data[$k]['integral']);
    		unset($data[$k]['fahuokdcode']);
    		unset($data[$k]['type']);
    		unset($data[$k]['groupid']);
    		unset($data[$k]['delivery']);
    		unset($data[$k]['sinceid']);
    		unset($data[$k]['pickuptime']);
    		switch ($v['status']) {
    			case 2:
    				$data[$k]['status'] = "待发货";
    				break;
    			case 3:
    				$data[$k]['status'] = "到达自提点";
    				break;
    			case 5:
    				$data[$k]['status'] = "已自提";
    				break;    			
    		}
    		$data[$k]['ctime'] = date('Y-m-d H:i:s', $v['ctime']);
    		$data[$k]['paytime'] = $v['paytime'] ? date('Y-m-d H:i:s', $v['paytime']) : '无';
    		$data[$k]['changetime'] = $v['changetime'] ? date('Y-m-d H:i:s', $v['changetime']) : '无';
    		$data[$k]['closetime'] = $v['closetime'] ? date('Y-m-d H:i:s', $v['closetime']) : '无';
    		$data[$k]['tuihuosqtime'] = $v['tuihuosqtime'] ? date('Y-m-d H:i:s', $v['tuihuosqtime']) : '无';
    		$data[$k]['tuihuotime'] = $v['tuihuotime'] ? date('Y-m-d H:i:s', $v['tuihuotime']) : '无';
    		$tmpitems = unserialize($v['items']);
    		$str = "";
    		foreach ($tmpitems as $vv) {
    			$vt = '品名：' . $vv['name'] . ' 属性：' . $vv['skuattr'] . '数量：' . $vv['num'] . '单价：' . $vv['price'];
    			$str = $str . $vt . '/';
    		}
    		$data[$k]['items'] = $str;
    		
    	}
     
    	$title = array('商品ID', '订单编号', '代金卷ID', '订单总价', '商品总数', '支付价格', '支付类型', '支付时间', '邮费', '会员ID', '会员微信ID', '收货姓名', '收货电话', '收货地址', '购买留言', '订单创建时间', '改价时间', '改价原因', '改价操作员', '关闭时间', '关闭原因', '关闭操作员', '退货退款金额', '退货退款申请时间', '退货退款完成时间', '退货快递公司', '退货快递单号', '退货原因', '退货操作员', '订单状态', '发货快递', '发货快递号', '订单商品详情');
    	$this->exportexcel($data, $title, '自提'. $tt . '订单' . date('Y-m-d H:i:s', time()));

        */
    }
    //CMS后台标签列表
    public function label()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '商城首页',
                'url' => U('Admin/Shop/index'),
            ),
            '1' => array(
                'name' => '标签列表',
                'url' => U('Admin/Shop/label'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //绑定搜索条件与分页
        $m = M('Shop_label');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $name = I('name') ? I('name') : '';
        if ($name) {
            $map['name'] = array('like', "%$name%");
            $this->assign("name", $name);
        }
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $cache = $m->where($map)->page($p, $psize)->select();
        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '标签列表', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }

    //CMS后台标签设置
    public function labelSet()
    {
        $id = I('id');
        $m = M('Shop_label');
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '商城首页',
                'url' => U('Admin/Shop/index'),
            ),
            '1' => array(
                'name' => '标签列表',
                'url' => U('Admin/Shop/label'),
            ),
            '2' => array(
                'name' => '标签设置',
                'url' => $id ? U('Admin/Shop/lebelSet', array('id' => $id)) : U('Admin/Shop/lebelSet'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //处理POST提交
        if (IS_POST) {
            $data = I('post.');
            $re = $id ? $m->save($data) : $m->add($data);
            if (FALSE !== $re) {
                $info['status'] = 1;
                $info['msg'] = '设置成功！';
            } else {
                $info['status'] = 0;
                $info['msg'] = '设置失败！';
            }
            $this->ajaxReturn($info);
        } else {
            if ($id) {
                $cache = $m->where('id=' . $id)->find();
                $this->assign('cache', $cache);
            }
            $this->display();
        }
    }

    public function labelDel()
    {
        $id = $_GET['id']; //必须使用get方法
        $m = M('Shop_label');
        if (!id) {
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

    /**
     * 导出数据为excel表格
     * @param $data    一个二维数组,结构如同从数据库查出来的数组
     * @param $title   excel的第一行标题,一个数组,如果为空则没有标题
     * @param $filename 下载的文件名
     * @examlpe
    $stu = M ('User');
     * $arr = $stu -> select();
     * exportexcel($arr,array('id','账户','密码','昵称'),'文件名!');
     */
    private function exportexcel($data = array(), $title = array(), $filename = 'report')
    {
        header("Content-type:application/octet-stream");
        header("Accept-Ranges:bytes");
        header("Content-type:application/vnd.ms-excel");
        header("Content-Disposition:attachment;filename=" . $filename . ".xls");
        header("Pragma: no-cache");
        header("Expires: 0");
        //导出xls 开始
        if (!empty($title)) {
            foreach ($title as $k => $v) {
                $title[$k] = iconv("UTF-8", "GB2312", $v);
            }
            $title = implode("\t", $title);
            echo "$title\n";
        }
        if (!empty($data)) {
            foreach ($data as $key => $val) {
                foreach ($val as $ck => $cv) {
                    $data[$key][$ck] = iconv("UTF-8", "GB2312", $cv);
                }
                $data[$key] = implode("\t", $data[$key]);

            }
            echo implode("\n", $data);
        }

    }
    // public function adSet(){
    //     $id = I('id');
    //     $m = M('Shop_goods');
    //      if (IS_POST) {
    //         $data = I('post.');
    //         $data['adressid'] = substr($data['adressid'],0,strlen($data['adressid'])-1);
    //         if ($id) {
    //             $data['etime'] = time();
    //             $re = $m->save($data);
    //             if (FALSE !== $re) {
    //                 $info['status'] = 1;
    //                 $info['msg'] = '设置成功！';
    //             } else {
    //                 $info['status'] = 0;
    //                 $info['msg'] = '设置失败！';
    //             }
    //         }else{
    //             $info['status'] = 0;
    //             $info['msg'] = '非法操作';
    //         }
    //         $this->ajaxReturn($info);
    //     }
    //     if ($id) {
    //         $cache = $m->where('id=' . $id)->find();
    //         $this->assign('cache', $cache);
    //     }
    //     $ziti = M('Since')->select();
    //     $this->assign('ziti', $ziti);
    //     $this->display();
    // }
    
    //自提核销
    public function clorder(){
    	//设置面包导航，主加载器请配置
    	$bread = array(
    			'0' => array(
    					'name' => '首页',
    					'url' => U('Admin/Index/index'),
    			),
    			'1' => array(
    					'name' => '商城管理',
    					'url' => U('Admin/Shop/goods'),
    			),
    			'2' => array(
    					'name' => '自提核销',
    					'url' => U('Admin/Shop/clorder'),
    			),
    	);
    	$p = $_GET['p'] ? $_GET['p'] : 1;
    	$m = M('Shop_order');
    	$map['address'] = array('neq','');
    	$map['delivery'] = 'since';
    	$map['ispay'] = 1;
    	$type = I('type', 0 ,'intval');
    	$this->assign('type', $type);
    	$name = I('name') ? I('name') : '';
    	if ($name) {
    		//订单号邦定
    		$map['oid'] = array('like', "%$name%");
    		$this->assign('name', $name);
    	}
    	switch ($type) {
    		case '1'://待发货
    			$map['status'] = 2;
    			break;
    		case '2'://到达自提点
    			$map['status'] = 3;
    			break;
    		case '3'://已自提
    			$map['status'] = 5;
    			break;
    		default://全部
    			$map['status'] = array('in', array(2,3,5));
    			break;
    	}
        $user=self::$CMS['user'];
        if($user["user_type"]==1){
            $map["adminid"]=$user["id"];
        }
    	$date =  I('date') ? I('date') : '';
    	if ($date) {
    		$timeArr = explode(" - ", $date);
    		$map['ctime'] = array('between',array(strtotime($timeArr[0]),strtotime($timeArr[1])+60*60*24));
    	}	$this->assign('date', $date);
    	$psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
    	$cache = $m->where($map)->page($p, $psize)->order('id desc')->select();
    	$count = $m->where($map)->count();
    	$this->getPage($count, $psize, 'App-loader', '自提核销', 'App-search');
    	$this->assign('cache', $cache);
    	$this->display();
    }
    //自提商品到达自提点
    public function pickupDeliver()
    {
    	$id = I('get.id');
    	if (!$id) {
    		$info['status'] = 0;
    		$info['msg'] = '未正常获取ID数据！';
    		$this->ajaxReturn($info);
    	}
    	$order = M('Shop_order')->where('id=' . $id)->find();
    	$map['id'] = $id;
    	$map['delivery'] = 'since';
    	$re = M('Shop_order')->where($map)->setField('status', 3);
    	$mlog = M('Shop_order_log');
    	$mslog = M('Shop_order_syslog');
    	$dwechat = D('Wechat');
    	if (FALSE !== $re) {
    		$log['oid'] = $id;
    		$log['msg'] = '订单已到达自提点';
    		$log['ctime'] = time();
    		$rlog = $mlog->add($log);
    		//后端LOG
    		$log['type'] = 3;
    		$log['paytype'] = $order['paytype'];
    		$rslog = $mslog->add($log);
    		
    		// 插入订单发货模板消息=====================
    		$vip = M('vip')->where(array('id' => $order['vipid']))->find();

    		$data = array();
    		$data['touser'] = $vip['openid'];
    		$data['template_id'] = '1oDtgGzNreAsnFGWahrpW4cwchNxxDkRWWnu19x7WY0';
    		$data['topcolor'] = "#0000FF";
    		$data['data'] = array(
    				'first' => array('value' => '亲爱的用户您的订单商品已到达自提点。'),
    				'keyword1' => array('value' => $order['oid']),
    				'keyword2' => array('value' => $order['vipaddress']),
    				'keyword3' => array('value' => '自提'),
    				'keyword4' => array('value' => $order['payprice'].'元'),
    				'keyword5' => array('value' => date('m-d H:i', $order['ctime'])),
    				'remark' => array('value' => '请尽快把他们领回家哦！感谢您对农牧源的支持！')
    		);
    		$options['appid'] = self::$SYS['set']['wxappid'];
    		$options['appsecret'] = self::$SYS['set']['wxappsecret'];
    			
    		$wx = new \Util\Wx\Wechat($options);
    		$rere = $wx->sendTemplateMessage($data);
    			
    		// 插入订单发货模板消息结束=================
    		$info['status'] = 1;
    		$info['msg'] = '操作成功！';
    	} else {
    		$info['status'] = 0;
    		$info['msg'] = '操作失败！';
    	}
    	$this->ajaxReturn($info);
    }
    
    //自提商品到达自提点
    public function pickupSuccess()
    {
    	$id = I('get.id');
    	if (!$id) {
    		$info['status'] = 0;
    		$info['msg'] = '未正常获取ID数据！';
    	}
    	//判断商城配置
    	if (!self::$CMS['shopset']) {
    		$info['status'] = 0;
    		$info['msg'] = '未正常获取商城配置信息！';
    		$this->ajaxReturn($info);
    	}
    	//判断会员配置
    	if (!self::$CMS['vipset']) {
    		$info['status'] = 0;
    		$info['msg'] = '未正常获取会员配置信息！';
    		$this->ajaxReturn($info);
    	}
    	$order = M('Shop_order')->where('id=' . $id)->find();
    	if (!$order) {
    		$info['status'] = 0;
    		$info['msg'] = '操作失败！';
    		$this->ajaxReturn($info);
    	}
    	$map['id'] = $id;
    	$map['delivery'] = 'since';
    	$re = M('Shop_order')->where($map)->save(array('status'=>5,'pickuptime'=>time()));
    	$mlog = M('Shop_order_log');
    	$mslog = M('Shop_order_syslog');
    	$dwechat = D('Wechat');
    	if (FALSE !== $re) {
    		//修改会员账户金额、经验、积分、等级
    		if (self::$CMS['vipset']['xf_exp'] > 0) {
    			$data_vip['exp'] = array('exp', 'exp+' . round($order['payprice'] * self::$CMS['vipset']['cz_exp'] / 100));
    			$data_vip['cur_exp'] = array('exp', 'cur_exp+' . round($order['payprice'] * self::$CMS['vipset']['cz_exp'] / 100));
    			$level = $this->getLevel(self::$CMS['vipset']['cur_exp'] + round($order['payprice'] * self::$CMS['vipset']['cz_exp'] / 100));
    			$data_vip['levelid'] = $level['levelid'];
    			//会员合计支付
    			$data_vip['total_buy'] = $data_vip['total_buy'] + $order['payprice'];
    		}
    		//获取赠送积分数
    		$score = get_order_credit($order);
    		if($score > 0){
    			$data_vip['score'] = array('exp', 'score+' .$score );
    		}
    		if(!empty($data_vip)) {
    			$re = M('Vip')->where('id='.$order['vipid'])->save($data_vip);
    			if (FALSE === $re) {
    				$this->error('更新会员信息失败！');
    			} else {
    				if($score > 0){
    					log_credit($order['id'], $score, 3, $order['oid']);
    				}
    			}
    		}
            //如果是区域管理员，需要扣取手续费，并将费用转到区域管理员的帐号
            handleAdminFee($order);
    		$log['oid'] = $id;
    		$log['msg'] = '订单自提成功';
    		$log['ctime'] = time();
    		$rlog = $mlog->add($log);
    		//后端LOG
    		$log['type'] = 5;
    		$log['paytype'] = $order['paytype'];
    		$rslog = $mslog->add($log);

    		// 插入订单发货模板消息=====================
    		$vip = M('vip')->where(array('id' => $order['vipid']))->find();
            //判断购物是否是合伙人，不是合伙人则统计配送费用
            handleDeliveryFee($order,$vip);
            if ($vip['groupid']==1) {
                handleCommission($order, $id);//发放分销佣金
            }
    		$tmpitems = unserialize($order['items']);
    		$goodsname = isset($tmpitems[0]) ? ($order['totalnum']>1 ? $tmpitems[0]['name'].'等'.$order['totalnum'].'件' : $tmpitems[0]['name']) : '';

    		$data = array();
    		$data['touser'] = $vip['openid'];
    		$data['template_id'] = 'aSf0Ck3XcS2cNsDRU28CGJ7CJTWjk-cdHoEHakF9Iok';
    		$data['topcolor'] = "#0000FF";
    		$data['data'] = array(
    				'first' => array('value' => '亲爱的用户您的商品已自提成功'),
    				'keyword1' => array('value' => $order['oid']),
    				'keyword2' => array('value' => $goodsname),
    				'keyword3' => array('value' => $order['vipname']),
    				'keyword4' => array('value' => date('Y年m月d日 H:i')),
    				'remark' => array('value' => '感谢您对农牧源的支持！')
    		);
    		$options['appid'] = self::$SYS['set']['wxappid'];
    		$options['appsecret'] = self::$SYS['set']['wxappsecret'];
    		
    		$wx = new \Util\Wx\Wechat($options);
    		$rere = $wx->sendTemplateMessage($data);

    		// 插入订单发货模板消息结束=================
    		$info['status'] = 1;
    		$info['msg'] = '操作成功！';
    	} else {
    		$info['status'] = 0;
    		$info['msg'] = '操作失败！';
    	}
    	$this->ajaxReturn($info);
    }
    public function deliveryList(){
        $bread = array(
            '0' => array(
                'name' => '首页',
                'url' => U('Admin/Index/index'),
            ),
            '1' => array(
                'name' => '配送员列表',
                'url' => U('Admin/Shop/deliveryList'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //绑定搜索条件与分页
        $m = M('deliveryman');
        $p = $_GET['p'] ? $_GET['p'] : 1;

        $name = I('name') ? I('name') : '';
        $map = array();

        if ($name) {
            $map['vipid|code|mobile|realName'] = array('like', "%$name%");
            $this->assign('name', $name);
        }
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $count = $m->where($map)->count();
        $cache = $m->where($map)->page($p, $psize)->order('id desc')->select();
        $this->getPage($count, $psize, 'App-loader', '配送员列表', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }

    /**
     * 自提点配送员设置
     * author: feng
     * create: 2017/9/23 10:32
     */
    public function deliverySet(){
        $id = I('id');
        $m = M('deliveryman');
        if (IS_POST) {
            $data = I('post.');
            $data['adlist'] = substr($data['adressid'],0,strlen($data['adressid'])-1);

            if ($id) {
                if($m->where(array("mobile"=>$data['mobile'],'id'=>array('neq',$id)))->find()){
                    $info['status'] = 0;
                    $info['msg'] = '手机号码已存在，设置失败！';
                    $this->ajaxReturn($info);
                }
                $cache=$m->where(array("id"=>$id))->find();
                if($cache["vipid"]!=$data["vipid"]){
                    $data["uptime"]=time();
                }
                $re = $m->save($data);
                if (FALSE !== $re) {
                    if(!$data["code"]){
                        $code=$id;
                        if(8-count($code)>0){
                            $code=random_str(8-count($code)).$code;
                        }
                        $m->where(array("id"=>$id))->setField("code","d_".$code);
                    }
                    $info['status'] = 1;
                    $info['msg'] = '设置成功！';
                } else {
                    $info['status'] = 0;
                    $info['msg'] = '设置失败！';
                }
            }else{
                if($m->where(array("mobile"=>$data['mobile']))->find()){
                    $info['status'] = 0;
                    $info['msg'] = '手机号码已存在，添加失败！';
                    $this->ajaxReturn($info);
                }
                $data["ctime"]=time();
                if($data["vipid"]&&(!$data["uptime"])){
                    $data["uptime"]=time();
                }
                $re=$m->add($data);
                if($re){
                    if(!$data["code"]){
                        $code=$re;
                        if(8-count($code)>0){
                            $code=random_str(8-count($code)).$code;
                        }
                        $m->where(array("id"=>$re))->setField("code","d_".$code);
                    }
                    $info['status'] = 1;
                    $info['msg'] = '添加成功！';
                }else{
                    $info['status'] = 0;
                    $info['msg'] = '添加失败！';
                }
            }
            $this->ajaxReturn($info);
        }
        if ($id) {
            $cache = $m->where('id=' . $id)->find();
            $this->assign('cache', $cache);
        }
        $province = M('Since')->distinct('province')->field('province')->select();
        $ziti = array();
        foreach($province as $k => $v) {
            $ziti['province'][$k]['id'] = $v['province'];
            $cityids = get_region_child_ids($v['province']);
            if($cityids) {
                $city = M('Since')->where(array('city'=>array('in', $cityids)))->distinct('city')->field('city')->select();
            } else {
                $city = array();
            }
            foreach($city as $kk => $vv) {
                $ziti['province'][$k]['city'][$kk]['id']= $vv['city'];
                $districtids = get_region_child_ids($vv['city']);
                if($districtids) {
                    $district = M('Since')->where(array('district'=>array('in', $districtids)))->distinct('district')->field('district')->select();
                    foreach($district as $kkk => $vvv) {
                        $ziti['province'][$k]['city'][$kk]['district'][$kkk]['id'] = $vvv['district'];
                        $ziti['province'][$k]['city'][$kk]['district'][$kkk]['sincelist'] = M('Since')->where('district='.$vvv['district'])->select();
                    }
                } else {
                    $ziti['province'][$k]['city'][$kk]['district']= array();
                }
            }
        }
        $this->assign('ziti', $ziti['province']);
        $region_list = get_region_list();
        $this->assign('region_list', $region_list);
        $this->display();
    }

    /**
     * 禁用配送员
     * author: feng
     * create: 2017/9/23 16:14
     */
    public function  deliveryStop(){
        $id = I('get.id');
        $status=I("get.status");
        if (!$id) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取ID数据！';
        }
        $m=M('deliveryman');
        $deliveryman = $m->where('id=' . $id)->find();
        if ($deliveryman) {
           if( $m->where('id=' . $id)->save(array("status"=>$status))!==false){
               $info['status'] = 1;
               $info['msg'] = '操作成功！';
           }else{
               $info['status'] = 0;
               $info['msg'] = '操作失败！';
           }

        }else{
            $info['status'] = 0;
            $info['msg'] = '操作失败！';
        }

        $this->ajaxReturn($info);
    }
}